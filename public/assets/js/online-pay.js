(function () {
  'use strict';
  var d = document.getElementById('drv'), box = document.getElementById('httpBox');
  if (d && box) d.addEventListener('change', function () { box.hidden = d.value !== 'http'; });
})();
