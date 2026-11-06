<?php $has = (bool)\App\Services\Brand::schoolLogoPath(); ?>
<div class="page-head"><div><h1 class="page-title">هوية المدرسة</h1><p class="page-sub">الاسم والشعار يظهران لكل مستخدمي المدرسة في الشريط الجانبي وأعلى كل صفحة.</p></div></div>
<form class="card-s" style="max-width:760px" method="post" action="<?= url('api/v1/school/identity') ?>" data-ajax data-multipart data-reload enctype="multipart/form-data" novalidate><?= csrf_field() ?>
  <div class="d-flex align-items-center gap-3 mb-3"><img id="logoPrev" alt="شعار المدرسة" src="<?= $has ? e(url('school/logo')) . '?v=' . time() : '' ?>" class="school-mark school-mark-lg" style="<?= $has ? '' : 'display:none' ?>">
    <div class="flex-grow-1"><label class="form-label" for="lg">شعار المدرسة (PNG أو JPG أو WebP، حتى 1 ميغابايت)</label><input class="form-control" type="file" id="lg" name="logo" accept="image/png,image/jpeg,image/webp" data-preview="#logoPrev">
    <?php if ($has): ?><div class="form-check mt-1"><input type="hidden" name="remove_logo" value="0"><input class="form-check-input" type="checkbox" name="remove_logo" value="1" id="rl"><label class="form-check-label" for="rl">حذف الشعار الحالي</label></div><?php endif; ?></div></div>
  <div class="row g-2">
    <div class="col-md-8"><label class="form-label" for="n">اسم المدرسة</label><input class="form-control" id="n" name="name" required value="<?= e($s['name'] ?? '') ?>"></div>
    <div class="col-md-4"><label class="form-label" for="ne">الاسم بالإنجليزية</label><input class="form-control" id="ne" name="name_en" dir="ltr" value="<?= e($s['name_en'] ?? '') ?>"></div>
    <?= geo_fields($s['governorate'] ?? null, $s['city'] ?? null) ?>
    <div class="col-12"><label class="form-label" for="a">العنوان</label><input class="form-control" id="a" name="address" value="<?= e($s['address'] ?? '') ?>"></div>
    <div class="col-md-6"><label class="form-label" for="p">الهاتف</label><input class="form-control" id="p" name="phone" dir="ltr" value="<?= e($s['phone'] ?? '') ?>"></div>
    <div class="col-md-6"><label class="form-label" for="e">البريد</label><input class="form-control" id="e" name="email" type="email" dir="ltr" value="<?= e($s['email'] ?? '') ?>"></div>
  </div>
  <button class="btn btn-primary mt-3">حفظ</button>
</form>
