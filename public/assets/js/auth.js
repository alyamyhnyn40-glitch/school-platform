// تبويب OTP: طلب الرمز ثم التحقق
(function () {
  'use strict';
  document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
    var input = document.getElementById(button.getAttribute('data-password-toggle'));
    if (!input) return;
    button.addEventListener('click', function () {
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      button.setAttribute('aria-pressed', show ? 'true' : 'false');
      button.setAttribute('aria-label', show ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور');
      var icon = button.querySelector('i');
      if (icon) icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
    });
  });

  document.querySelectorAll('input[autocomplete=one-time-code]').forEach(function (input) {
    input.addEventListener('input', function () {
      var digits = input.value
        .replace(/[٠-٩]/g, function (d) { return String('٠١٢٣٤٥٦٧٨٩'.indexOf(d)); })
        .replace(/[۰-۹]/g, function (d) { return String('۰۱۲۳۴۵۶۷۸۹'.indexOf(d)); })
        .replace(/\D/g, '');
      input.value = digits.slice(0, 6);
    });
  });

  var recoveryIdentifier = document.getElementById('identifier');
  var recoveryHint = document.getElementById('recoveryHint');
  if (recoveryIdentifier && recoveryHint) {
    recoveryIdentifier.addEventListener('input', function () {
      recoveryHint.textContent = recoveryIdentifier.value.indexOf('@') !== -1
        ? 'سيُرسل رمز تحقق إلى البريد إذا كان مرتبطًا بحساب نشط.'
        : 'سيُرسل رابط الاستعادة إلى رقم الهاتف أو البريد المرتبط بالحساب.';
    });
  }

  var emailInput = document.getElementById('em');
  var resendEmail = document.getElementById('resendEmail');
  var resendEmailButton = document.getElementById('resendEmailButton');
  var resendEmailForm = document.getElementById('resendEmailForm');
  if (emailInput && resendEmail && resendEmailButton && resendEmailForm) {
    resendEmailButton.addEventListener('click', function () {
      resendEmail.value = emailInput.value.trim();
      if (!resendEmail.value) {
        emailInput.focus();
        window.toast('أدخل البريد الإلكتروني أولًا.', 'warning');
        return;
      }
      if (resendEmailForm.requestSubmit) resendEmailForm.requestSubmit();
      else resendEmailButton.form.submit();
    });
  }

  var req = document.getElementById('otpRequestForm'), ver = document.getElementById('otpVerifyForm');
  if (!req || !ver) return;
  var code = ver.querySelector('[name=code]');
  var resend = document.getElementById('resendOtp');
  var changePhone = document.getElementById('changeOtpPhone');

  function showCodeStep() {
    ver.querySelector('[name=phone]').value = req.querySelector('[name=phone]').value;
    req.classList.add('d-none');
    ver.classList.remove('d-none');
    if (code) { code.value = ''; code.focus(); }
  }

  req.addEventListener('ajax:success', function () {
    showCodeStep();
  });

  if (resend) resend.addEventListener('click', function () {
    if (req.requestSubmit) req.requestSubmit();
    else req.querySelector('[type=submit]').click();
  });

  if (changePhone) changePhone.addEventListener('click', function () {
    ver.classList.add('d-none');
    req.classList.remove('d-none');
    req.querySelector('[name=phone]').focus();
  });

})();
