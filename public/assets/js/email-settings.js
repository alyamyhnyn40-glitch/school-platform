(function () {
  'use strict';
  var driver = document.getElementById('drv');
  var smtp = document.getElementById('httpBox');
  var sender = document.getElementById('senderBox');
  var logNotice = document.getElementById('logNotice');
  var username = document.getElementById('u');
  var fromEmail = document.getElementById('fe');
  var password = document.getElementById('pw');
  var togglePassword = document.getElementById('togglePassword');
  var copyUsername = document.getElementById('copyUsername');
  var settingsForm = document.getElementById('emailSettingsForm');
  var currentDriverLabel = document.getElementById('currentDriverLabel');
  var missingPasswordNotice = document.getElementById('missingPasswordNotice');
  var clearPassword = document.getElementById('cp');
  var hasPassword = !!(password && password.placeholder.indexOf('محفوظة') === 0);

  if (driver) {
    var updateSections = function () {
      var isSmtp = driver.value === 'smtp';
      if (smtp) smtp.hidden = !isSmtp;
      if (sender) sender.hidden = driver.value === 'log';
      if (logNotice) logNotice.hidden = driver.value !== 'log';
    };
    driver.addEventListener('change', updateSections);
    updateSections();
  }

  if (togglePassword && password) {
    togglePassword.addEventListener('click', function () {
      var visible = password.type === 'password';
      password.type = visible ? 'text' : 'password';
      togglePassword.setAttribute('aria-pressed', visible ? 'true' : 'false');
      togglePassword.setAttribute('aria-label', visible ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور');
      var icon = togglePassword.querySelector('i');
      if (icon) icon.className = visible ? 'bi bi-eye-slash' : 'bi bi-eye';
    });
  }

  if (copyUsername && username && fromEmail) {
    copyUsername.addEventListener('click', function () {
      fromEmail.value = username.value.trim();
      fromEmail.focus();
    });
  }

  if (settingsForm) {
    settingsForm.addEventListener('ajax:success', function () {
      if (driver && currentDriverLabel) {
        currentDriverLabel.textContent = {
          log: 'تجريبي (لا يرسل)',
          smtp: 'SMTP',
          mail: 'mail()'
        }[driver.value] || driver.value;
      }

      if (password && password.value) hasPassword = true;
      if (clearPassword && clearPassword.checked) {
        hasPassword = false;
        clearPassword.checked = false;
      }
      if (password) {
        password.value = '';
        password.placeholder = hasPassword
          ? 'محفوظة — اتركها فارغة للإبقاء عليها'
          : 'أدخل كلمة المرور';
      }
      if (missingPasswordNotice) missingPasswordNotice.hidden = hasPassword;
    });
  }
})();
