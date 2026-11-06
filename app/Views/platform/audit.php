<form id="filters" class="card-s mb-3 row g-2">
  <div class="col-md-3"><label class="form-label" for="school_id">المدرسة</label><select class="form-select" id="school_id" name="school_id"><option value="">الكل</option><?php foreach ($schools as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-3"><label class="form-label" for="action">نوع العملية</label><select class="form-select" id="action" name="action"><option value="">الكل</option><option value="school.">المدارس</option><option value="support.">الدعم الفني</option><option value="plan.">الخطط</option><option value="login">الدخول</option><option value="grade">الدرجات</option><option value="attendance">الحضور</option></select></div>
  <div class="col-md-3"><label class="form-label" for="from">من</label><input class="form-control" type="date" id="from" name="from"></div>
  <div class="col-md-3"><label class="form-label" for="to">إلى</label><input class="form-control" type="date" id="to" name="to"></div>
</form>
<div class="card-s" id="table"><?= e(__t('common.loading')) ?></div>
