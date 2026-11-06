<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth, DB, HttpException, Tenant};

/** الجدول الدراسي: إدخال يدوي مع كشف تعارض المعلم والقاعة والشعبة (D-04). */
final class TimetableService
{
    public const DAY_NAMES = [0 => 'الأحد', 1 => 'الاثنين', 2 => 'الثلاثاء', 3 => 'الأربعاء', 4 => 'الخميس', 5 => 'الجمعة', 6 => 'السبت'];

    /** الإعدادات: الأيام والحصص (قيم مبدئية قابلة للتعديل). */
    public static function config(): array
    {
        $j = SettingsService::get('timetable.config');
        if ($j && ($c = json_decode($j, true)) && !empty($c['days']) && !empty($c['periods'])) { return $c; }
        $periods = []; $t = 8 * 60;
        for ($i = 1; $i <= 6; $i++) { $periods[] = ['no' => $i, 'start' => sprintf('%02d:%02d', intdiv($t, 60), $t % 60), 'end' => sprintf('%02d:%02d', intdiv($t + 45, 60), ($t + 45) % 60)]; $t += 50; }
        return ['days' => [0, 1, 2, 3, 4], 'periods' => $periods];
    }
    public static function saveConfig(array $days, array $periods): void
    {
        Auth::authorize('timetable.manage');
        $days = array_values(array_unique(array_filter(array_map('intval', $days), fn($d) => $d >= 0 && $d <= 6))); sort($days);
        if (!$days) { throw new HttpException(422, 'اختر يوم دراسة واحدًا على الأقل'); }
        $clean = []; $prevEnd = '00:00';
        foreach (array_values($periods) as $i => $p) {
            $s = (string)($p['start'] ?? ''); $e = (string)($p['end'] ?? '');
            if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $s) || !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $e)) { throw new HttpException(422, 'وقت غير صالح في الحصة ' . ($i + 1)); }
            if ($e <= $s || $s < $prevEnd) { throw new HttpException(422, 'أوقات الحصة ' . ($i + 1) . ' متداخلة أو غير مرتبة'); }
            $clean[] = ['no' => $i + 1, 'start' => $s, 'end' => $e]; $prevEnd = $e;
        }
        if (!$clean || count($clean) > 12) { throw new HttpException(422, 'عدد الحصص من 1 إلى 12'); }
        $max = (int)DB::value('SELECT COALESCE(MAX(period_no),0) FROM timetable_slots WHERE school_id = ?', [Tenant::require()]);
        if ($max > count($clean)) { throw new HttpException(422, "توجد حصص موضوعة في الحصة $max. احذفها أولًا."); }
        $badDay = DB::value('SELECT day_of_week FROM timetable_slots WHERE school_id = ? AND day_of_week NOT IN (' . implode(',', $days) . ') LIMIT 1', [Tenant::require()]);
        if ($badDay !== null) { throw new HttpException(422, 'توجد حصص في يوم ' . self::DAY_NAMES[(int)$badDay] . '. احذفها أولًا.'); }
        SettingsService::set('timetable.config', json_encode(['days' => $days, 'periods' => $clean]));
        AuditService::log('timetable.config', 'school', Tenant::require(), null, ['days' => $days, 'periods' => count($clean)]);
    }

    // ---------- القاعات ----------
    public static function rooms(): array { Auth::authorize('timetable.view'); return DB::all('SELECT * FROM rooms WHERE school_id = ? ORDER BY name', [Tenant::require()]); }
    public static function addRoom(string $name, ?int $capacity): int
    {
        Auth::authorize('timetable.manage');
        if (trim($name) === '') { throw new HttpException(422, 'اسم القاعة مطلوب'); }
        try { DB::query('INSERT INTO rooms (school_id, name, capacity) VALUES (?,?,?)', [Tenant::require(), trim($name), $capacity ?: null]); }
        catch (\PDOException $e) { throw AcademicService::dbError($e, 'اسم القاعة مستخدم'); }
        return DB::lastId();
    }
    public static function deleteRoom(int $id): void
    {
        Auth::authorize('timetable.manage');
        if (DB::value('SELECT 1 FROM timetable_slots WHERE room_id = ? AND school_id = ? LIMIT 1', [$id, Tenant::require()])) { throw new HttpException(409, 'القاعة مستخدمة في الجدول'); }
        DB::query('DELETE FROM rooms WHERE id = ? AND school_id = ?', [$id, Tenant::require()]);
    }

    // ---------- القراءة ----------
    private const SELECT = 'SELECT t.*, sub.name AS subject, st.full_name AS teacher, r.name AS room, sc.name AS section_name, g.name AS grade_name
        FROM timetable_slots t JOIN section_subjects ss ON ss.id = t.section_subject_id JOIN subjects sub ON sub.id = ss.subject_id JOIN staff st ON st.id = t.staff_id
        JOIN sections sc ON sc.id = t.section_id JOIN grade_levels g ON g.id = sc.grade_level_id LEFT JOIN rooms r ON r.id = t.room_id';

    public static function forSection(int $sectionId): array
    {
        Auth::authorize('timetable.view');
        if (!Scope::canSeeSection($sectionId) && !Auth::can('timetable.manage')) { throw new HttpException(403, 'خارج نطاقك'); }
        AcademicService::sectionRaw($sectionId);
        return DB::all(self::SELECT . ' WHERE t.school_id = ? AND t.section_id = ? ORDER BY t.day_of_week, t.period_no', [Tenant::require(), $sectionId]);
    }
    public static function forTeacher(int $staffId, ?int $yearId = null): array
    {
        Auth::authorize('timetable.view');
        if (!Auth::can('timetable.manage') && $staffId !== Scope::staffId()) { throw new HttpException(403, 'يمكنك عرض جدولك فقط'); }
        $yearId ??= (int)(AcademicService::activeYear()['id'] ?? 0);
        return DB::all(self::SELECT . ' WHERE t.school_id = ? AND t.staff_id = ? AND t.academic_year_id = ? ORDER BY t.day_of_week, t.period_no', [Tenant::require(), $staffId, $yearId]);
    }
    /** حصص المستخدم الحالي (إن كان معلمًا) في تاريخ معيّن. */
    public static function today(?string $date = null): array
    {
        $staff = Scope::staffId(); if (!$staff) { return []; }
        $dow = (int)date('w', strtotime($date ?? 'today'));
        return array_values(array_filter(self::forTeacher($staff), fn($s) => (int)$s['day_of_week'] === $dow));
    }

    // ---------- الإدخال وكشف التعارض ----------
    /** يعيد قائمة رسائل التعارض (فارغة = لا تعارض). */
    public static function conflicts(int $yearId, int $sectionId, int $staffId, ?int $roomId, int $day, int $period, ?int $ignoreSlot = null, bool $lock = false): array
    {
        $sid = Tenant::require(); $msgs = []; $ig = $ignoreSlot ? ' AND t.id <> ' . (int)$ignoreSlot : ''; $fu = $lock ? ' FOR UPDATE' : '';
        $q = fn(string $col, int $val) => DB::one(self::SELECT . " WHERE t.school_id = ? AND t.academic_year_id = ? AND t.day_of_week = ? AND t.period_no = ? AND t.$col = ?$ig LIMIT 1$fu", [$sid, $yearId, $day, $period, $val]);
        if ($x = $q('section_id', $sectionId)) { $msgs[] = "الشعبة لديها حصة {$x['subject']} في هذا الوقت"; }
        if ($x = $q('staff_id', $staffId)) { $msgs[] = "المعلم {$x['teacher']} مشغول مع {$x['grade_name']} / {$x['section_name']} ({$x['subject']}) في هذا الوقت"; }
        if ($roomId && ($x = $q('room_id', $roomId))) { $msgs[] = "القاعة {$x['room']} محجوزة لـ {$x['grade_name']} / {$x['section_name']} ({$x['subject']}) في هذا الوقت"; }
        return $msgs;
    }

    public static function place(int $sectionSubjectId, int $day, int $period, ?int $roomId = null, ?int $staffId = null): int
    {
        Auth::authorize('timetable.manage');
        $sid = Tenant::require();
        $ss = DB::one('SELECT ss.*, sc.academic_year_id, sc.id AS sec FROM section_subjects ss JOIN sections sc ON sc.id = ss.section_id WHERE ss.id = ? AND ss.school_id = ?', [$sectionSubjectId, $sid]) ?? throw new HttpException(404, 'المادة غير موجودة');
        AcademicService::assertYearOpen((int)$ss['academic_year_id']);
        $cfg = self::config(); $pr = null; foreach ($cfg['periods'] as $p) { if ($p['no'] === $period) { $pr = $p; } }
        if (!in_array($day, $cfg['days'], true) || !$pr) { throw new HttpException(422, 'اليوم/الحصة خارج إعدادات الجدول'); }
        $teachers = array_map('intval', array_column(DB::all('SELECT staff_id FROM teacher_assignments WHERE section_subject_id = ? AND school_id = ?', [$sectionSubjectId, $sid]), 'staff_id'));
        if (!$teachers) { throw new HttpException(422, 'أسند معلمًا لهذه المادة أولًا (من صفحة الشعبة)'); }
        if ($staffId === null) { if (count($teachers) > 1) { throw new HttpException(422, 'للمادة أكثر من معلم: حدّد المعلم', ['staff_id' => 'مطلوب']); } $staffId = $teachers[0]; }
        elseif (!in_array($staffId, $teachers, true)) { throw new HttpException(422, 'المعلم غير مُسند لهذه المادة'); }
        if ($roomId && !DB::value('SELECT 1 FROM rooms WHERE id = ? AND school_id = ?', [$roomId, $sid])) { throw new HttpException(422, 'قاعة غير صالحة'); }
        return DB::transaction(function () use ($ss, $day, $period, $roomId, $staffId, $pr, $sid, $sectionSubjectId) {
            $c = self::conflicts((int)$ss['academic_year_id'], (int)$ss['sec'], $staffId, $roomId, $day, $period, null, true);
            if ($c) { throw new HttpException(409, implode(' — ', $c)); }
            $placed = (int)DB::value('SELECT COUNT(*) FROM timetable_slots WHERE section_subject_id = ? AND school_id = ?', [$sectionSubjectId, $sid]);
            if ($placed >= (int)$ss['weekly_periods']) { throw new HttpException(422, 'اكتمل عدد الحصص الأسبوعية لهذه المادة (' . (int)$ss['weekly_periods'] . ')'); }
            try { DB::query('INSERT INTO timetable_slots (school_id, academic_year_id, section_subject_id, section_id, staff_id, room_id, day_of_week, period_no, starts_at, ends_at) VALUES (?,?,?,?,?,?,?,?,?,?)',
                [$sid, $ss['academic_year_id'], $sectionSubjectId, $ss['sec'], $staffId, $roomId, $day, $period, $pr['start'], $pr['end']]); }
            catch (\PDOException $e) { throw AcademicService::dbError($e, 'تعارض في هذا الوقت'); }   // خط الدفاع الأخير: القيود الفريدة
            $id = DB::lastId(); AuditService::log('timetable.place', 'timetable_slot', $id, null, ['ss' => $sectionSubjectId, 'day' => $day, 'period' => $period]);
            return $id;
        });
    }
    public static function remove(int $slotId): void
    {
        Auth::authorize('timetable.manage');
        $s = DB::one('SELECT * FROM timetable_slots WHERE id = ? AND school_id = ?', [$slotId, Tenant::require()]) ?? throw new HttpException(404, 'الحصة غير موجودة');
        AcademicService::assertYearOpen((int)$s['academic_year_id']);
        DB::query('DELETE FROM timetable_slots WHERE id = ?', [$slotId]);
        AuditService::log('timetable.remove', 'timetable_slot', $slotId, ['day' => $s['day_of_week'], 'period' => $s['period_no']]);
    }
}
