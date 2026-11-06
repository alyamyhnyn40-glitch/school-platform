(function () {
  'use strict';
  var secSel = document.getElementById('section'), dateIn = document.getElementById('date'), perSel = document.getElementById('period');
  var box = document.getElementById('roster'), saveBtn = document.getElementById('saveBtn'), allBtn = document.getElementById('allPresent'), badge = document.getElementById('pendingBadge'), failBox = document.getElementById('failedOps'), info = document.getElementById('rosterInfo');
  if (!secSel) return;
  var STAT = [['present', 'حاضر', 'success'], ['absent', 'غائب', 'danger'], ['late', 'متأخر', 'warning'], ['excused', 'بعذر', 'info']];
  var current = null;
  function uuid() { return (window.crypto && crypto.randomUUID) ? crypto.randomUUID() : 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) { var r = Math.random() * 16 | 0; return (c === 'x' ? r : (r & 3 | 8)).toString(16); }); }
  function key(s, d, p) { return 'roster|' + s + '|' + d + '|' + p; }

  function render(d, fromCache) {
    current = d; box.textContent = '';
    info.textContent = fromCache ? 'نسخة مخزنة على الجهاز (لا يوجد اتصال)' : (d.taken ? 'سُجّل الحضور لهذا اليوم — يمكنك التعديل' : 'لم يُسجَّل الحضور بعد');
    if (!d.students.length) { box.appendChild(el('div', { class: 'empty-state', text: 'لا يوجد طلاب في هذه الشعبة' })); saveBtn.disabled = true; allBtn.disabled = true; return; }
    saveBtn.disabled = false; allBtn.disabled = false;
    var table = el('table', { class: 'table align-middle table-cards' }); table.appendChild(el('thead', {}, [el('tr', {}, [el('th', { text: '#' }), el('th', { text: 'الطالب' }), el('th', { text: 'الحالة' }), el('th', { text: 'ملاحظة' })])]));
    var tb = el('tbody');
    d.students.forEach(function (s, i) {
      var g = el('div', { class: 'btn-group att-group', role: 'group', 'aria-label': 'حالة ' + s.full_name });
      STAT.forEach(function (st) {
        var id = 'r' + s.enrollment_id + st[0];
        var inp = el('input', { type: 'radio', class: 'btn-check', name: 'st' + s.enrollment_id, id: id, value: st[0], 'data-enr': s.enrollment_id }); if (s.status === st[0]) inp.checked = true;
        g.appendChild(inp); g.appendChild(el('label', { class: 'btn btn-outline-' + st[2], for: id, text: st[1] }));
      });
      var note = el('input', { class: 'form-control form-control-sm', placeholder: 'ملاحظة', 'aria-label': 'ملاحظة ' + s.full_name, 'data-note': s.enrollment_id, maxlength: 255, value: s.note || '' });
      tb.appendChild(el('tr', {}, [el('td', { 'data-label': '#', text: String(i + 1) }), el('td', { 'data-label': 'الطالب', text: s.full_name }), el('td', { 'data-label': 'الحالة' }, [g]), el('td', { 'data-label': 'ملاحظة' }, [note])]));
    });
    table.appendChild(tb); box.appendChild(table);
  }

  function load() {
    var s = secSel.value, d = dateIn.value, p = perSel.value; if (!s || !d) return;
    box.textContent = 'جارٍ التحميل…';
    api('/api/v1/school/attendance/roster?section_id=' + s + '&date=' + d + '&period=' + p).then(function (r) {
      if (r.ok) { Offline.cachePut(key(s, d, p), r.data); render(r.data, false); return; }
      if (r.status === 0) { Offline.cacheGet(key(s, d, p)).then(function (c) { if (c) render(c, true); else { box.textContent = ''; box.appendChild(el('div', { class: 'alert alert-warning', text: 'لا يوجد اتصال ولا نسخة مخزنة لهذه الشعبة/التاريخ. افتح الصفحة مرة أثناء الاتصال أولًا.' })); } }); return; }
      box.textContent = ''; box.appendChild(el('div', { class: 'alert alert-danger', text: (r.error && r.error.message) || 'تعذر التحميل' }));
    });
  }

  allBtn.addEventListener('click', function () { box.querySelectorAll('input[value=present]').forEach(function (i) { i.checked = true; }); });
  saveBtn.addEventListener('click', function () {
    if (!current) return;
    var recs = [];
    current.students.forEach(function (s) {
      var c = box.querySelector('input[name=st' + s.enrollment_id + ']:checked'); if (!c) return;
      var note = box.querySelector('[data-note="' + s.enrollment_id + '"]').value;
      recs.push({ enrollment_id: s.enrollment_id, status: c.value, note: note, client_uuid: uuid() });
    });
    var missing = current.students.length - recs.length;
    if (!recs.length) { toast('اختر حالة طالب واحد على الأقل', 'warning'); return; }
    if (missing && !confirm('لم تُحدَّد حالة ' + missing + ' طالب. حفظ المحدَّد فقط؟')) return;
    var body = { section_id: +secSel.value, date: dateIn.value, period: +perSel.value, records: recs };
    saveBtn.classList.add('is-loading');
    api('/api/v1/school/attendance', { method: 'POST', body: body }).then(function (r) {
      saveBtn.classList.remove('is-loading');
      if (r.ok) { toast('تم الحفظ'); load(); return; }
      if (r.status === 0) {   // لا اتصال: خزّن محليًا وحدّث النسخة المخزنة
        Offline.queue('attendance', body).then(function () {
          recs.forEach(function (x) { current.students.forEach(function (s) { if (s.enrollment_id === x.enrollment_id) { s.status = x.status; s.note = x.note; } }); }); current.taken = true;
          Offline.cachePut(key(secSel.value, dateIn.value, perSel.value), current); toast('لا يوجد اتصال — حُفظ على الجهاز وسيُرسل تلقائيًا عند عودة الشبكة', 'warning');
        });
      } else { toast((r.error && r.error.message) || 'فشل الحفظ', 'danger'); }
    });
  });

  function showPending(ops) {
    var waiting = ops.filter(function (o) { return !o.error; }), failed = ops.filter(function (o) { return o.error; });
    badge.textContent = waiting.length ? 'بانتظار المزامنة: ' + waiting.length : ''; badge.classList.toggle('d-none', !waiting.length);
    failBox.textContent = '';
    failed.forEach(function (o) {
      var b = el('button', { class: 'btn btn-sm btn-outline-danger ms-2', type: 'button', text: 'تجاهل' }); b.onclick = function () { Offline.remove(o.id); };
      failBox.appendChild(el('div', { class: 'alert alert-danger py-2' }, [document.createTextNode('تعذّرت مزامنة حضور ' + (o.body.date || '') + ': ' + o.error), b]));
    });
  }
  Offline.onChange(showPending);

  [secSel, dateIn, perSel].forEach(function (x) { x.addEventListener('change', load); });
  document.getElementById('loadBtn').addEventListener('click', load);
  // تحميل مسبق لكشوف اليوم لكل الشعب كي تتوفر دون اتصال
  function prefetch() {
    if (!navigator.onLine) return; var d = dateIn.value;
    Array.prototype.forEach.call(secSel.options, function (o) { if (!o.value) return; api('/api/v1/school/attendance/roster?section_id=' + o.value + '&date=' + d + '&period=0').then(function (r) { if (r.ok) Offline.cachePut(key(o.value, d, '0'), r.data); }); });
  }
  try { var last = localStorage.getItem('att.section'); if (last && secSel.querySelector('option[value="' + last + '"]')) secSel.value = last; } catch (e) {}
  secSel.addEventListener('change', function () { try { localStorage.setItem('att.section', secSel.value); } catch (e) {} });
  if (secSel.value) load(); prefetch();
  window.addEventListener('online', function () { load(); });
})();
