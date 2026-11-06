<?php use App\Services\CredentialService as C; ?>
<div class="print-page text-center" style="max-width:800px;margin:auto;padding:2.5rem;border:6px double #0f766e">
 <h1 class="h3"><?= e($c['school_name']) ?></h1><div class="text-muted mb-4"><?= e($c['city'] ?? '') ?></div>
 <h2 class="h2 my-4"><?= e($c['title']) ?></h2><p class="fs-5">تشهد إدارة المدرسة بأن الطالب/ـة</p><p class="display-6 fw-bold"><?= e($c['full_name']) ?></p>
 <p class="fs-6"><?= e(C::KINDS[$c['kind']] ?? '') ?> <?= $c['notes'] ? '— ' . e($c['notes']) : '' ?></p><p class="mt-4">تاريخ الإصدار: <?= e($c['issued_on']) ?></p>
 <?php if ($c['revoked_at']): ?><p class="text-danger fs-4">هذه الشهادة مسحوبة</p><?php endif; ?>
 <div class="mt-5 small">رمز التحقق: <strong dir="ltr"><?= e($c['verify_code']) ?></strong><br>للتحقق من صحة الشهادة افتح <span dir="ltr"><?= e(url('verify/' . $c['verify_code'])) ?></span></div>
 <div class="d-flex justify-content-between mt-5"><div>ختم المدرسة</div><div>مدير المدرسة: ____________</div></div></div>
