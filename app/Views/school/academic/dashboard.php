<?php use App\Core\Chart;
$q = fn(array $o = []) => http_build_query(array_merge(['year' => $yearId, 'term' => $termId ?? 'year', 'grade' => $gradeId], $o));
$fmt = fn($v) => $v === null ? '—' : rtrim(rtrim(number_format((float)$v, 2), '0'), '.');
$period = 'النتيجة السنوية'; foreach ($terms as $t) { if ((int)$t['id'] === (int)$termId) { $period = $t['name']; } }
?>
<div class="page-head"><div><h1 class="page-title">لوحة الأداء الأكاديمي</h1><p class="page-sub">مؤشرات النتائج ونسب النجاح والرسوب والأوائل — <?= e($period) ?></p></div>
  <div class="d-flex gap-2 no-print"><button type="button" class="btn btn-outline-secondary" data-print><i class="bi bi-printer"></i> طباعة</button>
  <div class="dropdown"><button class="btn btn-outline-success dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-file-earmark-excel"></i> تصدير</button><ul class="dropdown-menu dropdown-menu-end">
    <?php foreach (['students' => 'كل النتائج', 'top' => 'الأوائل', 'failing' => 'الراسبون', 'sections' => 'مقارنة الشعب', 'subjects' => 'أداء المواد'] as $k => $l): ?><li><a class="dropdown-item" href="<?= url('school/academic-dashboard/export?' . $q(['kind' => $k])) ?>"><?= e($l) ?></a></li><?php endforeach; ?></ul></div></div></div>
<form class="card-s mb-3 row g-2 align-items-end no-print" method="get">
  <div class="col-sm-4"><label class="form-label" for="dy">السنة</label><select class="form-select" id="dy" name="year" data-autosubmit><?php foreach ($years as $y): ?><option value="<?= (int)$y['id'] ?>" <?= (int)$y['id'] === $yearId ? 'selected' : '' ?>><?= e($y['name']) ?></option><?php endforeach; ?></select></div>
  <div class="col-sm-4"><label class="form-label" for="dt">الفترة</label><select class="form-select" id="dt" name="term" data-autosubmit><?php foreach ($terms as $t): ?><option value="<?= (int)$t['id'] ?>" <?= (int)$t['id'] === (int)$termId ? 'selected' : '' ?>><?= e($t['name']) ?></option><?php endforeach; ?><option value="year" <?= $termId === null ? 'selected' : '' ?>>النتيجة السنوية</option></select></div>
  <div class="col-sm-4"><label class="form-label" for="dg">الصف</label><select class="form-select" id="dg" name="grade" data-autosubmit><option value="">كل الصفوف</option><?php foreach ($grades as $g): ?><option value="<?= (int)$g['id'] ?>" <?= (int)$g['id'] === (int)$gradeId ? 'selected' : '' ?>><?= e($g['name']) ?></option><?php endforeach; ?></select></div>
</form>
<?php if (!$d || !$d['kpi']['students']): ?><div class="card-s empty-state"><i class="bi bi-bar-chart"></i>لا توجد نتائج محسوبة لهذه الفترة بعد. أدخل الدرجات أولًا.</div>
<?php else: $k = $d['kpi']; ?>
<div class="row g-3 mb-3">
  <div class="col-6 col-lg-3"><div class="card-s stat"><div class="ico"><i class="bi bi-people"></i></div><div><div class="num"><?= (int)$k['students'] ?></div><div class="lbl">طالب بنتائج (<?= (int)$k['sections'] ?> شعبة)</div></div></div></div>
  <div class="col-6 col-lg-3"><div class="card-s stat"><div class="ico"><i class="bi bi-speedometer"></i></div><div><div class="num"><?= $fmt($k['average']) ?></div><div class="lbl">متوسط المعدلات</div></div></div></div>
  <div class="col-6 col-lg-3"><div class="card-s stat"><div class="ico"><i class="bi bi-check-circle"></i></div><div><div class="num"><?= $fmt($k['pass_rate']) ?>%</div><div class="lbl">نسبة النجاح (<?= (int)$k['pass'] ?>)</div></div></div></div>
  <div class="col-6 col-lg-3"><div class="card-s stat"><div class="ico"><i class="bi bi-x-circle"></i></div><div><div class="num"><?= (int)$k['fail'] ?></div><div class="lbl">راسب أو له مواد</div></div></div></div>
</div>
<div class="row g-3 mb-3">
  <div class="col-lg-4"><div class="card-s h-100 text-center"><h2 class="h6 text-start">النجاح والرسوب</h2><?= Chart::donut((float)$k['pass'], (float)$k['fail'], $fmt($k['pass_rate']) . '%') ?><div class="small mt-2"><span class="badge text-bg-success">ناجح <?= (int)$k['pass'] ?></span> <span class="badge text-bg-danger">راسب <?= (int)$k['fail'] ?></span></div></div></div>
  <div class="col-lg-8"><div class="card-s h-100"><h2 class="h6">توزيع المعدلات</h2><?= Chart::columns($d['distribution'], 'توزيع المعدلات') ?></div></div>
</div>
<div class="row g-3 mb-3">
  <div class="col-lg-6"><div class="card-s h-100"><h2 class="h6">متوسط الصفوف</h2><?= Chart::hbars(array_column($d['grades'], 'average', 'grade'), (float)$d['max'], '', null, 'متوسط الصفوف') ?></div></div>
  <div class="col-lg-6"><div class="card-s h-100"><h2 class="h6">أداء المواد <small class="text-muted">(الأضعف أولًا)</small></h2><?= Chart::hbars(array_column(array_slice($d['subjects'], 0, 12), 'average', 'subject'), 100, '%', 50.0, 'أداء المواد') ?></div></div>
</div>
<div class="row g-3 mb-3">
  <div class="col-lg-6"><div class="card-s h-100"><h2 class="h6"><i class="bi bi-trophy"></i> الأوائل</h2>
    <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>#</th><th>الطالب</th><th>الصف/الشعبة</th><th>المعدل</th></tr></thead><tbody>
    <?php foreach ($d['top'] as $s): ?><tr><td><span class="medal <?= $s['rank'] <= 3 ? 'm' . $s['rank'] : '' ?>"><?= (int)$s['rank'] ?></span></td><td><a href="<?= url('school/students/' . (int)$s['student_id'] . '/transcript') ?>"><?= e($s['name']) ?></a></td><td><?= e($s['grade'] . ' / ' . $s['section']) ?></td><td class="fw-bold"><?= $fmt($s['average']) ?> <small class="text-muted"><?= e($s['letter'] ?? '') ?></small></td></tr><?php endforeach; ?>
    </tbody></table></div></div></div>
  <div class="col-lg-6"><div class="card-s h-100"><h2 class="h6"><i class="bi bi-award"></i> أوائل كل صف</h2>
    <?php foreach ($d['top_by_grade'] as $g => $list): ?><div class="mb-2"><div class="small fw-bold text-muted"><?= e($g) ?></div><?php foreach ($list as $i => $s): ?><div class="d-flex justify-content-between border-bottom py-1"><span><span class="medal m<?= $i + 1 ?>"><?= $i + 1 ?></span> <?= e($s['name']) ?> <small class="text-muted"><?= e($s['section']) ?></small></span><strong><?= $fmt($s['average']) ?></strong></div><?php endforeach; ?></div><?php endforeach; ?></div></div>
</div>
<div class="card-s mb-3"><h2 class="h6">مقارنة الشعب</h2><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>الصف</th><th>الشعبة</th><th>الطلاب</th><th>المتوسط</th><th>الأعلى</th><th>الأدنى</th><th>نسبة النجاح</th></tr></thead><tbody>
  <?php foreach ($d['sections'] as $x): ?><tr><td><?= e($x['grade']) ?></td><td><?= e($x['section']) ?></td><td><?= (int)$x['students'] ?></td><td class="fw-bold"><?= $fmt($x['average']) ?></td><td><?= $fmt($x['highest']) ?></td><td><?= $fmt($x['lowest']) ?></td><td><div class="progress" style="height:18px;min-width:90px" role="progressbar" aria-valuenow="<?= (float)$x['pass_rate'] ?>" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar <?= $x['pass_rate'] < 60 ? 'bg-danger' : 'bg-success' ?>" style="width:<?= (float)$x['pass_rate'] ?>%"><?= $fmt($x['pass_rate']) ?>%</div></div></td></tr><?php endforeach; ?>
</tbody></table></div></div>
<?php if ($d['failing']): ?><div class="card-s mb-3"><h2 class="h6 text-danger"><i class="bi bi-exclamation-triangle"></i> يحتاجون متابعة (<?= count($d['failing']) ?>)</h2><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>الطالب</th><th>الصف/الشعبة</th><th>المعدل</th><th>النتيجة</th></tr></thead><tbody>
  <?php foreach (array_slice($d['failing'], 0, 30) as $s): ?><tr><td><a href="<?= url('school/students/' . (int)$s['student_id'] . '/transcript') ?>"><?= e($s['name']) ?></a></td><td><?= e($s['grade'] . ' / ' . $s['section']) ?></td><td><?= $fmt($s['average']) ?></td><td><span class="badge text-bg-danger"><?= e($s['result']) ?></span></td></tr><?php endforeach; ?>
</tbody></table></div></div><?php endif; ?>
<?php endif; ?>
