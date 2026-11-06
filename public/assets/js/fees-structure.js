(function () {
  'use strict';
  var f = document.getElementById('structForm'), box = document.getElementById('items'); if (!f) return;
  function total() { var t = 0; box.querySelectorAll('.irow').forEach(function (r) { var v = parseFloat(r.querySelector('[data-f=amount]').value); if (v > 0) t += v; }); document.getElementById('itemsTotal').textContent = 'الإجمالي: ' + t.toLocaleString('en-US', { minimumFractionDigits: 2 }); }
  function row(n, a) {
    var r = el('div', { class: 'row g-2 mb-1 irow' });
    r.appendChild(el('div', { class: 'col-7' }, [el('input', { class: 'form-control', 'data-f': 'name', placeholder: 'اسم البند (رسوم دراسية، زي…)', 'aria-label': 'اسم البند', value: n || '' })]));
    r.appendChild(el('div', { class: 'col-4' }, [el('input', { class: 'form-control', 'data-f': 'amount', type: 'number', step: '0.01', min: '0', placeholder: 'المبلغ', 'aria-label': 'المبلغ', value: a || '' })]));
    var x = el('button', { type: 'button', class: 'btn btn-sm btn-outline-danger', text: '✕', 'aria-label': 'حذف البند' }); x.onclick = function () { r.remove(); total(); }; r.appendChild(el('div', { class: 'col-1' }, [x])); return r;
  }
  box.appendChild(row('رسوم دراسية', '')); document.getElementById('addItem').addEventListener('click', function () { box.appendChild(row('', '')); });
  box.addEventListener('input', total); total();
  f.addEventListener('submit', function (e) {
    e.preventDefault(); var body = { items: [] };
    ['name', 'academic_year_id', 'grade_level_id', 'currency', 'installments_count', 'first_due_date', 'interval_months'].forEach(function (k) { body[k] = f.querySelector('[name=' + k + ']').value; });
    box.querySelectorAll('.irow').forEach(function (r) { body.items.push({ name: r.querySelector('[data-f=name]').value, amount: r.querySelector('[data-f=amount]').value }); });
    api(f.getAttribute('data-url'), { method: 'POST', body: body }).then(function (r) { if (r.ok) { toast('تم الحفظ'); setTimeout(function () { location.reload(); }, 500); } else toast((r.error && r.error.message) || 'خطأ', 'danger'); });
  });
})();
