<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth, DB, Env, HttpException, Tenant};

/** الاستيراد الجماعي: رفع ← معاينة بأخطاء كل صف ← اعتماد. أنواع: grades (الصفوف), subjects, sections, staff, students, guardians, scores (الدرجات). */
final class ImportService
{
    public const MAX_ROWS = 3000;
    public const MAX_BYTES = 5 * 1024 * 1024;

    /** الحقل الأساسي => أسماء الأعمدة المقبولة (بعد التوحيد) */
    private const HEADERS = [
        'students' => [
            'full_name' => ['الاسم', 'الاسم الكامل', 'اسم الطالب', 'الاسم الرباعي', 'full_name', 'name'],
            'gender' => ['الجنس', 'النوع', 'gender', 'sex'], 'birth_date' => ['تاريخ الميلاد', 'الميلاد', 'birth_date', 'dob'],
            'national_id' => ['الرقم الوطني', 'الهوية', 'رقم الهوية', 'national_id'], 'student_no' => ['رقم القيد', 'رقم الطالب', 'student_no'],
            'grade' => ['الصف', 'grade'], 'section' => ['الشعبة', 'الفصل', 'section'], 'phone' => ['الهاتف', 'هاتف الطالب', 'phone'],
            'address' => ['العنوان', 'address'], 'guardian_name' => ['ولي الأمر', 'اسم ولي الأمر', 'guardian_name'],
            'guardian_phone' => ['هاتف ولي الأمر', 'جوال ولي الأمر', 'guardian_phone'], 'guardian_relation' => ['صلة القرابة', 'القرابة', 'guardian_relation'],
        ],
        'staff' => [
            'full_name' => ['الاسم', 'الاسم الكامل', 'اسم الموظف', 'full_name'], 'gender' => ['الجنس', 'gender'], 'phone' => ['الهاتف', 'الجوال', 'phone'], 'email' => ['البريد', 'البريد الالكتروني', 'email'],
            'job_title' => ['الوظيفة', 'المسمى الوظيفي', 'job_title'], 'specialization' => ['التخصص', 'specialization'], 'is_teacher' => ['معلم', 'هل هو معلم', 'is_teacher'],
            'employee_no' => ['الرقم الوظيفي', 'employee_no'], 'hire_date' => ['تاريخ التعيين', 'hire_date'], 'national_id' => ['الرقم الوطني', 'national_id'],
        ],
        'guardians' => [
            'student_no' => ['رقم قيد الطالب', 'رقم القيد', 'student_no'], 'full_name' => ['الاسم', 'اسم ولي الامر', 'اسم ولي الأمر', 'full_name'], 'phone' => ['الهاتف', 'الجوال', 'phone'],
            'relation' => ['صلة القرابة', 'القرابة', 'relation'], 'whatsapp' => ['واتساب', 'whatsapp'], 'email' => ['البريد', 'email'], 'is_primary' => ['اساسي', 'أساسي', 'ولي الامر الاساسي', 'is_primary'],
        ],
        'grades' => [
            'stage' => ['المرحلة', 'stage'], 'name' => ['الصف', 'اسم الصف', 'grade', 'name'], 'sort_order' => ['الترتيب', 'order', 'sort_order'], 'next' => ['الصف التالي', 'يرفع الى', 'next'],
        ],
        'subjects' => [
            'name' => ['المادة', 'اسم المادة', 'subject', 'name'], 'code' => ['الرمز', 'رمز المادة', 'code'], 'grades' => ['الصفوف', 'الصف', 'grades'],
        ],
        'sections' => [
            'grade' => ['الصف', 'grade'], 'name' => ['الشعبة', 'اسم الشعبة', 'section', 'name'], 'capacity' => ['السعة', 'العدد الاقصى', 'capacity'], 'gender' => ['النوع', 'نوع الشعبة', 'gender'], 'homeroom' => ['مربي الصف', 'رائد الفصل', 'homeroom'],
        ],
        'scores' => [
            'student_no' => ['رقم القيد', 'رقم الطالب', 'student_no'], 'student_name' => ['اسم الطالب', 'الاسم', 'student_name'], 'subject' => ['المادة', 'subject'], 'term' => ['الفصل الدراسي', 'الفصل', 'term'],
            'title' => ['التقييم', 'عنوان التقييم', 'assessment', 'title'], 'type' => ['نوع التقييم', 'type'], 'max' => ['الدرجة العظمى', 'العظمى', 'max'], 'score' => ['الدرجة', 'score'],
        ],
    ];
    private const REQUIRED = ['students' => ['full_name', 'gender'], 'staff' => ['full_name'], 'guardians' => ['student_no', 'full_name'], 'grades' => ['stage', 'name'], 'subjects' => ['name', 'grades'], 'sections' => ['grade', 'name'], 'scores' => ['student_no', 'subject', 'term', 'title', 'type', 'max', 'score']];
    private const LABELS = [
        'students' => ['الاسم الكامل', 'الجنس', 'تاريخ الميلاد', 'الرقم الوطني', 'رقم القيد', 'الصف', 'الشعبة', 'الهاتف', 'العنوان', 'اسم ولي الأمر', 'هاتف ولي الأمر', 'صلة القرابة'],
        'staff' => ['الاسم الكامل', 'الجنس', 'الهاتف', 'البريد', 'الوظيفة', 'التخصص', 'معلم', 'الرقم الوظيفي', 'تاريخ التعيين'],
        'guardians' => ['رقم قيد الطالب', 'الاسم', 'الهاتف', 'صلة القرابة', 'واتساب', 'البريد', 'أساسي'],
        'grades' => ['المرحلة', 'الصف', 'الترتيب', 'الصف التالي'],
        'subjects' => ['المادة', 'الرمز', 'الصفوف'],
        'sections' => ['الصف', 'الشعبة', 'السعة', 'النوع', 'مربي الصف'],
        'scores' => ['رقم القيد', 'اسم الطالب', 'المادة', 'الفصل الدراسي', 'التقييم', 'نوع التقييم', 'الدرجة العظمى', 'الدرجة'],
    ];
    private const EXAMPLE = [
        'students' => ['أحمد محمد علي', 'ذكر', '2015-03-21', '', '', 'الصف الأول', 'أ', '', 'صنعاء', 'محمد علي', '0771234567', 'أب'],
        'staff' => ['خالد سالم', 'ذكر', '0771234567', 'k@example.com', 'معلم', 'رياضيات', 'نعم', '', '2024-09-01'],
        'guardians' => ['20260001', 'علي أحمد', '0771234567', 'أب', '', '', 'نعم'],
        'grades' => ['الأساسية', 'الأول', 1, 'الثاني'],
        'subjects' => ['الرياضيات', 'MATH', 'الأول، الثاني، الثالث'],
        'sections' => ['الأول', 'أ', 35, 'مختلطة', ''],
        'scores' => ['20260001', 'أحمد محمد', 'الرياضيات', 'الفصل الأول', 'اختبار شهري 1', 'أعمال الفصل', 20, 18],
    ];
    /** بالترتيب الموصى به للإعداد الأول. */
    public const KINDS = ['grades' => 'الصفوف والمراحل', 'subjects' => 'المواد', 'sections' => 'الشعب', 'staff' => 'الموظفون والمعلمون', 'students' => 'الطلاب', 'guardians' => 'أولياء الأمور', 'scores' => 'الدرجات'];
    public const HINTS = ['grades' => 'المرحلة والصف وترتيبه والصف الذي يُرفَّع إليه.', 'subjects' => 'المادة وصفوفها (يمكن عدة صفوف مفصولة بـ «،»).', 'sections' => 'شعب السنة الفعّالة لكل صف.',
        'staff' => 'بيانات الموظفين؛ «معلم: نعم» يجعله قابلًا للإسناد.', 'students' => 'الطالب وصفه وشعبته وولي أمره.', 'guardians' => 'أولياء أمور إضافيون برقم قيد الطالب.', 'scores' => 'درجات تقييم لكل طالب ومادة وفصل؛ «غ» للغائب.'];

    public static function template(string $kind): array
    {
        self::kind($kind); return [self::LABELS[$kind], self::EXAMPLE[$kind], self::lists($kind)];
    }
    /** قوائم منسدلة للقالب: [رقم العمود => ['values'=>…, 'multi'=>bool]] من بيانات المدرسة الحالية. */
    public static function lists(string $kind): array
    {
        $sid = Tenant::require(); $col = fn(string $f) => array_search($f, array_keys(self::HEADERS[$kind]), true);
        $grades = array_column(DB::all('SELECT g.name FROM grade_levels g LEFT JOIN stages s ON s.id = g.stage_id WHERE g.school_id = ? ORDER BY s.sort_order, g.sort_order', [$sid]), 'name');
        $yes = ['values' => ['نعم', 'لا']]; $gender = ['values' => ['ذكر', 'أنثى']];
        $year = AcademicService::activeYear();
        return array_filter(match ($kind) {
            'students' => [$col('gender') => $gender, $col('grade') => ['values' => $grades], $col('section') => ['values' => $year ? array_column(DB::all('SELECT DISTINCT name FROM sections WHERE school_id = ? AND academic_year_id = ? ORDER BY name', [$sid, $year['id']]), 'name') : []], $col('guardian_relation') => ['values' => ['أب', 'أم', 'أخ', 'أخت', 'عم', 'خال', 'جد', 'جدة', 'آخر']]],
            'staff' => [$col('gender') => $gender, $col('is_teacher') => $yes, $col('job_title') => ['values' => \App\Core\Lookup::JOBS], $col('specialization') => ['values' => \App\Core\Lookup::SPECIALIZATIONS]],
            'guardians' => [$col('relation') => ['values' => ['أب', 'أم', 'أخ', 'أخت', 'عم', 'خال', 'جد', 'جدة', 'آخر']], $col('is_primary') => $yes],
            'grades' => [$col('stage') => ['values' => array_column(DB::all('SELECT name FROM stages WHERE school_id = ? ORDER BY sort_order', [$sid]), 'name') ?: ['رياض الأطفال', 'الأساسية', 'الثانوية']], $col('next') => ['values' => $grades]],
            'subjects' => [$col('grades') => ['values' => $grades, 'multi' => true]],
            'sections' => [$col('grade') => ['values' => $grades], $col('gender') => ['values' => ['مختلطة', 'بنين', 'بنات']], $col('homeroom') => ['values' => array_column(DB::all('SELECT full_name FROM staff WHERE school_id = ? AND deleted_at IS NULL AND is_teacher = 1 ORDER BY full_name', [$sid]), 'full_name')]],
            'scores' => [$col('subject') => ['values' => array_column(DB::all('SELECT name FROM subjects WHERE school_id = ? ORDER BY name', [$sid]), 'name')], $col('term') => ['values' => $year ? array_column(DB::all('SELECT name FROM terms WHERE academic_year_id = ? ORDER BY term_no', [$year['id']]), 'name') : []],
                $col('type') => ['values' => array_column(DB::all('SELECT DISTINCT name FROM assessment_types WHERE school_id = ? ORDER BY name', [$sid]), 'name')], $col('score') => ['values' => ['غ']]],
            default => [],
        }, fn($x) => !empty($x['values']));
    }
    /** يفكّ حقلًا متعدد القيم: «الأول، الثاني / الثالث» → [..] */
    public static function multi(string $v): array { return array_values(array_filter(array_map('trim', preg_split('/[،,;\/|\n]+/u', $v) ?: []), fn($x) => $x !== '')); }
    private static function kind(string $k): void { if (!isset(self::HEADERS[$k])) { throw new HttpException(422, 'نوع استيراد غير معروف'); } }
    private static function perm(string $kind): void
    {
        Auth::authorize('import.run');
        Auth::authorize(['students' => 'students.manage', 'staff' => 'staff.manage', 'guardians' => 'guardians.manage', 'grades' => 'academic.manage', 'subjects' => 'academic.manage', 'sections' => 'academic.manage', 'scores' => 'grades.edit'][$kind]);
    }

    // ---------- الرفع والمعاينة ----------
    /** $file: عنصر من $_FILES. */
    public static function upload(string $kind, array $file): int
    {
        self::kind($kind); self::perm($kind);
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) { throw new HttpException(422, 'تعذّر رفع الملف'); }
        if ($file['size'] > self::MAX_BYTES) { throw new HttpException(422, 'حجم الملف يتجاوز 5MB'); }
        $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['csv', 'xlsx'], true)) { throw new HttpException(422, 'الصيغ المقبولة: xlsx أو csv'); }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $okMime = $ext === 'xlsx' ? in_array($mime, ['application/zip', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/octet-stream'], true) : str_starts_with((string)$mime, 'text/') || $mime === 'application/csv' || $mime === 'application/octet-stream';
        if (!$okMime) { throw new HttpException(422, 'نوع الملف لا يطابق امتداده'); }
        $dir = storage_path('uploads/imports'); if (!is_dir($dir)) { mkdir($dir, 0770, true); }
        $stored = bin2hex(random_bytes(16)) . '.' . $ext; $dest = "$dir/$stored";
        if (!(is_uploaded_file($file['tmp_name']) ? move_uploaded_file($file['tmp_name'], $dest) : copy($file['tmp_name'], $dest))) { throw new HttpException(500, 'تعذّر حفظ الملف'); }
        try { $rows = TabularFile::read($dest, $ext); } catch (\Throwable $e) { @unlink($dest); throw new HttpException(422, 'تعذّرت قراءة الملف: ' . $e->getMessage()); }
        if (count($rows) < 2) { @unlink($dest); throw new HttpException(422, 'الملف لا يحتوي صفوف بيانات'); }
        if (count($rows) - 1 > self::MAX_ROWS) { @unlink($dest); throw new HttpException(422, 'الحد الأقصى ' . self::MAX_ROWS . ' صف في الملف الواحد'); }
        [$map, $missing] = self::mapHeaders($kind, $rows[0]);
        if ($missing) { @unlink($dest); throw new HttpException(422, 'أعمدة مطلوبة ناقصة: ' . implode('، ', array_map(fn($f) => self::LABELS[$kind][array_search($f, array_keys(self::HEADERS[$kind]), true)] ?? $f, $missing))); }
        $sid = Tenant::require();
        return DB::transaction(function () use ($kind, $file, $stored, $rows, $map, $sid) {
            DB::query("INSERT INTO import_jobs (school_id, user_id, kind, original_name, stored_name, status, total_rows) VALUES (?,?,?,?,?, 'previewed', ?)", [$sid, Auth::id(), $kind, mb_substr((string)$file['name'], 0, 255), $stored, count($rows) - 1]);
            $job = DB::lastId(); $ctx = self::context($kind); $ok = $bad = 0; $seen = [];
            foreach (array_slice($rows, 1, null, true) as $i => $r) {
                $data = []; foreach ($map as $field => $col) { $data[$field] = trim((string)($r[$col] ?? '')); }
                [$clean, $errors] = self::validate($kind, $data, $ctx, $seen);
                DB::query('INSERT INTO import_rows (job_id, row_no, data, status, errors) VALUES (?,?,?,?,?)', [$job, $i + 1, json_encode($clean, JSON_UNESCAPED_UNICODE), $errors ? 'error' : 'ok', $errors ? json_encode($errors, JSON_UNESCAPED_UNICODE) : null]);
                $errors ? $bad++ : $ok++;
            }
            DB::query('UPDATE import_jobs SET ok_rows = ?, error_rows = ? WHERE id = ?', [$ok, $bad, $job]);
            AuditService::log('import.upload', 'import_job', $job, null, ['kind' => $kind, 'rows' => $ok + $bad, 'errors' => $bad]);
            return $job;
        });
    }

    /** يربط رؤوس الملف بالحقول. يعيد [field=>colIndex, missingRequired[]]. */
    private static function mapHeaders(string $kind, array $header): array
    {
        $norm = array_map(fn($h) => TabularFile::norm((string)$h), $header); $map = [];
        foreach (self::HEADERS[$kind] as $field => $aliases) {
            foreach ($aliases as $a) { $i = array_search(TabularFile::norm($a), $norm, true); if ($i !== false) { $map[$field] = $i; break; } }
        }
        return [$map, array_values(array_diff(self::REQUIRED[$kind], array_keys($map)))];
    }

    /** بيانات مرجعية تُحمَّل مرة واحدة: الشعب، أرقام القيد والهويات الموجودة. */
    private static function context(string $kind): array
    {
        $sid = Tenant::require(); $ctx = ['sections' => [], 'student_nos' => [], 'nids' => [], 'emails' => [], 'phones' => [], 'year' => AcademicService::activeYear()];
        if ($kind === 'students' && $ctx['year']) {
            foreach (DB::all('SELECT sc.id, sc.name, g.name AS grade, sc.gender, sc.capacity FROM sections sc JOIN grade_levels g ON g.id = sc.grade_level_id WHERE sc.school_id = ? AND sc.academic_year_id = ?', [$sid, $ctx['year']['id']]) as $s) {
                $ctx['sections'][self::gradeKey($s['grade']) . '|' . TabularFile::norm($s['name'])] = $s; $ctx['byGrade'][self::gradeKey($s['grade'])][] = $s;
            }
        }
        if (in_array($kind, ['students', 'guardians'], true)) { foreach (DB::all('SELECT student_no FROM students WHERE school_id = ? AND deleted_at IS NULL', [$sid]) as $r) { $ctx['student_nos'][$r['student_no']] = true; } }
        if ($kind === 'students') {
            foreach (DB::all("SELECT i.national_id_hash FROM students s JOIN student_identities i ON i.id = s.identity_id WHERE s.school_id = ? AND s.status = 'active' AND s.deleted_at IS NULL AND i.national_id_hash IS NOT NULL", [$sid]) as $r) { $ctx['nids'][$r['national_id_hash']] = true; }
        }
        if (in_array($kind, ['grades', 'subjects', 'sections'], true)) {
            foreach (DB::all('SELECT id, name FROM grade_levels WHERE school_id = ?', [$sid]) as $g) { $ctx['grades'][self::gradeKey($g['name'])] = (int)$g['id']; }
            foreach (DB::all('SELECT id, name FROM subjects WHERE school_id = ?', [$sid]) as $x) { $ctx['subjects'][TabularFile::norm($x['name'])] = (int)$x['id']; }
            foreach (DB::all('SELECT id, full_name FROM staff WHERE school_id = ? AND deleted_at IS NULL AND is_teacher = 1', [$sid]) as $x) { $ctx['teachers'][TabularFile::norm($x['full_name'])] = (int)$x['id']; }
            if ($ctx['year']) { foreach (DB::all('SELECT sc.name, sc.grade_level_id FROM sections sc WHERE sc.school_id = ? AND sc.academic_year_id = ?', [$sid, $ctx['year']['id']]) as $x) { $ctx['sections_by'][$x['grade_level_id'] . '|' . TabularFile::norm($x['name'])] = true; } }
        }
        if ($kind === 'scores' && $ctx['year']) {
            foreach (DB::all("SELECT st.student_no, e.id AS enrollment_id, e.section_id FROM students st JOIN student_enrollments e ON e.student_id = st.id AND e.academic_year_id = ? AND e.status = 'active' WHERE st.school_id = ? AND st.deleted_at IS NULL", [$ctx['year']['id'], $sid]) as $x) { $ctx['enr'][(string)$x['student_no']] = $x; }
            foreach (DB::all('SELECT ss.id, ss.section_id, ss.term_id, ss.is_elective, sub.name FROM section_subjects ss JOIN subjects sub ON sub.id = ss.subject_id JOIN sections sc ON sc.id = ss.section_id WHERE ss.school_id = ? AND sc.academic_year_id = ?', [$sid, $ctx['year']['id']]) as $x) { $ctx['ss'][$x['section_id'] . '|' . TabularFile::norm($x['name'])] = $x; }
            foreach (DB::all('SELECT id, name, term_no, grades_locked FROM terms WHERE academic_year_id = ? AND school_id = ?', [$ctx['year']['id'], $sid]) as $x) { $ctx['terms'][TabularFile::norm($x['name'])] = $x; $ctx['terms'][(string)$x['term_no']] = $x; }
            foreach (DB::all('SELECT t.id, t.name, t.scheme_id FROM assessment_types t WHERE t.school_id = ?', [$sid]) as $x) { $ctx['types'][TabularFile::norm($x['name'])][] = $x; }
        }
        if ($kind === 'staff') {
            foreach (DB::all('SELECT email, phone FROM users WHERE email IS NOT NULL OR phone IS NOT NULL') as $r) { if ($r['email']) { $ctx['emails'][$r['email']] = true; } if ($r['phone']) { $ctx['phones'][$r['phone']] = true; } }
        }
        return $ctx;
    }

    private static function validate(string $kind, array $d, array $ctx, array &$seen): array
    {
        $e = [];
        foreach (self::REQUIRED[$kind] as $f) { if (($d[$f] ?? '') === '') { $e[] = 'الحقل «' . (self::LABELS[$kind][array_search($f, array_keys(self::HEADERS[$kind]), true)] ?? $f) . '» مطلوب'; } }
        if ($kind !== 'sections' && isset($d['gender']) && $d['gender'] !== '') { $g = self::gender($d['gender']); if (!$g) { $e[] = 'الجنس غير مفهوم (استخدم ذكر/أنثى)'; } else { $d['gender'] = $g; } }
        foreach (['birth_date', 'hire_date'] as $df) { if (($d[$df] ?? '') !== '') { $x = self::date($d[$df]); if (!$x) { $e[] = 'تاريخ غير صالح في «' . $df . '» (استخدم YYYY-MM-DD)'; } else { $d[$df] = $x; } } }
        foreach (['phone', 'guardian_phone', 'whatsapp'] as $pf) { if (($d[$pf] ?? '') !== '') { $n = Phone::normalize($d[$pf]); if (!preg_match('/^\+\d{9,15}$/', $n)) { $e[] = 'رقم هاتف غير صالح'; } else { $d[$pf] = $n; } } }
        if (($d['email'] ?? '') !== '' && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) { $e[] = 'بريد إلكتروني غير صالح'; }
        $rel = $d['guardian_relation'] ?? $d['relation'] ?? '';
        if ($rel !== '') { $r = self::relation($rel); if (!$r) { $e[] = 'صلة القرابة غير مفهومة'; } else { $d[isset($d['relation']) ? 'relation' : 'guardian_relation'] = $r; } }
        if ($kind === 'students') {
            if (($d['grade'] ?? '') !== '' || ($d['section'] ?? '') !== '') {
                $sec = self::resolveSection($ctx, $d['grade'] ?? '', $d['section'] ?? '');
                if (!$ctx['year']) { $e[] = 'لا توجد سنة دراسية فعّالة لتسجيل الطالب في شعبة'; }
                elseif (!$sec) { $e[] = 'الصف/الشعبة غير موجودة في السنة الفعّالة'; }
                else {
                    $d['section_id'] = (int)$sec['id'];
                    $g = $d['gender'] ?? null; if ($g && (($sec['gender'] === 'boys' && $g === 'female') || ($sec['gender'] === 'girls' && $g === 'male'))) { $e[] = 'جنس الطالب لا يطابق نوع الشعبة'; }
                }
            }
            if (($d['student_no'] ?? '') !== '') {
                if (isset($ctx['student_nos'][$d['student_no']])) { $e[] = 'رقم القيد موجود مسبقًا'; }
                elseif (isset($seen['no'][$d['student_no']])) { $e[] = 'رقم القيد مكرر في الملف (الصف ' . $seen['no'][$d['student_no']] . ')'; }
                else { $seen['no'][$d['student_no']] = count($seen['rows'] ?? []) + 2; }
            }
            if (($d['national_id'] ?? '') !== '') {
                $h = hash_hmac('sha256', $d['national_id'], (string)Env::get('APP_ENC_KEY', 'k'));
                if (isset($ctx['nids'][$h])) { $e[] = 'الرقم الوطني مسجّل لطالب فعّال'; } elseif (isset($seen['nid'][$h])) { $e[] = 'الرقم الوطني مكرر في الملف'; } else { $seen['nid'][$h] = true; }
            }
            if (($d['guardian_phone'] ?? '') !== '' && ($d['guardian_name'] ?? '') === '') { $e[] = 'هاتف ولي الأمر بلا اسم'; }
        }
        if ($kind === 'staff') {
            $t = $d['is_teacher'] ?? ''; $d['is_teacher'] = in_array(TabularFile::norm($t), ['نعم', 'yes', '1', 'true', 'معلم', 'y'], true) ? 1 : 0;
            if (($d['email'] ?? '') !== '' && isset($ctx['emails'][mb_strtolower($d['email'])])) { $e[] = 'البريد مستخدم'; }
        }
        if ($kind === 'guardians') {
            if (($d['student_no'] ?? '') !== '' && !isset($ctx['student_nos'][$d['student_no']])) { $e[] = 'لا يوجد طالب بهذا الرقم'; }
            $d['is_primary'] = in_array(TabularFile::norm((string)($d['is_primary'] ?? '')), ['نعم', 'yes', '1', 'true', 'y'], true) ? 1 : 0;
        }
        if ($kind === 'grades') {
            $k = self::gradeKey($d['name'] ?? ''); if ($k !== '' && isset($ctx['grades'][$k])) { $e[] = 'الصف «' . $d['name'] . '» موجود مسبقًا'; }
            elseif ($k !== '' && isset($seen['grade'][$k])) { $e[] = 'الصف مكرر في الملف'; } else { $seen['grade'][$k] = true; }
            if (($d['sort_order'] ?? '') !== '' && !ctype_digit((string)$d['sort_order'])) { $e[] = 'الترتيب يجب أن يكون رقمًا'; }
            if (($d['next'] ?? '') !== '' && self::gradeKey($d['next']) === $k) { $e[] = 'الصف التالي لا يكون الصف نفسه'; }
        }
        if ($kind === 'subjects') {
            $ids = []; foreach (self::multi($d['grades'] ?? '') as $g) { $gid = $ctx['grades'][self::gradeKey($g)] ?? null; if (!$gid) { $e[] = 'الصف «' . $g . '» غير موجود (استورد الصفوف أولًا)'; } else { $ids[] = $gid; } }
            $d['grade_ids'] = array_values(array_unique($ids)); $d['exists'] = isset($ctx['subjects'][TabularFile::norm($d['name'] ?? '')]);
            if (isset($seen['subject'][TabularFile::norm($d['name'] ?? '')])) { $e[] = 'المادة مكررة في الملف'; } else { $seen['subject'][TabularFile::norm($d['name'] ?? '')] = true; }
        }
        if ($kind === 'sections') {
            $gid = $ctx['grades'][self::gradeKey($d['grade'] ?? '')] ?? null;
            if (!$ctx['year']) { $e[] = 'لا توجد سنة دراسية فعّالة'; }
            if (!$gid) { $e[] = 'الصف «' . ($d['grade'] ?? '') . '» غير موجود'; } else { $d['grade_level_id'] = $gid; $key = $gid . '|' . TabularFile::norm($d['name'] ?? ''); if (isset($ctx['sections_by'][$key])) { $e[] = 'الشعبة موجودة مسبقًا لهذا الصف في السنة الفعّالة'; } elseif (isset($seen['sec'][$key])) { $e[] = 'الشعبة مكررة في الملف'; } else { $seen['sec'][$key] = true; } }
            if (($d['capacity'] ?? '') !== '' && (!ctype_digit((string)$d['capacity']) || (int)$d['capacity'] < 1)) { $e[] = 'السعة رقم صحيح موجب'; }
            $gm = ['مختلطه' => 'mixed', 'مختلطة' => 'mixed', 'mixed' => 'mixed', 'بنين' => 'boys', 'boys' => 'boys', 'بنات' => 'girls', 'girls' => 'girls', '' => 'mixed'];
            $gv = TabularFile::norm($d['gender'] ?? ''); if (!isset($gm[$gv]) && !isset($gm[$d['gender'] ?? ''])) { $e[] = 'نوع الشعبة: مختلطة أو بنين أو بنات'; } else { $d['gender'] = $gm[$gv] ?? $gm[$d['gender']]; }
            if (($d['homeroom'] ?? '') !== '') { $t = $ctx['teachers'][TabularFile::norm($d['homeroom'])] ?? null; if (!$t) { $e[] = 'مربي الصف غير موجود بين المعلمين'; } else { $d['homeroom_staff_id'] = $t; } }
        }
        if ($kind === 'scores') {
            if (!$ctx['year']) { $e[] = 'لا توجد سنة دراسية فعّالة'; }
            $en = $ctx['enr'][(string)($d['student_no'] ?? '')] ?? null; if (!$en) { $e[] = 'لا يوجد طالب مسجّل بهذا الرقم في السنة الفعّالة'; }
            $term = $ctx['terms'][TabularFile::norm($d['term'] ?? '')] ?? null; if (!$term) { $e[] = 'الفصل الدراسي غير موجود في السنة الفعّالة'; } elseif ($term['grades_locked']) { $e[] = 'درجات هذا الفصل مقفلة'; }
            $ss = $en ? ($ctx['ss'][$en['section_id'] . '|' . TabularFile::norm($d['subject'] ?? '')] ?? null) : null;
            if ($en && !$ss) { $e[] = 'المادة غير مقررة على شعبة الطالب'; } elseif ($ss && $term && $ss['term_id'] && (int)$ss['term_id'] !== (int)$term['id']) { $e[] = 'المادة مقررة في فصل آخر حسب الخطة'; }
            if (!isset($ctx['types'][TabularFile::norm($d['type'] ?? '')])) { $e[] = 'نوع التقييم غير معروف (استخدم أسماء أنواع التقييم في نظام الدرجات)'; }
            $max = str_replace(',', '.', (string)($d['max'] ?? '')); if (!is_numeric($max) || (float)$max <= 0) { $e[] = 'الدرجة العظمى رقم موجب'; }
            $sc = trim((string)($d['score'] ?? '')); $absent = in_array(TabularFile::norm($sc), ['غ', 'غائب', 'absent', 'a'], true);
            if (!$absent) { $sc = str_replace(',', '.', $sc); if (!is_numeric($sc)) { $e[] = 'الدرجة رقم أو «غ» للغائب'; } elseif (is_numeric($max) && ((float)$sc < 0 || (float)$sc > (float)$max)) { $e[] = 'الدرجة خارج المدى (0 – ' . (float)$max . ')'; } }
            $d['absent'] = $absent ? 1 : 0; $d['score'] = $absent ? null : $sc; $d['max'] = $max;
            if ($en && $ss && $term) {
                $d['enrollment_id'] = (int)$en['enrollment_id']; $d['section_subject_id'] = (int)$ss['id']; $d['term_id'] = (int)$term['id'];
                $k = $d['enrollment_id'] . '|' . $d['section_subject_id'] . '|' . $d['term_id'] . '|' . TabularFile::norm($d['title'] ?? '');
                if (isset($seen['score'][$k])) { $e[] = 'درجة مكررة لنفس الطالب والتقييم (الصف ' . $seen['score'][$k] . ')'; } else { $seen['score'][$k] = count($seen['rows'] ?? []) + 2; }
            }
        }
        $seen['rows'][] = 1;
        return [$d, $e];
    }

    private static function resolveSection(array $ctx, string $grade, string $section): ?array
    {
        $g = self::gradeKey($grade); $s = TabularFile::norm($section);
        if ($g !== '' && $s !== '') { return $ctx['sections']["$g|$s"] ?? null; }
        if ($g !== '') { $c = $ctx['byGrade'][$g] ?? []; return count($c) === 1 ? $c[0] : null; }
        $m = array_filter($ctx['sections'], fn($x) => TabularFile::norm($x['name']) === $s); return count($m) === 1 ? array_values($m)[0] : null;
    }
    /** اسم الصف للمطابقة: يتجاهل كلمة «الصف» في البداية («الصف الأول» = «الأول»). */
    private static function gradeKey(string $g): string { return preg_replace('/^الصف\s+/u', '', TabularFile::norm($g)) ?? ''; }
    public static function gender(string $v): ?string
    {
        return match (TabularFile::norm($v)) { 'ذكر', 'ولد', 'male', 'm', 'ذ' => 'male', 'انثي', 'انثى', 'بنت', 'female', 'f', 'ا' => 'female', default => null };
    }
    public static function relation(string $v): ?string
    {
        return match (TabularFile::norm($v)) { 'اب', 'father' => 'father', 'ام', 'mother' => 'mother', 'اخ', 'brother' => 'brother', 'اخت', 'sister' => 'sister', 'عم', 'خال', 'uncle' => 'uncle', 'عمه', 'خاله', 'aunt' => 'aunt',
            'جد', 'grandfather' => 'grandfather', 'جده', 'grandmother' => 'grandmother', 'اخر', 'other', 'قريب' => 'other', default => null };
    }
    /** يقبل YYYY-MM-DD و D/M/YYYY و D-M-YYYY ورقم Excel التسلسلي. */
    public static function date(string $v): ?string
    {
        $v = trim($v);
        if (preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $v)) { [$y, $m, $d] = array_map('intval', explode('-', $v)); }
        elseif (preg_match('/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})$/', $v, $mt)) { [$d, $m, $y] = [(int)$mt[1], (int)$mt[2], (int)$mt[3]]; }
        elseif (preg_match('/^\d{5}(\.\d+)?$/', $v)) { $ts = (int)(((float)$v - 25569) * 86400); return date('Y-m-d', $ts); }
        else { return null; }
        return checkdate($m, $d, $y) && $y >= 1900 ? sprintf('%04d-%02d-%02d', $y, $m, $d) : null;
    }

    // ---------- القراءة ----------
    public static function job(int $id): array
    {
        Auth::authorize('import.run');
        return DB::one('SELECT * FROM import_jobs WHERE id = ? AND school_id = ?', [$id, Tenant::require()]) ?? throw new HttpException(404, 'عملية الاستيراد غير موجودة');
    }
    public static function jobs(): array
    {
        Auth::authorize('import.run');
        return DB::all('SELECT j.*, u.full_name AS user_name FROM import_jobs j LEFT JOIN users u ON u.id = j.user_id WHERE j.school_id = ? ORDER BY j.id DESC LIMIT 30', [Tenant::require()]);
    }
    public static function rows(int $jobId, bool $onlyErrors = false, int $limit = 200): array
    {
        self::job($jobId);
        $rows = DB::all('SELECT * FROM import_rows WHERE job_id = ? ' . ($onlyErrors ? "AND status = 'error' " : '') . 'ORDER BY row_no LIMIT ' . (int)$limit, [$jobId]);
        foreach ($rows as &$r) { $r['data'] = json_decode($r['data'], true); $r['errors'] = $r['errors'] ? json_decode($r['errors'], true) : []; }
        return $rows;
    }

    // ---------- الاعتماد ----------
    /** ينفّذ الصفوف السليمة فقط؛ فشل صف لا يوقف البقية ولا يلغي ما قبله. */
    public static function commit(int $jobId, bool $createGuardianAccounts = true): array
    {
        $job = self::job($jobId); self::perm($job['kind']);
        if ($job['status'] !== 'previewed') { throw new HttpException(422, 'تم اعتماد هذه العملية مسبقًا'); }
        @set_time_limit(300);
        DB::query("UPDATE import_jobs SET status = 'committed' WHERE id = ? AND status = 'previewed'", [$jobId]);   // قفل ضد الاعتماد المزدوج
        $done = 0; $failed = 0;
        foreach (DB::all("SELECT id, data FROM import_rows WHERE job_id = ? AND status = 'ok' ORDER BY row_no", [$jobId]) as $row) {
            $d = json_decode($row['data'], true);
            try {
                DB::transaction(function () use ($job, $d, $createGuardianAccounts) {
                    match ($job['kind']) {
                        'students' => self::importStudent($d, $createGuardianAccounts), 'staff' => self::importStaff($d), 'guardians' => self::importGuardian($d, $createGuardianAccounts),
                        'grades' => self::importGrade($d), 'subjects' => self::importSubject($d), 'sections' => self::importSection($d), 'scores' => self::importScore($d),
                    };
                });
                DB::query("UPDATE import_rows SET status = 'imported' WHERE id = ?", [$row['id']]); $done++;
            } catch (HttpException $e) {
                DB::query("UPDATE import_rows SET status = 'error', errors = ? WHERE id = ?", [json_encode([$e->getMessage()], JSON_UNESCAPED_UNICODE), $row['id']]); $failed++;
            }
        }
        if ($job['kind'] === 'grades') { self::linkNextGrades($jobId); }   // «الصف التالي» قد يرد في سطر لاحق
        $err = (int)DB::value("SELECT COUNT(*) FROM import_rows WHERE job_id = ? AND status = 'error'", [$jobId]);
        DB::query('UPDATE import_jobs SET ok_rows = ?, error_rows = ? WHERE id = ?', [$done, $err, $jobId]);
        AuditService::log('import.commit', 'import_job', $jobId, null, ['kind' => $job['kind'], 'imported' => $done, 'failed_at_commit' => $failed, 'errors' => $err]);
        @unlink(storage_path('uploads/imports/' . $job['stored_name']));   // لا نحتفظ بالملف الأصلي بعد الاعتماد (بيانات شخصية)
        return ['imported' => $done, 'errors' => $err];
    }
    public static function discard(int $jobId): void
    {
        $job = self::job($jobId);
        if ($job['status'] === 'committed') { throw new HttpException(422, 'لا يمكن حذف عملية معتمدة'); }
        @unlink(storage_path('uploads/imports/' . $job['stored_name']));
        DB::query('DELETE FROM import_jobs WHERE id = ? AND school_id = ?', [$jobId, Tenant::require()]);
    }

    private static function importStudent(array $d, bool $accounts): void
    {
        $id = StudentService::create($d);
        if (($d['guardian_name'] ?? '') !== '') {
            StudentService::addGuardian($id, ['full_name' => $d['guardian_name'], 'phone' => $d['guardian_phone'] ?? '', 'relation' => $d['guardian_relation'] ?? 'father', 'is_primary' => 1, 'create_account' => $accounts && ($d['guardian_phone'] ?? '') !== '' ? 1 : 0]);
        }
    }
    private static function importStaff(array $d): void { StaffService::create($d); }
    private static function importGuardian(array $d, bool $accounts): void
    {
        $sid = (int)DB::value('SELECT id FROM students WHERE school_id = ? AND student_no = ? AND deleted_at IS NULL', [Tenant::require(), $d['student_no']]);
        if (!$sid) { throw new HttpException(422, 'الطالب غير موجود'); }
        StudentService::addGuardian($sid, ['full_name' => $d['full_name'], 'phone' => $d['phone'] ?? '', 'whatsapp' => $d['whatsapp'] ?? '', 'email' => $d['email'] ?? '', 'relation' => $d['relation'] ?? 'father', 'is_primary' => $d['is_primary'] ?? 0, 'create_account' => $accounts && ($d['phone'] ?? '') !== '' ? 1 : 0]);
    }

    private static function importGrade(array $d): void
    {
        $sid = Tenant::require(); $stage = DB::value('SELECT id FROM stages WHERE school_id = ? AND name = ?', [$sid, $d['stage']]);
        if (!$stage) { $stage = AcademicService::saveStage(null, $d['stage'], (int)DB::value('SELECT COALESCE(MAX(sort_order),0)+1 FROM stages WHERE school_id = ?', [$sid])); }
        AcademicService::saveGrade(null, (int)$stage, $d['name'], ($d['sort_order'] ?? '') === '' ? (int)DB::value('SELECT COALESCE(MAX(sort_order),0)+1 FROM grade_levels WHERE school_id = ?', [$sid]) : (int)$d['sort_order'], null);
    }
    private static function linkNextGrades(int $jobId): void
    {
        $sid = Tenant::require(); $byKey = []; foreach (DB::all('SELECT id, name FROM grade_levels WHERE school_id = ?', [$sid]) as $g) { $byKey[self::gradeKey($g['name'])] = (int)$g['id']; }
        foreach (DB::all("SELECT data FROM import_rows WHERE job_id = ? AND status = 'imported'", [$jobId]) as $r) {
            $d = json_decode($r['data'], true); if (($d['next'] ?? '') === '') { continue; }
            $a = $byKey[self::gradeKey($d['name'])] ?? null; $b = $byKey[self::gradeKey($d['next'])] ?? null;
            if ($a && $b && $a !== $b) { DB::query('UPDATE grade_levels SET next_grade_level_id = ? WHERE id = ? AND school_id = ?', [$b, $a, $sid]); }
        }
    }
    private static function importSubject(array $d): void
    {
        $sid = Tenant::require(); $id = DB::value('SELECT id FROM subjects WHERE school_id = ? AND name = ?', [$sid, $d['name']]);
        if ($id) { foreach ($d['grade_ids'] as $g) { DB::query('INSERT IGNORE INTO subject_grade_levels (subject_id, grade_level_id, school_id) VALUES (?,?,?)', [$id, $g, $sid]); } return; }   // موجودة: تُربط بالصفوف الإضافية
        AcademicService::saveSubject(null, $d['name'], ($d['code'] ?? '') ?: null, $d['grade_ids']);
    }
    private static function importSection(array $d): void
    {
        $y = AcademicService::activeYear() ?? throw new HttpException(422, 'لا توجد سنة فعّالة');
        AcademicService::saveSection(null, ['academic_year_id' => $y['id'], 'grade_level_id' => $d['grade_level_id'], 'name' => $d['name'], 'gender' => $d['gender'] ?? 'mixed', 'capacity' => $d['capacity'] ?? '', 'homeroom_staff_id' => $d['homeroom_staff_id'] ?? '']);
    }
    private static function importScore(array $d): void
    {
        $sid = Tenant::require();
        $aid = DB::value('SELECT id FROM assessments WHERE school_id = ? AND term_id = ? AND section_subject_id = ? AND title = ?', [$sid, $d['term_id'], $d['section_subject_id'], trim($d['title'])]);
        if ($aid) { $a = DB::one('SELECT max_score, status FROM assessments WHERE id = ?', [$aid]); if (abs((float)$a['max_score'] - (float)$d['max']) > 0.001) { throw new HttpException(422, 'التقييم «' . $d['title'] . '» موجود بدرجة عظمى مختلفة (' . (float)$a['max_score'] . ')'); } }
        else {
            $scheme = GradingService::schemeForSection((int)DB::value('SELECT section_id FROM section_subjects WHERE id = ?', [$d['section_subject_id']]));
            $type = null; foreach ($scheme['types'] as $tid => $t) { if (TabularFile::norm($t['name']) === TabularFile::norm($d['type'])) { $type = (int)$tid; } }
            if (!$type) { throw new HttpException(422, 'نوع التقييم «' . $d['type'] . '» غير تابع لنظام درجات هذه الشعبة'); }
            $aid = GradingService::createAssessment((int)$d['term_id'], (int)$d['section_subject_id'], $type, trim($d['title']), (float)$d['max'], null);
        }
        GradingService::saveScores((int)$aid, [['enrollment_id' => $d['enrollment_id'], 'score' => $d['absent'] ? null : $d['score'], 'is_absent' => $d['absent']]]);
    }
}
