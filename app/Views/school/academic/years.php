<?php $stl = ['draft' => ['secondary', 'مسودة'], 'active' => ['success', 'فعّالة'], 'closed' => ['dark', 'مغلقة']]; $active = null; $drafts = []; foreach ($years as $y) { if ($y['status'] === 'active') $active = $y; if ($y['status'] === 'draft') $drafts[] = $y; } ?>
<div class="row g-3">
  <div class="col-lg-8">
    <?php if (!$years): ?><div class="card-s empty-state"><i class="bi bi-calendar3"></i>ابدأ بإضافة السنة الدراسية الأولى ثم فعّلها.</div><?php endif; ?>
    <?php foreach ($years as $y): $b = $stl[$y['status']]; ?>
    <div class="card-s mb-3">
      <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
        <h2 class="h6 mb-0"><?= e($y['name']) ?></h2><span class="badge text-bg-<?= $b[0] ?>"><?= $b[1] ?></span>
        <small class="text-muted" dir="ltr"><?= e($y['starts_on']) ?> → <?= e($y['ends_on']) ?></small>
        <span class="ms-auto d-flex gap-2">
          <?php if ($y['status'] === 'draft' && can('academic.manage')): ?><button class="btn btn-sm btn-success" data-api="<?= url("api/v1/school/years/{$y['id']}/activate") ?>" data-confirm="تفعيل هذه السنة؟">تفعيل</button><?php endif; ?>
          <?php if ($y['status'] === 'closed' && can('academic.promote')): ?><button class="btn btn-sm btn-outline-secondary" data-api="<?= url("api/v1/school/years/{$y['id']}/freeze") ?>" data-confirm="إعادة تجميد السجل الأكاديمي لهذه السنة من الدرجات الحالية؟" title="تحديث لقطة السجل الأكاديمي"><i class="bi bi-snow"></i> تجميد السجل</button><?php endif; ?>
          <?php if ($y['status'] === 'active' && can('academic.promote')): ?><button class="btn btn-sm btn-outline-dark" data-api="<?= url("api/v1/school/years/{$y['id']}/close") ?>" data-confirm="إغلاق السنة يجعلها للقراءة فقط. متابعة؟">إغلاق</button><?php endif; ?>
        </span>
      </div>
      <?php if ($y['terms']): ?><ul class="list-group list-group-flush mb-2">
        <?php foreach ($y['terms'] as $t): ?><li class="list-group-item bg-transparent d-flex justify-content-between"><span><?= e($t['name']) ?><?= $t['is_final'] ? ' <span class="badge text-bg-info">ختامي</span>' : '' ?> <small class="text-muted" dir="ltr"><?= e($t['starts_on']) ?> → <?= e($t['ends_on']) ?></small></span>
          <?php if ($y['status'] !== 'closed' && can('academic.manage')): ?><button class="btn btn-sm btn-outline-danger" data-api="<?= url("api/v1/school/terms/{$t['id']}") ?>" data-method="DELETE" data-confirm="حذف الفصل؟"><i class="bi bi-trash"></i></button><?php endif; ?></li><?php endforeach; ?></ul>
      <?php else: ?><p class="text-muted small">لا توجد فصول.</p><?php endif; ?>
      <?php if ($y['status'] !== 'closed' && can('academic.manage')): ?>
      <details><summary class="text-primary" style="cursor:pointer">إضافة فصل</summary>
        <form class="row g-2 mt-1" method="post" action="<?= url("api/v1/school/years/{$y['id']}/terms") ?>" data-ajax data-reload novalidate><?= csrf_field() ?>
          <div class="col-md-4"><input class="form-control" name="name" placeholder="اسم الفصل" required aria-label="اسم الفصل"></div>
          <div class="col-md-2"><input class="form-control" name="term_no" type="number" min="1" placeholder="الرقم" required aria-label="رقم الفصل"></div>
          <div class="col-md-3"><input class="form-control" name="starts_on" type="date" required aria-label="بداية"></div>
          <div class="col-md-3"><input class="form-control" name="ends_on" type="date" required aria-label="نهاية"></div>
          <div class="col-12 d-flex align-items-center gap-3"><div class="form-check"><input type="hidden" name="is_final" value="0"><input class="form-check-input" type="checkbox" name="is_final" value="1" id="f<?= (int)$y['id'] ?>"><label class="form-check-label" for="f<?= (int)$y['id'] ?>">فصل ختامي</label></div><button class="btn btn-primary" type="submit">إضافة</button></div>
        </form></details>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="col-lg-4 d-flex flex-column gap-3">
    <?php if (can('academic.manage')): ?>
    <a class="card-s text-decoration-none d-block" href="<?= url('school/years/new') ?>"><h3 class="h6 text-primary mb-1"><i class="bi bi-magic"></i> معالج السنة الجديدة</h3><p class="small text-muted mb-0">أنشئ السنة وانسخ إليها الفصول والشعب والمواد والخطة بنقرة واحدة.</p></a>
    <details class="card-s"><summary class="text-primary" style="cursor:pointer">إضافة سنة فارغة يدويًا</summary>
    <form class="mt-2" method="post" action="<?= url('api/v1/school/years') ?>" data-ajax data-reload novalidate><?= csrf_field() ?>
      <div class="mb-2"><label class="form-label" for="yn">الاسم (مثال 2026/2027)</label><input class="form-control" id="yn" name="name" required></div>
      <div class="row g-2"><div class="col-6"><label class="form-label" for="ys">البداية</label><input class="form-control" id="ys" name="starts_on" type="date" required></div><div class="col-6"><label class="form-label" for="ye">النهاية</label><input class="form-control" id="ye" name="ends_on" type="date" required></div></div>
      <button class="btn btn-primary mt-3" type="submit">إضافة</button>
    </form></details>
    <?php endif; ?>
    <?php if ($active && $drafts && can('academic.promote')): ?>
    <div class="card-s" id="promoBox" data-url-preview="<?= url('api/v1/school/promotion/preview') ?>" data-url-run="<?= url('api/v1/school/promotion/run') ?>" data-clone="<?= url('api/v1/school/years/') ?>">
      <h3 class="h6">الترفيع إلى سنة جديدة</h3>
      <p class="small text-muted">1) انسخ هيكل الشعب إلى السنة الجديدة. 2) عاين الخطة وعدّل القرارات. 3) نفّذ: تُغلق السنة الحالية وتُفعَّل الجديدة.</p>
      <input type="hidden" id="promoFrom" value="<?= (int)$active['id'] ?>">
      <label class="form-label" for="promoTo">إلى سنة</label>
      <select class="form-select mb-2" id="promoTo"><?php foreach ($drafts as $d): ?><option value="<?= (int)$d['id'] ?>"><?= e($d['name']) ?></option><?php endforeach; ?></select>
      <div class="d-grid gap-2"><button class="btn btn-outline-primary" id="promoClone" type="button">نسخ هيكل الشعب</button><button class="btn btn-primary" id="promoPreview" type="button">معاينة الترفيع</button></div>
    </div>
    <?php endif; ?>
  </div>
</div>
<div class="card-s mt-3 d-none" id="promoResult"></div>
