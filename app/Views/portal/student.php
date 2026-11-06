<?php $a = $attendance; ?>
<div class="d-flex flex-wrap gap-2 align-items-center mb-3"><h2 class="h4 mb-0"><?= e($student['full_name']) ?></h2><a class="btn btn-sm btn-outline-primary ms-auto" href="<?= url('portal/student/' . (int)$student['id'] . '/transcript') ?>"><i class="bi bi-journal-text"></i> السجل الأكاديمي</a><span class="text-muted"><?= $student['grade_name'] ? e($student['grade_name'] . ' / ' . $student['section_name']) : '' ?></span><code dir="ltr"><?= e($student['student_no']) ?></code>
  <span class="ms-auto d-flex gap-2"><?php if (\App\Services\SubscriptionService::moduleEnabled(\App\Core\Tenant::require(), 'fees')): ?><a class="btn btn-outline-success" href="<?= url('portal/fees/' . (int)$student['id']) ?>"><i class="bi bi-cash-coin"></i> الرسوم</a><?php endif; ?>
  <?php if ($multi): ?><a class="btn btn-outline-primary" href="<?= url('portal') ?>"><i class="bi bi-arrow-right"></i> أبنائي</a><?php endif; ?></span></div>
<?php if (!$year || !$a): ?><div class="card-s empty-state"><i class="bi bi-inbox"></i>لا توجد بيانات دراسية للسنة الحالية بعد.</div><?php else: ?>
<div class="row g-3 mb-3">
  <?php foreach ([['bi-calendar-check', 'نسبة الحضور (30 يومًا)', $a['pct'] === null ? '—' : $a['pct'] . '%'], ['bi-x-circle', 'أيام الغياب', $a['absent']], ['bi-clock-history', 'مرات التأخر', $a['late']], ['bi-journal-check', 'بعذر', $a['excused']]] as [$ic, $lb, $n]): ?>
  <div class="col-6 col-lg-3"><div class="card-s stat"><div class="ico"><i class="bi <?= $ic ?>"></i></div><div><div class="num fs-4"><?= e($n) ?></div><div class="lbl"><?= e($lb) ?></div></div></div></div><?php endforeach; ?></div>
<div class="row g-3">
  <div class="col-lg-5"><div class="card-s h-100"><h3 class="h6">آخر حالات الغياب/التأخر</h3>
    <?php if (!$recent): ?><div class="empty-state">لا توجد حالات. 👏</div><?php else: ?><ul class="list-group list-group-flush"><?php foreach ($recent as $r): ?><li class="list-group-item bg-transparent d-flex justify-content-between"><span dir="ltr"><?= e($r['d']) ?></span>
      <span class="badge text-bg-<?= ['absent' => 'danger', 'late' => 'warning', 'excused' => 'info'][$r['status']] ?>"><?= ['absent' => 'غائب', 'late' => 'متأخر', 'excused' => 'بعذر'][$r['status']] ?></span></li><?php endforeach; ?></ul><?php endif; ?></div></div>
  <div class="col-lg-7"><div class="card-s h-100"><h3 class="h6">الشهادات</h3>
    <?php if (!$cards): ?><div class="empty-state">لا توجد شهادات مُصدرة بعد.</div><?php else: ?><div class="d-flex flex-wrap gap-2"><?php foreach ($cards as $c): ?><a class="btn btn-outline-primary" target="_blank" href="<?= url('portal/report-cards/' . $c['id'] . '/print') ?>"><i class="bi bi-award"></i> <?= e($c['term']) ?> — <?= (float)$c['average'] ?>% (<?= e($c['result']) ?>)</a><?php endforeach; ?></div><?php endif; ?></div></div>
</div>
<h3 class="h5 mt-4">الدرجات</h3>
<?php if (!$terms): ?><div class="card-s empty-state"><i class="bi bi-clipboard-data"></i>لم تُعتمد درجات بعد. تظهر الدرجات هنا بعد اعتمادها من المدرسة.</div><?php endif; ?>
<?php foreach ($terms as $t): ?><div class="card-s mb-3"><div class="d-flex flex-wrap gap-2 align-items-center mb-2"><h4 class="h6 mb-0"><?= e($t['name']) ?></h4>
  <?php if ($t['provisional']): ?><span class="badge text-bg-warning">نتيجة مؤقتة (لم تكتمل/تُعتمد كل التقييمات)</span><?php endif; ?><span class="ms-auto">المعدل: <strong><?= (float)$t['average'] ?>%</strong> — <?= e($t['letter']) ?> — الترتيب <?= (int)$t['rank'] ?> من <?= (int)$t['class_size'] ?> — <strong class="<?= $t['result'] === 'ناجح' ? 'text-success' : 'text-danger' ?>"><?= e($t['result']) ?></strong></span></div>
  <div class="table-responsive"><table class="table table-sm align-middle table-cards mb-0"><thead><tr><th>المادة</th><th>الدرجة (من <?= (float)$t['max'] ?>)</th><th>النسبة %</th><th>التقدير</th></tr></thead><tbody>
  <?php foreach ($t['subjects'] as $s): ?><tr><td data-label="المادة"><?= e($s['name']) ?></td><td data-label="الدرجة" class="<?= $s['passed'] ? '' : 'text-danger fw-bold' ?>"><?= (float)$s['total'] ?></td><td data-label="النسبة %"><?= (float)$s['percent'] ?></td><td data-label="التقدير"><?= e($s['letter']) ?></td></tr><?php endforeach; ?></tbody></table></div></div><?php endforeach; ?>
<?php endif; ?>
