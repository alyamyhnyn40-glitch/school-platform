(function () {
  'use strict';
  // بعد القبول: تُعرض كلمة المرور المؤقتة مرة واحدة ثم يُعاد تحميل القائمة عند الإغلاق
  document.querySelectorAll('form[data-reload-after]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      e.preventDefault(); e.stopImmediatePropagation(); var b = f.querySelector('button'); b.disabled = true; var body = {}; new FormData(f).forEach(function (v, k) { body[k] = v; });
      api(f.getAttribute('action'), { method: 'POST', body: body }).then(function (r) {
        if (!r.ok) { b.disabled = false; toast((r.error && r.error.message) || 'خطأ', 'danger'); return; }
        var box = document.querySelector('[data-result]');
        box.innerHTML = '<div class="alert alert-success"><strong>' + r.data.message + '</strong><div class="mt-2">كلمة المرور المؤقتة لمدير المدرسة (تظهر <u>مرة واحدة</u>، سلّمها له أو اطلب منه «نسيت كلمة المرور»):</div><code class="fs-5 user-select-all" dir="ltr" id="tmpPw"></code> <button class="btn btn-sm btn-outline-success" id="doneBtn">تم، أعد تحميل القائمة</button></div>';
        document.getElementById('tmpPw').textContent = r.data.temp_password; document.getElementById('doneBtn').addEventListener('click', function () { location.reload(); }); window.scrollTo(0, 0);
      });
    }, true);
  });
})();
