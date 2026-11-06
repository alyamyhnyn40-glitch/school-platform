(function () {
  'use strict';
  document.querySelectorAll('.pay-form').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      e.preventDefault(); var b = f.querySelector('button'); b.classList.add('is-loading'); b.disabled = true;
      var body = {}; new FormData(f).forEach(function (v, k) { body[k] = v; });
      api(f.getAttribute('action'), { method: 'POST', body: body }).then(function (r) {
        if (r.ok && r.data && r.data.redirect) { toast(r.data.message || 'جارٍ التحويل…'); location.href = r.data.redirect; return; }
        b.classList.remove('is-loading'); b.disabled = false; toast((r.error && r.error.message) || 'تعذّر بدء الدفع', 'danger');
      });
    });
  });
})();
