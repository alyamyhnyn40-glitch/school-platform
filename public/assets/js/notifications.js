// جرس الإشعارات: عدّاد غير المقروء + قائمة، تحديث كل دقيقة.
(function () {
  'use strict';
  var btn = document.getElementById('notifBtn'); if (!btn) return;
  var badge = document.getElementById('notifBadge'), menu = document.getElementById('notifMenu'), mbadge = document.getElementById('msgBadge');
  function render(d) {
    var n = d.unread || 0; badge.textContent = n > 99 ? '99+' : n; badge.classList.toggle('d-none', !n);
    if (mbadge) { mbadge.textContent = d.messages || 0; mbadge.classList.toggle('d-none', !d.messages); }
    menu.textContent = ''; if (!d.items.length) { menu.appendChild(el('div', { class: 'p-3 text-muted small', text: 'لا توجد إشعارات' })); return; }
    d.items.forEach(function (i) {
      var a = el('a', { class: 'dropdown-item text-wrap py-2' + (i.read_at ? '' : ' fw-bold'), href: i.url || '#' }); a.appendChild(el('div', { text: i.title })); if (i.body) a.appendChild(el('small', { class: 'text-muted d-block', text: i.body })); menu.appendChild(a);
    });
    var all = el('button', { class: 'dropdown-item text-center text-primary small', type: 'button', text: 'تعليم الكل كمقروء' }); all.onclick = function () { api('/api/v1/notifications/read', { method: 'POST', body: {} }).then(load); }; menu.appendChild(all);
  }
  function load() { api('/api/v1/notifications').then(function (r) { if (r.ok) render(r.data); }); }
  load(); setInterval(load, 60000); document.addEventListener('visibilitychange', function () { if (!document.hidden) load(); });
})();
