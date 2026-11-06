<form class="card-s" method="post" action="<?= url('api/v1/platform/schools') ?>" data-ajax novalidate style="max-width:880px">
  <?= csrf_field() ?>
  <h2 class="h5 mb-3">بيانات المدرسة</h2>
  <div class="row g-3">
    <div class="col-md-8"><label class="form-label" for="name">اسم المدرسة *</label><input class="form-control" id="name" name="name" required></div>
    <div class="col-md-4"><label class="form-label" for="code">رمز المدرسة (اختياري)</label><input class="form-control" id="code" name="code" dir="ltr" maxlength="30"></div>
    <?= geo_fields(null, null, 'col-md-4') ?>
    <div class="col-md-4"><label class="form-label" for="phone">هاتف المدرسة</label><input class="form-control" id="phone" name="phone" dir="ltr"></div>
    <div class="col-md-6"><label class="form-label" for="email">بريد المدرسة</label><input class="form-control" id="email" name="email" type="email" dir="ltr"></div>
    <div class="col-md-6"><label class="form-label" for="address">العنوان</label><input class="form-control" id="address" name="address"></div>
    <div class="col-md-6"><label class="form-label" for="gender_policy">نوع المدرسة</label>
      <select class="form-select" id="gender_policy" name="gender_policy"><option value="mixed">مختلطة</option><option value="boys">بنين</option><option value="girls">بنات</option></select></div>
    <div class="col-md-6"><label class="form-label" for="base_currency">العملة الأساسية</label>
      <select class="form-select" id="base_currency" name="base_currency"><option value="YER">ريال يمني</option><option value="USD">دولار</option><option value="SAR">ريال سعودي</option></select></div>
  </div>
  <h2 class="h5 mt-4 mb-3">الاشتراك</h2>
  <div class="row g-3">
    <div class="col-md-4"><label class="form-label" for="plan_id">الخطة *</label>
      <select class="form-select" id="plan_id" name="plan_id" required><option value="">اختر…</option>
        <?php foreach ($plans as $p): if (!$p['is_active']) continue; ?><option value="<?= (int)$p['id'] ?>"><?= e($p['name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-4"><label class="form-label" for="starts_on">بداية الاشتراك</label><input class="form-control" id="starts_on" name="starts_on" type="date" value="<?= date('Y-m-d') ?>"></div>
    <div class="col-md-4"><label class="form-label" for="ends_on">نهاية الاشتراك</label><input class="form-control" id="ends_on" name="ends_on" type="date"></div>
  </div>
  <h2 class="h5 mt-4 mb-3">مدير المدرسة</h2>
  <div class="row g-3">
    <div class="col-md-4"><label class="form-label" for="admin_name">الاسم *</label><input class="form-control" id="admin_name" name="admin_name" required></div>
    <div class="col-md-4"><label class="form-label" for="admin_email">البريد *</label><input class="form-control" id="admin_email" name="admin_email" type="email" dir="ltr" required></div>
    <div class="col-md-4"><label class="form-label" for="admin_phone">الهاتف (للدخول برمز SMS)</label><input class="form-control" id="admin_phone" name="admin_phone" dir="ltr"></div>
  </div>
  <p class="text-muted small mt-3">تُنشأ كلمة مرور مؤقتة تظهر مرة واحدة، ويُلزَم المدير بتغييرها عند أول دخول.</p>
  <div class="alert alert-success d-none mt-3" data-result></div>
  <button class="btn btn-primary" type="submit">إنشاء المدرسة</button>
  <a class="btn btn-outline-secondary" href="<?= url('platform/schools') ?>"><?= e(__t('common.cancel')) ?></a>
</form>
