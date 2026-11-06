(function () {
  'use strict';
  var q = document.getElementById('q'), sec = document.getElementById('section'), st = document.getElementById('structure'), status = document.getElementById('status');
  function n(v) { return Number(v).toLocaleString('en-US', { minimumFractionDigits: 2 }); }
  var dt = new DataTable({
    root: document.getElementById('table'), endpoint: '/api/v1/school/fees/students', empty: 'لا توجد رسوم مطابقة',
    params: function () { return { q: q.value, section_id: sec.value, structure_id: st.value, status: status.value }; },
    columns: [
      { label: 'الطالب', render: function (r) { return el('a', { href: '/school/fees/students/' + r.id, class: 'fw-bold', text: r.full_name }); } },
      { label: 'الشعبة', render: function (r) { return r.grade_name + ' / ' + r.section_name; } },
      { label: 'الهيكل', render: function (r) { return r.structure_name; } },
      { label: 'المستحق', render: function (r) { return n(r.payable) + ' ' + r.currency; } },
      { label: 'المدفوع', render: function (r) { return el('span', { class: 'text-success', text: n(r.paid) }); } },
      { label: 'المتبقي', render: function (r) { return el('strong', { class: +r.balance > 0 ? 'text-danger' : 'text-success', text: n(r.balance) }); } },
      { label: 'الحالة', render: function (r) { var s = el('span'); var paid = +r.balance <= 0, part = +r.paid > 0;
        s.appendChild(el('span', { class: 'badge text-bg-' + (paid ? 'success' : part ? 'warning' : 'secondary'), text: paid ? 'مسدّد' : part ? 'جزئي' : 'لم يدفع' })); if (+r.overdue > 0) s.appendChild(el('span', { class: 'badge text-bg-danger ms-1', text: 'متأخر' })); return s; } }
    ]
  });
  q.addEventListener('input', dt.debounced); [sec, st, status].forEach(function (x) { x.addEventListener('change', function () { dt.load(1); }); });
})();
