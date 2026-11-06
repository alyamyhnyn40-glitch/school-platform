// نموذج السؤال: صفوف خيارات ديناميكية (نص + صورة مع معاينة قبل الحفظ + اختيار الإجابة الصحيحة)
(function () {
  'use strict';
  var rows = document.getElementById('optRows'), add = document.getElementById('addOpt'), form = document.getElementById('qForm');
  if (!rows || !add || !form) return;
  var MIN = 2, MAX = 8, n = 0;
  function renumber() {
    var items = rows.querySelectorAll('.opt-row'); items.forEach(function (r, i) {
      r.querySelector('input[type=radio]').value = i; r.querySelector('.opt-no').textContent = (i + 1) + '.';
      r.querySelector('.opt-del').disabled = items.length <= MIN;
    });
    add.disabled = items.length >= MAX;
  }
  function row() {
    n++; var id = 'o' + n, d = document.createElement('div'); d.className = 'opt-row';
    d.innerHTML = '<div class="d-flex gap-2 align-items-start"><div class="pt-2"><input class="form-check-input" type="radio" name="answer_idx" value="0" aria-label="الخيار الصحيح"></div>' +
      '<div class="flex-grow-1"><div class="d-flex gap-2"><span class="opt-no pt-1 fw-bold"></span><textarea class="form-control form-control-sm" name="options[]" rows="1" placeholder="نص الخيار (اختياري إن وُجدت صورة)" data-math="compact" aria-label="نص الخيار"></textarea></div>' +
      '<div class="d-flex gap-2 align-items-center mt-1"><input class="form-control form-control-sm" type="file" name="option_image[]" accept="image/png,image/jpeg,image/webp" data-preview="#' + id + '" aria-label="صورة الخيار"></div><img id="' + id + '" class="q-opt-img" alt="معاينة صورة الخيار" style="display:none"></div>' +
      '<button type="button" class="btn btn-sm btn-outline-danger opt-del" aria-label="حذف الخيار"><i class="bi bi-x-lg"></i></button></div>';
    d.querySelector('.opt-del').addEventListener('click', function () { if (rows.querySelectorAll('.opt-row').length > MIN) { d.remove(); renumber(); } });
    rows.appendChild(d); if (window.MathField) MathField.attach(d.querySelector('textarea')); renumber(); return d;
  }
  add.addEventListener('click', function () { row().querySelector('textarea').focus(); });
  row(); row(); rows.querySelector('input[type=radio]').checked = true;
  // إخفاء/إظهار الخيارات حسب نوع السؤال: الحقول المخفية لا تُرسل أخطاءً لأن الخادم يتجاهلها لغير mcq
  form.addEventListener('submit', function () { var k = document.getElementById('qkind'); if (k && k.value !== 'mcq') { rows.querySelectorAll('textarea,input[type=file]').forEach(function (x) { x.disabled = true; }); setTimeout(function () { rows.querySelectorAll('textarea,input[type=file]').forEach(function (x) { x.disabled = false; }); }, 0); } }, true);
})();
