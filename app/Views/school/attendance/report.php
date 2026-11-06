<form class="card-s mb-3 row g-2 align-items-end" method="get">
  <div class="col-6 col-md-2"><label class="form-label" for="from">من</label><input class="form-control" type="date" id="from" name="from" value="<?= e($from) ?>"></div>
  <div class="col-6 col-md-2"><label class="form-label" for="to">إلى</label><input class="form-control" type="date" id="to" name="to" value="<?= e($to) ?>"></div>
  <div class="col-12 col-md-3"><label class="form-label" for="section_id">الشعبة</label><select class="form-select" id="section_id" name="section_id"><option value="">كل الشعب</option><?php foreach ($sections as $s): ?><option value="<?= (int)$s['id'] ?>" <?= (int)$sectionId === (int)$s['id'] ? 'selected' : '' ?>><?= e($s['grade_name'] . ' / ' . $s['name']) ?></option><?php endforeach; ?></select></div>
  <div class="col-6 col-md-2"><label class="form-label" for="min">أيام الغياب ≥</label><input class="form-control" type="number" min="1" id="min" name="min" value="<?= (int)$min ?>"></div>
  <div class="col-6 col-md-3 d-flex gap-2"><button class="btn btn-primary flex-fill" type="submit">عرض</button>
    <?php if (can('reports.export')): ?><a class="btn btn-outline-success" href="<?= url('school/attendance/report.xlsx?' . http_build_query(['from' => $from, 'to' => $to, 'section_id' => $sectionId])) ?>"><i class="bi bi-file-earmark-excel"></i> Excel</a><?php endif; ?></div>
</form>
<div class="row g-3">
  <div class="col-lg-6"><div class="card-s"><h2 class="h6">اتجاه الحضور اليومي</h2><div style="height:240px"><canvas id="trend" data-trend="<?= e(json_encode($trend, JSON_UNESCAPED_UNICODE)) ?>"></canvas></div></div></div>
  <div class="col-lg-6"><div class="card-s"><h2 class="h6">الطلاب كثيرو الغياب (<?= count($absentees) ?>)</h2>
    <?php if (!$absentees): ?><div class="empty-state">لا يوجد طلاب بغياب متكرر في هذه الفترة.</div><?php else: ?><ul class="list-group list-group-flush"><?php foreach (array_slice($absentees, 0, 12) as $a): ?><li class="list-group-item bg-transparent d-flex justify-content-between"><a href="<?= url('school/students/' . (int)$a['student_id']) ?>"><?= e($a['full_name']) ?></a><span class="text-danger"><?= (int)$a['absent'] ?> غياب (<?= e($a['absence_pct']) ?>%)</span></li><?php endforeach; ?></ul><?php endif; ?></div></div>
</div>
<div class="card-s mt-3"><h2 class="h6">ملخص الحضور لكل طالب</h2>
  <?php if (!$rows): ?><div class="empty-state"><i class="bi bi-inbox"></i>لا توجد سجلات حضور في هذه الفترة.</div><?php else: ?>
  <div class="table-responsive"><table class="table table-sm align-middle table-cards"><thead><tr><th>الطالب</th><th>الشعبة</th><th>حاضر</th><th>غائب</th><th>متأخر</th><th>بعذر</th><th>الحضور %</th></tr></thead><tbody>
  <?php foreach ($rows as $x): ?><tr><td data-label="الطالب"><?= e($x['full_name']) ?></td><td data-label="الشعبة"><?= e($x['grade_name'] . ' / ' . $x['section_name']) ?></td><td data-label="حاضر"><?= $x['present'] ?></td><td data-label="غائب"><?= $x['absent'] ?></td><td data-label="متأخر"><?= $x['late'] ?></td><td data-label="بعذر"><?= $x['excused'] ?></td>
    <td data-label="الحضور %"><span class="badge text-bg-<?= $x['attendance_pct'] >= 90 ? 'success' : ($x['attendance_pct'] >= 75 ? 'warning' : 'danger') ?>"><?= e($x['attendance_pct']) ?></span></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div>
