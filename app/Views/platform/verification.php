<?= \App\Core\View::partial('settings_tabs', ['active' => 'verification']) ?>
<div class="card-s mb-3">
  <h1 class="h5 mb-1">إعدادات رموز التحقق (OTP)</h1>
  <p class="small text-muted mb-0">اضبط مدة صلاحية كل رمز، وعدد محاولات إدخاله، وحدّ طلب الرموز. إعدادات البريد تخص استعادة كلمة المرور؛ وSMS يخص دخول ولي الأمر.</p>
</div>
<form method="post" action="<?= url('api/v1/platform/settings/verification') ?>" data-ajax data-reload novalidate><?= csrf_field() ?>
<div class="row g-3">
  <div class="col-lg-6"><div class="card-s h-100">
    <div class="d-flex justify-content-between align-items-start"><h2 class="h6"><i class="bi bi-phone"></i> رمز التحقق عبر الجوال (SMS)</h2>
      <?= $v['sms_ready'] ? '<span class="badge text-bg-success">المزود مضبوط</span>' : '<a class="badge text-bg-warning text-decoration-none" href="' . e(url('platform/settings/sms')) . '">المزود غير مضبوط — اضبطه</a>' ?></div>
    <p class="small text-muted">يستعمله ولي الأمر للدخول برقم هاتفه. حد الطلبات يُطبّق لكل رقم، إضافة إلى حد حماية عنوان IP.</p>
    <div class="row g-2 mb-2">
      <div class="col-4"><label class="form-label" for="a1">الصلاحية (دقيقة)</label><input class="form-control" id="a1" name="sms_ttl" type="number" min="2" max="30" value="<?= (int)$v['sms.ttl'] ?>"></div>
      <div class="col-4"><label class="form-label" for="a2">محاولات إدخال الرمز</label><input class="form-control" id="a2" name="sms_tries" type="number" min="3" max="10" value="<?= (int)$v['sms.tries'] ?>"></div>
      <div class="col-4"><label class="form-label" for="a3">طلبات لكل رقم/ساعة</label><input class="form-control" id="a3" name="sms_per_hour" type="number" min="1" max="20" value="<?= (int)$v['sms.per_hour'] ?>"></div></div>
    <label class="form-label" for="a4">نص الرسالة</label><textarea class="form-control" id="a4" name="sms_template" rows="3"><?= e($v['sms.template']) ?></textarea>
    <div class="form-text">الرموز: <code dir="ltr">{code}</code> (إلزامي) · <code dir="ltr">{minutes}</code> · <code dir="ltr">{app}</code> اسم المنصة. أبقِ الرسالة قصيرة لتبقى في رسالة SMS واحدة.</div>
  </div></div>
  <div class="col-lg-6"><div class="card-s h-100">
    <div class="d-flex justify-content-between align-items-start"><h2 class="h6"><i class="bi bi-envelope"></i> رمز التحقق عبر البريد</h2>
      <?= $v['email_ready'] ? '<span class="badge text-bg-success">الإعداد مكتمل</span>' : '<a class="badge text-bg-warning text-decoration-none" href="' . e(url('platform/settings/email')) . '">إعداد البريد غير مكتمل — اضبطه</a>' ?></div>
    <p class="form-text">اكتمال الإعداد لا يضمن وصول الرسالة؛ أرسل رسالة اختبار من إعدادات البريد للتحقق من الاتصال.</p>
    <p class="small text-muted">يستعمله الموظف وولي الأمر لاستعادة كلمة المرور بالبريد. حد الطلبات يُطبّق لكل بريد، إضافة إلى حد حماية عنوان IP.</p>
    <div class="row g-2 mb-2">
      <div class="col-4"><label class="form-label" for="b1">الصلاحية (دقيقة)</label><input class="form-control" id="b1" name="email_ttl" type="number" min="5" max="60" value="<?= (int)$v['email.ttl'] ?>"></div>
      <div class="col-4"><label class="form-label" for="b2">محاولات إدخال الرمز</label><input class="form-control" id="b2" name="email_tries" type="number" min="3" max="10" value="<?= (int)$v['email.tries'] ?>"></div>
      <div class="col-4"><label class="form-label" for="b3">طلبات لكل بريد/ساعة</label><input class="form-control" id="b3" name="email_per_hour" type="number" min="1" max="20" value="<?= (int)$v['email.per_hour'] ?>"></div></div>
    <label class="form-label" for="b4">عنوان الرسالة</label><input class="form-control mb-2" id="b4" name="email_subject" value="<?= e($v['email.subject']) ?>">
    <label class="form-label" for="b5">نص الرسالة</label><textarea class="form-control" id="b5" name="email_template" rows="6"><?= e($v['email.template']) ?></textarea>
    <div class="form-text">الرموز: <code dir="ltr">{code}</code> (إلزامي) · <code dir="ltr">{name}</code> · <code dir="ltr">{minutes}</code> · <code dir="ltr">{app}</code>.</div>
  </div></div>
</div>
<div class="d-flex gap-2 mt-3"><button class="btn btn-primary">حفظ الإعدادات</button><a class="btn btn-outline-secondary" href="<?= url('platform/settings/sms') ?>">إعداد مزود SMS</a><a class="btn btn-outline-secondary" href="<?= url('platform/settings/email') ?>">إعداد البريد</a></div>
</form>
