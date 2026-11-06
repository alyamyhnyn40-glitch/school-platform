<?php $v = (bool)$p['voided_at']; $money = fn($x) => number_format((float)$x, 2); ?>
<section class="card-a4 receipt"><div class="frame">
  <?php if ($v): ?><div class="void-stamp">ملغى</div><?php endif; ?>
  <div class="sub"><?= e($p['school_name']) ?><?= $p['city'] ? ' — ' . e($p['city']) : '' ?></div><h1>سند قبض</h1>
  <table class="meta"><tr><td>رقم السند: <strong dir="ltr"><?= e($p['number']) ?></strong></td><td>التاريخ: <strong dir="ltr"><?= e($p['paid_on']) ?></strong></td></tr>
    <tr><td colspan="2">استلمنا من: <strong><?= e($p['guardian'] ?: '—') ?></strong> — عن الطالب/ة: <strong><?= e($p['student_name']) ?></strong> (قيد <?= e($p['student_no']) ?>)</td></tr>
    <tr><td colspan="2">وذلك عن: <?= e($p['structure_name']) ?></td></tr></table>
  <table><thead><tr><th>المبلغ المستلم</th><th>طريقة الدفع</th><th>المرجع</th></tr></thead><tbody><tr><td><strong><?= $money($p['amount']) ?> <?= e($p['currency']) ?></strong></td><td><?= e($p['method_name']) ?></td><td dir="ltr"><?= e($p['reference_no'] ?: '—') ?></td></tr></tbody></table>
  <?php if ($p['currency'] !== $p['fee_currency']): ?><div class="small">سعر الصرف المثبَّت: <?= (float)$p['rate_to_base'] ?> مقابل العملة الأساسية — المحتسب على الرسوم: <strong><?= $money($p['applied_amount']) ?> <?= e($p['fee_currency']) ?></strong></div><?php endif; ?>
  <table class="meta"><tr><td>المتبقي على الطالب بعد هذه الدفعة (حتى اليوم): <strong><?= $money($p['balance_now']) ?> <?= e($p['fee_cur']) ?></strong></td></tr></table>
  <?php if ($v): ?><div class="draft">هذا السند ملغى بتاريخ <?= e($p['voided_at']) ?> — السبب: <?= e($p['void_reason']) ?></div><?php endif; ?>
  <div class="sign"><div>المستلم: <?= e($p['received_by_name']) ?></div><div>ختم المدرسة</div><div>توقيع المسدِّد</div></div>
</div></section>
