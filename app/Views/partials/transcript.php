<?php /** @var array $t  (TranscriptService) ; $print bool */
$fmt = fn($v) => $v === null ? '—' : rtrim(rtrim(number_format((float)$v, 2), '0'), '.');
$c = $t['cumulative'];
?>
<div class="row g-3 mb-3">
  <div class="col-6 col-md-3"><div class="card-s stat"><div class="ico"><i class="bi bi-graph-up"></i></div><div><div class="num"><?= $c['average'] === null ? '—' : $fmt($c['average']) . '%' ?></div><div class="lbl">المعدل التراكمي</div></div></div></div>
  <div class="col-6 col-md-3"><div class="card-s stat"><div class="ico"><i class="bi bi-award"></i></div><div><div class="num"><?= $c['gpa'] === null ? '—' : $fmt($c['gpa']) ?></div><div class="lbl">GPA التراكمي</div></div></div></div>
  <div class="col-6 col-md-3"><div class="card-s stat"><div class="ico"><i class="bi bi-calendar-check"></i></div><div><div class="num"><?= (int)$c['years_passed'] ?>/<?= (int)$c['years'] ?></div><div class="lbl">سنوات ناجحة/محسوبة</div></div></div></div>
  <div class="col-6 col-md-3"><div class="card-s stat"><div class="ico"><i class="bi bi-hourglass-split"></i></div><div><div class="num"><?= $fmt($c['hours']) ?></div><div class="lbl">الساعات المعتمدة</div></div></div></div>
</div>
<?php if (!$t['years']): ?><div class="card-s empty-state"><i class="bi bi-journal-x"></i>لا توجد نتائج مسجلة بعد لهذا الطالب.</div><?php endif; ?>
<?php foreach (array_reverse($t['years']) as $y): ?>
<section class="card-s mb-3 transcript-year">
  <div class="d-flex flex-wrap align-items-center gap-2 mb-2"><h2 class="h6 mb-0"><i class="bi bi-calendar3"></i> <?= e($y['year']) ?> — <?= e($y['grade']) ?> / <?= e($y['section']) ?></h2>
    <?= $y['frozen'] ? '<span class="badge text-bg-secondary"><i class="bi bi-lock"></i> سجل مجمَّد</span>' : ($y['year_status'] === 'closed' ? '<span class="badge text-bg-light border">سنة مغلقة — محسوبة من الدرجات</span>' : '<span class="badge text-bg-info">السنة الجارية</span>') ?>
    <?php if ($y['annual'] && $y['annual']['average'] !== null): ?><span class="ms-auto badge text-bg-<?= $y['annual']['result'] === 'ناجح' ? 'success' : 'danger' ?> fs-6">المعدل السنوي <?= $fmt($y['annual']['average']) ?>% <?= e($y['annual']['letter'] ?? '') ?> · <?= e($y['annual']['result'] ?? '') ?></span><?php endif; ?></div>
  <?php if (!$y['terms'] && !$y['annual']): ?><div class="small text-muted">لا توجد درجات لهذه السنة.</div><?php endif; ?>
  <?php $all = []; foreach ($y['terms'] as $tm) { foreach ($tm['subjects'] as $s) { $all[$s['subject']] = $s['credit_hours']; } } if ($y['annual']) { foreach ($y['annual']['subjects'] as $s) { $all[$s['subject']] = $s['credit_hours']; } } ksort($all); ?>
  <?php if ($all): ?><div class="table-responsive"><table class="table table-sm align-middle transcript-table mb-0">
    <thead><tr><th>المادة</th><th>ساعات</th><?php foreach ($y['terms'] as $tm): ?><th><?= e($tm['name']) ?></th><?php endforeach; ?><?php if ($y['annual']): ?><th>السنوي</th><th>التقدير</th><?php endif; ?></tr></thead><tbody>
    <?php foreach ($all as $name => $h): ?><tr><td><?= e($name) ?></td><td><?= $fmt($h) ?></td>
      <?php foreach ($y['terms'] as $tm): $x = null; foreach ($tm['subjects'] as $s) { if ($s['subject'] === $name) { $x = $s; } } ?><td class="<?= $x && $x['passed'] === false ? 'text-danger fw-bold' : '' ?>"><?= $x ? $fmt($x['total']) : '<span class="text-muted">—</span>' ?></td><?php endforeach; ?>
      <?php if ($y['annual']): $x = null; foreach ($y['annual']['subjects'] as $s) { if ($s['subject'] === $name) { $x = $s; } } ?><td class="fw-bold <?= $x && $x['passed'] === false ? 'text-danger' : '' ?>"><?= $x ? $fmt($x['total']) : '—' ?></td><td><?= e($x['letter'] ?? '—') ?></td><?php endif; ?></tr><?php endforeach; ?>
    </tbody><tfoot><tr class="table-light"><th>المعدل</th><th></th><?php foreach ($y['terms'] as $tm): ?><th><?= $fmt($tm['average']) ?>%<div class="small fw-normal"><?= $tm['rank'] ? 'الترتيب ' . (int)$tm['rank'] . ($tm['size'] ? '/' . (int)$tm['size'] : '') : '' ?><?= $tm['gpa'] !== null ? ' · GPA ' . $fmt($tm['gpa']) : '' ?></div></th><?php endforeach; ?>
      <?php if ($y['annual']): ?><th><?= $fmt($y['annual']['average']) ?>%<div class="small fw-normal"><?= $y['annual']['rank'] ? 'الترتيب ' . (int)$y['annual']['rank'] : '' ?><?= $y['annual']['gpa'] !== null ? ' · GPA ' . $fmt($y['annual']['gpa']) : '' ?></div></th><th><?= e($y['annual']['letter'] ?? '') ?></th><?php endif; ?></tr></tfoot></table></div><?php endif; ?>
</section>
<?php endforeach; ?>
