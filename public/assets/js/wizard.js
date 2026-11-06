// مكوّن «معالج خطوات» عام: <form data-wizard> وبداخله <section class="wz-step" data-title="..."> لكل خطوة.
// يتحقق من حقول الخطوة الحالية قبل الانتقال، ويعرض ملخصًا في عنصر [data-wz-review] من الحقول ذات data-label.
(function () {
  'use strict';
  document.querySelectorAll('form[data-wizard]').forEach(function (form) {
    var steps = Array.prototype.slice.call(form.querySelectorAll('.wz-step')); if (!steps.length) return;
    var cur = 0, nav = document.createElement('ol'); nav.className = 'wz-nav';
    steps.forEach(function (s, i) { var li = document.createElement('li'); li.textContent = s.getAttribute('data-title') || ('الخطوة ' + (i + 1)); nav.appendChild(li); });
    form.insertBefore(nav, form.firstChild);
    var bar = document.createElement('div'); bar.className = 'wz-actions d-flex gap-2 mt-3';
    var prev = document.createElement('button'); prev.type = 'button'; prev.className = 'btn btn-outline-secondary'; prev.textContent = 'السابق';
    var next = document.createElement('button'); next.type = 'button'; next.className = 'btn btn-primary ms-auto'; next.textContent = 'التالي';
    var submit = form.querySelector('[data-wz-submit]') || form.querySelector('button[type=submit]'); bar.appendChild(prev); bar.appendChild(next); if (submit) { submit.classList.add('ms-auto'); bar.appendChild(submit); }
    form.appendChild(bar);
    function valid(step) {
      var ok = true; step.querySelectorAll('input,select,textarea').forEach(function (el) { if (el.offsetParent === null) return; if (!el.checkValidity()) { ok = false; el.classList.add('is-invalid'); } else { el.classList.remove('is-invalid'); } });
      if (!ok) { var f = step.querySelector('.is-invalid'); if (f) f.focus(); if (window.toast) toast('أكمل الحقول المطلوبة في هذه الخطوة', 'warning'); }
      return ok;
    }
    function review() {
      var box = form.querySelector('[data-wz-review]'); if (!box) return; box.textContent = '';
      form.querySelectorAll('[data-label]').forEach(function (el) {
        if (el.closest('.wz-step') && el.closest('.wz-step').hasAttribute('hidden-by-cond')) return;
        var v; if (el.type === 'checkbox') { v = el.checked ? 'نعم' : 'لا'; } else if (el.tagName === 'SELECT') { v = el.options[el.selectedIndex] ? el.options[el.selectedIndex].text : ''; } else { v = el.value; }
        if (v === '' || (el.offsetParent === null && el.type !== 'checkbox')) return;
        var row = document.createElement('div'); row.className = 'wz-review-row'; var k = document.createElement('span'); k.textContent = el.getAttribute('data-label'); var b = document.createElement('strong'); b.textContent = v; row.appendChild(k); row.appendChild(b); box.appendChild(row);
      });
    }
    function show(i) {
      cur = i; steps.forEach(function (s, j) { s.hidden = j !== i; }); Array.prototype.forEach.call(nav.children, function (li, j) { li.className = j < i ? 'done' : (j === i ? 'active' : ''); });
      prev.hidden = i === 0; next.hidden = i === steps.length - 1; if (submit) submit.hidden = i !== steps.length - 1;
      if (i === steps.length - 1) review(); var h = steps[i].querySelector('input,select,textarea'); if (h && i > 0) h.focus();
    }
    prev.addEventListener('click', function () { if (cur > 0) show(cur - 1); });
    next.addEventListener('click', function () { if (valid(steps[cur])) show(cur + 1); });
    form.addEventListener('keydown', function (e) { if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA' && cur < steps.length - 1) { e.preventDefault(); next.click(); } });
    show(0);
  });
})();
