<?php $ic = ['ok' => ['check-circle-fill', 'success', 'سليم'], 'warn' => ['exclamation-triangle-fill', 'warning', 'تحذير'], 'bad' => ['x-octagon-fill', 'danger', 'خطر']]; ?>
<div class="page-head"><div><h1 class="page-title">الفحص الأمني الدوري</h1><p class="page-sub">مراجعة سريعة لإعدادات التشغيل الحساسة. يُنصح بإجرائها شهريًا وبعد كل تحديث.</p></div>
  <form method="post" action="<?= url('api/v1/platform/security-check/reviewed') ?>" data-ajax data-reload><?= csrf_field() ?><button class="btn btn-primary"><i class="bi bi-clipboard-check"></i> تمت المراجعة</button></form></div>
<div class="row g-3 mb-3">
  <div class="col-6 col-lg-3"><div class="card-s stat"><div class="ico"><i class="bi bi-shield-check"></i></div><div><div class="num"><?= (int)$s['score'] ?>%</div><div class="lbl">درجة الأمان</div></div></div></div>
  <div class="col-6 col-lg-3"><div class="card-s stat"><div class="ico"><i class="bi bi-x-octagon"></i></div><div><div class="num"><?= (int)$s['bad'] ?></div><div class="lbl">بنود خطرة</div></div></div></div>
  <div class="col-6 col-lg-3"><div class="card-s stat"><div class="ico"><i class="bi bi-exclamation-triangle"></i></div><div><div class="num"><?= (int)$s['warn'] ?></div><div class="lbl">تحذيرات</div></div></div></div>
  <div class="col-6 col-lg-3"><div class="card-s stat"><div class="ico"><i class="bi bi-calendar-check"></i></div><div><div class="num" style="font-size:1rem"><?= $s['last_review'] ? e(substr($s['last_review'], 0, 10)) : '—' ?></div><div class="lbl">آخر مراجعة<?= $s['review_due'] ? ' <span class="badge text-bg-warning">مستحقة</span>' : '' ?></div></div></div></div>
</div>
<div class="card-s"><div class="list-group list-group-flush">
<?php foreach ($checks as $c): [$i, $cl, $lbl] = $ic[$c['level']]; ?>
  <div class="list-group-item bg-transparent d-flex gap-3 align-items-start"><i class="bi bi-<?= $i ?> text-<?= $cl ?> fs-4" aria-label="<?= $lbl ?>"></i>
    <div class="flex-grow-1"><div class="d-flex flex-wrap gap-2 align-items-center"><strong><?= e($c['title']) ?></strong><span class="badge text-bg-<?= $cl ?>"><?= $lbl ?></span><span class="small text-muted" dir="auto"><?= e($c['detail']) ?></span></div>
      <?php if ($c['level'] !== 'ok' && $c['fix'] !== ''): ?><div class="small mt-1"><i class="bi bi-wrench"></i> <?= e($c['fix']) ?></div><?php endif; ?></div></div>
<?php endforeach; ?></div></div>
<p class="small text-muted mt-2">البيانات الحساسة (الهوية الوطنية والملاحظات الصحية والإرشادية ومفاتيح المزودين) مشفّرة بمفتاح APP_ENC_KEY، وكل العمليات الحساسة تُسجَّل في «سجل التدقيق»، ومحاولات الدخول والرفض في «الأمان».</p>
