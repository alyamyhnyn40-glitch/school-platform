<div class="text-center py-5">
  <div class="display-1 fw-bold text-muted">404</div>
  <p class="lead mb-1"><?= e($message ?: __t('err.404')) ?></p>
  <a class="btn btn-primary mt-3" href="<?= url('/') ?>"><?= e(__t('err.home')) ?></a>
</div>
