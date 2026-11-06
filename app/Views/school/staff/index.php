<div class="d-flex flex-wrap gap-2 align-items-center mb-3">
  <input id="q" class="form-control" style="max-width:300px" type="search" placeholder="بحث بالاسم أو الهاتف أو الوظيفة" aria-label="بحث">
  <select id="kind" class="form-select" style="max-width:170px" aria-label="النوع"><option value="">الكل</option><option value="teachers">المعلمون فقط</option></select>
  <?php if (can('staff.manage')): ?><a class="btn btn-primary ms-auto" href="<?= url('school/staff/new') ?>"><i class="bi bi-plus-lg"></i> موظف جديد</a><?php endif; ?>
</div><div class="card-s" id="table"><?= e(__t('common.loading')) ?></div>
