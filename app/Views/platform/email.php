<?= \App\Core\View::partial('settings_tabs', ['active' => 'email']) ?>

<div class="row g-3">
  <div class="col-lg-7">
    <form id="emailSettingsForm" class="card-s" method="post" action="<?= url('api/v1/platform/settings/email') ?>" data-ajax novalidate>
      <?= csrf_field() ?>
      <div class="d-flex align-items-start gap-2 mb-3">
        <i class="bi bi-envelope-at fs-4 text-primary" aria-hidden="true"></i>
        <div>
          <h2 class="h5 mb-1">إعداد البريد الإلكتروني</h2>
          <p class="small text-muted mb-0">لإرسال رموز استعادة كلمة المرور وإشعارات تسجيل المدارس.</p>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label" for="drv">1. اختر طريقة الإرسال</label>
        <select class="form-select" id="drv" name="driver">
          <option value="smtp" <?= $s['driver'] === 'smtp' ? 'selected' : '' ?>>SMTP — Gmail أو مزود بريد آخر (موصى به)</option>
          <option value="mail" <?= $s['driver'] === 'mail' ? 'selected' : '' ?>>mail() — بريد الخادم</option>
          <option value="log" <?= $s['driver'] === 'log' ? 'selected' : '' ?>>تجريبي — حفظ الرسائل في سجل فقط</option>
        </select>
        <div id="logNotice" class="form-text mt-2" <?= $s['driver'] === 'log' ? '' : 'hidden' ?>>
          الوضع التجريبي لا يرسل رسائل؛ يحفظها في <code>storage/logs/mail.log</code>.
        </div>
      </div>

      <section id="httpBox" data-channel="email" <?= $s['driver'] === 'smtp' ? '' : 'hidden' ?> aria-labelledby="smtpTitle">
        <h3 id="smtpTitle" class="h6 mt-4">2. إعداد خادم SMTP</h3>
        <?php if (!$s['has_password']): ?>
          <div id="missingPasswordNotice" class="alert alert-warning py-2 small" role="status">لا توجد كلمة مرور SMTP محفوظة. أدخل كلمة مرور التطبيق واحفظ الإعدادات قبل الاختبار.</div>
        <?php endif; ?>
        <div class="mb-3">
          <label class="form-label" for="preset">مزود البريد</label>
          <select class="form-select" id="preset">
            <option value="">إعداد مخصص أو اختر مزودًا</option>
          </select>
          <div class="form-text" id="presetNote">اختر مزودًا لتعبئة الخادم والمنفذ والتشفير تلقائيًا.</div>
        </div>

        <div class="row g-2 mb-3">
          <div class="col-md-6">
            <label class="form-label" for="h">خادم SMTP</label>
            <input class="form-control" id="h" name="host" dir="ltr" value="<?= e($s['host']) ?>" placeholder="smtp.gmail.com" autocomplete="url">
          </div>
          <div class="col-5 col-md-3">
            <label class="form-label" for="p">المنفذ</label>
            <input class="form-control" id="p" name="port" type="number" min="1" max="65535" dir="ltr" value="<?= e($s['port']) ?>" inputmode="numeric">
          </div>
          <div class="col-7 col-md-3">
            <label class="form-label" for="e">التشفير</label>
            <select class="form-select" id="e" name="encryption">
              <option value="tls" <?= $s['encryption'] === 'tls' ? 'selected' : '' ?>>TLS · 587</option>
              <option value="ssl" <?= $s['encryption'] === 'ssl' ? 'selected' : '' ?>>SSL · 465</option>
              <option value="none" <?= $s['encryption'] === 'none' ? 'selected' : '' ?>>بلا تشفير</option>
            </select>
          </div>
        </div>

        <div class="row g-2 mb-2">
          <div class="col-md-6">
            <label class="form-label" for="u">اسم المستخدم</label>
            <input class="form-control" id="u" name="username" type="text" dir="ltr" autocomplete="username" value="<?= e($s['username']) ?>" placeholder="name@example.com">
          </div>
          <div class="col-md-6">
            <label class="form-label" for="pw">كلمة المرور أو كلمة مرور التطبيق</label>
            <div class="input-group">
              <input class="form-control" id="pw" name="password" type="password" dir="ltr" autocomplete="new-password" placeholder="<?= $s['has_password'] ? 'محفوظة — اتركها فارغة للإبقاء عليها' : 'أدخل كلمة المرور' ?>">
              <button class="btn btn-outline-secondary" type="button" id="togglePassword" aria-label="إظهار كلمة المرور" aria-pressed="false">
                <i class="bi bi-eye" aria-hidden="true"></i>
              </button>
            </div>
            <div class="form-text">لا تُعرض كلمة المرور المحفوظة. أدخل قيمة جديدة فقط عند تغييرها.</div>
          </div>
        </div>

        <?php if ($s['has_password']): ?>
          <div class="form-check my-2">
            <input type="hidden" name="clear_password" value="0">
            <input class="form-check-input" type="checkbox" name="clear_password" value="1" id="cp">
            <label class="form-check-label" for="cp">حذف كلمة المرور المحفوظة</label>
          </div>
        <?php endif; ?>

        <details class="mt-3">
          <summary class="small">إعداد متقدم: شهادة TLS</summary>
          <div class="form-check mt-2">
            <input type="hidden" name="allow_self_signed" value="0">
            <input class="form-check-input" type="checkbox" name="allow_self_signed" value="1" id="ss" <?= $s['allow_self_signed'] ? 'checked' : '' ?>>
            <label class="form-check-label" for="ss">قبول شهادة موقّعة ذاتيًا (للاختبار فقط؛ يقلل أمان الاتصال)</label>
          </div>
        </details>
      </section>

      <section id="senderBox" <?= $s['driver'] === 'log' ? 'hidden' : '' ?> aria-labelledby="senderTitle">
        <h3 id="senderTitle" class="h6 mt-4">هوية المرسل</h3>
        <div class="row g-2 mb-3">
          <div class="col-md-6">
            <label class="form-label" for="fe">بريد المرسل</label>
            <input class="form-control" id="fe" name="from_email" dir="ltr" type="email" value="<?= e($s['from_email']) ?>" placeholder="name@example.com" autocomplete="email">
            <div class="d-flex align-items-center justify-content-between gap-2">
              <div class="form-text">مع Gmail، استخدم عنوان الحساب نفسه.</div>
              <button class="btn btn-link btn-sm p-0" type="button" id="copyUsername">استخدم اسم مستخدم SMTP</button>
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="fn">اسم المرسل</label>
            <input class="form-control" id="fn" name="from_name" value="<?= e($s['from_name']) ?>" placeholder="<?= e(\App\Services\Brand::name()) ?>" autocomplete="organization">
          </div>
        </div>
      </section>

      <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
        <button class="btn btn-primary" type="submit"><i class="bi bi-check2-circle me-1" aria-hidden="true"></i>حفظ الإعدادات</button>
        <span class="small text-muted">احفظ التغييرات قبل إرسال الاختبار.</span>
      </div>
    </form>
  </div>

  <div class="col-lg-5">
    <form class="card-s" method="post" action="<?= url('api/v1/platform/settings/email/test') ?>" data-ajax novalidate>
      <?= csrf_field() ?>
      <div class="d-flex align-items-start gap-2 mb-3">
        <i class="bi bi-send fs-4 text-primary" aria-hidden="true"></i>
        <div>
          <h2 class="h5 mb-1">3. اختبر الإرسال</h2>
          <p class="small text-muted mb-0">ترسل الرسالة باستخدام الإعدادات المحفوظة حاليًا.</p>
        </div>
      </div>
      <label class="form-label" for="to">أرسل رسالة الاختبار إلى</label>
      <input class="form-control mb-3" id="to" name="to" type="email" dir="ltr" autocomplete="email" placeholder="you@example.com" required>
      <button class="btn btn-outline-primary w-100" type="submit"><i class="bi bi-send me-1" aria-hidden="true"></i>إرسال رسالة اختبار</button>
      <div class="small mt-3 text-muted">طريقة الإرسال الحالية: <strong id="currentDriverLabel"><?= ['log' => 'تجريبي (لا يرسل)', 'smtp' => 'SMTP', 'mail' => 'mail()'][$s['driver']] ?? e($s['driver']) ?></strong></div>
    </form>

    <div class="card-s mt-3 small">
      <h2 class="h6">مساعدة سريعة</h2>
      <ul class="mb-0 ps-3">
        <li>Gmail: استخدم كلمة مرور تطبيق، وليس كلمة مرور الحساب.</li>
        <li>إذا فشل الاختبار، تأكد من تطابق بريد المرسل مع اسم مستخدم SMTP.</li>
        <li>لتحسين وصول الرسائل، اضبط سجلات SPF وDKIM لنطاق المرسل.</li>
      </ul>
    </div>
  </div>
</div>
