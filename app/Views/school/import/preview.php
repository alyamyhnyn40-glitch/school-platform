<?php $done = $job['status'] === 'committed'; $jid = (int)$job['id']; ?>
<div class="d-flex flex-wrap gap-2 align-items-center mb-3"><h2 class="h5 mb-0"><?= e($kinds[$job['kind']] ?? '') ?> — <?= e($job['original_name']) ?></h2><span class="badge text-bg-<?= $done ? 'success' : 'warning' ?>"><?= $done ? 'معتمد' : 'معاينة — لم يُحفظ شيء بعد' ?></span>
  <a class="btn btn-outline-secondary ms-auto" href="<?= url('school/import') ?>">رجوع</a></div>
<div class="row g-3 mb-3"><?php foreach ([['إجمالي الصفوف', $job['total_rows'], 'primary'], [$done ? 'تم استيرادها' : 'صالحة للاستيراد', $job['ok_rows'], 'success'], ['بها أخطاء', $job['error_rows'], 'danger']] as [$l, $n, $c]): ?>
  <div class="col-4"><div class="card-s text-center"><div class="fs-3 fw-bold text-<?= $c ?>"><?= (int)$n ?></div><div class="small text-muted"><?= $l ?></div></div></div><?php endforeach; ?></div>
<div class="d-flex flex-wrap gap-2 mb-3">
  <?php if (!$done && (int)$job['ok_rows'] > 0): ?><button class="btn btn-success" data-api="<?= url("api/v1/school/import/$jid/commit") ?>" data-body='{"accounts":true}' data-confirm="سيُستورد <?= (int)$job['ok_rows'] ?> صف سليم فقط. متابعة؟"><i class="bi bi-check2-circle"></i> اعتماد الصفوف السليمة (<?= (int)$job['ok_rows'] ?>)</button><?php endif; ?>
  <?php if ((int)$job['error_rows'] > 0): ?><a class="btn btn-outline-danger" href="<?= url("school/import/$jid/errors.xlsx") ?>"><i class="bi bi-file-earmark-excel"></i> تقرير الأخطاء</a>
    <a class="btn btn-outline-secondary" href="<?= url("school/import/$jid" . ($onlyErrors ? '' : '?errors=1')) ?>"><?= $onlyErrors ? 'عرض الكل' : 'عرض الأخطاء فقط' ?></a><?php endif; ?>
  <?php if (!$done): ?><button class="btn btn-outline-danger ms-auto" data-api="<?= url("api/v1/school/import/$jid") ?>" data-method="DELETE" data-confirm="حذف هذه العملية؟">إلغاء</button><?php endif; ?></div>
<div class="card-s"><?php if (!$rows): ?><div class="empty-state">لا توجد صفوف.</div><?php else: ?>
<div class="table-responsive"><table class="table table-sm align-middle"><thead><tr><th>#</th><th>الحالة</th><th>البيانات</th><th>الأخطاء</th></tr></thead><tbody>
<?php foreach ($rows as $x): ?><tr class="<?= $x['status'] === 'error' ? 'table-danger' : '' ?>"><td><?= (int)$x['row_no'] ?></td><td><?= ['ok' => '✔ سليم', 'error' => '✘ خطأ', 'imported' => '✔ استُورد'][$x['status']] ?></td>
  <td class="small"><?php $tr = ['male' => 'ذكر', 'female' => 'أنثى', 'father' => 'أب', 'mother' => 'أم', 'brother' => 'أخ', 'sister' => 'أخت', 'uncle' => 'عم/خال', 'aunt' => 'عمة/خالة', 'grandfather' => 'جد', 'grandmother' => 'جدة', 'other' => 'آخر'];
  $show = []; foreach ($x['data'] as $k => $v) { if (is_scalar($v) && $v !== '' && !str_ends_with((string)$k, '_id') && !in_array($k, ['is_teacher', 'is_primary'], true)) { $show[] = $tr[$v] ?? $v; } } ?><?= e(implode(' · ', $show)) ?></td><td class="small text-danger"><?= e(implode(' | ', $x['errors'])) ?></td></tr><?php endforeach; ?></tbody></table></div>
<?php if ((int)$job['total_rows'] > 200): ?><p class="small text-muted mb-0">تُعرض أول 200 صف. تقرير الأخطاء يحوي الكل.</p><?php endif; ?><?php endif; ?></div>
