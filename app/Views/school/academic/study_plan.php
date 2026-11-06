<?php
$year = null; foreach ($years as $y) { if ((int)$y['id'] === $yearId) { $year = $y; } }
$open = $year && $year['status'] !== 'closed'; $can = can('academic.manage') && $open;
$gradeName = ''; foreach ($grades as $g) { if ((int)$g['id'] === $gradeId) { $gradeName = $g['name']; } }
$termOpts = function (?int $cur) use ($terms) { $h = '<option value="">كل الفصول</option>'; foreach ($terms as $t) { $h .= '<option value="' . (int)$t['id'] . '"' . ((int)$t['id'] === (int)$cur ? ' selected' : '') . '>' . e($t['name']) . ' فقط</option>'; } return $h; };
$subOpts = function (?int $cur) use ($subjects) { $h = '<option value="">— اختر المادة —</option>'; foreach ($subjects as $s) { $h .= '<option value="' . (int)$s['id'] . '"' . ((int)$s['id'] === (int)$cur ? ' selected' : '') . '>' . e($s['name']) . '</option>'; } return $h; };
$row = function (int $i, array $p) use ($termOpts, $subOpts, $can) { $d = $can ? '' : ' disabled';
  return '<tr class="plan-row"><td data-label="المادة"><select class="form-select" name="items[' . $i . '][subject_id]" aria-label="المادة"' . $d . '>' . $subOpts($p['subject_id'] ?? null) . '</select></td>'
    . '<td data-label="الفصل"><select class="form-select" name="items[' . $i . '][term_id]" aria-label="الفصل"' . $d . '>' . $termOpts(isset($p['term_id']) ? (int)$p['term_id'] : null) . '</select></td>'
    . '<td data-label="حصص/أسبوع"><input class="form-control" type="number" min="1" max="40" name="items[' . $i . '][weekly_periods]" value="' . (int)($p['weekly_periods'] ?? 4) . '" aria-label="الحصص"' . $d . '></td>'
    . '<td data-label="الساعات المعتمدة"><input class="form-control" type="number" min="0" max="20" step="0.5" name="items[' . $i . '][credit_hours]" value="' . e((string)(float)($p['credit_hours'] ?? 1)) . '" aria-label="الساعات"' . $d . '></td>'
    . '<td data-label="الدرجة العظمى"><input class="form-control" type="number" min="1" max="1000" name="items[' . $i . '][max_score]" value="' . e($p['max_score'] !== null && isset($p['max_score']) ? (string)(float)$p['max_score'] : '') . '" placeholder="حسب النظام" aria-label="الدرجة العظمى"' . $d . '></td>'
    . '<td data-label="اختيارية"><div class="form-check form-switch m-0"><input type="hidden" name="items[' . $i . '][is_elective]" value="0"><input class="form-check-input" type="checkbox" role="switch" name="items[' . $i . '][is_elective]" value="1" aria-label="اختيارية"' . (!empty($p['is_elective']) ? ' checked' : '') . $d . '></div></td>'
    . '<td>' . ($can ? '<button type="button" class="btn btn-sm btn-outline-danger plan-del" aria-label="حذف السطر"><i class="bi bi-x-lg"></i></button>' : '') . '</td></tr>'; };
?>
<div class="page-head"><div><h1 class="page-title">الخطة الدراسية</h1><p class="page-sub">حدّد مواد كل صف في كل سنة دراسية، وفي أي فصل تُدرَّس، وساعاتها، وهل هي اختيارية — ثم طبّقها على الشعب بنقرة.</p></div></div>
<form class="card-s mb-3 row g-2 align-items-end" method="get">
  <div class="col-sm-5 col-lg-4"><label class="form-label" for="fy">السنة الدراسية</label><select class="form-select" id="fy" name="year" data-autosubmit><?php foreach ($years as $y): ?><option value="<?= (int)$y['id'] ?>" <?= (int)$y['id'] === $yearId ? 'selected' : '' ?>><?= e($y['name']) ?> — <?= e(['draft' => 'مسودة', 'active' => 'فعّالة', 'closed' => 'مغلقة'][$y['status']] ?? $y['status']) ?></option><?php endforeach; ?></select></div>
  <div class="col-sm-5 col-lg-4"><label class="form-label" for="fg">الصف</label><select class="form-select" id="fg" name="grade" data-autosubmit><option value="">— كل الصفوف (ملخص) —</option><?php foreach ($grades as $g): ?><option value="<?= (int)$g['id'] ?>" <?= (int)$g['id'] === $gradeId ? 'selected' : '' ?>><?= e(($g['stage_name'] ?? '') !== '' ? $g['stage_name'] . ' — ' : '') . e($g['name']) ?></option><?php endforeach; ?></select></div>
  <div class="col-sm-2 col-lg-2"><button class="btn btn-outline-primary w-100">عرض</button></div>
</form>
<?php if (!$years): ?><div class="card-s empty-state"><i class="bi bi-calendar-plus"></i>أنشئ سنة دراسية أولًا من «السنوات والفصول».</div>
<?php elseif (!$gradeId): ?>
<div class="row g-3">
  <div class="col-lg-8"><div class="card-s"><h2 class="h6">صفوف السنة <?= e($year['name'] ?? '') ?></h2>
    <div class="table-responsive"><table class="table align-middle"><thead><tr><th>الصف</th><th>المواد</th><th>اختيارية</th><th>الساعات</th><th>الشعب</th><th></th></tr></thead><tbody>
    <?php foreach ($summary as $g): ?><tr><td><?= e($g['name']) ?><div class="small text-muted"><?= e($g['stage_name'] ?? '') ?></div></td><td><?= (int)$g['subjects'] ?: '<span class="badge text-bg-warning">بلا خطة</span>' ?></td><td><?= (int)$g['electives'] ?></td><td><?= e((string)(float)$g['hours']) ?></td><td><?= (int)$g['sections'] ?></td>
      <td><a class="btn btn-sm btn-primary" href="<?= url('school/study-plan?year=' . $yearId . '&grade=' . (int)$g['id']) ?>"><i class="bi bi-pencil"></i> <?= $can ? 'تحرير' : 'عرض' ?></a></td></tr><?php endforeach; ?>
    </tbody></table></div></div></div>
  <div class="col-lg-4">
    <?php if ($can): ?>
    <form class="card-s mb-3" method="post" action="<?= url('api/v1/school/study-plan/copy') ?>" data-ajax data-reload novalidate><?= csrf_field() ?><input type="hidden" name="year_id" value="<?= $yearId ?>">
      <h2 class="h6"><i class="bi bi-files"></i> نسخ الخطة من سنة سابقة</h2><select class="form-select mb-2" name="from_year" aria-label="السنة المصدر"><?php foreach ($years as $y): if ((int)$y['id'] === $yearId) { continue; } ?><option value="<?= (int)$y['id'] ?>"><?= e($y['name']) ?></option><?php endforeach; ?></select>
      <div class="form-text mb-2">تُنسخ مواد كل الصفوف مع فصولها (حسب رقم الفصل) وساعاتها. لا تُستبدل الموجودة.</div><button class="btn btn-outline-primary w-100">نسخ</button></form>
    <form class="card-s mb-3" method="post" action="<?= url('api/v1/school/study-plan/apply') ?>" data-ajax novalidate data-confirm="تطبيق الخطة على كل شعب السنة؟ تُضاف المواد الناقصة وتُحدَّث بياناتها، ولا تُحذف مادة لها درجات."><?= csrf_field() ?><input type="hidden" name="year_id" value="<?= $yearId ?>">
      <h2 class="h6"><i class="bi bi-diagram-3"></i> تطبيق على كل الشعب</h2><p class="small text-muted">يجعل مواد كل الشعب مطابقة لخطة صفها.</p><button class="btn btn-primary w-100">تطبيق الآن</button></form>
    <?php endif; ?>
    <?php if (can('grades.config')): ?><form class="card-s" method="post" action="<?= url('api/v1/school/study-plan/settings') ?>" data-ajax data-reload novalidate><?= csrf_field() ?><h2 class="h6"><i class="bi bi-calculator"></i> طريقة حساب المعدل</h2>
      <div class="form-check form-switch mb-2"><input type="hidden" name="credit_weighted" value="0"><input class="form-check-input" type="checkbox" role="switch" name="credit_weighted" value="1" id="cw" <?= $weighted ? 'checked' : '' ?>><label class="form-check-label" for="cw">معدل موزون بالساعات المعتمدة</label></div>
      <div class="form-text mb-2">بدونه: المعدل = متوسط درجات المواد. معه: المادة ذات الساعات الأكثر تؤثر أكثر.</div><button class="btn btn-outline-primary w-100">حفظ</button></form><?php endif; ?>
  </div>
</div>
<?php else: ?>
<form method="post" action="<?= url('api/v1/school/study-plan') ?>" data-ajax data-reload novalidate id="planForm"><?= csrf_field() ?>
  <input type="hidden" name="year_id" value="<?= $yearId ?>"><input type="hidden" name="grade_id" value="<?= $gradeId ?>">
  <div class="card-s mb-3">
    <div class="d-flex flex-wrap align-items-center gap-2 mb-2"><h2 class="h6 mb-0">مواد <?= e($gradeName) ?> — <?= e($year['name'] ?? '') ?></h2><?php if (!$open): ?><span class="badge text-bg-secondary">سنة مغلقة (للقراءة فقط)</span><?php endif; ?>
      <a class="btn btn-sm btn-outline-secondary ms-auto" href="<?= url('school/study-plan?year=' . $yearId) ?>"><i class="bi bi-arrow-right"></i> كل الصفوف</a></div>
    <?php if (!$terms): ?><div class="alert alert-warning small">لا توجد فصول في هذه السنة بعد؛ كل المواد ستكون «كل الفصول». أضف الفصول من «السنوات والفصول».</div><?php endif; ?>
    <div class="table-responsive"><table class="table align-middle plan-table"><thead><tr><th style="min-width:180px">المادة</th><th style="min-width:150px">الفصل</th><th>حصص/أسبوع</th><th>ساعات معتمدة</th><th>الدرجة العظمى</th><th>اختيارية</th><th></th></tr></thead>
      <tbody id="planRows"><?php foreach ($plan as $i => $p) { echo $row($i, $p); } if (!$plan && $can) { echo $row(0, []); } ?></tbody></table></div>
    <?php if ($can): ?><template id="planRowTpl"><?= $row(999999, []) ?></template>
    <button type="button" class="btn btn-outline-primary" id="planAdd"><i class="bi bi-plus-lg"></i> إضافة مادة</button>
    <div class="small text-muted mt-2">المادة «اختيارية» تُحتسب فقط للطلاب الذين تختارهم لها من صفحة الشعبة. اختيار فصل محدد يجعل المادة تظهر في نتائج ذلك الفصل فقط.</div><?php endif; ?>
  </div>
  <?php if ($can): ?><div class="d-flex flex-wrap gap-2 align-items-center"><div class="form-check"><input type="hidden" name="apply" value="0"><input class="form-check-input" type="checkbox" name="apply" value="1" id="ap" checked><label class="form-check-label" for="ap">تطبيق على شعب هذا الصف بعد الحفظ</label></div>
    <button class="btn btn-primary ms-auto"><i class="bi bi-check2"></i> حفظ الخطة</button></div><?php endif; ?>
</form>
<?php endif; ?>
