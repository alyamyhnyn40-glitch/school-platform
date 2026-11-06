<div class="auth-card">
  <div class="auth-brand"><i class="bi bi-envelope-check"></i><h1>إعادة تعيين كلمة المرور</h1><p>يُرسل الرمز فقط إلى بريد مرتبط بحساب نشط. إذا كان البريد مرتبطًا بحساب، فأدخل رمز التحقق المكوّن من 6 أرقام. صلاحيته <?= (int)$emailOtpTtl ?> دقيقة، وبعد <?= (int)$emailOtpTries ?> محاولات خاطئة اطلب رمزًا جديدًا.</p><p class="small text-muted mb-0">لأسباب أمنية، لا تعرض المنصة ما إذا كان البريد مسجّلًا.</p></div>
  <form method="post" action="<?= url('password/verify') ?>" data-ajax novalidate><?= csrf_field() ?>
    <div class="mb-3"><label class="form-label" for="em">البريد الإلكتروني الذي طلبت له الرمز</label><input class="form-control" id="em" name="email" type="email" dir="ltr" autocomplete="email" value="<?= e($email) ?>" required></div>
    <div class="mb-3"><label class="form-label" for="cd">رمز التحقق</label><input class="form-control text-center" id="cd" name="code" type="text" inputmode="numeric" pattern="[0-9]{6}" minlength="6" maxlength="6" autocomplete="one-time-code" dir="ltr" required><div class="form-text">تحقق من البريد الوارد ومجلد الرسائل غير المرغوب فيها. لا تشارك الرمز مع أحد.</div></div>
    <div class="mb-3"><label class="form-label" for="pw">كلمة المرور الجديدة</label><input class="form-control" id="pw" name="password" type="password" autocomplete="new-password" minlength="8" required><div class="form-text">8 أحرف على الأقل، وتحتوي على حرف ورقم.</div></div>
    <div class="mb-3"><label class="form-label" for="pc">تأكيد كلمة المرور</label><input class="form-control" id="pc" name="password_confirm" type="password" autocomplete="new-password" minlength="8" required></div>
    <button class="btn btn-primary w-100" type="submit">تغيير كلمة المرور</button>
  </form>
  <form id="resendEmailForm" class="mt-3" method="post" action="<?= url('password/forgot') ?>" data-ajax novalidate><?= csrf_field() ?>
    <input id="resendEmail" type="hidden" name="identifier" value="<?= e($email) ?>">
    <p class="small text-muted text-center mb-2">لم يصلك الرمز؟ يمكن طلب رمز جديد. تأكد من البريد أعلاه قبل إعادة الطلب.</p>
    <button id="resendEmailButton" class="btn btn-outline-secondary w-100" type="button">إعادة إرسال الرمز</button>
  </form>
  <div class="text-center mt-3 small"><a href="<?= url('password/forgot') ?>">تغيير البريد</a> · <a href="<?= url('login') ?>">العودة لتسجيل الدخول</a></div>
</div>
