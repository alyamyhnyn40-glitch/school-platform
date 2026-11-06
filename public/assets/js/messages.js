(function () {
  'use strict';
  var f = document.getElementById('msgForm'); if (!f) return;
  var q = document.getElementById('rq'), list = document.getElementById('rlist'), chosen = document.getElementById('chosen'), picked = {}, tm = null;
  var TYPE = { staff: 'موظف', student: 'طالب', guardian: 'ولي أمر' };
  function search() {
    api('/api/v1/messages/recipients?q=' + encodeURIComponent(q.value)).then(function (r) {
      list.textContent = ''; if (!r.ok) return;
      r.data.forEach(function (u) { if (picked[u.id]) return; var b = el('button', { type: 'button', class: 'list-group-item list-group-item-action', text: u.full_name + ' — ' + (TYPE[u.user_type] || '') }); b.onclick = function () { picked[u.id] = u.full_name; render(); list.textContent = ''; q.value = ''; }; list.appendChild(b); });
    });
  }
  function render() { chosen.textContent = ''; Object.keys(picked).forEach(function (id) { var b = el('span', { class: 'badge text-bg-primary' }, [document.createTextNode(picked[id] + ' ')]); var x = el('button', { type: 'button', class: 'btn-close btn-close-white ms-1', 'aria-label': 'إزالة', style: 'font-size:.6rem' }); x.onclick = function () { delete picked[id]; render(); }; b.appendChild(x); chosen.appendChild(b); }); }
  q.addEventListener('input', function () { clearTimeout(tm); tm = setTimeout(search, 250); }); q.addEventListener('focus', search);
  f.addEventListener('submit', function (e) {
    e.preventDefault(); var ids = Object.keys(picked).map(Number); if (!ids.length) { toast('اختر مستلمًا واحدًا على الأقل', 'warning'); return; }
    api(f.getAttribute('action'), { method: 'POST', body: { recipients: ids, subject: f.querySelector('[name=subject]').value, body: f.querySelector('[name=body]').value } }).then(function (r) { if (r.ok) location.href = r.data.redirect; else toast((r.error && r.error.message) || 'خطأ', 'danger'); });
  });
})();
