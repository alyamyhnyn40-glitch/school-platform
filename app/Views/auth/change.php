<div class="<?= $forced ? 'auth-card' : 'card-s' ?>" style="max-width:480px">
  <h2 class="h5 mb-3"><?= e(__t('auth.change_title')) ?></h2>
  <?php if ($forced): ?><div class="alert alert-warning"><?= e(__t('auth.change_forced')) ?></div><?php endif; ?>
  <form method="post" action="<?= url('password/change') ?>" data-ajax novalidate>
    <?= csrf_field() ?>
    <?php if (!$forced): ?><div class="mb-3"><label class="form-label" for="cp"><?= e(__t('auth.current_password')) ?></label><input class="form-control" id="cp" name="current_password" type="password" autocomplete="current-password" required></div><?php endif; ?>
    <div class="mb-3"><label class="form-label" for="p1"><?= e(__t('auth.new_password')) ?></label><input class="form-control" id="p1" name="password" type="password" autocomplete="new-password" required></div>
    <div class="mb-3"><label class="form-label" for="p2"><?= e(__t('auth.confirm_password')) ?></label><input class="form-control" id="p2" name="password_confirm" type="password" autocomplete="new-password" required></div>
    <button class="btn btn-primary" type="submit"><?= e(__t('auth.save')) ?></button>
  </form>
</div>
