(function () {
  'use strict';
  var q = document.getElementById('q'), st = document.getElementById('status');
  var badge = { active: ['success', 'فعّالة'], suspended: ['warning', 'موقوفة'], archived: ['secondary', 'مؤرشفة'] };
  var dt = new DataTable({
    root: document.getElementById('table'), endpoint: '/api/v1/platform/schools', empty: 'لا توجد مدارس مطابقة',
    params: function () { return { q: q.value, status: st.value }; },
    columns: [
      { label: 'المدرسة', render: function (r) { return el('a', { href: '/platform/schools/' + r.id, class: 'fw-bold', text: r.name }); } },
      { label: 'الرمز', render: function (r) { return r.code; } },
      { label: 'المدينة', render: function (r) { return r.city || '—'; } },
      { label: 'الخطة', render: function (r) { return r.plan_name ? r.plan_name + (r.ends_on ? ' (حتى ' + r.ends_on + ')' : '') : '—'; } },
      { label: 'الطلاب', render: function (r) { return String(r.students); } },
      { label: 'الحالة', render: function (r) { var b = badge[r.status] || ['secondary', r.status]; return el('span', { class: 'badge text-bg-' + b[0], text: b[1] }); } }
    ]
  });
  q.addEventListener('input', dt.debounced); st.addEventListener('change', function () { dt.load(1); });
})();
