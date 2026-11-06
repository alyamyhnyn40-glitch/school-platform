<div class="auth-card">
  <div class="auth-brand"><i class="bi bi-shield-lock"></i><h1><?= e(__t('auth.reset_title')) ?></h1></div>
  <form method="post" action="<?= url('password/reset/' . $token) ?>" data-ajax novalidate>
    <?= csrf_field() ?>
    <div class="mb-3"><label class="form-label" for="p1"><?= e(__t('auth.new_password')) ?></label><input class="form-control" id="p1" name="password" type="password" autocomplete="new-password" required></div>
    <div class="mb-3"><label class="form-label" for="p2"><?= e(__t('auth.confirm_password')) ?></label><input class="form-control" id="p2" name="password_confirm" type="password" autocomplete="new-password" required></div>
    <button class="btn btn-primary w-100" type="submit"><?= e(__t('auth.save')) ?></button>
  </form>
</div>
