(function () {
  'use strict';
  document.querySelectorAll('[data-toggle-module]').forEach(function (b) {
    b.addEventListener('click', function () {
      var boxes = Array.prototype.filter.call(b.closest('.card-s').querySelectorAll('input[type=checkbox]'), function (c) { return !c.disabled; });
      var all = boxes.every(function (c) { return c.checked; }); boxes.forEach(function (c) { c.checked = !all; });
    });
  });
})();
