<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Auth, HttpException, Request, Response, Session, Validator, View};
use App\Services\{AuthService, AuditService};

final class AuthController
{
    private static function normalizeOtp(string $code): string
    {
        return strtr($code, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);
    }

    public function showLogin(Request $r): Response
    {
        if (Auth::check()) { return Response::redirect('/dashboard'); }
        return Response::html(View::render('auth/login', [
            'title' => __t('auth.login'),
            'smsOtpTtl' => \App\Services\VerificationSettings::int('sms.ttl'),
            'smsOtpPerHour' => \App\Services\VerificationSettings::int('sms.per_hour'),
            'bodyClass' => 'bare-body login-body',
            'styles' => ['login.css'],
            'scripts' => ['auth.js'],
        ], 'bare'));
    }

    public function login(Request $r): Response
    {
        $v = Validator::make($r->all(), ['identifier' => 'required|max:190', 'password' => 'required'], ['identifier' => __t('auth.identifier'), 'password' => __t('auth.password')]);
        if ($v->fails()) { throw new HttpException(422, 'يرجى إدخال بيانات الدخول', $v->firstErrors()); }
        AuthService::attemptPassword((string)$r->input('identifier'), (string)$r->input('password'), $r->ip());
        return Response::ok(['redirect' => url(Auth::isPlatform() ? '/platform' : '/dashboard')]);
    }

    public function otpRequest(Request $r): Response
    {
        $v = Validator::make($r->all(), ['phone' => 'required|max:30']);
        if ($v->fails()) { throw new HttpException(422, 'أدخل رقم الهاتف', $v->firstErrors()); }
        AuthService::requestOtp((string)$r->input('phone'), $r->ip());
        return Response::ok(['message' => __t('auth.otp_sent')]);
    }

    public function otpVerify(Request $r): Response
    {
        $code = self::normalizeOtp((string)$r->input('code', ''));
        $data = $r->all(); $data['code'] = $code;
        $v = Validator::make($data, ['phone' => 'required', 'code' => 'required|regex:/^\d{6}$/'], ['code' => __t('auth.otp_code')]);
        if ($v->fails()) { throw new HttpException(422, 'أدخل الرمز المكوّن من 6 أرقام', $v->firstErrors()); }
        AuthService::verifyOtp((string)$r->input('phone'), $code, $r->ip());
        return Response::ok(['redirect' => url(Auth::isPlatform() ? '/platform' : '/dashboard')]);
    }

    public function logout(Request $r): Response
    {
        if (Auth::check()) { AuditService::log('logout', 'user', Auth::id()); }
        Auth::logout();
        return Response::redirect('/login');
    }

    public function showForgot(Request $r): Response
    {
        return Response::html(View::render('auth/forgot', ['title' => __t('auth.forgot_title'), 'scripts' => ['auth.js']], 'bare'));
    }

    public function forgot(Request $r): Response
    {
        $id = trim((string)$r->input('identifier')); if ($id === '') { throw new HttpException(422, 'أدخل البريد أو الهاتف'); }
        if (str_contains($id, '@')) {   // بريد: رمز تحقق (OTP) عبر البريد ثم صفحة إدخال الرمز
            \App\Services\PasswordCodeService::request($id, $r->ip());
            return Response::ok(['message' => 'إن كان البريد مسجّلًا فقد أُرسل إليه رمز التحقق.', 'redirect' => url('password/verify') . '?email=' . rawurlencode(mb_strtolower($id))]);
        }
        AuthService::requestReset($id);   // هاتف/اسم مستخدم: رابط عبر SMS كما كان
        return Response::ok(['message' => __t('auth.forgot_sent')]);
    }

    public function showVerify(Request $r): Response
    {
        return Response::html(View::render('auth/verify_code', [
            'title' => 'إدخال رمز التحقق',
            'email' => (string)$r->query('email', ''),
            'emailOtpTtl' => \App\Services\VerificationSettings::int('email.ttl'),
            'emailOtpTries' => \App\Services\VerificationSettings::int('email.tries'),
            'scripts' => ['auth.js'],
        ], 'bare'));
    }
    public function verifyCode(Request $r): Response
    {
        $code = self::normalizeOtp((string)$r->input('code', ''));
        $data = $r->all(); $data['code'] = $code;
        $v = Validator::make($data, [
            'email' => 'required|email|max:190',
            'code' => 'required|regex:/^\d{6}$/',
            'password' => 'required',
            'password_confirm' => 'required',
        ], [
            'email' => 'أدخل بريدًا إلكترونيًا صحيحًا',
            'code' => 'أدخل رمز التحقق المكوّن من 6 أرقام',
            'password' => 'أدخل كلمة المرور الجديدة',
            'password_confirm' => 'أعد إدخال كلمة المرور للتأكيد',
        ]);
        if ($v->fails()) { throw new HttpException(422, 'تحقق من الحقول المطلوبة', $v->firstErrors()); }
        if ($r->input('password') !== $r->input('password_confirm')) { throw new HttpException(422, 'كلمتا المرور غير متطابقتين', ['password_confirm' => 'غير متطابقة']); }
        \App\Services\PasswordCodeService::reset((string)$r->input('email', ''), $code, (string)$r->input('password', ''), $r->ip());
        return Response::ok(['message' => __t('auth.password_changed'), 'redirect' => url('/login')]);
    }

    public function showReset(Request $r): Response
    {
        return Response::html(View::render('auth/reset', ['title' => __t('auth.reset_title'), 'token' => $r->params['token']], 'bare'));
    }

    public function reset(Request $r): Response
    {
        if ($r->input('password') !== $r->input('password_confirm')) { throw new HttpException(422, 'كلمتا المرور غير متطابقتين'); }
        AuthService::resetPassword($r->params['token'], (string)$r->input('password'));
        return Response::ok(['message' => __t('auth.password_changed'), 'redirect' => url('/login')]);
    }

    public function showChange(Request $r): Response
    {
        $forced = (bool)(Auth::user()['must_change_password'] ?? 0);
        return Response::html(View::render('auth/change', ['title' => __t('auth.change_title'), 'forced' => $forced], $forced ? 'bare' : 'main'));
    }

    public function change(Request $r): Response
    {
        if ($r->input('password') !== $r->input('password_confirm')) { throw new HttpException(422, 'كلمتا المرور غير متطابقتين'); }
        $forced = (bool)(Auth::user()['must_change_password'] ?? 0);
        AuthService::changePassword((int)Auth::id(), $forced ? null : (string)$r->input('current_password'), (string)$r->input('password'));
        $u = Auth::user(); $u['must_change_password'] = 0; Session::set('user', $u);
        return Response::ok(['message' => __t('auth.password_changed'), 'redirect' => url(Auth::isPlatform() ? '/platform' : '/dashboard')]);
    }

    /** رمز CSRF حالي (تستخدمه المزامنة دون اتصال لتجديد الرمز). */
    public function csrf(Request $r): Response { return Response::ok(['token' => \App\Core\Csrf::token()]); }
}
