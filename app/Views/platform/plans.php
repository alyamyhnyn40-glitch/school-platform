<?php use App\Services\PlanService; ?>
<div class="row g-3">
<?php foreach (array_merge($plans, [['id' => 0, 'name' => '', 'max_students' => null, 'max_storage_mb' => null, 'modules' => '["core"]', 'price' => 0, 'currency' => 'USD', 'billing_period' => 'yearly', 'is_active' => 1, 'schools' => 0]]) as $p): $isNew = (int)$p['id'] === 0; $mods = json_decode($p['modules'], true) ?: []; ?>
  <div class="col-md-6 col-xl-4">
    <form class="card-s h-100" method="<?= $isNew ? 'post' : 'put' ?>" action="<?= url('api/v1/platform/plans' . ($isNew ? '' : '/' . (int)$p['id'])) ?>" data-ajax novalidate>
      <?= csrf_field() ?>
      <h3 class="h6 mb-3"><?= $isNew ? 'خطة جديدة' : e($p['name']) . ' <small class="text-muted">(' . (int)$p['schools'] . ' مدرسة)</small>' ?></h3>
      <div class="mb-2"><label class="form-label">الاسم</label><input class="form-control" name="name" value="<?= e($p['name']) ?>" required></div>
      <div class="row g-2 mb-2">
        <div class="col-6"><label class="form-label">أقصى عدد طلاب</label><input class="form-control" name="max_students" type="number" min="0" value="<?= e($p['max_students']) ?>" placeholder="بلا حد"></div>
        <div class="col-6"><label class="form-label">التخزين (MB)</label><input class="form-control" name="max_storage_mb" type="number" min="0" value="<?= e($p['max_storage_mb']) ?>" placeholder="بلا حد"></div>
        <div class="col-4"><label class="form-label">السعر</label><input class="form-control" name="price" type="number" step="0.01" min="0" value="<?= e($p['price']) ?>"></div>
        <div class="col-4"><label class="form-label">العملة</label><select class="form-select" name="currency"><?php foreach (['USD', 'YER', 'SAR'] as $c): ?><option <?= $p['currency'] === $c ? 'selected' : '' ?>><?= $c ?></option><?php endforeach; ?></select></div>
        <div class="col-4"><label class="form-label">الدورة</label><select class="form-select" name="billing_period"><option value="yearly" <?= $p['billing_period'] === 'yearly' ? 'selected' : '' ?>>سنوي</option><option value="monthly" <?= $p['billing_period'] === 'monthly' ? 'selected' : '' ?>>شهري</option></select></div>
      </div>
      <div class="mb-2"><div class="form-label">الوحدات</div>
        <?php foreach (PlanService::MODULES as $k => $l): ?><div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="modules[]" value="<?= $k ?>" id="m<?= (int)$p['id'] . $k ?>" <?= in_array($k, $mods, true) ? 'checked' : '' ?> <?= $k === 'core' ? 'disabled checked' : '' ?>><label class="form-check-label" for="m<?= (int)$p['id'] . $k ?>"><?= e($l) ?></label></div><?php endforeach; ?></div>
      <div class="form-check form-switch mb-3"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="a<?= (int)$p['id'] ?>" <?= $p['is_active'] ? 'checked' : '' ?>><label class="form-check-label" for="a<?= (int)$p['id'] ?>">فعّالة</label></div>
      <button class="btn btn-primary" type="submit"><?= $isNew ? 'إضافة' : 'حفظ' ?></button>
    </form>
  </div>
<?php endforeach; ?>
</div>
