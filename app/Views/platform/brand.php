<?= \App\Core\View::partial('settings_tabs', ['active' => 'brand']) ?>
<?php $b = $b; ?>
<form class="card-s" method="post" action="<?= url('api/v1/platform/settings/brand') ?>" data-ajax data-multipart data-reload enctype="multipart/form-data" novalidate><?= csrf_field() ?>
<div class="row g-3">
 <div class="col-lg-7">
  <h2 class="h6">هوية المنصة</h2>
  <div class="row g-2 mb-2"><div class="col-md-6"><label class="form-label" for="n">اسم المنصة (عربي) *</label><input class="form-control" id="n" name="name" value="<?= e($b['name']) ?>" required></div><div class="col-md-6"><label class="form-label" for="ne">الاسم بالإنجليزية</label><input class="form-control" id="ne" name="name_en" dir="ltr" value="<?= e($b['name_en']) ?>"></div></div>
  <div class="mb-2"><label class="form-label" for="tg">الشعار النصي (وصف قصير)</label><input class="form-control" id="tg" name="tagline" value="<?= e($b['tagline']) ?>"></div>
  <div class="row g-2 mb-3 align-items-end"><div class="col-md-4"><label class="form-label" for="cl">لون الهوية</label><input class="form-control form-control-color w-100" type="color" id="cl" name="color" value="<?= e(\App\Services\Brand::color()) ?>"></div><div class="col-md-8 small text-muted">يُطبَّق على الأزرار والروابط والقوائم في كل الواجهات.</div></div>
  <div class="mb-3"><label class="form-label" for="lg">الشعار (PNG أو JPG أو WebP، حتى 1 ميغابايت)</label><input class="form-control" type="file" id="lg" name="logo" accept="image/png,image/jpeg,image/webp" data-preview="#logoPrev"><div class="form-text">يُعاد ترميز الصورة وتُصغَّر إلى 512 بكسل لأمانك. SVG غير مسموح.</div>
   <div class="d-flex align-items-center gap-3 mt-2"><img id="logoPrev" alt="معاينة الشعار" src="<?= $hasLogo ? e(url('brand/logo')) . '?v=' . time() : '' ?>" style="max-height:70px;max-width:220px;object-fit:contain;<?= $hasLogo ? '' : 'display:none' ?>" class="border rounded p-1 bg-white">
    <?php if ($hasLogo): ?><div class="form-check"><input type="hidden" name="remove_logo" value="0"><input class="form-check-input" type="checkbox" name="remove_logo" value="1" id="rl"><label class="form-check-label" for="rl">حذف الشعار الحالي</label></div><?php endif; ?></div></div>
  <h2 class="h6 mt-4">بيانات التواصل</h2>
  <div class="row g-2 mb-2"><div class="col-md-6"><label class="form-label" for="em">البريد</label><input class="form-control" id="em" name="contact_email" dir="ltr" type="email" value="<?= e($b['contact_email']) ?>"></div><div class="col-md-6"><label class="form-label" for="ph">الهاتف</label><input class="form-control" id="ph" name="contact_phone" dir="ltr" value="<?= e($b['contact_phone']) ?>"></div>
   <div class="col-md-6"><label class="form-label" for="wa">واتساب</label><input class="form-control" id="wa" name="contact_whatsapp" dir="ltr" value="<?= e($b['contact_whatsapp']) ?>"></div><div class="col-md-6"><label class="form-label" for="wb">الموقع</label><input class="form-control" id="wb" name="website" dir="ltr" value="<?= e($b['website']) ?>" placeholder="https://"></div></div>
  <div class="mb-2"><label class="form-label" for="ad">العنوان</label><input class="form-control" id="ad" name="address" value="<?= e($b['address']) ?>"></div>
  <div class="mb-2"><label class="form-label" for="ft">نص التذييل (صفحة الهبوط)</label><input class="form-control" id="ft" name="footer" value="<?= e($b['footer']) ?>"></div>
 </div>
 <div class="col-lg-5">
  <h2 class="h6">إعدادات عامة</h2>
  <div class="form-check form-switch mb-2"><input type="hidden" name="allow_school_requests" value="0"><input class="form-check-input" type="checkbox" role="switch" name="allow_school_requests" value="1" id="ar" <?= $b['allow_school_requests'] === '1' ? 'checked' : '' ?>><label class="form-check-label" for="ar">استقبال طلبات تسجيل المدارس من صفحة الهبوط</label></div>
  <div class="form-check form-switch mb-2"><input type="hidden" name="allow_online_payments" value="0"><input class="form-check-input" type="checkbox" role="switch" name="allow_online_payments" value="1" id="op" <?= $b['allow_online_payments'] === '1' ? 'checked' : '' ?>><label class="form-check-label" for="op">السماح بالدفع الإلكتروني على مستوى المنصة (إيقافه يخفيه عن كل المدارس)</label></div>
  <div class="form-check form-switch mb-2"><input type="hidden" name="maintenance" value="0"><input class="form-check-input" type="checkbox" role="switch" name="maintenance" value="1" id="mt" <?= $b['maintenance'] === '1' ? 'checked' : '' ?>><label class="form-check-label" for="mt">وضع الصيانة (يمنع كل المستخدمين عدا المشغّل)</label></div>
  <div class="mb-3"><label class="form-label" for="mm">رسالة الصيانة</label><input class="form-control" id="mm" name="maintenance_message" value="<?= e($b['maintenance_message']) ?>"></div>
  <?php if ($b['maintenance'] === '1'): ?><div class="alert alert-warning small">وضع الصيانة <strong>مفعّل الآن</strong>: المدارس لا تستطيع الدخول.</div><?php endif; ?>
  <div class="card-s bg-body-tertiary small"><div class="fw-semibold mb-1">معاينة</div><div class="d-flex align-items-center gap-2"><span class="rounded-circle d-inline-block" style="width:18px;height:18px;background:<?= e(\App\Services\Brand::color()) ?>"></span><strong><?= e($b['name']) ?></strong></div><div class="text-muted"><?= e($b['tagline']) ?></div></div>
 </div>
</div>
<button class="btn btn-primary mt-3">حفظ</button></form>
