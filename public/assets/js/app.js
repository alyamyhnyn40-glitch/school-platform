(function () {
  'use strict';
  var BASE = (function () { var m = document.querySelector('meta[name=app-base]'); return m ? m.content.replace(/\/$/, '') : ''; })();
  window.APP_BASE = BASE;
  var csrf = function () { var m = document.querySelector('meta[name=csrf-token]'); return m ? m.content : ''; };

  /** fetch JSON مع CSRF ومعالجة أخطاء موحدة. يعيد {ok,data,error,meta,status}. */
  window.api = function (url, opts) {
    opts = opts || {};
    var headers = { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': csrf() };
    var body = opts.body;
    if (body && !(body instanceof FormData)) { headers['Content-Type'] = 'application/json'; body = JSON.stringify(body); }
    if (BASE && url.charAt(0) === '/' && url.indexOf(BASE + '/') !== 0) { url = BASE + url; }
    return fetch(url, { method: opts.method || 'GET', headers: headers, body: body, credentials: 'same-origin' })
      .then(function (r) {
        return r.json().catch(function () { return { ok: false, error: { message: 'استجابة غير صالحة' } }; })
          .then(function (j) {
            j.status = r.status; if (r.status === 401) { location.href = BASE + '/login'; }
            var fb = { 403: 'ليست لديك صلاحية لهذه العملية. اطلبها من مدير المدرسة.', 404: 'العنصر المطلوب غير موجود (ربما حُذف).', 409: 'تعارض مع بيانات موجودة.', 413: 'الملف أكبر من الحجم المسموح على الخادم.', 419: 'انتهت صلاحية الصفحة. أعد تحميلها ثم حاول مجددًا.', 422: 'تحقق من البيانات المدخلة.', 429: 'محاولات كثيرة في وقت قصير. انتظر قليلًا ثم أعد المحاولة.', 500: 'حدث خطأ في الخادم. أعد المحاولة، وإن تكرر فأبلغ الدعم.', 502: 'تعذّر الاتصال بالخدمة الخارجية.', 503: 'المنصة في صيانة مؤقتة.' };
            if (!j.ok) { j.error = j.error || {}; if (!j.error.message) j.error.message = fb[r.status] || 'حدث خطأ'; }
            return j;
          });
      })
      .catch(function () { return { ok: false, status: 0, error: { code: 'network', message: 'تعذر الاتصال بالخادم. تحقق من الإنترنت.' } }; });
  };

  /** مُنشئ عناصر DOM آمن (نصوص عبر textContent). */
  window.el = function (tag, attrs, kids) {
    var n = document.createElement(tag);
    Object.keys(attrs || {}).forEach(function (k) { if (k === 'text') n.textContent = attrs[k]; else n.setAttribute(k, attrs[k]); });
    (kids || []).forEach(function (c) { n.appendChild(typeof c === 'string' ? document.createTextNode(c) : c); });
    return n;
  };
  window.toast = function (msg, type) {
    var box = document.getElementById('toasts'); if (!box) { alert(msg); return; }
    var el = document.createElement('div');
    type = type || 'success';
    el.className = 'toast align-items-center text-bg-' + type + ' border-0';
    el.setAttribute('role', type === 'danger' ? 'alert' : 'status');
    var icon = { success: 'check-circle-fill', danger: 'x-octagon-fill', warning: 'exclamation-triangle-fill', info: 'info-circle-fill' }[type] || 'info-circle-fill';
    el.innerHTML = '<div class="d-flex"><div class="toast-body d-flex gap-2 align-items-start"><i class="bi bi-' + icon + ' fs-5" aria-hidden="true"></i><span></span></div><button type="button" class="btn-close btn-close-white m-auto me-2" data-bs-dismiss="toast" aria-label="إغلاق"></button></div>';
    el.querySelector('.toast-body span').textContent = msg;
    box.appendChild(el);
    var t = new bootstrap.Toast(el, { delay: Math.max(type === 'danger' ? 8000 : 4500, String(msg).length * 70) }); t.show();
    el.addEventListener('hidden.bs.toast', function () { el.remove(); });
  };

  // الشريط الجانبي
  var sb = document.getElementById('sidebar'), bd = document.getElementById('sidebarBackdrop'), tg = document.getElementById('sidebarToggle');
  function setSb(open) { if (!sb) return; sb.classList.toggle('open', open); bd.classList.toggle('show', open); }
  if (tg) tg.addEventListener('click', function () { setSb(!sb.classList.contains('open')); });
  if (bd) bd.addEventListener('click', function () { setSb(false); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setSb(false); });

  // قوائم تُرسل نموذجها عند التغيير (بدل onchange المحجوب بسياسة CSP): <select data-autosubmit>
  document.addEventListener('change', function (e) { var t = e.target; if (t.hasAttribute && t.hasAttribute('data-autosubmit') && t.form) t.form.submit(); });

  // الوضع الليلي
  var bm = document.getElementById('bottomMenu'), st = document.getElementById('sidebarToggle');
  if (bm && st) bm.addEventListener('click', function () { st.click(); });
  // أي جدول بلا غلاف يُغلَّف بتمرير أفقي حتى لا يكسر عرض الصفحة على الجوال
  document.querySelectorAll('.content table.table').forEach(function (t) {
    if (t.closest('.table-responsive') || t.classList.contains('table-cards')) return;
    var w = document.createElement('div'); w.className = 'table-responsive'; t.parentNode.insertBefore(w, t); w.appendChild(t);
  });
  // حقول شرطية: <div data-show-if="field=value"> أو "field!=value" أو "field=a,b". المخفي تُعطَّل حقوله فلا تُرسَل.
  function condEval(form, expr) {
    var neg = expr.indexOf('!=') > 0, parts = expr.split(neg ? '!=' : '='), name = parts[0].trim(), vals = (parts[1] || '').split(',');
    var els = form.querySelectorAll('[name="' + name + '"]'), v = '';
    Array.prototype.forEach.call(els, function (el) { if (el.type === 'checkbox' || el.type === 'radio') { if (el.checked) v = el.value; } else if (el.type !== 'hidden' || els.length === 1) { v = el.value; } });
    var hit = vals.indexOf(v) !== -1; return neg ? !hit : hit;
  }
  function condApply(root) {
    (root || document).querySelectorAll('[data-show-if]').forEach(function (box) {
      var form = box.closest('form') || document, show = condEval(form, box.getAttribute('data-show-if'));
      box.hidden = !show; box.querySelectorAll('input,select,textarea').forEach(function (el) { el.disabled = !show; });
    });
  }
  document.addEventListener('change', function (e) { var f = e.target.form; if (f && f.querySelector('[data-show-if]')) condApply(f); });
  condApply(document);
  // الجداول على الجوال: جدول قراءة فقط (بلا حقول إدخال) وبعدد أعمدة معقول يُعرض كبطاقات تلقائيًا
  document.querySelectorAll('.content table.table').forEach(function (t) {
    if (t.classList.contains('no-stack') || t.classList.contains('table-cards') || t.querySelector('input:not([type=hidden]),select,textarea')) return;
    var heads = Array.prototype.map.call(t.querySelectorAll('thead th'), function (th) { return th.textContent.trim(); }); if (!heads.length || heads.length > 8) return;
    t.querySelectorAll('tbody tr').forEach(function (tr) { Array.prototype.forEach.call(tr.children, function (td, i) { if (!td.hasAttribute('data-label') && heads[i]) td.setAttribute('data-label', heads[i]); }); });
    t.classList.add('table-cards', 'auto-stack');
  });
  document.addEventListener('click', function (e) { if (e.target.closest('[data-print]')) { e.preventDefault(); window.print(); } });
  document.querySelectorAll('select[data-autosubmit]').forEach(function (s) { if (!s.dataset.bound) { s.dataset.bound = '1'; s.addEventListener('change', function () { s.form.submit(); }); } });
  try { var fl = sessionStorage.getItem('flash'); if (fl) { sessionStorage.removeItem('flash'); setTimeout(function () { toast(fl); }, 150); } } catch (x) {}
  document.addEventListener('input', function (e) { if (e.target.classList && e.target.classList.contains('is-invalid')) { e.target.classList.remove('is-invalid'); var n = e.target.nextElementSibling; if (n && n.classList.contains('field-error')) n.remove(); } });
  var bb = document.getElementById('btnBack');
  if (bb) bb.addEventListener('click', function () { var home = document.querySelector('.crumb-home'); if (history.length > 1 && document.referrer && document.referrer.indexOf(location.origin) === 0) history.back(); else if (home) location.href = home.href; });
  var th = document.getElementById('themeToggle');
  if (th) th.addEventListener('click', function () {
    var cur = document.documentElement.getAttribute('data-theme');
    var dark = cur === 'dark' || (cur === 'auto' && matchMedia('(prefers-color-scheme: dark)').matches);
    var next = dark ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    try { localStorage.setItem('theme', next); } catch (e) {}
  });

  // تأكيد قبل الإجراءات الخطرة: <button data-confirm="نص">
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-confirm]');
    if (b && !window.confirm(b.getAttribute('data-confirm'))) { e.preventDefault(); e.stopPropagation(); }
  }, true);

  // أزرار إجراء مباشر: <button data-api="/api/..." data-method="POST|DELETE" data-confirm="..." data-body='{"k":"v"}'> ثم تحديث الصفحة
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-api]'); if (!b || e.defaultPrevented) return;
    e.preventDefault(); b.classList.add('is-loading');
    var body = b.getAttribute('data-body'); var payload = body ? JSON.parse(body) : {};
    var pr = b.getAttribute('data-prompt'); if (pr) { var ans = window.prompt(pr); if (ans === null) { b.classList.remove('is-loading'); return; } payload[b.getAttribute('data-prompt-field') || 'reason'] = ans; }
    api(b.getAttribute('data-api'), { method: b.getAttribute('data-method') || 'POST', body: payload }).then(function (r) {
      b.classList.remove('is-loading');
      if (!r.ok) { toast((r.error && r.error.message) || 'حدث خطأ', 'danger'); return; }
      if (r.data && r.data.message) { try { sessionStorage.setItem('flash', r.data.message); } catch (x) { toast(r.data.message); } }
      if (r.data && r.data.redirect) { location.href = r.data.redirect; } else { location.reload(); }
    });
  });

  // نماذج AJAX: <form data-ajax data-redirect="/x"> ترسل JSON وتعرض أخطاء الحقول
  document.addEventListener('submit', function (e) {
    var f = e.target; if (!f.hasAttribute('data-ajax')) return;
    e.preventDefault();
    var btn = f.querySelector('[type=submit]'); if (btn) btn.classList.add('is-loading');
    f.querySelectorAll('.field-error').forEach(function (n) { n.remove(); }); f.querySelectorAll('.is-invalid').forEach(function (n) { n.classList.remove('is-invalid'); n.removeAttribute('aria-invalid'); });
    var data = {}; new FormData(f).forEach(function (v, k) { if (k.slice(-2) === '[]') { k = k.slice(0, -2); (data[k] = data[k] || []).push(v); } else { data[k] = v; } });
    var payload = f.hasAttribute('data-multipart') ? new FormData(f) : data;   // data-multipart: يرسل الملفات
    api(f.getAttribute('action') || location.pathname, { method: f.getAttribute('method') || 'POST', body: payload }).then(function (r) {
      if (btn) btn.classList.remove('is-loading');
      if (r.ok) {
        if (r.data && r.data.message) toast(r.data.message);
        var box = document.querySelector('[data-result]');
        if (r.data && r.data.temp_password && box) { window.showSecret(box, r.data); return; }
        var to = (r.data && r.data.redirect) || f.getAttribute('data-redirect');
        if ((to || f.hasAttribute('data-reload')) && r.data && r.data.message) { try { sessionStorage.setItem('flash', r.data.message); } catch (x) {} }
        if (to) { location.href = to; } else if (f.hasAttribute('data-reload')) { location.reload(); } else { f.dispatchEvent(new CustomEvent('ajax:success', { detail: r, bubbles: true })); }
      } else {
        toast((r.error && r.error.message) || 'حدث خطأ', 'danger');
        var fields = (r.error && r.error.fields) || {};
        var first = null;
        Object.keys(fields).forEach(function (k) {
          var inp = f.querySelector('[name="' + k + '"]') || f.querySelector('[name="' + k + '[]"]'); if (!inp) return;
          inp.classList.add('is-invalid'); inp.setAttribute('aria-invalid', 'true'); first = first || inp;
          var d = document.createElement('div'); d.className = 'field-error'; d.textContent = fields[k]; inp.insertAdjacentElement('afterend', d);
        });
        if (first) { first.focus(); if (first.scrollIntoView) first.scrollIntoView({ block: 'center', behavior: 'smooth' }); }
      }
    });
  });

  /** يعرض كلمة مرور مؤقتة مرة واحدة مع زر المتابعة (لا تُخزَّن في أي مكان آخر). */
  window.showSecret = function (box, d) {
    box.classList.remove('d-none'); box.textContent = '';
    var t = document.createElement('div'); t.className = 'fw-bold mb-2'; t.textContent = 'كلمة المرور المؤقتة (تظهر مرة واحدة فقط — سلّمها للمدير؛ سيُطلب منه تغييرها عند أول دخول):'; box.appendChild(t);
    var c = document.createElement('code'); c.className = 'fs-4 d-block mb-2'; c.setAttribute('dir', 'ltr'); c.textContent = d.temp_password; box.appendChild(c);
    if (d.email) { var e = document.createElement('div'); e.className = 'mb-2'; e.textContent = 'البريد: ' + d.email; box.appendChild(e); }
    if (d.redirect) { var a = document.createElement('a'); a.className = 'btn btn-primary'; a.href = d.redirect; a.textContent = 'متابعة'; box.appendChild(a); }
    box.scrollIntoView({ behavior: 'smooth', block: 'center' });
  };

  document.addEventListener('click', function (e) { if (e.target.closest('[data-print]')) window.print(); });

  // Service Worker
  if ('serviceWorker' in navigator) { window.addEventListener('load', function () { navigator.serviceWorker.register(BASE + '/sw.js').catch(function () {}); }); }
})();
// زر إعادة تعيين كلمة مرور من قائمة المستخدمين: يعرض الكلمة المؤقتة مرة واحدة
document.addEventListener('click', function (e) {
  var b = e.target.closest('.reset-pw'); if (!b) return;
  if (!confirm('إعادة تعيين كلمة مرور هذا المستخدم؟')) return;
  api(b.getAttribute('data-url'), { method: 'POST', body: {} }).then(function (r) { var box = document.querySelector('[data-result]'); if (r.ok && box) window.showSecret(box, r.data); else toast((r.error && r.error.message) || 'فشل', 'danger'); });
});

// الدخول للحصة المباشرة: تُفتح نافذة فورًا (لتفادي حجب النوافذ) ثم تُوجَّه للرابط بعد تسجيل الحضور على الخادم.
document.addEventListener('click', function (e) {
  var b = e.target.closest('[data-join]'); if (!b) return;
  var w = window.open('', '_blank'); b.classList.add('is-loading');
  api(b.getAttribute('data-join'), { method: 'POST', body: {} }).then(function (r) {
    b.classList.remove('is-loading');
    if (r.ok && r.data.url) { if (r.data.message) toast(r.data.message); if (w) { w.opener = null; w.location.href = r.data.url; } else { location.href = r.data.url; } if (b.hasAttribute('data-join-reload')) setTimeout(function () { location.reload(); }, 1200); }
    else { if (w) w.close(); toast((r.error && r.error.message) || 'تعذّر الدخول', 'danger'); }
  });
});
