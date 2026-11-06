<?php if (!$year): ?><div class="alert alert-warning">لا توجد سنة دراسية فعّالة. فعّل سنة من «السنوات والفصول».</div><?php elseif (!$sections): ?><div class="alert alert-info">لا توجد شعب متاحة لك لتسجيل الحضور.</div><?php else: ?>
<div class="card-s mb-3">
  <div class="row g-2 align-items-end">
    <div class="col-12 col-md-4"><label class="form-label" for="section">الشعبة</label><select class="form-select" id="section"><?php foreach ($sections as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['grade_name'] . ' / ' . $s['name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-6 col-md-3"><label class="form-label" for="date">التاريخ</label><input class="form-control" type="date" id="date" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>"></div>
    <div class="col-6 col-md-3"><label class="form-label" for="period">الحصة</label><select class="form-select" id="period"><option value="0">اليوم كاملًا</option><?php for ($i = 1; $i <= 8; $i++): ?><option value="<?= $i ?>">الحصة <?= $i ?></option><?php endfor; ?></select></div>
    <div class="col-12 col-md-2"><button class="btn btn-outline-primary w-100" id="loadBtn" type="button">عرض</button></div>
  </div>
  <div class="d-flex flex-wrap gap-2 align-items-center mt-3"><small class="text-muted" id="rosterInfo"></small><span class="badge text-bg-warning d-none" id="pendingBadge"></span>
    <span class="ms-auto d-flex gap-2"><button class="btn btn-outline-success" id="allPresent" type="button">الكل حاضر</button><button class="btn btn-primary" id="saveBtn" type="button"><i class="bi bi-check2"></i> حفظ الحضور</button></span></div>
</div>
<div id="failedOps"></div>
<div class="card-s" id="roster"><?= e(__t('common.loading')) ?></div>
<?php endif; ?>
