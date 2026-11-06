<!doctype html>
<html lang="ar" dir="rtl" data-theme="auto">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="app-base" content="<?= e(rtrim((string)\App\Core\Env::get('APP_BASE_PATH', ''), '/')) ?>">
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
  <title><?= e(\App\Services\Brand::name() . ' — ' . \App\Services\Brand::get('tagline')) ?></title>
  <meta name="description" content="<?= e(\App\Services\Brand::name() . ': ' . \App\Services\Brand::get('tagline')) ?>">
  <link rel="manifest" href="<?= url('manifest.webmanifest') ?>"><meta name="theme-color" content="<?= e(\App\Services\Brand::color()) ?>">
  <link rel="icon" href="<?= \App\Services\Brand::hasLogo() ? url('brand/logo') : asset('img/icon-192.png') ?>">
  <link rel="stylesheet" href="<?= asset('vendor/bootstrap/bootstrap.rtl.min.css') ?>">
  <link rel="stylesheet" href="<?= asset('vendor/icons/bootstrap-icons.min.css') ?>">
  <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
  <link rel="stylesheet" href="<?= asset('css/landing.css') ?>">
  <style><?= \App\Services\Brand::css() ?></style>
  <script src="<?= asset('js/theme-init.js') ?>"></script>
</head>
<body class="landing-body">
  <?= $content ?>
  <div class="toast-container position-fixed bottom-0 start-0 p-3" id="toasts" aria-live="polite"></div>
  <script src="<?= asset('vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
  <script src="<?= asset('js/app.js') ?>"></script>
  <script type="application/json" id="geoMap"><?= json_encode(\App\Core\Lookup::cityMap(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
  <script src="<?= asset('js/lookups.js') ?>"></script>
  <?php foreach (($scripts ?? []) as $s): ?><script src="<?= asset('js/' . $s) ?>"></script><?php endforeach; ?>
</body>
</html>
