<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth, DB, HttpException, Tenant};

/** القبول والتسجيل: طلب ← مراجعة ← قبول/رفض ← تسجيل كطالب معتمد (يربط بوحدة الطلاب). */
final class AdmissionService
{
    public const STATUSES = ['new' => 'جديد', 'review' => 'قيد المراجعة', 'accepted' => 'مقبول', 'rejected' => 'مرفوض', 'enrolled' => 'مُسجَّل'];
    private const FLOW = ['new' => ['review', 'accepted', 'rejected'], 'review' => ['new', 'accepted', 'rejected'], 'accepted' => ['review', 'rejected'], 'rejected' => ['review'], 'enrolled' => []];

    public static function stats(): array
    {
        Auth::authorize('admissions.manage'); $out = array_fill_keys(array_keys(self::STATUSES), 0);
        foreach (DB::all('SELECT status, COUNT(*) AS n FROM admission_applications WHERE school_id = ? GROUP BY status', [Tenant::require()]) as $r) { $out[$r['status']] = (int)$r['n']; } return $out;
    }
    public static function search(int $page, int $per, string $q, string $status, ?int $gradeId): array
    {
        Auth::authorize('admissions.manage'); $per = min(100, max(1, $per)); $page = max(1, $page); $w = 'a.school_id = ?'; $p = [Tenant::require()];
        if (isset(self::STATUSES[$status])) { $w .= ' AND a.status = ?'; $p[] = $status; }
        if ($q !== '') { $l = '%' . addcslashes($q, '%_\\') . '%'; $w .= ' AND (a.applicant_name LIKE ? OR a.guardian_name LIKE ? OR a.guardian_phone LIKE ?)'; array_push($p, $l, $l, $l); }
        if ($gradeId) { $w .= ' AND a.grade_level_id = ?'; $p[] = $gradeId; }
        $total = (int)DB::value("SELECT COUNT(*) FROM admission_applications a WHERE $w", $p);
        $rows = DB::all("SELECT a.*, g.name AS grade_name FROM admission_applications a LEFT JOIN grade_levels g ON g.id = a.grade_level_id WHERE $w ORDER BY a.id DESC LIMIT $per OFFSET " . (($page - 1) * $per), $p);
        return ['rows' => $rows, 'meta' => ['page' => $page, 'per_page' => $per, 'total' => $total, 'pages' => (int)ceil($total / $per)]];
    }
    public static function find(int $id): array
    {
        Auth::authorize('admissions.manage');
        $a = DB::one('SELECT a.*, g.name AS grade_name, u.full_name AS decided_name FROM admission_applications a LEFT JOIN grade_levels g ON g.id = a.grade_level_id LEFT JOIN users u ON u.id = a.decided_by WHERE a.id = ? AND a.school_id = ?', [$id, Tenant::require()]) ?? throw new HttpException(404, 'الطلب غير موجود');
        $a['national_id'] = Crypto::decrypt($a['national_id_enc']); unset($a['national_id_enc']);
        $a['documents'] = DB::all('SELECT d.id, d.title, d.file_id, f.original_name, f.size_bytes FROM admission_documents d JOIN files f ON f.id = d.file_id AND f.deleted_at IS NULL WHERE d.application_id = ? ORDER BY d.id', [$id]);
        return $a;
    }
    private static function clean(array $d): array
    {
        $name = trim((string)($d['applicant_name'] ?? '')); if (mb_strlen($name) < 3) { throw new HttpException(422, 'اسم المتقدّم مطلوب', ['applicant_name' => 'مطلوب']); }
        $g = ($d['gender'] ?? '') === '' ? null : (in_array($d['gender'], ['male', 'female'], true) ? $d['gender'] : throw new HttpException(422, 'الجنس غير صالح', ['gender' => 'غير صالح']));
        $bd = ($d['birth_date'] ?? '') === '' ? null : ($d['birth_date']); if ($bd && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $bd)) { throw new HttpException(422, 'تاريخ الميلاد غير صالح', ['birth_date' => 'غير صالح']); }
        $grade = ($d['grade_level_id'] ?? '') === '' ? null : (int)$d['grade_level_id']; if ($grade) { DB::value('SELECT 1 FROM grade_levels WHERE id = ? AND school_id = ?', [$grade, Tenant::require()]) ?? throw new HttpException(422, 'صف غير صالح', ['grade_level_id' => 'غير صالح']); }
        $ph = ($d['guardian_phone'] ?? '') === '' ? null : Phone::normalize((string)$d['guardian_phone']); if ($ph && !preg_match('/^\+\d{9,15}$/', $ph)) { throw new HttpException(422, 'هاتف ولي الأمر غير صالح', ['guardian_phone' => 'غير صالح']); }
        $rel = ImportService::relation((string)($d['guardian_relation'] ?? '')) ?? (($d['guardian_relation'] ?? '') === '' ? null : throw new HttpException(422, 'صلة القرابة غير صالحة'));
        return ['applicant_name' => $name, 'gender' => $g, 'birth_date' => $bd, 'grade_level_id' => $grade, 'guardian_name' => ($d['guardian_name'] ?? '') ?: null, 'guardian_phone' => $ph, 'guardian_relation' => $rel,
            'previous_school' => ($d['previous_school'] ?? '') ?: null, 'address' => ($d['address'] ?? '') ?: null, 'notes' => ($d['notes'] ?? '') ?: null, 'national_id_enc' => Crypto::encrypt(trim((string)($d['national_id'] ?? '')))];
    }
    public static function create(array $d): int
    {
        Auth::authorize('admissions.manage'); $sid = Tenant::require(); $c = self::clean($d);
        if (DB::value("SELECT 1 FROM admission_applications WHERE school_id = ? AND applicant_name = ? AND COALESCE(birth_date,'') = COALESCE(?,'') AND COALESCE(guardian_phone,'') = COALESCE(?,'') AND status NOT IN ('rejected','enrolled')", [$sid, $c['applicant_name'], $c['birth_date'], $c['guardian_phone']])) { throw new HttpException(409, 'يوجد طلب مفتوح لنفس المتقدّم'); }
        $y = AcademicService::activeYear();
        DB::query('INSERT INTO admission_applications (school_id, applicant_name, birth_date, gender, grade_level_id, guardian_name, guardian_phone, guardian_relation, previous_school, address, notes, national_id_enc, academic_year_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [$sid, $c['applicant_name'], $c['birth_date'], $c['gender'], $c['grade_level_id'], $c['guardian_name'], $c['guardian_phone'], $c['guardian_relation'], $c['previous_school'], $c['address'], $c['notes'], $c['national_id_enc'], $y['id'] ?? null]);
        $id = DB::lastId(); AuditService::log('admission.create', 'admission', $id, null, ['name' => $c['applicant_name']]); return $id;
    }
    public static function update(int $id, array $d): void
    {
        $a = self::find($id); if (in_array($a['status'], ['enrolled'], true)) { throw new HttpException(422, 'الطلب مُسجَّل ولا يُعدَّل'); }
        $c = self::clean($d + ['national_id' => $a['national_id']]);
        DB::query('UPDATE admission_applications SET applicant_name=?, birth_date=?, gender=?, grade_level_id=?, guardian_name=?, guardian_phone=?, guardian_relation=?, previous_school=?, address=?, notes=?, national_id_enc=? WHERE id=? AND school_id=?',
            [$c['applicant_name'], $c['birth_date'], $c['gender'], $c['grade_level_id'], $c['guardian_name'], $c['guardian_phone'], $c['guardian_relation'], $c['previous_school'], $c['address'], $c['notes'], $c['national_id_enc'], $id, Tenant::require()]);
        AuditService::log('admission.update', 'admission', $id);
    }
    public static function setStatus(int $id, string $to, ?string $note): void
    {
        $a = self::find($id);
        if (!isset(self::STATUSES[$to]) || !in_array($to, self::FLOW[$a['status']] ?? [], true)) { throw new HttpException(422, 'انتقال غير مسموح من «' . self::STATUSES[$a['status']] . '» إلى «' . (self::STATUSES[$to] ?? $to) . '»'); }
        if ($to === 'rejected' && mb_strlen(trim((string)$note)) < 3) { throw new HttpException(422, 'اذكر سبب الرفض', ['note' => 'مطلوب']); }
        if ($to === 'accepted' && !$a['gender']) { throw new HttpException(422, 'حدّد جنس المتقدّم قبل القبول', ['gender' => 'مطلوب']); }
        DB::query('UPDATE admission_applications SET status = ?, decision_note = ?, decided_by = ?, decided_at = ? WHERE id = ? AND school_id = ?', [$to, $note ? mb_substr($note, 0, 255) : null, Auth::id(), in_array($to, ['accepted', 'rejected'], true) ? now() : null, $id, Tenant::require()]);
        AuditService::log('admission.status', 'admission', $id, ['status' => $a['status']], ['status' => $to, 'note' => $note]);
    }
    /** يحوّل طلبًا مقبولًا إلى طالب فعلي (وولي أمره) في شعبة. كل شيء أو لا شيء. */
    public static function enroll(int $id, int $sectionId): int
    {
        Auth::authorize('admissions.manage'); $a = self::find($id);
        if ($a['status'] !== 'accepted') { throw new HttpException(422, 'يجب أن يكون الطلب مقبولًا'); }
        $sec = DB::one('SELECT * FROM sections WHERE id = ? AND school_id = ?', [$sectionId, Tenant::require()]) ?? throw new HttpException(422, 'شعبة غير صالحة', ['section_id' => 'غير صالحة']);
        if ($a['grade_level_id'] && (int)$a['grade_level_id'] !== (int)$sec['grade_level_id']) { throw new HttpException(422, 'الشعبة لا تتبع الصف المطلوب في الطلب'); }
        return DB::transaction(function () use ($a, $id, $sectionId) {
            $stu = StudentService::create(['full_name' => $a['applicant_name'], 'gender' => $a['gender'], 'birth_date' => $a['birth_date'] ?: '', 'national_id' => $a['national_id'] ?? '', 'section_id' => $sectionId,
                'previous_school' => $a['previous_school'], 'address' => $a['address'], 'admission_date' => date('Y-m-d')]);
            if ($a['guardian_name']) { StudentService::addGuardian($stu, ['full_name' => $a['guardian_name'], 'phone' => $a['guardian_phone'] ?? '', 'relation' => $a['guardian_relation'] ?? 'father', 'is_primary' => 1, 'financially_responsible' => 1, 'create_account' => $a['guardian_phone'] ? 1 : 0]); }
            DB::query("UPDATE admission_applications SET status = 'enrolled', student_id = ? WHERE id = ? AND school_id = ?", [$stu, $id, Tenant::require()]);
            AuditService::log('admission.enroll', 'admission', $id, null, ['student_id' => $stu, 'section' => $sectionId]); return $stu;
        });
    }
    public static function addDocument(int $id, string $title, array $file): int
    {
        $a = self::find($id); if (trim($title) === '') { throw new HttpException(422, 'عنوان الوثيقة مطلوب', ['title' => 'مطلوب']); }
        $fid = FileService::store($file, 'admission', $id); DB::query('INSERT INTO admission_documents (application_id, title, file_id) VALUES (?,?,?)', [$id, trim($title), $fid]); $did = DB::lastId();
        AuditService::log('admission.document', 'admission', $id, null, ['title' => $title]); return $did;
    }
    public static function removeDocument(int $docId): void
    {
        Auth::authorize('admissions.manage');
        $d = DB::one('SELECT d.* FROM admission_documents d JOIN admission_applications a ON a.id = d.application_id WHERE d.id = ? AND a.school_id = ?', [$docId, Tenant::require()]) ?? throw new HttpException(404, 'الوثيقة غير موجودة');
        DB::query('DELETE FROM admission_documents WHERE id = ?', [$docId]); FileService::remove((int)$d['file_id']);
    }
}
