<?php
declare(strict_types=1);
namespace App\Controllers\Platform;

use App\Core\{DB, Request, Response, View};

final class PlatformDashboardController
{
    public function index(Request $r): Response
    {
        $stats = [
            'schools' => (int)DB::value('SELECT COUNT(*) FROM schools WHERE deleted_at IS NULL'),
            'active' => (int)DB::value("SELECT COUNT(*) FROM schools WHERE status = 'active' AND deleted_at IS NULL"),
            'students' => (int)DB::value("SELECT COUNT(*) FROM students WHERE status = 'active' AND deleted_at IS NULL"),
            'users' => (int)DB::value("SELECT COUNT(*) FROM users WHERE user_type <> 'platform' AND deleted_at IS NULL"),
        ];
        return Response::html(View::render('platform/dashboard', ['title' => __t('nav.dashboard'), 'stats' => $stats]));
    }
}
