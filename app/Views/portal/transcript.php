<div class="page-head"><div><h1 class="page-title">السجل الأكاديمي</h1><p class="page-sub"><?= e($t['student']['full_name']) ?> — النتائج المعتمدة فقط</p></div><a class="btn btn-outline-primary" href="<?= url('portal/student/' . (int)$t['student']['id']) ?>"><i class="bi bi-arrow-right"></i> رجوع</a></div>
<?= \App\Core\View::partial('transcript', ['t' => $t]) ?>
