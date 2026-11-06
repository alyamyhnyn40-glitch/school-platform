<?php $a = $a ?? []; $v = fn($k) => e($a[$k] ?? ''); ?>
<form class="card-s" method="<?= !empty($a['id']) ? 'put' : 'post' ?>" action="<?= url('api/v1/school/admissions' . (!empty($a['id']) ? '/' . (int)$a['id'] : '')) ?>" data-ajax novalidate><?= csrf_field() ?>
<div class="row g-3">
  <div class="col-md-6"><label class="form-label" for="n">اسم المتقدّم *</label><input class="form-control" id="n" name="applicant_name" value="<?= $v('applicant_name') ?>" required></div>
  <div class="col-md-3"><label class="form-label" for="g">الجنس</label><select class="form-select" id="g" name="gender"><option value="">—</option><option value="male" <?= ($a['gender'] ?? '') === 'male' ? 'selected' : '' ?>>ذكر</option><option value="female" <?= ($a['gender'] ?? '') === 'female' ? 'selected' : '' ?>>أنثى</option></select></div>
  <div class="col-md-3"><label class="form-label" for="b">تاريخ الميلاد</label><input class="form-control" type="date" id="b" name="birth_date" value="<?= $v('birth_date') ?>"></div>
  <div class="col-md-4"><label class="form-label" for="gr">الصف المطلوب</label><select class="form-select" id="gr" name="grade_level_id"><option value="">—</option><?php foreach ($grades as $g): ?><option value="<?= (int)$g['id'] ?>" <?= (int)($a['grade_level_id'] ?? 0) === (int)$g['id'] ? 'selected' : '' ?>><?= e($g['name']) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-4"><label class="form-label" for="ni">رقم الهوية (مشفّر)</label><input class="form-control" id="ni" name="national_id" value="<?= $v('national_id') ?>"></div>
  <div class="col-md-4"><label class="form-label" for="ps">المدرسة السابقة</label><input class="form-control" id="ps" name="previous_school" value="<?= $v('previous_school') ?>"></div>
  <div class="col-md-4"><label class="form-label" for="gn">ولي الأمر</label><input class="form-control" id="gn" name="guardian_name" value="<?= $v('guardian_name') ?>"></div>
  <div class="col-md-4"><label class="form-label" for="gp">هاتف ولي الأمر</label><input class="form-control" id="gp" name="guardian_phone" dir="ltr" value="<?= $v('guardian_phone') ?>"></div>
  <div class="col-md-4"><label class="form-label" for="gl">الصلة</label><input class="form-control" id="gl" name="guardian_relation" placeholder="أب / أم / أخ…" value="<?= $v('guardian_relation') ?>"></div>
  <div class="col-md-6"><label class="form-label" for="ad">العنوان</label><input class="form-control" id="ad" name="address" value="<?= $v('address') ?>"></div>
  <div class="col-md-6"><label class="form-label" for="nt">ملاحظات</label><input class="form-control" id="nt" name="notes" value="<?= $v('notes') ?>"></div>
</div><div class="mt-3"><button class="btn btn-primary">حفظ</button> <a class="btn btn-outline-secondary" href="<?= url('school/admissions') ?>">رجوع</a></div></form>
