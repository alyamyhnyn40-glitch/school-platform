<?php
$initial = mb_substr(trim($user['full_name']), 0, 1);
$hasLogo = $school && \App\Services\Brand::schoolLogoPath();
$fmt = fn(?string $d) => $d ? date('Y-m-d H:i', strtotime($d)) : '—';
?>
<div class="page-head"><div><h1 class="page-title">ملفي الشخصي</h1><p class="page-sub">بياناتك ومدرستك وإعدادات حسابك في مكان واحد.</p></div></div>
<div class="row g-3">
  <div class="col-lg-4">
    <div class="card-s profile-card text-center">
      <div class="avatar avatar-xl mx-auto" aria-hidden="true"><?= e($initial) ?></div>
      <h2 class="h5 mt-3 mb-1"><?= e($user['full_name']) ?></h2>
      <span class="badge text-bg-primary"><?= e($type_label) ?></span>
      <?php foreach ($roles as $r): ?><span class="badge text-bg-light border ms-1"><?= e($r) ?></span><?php endforeach; ?>
      <?php if ($school): ?>
      <hr><div class="d-flex align-items-center gap-3 text-start">
        <?php if ($hasLogo): ?><img class="school-mark" src="<?= url('school/logo') ?>" alt=""><?php else: ?><span class="school-mark school-mark-ph"><i class="bi bi-building"></i></span><?php endif; ?>
        <div><div class="small text-muted">مدرستي</div><div class="fw-bold"><?= e($school['name']) ?></div><?php if (!empty($school['city'])): ?><div class="small text-muted"><?= e($school['city']) ?></div><?php endif; ?></div></div>
      <?php endif; ?>
      <hr><div class="d-grid gap-2"><a class="btn btn-outline-primary" href="<?= url('password/change') ?>"><i class="bi bi-key"></i> تغيير كلمة المرور</a>
        <form method="post" action="<?= url('logout') ?>"><?= csrf_field() ?><button class="btn btn-outline-danger w-100"><i class="bi bi-box-arrow-left"></i> تسجيل الخروج</button></form></div>
    </div>
  </div>
  <div class="col-lg-8">
    <form class="card-s mb-3" method="post" action="<?= url('api/v1/profile') ?>" data-ajax data-reload novalidate><?= csrf_field() ?>
      <h2 class="h6 mb-3">بياناتي</h2>
      <div class="row g-2">
        <div class="col-md-6"><label class="form-label" for="fn">الاسم الكامل</label><input class="form-control" id="fn" name="full_name" required value="<?= e($user['full_name']) ?>"></div>
        <div class="col-md-6"><label class="form-label" for="th">مظهر الواجهة</label><select class="form-select" id="th" name="theme"><?php foreach (['auto' => 'تلقائي (حسب جهازك)', 'light' => 'فاتح', 'dark' => 'داكن'] as $k => $v): ?><option value="<?= $k ?>" <?= $user['theme'] === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></div>
        <div class="col-md-6"><label class="form-label">البريد الإلكتروني</label><input class="form-control" dir="ltr" readonly value="<?= e($user['email'] ?? '—') ?>"></div>
        <div class="col-md-6"><label class="form-label">الهاتف</label><input class="form-control" dir="ltr" readonly value="<?= e($user['phone'] ?? '—') ?>"></div>
        <?php if ($user['username']): ?><div class="col-md-6"><label class="form-label">اسم المستخدم</label><input class="form-control" dir="ltr" readonly value="<?= e($user['username']) ?>"></div><?php endif; ?>
      </div>
      <div class="form-text mt-2">البريد والهاتف واسم المستخدم هي وسائل دخولك؛ لتغييرها اطلب ذلك من إدارة المدرسة.</div>
      <button class="btn btn-primary mt-3">حفظ التغييرات</button>
    </form>
    <?php if (!empty($extra['staff'])): ?><div class="card-s mb-3"><h2 class="h6">بيانات وظيفية</h2><dl class="row mb-0"><dt class="col-sm-4">المسمى الوظيفي</dt><dd class="col-sm-8"><?= e($extra['staff']['job_title'] ?? '—') ?></dd><dt class="col-sm-4">الرقم الوظيفي</dt><dd class="col-sm-8"><?= e($extra['staff']['employee_no'] ?? '—') ?></dd></dl></div><?php endif; ?>
    <?php if (!empty($extra['student'])): $s = $extra['student']; ?><div class="card-s mb-3"><h2 class="h6">بيانات دراسية</h2><dl class="row mb-0"><dt class="col-sm-4">رقم القيد</dt><dd class="col-sm-8"><?= e($s['student_no'] ?? '—') ?></dd><dt class="col-sm-4">الصف</dt><dd class="col-sm-8"><?= e($s['grade'] ?? '—') ?></dd><dt class="col-sm-4">الشعبة</dt><dd class="col-sm-8"><?= e($s['section'] ?? '—') ?></dd></dl></div><?php endif; ?>
    <?php if (!empty($extra['schools'])): ?><div class="card-s mb-3"><h2 class="h6">مدارس أبنائي</h2><ul class="list-unstyled mb-0"><?php foreach ($extra['schools'] as $g): ?><li class="py-1"><i class="bi bi-building text-muted"></i> <?= e($g['name']) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <div class="card-s"><h2 class="h6">معلومات الحساب</h2><dl class="row mb-0 small"><dt class="col-sm-4">آخر دخول</dt><dd class="col-sm-8"><?= e($fmt($user['last_login_at'])) ?></dd><dt class="col-sm-4">تاريخ إنشاء الحساب</dt><dd class="col-sm-8"><?= e($fmt($user['created_at'])) ?></dd></dl></div>
  </div>
</div>
