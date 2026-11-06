<?php
declare(strict_types=1);
namespace App\Controllers\Platform;

use App\Core\{HttpException, Request, Response, View};
use App\Services\{Brand, MailSettingsService as M, SmsSettingsService as S};

final class SettingsController
{
    private function page(string $ch): Response { $s = S::current($ch); return Response::html(View::render('platform/sms', ['title' => 'إعدادات ' . $s['label'], 's' => $s, 'scripts' => ['platform-sms.js', 'provider-presets.js']])); }
    private function test(Request $r, string $ch): Response
    {
        $x = S::test((string)$r->input('phone', ''), $ch);
        if (!$x['ok']) { $d = $x['detail']; throw new HttpException(422, 'فشل الإرسال التجريبي: ' . ($d['error'] ?? ('رمز الاستجابة ' . ($d['code'] ?? '?'))) . (!empty($d['response']) ? ' — ' . $d['response'] : '')); }
        return Response::ok(['message' => $x['driver'] === 'http' ? 'أُرسلت رسالة الاختبار بنجاح (رمز ' . $x['detail']['code'] . ')' : $x['detail']['note']]);
    }
    public function sms(Request $r): Response { return $this->page('sms'); }
    public function save(Request $r): Response { S::save($r->all(), 'sms'); return Response::ok(['message' => 'حُفظت الإعدادات']); }
    public function testSms(Request $r): Response { return $this->test($r, 'sms'); }
    public function whatsapp(Request $r): Response { return $this->page('whatsapp'); }
    public function saveWhatsapp(Request $r): Response { S::save($r->all(), 'whatsapp'); return Response::ok(['message' => 'حُفظت الإعدادات']); }
    public function testWhatsapp(Request $r): Response { return $this->test($r, 'whatsapp'); }

    // ----- الهوية والإعدادات العامة -----
    public function brand(Request $r): Response
    {
        \App\Core\Auth::authorize('platform.settings'); Brand::forget();
        return Response::html(View::render('platform/brand', ['title' => 'إعدادات المنصة والهوية', 'b' => Brand::all(), 'hasLogo' => Brand::hasLogo(), 'scripts' => ['image-preview.js']]));
    }
    public function saveBrand(Request $r): Response
    {
        $f = !empty($_FILES['logo']) && ($_FILES['logo']['error'] ?? 4) !== UPLOAD_ERR_NO_FILE ? $_FILES['logo'] : null;
        Brand::save($r->all(), $f, (bool)$r->input('remove_logo') && $r->input('remove_logo') !== '0'); return Response::ok(['message' => 'حُفظت الإعدادات']);
    }
    /** شعار المنصة العام (بلا جلسة). */
    public function logo(Request $r): Response
    {
        $p = Brand::logoPath(); if (!$p) { throw new HttpException(404, 'لا شعار'); }
        return new Response((string)file_get_contents($p), 200, ['Content-Type' => Brand::logoMime(), 'Cache-Control' => 'public, max-age=3600', 'X-Content-Type-Options' => 'nosniff']);
    }

    // ----- البريد -----
    public function email(Request $r): Response { return Response::html(View::render('platform/email', ['title' => 'إعدادات البريد', 's' => M::current(), 'scripts' => ['platform-sms.js', 'provider-presets.js', 'email-settings.js']])); }
    public function saveEmail(Request $r): Response { M::save($r->all()); return Response::ok(['message' => 'حُفظت الإعدادات']); }
    public function testEmail(Request $r): Response
    {
        $x = M::test((string)$r->input('to', ''));
        if (!$x['ok']) { throw new HttpException(422, 'فشل الإرسال التجريبي: ' . ($x['error'] ?? 'غير معروف')); }
        return Response::ok(['message' => $x['driver'] === 'log' ? 'الوضع التجريبي: كُتبت الرسالة في storage/logs/mail.log' : 'أُرسلت رسالة الاختبار بنجاح']);
    }

    // ----- المساعد الذكي -----
    public function ai(Request $r): Response { return Response::html(View::render('platform/ai', ['title' => 'إعدادات المساعد الذكي', 'c' => \App\Services\AssistantService::config(), 'scripts' => ['platform-sms.js', 'provider-presets.js']])); }
    public function saveAi(Request $r): Response { \App\Services\AssistantService::saveConfig($r->all()); return Response::ok(['message' => 'حُفظت الإعدادات']); }
    public function testAi(Request $r): Response
    {
        $x = \App\Services\AssistantService::test(); if (!$x['ok']) { throw new HttpException(422, 'فشل الاختبار: ' . ($x['error'] ?? '')); }
        return Response::ok(['message' => 'نجح الاتصال. ردّ المساعد: ' . mb_substr($x['answer'], 0, 160)]);
    }

    // ----- رموز التحقق -----
    public function verification(Request $r): Response { return Response::html(View::render('platform/verification', ['title' => 'إعدادات رموز التحقق', 'v' => \App\Services\VerificationSettings::current()])); }
    public function saveVerification(Request $r): Response { \App\Services\VerificationSettings::save($r->all()); return Response::ok(['message' => 'حُفظت إعدادات التحقق']); }

    // ----- الفحص الأمني -----
    public function securityCheck(Request $r): Response { $c = \App\Services\SecurityCheckService::run(); return Response::html(View::render('platform/security_check', ['title' => 'الفحص الأمني', 'checks' => $c, 's' => \App\Services\SecurityCheckService::summary($c)])); }
    public function securityReviewed(Request $r): Response { \App\Services\SecurityCheckService::markReviewed(); return Response::ok(['message' => 'سُجّلت المراجعة الأمنية بتاريخ اليوم.']); }
}
