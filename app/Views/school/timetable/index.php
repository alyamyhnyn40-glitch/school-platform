<?php
$by = []; foreach ($slots as $s) { $by[(int)$s['day_of_week']][(int)$s['period_no']] = $s; }
$D = \App\Services\TimetableService::DAY_NAMES;
?>
<div class="d-flex flex-wrap gap-2 align-items-center mb-3 no-print">
  <form method="get" class="d-flex gap-2"><select class="form-select" name="section" data-autosubmit aria-label="الشعبة"><?php foreach ($sections as $s): ?><option value="<?= (int)$s['id'] ?>" <?= $secId === (int)$s['id'] ? 'selected' : '' ?>><?= e($s['grade_name'] . ' / ' . $s['name']) ?></option><?php endforeach; ?></select></form>
  <a class="btn btn-outline-secondary ms-auto" href="<?= url('school/timetable/config') ?>"><i class="bi bi-gear"></i> الأيام والحصص والقاعات</a><button class="btn btn-outline-primary" data-print type="button"><i class="bi bi-printer"></i> طباعة</button></div>
<?php if (!$year): ?><div class="alert alert-warning">لا توجد سنة فعّالة.</div><?php elseif (!$sections): ?><div class="alert alert-info">لا توجد شعب في السنة الفعّالة.</div><?php else: ?>
<div class="row g-3"><div class="col-xl-9"><div class="card-s"><div class="table-responsive"><table class="table table-bordered text-center align-middle tt-table mb-0">
  <thead><tr><th style="width:90px">الحصة</th><?php foreach ($cfg['days'] as $d): ?><th><?= $D[$d] ?></th><?php endforeach; ?></tr></thead><tbody>
  <?php foreach ($cfg['periods'] as $p): ?><tr><th class="small"><?= (int)$p['no'] ?><br><span class="text-muted" dir="ltr"><?= e($p['start']) ?>–<?= e($p['end']) ?></span></th>
    <?php foreach ($cfg['days'] as $d): $x = $by[$d][$p['no']] ?? null; ?><td class="tt-cell"><?php if ($x): ?><div class="fw-bold"><?= e($x['subject']) ?></div><div class="small text-muted"><?= e($x['teacher']) ?></div><?php if ($x['room']): ?><div class="small"><i class="bi bi-door-open"></i> <?= e($x['room']) ?></div><?php endif; ?>
      <button class="btn btn-sm btn-link text-danger p-0 no-print" data-api="<?= url('api/v1/school/timetable/' . (int)$x['id']) ?>" data-method="DELETE" data-confirm="حذف الحصة؟" aria-label="حذف"><i class="bi bi-x-circle"></i></button>
      <?php else: ?><button class="btn btn-sm btn-outline-primary slot-add no-print" type="button" data-day="<?= (int)$d ?>" data-period="<?= (int)$p['no'] ?>" data-label="<?= e($D[$d] . ' — الحصة ' . $p['no']) ?>" aria-label="إضافة حصة <?= e($D[$d]) ?> <?= (int)$p['no'] ?>">+</button><?php endif; ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody></table></div></div></div>
  <div class="col-xl-3 no-print"><form class="card-s" id="slotForm" method="post" action="<?= url('api/v1/school/timetable') ?>" data-ajax data-reload novalidate><?= csrf_field() ?>
    <h3 class="h6">إضافة حصة</h3><input type="hidden" name="day" id="slotDay"><input type="hidden" name="period" id="slotPeriod">
    <div class="alert alert-secondary py-2 small" id="slotLabel">اضغط «+» في خلية فارغة لاختيار الوقت.</div>
    <div class="mb-2"><label class="form-label" for="ssel">المادة (الحصص الموضوعة/الأسبوعية)</label><select class="form-select" id="ssel" name="section_subject_id" required><option value="">— اختر —</option>
      <?php $placed = []; foreach ($slots as $s) { $placed[$s['section_subject_id']] = ($placed[$s['section_subject_id']] ?? 0) + 1; } foreach ($items as $it): ?><option value="<?= (int)$it['id'] ?>"><?= e($it['subject_name']) ?> (<?= $placed[$it['id']] ?? 0 ?>/<?= (int)$it['weekly_periods'] ?>) — <?= e($it['teachers'] ?: 'بلا معلم') ?></option><?php endforeach; ?></select></div>
    <div class="mb-2"><label class="form-label" for="staff">المعلم (إن كان للمادة أكثر من معلم)</label><select class="form-select" id="staff" name="staff_id"><option value="">تلقائي</option><?php foreach ($teachers as $t): ?><option value="<?= (int)$t['id'] ?>"><?= e($t['full_name']) ?></option><?php endforeach; ?></select></div>
    <div class="mb-2"><label class="form-label" for="room">القاعة</label><select class="form-select" id="room" name="room_id"><option value="">بلا قاعة</option><?php foreach ($rooms as $r): ?><option value="<?= (int)$r['id'] ?>"><?= e($r['name']) ?></option><?php endforeach; ?></select></div>
    <button class="btn btn-primary w-100" type="submit" id="slotSubmit" disabled>إضافة</button><p class="small text-muted mt-2 mb-0">يرفض النظام تلقائيًا أي تعارض في المعلم أو القاعة أو الشعبة ويذكر السبب.</p></form></div></div>
<?php endif; ?>
