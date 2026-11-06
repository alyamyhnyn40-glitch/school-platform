<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Auth, HttpException, Request, Response, View};
use App\Services\{Brand, SchoolRequestService as S};

final class LandingController
{
    /** الصفحة الرئيسية: المسجَّل يُحوَّل إلى لوحته، والزائر يرى صفحة الهبوط. */
    public function index(Request $r): Response
    {
        if (Auth::check()) { return Response::redirect(Auth::isPlatform() ? '/platform' : '/dashboard'); }
        return Response::html(View::render('landing/index', ['title' => Brand::name(), 'challenge' => S::challenge(), 'sent' => $r->query('sent') === '1', 'scripts' => ['image-preview.js']], 'landing'));
    }
    public function submit(Request $r): Response
    {
        $f = !empty($_FILES['logo']) && ($_FILES['logo']['error'] ?? 4) !== UPLOAD_ERR_NO_FILE ? $_FILES['logo'] : null;
        S::submit($r->all(), $f, $r->ip());
        return Response::ok(['message' => 'استلمنا طلبكم وسنتواصل معكم قريبًا', 'redirect' => url('/') . '?sent=1#request']);
    }

    /** شعار المدرسة الحالية للمستخدم المسجّل. */
    public function schoolLogo(Request $r): Response
    {
        $p = Brand::schoolLogoPath(); if (!$p) { throw new HttpException(404, 'لا شعار'); }
        $mime = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'webp' => 'image/webp'][pathinfo($p, PATHINFO_EXTENSION)] ?? 'application/octet-stream';
        return new Response((string)file_get_contents($p), 200, ['Content-Type' => $mime, 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, max-age=600']);
    }

    /** manifest التطبيق (PWA) ديناميكي ليراعي المسار الفرعي واسم المنصة ولونها. */
    public function manifest(Request $r): Response
    {
        $b = rtrim((string)\App\Core\Env::get('APP_BASE_PATH', ''), '/');
        $m = ['name' => Brand::name() . ' — ' . Brand::get('tagline'), 'short_name' => Brand::name(), 'lang' => 'ar', 'dir' => 'rtl', 'start_url' => $b . '/dashboard', 'scope' => $b . '/', 'display' => 'standalone',
            'background_color' => '#f5f7fb', 'theme_color' => Brand::color(),
            'icons' => [['src' => $b . '/assets/img/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png'], ['src' => $b . '/assets/img/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable']]];
        return new Response((string)json_encode($m, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 200, ['Content-Type' => 'application/manifest+json; charset=utf-8', 'Cache-Control' => 'public, max-age=3600']);
    }
}
