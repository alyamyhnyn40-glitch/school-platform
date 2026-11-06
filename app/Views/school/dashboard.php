<?php
$__logo = \App\Services\Brand::schoolLogoPath() ? url('school/logo') : null;
$__roles = \App\Services\ProfileService::me()['roles'];
$quick = [
  ['school/attendance', 'bi-check2-square', 'تسجيل الحضور', 'attendance.take', null],
  ['school/grades', 'bi-pencil-square', 'إدخال الدرجات', 'grades.edit', null],
  ['school/lms', 'bi-camera-video', 'حصة مباشرة / تعليم', 'lms.manage', 'lms'],
  ['school/students', 'bi-people', 'الطلاب', 'students.view', null],
  ['school/fees', 'bi-cash-coin', 'الرسوم والتحصيل', 'fees.view', 'fees'],
  ['school/timetable', 'bi-grid-3x3-gap', 'الجدول الدراسي', 'timetable.view', null],
  ['school/announcements', 'bi-megaphone', 'إعلان جديد', 'comms.announce', 'comms'],
  ['school/staff', 'bi-person-badge', 'الموظفون', 'staff.view', null],
  ['school/results', 'bi-award', 'النتائج والشهادات', 'grades.view', null],
  ['school/academic-dashboard', 'bi-bar-chart-line', 'لوحة الأداء الأكاديمي', 'grades.view', null],
  ['school/study-plan', 'bi-journal-check', 'الخطة الدراسية', 'academic.manage', null],
  ['school/users', 'bi-shield-lock', 'المستخدمون والأدوار', 'rbac.manage', null],
  ['school/identity', 'bi-building-gear', 'هوية المدرسة', 'school.settings', null],
  ['school/backup', 'bi-cloud-arrow-up', 'النسخ الاحتياطي', 'backup.manage', null],
  ['messages', 'bi-chat-dots', 'الرسائل', 'comms.view', 'comms'],
];
?>
<section class="hero-welcome">
  <?php if ($__logo): ?><img class="school-mark" src="<?= $__logo ?>" alt=""><?php else: ?><span class="school-mark school-mark-ph"><i class="bi bi-building"></i></span><?php endif; ?>
  <div><h1><?= e($school['name'] ?? '') ?></h1><p><?= e(__t('dash.welcome', ['name' => \App\Core\Auth::user()['full_name']])) ?><?= $__roles ? ' — ' . e(implode('، ', $__roles)) : '' ?></p></div>
</section>
<div class="row g-3 mb-3">
  <?php foreach ([['bi-people', 'الطلاب الفعّالون', $stats['students'], 'students.view'], ['bi-person-badge', 'الموظفون', $stats['staff'], 'staff.view'], ['bi-diagram-3', 'الشعب', $stats['sections'], 'academic.view']] as [$ic, $lb, $n, $pm]): if (!can($pm)) { continue; } ?>
  <div class="col-12 col-sm-6 col-xl-4"><div class="card-s stat"><div class="ico"><i class="bi <?= $ic ?>"></i></div><div><div class="num"><?= (int)$n ?></div><div class="lbl"><?= e($lb) ?></div></div></div></div>
  <?php endforeach; ?>
</div>
<?php $qs = array_filter($quick, fn($q) => can($q[3]) && (!$q[4] || \App\Services\SubscriptionService::moduleEnabled(\App\Core\Tenant::require(), $q[4]))); if ($qs): ?>
<h2 class="section-title">وصول سريع</h2>
<div class="quick-grid"><?php foreach ($qs as $q): ?><a class="quick-tile" href="<?= url($q[0]) ?>"><i class="bi <?= $q[1] ?>" aria-hidden="true"></i><span><?= e($q[2]) ?></span></a><?php endforeach; ?></div>
<?php endif; ?>
<?php if (!$stats['students'] && !$stats['sections']): ?><div class="card-s empty-state"><i class="bi bi-inbox"></i><?= e(__t('dash.empty_school')) ?></div><?php endif; ?>

<?php if (!empty($today)): ?><div class="card-s mt-3"><h3 class="h6 mb-3"><i class="bi bi-calendar-day"></i> حصصي اليوم</h3>
  <div class="list-group list-group-flush"><?php foreach ($today as $t): ?><div class="list-group-item bg-transparent d-flex flex-wrap gap-2 align-items-center"><span class="badge text-bg-primary">الحصة <?= (int)$t['period_no'] ?></span><strong><?= e($t['subject']) ?></strong><span class="text-muted"><?= e($t['grade_name'] . ' / ' . $t['section_name']) ?><?= $t['room'] ? ' — ' . e($t['room']) : '' ?></span><small dir="ltr" class="text-muted"><?= e(substr($t['starts_at'], 0, 5)) ?>–<?= e(substr($t['ends_at'], 0, 5)) ?></small>
    <span class="ms-auto d-flex gap-2"><?php if (can('attendance.take')): ?><a class="btn btn-sm btn-outline-primary" href="<?= url('school/attendance') ?>">الحضور</a><?php endif; ?><?php if (can('grades.edit')): ?><a class="btn btn-sm btn-outline-secondary" href="<?= url('school/grades') ?>">الدرجات</a><?php endif; ?></span></div><?php endforeach; ?></div></div>
<?php endif; ?>
<?php foreach (($liveNow ?? []) as $v): ?><div class="alert alert-success d-flex flex-wrap align-items-center gap-2 mt-3"><span class="spinner-grow spinner-grow-sm text-success" aria-hidden="true"></span><div class="flex-grow-1"><strong><?= $v['state'] === 'live' ? 'حصتك المباشرة جارية' : 'حصة مباشرة تبدأ قريبًا' ?>:</strong> <?= e($v['title']) ?> <small class="text-muted">— <?= e($v['subject_name']) ?></small></div><a class="btn btn-sm btn-success" href="<?= url('school/lms?ss=' . (int)$v['section_subject_id']) ?>">فتح الحصص</a></div><?php endforeach; ?>
