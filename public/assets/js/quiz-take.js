// حل الاختبار: حفظ تلقائي لكل إجابة، مؤقت من وقت الخادم، وتسليم تلقائي عند انتهاء الوقت. الإجابات الصحيحة لا تصل المتصفح أصلًا.
(function () {
  'use strict';
  var f = document.getElementById('takeForm'); if (!f) return;
  var state = document.getElementById('saveState'), timer = document.getElementById('timer'), done = false, pending = 0;
  function val(b) {
    var kind = b.getAttribute('data-kind'), id = b.getAttribute('data-qid');
    if (kind === 'mcq' || kind === 'true_false') { var c = b.querySelector('input[type=radio]:checked'); return c ? c.value : null; }
    return b.querySelector('input,textarea').value;
  }
  function save(b) {
    if (done) return; pending++; state.textContent = 'جارٍ الحفظ…';
    api(f.getAttribute('data-answer-url'), { method: 'POST', body: { question_id: +b.getAttribute('data-qid'), answer: val(b) } }).then(function (r) {
      pending--; if (r.ok) { state.textContent = '✔ حُفظ'; state.className = 'small text-success'; }
      else if (r.status === 0) { state.textContent = 'لا اتصال — أعد المحاولة'; state.className = 'small text-danger'; }
      else { state.textContent = (r.error && r.error.message) || 'خطأ'; state.className = 'small text-danger'; if (r.status === 422) finish(true); }
    });
  }
  var t = {}; f.querySelectorAll('.qblock').forEach(function (b) {
    b.addEventListener('change', function () { save(b); });
    b.addEventListener('input', function (e) { if (e.target.tagName === 'INPUT' && e.target.type === 'radio') return; clearTimeout(t[b.getAttribute('data-qid')]); t[b.getAttribute('data-qid')] = setTimeout(function () { save(b); }, 800); });
  });
  function finish(auto) {
    if (done) return; done = true;
    api(f.getAttribute('data-submit-url'), { method: 'POST', body: {} }).then(function (r) { if (r.ok) { location.href = r.data.redirect; } else { done = false; toast((r.error && r.error.message) || 'تعذّر التسليم', 'danger'); if (auto) setTimeout(function () { location.reload(); }, 1500); } });
  }
  f.addEventListener('submit', function (e) { e.preventDefault(); if (confirm('تسليم الاختبار الآن؟ لا يمكنك التعديل بعد التسليم.')) { f.querySelectorAll('.qblock').forEach(save); setTimeout(function () { finish(false); }, 400); } });
  if (timer) {
    var end = Date.now() + (+timer.getAttribute('data-remaining')) * 1000;
    (function tick() {
      var left = Math.max(0, Math.round((end - Date.now()) / 1000)), m = Math.floor(left / 60), s = left % 60;
      timer.textContent = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s; if (left <= 60) timer.classList.add('blink');
      if (left <= 0) { toast('انتهى الوقت — يجري التسليم', 'warning'); finish(true); return; } setTimeout(tick, 1000);
    })();
  }
  window.addEventListener('beforeunload', function (e) { if (!done && pending > 0) { e.preventDefault(); e.returnValue = ''; } });
})();
