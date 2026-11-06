<?php $sid = (int)$school['id']; $st = ['active' => ['success', 'فعّالة'], 'suspended' => ['warning', 'موقوفة'], 'archived' => ['secondary', 'مؤرشفة']][$school['status']]; $plan = $stats['plan']; ?>
<div class="d-flex flex-wrap gap-2 align-items-center mb-3">
  <h2 class="h4 mb-0"><?= e($school['name']) ?></h2><span class="badge text-bg-<?= $st[0] ?>"><?= $st[1] ?></span><code dir="ltr"><?= e($school['code']) ?></code>
  <a class="btn btn-outline-primary ms-auto" href="<?= url('platform/schools') ?>"><i class="bi bi-arrow-right"></i> <?= e(__t('common.back')) ?></a>
</div>
<div class="alert alert-success d-none" data-result></div>
<div class="row g-3 mb-3">
  <?php foreach ([['bi-people', 'الطلاب', $stats['students'] . ($plan && $plan['max_students'] ? ' من ' . $plan['max_students'] : '')], ['bi-person-badge', 'الموظفون', $stats['staff']], ['bi-person-lines-fill', 'المستخدمون', $stats['users']], ['bi-box-arrow-in-right', 'دخول (30 يومًا)', $stats['logins_30d']], ['bi-hdd', 'التخزين (MB)', $stats['storage_mb'] . ($plan && $plan['max_storage_mb'] ? ' من ' . $plan['max_storage_mb'] : '')]] as [$ic, $lb, $n]): ?>
  <div class="col-6 col-xl"><div class="card-s stat"><div class="ico"><i class="bi <?= $ic ?>"></i></div><div><div class="num fs-4"><?= e($n) ?></div><div class="lbl"><?= e($lb) ?></div></div></div></div>
  <?php endforeach; ?>
</div>
<div class="row g-3">
  <div class="col-lg-7">
    <form class="card-s" method="put" action="<?= url("api/v1/platform/schools/$sid") ?>" data-ajax novalidate>
      <?= csrf_field() ?><h3 class="h6 mb-3">بيانات المدرسة</h3>
      <div class="row g-2">
        <div class="col-md-8"><label class="form-label" for="name">الاسم</label><input class="form-control" id="name" name="name" value="<?= e($school['name']) ?>" required></div>
        <?= geo_fields($school['governorate'], $school['city'], 'col-md-4') ?>
        <div class="col-md-4"><label class="form-label" for="phone">الهاتف</label><input class="form-control" id="phone" name="phone" dir="ltr" value="<?= e($school['phone']) ?>"></div>
        <div class="col-md-4"><label class="form-label" for="email">البريد</label><input class="form-control" id="email" name="email" dir="ltr" value="<?= e($school['email']) ?>"></div>
        <div class="col-12"><label class="form-label" for="address">العنوان</label><input class="form-control" id="address" name="address" value="<?= e($school['address']) ?>"></div>
        <div class="col-md-6"><label class="form-label" for="gender_policy">النوع</label><select class="form-select" id="gender_policy" name="gender_policy">
          <?php foreach (['mixed' => 'مختلطة', 'boys' => 'بنين', 'girls' => 'بنات'] as $k => $l): ?><option value="<?= $k ?>" <?= $school['gender_policy'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
        <div class="col-md-6"><label class="form-label" for="base_currency">العملة الأساسية</label><select class="form-select" id="base_currency" name="base_currency">
          <?php foreach (['YER' => 'ريال يمني', 'USD' => 'دولار', 'SAR' => 'ريال سعودي'] as $k => $l): ?><option value="<?= $k ?>" <?= $school['base_currency'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
      </div>
      <button class="btn btn-primary mt-3" type="submit"><?= e(__t('auth.save')) ?></button>
    </form>
  </div>
  <div class="col-lg-5 d-flex flex-column gap-3">
    <div class="card-s">
      <h3 class="h6 mb-3">حالة المدرسة</h3>
      <div class="d-flex flex-wrap gap-2">
        <?php foreach (['active' => ['تفعيل', 'success'], 'suspended' => ['إيقاف', 'warning'], 'archived' => ['أرشفة', 'secondary']] as $s => [$l, $c]): if ($school['status'] === $s) continue; ?>
        <form method="post" action="<?= url("api/v1/platform/schools/$sid/status") ?>" data-ajax><?= csrf_field() ?><input type="hidden" name="status" value="<?= $s ?>">
          <button class="btn btn-outline-<?= $c ?>" type="submit" data-confirm="<?= e("تأكيد: $l المدرسة؟") ?>"><?= $l ?></button></form>
        <?php endforeach; ?>
      </div>
      <?php if (can('platform.support') && $school['status'] === 'active'): ?>
      <hr><h3 class="h6">الدخول للدعم الفني</h3>
      <form method="post" action="<?= url("api/v1/platform/schools/$sid/support/enter") ?>" data-ajax novalidate><?= csrf_field() ?>
        <label class="form-label" for="reason">سبب الدخول (يُسجَّل)</label><input class="form-control mb-2" id="reason" name="reason" required minlength="5">
        <button class="btn btn-outline-primary" type="submit"><i class="bi bi-life-preserver"></i> دخول كدعم فني</button></form>
      <?php endif; ?>
      <hr><button class="btn btn-outline-danger" id="resetAdmin" type="button" data-url="<?= url("api/v1/platform/schools/$sid/reset-admin") ?>" data-confirm="سيُعاد تعيين كلمة مرور مدير المدرسة. متابعة؟"><i class="bi bi-key"></i> إعادة تعيين كلمة مرور المدير</button>
    </div>
    <form class="card-s" method="post" action="<?= url("api/v1/platform/schools/$sid/subscription") ?>" data-ajax novalidate>
      <?= csrf_field() ?><h3 class="h6 mb-3">الاشتراك الحالي: <?= $plan ? e($plan['plan_name']) : '<span class="text-danger">لا يوجد اشتراك فعّال</span>' ?></h3>
      <div class="row g-2">
        <div class="col-12"><label class="form-label" for="plan_id">الخطة</label><select class="form-select" id="plan_id" name="plan_id"><?php foreach ($plans as $p): ?><option value="<?= (int)$p['id'] ?>" <?= $plan && (int)$plan['plan_id'] === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-6"><label class="form-label" for="starts_on">من</label><input class="form-control" id="starts_on" name="starts_on" type="date" value="<?= date('Y-m-d') ?>" required></div>
        <div class="col-6"><label class="form-label" for="ends_on">إلى</label><input class="form-control" id="ends_on" name="ends_on" type="date"></div>
        <div class="col-12"><label class="form-label" for="amount">المبلغ</label><input class="form-control" id="amount" name="amount" type="number" step="0.01" min="0" value="0"></div>
      </div>
      <button class="btn btn-primary mt-3" type="submit">تحديث الاشتراك</button>
    </form>
  </div>
</div>
<div class="row g-3 mt-1">
  <div class="col-lg-6"><div class="card-s"><h3 class="h6">سجل الاشتراكات</h3>
    <?php if (!$subs): ?><div class="empty-state">لا يوجد</div><?php else: ?><ul class="list-group list-group-flush">
      <?php foreach ($subs as $s): ?><li class="list-group-item bg-transparent d-flex justify-content-between"><span><?= e($s['plan_name']) ?> — <span dir="ltr"><?= e($s['starts_on']) ?> → <?= e($s['ends_on'] ?? '∞') ?></span></span><span class="badge text-bg-<?= $s['status'] === 'active' ? 'success' : 'secondary' ?>"><?= $s['status'] === 'active' ? 'فعّال' : 'ملغى/منتهٍ' ?></span></li><?php endforeach; ?></ul><?php endif; ?></div></div>
  <div class="col-lg-6"><div class="card-s"><h3 class="h6">سجل دخول الدعم الفني</h3>
    <?php if (!$support): ?><div class="empty-state">لا يوجد</div><?php else: ?><ul class="list-group list-group-flush">
      <?php foreach ($support as $l): ?><li class="list-group-item bg-transparent"><strong><?= e($l['full_name']) ?></strong> — <?= e($l['reason']) ?><br><small class="text-muted" dir="ltr"><?= e($l['started_at']) ?> → <?= e($l['ended_at'] ?? 'مستمر') ?></small></li><?php endforeach; ?></ul><?php endif; ?></div></div>
</div>
