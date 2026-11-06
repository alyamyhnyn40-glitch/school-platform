(function () {
  'use strict';
  var c = document.getElementById('trend'); if (!c || !window.Chart) return;
  var d = JSON.parse(c.getAttribute('data-trend') || '[]'); if (!d.length) { c.replaceWith(el('div', { class: 'empty-state', text: 'لا توجد بيانات للرسم' })); return; }
  new Chart(c, { type: 'line', data: { labels: d.map(function (x) { return x.d; }), datasets: [{ label: 'نسبة الحضور %', data: d.map(function (x) { return +x.pct; }), borderColor: '#0f766e', backgroundColor: 'rgba(15,118,110,.15)', fill: true, tension: .25 }] },
    options: { responsive: true, maintainAspectRatio: false, scales: { y: { min: 0, max: 100 } }, plugins: { legend: { display: false } } } });
})();
