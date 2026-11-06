<?php
declare(strict_types=1);
namespace App\Controllers\School;

use App\Core\{HttpException, Request, Response, View};
use App\Services\RbacService as R;

final class RbacController
{
    public function roles(Request $r): Response { return Response::html(View::render('school/roles/index', ['title' => 'الأدوار والصلاحيات', 'roles' => R::roles()])); }
    public function role(Request $r): Response
    {
        return Response::html(View::render('school/roles/edit', ['title' => 'تعديل دور', 'role' => R::role((int)$r->params['id']), 'catalog' => R::catalog(), 'labels' => R::moduleLabels(), 'scripts' => ['roles.js']]));
    }
    public function users(Request $r): Response
    {
        return Response::html(View::render('school/roles/users', ['title' => 'المستخدمون', 'users' => R::users(), 'roles' => array_values(array_filter(R::roles(), fn($x) => !in_array($x['code'], ['student', 'guardian'], true)))]));
    }
    public function saveRole(Request $r): Response
    {
        $id = R::saveRole(isset($r->params['id']) ? (int)$r->params['id'] : null, (string)$r->input('name', ''), (array)$r->input('perms', []));
        return Response::ok(['id' => $id, 'message' => 'تم حفظ الدور', 'redirect' => url("school/roles/$id")]);
    }
    public function deleteRole(Request $r): Response { R::deleteRole((int)$r->params['id']); return Response::ok(['message' => 'تم حذف الدور', 'redirect' => url('school/roles')]); }
    public function userRoles(Request $r): Response { R::setUserRoles((int)$r->params['id'], (array)$r->input('role_ids', [])); return Response::ok(['message' => 'تم تحديث أدوار المستخدم']); }
    public function userStatus(Request $r): Response { R::setUserStatus((int)$r->params['id'], (bool)$r->input('active')); return Response::ok(['message' => 'تم تحديث الحالة']); }
    public function userReset(Request $r): Response
    {
        [$login, $temp] = R::resetPassword((int)$r->params['id']);
        return Response::ok(['message' => 'تمت إعادة التعيين', 'email' => $login, 'temp_password' => $temp]);
    }

    public function userPerms(Request $r): Response
    {
        $uid = (int)$r->params['id']; $u = \App\Core\DB::one('SELECT id, full_name, email FROM users WHERE id = ? AND school_id = ?', [$uid, \App\Core\Tenant::require()]);
        return Response::html(View::render('school/roles/user_perms', ['title' => 'صلاحيات خاصة للمستخدم', 'u' => $u, 'eff' => R::effective($uid), 'catalog' => R::catalog(), 'modules' => R::moduleLabels()]));
    }
    public function saveUserPerms(Request $r): Response
    {
        $map = $r->input('perm'); $set = R::setUserOverrides((int)$r->params['id'], is_array($map) ? $map : []);
        $a = count(array_filter($set, fn($x) => $x === 'allow')); $d = count(array_filter($set, fn($x) => $x === 'deny'));
        return Response::ok(['message' => $set ? "حُفظت الصلاحيات الخاصة: $a ممنوحة و$d محجوبة. تسري فورًا." : 'أُزيلت الصلاحيات الخاصة؛ يعتمد المستخدم على أدواره فقط.']);
    }
}
