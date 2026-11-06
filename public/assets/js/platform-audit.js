(function () {
  'use strict';
  var f = document.getElementById('filters');
  function p() { var o = {}; new FormData(f).forEach(function (v, k) { if (v) o[k] = v; }); return o; }
  var labels = { 'school.create': 'إنشاء مدرسة', 'school.update': 'تعديل مدرسة', 'school.status': 'تغيير حالة مدرسة', 'school.subscription': 'تغيير اشتراك', 'school.admin_reset': 'إعادة تعيين كلمة مرور المدير',
    'support.enter': 'دخول دعم فني', 'support.exit': 'خروج من الدعم', 'plan.create': 'إنشاء خطة', 'plan.update': 'تعديل خطة', login: 'تسجيل دخول', logout: 'تسجيل خروج', password_change: 'تغيير كلمة مرور' };
  function short(v) { if (!v) return ''; try { return JSON.stringify(JSON.parse(v)); } catch (e) { return v; } }
  var dt = new DataTable({
    root: document.getElementById('table'), endpoint: '/api/v1/platform/audit', params: p, empty: 'لا توجد سجلات',
    columns: [
      { label: 'الوقت', render: function (r) { return el('span', { dir: 'ltr', text: r.created_at }); } },
      { label: 'المستخدم', render: function (r) { return r.user_name || '—'; } },
      { label: 'المدرسة', render: function (r) { return r.school_name || '—'; } },
      { label: 'العملية', render: function (r) { var s = el('span', { text: labels[r.action] || r.action }); if (+r.via_support) s.appendChild(el('span', { class: 'badge text-bg-info ms-1', text: 'دعم' })); return s; } },
      { label: 'التفاصيل', render: function (r) { var o = short(r.old_values), n = short(r.new_values); return el('small', { class: 'text-muted text-break', dir: 'ltr', text: (o ? o + ' ← ' : '') + n }); } }
    ]
  });
  f.addEventListener('submit', function (e) { e.preventDefault(); });
  f.addEventListener('change', function () { dt.load(1); });
})();
