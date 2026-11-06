(function () {
  'use strict';
  var day = document.getElementById('slotDay'), per = document.getElementById('slotPeriod'), lbl = document.getElementById('slotLabel'), sub = document.getElementById('slotSubmit');
  document.addEventListener('click', function (e) {
    var b = e.target.closest('.slot-add'); if (!b) return;
    document.querySelectorAll('.slot-add.active').forEach(function (x) { x.classList.remove('active', 'btn-primary'); x.classList.add('btn-outline-primary'); });
    b.classList.add('active', 'btn-primary'); b.classList.remove('btn-outline-primary');
    day.value = b.getAttribute('data-day'); per.value = b.getAttribute('data-period'); lbl.textContent = b.getAttribute('data-label'); lbl.className = 'alert alert-primary py-2 small'; sub.disabled = false;
    document.getElementById('ssel').focus();
  });
})();
