(function () {
  'use strict';
  var q = document.getElementById('q'), g = document.getElementById('grade'), s = document.getElementById('section'), st = document.getElementById('status');
  var badge = { active: ['success', 'فعّال'], withdrawn: ['warning', 'منسحب'], transferred: ['info', 'منتقل'], graduated: ['dark', 'متخرج'] };
  var dt = new DataTable({
    root: document.getElementById('table'), endpoint: '/api/v1/school/students', empty: 'لا يوجد طلاب مطابقون',
    params: function () { return { q: q.value, grade_id: g.value, section_id: s.value, status: st.value }; },
    columns: [
      { label: 'رقم القيد', render: function (r) { return el('span', { dir: 'ltr', text: r.student_no }); } },
      { label: 'الاسم', render: function (r) { return el('a', { href: '/school/students/' + r.id, class: 'fw-bold', text: r.full_name }); } },
      { label: 'الصف', render: function (r) { return r.grade_name ? r.grade_name + ' / ' + r.section_name : '—'; } },
      { label: 'الجنس', render: function (r) { return r.gender === 'male' ? 'ذكر' : 'أنثى'; } },
      { label: 'الحالة', render: function (r) { var b = badge[r.status] || ['secondary', r.status]; return el('span', { class: 'badge text-bg-' + b[0], text: b[1] }); } }
    ]
  });
  q.addEventListener('input', dt.debounced);
  [g, s, st].forEach(function (x) { x.addEventListener('change', function () { dt.load(1); }); });
})();
