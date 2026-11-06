<?php $labels = ['draft' => 'مسودة', 'approved' => 'معتمد', 'paid' => 'مصروف']; ?>
<div class="row g-3"><div class="col-lg-4"><form class="card-s" method="post" action="<?= url('api/v1/school/payroll') ?>" data-ajax data-redirect novalidate><?= csrf_field() ?><h2 class="h6">كشف شهر جديد</h2>
 <label class="form-label" for="pm">الشهر</label><input class="form-control mb-2" id="pm" name="period" type="month" value="<?= e(date('Y-m', strtotime('-1 month'))) ?>" required>
 <p class="small text-muted">يُحتسب من العقود السارية في الشهر، مع تناسب الأيام لمن بدأ أو انتهى عقده خلال الشهر.</p><button class="btn btn-primary w-100">إنشاء</button>
 <a class="btn btn-outline-secondary w-100 mt-2" href="<?= url('school/payroll/contracts') ?>">العقود</a></form></div>
<div class="col-lg-8"><div class="card-s"><?php if (!$runs): ?><div class="empty-state">لا توجد كشوف.</div><?php endif; ?>
<?php foreach ($runs as $r): ?><div class="d-flex justify-content-between border-bottom py-2"><a href="<?= url('school/payroll/' . (int)$r['id']) ?>"><?= e($r['period']) ?></a><span><span class="badge text-bg-<?= $r['status'] === 'paid' ? 'success' : ($r['status'] === 'approved' ? 'info' : 'secondary') ?>"><?= e($labels[$r['status']]) ?></span> <small class="text-muted"><?= (int)$r['n'] ?> موظف</small></span></div><?php endforeach; ?></div></div></div>
