<?php $money = fn($x) => number_format((float)$x, 2); $c = $i['status'] === 'cancelled'; ?>
<section class="card-a4"><div class="frame">
  <?php if ($c): ?><div class="void-stamp">ملغاة</div><?php endif; ?>
  <div class="sub"><?= e($i['school_name']) ?><?= $i['city'] ? ' — ' . e($i['city']) : '' ?><?= $i['school_phone'] ? ' — ' . e($i['school_phone']) : '' ?></div><h1>فاتورة رسوم دراسية</h1>
  <?php if ($i['tax']['tax_enabled'] && $i['tax']['tax_number']): ?><div class="sub">الرقم الضريبي: <span dir="ltr"><?= e($i['tax']['tax_number']) ?></span></div><?php endif; ?>
  <table class="meta"><tr><td>رقم الفاتورة: <strong dir="ltr"><?= e($i['number']) ?></strong></td><td>التاريخ: <strong dir="ltr"><?= e($i['issued_on']) ?></strong></td></tr><tr><td>المطلوب من: <strong><?= e($i['guardian_name'] ?: '—') ?></strong></td><td>العام الدراسي: <strong><?= e($i['year_name']) ?></strong></td></tr>
    <tr><td colspan="2">الطالب/ة: <strong><?= e($i['student_name']) ?></strong> — <?= e($i['grade_name'] . ' / ' . $i['section_name']) ?> (قيد <?= e($i['student_no']) ?>)</td></tr></table>
  <table><thead><tr><th>البيان</th><th>المبلغ (<?= e($i['currency']) ?>)</th></tr></thead><tbody><?php foreach ($i['lines'] as $l): ?><tr><td><?= e($l['description']) ?></td><td class="<?= (float)$l['amount'] < 0 ? 'text-danger' : '' ?>"><?= $money($l['amount']) ?></td></tr><?php endforeach; ?></tbody></table>
  <table class="meta"><tr><td>الإجمالي قبل الضريبة: <strong><?= $money($i['subtotal']) ?></strong></td></tr>
    <?php if ($i['tax_percent'] !== null): ?><tr><td><?= e($i['tax']['tax_label'] ?: 'الضريبة') ?> (<?= (float)$i['tax_percent'] ?>%): <strong><?= $money($i['tax_amount']) ?></strong></td></tr><?php elseif ($i['tax']['exempt_note']): ?><tr><td class="small"><?= e($i['tax']['exempt_note']) ?></td></tr><?php endif; ?>
    <tr><td class="fs-5">الإجمالي المستحق: <strong><?= $money($i['total']) ?> <?= e($i['currency']) ?></strong></td></tr></table>
  <div class="small text-muted">سعر الصرف المرجعي عند الإصدار: 1 <?= e($i['currency']) ?> = <?= (float)$i['rate_to_base'] ?> (العملة الأساسية للمدرسة)</div>
  <?php if ($c): ?><div class="draft">ملغاة — <?= e($i['cancelled_reason']) ?></div><?php endif; ?>
  <div class="sign"><div>المحاسب</div><div>ختم المدرسة</div><div>مدير المدرسة</div></div>
</div></section>
