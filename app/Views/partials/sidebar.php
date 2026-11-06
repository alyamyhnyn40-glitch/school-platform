<?php
use App\Core\Auth;
$path = (new \App\Core\Request())->path();
$items = [];
if (Auth::isPlatform() && !\App\Core\Session::get('support_mode')) {
    $items = [
        ['#', 'الرئيسية'],
        ['/platform', 'bi-speedometer2', 'nav.dashboard', null],
        ['#', 'إدارة المنصة'],
        ['/platform/schools', 'bi-building', 'nav.schools', 'platform.schools'],
        ['/platform/requests', 'bi-inbox', 'nav.requests', 'platform.schools'],
        ['/platform/plans', 'bi-card-checklist', 'nav.plans', 'platform.plans'],
        ['#', 'الإعدادات والمراقبة'],
        ['/platform/settings/brand', 'bi-gear', 'nav.platform_settings', 'platform.settings'],
        ['/platform/audit', 'bi-journal-text', 'nav.audit', 'platform.audit'],
        ['/platform/security', 'bi-shield-exclamation', 'nav.security', 'platform.audit'],
        ['/platform/security-check', 'bi-shield-check', 'nav.security_check', 'platform.settings'],
    ];
} elseif (in_array(Auth::type(), ['guardian', 'student'], true)) {
    $items = [['/portal', 'bi-house-heart', 'nav.portal', 'portal.view'], ['/learn', 'bi-journal-bookmark', 'nav.learn', 'portal.view', 'lms'], ['/announcements', 'bi-megaphone', 'nav.announcements', 'comms.view', 'comms'], ['/messages', 'bi-chat-dots', 'nav.messages', 'comms.view', 'comms']];
    if (Auth::type() !== 'student') { $items = array_values(array_filter($items, fn($i) => $i[0] !== '/learn')); }
} else {
    $items = [
        ['#', 'الرئيسية'],
        ['/dashboard', 'bi-speedometer2', 'nav.dashboard', null],
        ['#', 'شؤون الطلاب والمدرسة'],
        ['/school/students', 'bi-people', 'nav.students', 'students.view'],
        ['/school/staff', 'bi-person-badge', 'nav.staff', 'staff.view'],
        ['/school/sections', 'bi-diagram-3', 'nav.sections', 'academic.view'],
        ['/school/structure', 'bi-bookmarks', 'nav.structure', 'academic.view'],
        ['/school/study-plan', 'bi-journal-check', 'nav.study_plan', 'academic.view'],
        ['/school/years', 'bi-calendar3', 'nav.years', 'academic.view'],
        ['/school/admissions', 'bi-person-plus', 'nav.admissions', 'admissions.manage', 'extended'],
        ['#', 'الدراسة اليومية'],
        ['/school/timetable', 'bi-grid-3x3-gap', 'nav.timetable', 'timetable.view'],
        ['/school/attendance', 'bi-check2-square', 'nav.attendance', 'attendance.view'],
        ['/school/grades', 'bi-pencil-square', 'nav.grades', 'grades.view'],
        ['/school/results', 'bi-award', 'nav.results', 'grades.view'],
        ['/school/transcripts', 'bi-journal-text', 'nav.transcripts', 'grades.view'],
        ['/school/lms', 'bi-mortarboard', 'nav.lms', 'lms.manage', 'lms'],
        ['#', 'التواصل'],
        ['/school/announcements', 'bi-megaphone', 'nav.announce', 'comms.announce', 'comms'],
        ['#', 'المالية والموارد'],
        ['/school/fees', 'bi-cash-coin', 'nav.fees', 'fees.view', 'fees'],
        ['/school/payroll', 'bi-wallet2', 'nav.payroll', 'payroll.manage', 'extended'],
        ['/school/facilities', 'bi-building-check', 'nav.facilities', 'facilities.book', 'extended'],
        ['#', 'المتابعة والتقارير'],
        ['/school/academic-dashboard', 'bi-bar-chart-line', 'nav.academic_dash', 'grades.view'],
        ['/school/risk', 'bi-exclamation-triangle', 'nav.risk', 'risk.view', 'extended'],
        ['/school/counseling', 'bi-chat-heart', 'nav.counseling', 'counseling.manage', 'extended'],
        ['/school/attendance/report', 'bi-bar-chart', 'nav.att_report', 'reports.view'],
        ['/school/analytics', 'bi-graph-up-arrow', 'nav.analytics', 'analytics.view', 'extended'],
        ['/school/credentials', 'bi-patch-check', 'nav.credentials', 'reportcards.issue', 'extended'],
        ['#', 'الإدارة والإعدادات'],
        ['/school/identity', 'bi-building-gear', 'nav.identity', 'school.settings'],
        ['/school/users', 'bi-shield-lock', 'nav.users', 'rbac.manage'],
        ['/school/grades/config', 'bi-sliders', 'nav.grading_config', 'grades.config'],
        ['/school/backup', 'bi-cloud-arrow-up', 'nav.backup', 'backup.manage'],
        ['/school/import', 'bi-file-earmark-arrow-up', 'nav.import', 'import.run'],
        ['/school/audit', 'bi-journal-text', 'nav.audit', 'audit.view'],
        ['/school/security', 'bi-shield-exclamation', 'nav.security', 'audit.view'],
    ];}
?>
<nav class="sidebar-nav" aria-label="القائمة الرئيسية">
<?php
$isActive = function (string $href) use ($path): bool {
    return $path === $href || ($href !== '/platform' && $href !== '/dashboard' && $href !== '/school/grades' && $href !== '/school/attendance' && str_starts_with($path, $href . '/') && !($href === '/school/grades' )) || ($href === '/school/grades' && (bool)preg_match('#^/school/grades/\d+#', $path));
};
$out = []; $pending = null;
foreach ($items as $it) {
    if ($it[0] === '#') { $pending = $it[1]; continue; }
    [$href, $icon, $label, $perm] = $it; $mod = $it[4] ?? null;
    if ($perm && !can($perm)) { continue; }
    if ($mod && !\App\Services\SubscriptionService::moduleEnabled(\App\Core\Tenant::require(), $mod)) { continue; }
    if ($pending !== null) { $out[] = ['h', $pending]; $pending = null; }
    $out[] = ['i', $href, $icon, $label, $isActive($href)];
}
foreach ($out as $o):
    if ($o[0] === 'h'): ?><div class="nav-group"><?= e($o[1]) ?></div><?php
    else: ?><a href="<?= url($o[1]) ?>" class="<?= $o[4] ? 'active' : '' ?>"<?= $o[4] ? ' aria-current="page"' : '' ?>><i class="bi <?= $o[2] ?>" aria-hidden="true"></i><span><?= e(__t($o[3])) ?></span></a><?php
    endif;
endforeach; ?>
</nav>
