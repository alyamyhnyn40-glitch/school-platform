<?php
$tabs = [['platform/settings/brand', 'bi-palette', 'الهوية والإعدادات العامة', 'brand'], ['platform/settings/sms', 'bi-chat-dots', 'الرسائل SMS', 'sms'], ['platform/settings/verification', 'bi-shield-check', 'رموز التحقق', 'verification'], ['platform/settings/whatsapp', 'bi-whatsapp', 'واتساب', 'whatsapp'], ['platform/settings/email', 'bi-envelope', 'البريد', 'email'], ['platform/settings/ai', 'bi-stars', 'المساعد الذكي', 'ai']];
$active = $active ?? '';
?>
<ul class="nav nav-pills mb-3 flex-wrap gap-1"><?php foreach ($tabs as [$href, $icon, $label, $key]): ?><li class="nav-item"><a class="nav-link <?= $active === $key ? 'active' : '' ?>" href="<?= url($href) ?>"><i class="bi <?= $icon ?>"></i> <?= e($label) ?></a></li><?php endforeach; ?></ul>
