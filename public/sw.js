// Service Worker: تخزين الأصول الثابتة مؤقتًا، والشبكة أولًا للصفحات
var CACHE = 'school-v3';
// المسار الأساسي يُستنتج من موقع هذا الملف (يعمل داخل مجلد فرعي مثل /school-platform/public/)
var BASE = self.location.pathname.replace(/sw\.js$/, '');
var SHELL = [BASE + 'offline.html'];
self.addEventListener('install', function (e) { e.waitUntil(caches.open(CACHE).then(function (c) { return c.addAll(SHELL); }).then(function () { return self.skipWaiting(); })); });
self.addEventListener('activate', function (e) {
  e.waitUntil(caches.keys().then(function (ks) { return Promise.all(ks.filter(function (k) { return k !== CACHE; }).map(function (k) { return caches.delete(k); })); }).then(function () { return self.clients.claim(); }));
});
self.addEventListener('fetch', function (e) {
  var req = e.request; if (req.method !== 'GET') return;
  var url = new URL(req.url); if (url.origin !== location.origin) return;
  if (url.pathname.indexOf(BASE + 'assets/') === 0) {
    // الأصول: الكاش أولًا
    e.respondWith(caches.match(req).then(function (hit) {
      return hit || fetch(req).then(function (res) { var cp = res.clone(); caches.open(CACHE).then(function (c) { c.put(req, cp); }); return res; });
    }));
    return;
  }
  if (req.mode === 'navigate') {
    // صفحات الحضور والدرجات تُخزَّن لتعمل دون اتصال (الشبكة أولًا ثم النسخة المخزنة)
    var cacheable = /\/school\/(attendance$|grades\/\d+$)/.test(url.pathname) && url.pathname.indexOf(BASE) === 0;
    e.respondWith(fetch(req).then(function (res) {
      if (cacheable && res.ok) { var cp = res.clone(); caches.open(CACHE).then(function (c) { c.put(req, cp); }); }
      return res;
    }).catch(function () { return caches.match(req).then(function (hit) { return hit || caches.match(BASE + 'offline.html'); }); }));
  }
});
