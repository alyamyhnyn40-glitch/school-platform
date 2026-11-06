(function () {
  'use strict';
  var box = document.getElementById('payBox'); if (!box || box.getAttribute('data-state') !== 'pending') return;
  var tries = 0;
  (function poll() {
    api(box.getAttribute('data-status-url')).then(function (r) {
      if (r.ok && r.data.status !== 'pending') { location.reload(); return; }
      if (++tries < 40) setTimeout(poll, 3000);
    });
  })();
})();
