<form class="card-s" method="post" action="<?= url('api/v1/school/staff') ?>" data-ajax novalidate style="max-width:900px"><?= csrf_field() ?>
  <h2 class="h5 mb-3">بيانات الموظف</h2>
  <div class="row g-3">
    <div class="col-md-6"><label class="form-label" for="full_name">الاسم *</label><input class="form-control" id="full_name" name="full_name" required></div>
    <div class="col-md-3"><label class="form-label" for="gender">الجنس</label><select class="form-select" id="gender" name="gender"><option value="">—</option><option value="male">ذكر</option><option value="female">أنثى</option></select></div>
    <div class="col-md-3"><label class="form-label" for="birth_date">الميلاد</label><input class="form-control" id="birth_date" name="birth_date" type="date"></div>
    <div class="col-md-4"><label class="form-label" for="job_title">الوظيفة</label><input class="form-control" id="job_title" name="job_title" list="dlJobs" autocomplete="off" placeholder="اختر أو اكتب"><datalist id="dlJobs"><?php foreach (\App\Core\Lookup::JOBS as $j): ?><option value="<?= e($j) ?>"><?php endforeach; ?></datalist></div>
    <div class="col-md-4"><label class="form-label" for="specialization">التخصص</label><input class="form-control" id="specialization" name="specialization" list="dlSpec" autocomplete="off" placeholder="اختر أو اكتب"><datalist id="dlSpec"><?php foreach (\App\Core\Lookup::SPECIALIZATIONS as $j): ?><option value="<?= e($j) ?>"><?php endforeach; ?></datalist></div>
    <div class="col-md-4"><label class="form-label" for="employee_no">الرقم الوظيفي</label><input class="form-control" id="employee_no" name="employee_no" dir="ltr"></div>
    <div class="col-md-4"><label class="form-label" for="phone">الهاتف</label><input class="form-control" id="phone" name="phone" dir="ltr"></div>
    <div class="col-md-4"><label class="form-label" for="email">البريد</label><input class="form-control" id="email" name="email" type="email" dir="ltr"></div>
    <div class="col-md-4"><label class="form-label" for="hire_date">تاريخ التعيين</label><input class="form-control" id="hire_date" name="hire_date" type="date"></div>
    <div class="col-md-6"><label class="form-label" for="national_id">الرقم الوطني (مشفّر)</label><input class="form-control" id="national_id" name="national_id" dir="ltr" autocomplete="off"></div>
    <div class="col-md-6"><label class="form-label" for="address">العنوان</label><input class="form-control" id="address" name="address"></div>
    <div class="col-12"><div class="form-check"><input type="hidden" name="is_teacher" value="0"><input class="form-check-input" type="checkbox" name="is_teacher" value="1" id="is_teacher"><label class="form-check-label" for="is_teacher">معلّم (يمكن إسناد مواد له)</label></div></div>
  </div>
  <?php if (can('rbac.manage') || can('staff.manage')): ?><hr><div class="form-check mb-2"><input type="hidden" name="create_account" value="0"><input class="form-check-input" type="checkbox" name="create_account" value="1" id="create_account"><label class="form-check-label" for="create_account">إنشاء حساب دخول (يتطلب بريدًا أو هاتفًا)</label></div>
  <div class="col-md-4 mb-2"><label class="form-label" for="role_id">الدور</label><select class="form-select" id="role_id" name="role_id"><option value="">تلقائي (معلم/وكيل)</option><?php foreach ($roles as $r): ?><option value="<?= (int)$r['id'] ?>"><?= e($r['name']) ?></option><?php endforeach; ?></select></div><?php endif; ?>
  <div class="alert alert-success d-none mt-3" data-result></div>
  <button class="btn btn-primary" type="submit">حفظ</button> <a class="btn btn-outline-secondary" href="<?= url('school/staff') ?>"><?= e(__t('common.cancel')) ?></a>
</form>
