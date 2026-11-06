<!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? '') ?></title>
  <link rel="stylesheet" href="<?= asset('vendor/bootstrap/bootstrap.rtl.min.css') ?>">
  <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
  <style><?= \App\Services\Brand::css() ?></style>
  <link rel="stylesheet" href="<?= asset('css/print.css') ?>">
</head>
<body class="print-body">
  <div class="print-toolbar no-print"><button class="btn btn-primary" data-print type="button"><i class="bi bi-printer"></i> طباعة / حفظ PDF</button>
    <span class="text-muted small">لحفظ PDF اختر «حفظ كـ PDF» من نافذة الطباعة، وحجم الورق A4.</span></div>
  <?= $content ?>
  <script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
