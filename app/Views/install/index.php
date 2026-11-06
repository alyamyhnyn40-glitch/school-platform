<div class="auth-card">
  <div class="auth-brand"><i class="bi bi-gear-wide-connected"></i><h1>تثبيت منصة مدرستي</h1><p>خطوة واحدة لإعداد القاعدة وحساب مشغّل المنصة</p></div>
  <h2 class="h6">متطلبات الخادم</h2>
  <ul class="list-group mb-3">
    <?php foreach ($reqs as [$label, $pass, $required]): ?>
      <li class="list-group-item d-flex justify-content-between"><span><?= e($label) ?></span>
        <span class="<?= $pass ? 'text-success' : ($required ? 'text-danger' : 'text-warning') ?>"><?= $pass ? '✔' : ($required ? '✘ مطلوب' : '! اختياري') ?></span></li>
    <?php endforeach; ?>
  </ul>
  <?php if ($ok): ?>
  <form method="post" action="<?= url('install') ?>" data-ajax novalidate>
    <?= csrf_field() ?>
    <h2 class="h6">قاعدة البيانات (MySQL 8 / MariaDB 10.6+)</h2>
    <div class="row g-2 mb-2">
      <div class="col-8"><label class="form-label" for="db_host">الخادم</label><input class="form-control" id="db_host" name="db_host" value="127.0.0.1" dir="ltr" required></div>
      <div class="col-4"><label class="form-label" for="db_port">المنفذ</label><input class="form-control" id="db_port" name="db_port" value="3306" dir="ltr" required></div>
      <div class="col-12"><label class="form-label" for="db_name">اسم القاعدة</label><input class="form-control" id="db_name" name="db_name" dir="ltr" required placeholder="مثل yemen_school_db"><div class="form-check mt-1"><input type="hidden" name="create_db" value="0"><input class="form-check-input" type="checkbox" name="create_db" value="1" id="create_db" checked><label class="form-check-label small" for="create_db">أنشئ القاعدة إن لم تكن موجودة</label></div></div>
      <div class="col-6"><label class="form-label" for="db_user">المستخدم</label><input class="form-control" id="db_user" name="db_user" dir="ltr" required></div>
      <div class="col-6"><label class="form-label" for="db_pass">كلمة المرور</label><input class="form-control" id="db_pass" name="db_pass" type="password" dir="ltr"></div>
    </div>
    <h2 class="h6 mt-3">الموقع</h2>
    <div class="row g-2 mb-2">
      <div class="col-12"><label class="form-label" for="app_url">رابط الموقع</label><input class="form-control" id="app_url" name="app_url" value="<?= e((!empty($_SERVER['HTTPS']) ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? '')) ?>" dir="ltr" required></div>
    </div>
    <h2 class="h6 mt-3">خيارات إضافية</h2>
    <div class="form-check mb-2"><input type="hidden" name="load_demo" value="0"><input class="form-check-input" type="checkbox" name="load_demo" value="1" id="load_demo"><label class="form-check-label" for="load_demo">تحميل مدرسة تجريبية ببيانات جاهزة للتجربة (طلاب، معلمون، درجات، رسوم…) — كلمة مرور حساباتها <code>Demo12345</code></label></div>
    <details class="mb-2"><summary class="small text-muted">لدي تثبيت قديم في هذه القاعدة (تحديث/نقل)</summary>
      <div class="mt-2"><label class="form-label small" for="enc_key">مفتاح التشفير القديم APP_ENC_KEY (من ملف .env القديم)</label><input class="form-control" id="enc_key" name="enc_key" dir="ltr" autocomplete="off" placeholder="اتركه فارغًا لتثبيت جديد"><div class="form-text">إن كانت القاعدة تحوي مشغّل منصة سابقًا فلن تُمسّ بياناتها؛ يُستعاد الإعداد فقط. بدون المفتاح القديم لا تُقرأ الحقول المشفّرة.</div></div></details>
    <h2 class="h6 mt-3">حساب مشغّل المنصة</h2>
    <div class="row g-2 mb-3">
      <div class="col-12"><label class="form-label" for="admin_name">الاسم</label><input class="form-control" id="admin_name" name="admin_name" required></div>
      <div class="col-md-6"><label class="form-label" for="admin_email">البريد الإلكتروني</label><input class="form-control" id="admin_email" name="admin_email" type="email" dir="ltr" required></div>
      <div class="col-md-6"><label class="form-label" for="admin_phone">الهاتف (للدخول بـ OTP)</label><input class="form-control" id="admin_phone" name="admin_phone" type="tel" dir="ltr"></div>
      <div class="col-md-6"><label class="form-label" for="admin_password">كلمة المرور</label><input class="form-control" id="admin_password" name="admin_password" type="password" autocomplete="new-password" required></div>
      <div class="col-md-6"><label class="form-label" for="admin_password_confirm">تأكيد كلمة المرور</label><input class="form-control" id="admin_password_confirm" name="admin_password_confirm" type="password" autocomplete="new-password" required></div>
    </div>
    <button class="btn btn-primary w-100" type="submit">تثبيت المنصة</button>
  </form>
  <?php else: ?><div class="alert alert-danger mb-0">أصلح المتطلبات الناقصة ثم أعد تحميل الصفحة.</div><?php endif; ?>
</div>
