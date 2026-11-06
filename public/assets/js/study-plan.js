// محرر الخطة الدراسية: إضافة/حذف سطور، وإرسال تلقائي لقوائم التصفية
(function () {
  'use strict';
  var body = document.getElementById('planRows'), tpl = document.getElementById('planRowTpl'), add = document.getElementById('planAdd'); if (!body || !tpl || !add) return;
  var next = body.querySelectorAll('tr').length + 1;
  add.addEventListener('click', function () {
    var html = tpl.innerHTML.replace(/\[999999\]/g, '[' + (next++) + ']'); var t = document.createElement('tbody'); t.innerHTML = html; var tr = t.firstElementChild; body.appendChild(tr); var s = tr.querySelector('select'); if (s) s.focus();
  });
  body.addEventListener('click', function (e) { var b = e.target.closest('.plan-del'); if (b) { var tr = b.closest('tr'); if (body.querySelectorAll('tr').length > 1) tr.remove(); else tr.querySelectorAll('select,input[type=number]').forEach(function (x) { x.value = ''; }); } });
})();
