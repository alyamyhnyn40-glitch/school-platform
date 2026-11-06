<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Auth, DB, HttpException, Tenant};

/** رفع وتخزين الملفات خارج public وتقديمها عبر Controller بفحص الصلاحية. */
final class FileService
{
    public const MAX_BYTES = 10 * 1024 * 1024;
    /** الامتداد => أنواع MIME المقبولة (يجب أن يتطابق الاثنان). */
    private const ALLOWED = [
        'pdf' => ['application/pdf'], 'txt' => ['text/plain'], 'jpg' => ['image/jpeg'], 'jpeg' => ['image/jpeg'], 'png' => ['image/png'], 'gif' => ['image/gif'], 'webp' => ['image/webp'],
        'doc' => ['application/msword', 'application/vnd.ms-office', 'application/octet-stream'], 'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
        'xls' => ['application/vnd.ms-excel', 'application/vnd.ms-office', 'application/octet-stream'], 'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/octet-stream'],
        'ppt' => ['application/vnd.ms-powerpoint', 'application/vnd.ms-office', 'application/octet-stream'], 'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip', 'application/octet-stream'],
    ];
    private const INLINE = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];

    /** $file: عنصر من $_FILES. يعيد معرّف الملف. */
    public static function store(array $file, string $context, ?int $contextId): int
    {
        $sid = Tenant::require();
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) { throw new HttpException(422, 'تعذّر رفع الملف'); }
        if ((int)$file['size'] <= 0 || (int)$file['size'] > self::MAX_BYTES) { throw new HttpException(422, 'حجم الملف يجب ألا يتجاوز 10MB'); }
        $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
        if (!isset(self::ALLOWED[$ext])) { throw new HttpException(422, 'نوع الملف غير مسموح (المسموح: ' . implode('، ', array_keys(self::ALLOWED)) . ')'); }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: '';
        if (!in_array($mime, self::ALLOWED[$ext], true)) { throw new HttpException(422, 'محتوى الملف لا يطابق امتداده'); }
        if (SubscriptionService::storageLimitReached($sid, (int)$file['size'])) { throw new HttpException(422, 'تم بلوغ حد التخزين في اشتراك المدرسة'); }
        $dir = storage_path('uploads/files/' . $sid . '/' . date('Y-m')); if (!is_dir($dir)) { mkdir($dir, 0770, true); }
        $stored = bin2hex(random_bytes(16)) . '.' . $ext; $dest = "$dir/$stored";
        if (!(is_uploaded_file($file['tmp_name']) ? move_uploaded_file($file['tmp_name'], $dest) : copy($file['tmp_name'], $dest))) { throw new HttpException(500, 'تعذّر حفظ الملف'); }
        chmod($dest, 0640);
        $name = mb_substr(preg_replace('/[\x00-\x1F\x7F\/\\\\]/u', '', (string)$file['name']) ?? 'file', 0, 200);
        DB::query('INSERT INTO files (school_id, owner_user_id, context, context_id, original_name, stored_name, mime, size_bytes) VALUES (?,?,?,?,?,?,?,?)', [$sid, Auth::id(), $context, $contextId, $name, date('Y-m') . '/' . $stored, $mime, (int)$file['size']]);
        return DB::lastId();
    }

    /**
     * يحفظ صورة (PNG/JPG/WebP) بعد فحصها فعليًا وإعادة ترميزها وتصغيرها؛ تُحفظ في files كبقية الملفات (خارج public).
     * @return int معرّف الملف
     */
    public static function storeImage(array $file, string $context, ?int $contextId, int $maxBytes = 2097152, int $maxDim = 1600): int
    {
        $sid = Tenant::require();
        if (SubscriptionService::storageLimitReached($sid, (int)($file['size'] ?? 0))) { throw new HttpException(422, 'تم بلوغ حد التخزين في اشتراك المدرسة'); }
        $dir = storage_path('uploads/files/' . $sid . '/' . date('Y-m')); $stored = ImageUpload::storeSafe($file, $dir, $maxBytes, $maxDim);
        $path = $dir . '/' . $stored; @chmod($path, 0640); $mime = ImageUpload::MIMES ? array_search(pathinfo($stored, PATHINFO_EXTENSION), ImageUpload::MIMES, true) : 'image/png';
        $name = mb_substr(preg_replace('/[\x00-\x1F\x7F\/\\\\]/u', '', (string)($file['name'] ?? 'image')) ?? 'image', 0, 200);
        DB::query('INSERT INTO files (school_id, owner_user_id, context, context_id, original_name, stored_name, mime, size_bytes) VALUES (?,?,?,?,?,?,?,?)', [$sid, Auth::id(), $context, $contextId, $name, date('Y-m') . '/' . $stored, $mime, (int)filesize($path)]);
        return DB::lastId();
    }

    public static function attach(int $fileId, ?int $contextId): void { DB::query('UPDATE files SET context_id = ? WHERE id = ? AND school_id = ?', [$contextId, $fileId, Tenant::require()]); }

    public static function find(int $id): array
    {
        return DB::one('SELECT * FROM files WHERE id = ? AND school_id = ? AND deleted_at IS NULL', [$id, Tenant::require()]) ?? throw new HttpException(404, 'الملف غير موجود');
    }
    public static function remove(int $id): void
    {
        $f = DB::one('SELECT * FROM files WHERE id = ? AND school_id = ? AND deleted_at IS NULL', [$id, Tenant::require()]); if (!$f) { return; }
        DB::query('UPDATE files SET deleted_at = NOW() WHERE id = ?', [$id]);
        @unlink(storage_path('uploads/files/' . Tenant::require() . '/' . $f['stored_name']));
    }
    public static function path(array $f): string { return storage_path('uploads/files/' . $f['school_id'] . '/' . $f['stored_name']); }
    public static function isInline(string $mime): bool { return in_array($mime, self::INLINE, true); }

    /** يفتح ملفًا لتقديمه: يفحص أن المستخدم يملك حق الوصول حسب سياق الملف. */
    public static function open(int $id): array
    {
        $f = self::find($id); $ctx = $f['context']; $cid = (int)$f['context_id'];
        if ($ctx === 'material') {
            $m = DB::one('SELECT m.*, l.is_published FROM materials m JOIN lessons l ON l.id = m.lesson_id WHERE m.id = ? AND m.school_id = ? AND m.deleted_at IS NULL AND m.file_id = ?', [$cid, Tenant::require(), $id]) ?? throw new HttpException(404, 'الملف غير موجود');
            if (Auth::type() === 'staff') { LmsService::assertManage((int)$m['section_subject_id']); } else { LmsService::learner((int)$m['section_subject_id']); if (!$m['is_published']) { throw new HttpException(404, 'الملف غير موجود'); } }
        } elseif ($ctx === 'submission') {
            $su = DB::one('SELECT su.*, a.section_subject_id, s.user_id AS student_user, s.id AS student_id FROM submissions su JOIN assignments a ON a.id = su.assignment_id JOIN student_enrollments e ON e.id = su.enrollment_id JOIN students s ON s.id = e.student_id WHERE su.id = ? AND su.school_id = ? AND su.file_id = ?', [$cid, Tenant::require(), $id]) ?? throw new HttpException(404, 'الملف غير موجود');
            if (Auth::type() === 'staff') { LmsService::assertManage((int)$su['section_subject_id']); }
            elseif (Auth::type() === 'student') { if ((int)$su['student_user'] !== Auth::id()) { throw new HttpException(403, 'ليس ملفك'); } }
            else { Auth::authorize('portal.view'); if (!in_array((int)$su['student_id'], PortalService::studentIds(), true)) { throw new HttpException(403, 'لا تملك صلاحية'); } }
        } elseif ($ctx === 'admission') {
            Auth::authorize('admissions.manage');
            DB::value('SELECT 1 FROM admission_documents d JOIN admission_applications a ON a.id = d.application_id WHERE d.file_id = ? AND a.school_id = ?', [$id, Tenant::require()]) ?? throw new HttpException(404, 'الملف غير موجود');
        } elseif ($ctx === 'question') {
            // صور الأسئلة: المعلم (lms.manage) دائمًا؛ الطالب فقط إن كان له محاولة على اختبار منشور يحوي السؤال (فلا تُسرَّب قبل بدء الاختبار)
            $q = DB::one('SELECT id, subject_id FROM question_bank WHERE id = ? AND school_id = ? AND deleted_at IS NULL AND (image_file_id = ? OR JSON_CONTAINS(COALESCE(option_images, JSON_ARRAY()), ?))', [$cid, Tenant::require(), $id, (string)$id]) ?? throw new HttpException(404, 'الملف غير موجود');
            if (Auth::type() === 'staff') { Auth::authorize('lms.manage'); }
            elseif (Auth::type() === 'student') {
                DB::value("SELECT 1 FROM quiz_attempts a JOIN quizzes z ON z.id = a.quiz_id JOIN quiz_questions qq ON qq.quiz_id = z.id JOIN student_enrollments e ON e.id = a.enrollment_id JOIN students s ON s.id = e.student_id WHERE qq.question_id = ? AND z.is_published = 1 AND z.deleted_at IS NULL AND s.user_id = ? AND a.school_id = ? LIMIT 1", [(int)$q['id'], Auth::id(), Tenant::require()]) ?? throw new HttpException(403, 'لا تملك صلاحية');
            } else { throw new HttpException(403, 'لا تملك صلاحية'); }
        } elseif ($ctx === 'claim') {   // إيصال تحويل: موظفو الرسوم أو ولي الأمر صاحب الإشعار فقط
            $c = DB::one('SELECT * FROM payment_claims WHERE id = ? AND school_id = ? AND receipt_file_id = ?', [$cid, Tenant::require(), $id]) ?? throw new HttpException(404, 'الملف غير موجود');
            if (Auth::type() === 'staff') { Auth::authorize('fees.view'); } elseif (Auth::type() === 'guardian') { if ((int)$c['guardian_user_id'] !== Auth::id()) { throw new HttpException(403, 'ليس ملفك'); } } else { throw new HttpException(403, 'لا تملك صلاحية'); }
        } else { throw new HttpException(403, 'لا يمكن فتح هذا الملف'); }
        return $f;
    }
}
