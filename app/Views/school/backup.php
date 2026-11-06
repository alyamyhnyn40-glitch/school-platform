<?php
$fmt = fn(int $b) => $b >= 1048576 ? number_format($b / 1048576, 1) . ' م.ب' : number_format($b / 1024, 0) . ' ك.ب';
$cb = ['none' => ['secondary', 'محلية فقط'], 'ok' => ['success', 'مرفوعة للسحابة'], 'failed' => ['danger', 'فشل الرفع']];
?>
<div class="page-head"><div><h1 class="page-title">النسخ الاحتياطي</h1><p class="page-sub">نسخة كاملة لبيانات مدرستك وملفاتها: نزّلها على جهازك أو احفظها تلقائيًا في تخزين سحابي خاص بك.</p></div></div>
<div class="row g-3">
  <div class="col-lg-5">
    <form class="card-s mb-3" method="post" action="<?= url('api/v1/school/backup') ?>" data-ajax data-reload novalidate><?= csrf_field() ?>
      <h2 class="h6"><i class="bi bi-download"></i> إنشاء نسخة الآن</h2>
      <div class="form-check form-switch mb-2"><input type="hidden" name="with_files" value="0"><input class="form-check-input" type="checkbox" role="switch" name="with_files" value="1" id="wf" checked><label class="form-check-label" for="wf">تضمين الملفات المرفوعة (صور، مرفقات، شعار)</label></div>
      <div class="mb-2"><label class="form-label" for="pw">كلمة مرور للأرشيف (اختياري — تشفير AES-256)</label><input class="form-control" id="pw" name="password" type="password" autocomplete="new-password" minlength="8" placeholder="8 أحرف فأكثر"><div class="form-text">إن نسيتها فلا يمكن فتح النسخة. النسخة تحوي بيانات حساسة فاحفظها بأمان.</div></div>
      <?php if ($cfg['cloud_enabled']): ?><div class="form-check mb-2"><input type="hidden" name="to_cloud" value="0"><input class="form-check-input" type="checkbox" name="to_cloud" value="1" id="tc" checked><label class="form-check-label" for="tc">رفعها أيضًا إلى التخزين السحابي</label></div><?php endif; ?>
      <button class="btn btn-primary w-100"><i class="bi bi-archive"></i> إنشاء نسخة</button>
    </form>
    <form class="card-s" method="post" action="<?= url('api/v1/school/backup/settings') ?>" data-ajax data-reload novalidate id="bkCfg"><?= csrf_field() ?>
      <h2 class="h6"><i class="bi bi-cloud"></i> التخزين السحابي والجدولة</h2>
      <div class="row g-2 mb-2"><div class="col-6"><label class="form-label" for="au">النسخ التلقائي</label><select class="form-select" id="au" name="auto"><?php foreach (['off' => 'متوقف', 'daily' => 'يوميًا', 'weekly' => 'أسبوعيًا'] as $k => $l): ?><option value="<?= $k ?>" <?= $cfg['auto'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
        <div class="col-6"><label class="form-label" for="kp">النسخ المحلية المحفوظة</label><input class="form-control" id="kp" name="keep" type="number" min="1" max="60" value="<?= (int)$cfg['keep'] ?>"></div></div>
      <div class="form-check mb-2"><input type="hidden" name="files" value="0"><input class="form-check-input" type="checkbox" name="files" value="1" id="af" <?= $cfg['files'] ? 'checked' : '' ?>><label class="form-check-label" for="af">تضمين الملفات في النسخ التلقائية</label></div>
      <div class="form-check form-switch mb-2"><input type="hidden" name="cloud_enabled" value="0"><input class="form-check-input" type="checkbox" role="switch" name="cloud_enabled" value="1" id="ce" <?= $cfg['cloud_enabled'] ? 'checked' : '' ?>><label class="form-check-label" for="ce">تفعيل الرفع إلى تخزين سحابي (متوافق مع S3)</label></div>
      <div id="cloudBox" <?= $cfg['cloud_enabled'] ? '' : 'hidden' ?>>
        <label class="form-label" for="cp"><i class="bi bi-magic"></i> مزود جاهز</label><select class="form-select mb-2" id="cp"><option value="">— مخصص —</option></select>
        <div class="row g-2 mb-2"><div class="col-12"><label class="form-label" for="ep">عنوان الخدمة (Endpoint)</label><input class="form-control" id="ep" name="endpoint" dir="ltr" value="<?= e($cfg['endpoint']) ?>" placeholder="https://s3.amazonaws.com"></div>
          <div class="col-6"><label class="form-label" for="bk">الحاوية (Bucket)</label><input class="form-control" id="bk" name="bucket" dir="ltr" value="<?= e($cfg['bucket']) ?>"></div>
          <div class="col-6"><label class="form-label" for="rg">المنطقة (Region)</label><input class="form-control" id="rg" name="region" dir="ltr" value="<?= e($cfg['region']) ?>"></div>
          <div class="col-6"><label class="form-label" for="ak">مفتاح الوصول</label><input class="form-control" id="ak" name="access_key" type="password" dir="ltr" autocomplete="new-password" placeholder="<?= $cfg['has_key'] ? '•••••• محفوظ' : '' ?>"></div>
          <div class="col-6"><label class="form-label" for="sk">المفتاح السري</label><input class="form-control" id="sk" name="secret_key" type="password" dir="ltr" autocomplete="new-password" placeholder="<?= $cfg['has_secret'] ? '•••••• محفوظ' : '' ?>"></div>
          <div class="col-12"><label class="form-label" for="px">مجلد داخل الحاوية</label><input class="form-control" id="px" name="prefix" dir="ltr" value="<?= e($cfg['prefix']) ?>"></div></div>
        <?php if ($cfg['has_key'] || $cfg['has_secret']): ?><div class="form-check mb-2"><input type="hidden" name="clear_keys" value="0"><input class="form-check-input" type="checkbox" name="clear_keys" value="1" id="ck"><label class="form-check-label small" for="ck">حذف المفاتيح المحفوظة</label></div><?php endif; ?>
        <div class="form-text mb-2" id="cpNote">المفاتيح تُشفَّر ولا تُعرض ولا تدخل في النسخ. أنشئ مفتاحًا بصلاحية الكتابة على هذه الحاوية فقط.</div>
      </div>
      <div class="d-flex gap-2"><button class="btn btn-primary">حفظ</button><?php if ($cfg['cloud_enabled']): ?><button type="button" class="btn btn-outline-primary" data-api="<?= url('api/v1/school/backup/test') ?>">اختبار الاتصال</button><?php endif; ?></div>
    </form>
  </div>
  <div class="col-lg-7">
    <div class="card-s"><h2 class="h6">النسخ المحفوظة</h2>
      <?php if (!$rows): ?><div class="empty-state"><i class="bi bi-archive"></i>لا توجد نسخ بعد. أنشئ أول نسخة.</div><?php else: ?>
      <div class="table-responsive"><table class="table align-middle"><thead><tr><th>التاريخ</th><th>الحجم</th><th>النوع</th><th>التخزين</th><th></th></tr></thead><tbody>
      <?php foreach ($rows as $b): [$cls, $lbl] = $cb[$b['cloud_status']]; ?>
        <tr><td><?= e(date('Y-m-d H:i', strtotime($b['created_at']))) ?><div class="small text-muted"><?= $b['kind'] === 'auto' ? 'تلقائية' : 'يدوية' ?><?= $b['encrypted'] ? ' · <i class="bi bi-lock-fill"></i> مشفّرة' : '' ?></div></td>
          <td><?= e($fmt((int)$b['size_bytes'])) ?><div class="small text-muted"><?= (int)$b['row_count'] ?> سجل</div></td>
          <td><?= $b['includes_files'] ? 'بيانات + ملفات' : 'بيانات فقط' ?></td>
          <td><span class="badge text-bg-<?= $cls ?>"><?= e($lbl) ?></span><?php if ($b['cloud_error']): ?><div class="small text-danger"><?= e($b['cloud_error']) ?></div><?php endif; ?></td>
          <td class="text-nowrap"><a class="btn btn-sm btn-outline-primary" href="<?= url('school/backup/' . (int)$b['id'] . '/download') ?>" title="تنزيل"><i class="bi bi-download"></i></a>
            <?php if ($cfg['cloud_enabled'] && $b['cloud_status'] !== 'ok'): ?><button class="btn btn-sm btn-outline-secondary" data-api="<?= url('api/v1/school/backup/' . (int)$b['id'] . '/upload') ?>" title="رفع للسحابة"><i class="bi bi-cloud-upload"></i></button><?php endif; ?>
            <button class="btn btn-sm btn-outline-danger" data-api="<?= url('api/v1/school/backup/' . (int)$b['id']) ?>" data-method="DELETE" data-confirm="حذف هذه النسخة من الخادم؟" title="حذف"><i class="bi bi-trash"></i></button></td></tr>
      <?php endforeach; ?></tbody></table></div><?php endif; ?></div>
    <div class="alert alert-warning small mt-3 mb-0"><strong>تنبيه:</strong> الحقول الحساسة (الهوية الوطنية، الملاحظات الصحية والإرشادية) محفوظة مشفّرة بمفتاح <code dir="ltr">APP_ENC_KEY</code> الخاص بالخادم؛ احتفظ بنسخة من ملف <code dir="ltr">.env</code> مع النسخ، وإلا تتعذّر قراءتها عند الاستعادة. الاستعادة تتم على قاعدة جديدة (تعليمات داخل الأرشيف في README.txt).</div>
  </div>
</div>
