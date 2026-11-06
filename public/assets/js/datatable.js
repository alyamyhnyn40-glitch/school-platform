// جدول AJAX عام: بحث/فلترة/ترقيم من الخادم. الاستخدام: new DataTable({root, endpoint, columns, params})
(function () {
  'use strict';
  window.DataTable = function (cfg) {
    var root = cfg.root, page = 1, timer = null, self = this;
    root.textContent = '';
    var wrap = el('div', { class: 'table-responsive' });
    var table = el('table', { class: 'table table-hover align-middle table-cards' });
    var thead = el('thead'), tr = el('tr');
    cfg.columns.forEach(function (c) { tr.appendChild(el('th', { text: c.label })); });
    thead.appendChild(tr); table.appendChild(thead);
    var tbody = el('tbody'); table.appendChild(tbody); wrap.appendChild(table);
    var pager = el('nav', { class: 'd-flex justify-content-between align-items-center mt-2 flex-wrap gap-2', 'aria-label': 'ترقيم الصفحات' });
    root.appendChild(wrap); root.appendChild(pager);

    this.load = function (p) {
      page = p || 1; root.classList.add('opacity-50');
      var qs = new URLSearchParams(Object.assign({ page: page }, cfg.params ? cfg.params() : {}));
      api(cfg.endpoint + '?' + qs.toString()).then(function (r) {
        root.classList.remove('opacity-50'); tbody.textContent = ''; pager.textContent = '';
        if (!r.ok) { toast((r.error && r.error.message) || 'تعذر التحميل', 'danger'); return; }
        if (!r.data.length) {
          var td = el('td', { colspan: cfg.columns.length, class: 'empty-state' }); td.appendChild(el('i', { class: 'bi bi-inbox' })); td.appendChild(document.createTextNode(cfg.empty || 'لا توجد بيانات'));
          tbody.appendChild(el('tr', {}, [td])); return;
        }
        r.data.forEach(function (row) {
          var tr = el('tr');
          cfg.columns.forEach(function (c) {
            var td = el('td', { 'data-label': c.label }); var v = c.render(row);
            if (v instanceof Node) td.appendChild(v); else td.textContent = v == null ? '' : v;
            tr.appendChild(td);
          });
          tbody.appendChild(tr);
        });
        var m = r.meta;
        pager.appendChild(el('small', { class: 'text-muted', text: 'الإجمالي: ' + m.total }));
        var g = el('div', { class: 'btn-group' });
        var prev = el('button', { class: 'btn btn-outline-primary', type: 'button', text: 'السابق' }); prev.disabled = m.page <= 1;
        var next = el('button', { class: 'btn btn-outline-primary', type: 'button', text: 'التالي' }); next.disabled = m.page >= m.pages;
        prev.onclick = function () { self.load(m.page - 1); }; next.onclick = function () { self.load(m.page + 1); };
        g.appendChild(prev); g.appendChild(el('span', { class: 'btn disabled', text: m.page + ' / ' + (m.pages || 1) })); g.appendChild(next);
        pager.appendChild(g);
      });
    };
    this.debounced = function () { clearTimeout(timer); timer = setTimeout(function () { self.load(1); }, 300); };
    this.load(1);
  };
})();
