<!doctype html>
<html lang="ar" dir="rtl" data-theme="auto">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="app-base" content="<?= e(rtrim((string)\App\Core\Env::get('APP_BASE_PATH', ''), '/')) ?>">
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
  <title><?= e(($title ?? '') !== '' ? $title . ' — ' : '') . __t('app.name') ?></title>
  <link rel="manifest" href="<?= url('manifest.webmanifest') ?>"><meta name="theme-color" content="#0f766e">
  <link rel="icon" href="<?= \App\Services\Brand::hasLogo() ? url('brand/logo') : asset('img/icon-192.png') ?>">
  <link rel="stylesheet" href="<?= asset('vendor/bootstrap/bootstrap.rtl.min.css') ?>">
  <link rel="stylesheet" href="<?= asset('vendor/icons/bootstrap-icons.min.css') ?>">
  <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
  <?php foreach (($styles ?? []) as $c): ?><link rel="stylesheet" href="<?= asset('css/' . $c) ?>"><?php endforeach; ?>
  <style><?= \App\Services\Brand::css() ?></style>
  <script src="<?= asset('js/theme-init.js') ?>"></script>
</head>
<body class="<?= e($bodyClass ?? 'bare-body') ?>">
  <main class="bare-wrap<?= !empty($bodyClass) && str_contains($bodyClass, 'login-body') ? ' login-shell' : '' ?>"><?= $content ?></main>
  <div class="toast-container position-fixed bottom-0 start-0 p-3" id="toasts" aria-live="polite"></div>
  <script src="<?= asset('vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
  <script src="<?= asset('js/app.js') ?>"></script>
  <?php foreach (($scripts ?? []) as $s): ?><script src="<?= asset('js/' . $s) ?>"></script><?php endforeach; ?>
</body>
</html>
