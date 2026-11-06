(function () {
  'use strict';
  var f = document.getElementById('filters');
  var L = { 'attendance.record': 'تسجيل حضور', 'attendance.change': 'تعديل حضور', 'grade.record': 'إدخال درجة', 'grade.change': 'تعديل درجة', 'assessment.create': 'إنشاء تقييم', 'assessment.approve': 'اعتماد تقييم', 'assessment.reopen': 'إعادة فتح تقييم', 'assessment.delete': 'حذف تقييم', 'term.lock': 'قفل فصل', 'term.unlock': 'فتح قفل فصل', 'reportcard.issue': 'إصدار شهادات', 'grading.scheme': 'نظام درجات', 'student.create': 'إضافة طالب', 'student.update': 'تعديل طالب', 'student.delete': 'حذف طالب', 'student.status': 'تغيير حالة طالب', 'year.promote': 'ترفيع جماعي', login: 'دخول' };
  function p() { var o = {}; new FormData(f).forEach(function (v, k) { if (v) o[k] = v; }); return o; }
  function short(v) { if (!v) return ''; try { return JSON.stringify(JSON.parse(v)); } catch (e) { return v; } }
  var dt = new DataTable({ root: document.getElementById('table'), endpoint: '/api/v1/school/audit', params: p, empty: 'لا توجد سجلات',
    columns: [
      { label: 'الوقت', render: function (r) { return el('span', { dir: 'ltr', text: r.created_at }); } }, { label: 'المستخدم', render: function (r) { return r.user_name || '—'; } },
      { label: 'العملية', render: function (r) { var s = el('span', { text: L[r.action] || r.action }); if (+r.via_support) s.appendChild(el('span', { class: 'badge text-bg-info ms-1', text: 'دعم' })); return s; } },
      { label: 'التفاصيل (قبل ← بعد)', render: function (r) { var o = short(r.old_values), n = short(r.new_values); return el('small', { class: 'text-muted text-break', dir: 'ltr', text: (o ? o + ' ← ' : '') + n }); } }] });
  f.addEventListener('submit', function (e) { e.preventDefault(); }); f.addEventListener('change', function () { dt.load(1); });
})();
