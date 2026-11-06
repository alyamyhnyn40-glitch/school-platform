(function () {
  'use strict';
  var all = document.getElementById('all');
  if (all) all.addEventListener('change', function () { document.querySelectorAll('.pick').forEach(function (c) { c.checked = all.checked; }); });
  document.querySelectorAll('.remind-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var ids = []; document.querySelectorAll('.pick:checked').forEach(function (c) { ids.push(+c.value); });
      if (!ids.length) { toast('حدّد طالبًا واحدًا على الأقل', 'warning'); return; }
      var ch = btn.getAttribute('data-channel'), name = ch === 'whatsapp' ? 'واتساب' : 'SMS';
      if (!confirm('إضافة رسائل تذكير ' + name + ' لأولياء أمور ' + ids.length + ' طالب إلى طابور الإرسال؟')) return;
      api(btn.getAttribute('data-url'), { method: 'POST', body: { fee_ids: ids, channel: ch } }).then(function (r) { toast(r.ok ? r.data.message : ((r.error && r.error.message) || 'خطأ'), r.ok ? 'success' : 'danger'); });
    });
  });
})();
