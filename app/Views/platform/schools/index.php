<div class="d-flex flex-wrap gap-2 align-items-center mb-3">
  <input id="q" class="form-control" style="max-width:320px" type="search" placeholder="بحث بالاسم أو الرمز أو المدينة" aria-label="بحث">
  <select id="status" class="form-select" style="max-width:180px" aria-label="الحالة">
    <option value="">كل الحالات</option><option value="active">فعّالة</option><option value="suspended">موقوفة</option><option value="archived">مؤرشفة</option>
  </select>
  <a class="btn btn-primary ms-auto" href="<?= url('platform/schools/new') ?>"><i class="bi bi-plus-lg"></i> مدرسة جديدة</a>
</div>
<div class="card-s" id="table"><?= e(__t('common.loading')) ?></div>
