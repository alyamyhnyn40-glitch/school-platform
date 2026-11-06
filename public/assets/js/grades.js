(function () {
  'use strict';
  var tbl = document.getElementById('gradeTable'); if (!tbl) return;
  var aid = tbl.getAttribute('data-assessment'), max = parseFloat(tbl.getAttribute('data-max')), editable = tbl.getAttribute('data-editable') === '1';
  var badge = document.getElementById('pendingBadge'), failBox = document.getElementById('failedOps'), stats = document.getElementById('gradeStats');
  var dirty = {}, timer = null;
  function uuid() { return (window.crypto && crypto.randomUUID) ? crypto.randomUUID() : 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) { var r = Math.random() * 16 | 0; return (c === 'x' ? r : (r & 3 | 8)).toString(16); }); }
  function rows() { return Array.prototype.slice.call(tbl.querySelectorAll('tbody tr')); }
  function setState(tr, txt, cls) { var s = tr.querySelector('.st'); s.textContent = txt; s.className = 'st small ' + (cls || 'text-muted'); }
  function updateStats() {
    var n = 0, sum = 0, tot = rows().length;
    rows().forEach(function (tr) { var v = tr.querySelector('.sc').value, ab = tr.querySelector('.ab').checked; if (ab) { n++; } else if (v !== '') { n++; sum += parseFloat(v); } });
    var c = rows().filter(function (tr) { return !tr.querySelector('.ab').checked && tr.querySelector('.sc').value !== ''; }).length;
    stats.textContent = 'المُدخَل: ' + n + ' / ' + tot + (c ? ' — المتوسط: ' + (sum / c).toFixed(2) + ' من ' + max : '');
  }
  function collect() {
    var out = [];
    Object.keys(dirty).forEach(function (eid) {
      var tr = tbl.querySelector('tr[data-enr="' + eid + '"]'); var v = tr.querySelector('.sc').value, ab = tr.querySelector('.ab').checked;
      out.push({ enrollment_id: +eid, score: ab || v === '' ? null : v, is_absent: ab ? 1 : 0, note: tr.querySelector('.nt').value, client_uuid: uuid() });
    });
    return out;
  }
  function save() {
    var scores = collect(); if (!scores.length) return; var ids = Object.keys(dirty); dirty = {};
    ids.forEach(function (e) { setState(tbl.querySelector('tr[data-enr="' + e + '"]'), 'جارٍ الحفظ…'); });
    api('/api/v1/school/grades/' + aid + '/scores', { method: 'POST', body: { scores: scores } }).then(function (r) {
      if (r.ok) { ids.forEach(function (e) { setState(tbl.querySelector('tr[data-enr="' + e + '"]'), '✔ محفوظ', 'text-success'); }); return; }
      if (r.status === 0) { Offline.queue('grades', { assessment_id: +aid, scores: scores }).then(function () { ids.forEach(function (e) { setState(tbl.querySelector('tr[data-enr="' + e + '"]'), 'محفوظ على الجهاز', 'text-warning'); }); toast('لا اتصال — حُفظت الدرجات على الجهاز وستُرسل تلقائيًا', 'warning'); }); return; }
      ids.forEach(function (e) { dirty[e] = 1; setState(tbl.querySelector('tr[data-enr="' + e + '"]'), '✘ ' + ((r.error && r.error.message) || 'خطأ'), 'text-danger'); }); toast((r.error && r.error.message) || 'فشل الحفظ', 'danger');
    });
  }
  function onEdit(e) {
    var tr = e.target.closest('tr'); if (!tr) return; var sc = tr.querySelector('.sc'), ab = tr.querySelector('.ab');
    if (e.target === ab) { sc.disabled = ab.checked; if (ab.checked) sc.value = ''; }
    var v = sc.value; if (v !== '' && (isNaN(v) || +v < 0 || +v > max)) { sc.classList.add('is-invalid'); setState(tr, 'خارج المدى 0–' + max, 'text-danger'); return; } sc.classList.remove('is-invalid');
    dirty[tr.getAttribute('data-enr')] = 1; setState(tr, 'تعديل…'); updateStats(); clearTimeout(timer); timer = setTimeout(save, 700);
  }
  if (editable) {
    tbl.addEventListener('input', onEdit); tbl.addEventListener('change', onEdit);
    tbl.addEventListener('keydown', function (e) {   // Enter/الأسهم للتنقل بين الصفوف كجدول Excel
      if (!e.target.classList.contains('sc')) return; var list = rows().map(function (t) { return t.querySelector('.sc'); }), i = list.indexOf(e.target), n = null;
      if (e.key === 'Enter' || e.key === 'ArrowDown') n = list[i + 1]; else if (e.key === 'ArrowUp') n = list[i - 1];
      if (n) { e.preventDefault(); n.focus(); n.select(); }
    });
    window.addEventListener('beforeunload', function (e) { if (Object.keys(dirty).length) { e.preventDefault(); e.returnValue = ''; } });
  }
  updateStats();
  Offline.onChange(function (ops) {
    var w = ops.filter(function (o) { return !o.error; }), f = ops.filter(function (o) { return o.error; });
    badge.textContent = w.length ? 'بانتظار المزامنة: ' + w.length : ''; badge.classList.toggle('d-none', !w.length); failBox.textContent = '';
    f.forEach(function (o) { var b = el('button', { class: 'btn btn-sm btn-outline-danger ms-2', type: 'button', text: 'تجاهل' }); b.onclick = function () { Offline.remove(o.id); }; failBox.appendChild(el('div', { class: 'alert alert-danger py-2' }, [document.createTextNode('تعذّرت مزامنة درجات: ' + o.error), b])); });
  });
})();
