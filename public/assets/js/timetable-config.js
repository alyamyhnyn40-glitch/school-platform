(function () {
  'use strict';
  var f = document.getElementById('cfgForm'), box = document.getElementById('periods'); if (!f) return;
  function row(p) {
    var r = el('div', { class: 'row g-2 mb-1 align-items-center prow' });
    r.appendChild(el('div', { class: 'col-2 fw-bold', text: '' })); r.appendChild(el('div', { class: 'col-4' }, [el('input', { type: 'time', class: 'form-control', 'data-f': 'start', value: p.start || '', 'aria-label': 'بداية' })]));
    r.appendChild(el('div', { class: 'col-4' }, [el('input', { type: 'time', class: 'form-control', 'data-f': 'end', value: p.end || '', 'aria-label': 'نهاية' })]));
    var x = el('button', { type: 'button', class: 'btn btn-sm btn-outline-danger', text: '✕', 'aria-label': 'حذف' }); x.onclick = function () { r.remove(); num(); }; r.appendChild(el('div', { class: 'col-2' }, [x])); return r;
  }
  function num() { box.querySelectorAll('.prow').forEach(function (r, i) { r.firstChild.textContent = 'الحصة ' + (i + 1); }); }
  JSON.parse(f.getAttribute('data-periods')).forEach(function (p) { box.appendChild(row(p)); }); num();
  document.getElementById('addPeriod').addEventListener('click', function () { box.appendChild(row({})); num(); });
  f.addEventListener('submit', function (e) {
    e.preventDefault(); var days = [], periods = [];
    f.querySelectorAll('[name=days]:checked').forEach(function (c) { days.push(+c.value); });
    box.querySelectorAll('.prow').forEach(function (r) { periods.push({ start: r.querySelector('[data-f=start]').value, end: r.querySelector('[data-f=end]').value }); });
    api('/api/v1/school/timetable/config', { method: 'POST', body: { days: days, periods: periods } }).then(function (r) { if (r.ok) toast('تم الحفظ'); else toast((r.error && r.error.message) || 'خطأ', 'danger'); });
  });
})();
