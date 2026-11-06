<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Auth, DB, Request, Response, Tenant, View};

final class DashboardController
{
    public function home(Request $r): Response { return Response::redirect(Auth::check() ? (Auth::isPlatform() ? '/platform' : '/dashboard') : '/login'); }

    public function school(Request $r): Response
    {
        if (Auth::isPlatform() && !\App\Core\Session::get('support_mode')) { return Response::redirect('/platform'); }
        if (in_array(Auth::type(), ['guardian', 'student'], true)) { return Response::redirect('/portal'); }
        $sid = Tenant::require();
        $stats = [
            'students' => (int)DB::value("SELECT COUNT(*) FROM students WHERE school_id = ? AND status = 'active' AND deleted_at IS NULL", [$sid]),
            'staff' => (int)DB::value('SELECT COUNT(*) FROM staff WHERE school_id = ? AND deleted_at IS NULL', [$sid]),
            'sections' => (int)DB::value('SELECT COUNT(*) FROM sections WHERE school_id = ?', [$sid]),
        ];
        $school = DB::one('SELECT name FROM schools WHERE id = ?', [$sid]);
        $liveNow = []; if (Auth::can('lms.manage') && \App\Services\SubscriptionService::moduleEnabled($sid, 'lms')) { try { $liveNow = \App\Services\LiveService::liveNow(); } catch (\Throwable) { $liveNow = []; } }
        return Response::html(View::render('school/dashboard', ['liveNow' => $liveNow, 'title' => __t('nav.dashboard'), 'stats' => $stats, 'school' => $school, 'today' => Auth::can('timetable.view') ? \App\Services\TimetableService::today() : [], 'periods' => Auth::can('timetable.view') ? \App\Services\TimetableService::config()['periods'] : []]));
    }
}
