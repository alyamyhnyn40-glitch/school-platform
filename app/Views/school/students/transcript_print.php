<div class="print-page" style="max-width:900px;margin:auto;padding:1rem">
 <div class="d-flex align-items-center gap-3 mb-3 border-bottom pb-2"><?php if (\App\Services\Brand::schoolLogoPath()): ?><img src="<?= url('school/logo') ?>" alt="" style="height:64px"><?php endif; ?>
  <div><h1 class="h4 mb-0"><?= e($school['name']) ?></h1><div class="text-muted"><?= e($school['city'] ?? '') ?></div></div><div class="ms-auto text-end"><h2 class="h5 mb-0">السجل الأكاديمي</h2><div><?= e($t['student']['full_name']) ?> — <?= e($t['student']['student_no'] ?? '') ?></div></div></div>
 <?= \App\Core\View::partial('transcript', ['t' => $t]) ?>
 <div class="d-flex justify-content-between mt-4 small"><div>تاريخ الطباعة: <?= date('Y-m-d') ?></div><div>ختم وتوقيع الإدارة: ____________</div></div>
</div>
