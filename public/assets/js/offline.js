// طبقة العمل دون اتصال: طابور عمليات + كاش بيانات في IndexedDB، ومزامنة تلقائية عند عودة الاتصال.
(function () {
  'use strict';
  var db = null, listeners = [], flushing = false;
  function open() {
    return new Promise(function (res, rej) {
      if (db) return res(db);
      if (!window.indexedDB) return rej(new Error('IndexedDB غير مدعوم'));
      var r = indexedDB.open('school-offline', 1);
      r.onupgradeneeded = function () { r.result.createObjectStore('ops', { keyPath: 'id', autoIncrement: true }); r.result.createObjectStore('cache', { keyPath: 'key' }); };
      r.onsuccess = function () { db = r.result; res(db); }; r.onerror = function () { rej(r.error); };
    });
  }
  function run(store, mode, fn) {
    return open().then(function (d) {
      return new Promise(function (res, rej) {
        var t = d.transaction(store, mode), req = fn(t.objectStore(store));
        t.oncomplete = function () { res(req ? req.result : undefined); }; t.onerror = function () { rej(t.error); }; t.onabort = function () { rej(t.error); };
      });
    });
  }
  function notify() { Offline.pending().then(function (ops) { listeners.forEach(function (f) { try { f(ops); } catch (e) {} }); }); }
  var ENDPOINT = { attendance: '/api/v1/school/attendance/sync', grades: '/api/v1/school/grades/sync' };

  var Offline = window.Offline = {
    onChange: function (f) { listeners.push(f); notify(); },
    cachePut: function (key, value) { return run('cache', 'readwrite', function (s) { return s.put({ key: key, value: value, at: Date.now() }); }).catch(function () {}); },
    cacheGet: function (key) { return run('cache', 'readonly', function (s) { return s.get(key); }).then(function (r) { return r ? r.value : null; }).catch(function () { return null; }); },
    queue: function (kind, body) { return run('ops', 'readwrite', function (s) { return s.add({ kind: kind, body: body, at: Date.now(), error: null }); }).then(function () { notify(); }); },
    pending: function () { return run('ops', 'readonly', function (s) { return s.getAll(); }).catch(function () { return []; }); },
    remove: function (id) { return run('ops', 'readwrite', function (s) { return s.delete(id); }).then(notify); },
    mark: function (op, msg) { op.error = msg; return run('ops', 'readwrite', function (s) { return s.put(op); }); },
    /** يرسل العمليات المعلّقة. العملية الناجحة تُحذف؛ والمرفوضة (4xx) تبقى مع رسالة الخطأ ليراجعها المستخدم. */
    flush: function () {
      if (flushing || !navigator.onLine) return Promise.resolve();
      flushing = true;
      return Offline.pending().then(function (ops) {
        ops = ops.filter(function (o) { return !o.error; });
        if (!ops.length) return;
        return api('/api/v1/csrf').then(function (t) {
          if (t.ok) { var m = document.querySelector('meta[name=csrf-token]'); if (m) m.content = t.data.token; } else if (t.status === 0 || t.status === 401) { throw new Error('offline'); }
          var kinds = {}; ops.forEach(function (o) { (kinds[o.kind] = kinds[o.kind] || []).push(o); });
          return Promise.all(Object.keys(kinds).map(function (k) {
            var list = kinds[k];
            return api(ENDPOINT[k], { method: 'POST', body: { ops: list.map(function (o) { return o.body; }) } }).then(function (r) {
              if (!r.ok) { if (r.status === 0) throw new Error('offline'); return Promise.all(list.map(function (o) { return Offline.mark(o, (r.error && r.error.message) || 'رُفضت العملية'); })); }
              return Promise.all(r.data.map(function (res, i) { return res.ok ? Offline.remove(list[i].id) : Offline.mark(list[i], res.message || 'رُفضت العملية'); }));
            });
          }));
        });
      }).catch(function () {}).then(function () { flushing = false; notify(); });
    }
  };
  window.addEventListener('online', function () { Offline.flush(); });
  setInterval(function () { Offline.flush(); }, 30000);
  window.addEventListener('load', function () { setTimeout(Offline.flush, 800); });
})();
