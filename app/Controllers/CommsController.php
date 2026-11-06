<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Auth, HttpException, Request, Response, View};
use App\Services\{AcademicService, CommsService as C, FileService, NotificationService as N};

final class CommsController
{
    public function announcements(Request $r): Response { return Response::html(View::render('comms/announcements', ['title' => 'الإعلانات', 'items' => C::visible(50)])); }
    public function manage(Request $r): Response
    {
        $y = AcademicService::activeYear(); $secs = $y ? AcademicService::sections((int)$y['id']) : [];
        $ids = \App\Services\Scope::sectionIds(); $secs = array_values(array_filter($secs, fn($s) => $ids === null || in_array((int)$s['id'], $ids, true)));
        return Response::html(View::render('comms/manage', ['title' => 'إدارة الإعلانات', 'items' => C::manageList(), 'sections' => $secs, 'admin' => Auth::can('comms.notify'), 'wa' => N::whatsappEnabled()]));
    }
    public function create(Request $r): Response
    {
        C::create((string)$r->input('title', ''), (string)$r->input('body', ''), (string)$r->input('audience', 'all'), $r->input('section_id') ? (int)$r->input('section_id') : null, (bool)$r->input('send_sms') && $r->input('send_sms') !== '0', (bool)$r->input('send_whatsapp') && $r->input('send_whatsapp') !== '0');
        return Response::ok(['message' => 'تم نشر الإعلان']);
    }
    public function delete(Request $r): Response { C::delete((int)$r->params['id']); return Response::ok(['message' => 'تم الحذف']); }

    public function inbox(Request $r): Response { return Response::html(View::render('comms/inbox', ['title' => 'الرسائل', 'threads' => C::inbox()])); }
    public function compose(Request $r): Response { return Response::html(View::render('comms/new', ['title' => 'رسالة جديدة', 'scripts' => ['messages.js']])); }
    public function thread(Request $r): Response { return Response::html(View::render('comms/thread', ['title' => 'محادثة', 't' => C::thread((int)$r->params['id'])])); }
    public function send(Request $r): Response
    {
        $id = C::send((array)$r->input('recipients', []), (string)$r->input('subject', ''), (string)$r->input('body', ''), $r->input('thread_id') ? (int)$r->input('thread_id') : null);
        return Response::ok(['message' => 'أُرسلت الرسالة', 'redirect' => url("messages/$id")]);
    }
    public function recipients(Request $r): Response { return Response::ok(C::recipients((string)$r->query('q', ''))); }

    public function notifications(Request $r): Response { return Response::ok(['unread' => N::unread(), 'messages' => C::unreadMessages(), 'items' => N::latest(15)]); }
    public function markRead(Request $r): Response { N::markRead($r->input('id') ? (int)$r->input('id') : null); return Response::ok(['unread' => N::unread()]); }

    /** تنزيل ملف بعد فحص الصلاحية (الملفات خارج public). */
    public function file(Request $r): Response
    {
        $f = FileService::open((int)$r->params['id']); $path = FileService::path($f);
        if (!is_file($path)) { throw new HttpException(404, 'الملف غير موجود'); }
        $inline = FileService::isInline($f['mime']); $ascii = preg_replace('/[^A-Za-z0-9._-]/', '_', $f['original_name']);
        return new Response((string)file_get_contents($path), 200, ['Content-Type' => $f['mime'], 'Content-Length' => (string)filesize($path), 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store',
            'Content-Disposition' => ($inline ? 'inline' : 'attachment') . "; filename=\"$ascii\"; filename*=UTF-8''" . rawurlencode($f['original_name']), 'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; sandbox"]);
    }
}
