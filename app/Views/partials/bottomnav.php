<?php
use App\Core\Auth;
$path = (new \App\Core\Request())->path(); $t = Auth::type(); $items = [];
if (Auth::isPlatform() && !\App\Core\Session::get('support_mode')) {
    $items = [['/platform', 'bi-speedometer2', 'الرئيسية'], ['/platform/schools', 'bi-building', 'المدارس'], ['/platform/requests', 'bi-inbox', 'الطلبات'], ['/platform/settings/brand', 'bi-gear', 'الإعدادات']];
} elseif (in_array($t, ['guardian', 'student'], true)) {
    $items = [['/portal', 'bi-house-heart', 'الرئيسية']]; if ($t === 'student') { $items[] = ['/learn', 'bi-journal-bookmark', 'دروسي']; } else { $items[] = ['/portal/fees', 'bi-cash-coin', 'الرسوم']; }
    $items[] = ['/announcements', 'bi-megaphone', 'الإعلانات']; $items[] = ['/messages', 'bi-chat-dots', 'الرسائل'];
} else {
    $items = [['/dashboard', 'bi-speedometer2', 'الرئيسية']];
    foreach ([['/school/attendance', 'bi-check2-square', 'الحضور', 'attendance.take'], ['/school/grades', 'bi-pencil-square', 'الدرجات', 'grades.view'], ['/school/students', 'bi-people', 'الطلاب', 'students.view'], ['/school/fees', 'bi-cash-coin', 'الرسوم', 'fees.view'], ['/messages', 'bi-chat-dots', 'الرسائل', 'comms.view']] as $c) { if (count($items) < 4 && can($c[3])) { $items[] = [$c[0], $c[1], $c[2]]; } }
}
?>
<nav class="bottom-nav d-lg-none" aria-label="تنقل سريع">
  <?php foreach ($items as [$h, $i, $l]): $on = $path === $h || ($h !== '/dashboard' && $h !== '/platform' && $h !== '/portal' && str_starts_with($path, $h . '/')); ?>
  <a href="<?= url($h) ?>" class="<?= $on ? 'on' : '' ?>"<?= $on ? ' aria-current="page"' : '' ?>><i class="bi <?= $i ?>" aria-hidden="true"></i><span><?= e($l) ?></span></a>
  <?php endforeach; ?>
  <button type="button" id="bottomMenu" aria-label="القائمة الكاملة"><i class="bi bi-grid-3x3-gap" aria-hidden="true"></i><span>المزيد</span></button>
</nav>
