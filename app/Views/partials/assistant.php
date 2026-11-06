<?php /** زر المساعد العائم — يُحمَّل لمستخدم مسجّل فقط */
$__ai = \App\Services\AssistantService::suggestions(); ?>
<button type="button" class="ast-fab" id="astFab" aria-label="فتح المساعد الذكي" aria-expanded="false" aria-controls="astPanel"><i class="bi bi-chat-dots-fill" aria-hidden="true"></i></button>
<section class="ast-panel" id="astPanel" role="dialog" aria-label="المساعد الذكي" hidden data-ask="<?= url('api/v1/assistant/ask') ?>" data-csrf="<?= e(\App\Core\Csrf::token()) ?>" data-base="<?= e(url('')) ?>">
  <header class="ast-head"><strong><i class="bi bi-stars" aria-hidden="true"></i> مساعد <?= e(\App\Services\Brand::name()) ?></strong><div><button type="button" class="btn btn-sm btn-link text-white" id="astClear" title="محادثة جديدة" aria-label="محادثة جديدة"><i class="bi bi-arrow-counterclockwise"></i></button><button type="button" class="btn btn-sm btn-link text-white" id="astClose" aria-label="إغلاق"><i class="bi bi-x-lg"></i></button></div></header>
  <div class="ast-body" id="astBody" aria-live="polite"></div>
  <div class="ast-chips" id="astChips"><?php foreach ($__ai as $s): ?><button type="button" class="ast-chip"><?= e($s) ?></button><?php endforeach; ?></div>
  <form class="ast-form" id="astForm" novalidate><input class="form-control" id="astInput" maxlength="500" autocomplete="off" placeholder="اسألني عن استخدام المنصة…" aria-label="سؤالك"><button class="btn btn-primary" aria-label="إرسال"><i class="bi bi-send" aria-hidden="true"></i></button></form>
  <div class="ast-note">قد يخطئ المساعد. لا تشارك كلمات مرور أو بيانات شخصية.</div>
</section>
