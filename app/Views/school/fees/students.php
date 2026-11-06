<div class="d-flex flex-wrap gap-2 align-items-center mb-3">
  <input id="q" class="form-control" style="max-width:260px" type="search" placeholder="اسم الطالب أو رقم القيد" aria-label="بحث">
  <select id="section" class="form-select" style="max-width:190px" aria-label="الشعبة"><option value="">كل الشعب</option><?php foreach ($sections as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['grade_name'] . ' / ' . $s['name']) ?></option><?php endforeach; ?></select>
  <select id="structure" class="form-select" style="max-width:210px" aria-label="الهيكل"><option value="">كل الهياكل</option><?php foreach ($structures as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?></option><?php endforeach; ?></select>
  <select id="status" class="form-select" style="max-width:150px" aria-label="الحالة"><option value="">كل الحالات</option><option value="unpaid">لم يدفع</option><option value="partial">دفع جزئيًا</option><option value="paid">مسدّد</option><option value="overdue">متأخر</option></select>
</div><div class="card-s" id="table"><?= e(__t('common.loading')) ?></div>
