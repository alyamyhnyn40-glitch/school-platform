(function () {
  'use strict';
  function rowType(t) { var r = el('div', { class: 'row g-2 mb-1 type-row' }); r.innerHTML = ''; [['name', 'اسم التقييم', t.name || '', 'col-6'], ['weight', 'الوزن %', t.weight || '', 'col-3'], ['max_score', 'العظمى', t.max_score || '', 'col-3']].forEach(function (f) { r.appendChild(el('div', { class: f[3] }, [el('input', { class: 'form-control', 'data-f': f[0], placeholder: f[1], 'aria-label': f[1], value: f[2], type: f[0] === 'name' ? 'text' : 'number', step: '0.01' })])); }); return r; }
  function rowScale(t) { var r = el('div', { class: 'row g-2 mb-1 scale-row' }); [['label', 'التقدير', t.label || '', 'col-7', 'text'], ['min_percent', 'من %', t.min_percent === undefined ? '' : t.min_percent, 'col-5', 'number']].forEach(function (f) { r.appendChild(el('div', { class: f[3] }, [el('input', { class: 'form-control', 'data-f': f[0], placeholder: f[1], 'aria-label': f[1], value: f[2], type: f[4], step: '0.01' })])); }); return r; }
  document.querySelectorAll('.scheme-form').forEach(function (f) {
    var tBox = f.querySelector('.types'), sBox = f.querySelector('.scales');
    JSON.parse(f.getAttribute('data-types') || '[]').forEach(function (t) { tBox.appendChild(rowType(t)); });
    JSON.parse(f.getAttribute('data-scales') || '[]').forEach(function (t) { sBox.appendChild(rowScale(t)); });
    f.querySelector('.add-type').addEventListener('click', function () { tBox.appendChild(rowType({})); });
    f.querySelector('.add-scale').addEventListener('click', function () { sBox.appendChild(rowScale({})); });
    function sum() { var s = 0; tBox.querySelectorAll('[data-f=weight]').forEach(function (i) { s += parseFloat(i.value) || 0; }); f.querySelector('.wsum').textContent = 'مجموع الأوزان: ' + s; }
    f.addEventListener('input', sum); sum();
    f.addEventListener('submit', function (e) {
      e.preventDefault(); var body = { types: [], scales: [] };
      ['name', 'max_score', 'pass_score', 'average_method', 'rounding', 'stage_id'].forEach(function (k) { body[k] = f.querySelector('[name=' + k + ']').value; });
      body.is_default = f.querySelector('[name=is_default]').checked ? 1 : 0;
      tBox.querySelectorAll('.type-row').forEach(function (r) { var o = {}; r.querySelectorAll('[data-f]').forEach(function (i) { o[i.getAttribute('data-f')] = i.value; }); body.types.push(o); });
      sBox.querySelectorAll('.scale-row').forEach(function (r) { var o = {}; r.querySelectorAll('[data-f]').forEach(function (i) { o[i.getAttribute('data-f')] = i.value; }); body.scales.push(o); });
      api(f.getAttribute('data-url'), { method: f.getAttribute('data-method'), body: body }).then(function (r) { if (r.ok) { toast('تم الحفظ'); setTimeout(function () { location.reload(); }, 500); } else toast((r.error && r.error.message) || 'خطأ', 'danger'); });
    });
  });
})();
