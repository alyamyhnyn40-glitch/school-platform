/*
 * محرر المعادلات الرياضية (KaTeX محلي، بلا CDN).
 *  - أي <textarea|input data-math> يحصل على زر «محرر المعادلات» ولوحة رموز ومعاينة حية.
 *  - الصيغة تُخزَّن كنص عادي بين $...$ (ضمن السطر) أو $$...$$ (سطر مستقل)، وتُعرَض في كل الصفحات بـ MathField.render(el).
 *  - أي عنصر بصنف .math-content تُرسَم معادلاته تلقائيًا عند تحميل الصفحة.
 */
(function () {
  'use strict';
  var DELIMS = [{ left: '$$', right: '$$', display: true }, { left: '\\[', right: '\\]', display: true }, { left: '$', right: '$', display: false }, { left: '\\(', right: '\\)', display: false }];
  var OPTS = { delimiters: DELIMS, throwOnError: false, trust: false, strict: 'ignore', maxExpand: 1000, errorColor: '#b91c1c' };
  function render(root) { if (window.renderMathInElement && root) { try { window.renderMathInElement(root, OPTS); } catch (e) { /* لا يكسر الصفحة */ } } }
  function tex(s, display) { try { return window.katex.renderToString(s, { throwOnError: false, displayMode: !!display, trust: false, strict: 'ignore', maxExpand: 1000 }); } catch (e) { return ''; } }

  // القوالب: ‸ = موضع المؤشر بعد الإدراج، و{} = خانة فارغة (Tab ينتقل إليها)
  var GROUPS = [
    { id: 'basic', name: 'أساسيات', items: [
      ['كسر', '\\frac{‸}{}', '\\frac{a}{b}'], ['أس', '^{‸}', 'x^{n}'], ['دليل سفلي', '_{‸}', 'x_{i}'], ['جذر تربيعي', '\\sqrt{‸}', '\\sqrt{x}'], ['جذر نوني', '\\sqrt[‸]{}', '\\sqrt[n]{x}'],
      ['قيمة مطلقة', '\\left|‸\\right|', '|x|'], ['أقواس', '\\left(‸\\right)', '(\\,)'], ['أقواس مربعة', '\\left[‸\\right]', '[\\,]'], ['أقواس معقوفة', '\\left\\{‸\\right\\}', '\\{\\,\\}'], ['كسر كبير', '\\dfrac{‸}{}', '\\dfrac{a}{b}'],
      ['س تربيع', 'x^{2}‸', 'x^2'], ['ضرب', '\\times ', '\\times'], ['قسمة', '\\div ', '\\div'], ['زائد أو ناقص', '\\pm ', '\\pm'], ['نقطة ضرب', '\\cdot ', '\\cdot'], ['نسبة مئوية', '\\%', '\\%'], ['لوغاريتم', '\\log_{‸}', '\\log_b'], ['لوغاريتم طبيعي', '\\ln ', '\\ln'], ['جيب', '\\sin ', '\\sin'], ['جيب تمام', '\\cos ', '\\cos'], ['ظل', '\\tan ', '\\tan'], ['عامل', '‸!', 'n!'], ['تباديل/توافيق', '\\binom{‸}{}', '\\binom{n}{k}'] ] },
    { id: 'calc', name: 'تفاضل وتكامل', items: [
      ['تكامل غير محدد', '\\int ‸ \\, dx', '\\int'], ['تكامل محدد', '\\int_{‸}^{} \\, dx', '\\int_a^b'], ['تكامل ثنائي', '\\iint ‸ \\, dA', '\\iint'], ['تكامل ثلاثي', '\\iiint ‸ \\, dV', '\\iiint'], ['تكامل مغلق', '\\oint ‸ \\, ds', '\\oint'],
      ['مجموع', '\\sum_{i=1}^{‸} ', '\\sum'], ['جداء', '\\prod_{i=1}^{‸} ', '\\prod'], ['نهاية', '\\lim_{x \\to ‸} ', '\\lim'], ['مشتقة', '\\frac{d}{dx}‸', '\\frac{d}{dx}'], ['مشتقة جزئية', '\\frac{\\partial ‸}{\\partial x}', '\\partial'], ['مشتقة ثانية', '\\frac{d^{2}‸}{dx^{2}}', '\\frac{d^2}{dx^2}'],
      ['ما لا نهاية', '\\infty ', '\\infty'], ['تدرّج', '\\nabla ', '\\nabla'], ['f(x)', 'f(x)‸', 'f(x)'], ['مشتقة أولى', "f'(x)", "f'"], ['دلتا س', '\\Delta x', '\\Delta x'], ['إلى', '\\to ', '\\to'] ] },
    { id: 'matrix', name: 'مصفوفات ونظم', items: [
      ['مصفوفة 2×2', '\\begin{pmatrix} ‸ &  \\\\  &  \\end{pmatrix}', '\\begin{pmatrix}a&b\\\\c&d\\end{pmatrix}'], ['مصفوفة 3×3', '\\begin{pmatrix} ‸ &  &  \\\\  &  &  \\\\  &  &  \\end{pmatrix}', '\\begin{pmatrix}a&b&c\\\\d&e&f\\\\g&h&i\\end{pmatrix}'],
      ['مصفوفة مربعة', '\\begin{bmatrix} ‸ &  \\\\  &  \\end{bmatrix}', '\\begin{bmatrix}a&b\\\\c&d\\end{bmatrix}'], ['محدد 2×2', '\\begin{vmatrix} ‸ &  \\\\  &  \\end{vmatrix}', '\\begin{vmatrix}a&b\\\\c&d\\end{vmatrix}'], ['محدد 3×3', '\\begin{vmatrix} ‸ &  &  \\\\  &  &  \\\\  &  &  \\end{vmatrix}', '|A|_{3}'],
      ['متجه عمودي', '\\begin{pmatrix} ‸ \\\\  \\\\  \\end{pmatrix}', '\\begin{pmatrix}x\\\\y\\\\z\\end{pmatrix}'], ['نظام معادلات', '\\begin{cases} ‸ \\\\  \\end{cases}', '\\begin{cases}x\\\\y\\end{cases}'], ['معادلات محاذاة', '\\begin{aligned} ‸ &=  \\\\  &=  \\end{aligned}', '\\begin{aligned}a&=b\\end{aligned}'], ['متجه', '\\vec{‸}', '\\vec{v}'], ['سهم فوق', '\\overrightarrow{‸}', '\\overrightarrow{AB}'], ['خط فوق', '\\overline{‸}', '\\overline{AB}'], ['قبعة', '\\hat{‸}', '\\hat{i}'] ] },
    { id: 'greek', name: 'يونانية', items: 'α β γ δ ε ζ η θ ι κ λ μ ν ξ π ρ σ τ υ φ χ ψ ω Γ Δ Θ Λ Ξ Π Σ Φ Ψ Ω'.split(' ').map(function (c) { var m = { 'α': 'alpha', 'β': 'beta', 'γ': 'gamma', 'δ': 'delta', 'ε': 'epsilon', 'ζ': 'zeta', 'η': 'eta', 'θ': 'theta', 'ι': 'iota', 'κ': 'kappa', 'λ': 'lambda', 'μ': 'mu', 'ν': 'nu', 'ξ': 'xi', 'π': 'pi', 'ρ': 'rho', 'σ': 'sigma', 'τ': 'tau', 'υ': 'upsilon', 'φ': 'phi', 'χ': 'chi', 'ψ': 'psi', 'ω': 'omega', 'Γ': 'Gamma', 'Δ': 'Delta', 'Θ': 'Theta', 'Λ': 'Lambda', 'Ξ': 'Xi', 'Π': 'Pi', 'Σ': 'Sigma', 'Φ': 'Phi', 'Ψ': 'Psi', 'Ω': 'Omega' }[c]; return [c, '\\' + m + ' ', '\\' + m]; }) },
    { id: 'ops', name: 'عمليات ومنطق', items: [
      ['لا يساوي', '\\neq ', '\\neq'], ['تقريبًا', '\\approx ', '\\approx'], ['مطابق', '\\equiv ', '\\equiv'], ['أصغر أو يساوي', '\\leq ', '\\leq'], ['أكبر أو يساوي', '\\geq ', '\\geq'], ['أصغر بكثير', '\\ll ', '\\ll'], ['أكبر بكثير', '\\gg ', '\\gg'], ['يتناسب مع', '\\propto ', '\\propto'],
      ['ينتمي', '\\in ', '\\in'], ['لا ينتمي', '\\notin ', '\\notin'], ['مجموعة جزئية', '\\subset ', '\\subset'], ['جزئية أو تساوي', '\\subseteq ', '\\subseteq'], ['اتحاد', '\\cup ', '\\cup'], ['تقاطع', '\\cap ', '\\cap'], ['مجموعة خالية', '\\emptyset ', '\\emptyset'],
      ['لكل', '\\forall ', '\\forall'], ['يوجد', '\\exists ', '\\exists'], ['نفي', '\\neg ', '\\neg'], ['و', '\\land ', '\\land'], ['أو', '\\lor ', '\\lor'], ['يلزم', '\\Rightarrow ', '\\Rightarrow'], ['إذا وفقط إذا', '\\Leftrightarrow ', '\\Leftrightarrow'], ['سهم', '\\rightarrow ', '\\rightarrow'], ['إذن', '\\therefore ', '\\therefore'], ['لأن', '\\because ', '\\because'],
      ['زاوية', '\\angle ', '\\angle'], ['عمودي', '\\perp ', '\\perp'], ['يوازي', '\\parallel ', '\\parallel'], ['مثلث', '\\triangle ', '\\triangle'], ['درجة', '^{\\circ}', '^{\\circ}'], ['الأعداد الحقيقية', '\\mathbb{R}', '\\mathbb{R}'], ['الأعداد الطبيعية', '\\mathbb{N}', '\\mathbb{N}'], ['الأعداد الصحيحة', '\\mathbb{Z}', '\\mathbb{Z}'], ['الأعداد النسبية', '\\mathbb{Q}', '\\mathbb{Q}'] ] },
    { id: 'sci', name: 'علمية', items: [
      ['صيغة علمية', '\\times 10^{‸}', '\\times 10^{n}'], ['درجة مئوية', '^{\\circ}\\mathrm{C}', '^\\circ\\mathrm{C}'], ['متر/ثانية', '\\mathrm{m/s}', '\\mathrm{m/s}'], ['متر/ثانية²', '\\mathrm{m/s^{2}}', '\\mathrm{m/s^2}'], ['نيوتن', '\\mathrm{N}', '\\mathrm{N}'], ['جول', '\\mathrm{J}', '\\mathrm{J}'], ['واط', '\\mathrm{W}', '\\mathrm{W}'], ['أوم', '\\Omega ', '\\Omega'], ['أنغستروم', '\\text{Å}', '\\text{Å}'],
      ['ماء', '\\mathrm{H_{2}O}', '\\mathrm{H_2O}'], ['ثاني أكسيد الكربون', '\\mathrm{CO_{2}}', '\\mathrm{CO_2}'], ['تفاعل عكسي', '\\rightleftharpoons ', '\\rightleftharpoons'], ['سهم تفاعل', '\\xrightarrow{‸}', '\\xrightarrow{\\Delta}'], ['أيون', '^{‸}', '\\mathrm{Na^+}'], ['ثابت بلانك', '\\hbar ', '\\hbar'], ['ميكرو', '\\mu ', '\\mu'], ['سرعة', 'v = \\frac{\\Delta x}{\\Delta t}', 'v=\\frac{\\Delta x}{\\Delta t}'], ['قوة', 'F = ma', 'F=ma'], ['طاقة', 'E = mc^{2}', 'E=mc^2'], ['جذر المتوسط', '\\sqrt{\\overline{‸^{2}}}', '\\sqrt{\\overline{v^2}}'] ] }
  ];

  function el(tag, attrs, kids) { var n = document.createElement(tag); Object.keys(attrs || {}).forEach(function (k) { if (k === 'text') n.textContent = attrs[k]; else n.setAttribute(k, attrs[k]); }); (kids || []).forEach(function (c) { n.appendChild(c); }); return n; }
  var uid = 0;

  function attach(field) {
    if (field.getAttribute('data-math-ready')) return; field.setAttribute('data-math-ready', '1');
    var id = 'mf' + (++uid), wrap = el('div', { 'class': 'mf mt-1' });
    var compact = field.getAttribute('data-math') === 'compact';
    var toggle = el('button', { type: 'button', 'class': 'btn btn-sm btn-outline-primary mf-toggle' + (compact ? ' compact' : ''), 'aria-expanded': 'false', 'aria-controls': id, title: 'محرر المعادلات' }, [el('span', { text: compact ? '∑' : '∑ محرر المعادلات' })]);
    var live = el('div', { 'class': 'mf-live math-content small border rounded p-2 mt-1', hidden: 'hidden', 'aria-label': 'معاينة النص مع المعادلات', dir: 'auto' });
    var panel = el('div', { 'class': 'mf-panel card-s mt-1', id: id, hidden: 'hidden' });
    wrap.appendChild(toggle); wrap.appendChild(panel); wrap.appendChild(live);
    field.parentNode.insertBefore(wrap, field.nextSibling);

    var built = false, src, prev, palette, tabs, lastPos = null;
    function updateLive() {
      var v = field.value; if (v.indexOf('$') === -1 && v.indexOf('\\(') === -1) { live.hidden = true; return; }
      live.hidden = false; live.textContent = v; render(live);
    }
    function updatePrev() { prev.innerHTML = src.value.trim() ? tex(src.value, true) : '<span class="text-muted small">معاينة المعادلة تظهر هنا</span>'; }
    function insertIntoSrc(tpl) {
      var s = src.selectionStart, e = src.selectionEnd, v = src.value, sel = v.slice(s, e); var t = tpl;
      if (sel && t.indexOf('‸') !== -1) t = t.replace('‸', sel);   // يُحاط المحدَّد بالقالب
      var caret = t.indexOf('‸'); t = t.replace('‸', ''); src.value = v.slice(0, s) + t + v.slice(e); var pos = s + (caret === -1 ? t.length : caret); src.focus(); src.setSelectionRange(pos, pos); updatePrev();
    }
    function insertIntoField(text) {
      var p = lastPos != null ? lastPos : field.value.length, v = field.value; var pre = v.slice(0, p), post = v.slice(p);
      var sp1 = pre && !/\s$/.test(pre) ? ' ' : '', sp2 = post && !/^\s/.test(post) ? ' ' : '';
      field.value = pre + sp1 + text + sp2 + post; lastPos = (pre + sp1 + text).length; field.dispatchEvent(new Event('input', { bubbles: true })); field.focus(); field.setSelectionRange(lastPos + sp2.length, lastPos + sp2.length);
    }
    function build() {
      built = true; if (!window.katex) { panel.appendChild(el('div', { 'class': 'text-danger small', text: 'تعذّر تحميل مكتبة المعادلات.' })); return; }
      tabs = el('ul', { 'class': 'nav nav-pills mb-2 flex-wrap gap-1 mf-tabs', role: 'tablist' }); palette = el('div', { 'class': 'mf-palette mb-2', role: 'group', 'aria-label': 'رموز ومعادلات جاهزة' });
      GROUPS.forEach(function (g, i) {
        var b = el('button', { type: 'button', 'class': 'nav-link py-1 px-2' + (i ? '' : ' active'), role: 'tab', 'data-g': g.id, text: g.name }); tabs.appendChild(el('li', { 'class': 'nav-item' }, [b]));
      });
      function show(gid) {
        tabs.querySelectorAll('.nav-link').forEach(function (n) { n.classList.toggle('active', n.getAttribute('data-g') === gid); }); palette.textContent = '';
        GROUPS.filter(function (g) { return g.id === gid; })[0].items.forEach(function (it) {
          var b = el('button', { type: 'button', 'class': 'mf-key', title: it[0], 'aria-label': it[0] }); b.innerHTML = tex(it[2] || it[1], false); b.addEventListener('click', function () { insertIntoSrc(it[1]); }); palette.appendChild(b);
        });
      }
      tabs.addEventListener('click', function (e) { var b = e.target.closest('[data-g]'); if (b) show(b.getAttribute('data-g')); });
      src = el('textarea', { 'class': 'form-control mf-src', rows: '2', dir: 'ltr', placeholder: 'اكتب أو الصق صيغة LaTeX هنا، أو استخدم الأزرار أعلاه', 'aria-label': 'صيغة المعادلة', spellcheck: 'false' });
      prev = el('div', { 'class': 'mf-prev border rounded p-2 mb-2', 'aria-live': 'polite', dir: 'ltr' });
      var bi = el('button', { type: 'button', 'class': 'btn btn-sm btn-primary', text: 'إدراج ضمن السطر  $…$' }), bb = el('button', { type: 'button', 'class': 'btn btn-sm btn-outline-primary', text: 'إدراج كسطر مستقل  $$…$$' }), bc = el('button', { type: 'button', 'class': 'btn btn-sm btn-outline-secondary', text: 'مسح' });
      bi.addEventListener('click', function () { var v = src.value.trim(); if (!v) return; insertIntoField('$' + v + '$'); });
      bb.addEventListener('click', function () { var v = src.value.trim(); if (!v) return; insertIntoField('$$' + v + '$$'); });
      bc.addEventListener('click', function () { src.value = ''; updatePrev(); src.focus(); });
      src.addEventListener('input', updatePrev);
      src.addEventListener('keydown', function (e) {   // Tab: الانتقال للخانة الفارغة التالية {}
        if (e.key !== 'Tab' || e.shiftKey) return; var i = src.value.indexOf('{}', src.selectionEnd); if (i === -1) i = src.value.indexOf('{}'); if (i === -1) return; e.preventDefault(); src.setSelectionRange(i + 1, i + 1);
      });
      panel.appendChild(tabs); panel.appendChild(palette); panel.appendChild(src); panel.appendChild(prev); panel.appendChild(el('div', { 'class': 'd-flex flex-wrap gap-2 align-items-center' }, [bi, bb, bc, el('small', { 'class': 'text-muted', text: 'Tab للانتقال بين الخانات الفارغة {}' })]));
      show('basic'); updatePrev();
    }
    toggle.addEventListener('click', function () { var open = panel.hidden; if (open && !built) build(); panel.hidden = !open; toggle.setAttribute('aria-expanded', open ? 'true' : 'false'); if (open && src) src.focus(); });
    ['blur', 'keyup', 'click'].forEach(function (ev) { field.addEventListener(ev, function () { lastPos = field.selectionStart; }); });
    field.addEventListener('input', updateLive); updateLive();
  }
  function init() { document.querySelectorAll('textarea[data-math], input[data-math]').forEach(attach); render(document.body.querySelectorAll('.math-content').length ? document : null); document.querySelectorAll('.math-content').forEach(render); }
  window.MathField = { attach: attach, render: render, tex: tex };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
