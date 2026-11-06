<form id="filters" class="card-s mb-3 row g-2">
  <div class="col-md-4"><label class="form-label" for="action">نوع العملية</label><select class="form-select" id="action" name="action"><option value="">الكل</option><option value="grade">الدرجات</option><option value="assessment">التقييمات</option><option value="attendance">الحضور</option><option value="student">الطلاب</option><option value="term">قفل الفصول</option><option value="reportcard">الشهادات</option><option value="year">السنوات والترفيع</option><option value="login">الدخول</option></select></div>
  <div class="col-md-4"><label class="form-label" for="from">من</label><input class="form-control" type="date" id="from" name="from"></div>
  <div class="col-md-4"><label class="form-label" for="to">إلى</label><input class="form-control" type="date" id="to" name="to"></div>
</form><div class="card-s" id="table"><?= e(__t('common.loading')) ?></div>
