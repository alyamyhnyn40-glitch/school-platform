(function () {
  'use strict';
  var panel = document.getElementById('astPanel'), fab = document.getElementById('astFab');
  if (!panel || !fab) return;
  var body = document.getElementById('astBody'), form = document.getElementById('astForm'), input = document.getElementById('astInput'), chips = document.getElementById('astChips');
  var KEY = 'ast_hist', hist = [], busy = false;
  try { hist = JSON.parse(sessionStorage.getItem('ast_hist') || '[]') || []; } catch (e) { hist = []; }
  function save() { try { sessionStorage.setItem(KEY, JSON.stringify(hist.slice(-12))); } catch (e) {} }
  function csrf() { var m = document.querySelector('meta[name=csrf-token]'); return m ? m.content : panel.dataset.csrf; }
  // تنسيق مبسّط آمن: **غامق** وأسطر جديدة فقط، عبر عقد DOM (بلا innerHTML)
  function fmt(el, text) {
    String(text).split('\n').forEach(function (line, i) {
      if (i) el.appendChild(document.createElement('br'));
      line.split(/(\*\*[^*]+\*\*)/).forEach(function (p) {
        if (/^\*\*[^*]+\*\*$/.test(p)) { var b = document.createElement('strong'); b.textContent = p.slice(2, -2); el.appendChild(b); }
        else if (p) el.appendChild(document.createTextNode(p));
      });
    });
  }
  function add(role, text, links) {
    var d = document.createElement('div'); d.className = 'ast-msg ast-' + role; fmt(d, text);
    if (links && links.length) {
      var w = document.createElement('div'); w.className = 'ast-links';
      links.forEach(function (l) { if (!l.url || (l.url.charAt(0) !== '/' && l.url.indexOf(location.origin + '/') !== 0)) return; var a = document.createElement('a'); a.href = l.url; a.textContent = l.title || l.url; w.appendChild(a); });
      if (w.children.length) d.appendChild(w);
    }
    body.appendChild(d); body.scrollTop = body.scrollHeight; return d;
  }
  function welcome() { add('bot', 'مرحبًا! أنا مساعد المنصة. اسألني عن أي خطوة أو صفحة، أو اختر سؤالًا من الأسفل.'); }
  function render() { body.textContent = ''; if (!hist.length) { welcome(); } hist.forEach(function (h) { add(h.role === 'user' ? 'me' : 'bot', h.content, h.links); }); }
  function open(v) { panel.hidden = !v; fab.setAttribute('aria-expanded', v ? 'true' : 'false'); if (v) { render(); input.focus(); } }
  fab.addEventListener('click', function () { open(panel.hidden); });
  document.getElementById('astClose').addEventListener('click', function () { open(false); fab.focus(); });
  document.getElementById('astClear').addEventListener('click', function () { hist = []; save(); render(); chips.hidden = false; });
  panel.addEventListener('keydown', function (e) { if (e.key === 'Escape') { open(false); fab.focus(); } });
  chips.addEventListener('click', function (e) { if (e.target.classList.contains('ast-chip')) { send(e.target.textContent); } });
  form.addEventListener('submit', function (e) { e.preventDefault(); send(input.value); });
  function send(q) {
    q = (q || '').trim(); if (busy || q.length < 2) return; busy = true; input.value = ''; chips.hidden = true;
    var h0 = hist.map(function (h) { return { role: h.role, content: h.content }; });
    hist.push({ role: 'user', content: q }); add('me', q); var wait = add('bot', '…'); wait.classList.add('ast-wait');
    fetch(panel.dataset.ask, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': csrf() },
      body: JSON.stringify({ message: q, history: h0, path: location.pathname }) })
      .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
      .then(function (x) {
        wait.remove();
        if (!x.ok) { var m = (x.j && x.j.message) || 'تعذّر الحصول على إجابة'; add('bot', m); hist.pop(); }
        else { var d = x.j.data || x.j; hist.push({ role: 'assistant', content: d.answer, links: d.links || [] }); add('bot', d.answer, d.links); }
        save();
      })
      .catch(function () { wait.remove(); add('bot', 'تعذّر الاتصال بالخادم.'); hist.pop(); })
      .then(function () { busy = false; input.focus(); });
  }
})();
