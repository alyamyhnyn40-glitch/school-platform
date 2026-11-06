<?php
declare(strict_types=1);
namespace App\Middleware;

use App\Core\{Auth, DB, HttpException, Request, Response, Session};
use App\Services\AuthService;

final class AuthMiddleware
{
    public function handle(Request $r, callable $next, ?string $arg = null): Response
    {
        if (!Auth::check()) {
            if ($r->isAjax()) { throw new HttpException(401, 'يجب تسجيل الدخول'); }
            return Response::redirect('/login');
        }
        // تحديث الصلاحيات من القاعدة في كل طلب: التعديل/التعطيل يسري فورًا دون انتظار تسجيل دخول جديد
        if (!Auth::isPlatform()) {
            $u = DB::one('SELECT id, user_type, school_id, status FROM users WHERE id = ? AND deleted_at IS NULL', [Auth::id()]);
            if (!$u || $u['status'] !== 'active') { Auth::logout(); if ($r->isAjax()) { throw new HttpException(401, 'تم تعطيل الحساب'); } return Response::redirect('/login'); }
            if ($u['user_type'] === 'guardian' && \App\Core\Tenant::id() !== null) { $u['school_id'] = \App\Core\Tenant::id(); }   // ولي الأمر: المدرسة الحالية من الجلسة (تبديل بين المدارس)
            Session::set('perms', AuthService::permissionsFor($u));
        }
        // إلزام تغيير كلمة المرور عند أول دخول
        if ((Auth::user()['must_change_password'] ?? 0) && $r->path() !== '/password/change' && $r->path() !== '/logout') {
            return $r->isAjax() ? throw new HttpException(403, 'يجب تغيير كلمة المرور أولًا') : Response::redirect('/password/change');
        }
        return $next($r);
    }
}
