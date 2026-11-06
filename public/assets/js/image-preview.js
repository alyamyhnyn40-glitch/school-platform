// معاينة محلية للصورة قبل الحفظ: <input type=file data-preview="#imgId"> (بلا رفع للخادم ولا CDN)
(function () {
  'use strict';
  document.addEventListener('change', function (e) {
    var inp = e.target; if (!inp.matches || !inp.matches('input[type=file][data-preview]')) return;
    var img = document.querySelector(inp.getAttribute('data-preview')); var f = inp.files && inp.files[0]; if (!img) return;
    if (!f) { img.style.display = 'none'; return; }
    if (!/^image\/(png|jpeg|webp)$/.test(f.type)) { img.style.display = 'none'; if (window.toast) toast('الصيغ المسموحة: PNG وJPG وWebP', 'warning'); inp.value = ''; return; }
    var r = new FileReader(); r.onload = function () { img.src = r.result; img.style.display = ''; }; r.readAsDataURL(f);
  });
})();
