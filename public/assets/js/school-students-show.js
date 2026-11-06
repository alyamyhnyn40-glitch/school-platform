(function () {
  'use strict';
  var b = document.getElementById('mkAccount'), box = document.querySelector('[data-result]'); if (!b) return;
  b.addEventListener('click', function () {
    var c = b.getAttribute('data-confirm'); if (c && !confirm(c)) return;
    api(b.getAttribute('data-url'), { method: 'POST', body: {} }).then(function (r) { if (r.ok) window.showSecret(box, r.data); else toast((r.error && r.error.message) || 'فشل', 'danger'); });
  });
})();
