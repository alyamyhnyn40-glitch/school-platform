// تبسيط نموذج الحصص: Jitsi افتراضي، ورابط خارجي اختياري؛ ونسخ الرابط بنقرة
(function () {
  'use strict';
  var sw = document.getElementById('vext'), box = document.getElementById('vextBox'), prov = document.getElementById('vp'), url = document.getElementById('vu');
  if (sw && box) {
    prov.value = 'jitsi';
    sw.addEventListener('change', function () { box.hidden = !sw.checked; if (!sw.checked) { prov.value = 'jitsi'; url.value = ''; } else { prov.value = 'zoom'; } });
    prov.addEventListener('change', function () { if (prov.value === 'jitsi') { url.value = ''; } });
  }
  var start = document.getElementById('vs');
  if (start && !start.value) { var d = new Date(Date.now() + 3600000); d.setMinutes(0, 0, 0); var p = function (x) { return String(x).padStart(2, '0'); }; start.value = d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate()) + 'T' + p(d.getHours()) + ':00'; }
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-copy]'); if (!b) return; var t = b.getAttribute('data-copy');
    var done = function () { toast('نُسخ الرابط'); };
    if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(t).then(done, function () { window.prompt('انسخ الرابط:', t); }); else window.prompt('انسخ الرابط:', t);
  });
})();
