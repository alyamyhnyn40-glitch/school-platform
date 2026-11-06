<?php
declare(strict_types=1);
namespace App\Core;

/** هوية المستخدم الحالي وصلاحياته (مخزنة في الجلسة ومحدّثة عند الدخول). */
final class Auth
{
    public static function check(): bool { return Session::get('user_id') !== null; }
    public static function id(): ?int { $i = Session::get('user_id'); return $i === null ? null : (int)$i; }
    public static function user(): ?array { return Session::get('user'); }
    public static function type(): ?string { return self::user()['user_type'] ?? null; }
    public static function isPlatform(): bool { return self::type() === 'platform'; }

    public static function login(array $user, array $perms): void
    {
        Session::regenerate();
        Session::set('user_id', (int)$user['id']);
        Session::set('user', ['id' => (int)$user['id'], 'full_name' => $user['full_name'], 'user_type' => $user['user_type'],
            'school_id' => $user['school_id'] !== null ? (int)$user['school_id'] : null, 'must_change_password' => (int)$user['must_change_password'],
            'theme' => $user['theme'] ?? 'auto']);
        Session::set('school_id', $user['school_id'] !== null ? (int)$user['school_id'] : null);
        Session::set('perms', $perms);
    }
    public static function logout(): void { Session::destroy(); }

    public static function perms(): array { return Session::get('perms', []); }
    public static function can(string $perm): bool
    {
        $p = self::perms();
        return in_array('*', $p, true) || in_array($perm, $p, true);
    }
    /** هل يملك المستخدم أيًّا من هذه الصلاحيات؟ */
    public static function canAny(string ...$perms): bool { foreach ($perms as $p) { if (self::can($p)) { return true; } } return false; }
    /**
     * صلاحيات تحتاج قراءة الهيكل الأكاديمي (صفوف/شعب/مواد/سنوات) كبيانات مرجعية للفلاتر والقوائم.
     * قراءة المرجع لا تتطلب «عرض الهيكل الأكاديمي» تحديدًا: المحاسب والمرشد مثلًا يحتاجان قائمة الشعب لفلاتر صفحاتهما.
     */
    public const LOOKUP_PERMS = ['academic.view', 'students.view', 'attendance.view', 'grades.view', 'fees.view', 'reports.view', 'timetable.view', 'lms.view', 'admissions.manage', 'risk.view', 'counseling.manage', 'analytics.view', 'staff.view', 'comms.announce'];
    public static function authorizeLookup(): void
    {
        if (!self::canAny(...self::LOOKUP_PERMS)) { throw (new HttpException(403, 'ليست لديك صلاحية تنفيذ هذه العملية'))->withPerm('lookup:academic'); }
    }
    /** يرمي 403 إن لم يملك أيًّا منها. */
    public static function authorizeAny(string ...$perms): void
    {
        if (!self::canAny(...$perms)) { throw (new HttpException(403, 'ليست لديك صلاحية تنفيذ هذه العملية'))->withPerm(implode('|', $perms)); }
    }
    /** يرمي 403 إن لم تتوفر الصلاحية (للاستخدام داخل الـ Services). */
    public static function authorize(string $perm): void
    {
        if (!self::can($perm)) { throw (new HttpException(403, 'ليست لديك صلاحية تنفيذ هذه العملية'))->withPerm($perm); }
    }
}
