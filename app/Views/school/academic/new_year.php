<div class="page-head"><div><h1 class="page-title">سنة دراسية جديدة</h1><p class="page-sub">أنشئ السنة الجديدة وانسخ إليها الفصول والشعب والمواد والخطة الدراسية في خطوة واحدة.</p></div><a class="btn btn-outline-secondary" href="<?= url('school/years') ?>"><i class="bi bi-arrow-right"></i> السنوات والفصول</a></div>
<form class="card-s" style="max-width:860px" method="post" action="<?= url('api/v1/school/years/wizard') ?>" data-ajax data-wizard novalidate><?= csrf_field() ?>
  <section class="wz-step" data-title="بيانات السنة">
    <div class="row g-3">
      <div class="col-md-6"><label class="form-label" for="wn">اسم السنة</label><input class="form-control" id="wn" name="name" value="<?= e($s['name']) ?>" required maxlength="50" data-label="اسم السنة"><div class="form-text">مثل 2027/2028</div></div>
      <div class="col-md-6"><label class="form-label" for="wsrc">النسخ من سنة</label><select class="form-select" id="wsrc" name="source_year_id" data-label="النسخ من"><option value="">— لا شيء (سنة فارغة) —</option><?php foreach ($years as $y): ?><option value="<?= (int)$y['id'] ?>" <?= (int)$y['id'] === (int)$s['source'] ? 'selected' : '' ?>><?= e($y['name']) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-6"><label class="form-label" for="ws">تاريخ البداية</label><input class="form-control" id="ws" name="starts_on" type="date" value="<?= e($s['starts_on']) ?>" required data-label="البداية"></div>
      <div class="col-md-6"><label class="form-label" for="we">تاريخ النهاية</label><input class="form-control" id="we" name="ends_on" type="date" value="<?= e($s['ends_on']) ?>" required data-label="النهاية"></div>
    </div>
  </section>
  <section class="wz-step" data-title="الفصول الدراسية">
    <div data-show-if="source_year_id!=">
      <label class="opt-card"><input type="hidden" name="copy_terms" value="0"><input class="form-check-input" type="checkbox" name="copy_terms" value="1" checked data-label="نسخ الفصول"><span><strong>انسخ فصول السنة المصدر</strong><br><small class="text-muted">بنفس الأسماء والأرقام، والتواريخ مُزاحة سنة للأمام.</small></span></label>
    </div>
    <div data-show-if="source_year_id=">
      <label class="form-label" for="wtc">عدد الفصول</label><select class="form-select" id="wtc" name="terms_count" data-label="عدد الفصول"><option value="2">فصلان (مقسّمة بالتساوي)</option><option value="3">ثلاثة فصول</option><option value="1">فصل واحد</option><option value="0">لا تُنشئ فصولًا الآن</option></select>
    </div>
  </section>
  <section class="wz-step" data-title="الشعب والمواد">
    <div data-show-if="source_year_id!=">
      <label class="opt-card"><input type="hidden" name="copy_plan" value="0"><input class="form-check-input" type="checkbox" name="copy_plan" value="1" checked data-label="نسخ الخطة الدراسية"><span><strong>انسخ الخطة الدراسية</strong><br><small class="text-muted">مواد كل صف وفصولها وساعاتها.</small></span></label>
      <label class="opt-card"><input type="hidden" name="copy_sections" value="0"><input class="form-check-input" type="checkbox" name="copy_sections" value="1" checked data-label="نسخ الشعب"><span><strong>انسخ الشعب بموادها</strong><br><small class="text-muted">نفس أسماء الشعب وسعتها لكل صف.</small></span></label>
      <label class="opt-card"><input type="hidden" name="copy_teachers" value="0"><input class="form-check-input" type="checkbox" name="copy_teachers" value="1" data-label="نسخ إسناد المعلمين"><span><strong>انسخ إسناد المعلمين ومربّي الصفوف</strong><br><small class="text-muted">يمكن تعديله لاحقًا من صفحة كل شعبة.</small></span></label>
      <label class="opt-card"><input type="hidden" name="apply_plan" value="0"><input class="form-check-input" type="checkbox" name="apply_plan" value="1" checked data-label="تطبيق الخطة على الشعب"><span><strong>طبّق الخطة على الشعب الجديدة</strong><br><small class="text-muted">لتتطابق مواد الشعب مع الخطة.</small></span></label>
    </div>
    <div data-show-if="source_year_id="><div class="alert alert-info mb-0">سنة فارغة: أضف الشعب والخطة الدراسية بعد الإنشاء.</div></div>
  </section>
  <section class="wz-step" data-title="المراجعة والتأكيد">
    <p class="text-muted">راجع الاختيارات ثم اضغط «إنشاء السنة». لن تتأثر السنة الحالية ولا طلابها؛ الترفيع خطوة منفصلة.</p>
    <div data-wz-review class="mb-2"></div>
    <button class="btn btn-success" type="submit" data-wz-submit><i class="bi bi-check2-circle"></i> إنشاء السنة</button>
  </section>
</form>
