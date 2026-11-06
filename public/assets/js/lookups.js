// قوائم مترابطة: المدينة تتصفى بحسب المحافظة (بيانات من data-geo-map على body أو من ملف JSON مضمّن)
(function () {
  'use strict';
  var map = null;
  function load(cb) { if (map) return cb(); var el = document.getElementById('geoMap'); if (el) { try { map = JSON.parse(el.textContent); } catch (e) { map = {}; } } else { map = {}; } cb(); }
  function fill(gov) {
    var id = gov.getAttribute('data-geo-gov'), list = document.getElementById(id + 'l'); if (!list) return;
    list.textContent = ''; (map[gov.value] || []).forEach(function (c) { var o = document.createElement('option'); o.value = c; list.appendChild(o); });
  }
  load(function () {
    document.querySelectorAll('select[data-geo-gov]').forEach(function (g) { fill(g); g.addEventListener('change', function () { fill(g); }); });
  });
})();
