<div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
  <?php foreach (['open' => 'مفتوحة', 'handled' => 'المعالَجة', 'all' => 'الكل'] as $k => $l): ?><a class="badge text-bg-<?= $status === $k ? 'primary' : 'secondary' ?> text-decoration-none fs-6" href="<?= url('school/risk?status=' . $k) ?>"><?= $l ?></a><?php endforeach; ?>
  <span class="ms-auto d-flex gap-2"><?php if ($can_act): ?><a class="btn btn-outline-secondary btn-sm" href="<?= url('school/risk/settings') ?>"><i class="bi bi-sliders"></i> المؤشرات</a><?php endif; ?><button class="btn btn-primary btn-sm" data-api="<?= url('api/v1/school/risk/scan') ?>" data-reload><i class="bi bi-arrow-repeat"></i> فحص الآن</button></span>
</div>
<div class="alert alert-secondary small">التنبيهات <strong>مؤشرات للمتابعة وليست أحكامًا</strong>. كل تنبيه يوضّح سببه، والقرار للمرشد أو الإدارة. لا تُرسل إشعارات تلقائية لأولياء الأمور.</div>
<?php if (!$groups): ?><div class="card-s empty-state">لا توجد تنبيهات. اضغط «فحص الآن» بعد تسجيل الحضور والدرجات.</div><?php endif; ?>
<?php foreach ($groups as $g): ?><div class="card-s mb-3"><div class="d-flex justify-content-between"><h2 class="h6 mb-1"><?= e($g['student']['name']) ?> <small class="text-muted"><?= e($g['student']['no']) ?> · <?= e(trim($g['student']['class'], ' /')) ?></small></h2></div>
<?php foreach ($g['flags'] as $f): ?><div class="border-top py-2"><div><span class="badge text-bg-warning"><?= e($f['label'] ?? $f['indicator_code']) ?></span> <span class="small"><?= e($f['explanation'] ?? $f['reason'] ?? '') ?></span></div>
 <?php if ($f['status'] !== 'open'): ?><div class="small text-muted">الحالة: <?= e(['reviewed' => 'روجع', 'dismissed' => 'استُبعد', 'case_opened' => 'فُتحت حالة'][$f['status']] ?? $f['status']) ?> <?= $f['note'] ? '— ' . e($f['note']) : '' ?></div>
 <?php elseif ($can_act): ?><div class="d-flex flex-wrap gap-1 mt-1">
  <button class="btn btn-sm btn-outline-primary" data-api="<?= url('api/v1/school/risk/' . (int)$f['id'] . '/case') ?>" data-body='{}' data-redirect>فتح حالة إرشاد</button>
  <button class="btn btn-sm btn-outline-success" data-api="<?= url('api/v1/school/risk/' . (int)$f['id'] . '/review') ?>" data-prompt="ملاحظة المراجعة" data-prompt-field="note" data-reload>تمت المراجعة</button>
  <button class="btn btn-sm btn-outline-secondary" data-api="<?= url('api/v1/school/risk/' . (int)$f['id'] . '/dismiss') ?>" data-prompt="سبب الاستبعاد (مطلوب)" data-prompt-field="note" data-reload>استبعاد</button></div><?php endif; ?></div><?php endforeach; ?></div><?php endforeach; ?>
