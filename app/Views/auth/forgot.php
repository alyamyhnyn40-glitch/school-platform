<div class="auth-card">
  <div class="auth-brand"><i class="bi bi-key"></i><h1><?= e(__t('auth.forgot_title')) ?></h1></div>
  <form method="post" action="<?= url('password/forgot') ?>" data-ajax novalidate>
    <?= csrf_field() ?>
    <p class="small text-muted text-center">أدخل البريد الإلكتروني لإرسال رمز تحقق، أو رقم الهاتف/اسم المستخدم لإرسال رابط الاستعادة.</p>
    <div class="mb-3"><label class="form-label" for="identifier"><?= e(__t('auth.identifier')) ?></label><input class="form-control" id="identifier" name="identifier" type="text" autocomplete="username" dir="ltr" required><div id="recoveryHint" class="form-text">إذا كان الحساب مسجلًا، ستصلك تعليمات الاستعادة عبر قناة الاتصال المتاحة.</div></div>
    <button class="btn btn-primary w-100" type="submit"><?= e(__t('auth.send_link')) ?></button>
    <div class="text-center mt-3"><a href="<?= url('login') ?>"><?= e(__t('auth.back_login')) ?></a></div>
  </form>
</div>
