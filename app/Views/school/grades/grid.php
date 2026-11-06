<?php $a = $assessment; $st = ['open' => ['secondary', 'مفتوح'], 'approved' => ['success', 'معتمد'], 'locked' => ['dark', 'مقفل']][$a['status']]; ?>
<div class="d-flex flex-wrap gap-2 align-items-center mb-3">
  <h2 class="h5 mb-0"><?= e($a['title']) ?></h2><span class="badge text-bg-<?= $st[0] ?>"><?= $st[1] ?></span><span class="text-muted"><?= e($a['subject_name']) ?> — <?= e($a['section_name']) ?> — العظمى <?= (float)$a['max_score'] ?></span>
  <span class="badge text-bg-warning d-none" id="pendingBadge"></span>
  <span class="ms-auto d-flex gap-2">
    <?php if ($a['status'] === 'open' && can('grades.approve')): ?><button class="btn btn-success" data-api="<?= url('api/v1/school/assessments/' . (int)$a['id'] . '/approve') ?>" data-confirm="اعتماد الدرجات؟ لن يمكن تعديلها بعدها دون إعادة فتح مُسجَّلة.">اعتماد</button><?php endif; ?>
    <?php if ($a['status'] === 'approved' && can('grades.approve')): ?><button class="btn btn-outline-warning" data-api="<?= url('api/v1/school/assessments/' . (int)$a['id'] . '/reopen') ?>" data-prompt="سبب إعادة الفتح (يُسجَّل في التدقيق):">إعادة فتح</button><?php endif; ?>
    <a class="btn btn-outline-secondary" href="<?= url('school/grades?term=' . (int)$a['term_id'] . '&ss=' . (int)$a['section_subject_id']) ?>">رجوع</a></span></div>
<div id="failedOps"></div>
<?php if (!$editable): ?><div class="alert alert-info">هذا التقييم <?= $a['status'] === 'open' ? 'مقفل الفصل' : 'معتمد' ?> ولا يمكن تعديله.</div><?php endif; ?>
<div class="card-s">
  <div class="small text-muted mb-2" id="gradeStats"></div>
  <div class="table-responsive"><table class="table align-middle grade-table" id="gradeTable" data-assessment="<?= (int)$a['id'] ?>" data-max="<?= (float)$a['max_score'] ?>" data-editable="<?= $editable ? '1' : '0' ?>">
    <thead><tr><th>#</th><th>الطالب</th><th style="min-width:110px">الدرجة / <?= (float)$a['max_score'] ?></th><th>غائب</th><th class="d-none d-sm-table-cell">ملاحظة</th><th>الحالة</th></tr></thead><tbody>
    <?php foreach ($rows as $i => $r): ?><tr data-enr="<?= (int)$r['enrollment_id'] ?>"><td><?= $i + 1 ?></td><td><?= e($r['full_name']) ?></td>
      <td><input class="form-control sc" type="number" step="0.01" min="0" max="<?= (float)$a['max_score'] ?>" inputmode="decimal" value="<?= $r['score'] === null ? '' : (float)$r['score'] ?>" aria-label="درجة <?= e($r['full_name']) ?>" <?= !$editable || $r['is_absent'] ? 'disabled' : '' ?>></td>
      <td><input class="form-check-input ab" type="checkbox" aria-label="غائب" <?= $r['is_absent'] ? 'checked' : '' ?> <?= $editable ? '' : 'disabled' ?>></td>
      <td class="d-none d-sm-table-cell"><input class="form-control nt" maxlength="255" value="<?= e($r['note']) ?>" aria-label="ملاحظة" <?= $editable ? '' : 'disabled' ?>></td><td><span class="st small text-muted"></span></td></tr><?php endforeach; ?></tbody></table></div>
  <?php if (!$rows): ?><div class="empty-state">لا يوجد طلاب في الشعبة.</div><?php endif; ?>
  <?php if ($editable): ?><p class="small text-muted mb-0">يُحفظ تلقائيًا أثناء الكتابة. Enter أو ↑↓ للتنقل. الدرجة الفارغة = لم تُدخل بعد، «غائب» = صفر محسوب.</p><?php endif; ?>
</div>
