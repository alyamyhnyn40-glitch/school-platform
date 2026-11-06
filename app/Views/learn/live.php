<?php $st = $l['state']; $label = ['live' => ['success', 'مباشر الآن'], 'soon' => ['warning', 'تبدأ بعد قليل'], 'upcoming' => ['secondary', 'قادمة'], 'ended' => ['dark', 'انتهت']][$st]; $joinable = in_array($st, ['live', 'soon'], true); ?>
<div class="card-s mx-auto text-center" style="max-width:560px">
  <div class="display-4 mb-2 text-<?= $label[0] ?>"><i class="bi bi-camera-video<?= $st === 'ended' ? '-off' : '' ?>-fill"></i></div>
  <h2 class="h4"><?= e($l['title']) ?></h2><div class="text-muted mb-2"><?= e($l['subject_name']) ?></div><span class="badge text-bg-<?= $label[0] ?> fs-6 mb-3"><?= $label[1] ?></span>
  <div class="small text-muted mb-3" dir="ltr"><?= e(substr($l['starts_at'], 0, 16)) ?><?= $l['ends_at'] ? ' → ' . e(substr($l['ends_at'], 11, 5)) : '' ?></div>
  <?php if ($joinable): ?><button class="btn btn-success btn-lg w-100" type="button" data-join="<?= url('api/v1/learn/live/' . (int)$l['id'] . '/join') ?>"><i class="bi bi-box-arrow-up-right"></i> انضم الآن</button><p class="small text-muted mt-2 mb-0">يُسجَّل حضورك تلقائيًا عند الدخول. تُفتح الحصة في نافذة جديدة.</p>
  <?php elseif ($st === 'upcoming'): ?><div class="alert alert-info mb-0">يُفتح الدخول قبل الموعد بـ 15 دقيقة. ستصلك إشعارات عند البدء.</div><?php else: ?><div class="alert alert-secondary mb-0">انتهت هذه الحصة.</div><?php endif; ?>
  <a class="btn btn-link mt-2" href="<?= url('learn') ?>">العودة إلى موادي</a></div>
