(function () {
  'use strict';
  var box = document.querySelector('[data-result]');
  var btn = document.getElementById('resetAdmin');
  if (btn) btn.addEventListener('click', function () {
    api(btn.getAttribute('data-url'), { method: 'POST', body: {} }).then(function (r) {
      if (r.ok) { window.showSecret(box, r.data); } else { toast((r.error && r.error.message) || 'فشل', 'danger'); }
    });
  });
})();
