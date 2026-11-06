<?php foreach ($cards as $c): ?>
<section class="card-a4"><div class="frame">
  <div class="sub">الجمهورية اليمنية</div><h1><?= e($c['school']['name']) ?></h1>
  <div class="sub"><?= e($c['school']['city'] ?? '') ?></div>
  <h2 class="h4 text-center my-3"><?= $c['is_final'] ? 'الشهادة النهائية' : 'شهادة نتائج ' . e($c['term']) ?></h2>
  <?php if (!empty($c['incomplete'])): ?><div class="draft">مسوّدة — نتائج غير مكتملة أو غير معتمدة</div><?php endif; ?>
  <table class="meta"><tr><td>اسم الطالب: <strong><?= e($c['student']['name']) ?></strong></td><td>رقم القيد: <strong><?= e($c['student']['no']) ?></strong></td></tr>
    <tr><td>الصف/الشعبة: <strong><?= e($c['section']) ?></strong></td><td>العام الدراسي: <strong><?= e($c['year']) ?></strong></td></tr></table>
  <table><thead><tr><th>المادة</th><th>الدرجة (من <?= (float)$c['scheme']['max_score'] ?>)</th><th>النسبة %</th><th>التقدير</th></tr></thead><tbody>
    <?php foreach ($c['subjects'] as $s): ?><tr><td><?= e($s['name']) ?></td><td class="<?= $s['passed'] ? '' : 'fail' ?>"><?= (float)$s['total'] ?></td><td><?= (float)$s['percent'] ?></td><td><?= e($s['letter']) ?></td></tr><?php endforeach; ?></tbody></table>
  <table class="meta"><tr><td>المعدل: <strong><?= (float)$c['average'] ?>%</strong></td><td>التقدير: <strong><?= e($c['letter']) ?></strong></td><td>الترتيب: <strong><?= (int)$c['rank'] ?> من <?= (int)$c['class_size'] ?></strong></td></tr>
    <tr><td colspan="3">النتيجة: <strong class="<?= $c['result'] === 'ناجح' ? '' : 'fail' ?>"><?= e($c['result']) ?></strong></td></tr>
    <tr><td colspan="3">الغياب: <?= (int)$c['attendance']['absent'] ?> يوم — التأخر: <?= (int)$c['attendance']['late'] ?> — بعذر: <?= (int)$c['attendance']['excused'] ?></td></tr></table>
  <div class="sign"><div>مربّي الشعبة</div><div>ختم المدرسة</div><div>مدير المدرسة</div></div>
  <div class="text-center small mt-3 text-muted">تاريخ الإصدار: <?= e($c['issued_at']) ?></div>
</div></section>
<?php endforeach; ?>
