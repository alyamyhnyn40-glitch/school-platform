<?php $labels = \App\Services\OnlinePaymentService::STATUSES; $st = $op['status']; ?>
<div class="card-s mx-auto text-center" style="max-width:520px" id="payBox" data-status-url="<?= e(url('api/v1/portal/pay/' . $op['reference'] . '/status')) ?>" data-state="<?= e($st) ?>">
  <div class="display-4 mb-2" id="payIcon"><?= $st === 'paid' ? '<i class="bi bi-check-circle text-success"></i>' : (in_array($st, ['failed', 'cancelled', 'expired'], true) ? '<i class="bi bi-x-circle text-danger"></i>' : '<i class="bi bi-hourglass-split text-warning"></i>') ?></div>
  <h2 class="h5" id="payLabel"><?= e($labels[$st] ?? $st) ?></h2>
  <p class="mb-1"><?= e(number_format((float)$op['amount'], 2)) ?> <?= e($op['currency']) ?></p>
  <p class="small text-muted" id="payNote"><?php if ($st === 'pending'): ?>بانتظار تأكيد المزود… قد يستغرق ثوانٍ. لا تُعد الدفع.<?php elseif ($st === 'paid'): ?>سند القبض: <strong dir="ltr"><?= e($op['receipt_no']) ?></strong><?php elseif ($st === 'review'): ?>دفعتك مستلمة لدى المزود وتجري مراجعتها في المدرسة، ولن تُخصم مرتين.<?php else: ?><?= e($op['failure_reason'] ?? '') ?><?php endif; ?></p>
  <a class="btn btn-outline-primary" href="<?= url('portal/fees/' . (int)$op['student_id']) ?>">العودة إلى كشف الرسوم</a>
</div>
