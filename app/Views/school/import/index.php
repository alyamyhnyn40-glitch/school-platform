<div class="row g-3">
  <div class="col-lg-5">
    <form class="card-s" id="uploadForm" novalidate><?= csrf_field() ?>
    <ol class="wz-nav"><li class="active">اختر النوع</li><li>نزّل القالب واملأه</li><li>ارفع وراجع</li></ol>
    <div class="mb-3"><label class="form-label" for="kind">1) نوع البيانات</label><select class="form-select" id="kind" name="kind"><?php foreach ($kinds as $k => $l): ?><option value="<?= $k ?>" data-hint="<?= e($hints[$k] ?? '') ?>" data-tpl="<?= e(url("school/import/template/$k.xlsx")) ?>"><?= e($l) ?></option><?php endforeach; ?></select><div class="form-text" id="kindHint"></div></div>
    <div class="mb-3"><label class="form-label">2) القالب</label><div><a class="btn btn-outline-success" id="tplLink" href="#"><i class="bi bi-file-earmark-excel"></i> تنزيل قالب Excel بالقوائم المنسدلة</a></div>
      <div class="form-text">القالب يحوي قوائم منسدلة بأسماء الصفوف والشعب والمواد الموجودة في مدرستك لتقليل الأخطاء. الحقول متعددة القيم (مثل صفوف المادة) تُكتب مفصولة بـ «،».</div></div>
    <div class="mb-3"><label class="form-label" for="file">3) الملف (xlsx أو csv، حتى 5MB و3000 صف)</label><input class="form-control" type="file" id="file" name="file" accept=".xlsx,.csv" required></div>
    <button class="btn btn-primary w-100" type="submit"><i class="bi bi-upload"></i> رفع ومراجعة قبل الحفظ</button>
    <p class="small text-muted mt-2 mb-0"><i class="bi bi-shield-check"></i> لن يُحفظ شيء قبل أن تراجع المعاينة: كل صف تظهر حالته وسبب الخطأ برقم الصف، وتُستورد الصفوف السليمة فقط.</p>
  </form>
  <div class="card-s mt-3 small"><h3 class="h6">الترتيب الموصى به لمدرسة جديدة</h3><ol class="mb-0 ps-3"><?php foreach ($allKinds as $k => $l): ?><li><?= e($l) ?> — <span class="text-muted"><?= e($hints[$k] ?? '') ?></span></li><?php endforeach; ?></ol></div></div>
  <div class="col-lg-7"><div class="card-s"><h2 class="h6">آخر العمليات</h2>
    <?php if (!$jobs): ?><div class="empty-state"><i class="bi bi-file-earmark-arrow-up"></i>لا توجد عمليات استيراد بعد.</div><?php else: ?>
    <div class="table-responsive"><table class="table table-sm align-middle table-cards"><thead><tr><th>الملف</th><th>النوع</th><th>الصفوف</th><th>الأخطاء</th><th>الحالة</th><th></th></tr></thead><tbody>
    <?php foreach ($jobs as $j): ?><tr><td data-label="الملف"><?= e($j['original_name']) ?><br><small class="text-muted" dir="ltr"><?= e($j['created_at']) ?></small></td><td data-label="النوع"><?= e($kinds[$j['kind']] ?? $j['kind']) ?></td><td data-label="الصفوف"><?= (int)$j['total_rows'] ?></td><td data-label="الأخطاء"><?= (int)$j['error_rows'] ?></td>
      <td data-label="الحالة"><span class="badge text-bg-<?= $j['status'] === 'committed' ? 'success' : 'warning' ?>"><?= $j['status'] === 'committed' ? 'معتمد' : 'بانتظار الاعتماد' ?></span></td><td><a class="btn btn-sm btn-outline-primary" href="<?= url('school/import/' . (int)$j['id']) ?>">فتح</a></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div></div>
</div>
