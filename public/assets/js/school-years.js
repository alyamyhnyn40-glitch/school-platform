(function () {
  'use strict';
  var box = document.getElementById('promoBox'); if (!box) return;
  var res = document.getElementById('promoResult');
  var from = document.getElementById('promoFrom'), to = document.getElementById('promoTo');
  function decisions() { var d = {}; res.querySelectorAll('select[data-enr]').forEach(function (s) { d[s.getAttribute('data-enr')] = s.value; }); return d; }
  document.getElementById('promoClone').addEventListener('click', function () {
    api(box.getAttribute('data-clone') + to.value + '/clone-structure', { method: 'POST', body: { from: from.value } }).then(function (r) { toast(r.ok ? r.data.message : r.error.message, r.ok ? 'success' : 'danger'); });
  });
  function preview() {
    api(box.getAttribute('data-url-preview'), { method: 'POST', body: { from: from.value, to: to.value, decisions: decisions() } }).then(function (r) {
      if (!r.ok) { toast(r.error.message, 'danger'); return; }
      var d = r.data; res.classList.remove('d-none'); res.textContent = '';
      var c = d.counts; res.appendChild(el('h3', { class: 'h6', text: 'معاينة الترفيع: ' + c.promote + ' ترفيع، ' + c.repeat + ' إعادة، ' + c.graduate + ' تخريج، ' + c.withdraw + ' انسحاب' }));
      if (d.unmapped.length) { res.appendChild(el('div', { class: 'alert alert-danger', text: 'لا توجد شعبة مستهدفة لـ ' + d.unmapped.length + ' طالب (مثال: ' + d.unmapped[0].student + '). انسخ الهيكل أو أضف شعبًا/سعة في السنة الجديدة.' })); }
      var t = el('table', { class: 'table table-sm table-cards' }); t.appendChild(el('thead', {}, [el('tr', {}, [el('th', { text: 'الطالب' }), el('th', { text: 'الشعبة الحالية' }), el('th', { text: 'القرار' })])]));
      var tb = el('tbody');
      d.plan.forEach(function (p) {
        var sel = el('select', { class: 'form-select form-select-sm', 'data-enr': p.enrollment_id, 'aria-label': 'قرار ' + p.full_name });
        [['promote', 'ترفيع'], ['repeat', 'إعادة'], ['withdraw', 'انسحاب']].forEach(function (o) { var op = el('option', { value: o[0], text: o[1] }); if ((p.decision === 'graduate' ? 'promote' : p.decision) === o[0]) op.selected = true; sel.appendChild(op); });
        sel.addEventListener('change', preview);
        tb.appendChild(el('tr', {}, [el('td', { 'data-label': 'الطالب', text: p.full_name }), el('td', { 'data-label': 'الشعبة', text: p.section_name }), el('td', { 'data-label': 'القرار' }, [sel])]));
      });
      t.appendChild(tb); res.appendChild(t);
      var go = el('button', { class: 'btn btn-danger', type: 'button', text: 'تنفيذ الترفيع وإغلاق السنة الحالية' }); go.disabled = d.unmapped.length > 0 || !d.plan.length;
      go.addEventListener('click', function () {
        if (!confirm('سيتم الترفيع وإغلاق السنة الحالية (للقراءة فقط) وتفعيل السنة الجديدة. لا يمكن التراجع. متابعة؟')) return;
        api(box.getAttribute('data-url-run'), { method: 'POST', body: { from: from.value, to: to.value, decisions: decisions() } }).then(function (r2) { if (r2.ok) { toast(r2.data.message); setTimeout(function () { location.reload(); }, 800); } else toast(r2.error.message, 'danger'); });
      });
      res.appendChild(go);
    });
  }
  document.getElementById('promoPreview').addEventListener('click', preview);
})();
