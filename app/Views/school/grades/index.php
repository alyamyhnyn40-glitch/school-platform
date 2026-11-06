<form class="card-s mb-3 row g-2 align-items-end" method="get">
  <div class="col-12 col-md-3"><label class="form-label" for="term">الفصل</label><select class="form-select" id="term" name="term"><?php foreach ($terms as $t): ?><option value="<?= (int)$t['id'] ?>" <?= $termId === (int)$t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?><?= $t['grades_locked'] ? ' 🔒' : '' ?></option><?php endforeach; ?></select></div>
  <div class="col-12 col-md-6"><label class="form-label" for="ss">المادة والشعبة</label><select class="form-select" id="ss" name="ss"><option value="">— اختر —</option><?php foreach ($subjects as $s): ?><option value="<?= (int)$s['id'] ?>" <?= $ssId === (int)$s['id'] ? 'selected' : '' ?>><?= e($s['subject'] . ' — ' . $s['grade'] . ' / ' . $s['section']) ?></option><?php endforeach; ?></select></div>
  <div class="col-12 col-md-3"><button class="btn btn-primary w-100" type="submit">عرض</button></div>
</form>
<?php if (!$terms): ?><div class="alert alert-warning">لا توجد فصول في السنة الفعّالة. أضف فصلًا من «السنوات والفصول».</div><?php endif; ?>
<?php if (!$subjects): ?><div class="alert alert-info">لا توجد مواد مُسندة إليك في السنة الفعّالة.</div><?php endif; ?>
<?php if ($ssId && $termId): ?>
<div class="row g-3"><div class="col-lg-8"><div class="card-s"><h2 class="h6">التقييمات</h2>
  <?php if (!$assessments): ?><div class="empty-state"><i class="bi bi-clipboard"></i>لا توجد تقييمات لهذه المادة في الفصل. أنشئ أول تقييم.</div><?php else: ?>
  <div class="table-responsive"><table class="table align-middle table-cards"><thead><tr><th>التقييم</th><th>النوع</th><th>العظمى</th><th>المُدخَل</th><th>الحالة</th><th></th></tr></thead><tbody>
  <?php foreach ($assessments as $a): ?><tr><td data-label="التقييم"><a class="fw-bold" href="<?= url('school/grades/' . (int)$a['id']) ?>"><?= e($a['title']) ?></a></td><td data-label="النوع"><?= e($a['type_name']) ?></td><td data-label="العظمى"><?= (float)$a['max_score'] ?></td><td data-label="المُدخَل"><?= (int)$a['entered'] ?></td>
    <td data-label="الحالة"><span class="badge text-bg-<?= ['open' => 'secondary', 'approved' => 'success', 'locked' => 'dark'][$a['status']] ?>"><?= ['open' => 'مفتوح', 'approved' => 'معتمد', 'locked' => 'مقفل'][$a['status']] ?></span></td>
    <td><a class="btn btn-sm btn-outline-primary" href="<?= url('school/grades/' . (int)$a['id']) ?>">فتح</a></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div></div>
  <div class="col-lg-4"><form class="card-s" method="post" action="<?= url('api/v1/school/assessments') ?>" data-ajax novalidate><?= csrf_field() ?>
    <input type="hidden" name="term_id" value="<?= (int)$termId ?>"><input type="hidden" name="section_subject_id" value="<?= (int)$ssId ?>"><h3 class="h6">تقييم جديد</h3>
    <div class="mb-2"><label class="form-label" for="title">العنوان</label><input class="form-control" id="title" name="title" required placeholder="مثال: اختبار شهري 1"></div>
    <div class="mb-2"><label class="form-label" for="type_id">النوع (الوزن)</label><select class="form-select" id="type_id" name="type_id"><?php foreach ($scheme['types'] as $t): ?><option value="<?= (int)$t['id'] ?>"><?= e($t['name']) ?> (<?= (float)$t['weight'] ?>%)</option><?php endforeach; ?></select></div>
    <div class="row g-2"><div class="col-6"><label class="form-label" for="max_score">الدرجة العظمى</label><input class="form-control" id="max_score" name="max_score" type="number" step="0.01" min="0.01" required></div><div class="col-6"><label class="form-label" for="held_on">التاريخ</label><input class="form-control" id="held_on" name="held_on" type="date"></div></div>
    <button class="btn btn-primary mt-3" type="submit">إنشاء وفتح الجدول</button></form></div></div>
<?php endif; ?>
