(function () {
  'use strict';
  var k = document.getElementById('qkind');
  if (k) { var upd = function () { document.querySelectorAll('#qForm [data-kind]').forEach(function (d) { d.classList.toggle('d-none', d.getAttribute('data-kind') !== k.value); }); }; k.addEventListener('change', upd); upd(); }
  var mk = document.getElementById('mk');
  if (mk) mk.addEventListener('change', function () { document.getElementById('mfile').classList.toggle('d-none', mk.value !== 'file'); document.getElementById('murl').classList.toggle('d-none', mk.value === 'file'); });
})();
