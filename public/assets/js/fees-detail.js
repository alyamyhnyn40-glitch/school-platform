(function () {
  'use strict';
  var f = document.getElementById('payForm'); if (!f) return;
  var feeCur = f.getAttribute('data-fee-currency'), base = f.getAttribute('data-base'), rates = JSON.parse(f.getAttribute('data-rates')), bal = parseFloat(f.getAttribute('data-balance'));
  var amount = document.getElementById('amount'), cur = document.getElementById('currency'), rateRow = document.getElementById('rateRow'), rate = document.getElementById('rate'), info = document.getElementById('convInfo'), method = document.getElementById('method_id'), ref = document.getElementById('reference_no'), hint = document.getElementById('refHint');
  function r(c) { return c === base ? 1 : parseFloat(rates[c]); }
  function fmt(x) { return x.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
  function refresh() {
    var c = cur.value; document.getElementById('rateCur').textContent = c;
    if (c === base) { rateRow.classList.add('d-none'); rate.value = ''; } else { rateRow.classList.remove('d-none'); if (!rate.value || rate.dataset.cur !== c) { rate.value = rates[c] || ''; rate.dataset.cur = c; } }
    var a = parseFloat(amount.value), rp = c === base ? 1 : parseFloat(rate.value), rf = r(feeCur);
    if (c !== feeCur && a > 0 && rp > 0 && rf > 0) { var applied = a * rp / rf; info.textContent = '≈ ' + fmt(applied) + ' ' + feeCur + ' من الرسوم' + (applied > bal + 0.005 ? ' — يتجاوز المتبقي!' : ''); info.className = 'small ' + (applied > bal + 0.005 ? 'text-danger' : 'text-muted'); }
    else if (c !== feeCur && (!rp || !rf)) { info.textContent = 'حدّد سعر الصرف'; info.className = 'small text-danger'; } else { info.textContent = ''; }
  }
  function kind() { var o = method.options[method.selectedIndex]; var nc = o && o.getAttribute('data-kind') !== 'cash'; hint.textContent = nc ? '(مطلوب)' : '(اختياري)'; ref.required = nc; }
  [amount, cur, rate].forEach(function (x) { x.addEventListener('input', refresh); }); cur.addEventListener('change', refresh); method.addEventListener('change', kind);
  document.getElementById('fillBal').addEventListener('click', function () { var c = cur.value; var v = c === feeCur ? bal : bal * r(feeCur) / (c === base ? 1 : parseFloat(rate.value || rates[c] || 0)); amount.value = isFinite(v) ? (Math.ceil(v * 100) / 100).toFixed(2) : ''; refresh(); });
  refresh(); kind();
  f.addEventListener('ajax:success', function (e) {
    var d = e.detail.data, box = f.querySelector('[data-result]'); box.classList.remove('d-none'); box.textContent = '';
    box.appendChild(el('div', { class: 'fw-bold', text: 'سند ' + d.receipt_no + ' — المتبقي ' + fmt(parseFloat(d.balance)) + ' ' + feeCur }));
    var a = el('a', { class: 'btn btn-sm btn-primary mt-2', href: d.print, target: '_blank', text: 'طباعة السند' }); var b = el('button', { class: 'btn btn-sm btn-outline-secondary mt-2 ms-2', type: 'button', text: 'تحديث الصفحة' }); b.onclick = function () { location.reload(); };
    box.appendChild(a); box.appendChild(b); f.querySelector('button[type=submit]').disabled = true;
  });
})();
