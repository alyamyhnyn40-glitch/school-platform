<div class="d-flex flex-wrap gap-2 align-items-center mb-3" id="filters">
  <input id="q" class="form-control" style="max-width:280px" type="search" placeholder="بحث بالاسم أو رقم القيد أو الهاتف" aria-label="بحث">
  <select id="grade" class="form-select" style="max-width:170px" aria-label="الصف"><option value="">كل الصفوف</option><?php foreach ($grades as $g): ?><option value="<?= (int)$g['id'] ?>"><?= e($g['name']) ?></option><?php endforeach; ?></select>
  <select id="section" class="form-select" style="max-width:170px" aria-label="الشعبة"><option value="">كل الشعب</option><?php foreach ($sections as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['grade_name'] . ' / ' . $s['name']) ?></option><?php endforeach; ?></select>
  <select id="status" class="form-select" style="max-width:150px" aria-label="الحالة"><option value="">كل الحالات</option><option value="active" selected>فعّال</option><option value="withdrawn">منسحب</option><option value="transferred">منتقل</option><option value="graduated">متخرج</option></select>
  <?php if (can('students.manage')): ?><a class="btn btn-primary ms-auto" href="<?= url('school/students/new') ?>"><i class="bi bi-plus-lg"></i> طالب جديد</a><?php endif; ?>
</div>
<div class="card-s" id="table"><?= e(__t('common.loading')) ?></div>
