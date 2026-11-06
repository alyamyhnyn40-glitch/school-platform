<?php if (!$year): ?><div class="alert alert-warning">لا توجد سنة دراسية فعّالة؛ ستُسجَّل بيانات الطالب دون شعبة. أنشئ سنة وفعّلها لاحقًا ثم أسند الطالب لشعبة.</div><?php endif; ?>
<div class="page-head"><div><h1 class="page-title">تسجيل طالب جديد</h1><p class="page-sub">أربع خطوات قصيرة؛ الحقول المعلَّمة * إلزامية فقط.</p></div><a class="btn btn-outline-secondary" href="<?= url('school/import') ?>"><i class="bi bi-file-earmark-excel"></i> استيراد عدة طلاب من Excel</a></div>
<form class="card-s" method="post" action="<?= url('api/v1/school/students') ?>" data-ajax data-wizard novalidate style="max-width:900px"><?= csrf_field() ?>
  <section class="wz-step" data-title="البيانات الأساسية">
    <div class="row g-3">
      <div class="col-md-8"><label class="form-label" for="full_name">الاسم الرباعي *</label><input class="form-control" id="full_name" name="full_name" required minlength="3" data-label="الاسم" autofocus></div>
      <div class="col-md-4"><label class="form-label" for="gender">الجنس *</label><select class="form-select" id="gender" name="gender" required data-label="الجنس"><option value="male">ذكر</option><option value="female">أنثى</option></select></div>
      <div class="col-md-4"><label class="form-label" for="birth_date">تاريخ الميلاد</label><input class="form-control" id="birth_date" name="birth_date" type="date" max="<?= date('Y-m-d') ?>" data-label="تاريخ الميلاد"></div>
      <div class="col-md-4"><label class="form-label" for="birth_place">مكان الميلاد</label><input class="form-control" id="birth_place" name="birth_place" list="dlGov" autocomplete="off" placeholder="اختر أو اكتب" data-label="مكان الميلاد"><datalist id="dlGov"><?php foreach (\App\Core\Lookup::governorates() as $j): ?><option value="<?= e($j) ?>"><?php endforeach; ?></datalist></div>
      <div class="col-md-4"><label class="form-label" for="nationality">الجنسية</label><select class="form-select" id="nationality" name="nationality" data-label="الجنسية"><?= options(\App\Core\Lookup::NATIONALITIES, 'يمني', false) ?></select></div>
    </div>
  </section>
  <section class="wz-step" data-title="التسجيل الدراسي">
    <div class="row g-3">
      <div class="col-md-6"><label class="form-label" for="section_id">الصف / الشعبة <?= $year ? '(' . e($year['name']) . ')' : '' ?></label><select class="form-select" id="section_id" name="section_id" data-label="الشعبة"><option value="">— بدون (لاحقًا) —</option><?php $g = null; foreach ($sections as $s): if ($g !== $s['grade_name']) { if ($g !== null) { echo '</optgroup>'; } $g = $s['grade_name']; echo '<optgroup label="' . e($g) . '">'; } ?><option value="<?= (int)$s['id'] ?>"><?= e($s['grade_name'] . ' / ' . $s['name']) ?></option><?php endforeach; if ($g !== null) { echo '</optgroup>'; } ?></select></div>
      <div class="col-md-6"><label class="form-label" for="student_no">رقم القيد</label><input class="form-control" id="student_no" name="student_no" dir="ltr" placeholder="يُولَّد تلقائيًا إن تُرك فارغًا" data-label="رقم القيد"></div>
      <div class="col-12"><label class="form-label" for="previous_school">المدرسة السابقة</label><input class="form-control" id="previous_school" name="previous_school" data-label="المدرسة السابقة"></div>
    </div>
  </section>
  <section class="wz-step" data-title="الهوية والتواصل">
    <div class="row g-3">
      <div class="col-md-6"><label class="form-label" for="national_id">الرقم الوطني/الهوية <i class="bi bi-lock text-muted" title="يُحفظ مشفّرًا"></i></label><input class="form-control" id="national_id" name="national_id" dir="ltr" autocomplete="off" inputmode="numeric"></div>
      <div class="col-md-6"><label class="form-label" for="phone">هاتف الطالب</label><input class="form-control" id="phone" name="phone" dir="ltr" inputmode="tel" placeholder="7xxxxxxxx" data-label="الهاتف"></div>
      <div class="col-12"><label class="form-label" for="address">العنوان</label><input class="form-control" id="address" name="address" data-label="العنوان"></div>
    </div>
  </section>
  <section class="wz-step" data-title="الصحة والمراجعة">
    <div class="form-check form-switch mb-2"><input type="hidden" name="has_health" value="0"><input class="form-check-input" type="checkbox" role="switch" name="has_health" value="1" id="hh"><label class="form-check-label" for="hh">لدى الطالب احتياجات خاصة أو ملاحظات صحية</label></div>
    <div class="row g-3 mb-3" data-show-if="has_health=1">
      <div class="col-md-6"><label class="form-label" for="special_needs">احتياجات خاصة</label><input class="form-control" id="special_needs" name="special_needs" data-label="احتياجات خاصة"></div>
      <div class="col-12"><label class="form-label" for="health_notes">ملاحظات صحية <i class="bi bi-lock text-muted" title="تُحفظ مشفّرة"></i></label><textarea class="form-control" id="health_notes" name="health_notes" rows="2"></textarea></div>
    </div>
    <h3 class="h6">مراجعة</h3><div data-wz-review class="mb-2"></div>
    <p class="small text-muted">بعد الحفظ تُضاف بيانات ولي الأمر من ملف الطالب.</p>
    <button class="btn btn-success" type="submit" data-wz-submit><i class="bi bi-check2-circle"></i> حفظ وتسجيل</button>
  </section>
</form>
