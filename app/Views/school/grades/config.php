<div class="alert alert-info">القيم المبدئية (100 / نجاح 50 / تقديرات) <strong>للبدء فقط</strong>؛ عدّلها لتطابق نظام الوزارة والمدرسة. تغيير الأوزان غير ممكن بعد إنشاء تقييمات على النظام.</div>
<?php foreach (array_merge($schemes, [['id' => 0, 'name' => '', 'max_score' => 100, 'pass_score' => 50, 'average_method' => 'weighted', 'rounding' => 2, 'stage_id' => null, 'is_default' => 0, 'types' => [['name' => '', 'weight' => 100, 'max_score' => 100]], 'scales' => [['label' => 'ناجح', 'min_percent' => 0]]]]) as $s): $new = (int)$s['id'] === 0; ?>
<form class="card-s mb-3 scheme-form" data-url="<?= url('api/v1/school/grading-schemes' . ($new ? '' : '/' . (int)$s['id'])) ?>" data-method="<?= $new ? 'POST' : 'PUT' ?>" data-types="<?= e(json_encode($s['types'], JSON_UNESCAPED_UNICODE)) ?>" data-scales="<?= e(json_encode($s['scales'], JSON_UNESCAPED_UNICODE)) ?>">
  <h2 class="h6"><?= $new ? 'نظام جديد (لمرحلة محددة)' : e($s['name']) . ($s['is_default'] ? ' <span class="badge text-bg-success">افتراضي</span>' : '') ?></h2>
  <div class="row g-2 mb-2">
    <div class="col-md-4"><label class="form-label">الاسم</label><input class="form-control" name="name" value="<?= e($s['name']) ?>" required></div>
    <div class="col-md-2 col-6"><label class="form-label">الدرجة العظمى</label><input class="form-control" name="max_score" type="number" step="0.01" value="<?= (float)$s['max_score'] ?>"></div>
    <div class="col-md-2 col-6"><label class="form-label">درجة النجاح</label><input class="form-control" name="pass_score" type="number" step="0.01" value="<?= (float)$s['pass_score'] ?>"></div>
    <div class="col-md-2 col-6"><label class="form-label">طريقة المعدل</label><select class="form-select" name="average_method"><option value="weighted" <?= $s['average_method'] === 'weighted' ? 'selected' : '' ?>>موزون</option><option value="arithmetic" <?= $s['average_method'] === 'arithmetic' ? 'selected' : '' ?>>متوسط حسابي</option></select></div>
    <div class="col-md-2 col-6"><label class="form-label">الكسور</label><input class="form-control" name="rounding" type="number" min="0" max="4" value="<?= (int)$s['rounding'] ?>"></div>
    <div class="col-md-4"><label class="form-label">المرحلة (فارغ = كل المراحل)</label><select class="form-select" name="stage_id"><option value="">كل المراحل</option><?php foreach ($stages as $st): ?><option value="<?= (int)$st['id'] ?>" <?= (int)$s['stage_id'] === (int)$st['id'] ? 'selected' : '' ?>><?= e($st['name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-4 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_default" id="d<?= (int)$s['id'] ?>" <?= $s['is_default'] ? 'checked' : '' ?>><label class="form-check-label" for="d<?= (int)$s['id'] ?>">النظام الافتراضي</label></div></div>
  </div>
  <div class="row g-3"><div class="col-lg-7"><h3 class="h6">أنواع التقييم والأوزان <small class="wsum text-muted"></small></h3><div class="types"></div><button type="button" class="btn btn-sm btn-outline-primary add-type">+ نوع تقييم</button></div>
    <div class="col-lg-5"><h3 class="h6">سلّم التقديرات</h3><div class="scales"></div><button type="button" class="btn btn-sm btn-outline-primary add-scale">+ تقدير</button></div></div>
  <button class="btn btn-primary mt-3" type="submit"><?= $new ? 'إضافة النظام' : 'حفظ' ?></button>
</form>
<?php endforeach; ?>
