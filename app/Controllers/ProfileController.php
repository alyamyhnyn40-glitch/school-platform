<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Request, Response, View};
use App\Services\{ProfileService, SchoolIdentityService};

final class ProfileController
{
    public function show(Request $r): Response { return Response::html(View::render('profile/show', ['title' => 'ملفي الشخصي'] + ProfileService::me())); }
    public function update(Request $r): Response { ProfileService::update($r->all()); return Response::ok(['message' => 'حُفظ ملفك الشخصي']); }
    public function school(Request $r): Response { return Response::html(View::render('school/identity', ['title' => 'هوية المدرسة', 's' => SchoolIdentityService::get(), 'scripts' => ['image-preview.js']])); }
    public function saveSchool(Request $r): Response
    {
        $f = !empty($_FILES['logo']) && ($_FILES['logo']['error'] ?? 4) !== UPLOAD_ERR_NO_FILE ? $_FILES['logo'] : null;
        SchoolIdentityService::save($r->all(), $f, !empty($r->input('remove_logo')) && $r->input('remove_logo') !== '0');
        return Response::ok(['message' => 'حُفظت هوية المدرسة']);
    }
}
