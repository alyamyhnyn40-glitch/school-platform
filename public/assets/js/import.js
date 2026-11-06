(function () {
  'use strict';
  var f = document.getElementById('uploadForm'); if (!f) return;
  var kind = f.querySelector('[name=kind]'), hint = document.getElementById('kindHint'), tpl = document.getElementById('tplLink'), nav = f.querySelectorAll('.wz-nav li');
  function sync() { var o = kind.options[kind.selectedIndex]; if (!o) return; if (hint) hint.textContent = o.getAttribute('data-hint') || ''; if (tpl) tpl.href = o.getAttribute('data-tpl'); }
  kind.addEventListener('change', sync); sync();
  function step(i) { Array.prototype.forEach.call(nav, function (li, j) { li.className = j < i ? 'done' : (j === i ? 'active' : ''); }); }
  if (tpl) tpl.addEventListener('click', function () { step(1); });
  f.querySelector('[name=file]').addEventListener('change', function () { step(2); });
  f.addEventListener('submit', function (e) {
    e.preventDefault(); var btn = f.querySelector('button[type=submit]'); btn.classList.add('is-loading');
    var fd = new FormData(); fd.append('kind', f.querySelector('[name=kind]').value); var file = f.querySelector('[name=file]').files[0];
    if (!file) { toast('اختر ملفًا', 'warning'); btn.classList.remove('is-loading'); return; } fd.append('file', file);
    api('/api/v1/school/import', { method: 'POST', body: fd }).then(function (r) { btn.classList.remove('is-loading'); if (r.ok) { location.href = r.data.redirect; } else toast((r.error && r.error.message) || 'فشل الرفع', 'danger'); });
  });
})();
