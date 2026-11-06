<div class="login-layout">
  <section class="auth-card login-panel" aria-labelledby="loginTitle">
    <a class="login-brand" href="<?= url('/') ?>" aria-label="<?= e(__t('app.name')) ?>">
      <?php if (\App\Services\Brand::hasLogo()): ?>
        <img src="<?= url('brand/logo') ?>" alt="">
      <?php else: ?>
        <span class="login-brand-icon"><i class="bi bi-mortarboard-fill" aria-hidden="true"></i></span>
      <?php endif; ?>
      <span><?= e(__t('app.name')) ?></span>
    </a>

    <header class="login-heading">
      <span class="login-eyebrow">بوابة المدرسة الرقمية</span>
      <h1 id="loginTitle">أهلاً بعودتك</h1>
      <p>سجّل الدخول لمتابعة شؤون مدرستك من مكان واحد.</p>
    </header>

    <ul class="nav nav-pills login-tabs" role="tablist" aria-label="طريقة تسجيل الدخول">
      <li class="nav-item" role="presentation"><button class="nav-link active" id="passwordTab" data-bs-toggle="pill" data-bs-target="#tabPwd" type="button" role="tab" aria-controls="tabPwd" aria-selected="true"><i class="bi bi-key" aria-hidden="true"></i><?= e(__t('auth.password_tab')) ?></button></li>
      <li class="nav-item" role="presentation"><button class="nav-link" id="otpTab" data-bs-toggle="pill" data-bs-target="#tabOtp" type="button" role="tab" aria-controls="tabOtp" aria-selected="false"><i class="bi bi-phone" aria-hidden="true"></i><?= e(__t('auth.otp_tab')) ?></button></li>
    </ul>

    <div class="tab-content login-tab-content">
      <div class="tab-pane fade show active" id="tabPwd" role="tabpanel" aria-labelledby="passwordTab" tabindex="0">
        <form method="post" action="<?= url('login') ?>" data-ajax novalidate>
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label" for="identifier"><?= e(__t('auth.identifier')) ?></label>
            <div class="login-input-wrap"><i class="bi bi-person" aria-hidden="true"></i><input class="form-control" id="identifier" name="identifier" autocomplete="username" dir="ltr" placeholder="name@example.com" required></div>
          </div>
          <div class="mb-2">
            <label class="form-label" for="password"><?= e(__t('auth.password')) ?></label>
            <div class="login-input-wrap login-password-wrap">
              <i class="bi bi-lock" aria-hidden="true"></i>
              <input class="form-control" id="password" name="password" type="password" autocomplete="current-password" required>
              <button class="login-password-toggle" type="button" data-password-toggle="password" aria-label="إظهار كلمة المرور" aria-pressed="false"><i class="bi bi-eye" aria-hidden="true"></i></button>
            </div>
          </div>
          <div class="login-forgot-row"><a href="<?= url('password/forgot') ?>"><?= e(__t('auth.forgot')) ?><i class="bi bi-arrow-up-left" aria-hidden="true"></i></a></div>
          <button class="btn btn-primary w-100 login-submit" type="submit"><span><?= e(__t('auth.login')) ?></span><i class="bi bi-arrow-left" aria-hidden="true"></i></button>
        </form>
      </div>

      <div class="tab-pane fade" id="tabOtp" role="tabpanel" aria-labelledby="otpTab" tabindex="0">
        <form id="otpRequestForm" method="post" action="<?= url('login/otp/request') ?>" data-ajax novalidate>
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label" for="otpPhone"><?= e(__t('auth.phone')) ?></label>
            <div class="login-input-wrap"><i class="bi bi-telephone" aria-hidden="true"></i><input class="form-control" id="otpPhone" name="phone" type="tel" inputmode="tel" autocomplete="tel" dir="ltr" placeholder="7xxxxxxxx" required></div>
          </div>
          <p class="login-hint"><i class="bi bi-info-circle" aria-hidden="true"></i><span>سيصلك رمز SMS صالح لمدة <?= (int)$smsOtpTtl ?> دقائق. حد الطلبات <?= (int)$smsOtpPerHour ?> مرات في الساعة لكل رقم.</span></p>
          <button class="btn btn-primary w-100 login-submit" type="submit"><span><?= e(__t('auth.send_otp')) ?></span><i class="bi bi-arrow-left" aria-hidden="true"></i></button>
        </form>
        <form id="otpVerifyForm" class="d-none" method="post" action="<?= url('login/otp/verify') ?>" data-ajax novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="phone">
          <p id="otpSentNotice" class="login-hint" role="status" aria-live="polite"><i class="bi bi-chat-square-dots" aria-hidden="true"></i><span>أدخل الرمز المكوّن من 6 أرقام، ولا تشاركه مع أحد.</span></p>
          <div class="mb-3"><label class="form-label" for="otpCode"><?= e(__t('auth.otp_code')) ?></label><input class="form-control login-code-input" id="otpCode" name="code" type="text" inputmode="numeric" pattern="[0-9]{6}" minlength="6" maxlength="6" autocomplete="one-time-code" dir="ltr" placeholder="••••••" required></div>
          <button class="btn btn-primary w-100 login-submit" type="submit"><span><?= e(__t('auth.verify')) ?></span><i class="bi bi-arrow-left" aria-hidden="true"></i></button>
          <div class="login-otp-actions"><button class="btn btn-link" id="resendOtp" type="button">إعادة إرسال الرمز</button><button class="btn btn-link" id="changeOtpPhone" type="button">تغيير رقم الهاتف</button></div>
        </form>
      </div>
    </div>

    <div class="login-secure-note"><i class="bi bi-shield-check" aria-hidden="true"></i><span>جلسة دخول محمية — لا تشارك بيانات حسابك مع أحد.</span></div>
  </section>

  <aside class="login-showcase" aria-label="مزايا منصة مدرستي">
    <div class="showcase-orbit showcase-orbit-one" aria-hidden="true"></div>
    <div class="showcase-orbit showcase-orbit-two" aria-hidden="true"></div>
    <div class="showcase-content">
      <div class="showcase-mark"><i class="bi bi-mortarboard-fill" aria-hidden="true"></i></div>
      <span class="showcase-kicker">منصة إدارة المدارس اليمنية</span>
      <h2>كل تفاصيل مدرستك،<br><span>أوضح وأسهل.</span></h2>
      <p><?= e(__t('app.tagline')) ?>، بتجربة عربية مصممة لتسهّل العمل اليومي وتجمع فريق المدرسة في مساحة واحدة.</p>
      <div class="showcase-features">
        <div><span class="feature-icon"><i class="bi bi-people" aria-hidden="true"></i></span><span><strong>شؤون المدرسة</strong><small>طلاب، معلمون، وصفوف في مكان واحد</small></span></div>
        <div><span class="feature-icon"><i class="bi bi-calendar2-check" aria-hidden="true"></i></span><span><strong>متابعة يومية</strong><small>الحضور، الدرجات، والجدول الدراسي</small></span></div>
        <div><span class="feature-icon"><i class="bi bi-chat-dots" aria-hidden="true"></i></span><span><strong>تواصل أسهل</strong><small>قنوات أوضح للمدرسة وأولياء الأمور</small></span></div>
      </div>
    </div>
    <div class="showcase-footer"><span><i class="bi bi-globe2" aria-hidden="true"></i> مصممة للمدارس اليمنية</span><span>واجهة عربية بالكامل</span></div>
  </aside>
</div>
