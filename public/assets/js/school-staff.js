(function () {
  'use strict';
  var q = document.getElementById('q'), k = document.getElementById('kind');
  var dt = new DataTable({
    root: document.getElementById('table'), endpoint: '/api/v1/school/staff', empty: 'لا يوجد موظفون',
    params: function () { return { q: q.value, kind: k.value }; },
    columns: [
      { label: 'الاسم', render: function (r) { return el('a', { href: '/school/staff/' + r.id, class: 'fw-bold', text: r.full_name }); } },
      { label: 'الوظيفة', render: function (r) { return r.job_title || '—'; } },
      { label: 'التخصص', render: function (r) { return r.specialization || '—'; } },
      { label: 'الهاتف', render: function (r) { return el('span', { dir: 'ltr', text: r.phone || '—' }); } },
      { label: 'النوع', render: function (r) { return +r.is_teacher ? el('span', { class: 'badge text-bg-info', text: 'معلم' }) : '—'; } }
    ]
  });
  q.addEventListener('input', dt.debounced); k.addEventListener('change', function () { dt.load(1); });
})();
