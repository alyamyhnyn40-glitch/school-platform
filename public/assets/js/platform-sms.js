(function () {
  'use strict';
  var d = document.getElementById('drv') || document.getElementById('prov'), box = document.getElementById('httpBox') || document.getElementById('aiBox');
  if (d && box) d.addEventListener('change', function () { box.hidden = !(d.value === 'http' || d.value === 'smtp' || d.value === 'anthropic'); });
})();
