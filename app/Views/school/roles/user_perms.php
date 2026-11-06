<?php $roles = array_flip($eff['roles']); $ov = $eff['overrides']; $effective = array_flip($eff['effective']); ?>
<div class="page-head"><div><h1 class="page-title">صلاحيات خاصة: <?= e($u['full_name'] ?? '') ?></h1><p class="page-sub">الأصل أن يأخذ المستخدم صلاحياته من أدواره. هنا تمنحه صلاحية إضافية أو تحجب عنه صلاحية لدور ما — دون إنشاء دور جديد.</p></div><a class="btn btn-outline-secondary" href="<?= url('school/users') ?>"><i class="bi bi-arrow-right"></i> المستخدمون</a></div>
<div class="alert alert-info small"><i class="bi bi-info-circle"></i> «من الدور» = يملكها عبر أحد أدواره. «منح» يضيفها. «حجب» يمنعها حتى لو منحها الدور. لا يمكنك منح صلاحية لا تملكها أنت. كل تغيير يُسجَّل في سجل التدقيق.</div>
<form method="post" action="<?= url('api/v1/school/users/' . (int)$u['id'] . '/permissions') ?>" data-ajax data-reload novalidate><?= csrf_field() ?>
<div class="row g-3">
<?php foreach ($catalog as $mod => $perms): ?>
  <div class="col-lg-6"><div class="card-s h-100"><h2 class="h6"><?= e($modules[$mod] ?? $mod) ?></h2>
    <?php foreach ($perms as $code => $label): $st = $ov[$code] ?? 'inherit'; ?>
    <div class="d-flex flex-wrap align-items-center gap-2 py-2 border-bottom">
      <div class="flex-grow-1"><div><?= e($label) ?></div><small class="text-muted" dir="ltr"><?= e($code) ?></small>
        <?= isset($roles[$code]) ? '<span class="badge text-bg-light border ms-1">من الدور</span>' : '' ?><?= isset($effective[$code]) ? '<span class="badge text-bg-success ms-1">فعّالة</span>' : '<span class="badge text-bg-secondary ms-1">غير متاحة</span>' ?></div>
      <select class="form-select form-select-sm w-auto" name="perm[<?= e($code) ?>]" aria-label="<?= e($label) ?>">
        <option value="inherit" <?= $st === 'inherit' ? 'selected' : '' ?>>حسب الدور</option><option value="allow" <?= $st === 'allow' ? 'selected' : '' ?>>منح</option><option value="deny" <?= $st === 'deny' ? 'selected' : '' ?>>حجب</option></select>
    </div>
    <?php endforeach; ?></div></div>
<?php endforeach; ?>
</div>
<div class="d-flex justify-content-end mt-3"><button class="btn btn-primary"><i class="bi bi-check2"></i> حفظ الصلاحيات الخاصة</button></div>
</form>
