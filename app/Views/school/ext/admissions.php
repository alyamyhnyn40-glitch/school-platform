<?php use App\Services\AdmissionService as A; ?>
<div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
  <?php foreach (A::STATUSES as $k => $lab): ?><a class="badge text-bg-<?= $status === $k ? 'primary' : 'secondary' ?> text-decoration-none fs-6" href="<?= url('school/admissions?status=' . $k) ?>"><?= e($lab) ?> <?= (int)$stats[$k] ?></a><?php endforeach; ?>
  <a class="btn btn-primary btn-sm ms-auto" href="<?= url('school/admissions/new') ?>"><i class="bi bi-plus-lg"></i> طلب جديد</a>
</div>
<form class="card-s mb-3 d-flex flex-wrap gap-2" method="get"><input class="form-control w-auto flex-grow-1" name="q" value="<?= e($q) ?>" placeholder="بحث بالاسم أو الهاتف" aria-label="بحث"><input type="hidden" name="status" value="<?= e($status) ?>"><button class="btn btn-outline-primary">بحث</button></form>
<div class="card-s"><?php if (!$res['rows']): ?><div class="empty-state">لا توجد طلبات.</div><?php else: ?>
<div class="table-responsive"><table class="table table-sm align-middle table-cards mb-0"><thead><tr><th>المتقدّم</th><th>الصف</th><th>ولي الأمر</th><th>الحالة</th><th>التاريخ</th></tr></thead><tbody>
<?php foreach ($res['rows'] as $a): ?><tr><td data-label="المتقدّم"><a href="<?= url('school/admissions/' . (int)$a['id']) ?>"><?= e($a['applicant_name']) ?></a></td><td data-label="الصف"><?= e($a['grade_name'] ?? '—') ?></td><td data-label="ولي الأمر"><?= e($a['guardian_name'] ?? '—') ?> <small class="text-muted" dir="ltr"><?= e($a['guardian_phone'] ?? '') ?></small></td><td data-label="الحالة"><span class="badge text-bg-info"><?= e(A::STATUSES[$a['status']] ?? $a['status']) ?></span></td><td data-label="التاريخ"><?= e(substr((string)$a['created_at'], 0, 10)) ?></td></tr><?php endforeach; ?>
</tbody></table></div>
<?php if ($res['meta']['pages'] > 1): ?><div class="d-flex gap-2 mt-2"><?php for ($i = 1; $i <= $res['meta']['pages']; $i++): ?><a class="btn btn-sm btn-outline-secondary" href="<?= url('school/admissions?page=' . $i . '&status=' . urlencode($status) . '&q=' . urlencode($q)) ?>"><?= $i ?></a><?php endfor; ?></div><?php endif; ?>
<?php endif; ?></div>
