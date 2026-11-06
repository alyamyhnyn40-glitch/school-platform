(function () {
  'use strict';
  var sel = document.getElementById('term'); if (!sel || !window.Chart) return;
  var url = sel.getAttribute('data-analytics'), charts = {};
  var colors = ['#0f766e', '#2563eb', '#d97706', '#9333ea', '#dc2626', '#0891b2', '#65a30d', '#be185d'];
  function draw(id, cfg) { if (charts[id]) charts[id].destroy(); charts[id] = new Chart(document.getElementById(id), cfg); }
  function load() {
    api(url + '?term=' + encodeURIComponent(sel.value)).then(function (r) {
      if (!r.ok) return; var d = r.data;
      if (d.subjects) {
        var s = d.subjects.subjects; document.getElementById('subEmpty').textContent = s.length ? '' : 'لا درجات معتمدة في هذا الفصل بعد.';
        draw('chSub', { type: 'bar', data: { labels: s.map(function (x) { return x.subject; }), datasets: [{ label: 'المتوسط %', data: s.map(function (x) { return x.average; }), backgroundColor: colors[0] }, { label: 'نسبة النجاح %', data: s.map(function (x) { return x.pass_rate; }), backgroundColor: colors[1] }] }, options: { scales: { y: { min: 0, max: 100 } } } });
      }
      if (d.corr) {
        document.getElementById('corrNote').textContent = (d.corr.r === null ? '' : 'معامل الارتباط r = ' + d.corr.r + ' (n=' + d.corr.n + ') — ') + d.corr.note;
        draw('chCorr', { type: 'scatter', data: { datasets: [{ label: 'طالب', data: d.corr.points.map(function (p) { return { x: p.attendance, y: p.grade }; }), backgroundColor: colors[0] }] }, options: { scales: { x: { title: { display: true, text: 'الحضور %' }, min: 0, max: 100 }, y: { title: { display: true, text: 'التحصيل %' }, min: 0, max: 100 } } } });
      }
      var t = d.trends; document.getElementById('trendNote').textContent = t.note;
      draw('chTrend', { type: 'line', data: { labels: t.terms, datasets: t.sections.map(function (s, i) { return { label: s.section, data: s.series, borderColor: colors[i % colors.length], spanGaps: true, tension: .2 }; }) }, options: { scales: { y: { min: 0, max: 100 } } } });
      var p = t.sections.filter(function (s) { return s.projection !== null; });
      document.getElementById('proj').textContent = p.length ? 'توقع استرشادي للفصل القادم: ' + p.map(function (s) { return s.section + ' ≈ ' + s.projection + '%'; }).join('، ') : '';
    });
  }
  sel.addEventListener('change', load); load();
})();
