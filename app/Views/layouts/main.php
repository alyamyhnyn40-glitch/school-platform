<?php use App\Core\Auth; $u = Auth::user(); ?>
<!doctype html>
<html lang="ar" dir="rtl" data-theme="<?= e($u['theme'] ?? 'auto') ?>">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="app-base" content="<?= e(rtrim((string)\App\Core\Env::get('APP_BASE_PATH', ''), '/')) ?>">
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
  <title><?= e((($title ?? '') !== '' ? $title . ' — ' : '') . (\App\Services\Brand::schoolName() ?: __t('app.name'))) ?></title>
  <link rel="manifest" href="<?= url('manifest.webmanifest') ?>"><meta name="theme-color" content="#0f766e">
  <link rel="icon" href="<?= \App\Services\Brand::hasLogo() ? url('brand/logo') : asset('img/icon-192.png') ?>">
  <link rel="stylesheet" href="<?= asset('vendor/bootstrap/bootstrap.rtl.min.css') ?>">
  <link rel="stylesheet" href="<?= asset('vendor/icons/bootstrap-icons.min.css') ?>">
  <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
  <?php if ($__ast = (\App\Core\Auth::check() && \App\Services\AssistantService::enabled())): ?><link rel="stylesheet" href="<?= asset('css/assistant.css') ?>"><?php endif; ?>
  <?php foreach (($styles ?? []) as $c): ?><link rel="stylesheet" href="<?= asset('css/' . $c) ?>"><?php endforeach; ?>
  <style><?= \App\Services\Brand::css() ?></style>
  <script src="<?= asset('js/theme-init.js') ?>"></script>
</head>
<body>
  <a class="visually-hidden-focusable skip-link" href="#main"><?= e(__t('nav.menu')) ?></a>
  <div class="app-shell">
    <aside class="sidebar" id="sidebar" aria-label="<?= e(__t('nav.menu')) ?>">
      <?php $__sn = \App\Services\Brand::schoolName(); $__sl = \App\Services\Brand::schoolLogoPath() ? url('school/logo') : null; $__pl = \App\Services\Brand::hasLogo() ? url('brand/logo') : null; ?>
      <a class="sidebar-brand" href="<?= url(\App\Core\Auth::isPlatform() && !\App\Core\Session::get('support_mode') ? 'platform' : 'dashboard') ?>" aria-label="<?= e($__sn ?? \App\Services\Brand::name()) ?>">
        <?php if ($__sn): ?>
          <?php if ($__sl): ?><img class="school-mark" src="<?= $__sl ?>" alt=""><?php else: ?><span class="school-mark school-mark-ph"><i class="bi bi-building"></i></span><?php endif; ?>
          <span class="brand-text"><strong><?= e($__sn) ?></strong><small>على منصة <?= e(\App\Services\Brand::name()) ?></small></span>
        <?php else: ?>
          <?php if ($__pl): ?><img class="school-mark" src="<?= $__pl ?>" alt=""><?php else: ?><span class="school-mark school-mark-ph"><i class="bi bi-mortarboard-fill"></i></span><?php endif; ?>
          <span class="brand-text"><strong><?= e(\App\Services\Brand::name()) ?></strong><small>لوحة المشغّل</small></span>
        <?php endif; ?>
      </a>
      <?= \App\Core\View::partial('sidebar') ?>
    </aside>
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
    <div class="app-main">
      <header class="topbar">
        <button class="btn btn-icon d-lg-none" id="sidebarToggle" aria-label="<?= e(__t('nav.menu')) ?>"><i class="bi bi-list"></i></button>
        <?php $__home = \App\Core\Auth::isPlatform() && !\App\Core\Session::get('support_mode') ? '/platform' : (in_array(\App\Core\Auth::type(), ['guardian', 'student'], true) ? '/portal' : '/dashboard'); $__onHome = (new \App\Core\Request())->path() === $__home; ?>
        <nav class="topbar-crumbs" aria-label="مسار الصفحة">
          <?php if (!$__onHome): ?><button type="button" class="btn btn-icon btn-back" id="btnBack" aria-label="رجوع"><i class="bi bi-arrow-right"></i></button><a class="crumb-home" href="<?= url($__home) ?>"><i class="bi bi-house-door"></i><span class="d-none d-sm-inline"> الرئيسية</span></a><i class="bi bi-chevron-left crumb-sep" aria-hidden="true"></i><?php endif; ?>
          <span class="topbar-title"><?= e($title ?? '') ?></span>
        </nav>
        <?php if ($__sn): ?><a class="ctx-chip d-none d-md-flex" href="<?= url($__home) ?>" title="مدرستك الحالية"><?php if ($__sl): ?><img src="<?= $__sl ?>" alt=""><?php else: ?><i class="bi bi-building"></i><?php endif; ?><span><strong><?= e($__sn) ?></strong><?php if ($__yr = \App\Services\Brand::activeYear()): ?><small>العام الدراسي <?= e($__yr) ?></small><?php endif; ?></span></a><?php endif; ?>
        <div class="ms-auto d-flex align-items-center gap-2">
          <?php if (($u['user_type'] ?? '') !== 'platform' || \App\Core\Session::get('support_mode')): ?>
          <a class="btn btn-icon position-relative" href="<?= url('messages') ?>" aria-label="الرسائل"><i class="bi bi-chat-dots"></i><span class="badge text-bg-danger position-absolute top-0 start-0 d-none" id="msgBadge" style="font-size:.6rem">0</span></a>
          <div class="dropdown"><button class="btn btn-icon position-relative" id="notifBtn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="الإشعارات"><i class="bi bi-bell"></i><span class="badge text-bg-danger position-absolute top-0 start-0 d-none" id="notifBadge" style="font-size:.6rem">0</span></button><div class="dropdown-menu dropdown-menu-end notif-menu p-0" id="notifMenu"></div></div>
          <?php endif; ?>
          <?php if (($u['user_type'] ?? '') === 'guardian' && count($gs = \App\Services\AuthService::guardianSchools((int)($u['id'] ?? 0))) > 1): $curSchool = \App\Core\Tenant::id(); ?>
          <div class="dropdown"><button class="btn btn-icon-text dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" aria-label="تبديل المدرسة"><i class="bi bi-building"></i><span class="d-none d-md-inline"><?php foreach ($gs as $g) { if ((int)$g['id'] === (int)$curSchool) { echo e($g['name']); } } ?></span></button>
            <ul class="dropdown-menu dropdown-menu-end"><li><h6 class="dropdown-header">مدارس أبنائك</h6></li>
            <?php foreach ($gs as $g): ?><li><button class="dropdown-item<?= (int)$g['id'] === (int)$curSchool ? ' active' : '' ?>" type="button" data-api="<?= url('api/v1/portal/switch-school') ?>" data-body='<?= e(json_encode(['school_id' => (int)$g['id']])) ?>'><?= e($g['name']) ?></button></li><?php endforeach; ?></ul></div>
          <?php endif; ?>
          <button class="btn btn-icon" id="themeToggle" aria-label="الوضع الليلي"><i class="bi bi-moon-stars"></i></button>
          <div class="dropdown">
            <button class="btn user-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" aria-label="قائمة حسابي"><span class="avatar" aria-hidden="true"><?= e(mb_substr(trim((string)($u['full_name'] ?? '')), 0, 1)) ?></span><span class="d-none d-md-flex flex-column text-start lh-sm"><strong><?= e($u['full_name'] ?? '') ?></strong><small class="text-muted"><?= e(\App\Services\ProfileService::TYPE_LABELS[$u['user_type'] ?? ''] ?? '') ?></small></span></button>
            <ul class="dropdown-menu dropdown-menu-end user-menu">
              <li class="user-menu-head"><strong><?= e($u['full_name'] ?? '') ?></strong><?php if ($__sn): ?><small><?= e($__sn) ?></small><?php endif; ?></li>
              <li><a class="dropdown-item" href="<?= url('profile') ?>"><i class="bi bi-person-vcard"></i> ملفي الشخصي</a></li>
              <?php if (\App\Core\Auth::can('school.settings') && $__sn): ?><li><a class="dropdown-item" href="<?= url('school/identity') ?>"><i class="bi bi-building-gear"></i> هوية المدرسة</a></li><?php endif; ?>
              <li><a class="dropdown-item" href="<?= url('password/change') ?>"><i class="bi bi-key"></i> <?= e(__t('auth.change_title')) ?></a></li>
              <li><hr class="dropdown-divider"></li>
              <li><form method="post" action="<?= url('logout') ?>"><?= csrf_field() ?><button class="dropdown-item text-danger" type="submit"><i class="bi bi-box-arrow-left"></i> <?= e(__t('nav.logout')) ?></button></form></li>
            </ul>
          </div>
        </div>
      </header>
      <?php if ($sm = \App\Core\Session::get('support_mode')): ?>
      <div class="support-banner"><i class="bi bi-life-preserver"></i> وضع الدعم الفني: <strong><?= e($sm['school_name']) ?></strong> — كل إجراءاتك مسجّلة.
        <form method="post" action="<?= url('support/exit') ?>" class="d-inline"><?= csrf_field() ?><button class="btn btn-sm btn-light" type="submit">إنهاء الدعم</button></form></div>
      <?php endif; ?>
      <main id="main" class="content">
        <?php foreach (['success' => 'success', 'error' => 'danger', 'info' => 'info'] as $k => $cls): if ($m = \App\Core\Session::getFlash("msg_$k", null)): ?>
          <div class="alert alert-<?= $cls ?> alert-dismissible" role="alert"><?= e($m) ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button></div>
        <?php endif; endforeach; ?>
        <?= $content ?>
      </main>
    </div>
  </div>
  <?= \App\Core\View::partial('bottomnav', ['home' => $__home]) ?>
  <?php if (!empty($__ast)) { echo \App\Core\View::partial('assistant'); } ?>
  <div class="toast-container position-fixed bottom-0 start-0 p-3" id="toasts" aria-live="polite"></div>
  <script src="<?= asset('vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
  <script src="<?= asset('js/app.js') ?>"></script>
  <script type="application/json" id="geoMap"><?= json_encode(\App\Core\Lookup::cityMap(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
  <script src="<?= asset('js/lookups.js') ?>"></script>
  <?php if (($u['user_type'] ?? '') !== 'platform' || \App\Core\Session::get('support_mode')): ?><script src="<?= asset('js/notifications.js') ?>"></script><?php endif; ?>
  <?php if (!empty($__ast)): ?><script src="<?= asset('js/assistant.js') ?>"></script><?php endif; ?>
  <?php foreach (($scripts ?? []) as $s): ?><script src="<?= asset('js/' . $s) ?>"></script><?php endforeach; ?>
</body>
</html>
