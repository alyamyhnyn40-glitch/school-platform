<div class="d-flex flex-wrap gap-2 align-items-center mb-3">
  <form method="get" class="d-flex gap-2"><select class="form-select" name="year" data-autosubmit aria-label="السنة"><?php foreach ($years as $y): ?><option value="<?= (int)$y['id'] ?>" <?= (int)$yearId === (int)$y['id'] ? 'selected' : '' ?>><?= e($y['name']) ?> (<?= ['draft' => 'مسودة', 'active' => 'فعّالة', 'closed' => 'مغلقة'][$y['status']] ?>)</option><?php endforeach; ?></select><noscript><button class="btn btn-primary">عرض</button></noscript></form>
</div>
<div class="row g-3">
  <div class="col-lg-8"><div class="card-s">
    <?php if (!$sections): ?><div class="empty-state"><i class="bi bi-diagram-3"></i>لا توجد شعب لهذه السنة. أضف شعبة أو انسخ الهيكل من سنة سابقة.</div><?php else: ?>
    <div class="table-responsive"><table class="table table-hover align-middle table-cards"><thead><tr><th>الصف</th><th>الشعبة</th><th>النوع</th><th>الطلاب</th><th></th></tr></thead><tbody>
      <?php foreach ($sections as $s): ?><tr><td data-label="الصف"><?= e($s['grade_name']) ?></td><td data-label="الشعبة"><a class="fw-bold" href="<?= url('school/sections/' . (int)$s['id']) ?>"><?= e($s['name']) ?></a></td>
        <td data-label="النوع"><?= ['mixed' => 'مختلطة', 'boys' => 'بنين', 'girls' => 'بنات'][$s['gender']] ?></td><td data-label="الطلاب"><?= (int)$s['students'] ?><?= $s['capacity'] ? ' / ' . (int)$s['capacity'] : '' ?></td>
        <td><a class="btn btn-sm btn-outline-primary" href="<?= url('school/sections/' . (int)$s['id']) ?>">إدارة</a></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div></div>
  <?php if (can('academic.manage') && $yearId && $grades): ?><div class="col-lg-4"><form class="card-s" method="post" action="<?= url('api/v1/school/sections') ?>" data-ajax data-reload novalidate><?= csrf_field() ?>
    <input type="hidden" name="academic_year_id" value="<?= (int)$yearId ?>"><h3 class="h6">شعبة جديدة</h3>
    <div class="mb-2"><label class="form-label" for="g">الصف</label><select class="form-select" id="g" name="grade_level_id"><?php foreach ($grades as $g): ?><option value="<?= (int)$g['id'] ?>"><?= e($g['name']) ?></option><?php endforeach; ?></select></div>
    <div class="row g-2"><div class="col-6"><label class="form-label" for="sn">الاسم</label><input class="form-control" id="sn" name="name" required></div><div class="col-6"><label class="form-label" for="sc">السعة</label><input class="form-control" id="sc" name="capacity" type="number" min="1"></div></div>
    <div class="row g-2 mt-1"><div class="col-6"><label class="form-label" for="sg">النوع</label><select class="form-select" id="sg" name="gender"><option value="mixed">مختلطة</option><option value="boys">بنين</option><option value="girls">بنات</option></select></div>
      <div class="col-6"><label class="form-label" for="sh">مربّي الشعبة</label><select class="form-select" id="sh" name="homeroom_staff_id"><option value="">—</option><?php foreach ($teachers as $t): ?><option value="<?= (int)$t['id'] ?>"><?= e($t['full_name']) ?></option><?php endforeach; ?></select></div></div>
    <button class="btn btn-primary mt-3" type="submit">إضافة</button></form></div><?php endif; ?>
</div>
