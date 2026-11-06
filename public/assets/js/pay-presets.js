// قوالب جاهزة لبوابات الدفع: تملأ حقول الإعداد. مفاتيح المتجر تُلصق في الترويسات يدويًا (لا تُحفظ في القالب).
(function () {
  'use strict';
  var P = {
    stripe: { label: 'Stripe Checkout', note: 'يدعم الدولار والريال السعودي (لا يدعم الريال اليمني). استبدل sk_live_... بمفتاحك السري. التحقق يتم باستعلام حالة الجلسة.',
      f: { cu: 'https://api.stripe.com/v1/checkout/sessions', cm: 'POST', ch: '{"Authorization":"Bearer sk_live_REPLACE"}',
        cb: 'mode=payment&success_url={return_url}&cancel_url={return_url}&client_reference_id={reference}&line_items[0][quantity]=1&line_items[0][price_data][currency]={currency}&line_items[0][price_data][unit_amount]={amount_minor}&line_items[0][price_data][product_data][name]={description}',
        cp: 'url', pp: 'id', rp: 'data.object.client_reference_id', sp: 'data.object.payment_status', sv: 'paid', fv: 'unpaid', ap: 'data.object.amount_total',
        su: 'https://api.stripe.com/v1/checkout/sessions/{provider_ref}', sm: 'GET', stp: 'payment_status', stv: 'paid', sta: 'amount_total', stb: '', sth: '{"Authorization":"Bearer sk_live_REPLACE"}' } },
    tap: { label: 'Tap Payments', note: 'يدعم عملات الخليج والدولار. استبدل sk_live_... بمفتاحك السري. عدّل بيانات العميل إن لزم.',
      f: { cu: 'https://api.tap.company/v2/charges', cm: 'POST', ch: '{"Authorization":"Bearer sk_live_REPLACE"}',
        cb: '{"amount":{amount},"currency":"{currency}","threeDSecure":true,"description":"{description}","reference":{"transaction":"{reference}","order":"{reference}"},"customer":{"first_name":"ولي أمر","email":"parent@example.com"},"source":{"id":"src_all"},"post":{"url":"{callback_url}"},"redirect":{"url":"{return_url}"}}',
        cp: 'transaction.url', pp: 'id', rp: 'reference.transaction', sp: 'status', sv: 'CAPTURED', fv: 'FAILED,DECLINED,CANCELLED,ABANDONED,TIMEDOUT', ap: 'amount',
        su: 'https://api.tap.company/v2/charges/{provider_ref}', sm: 'GET', stp: 'status', stv: 'CAPTURED', sta: 'amount', stb: '', sth: '{"Authorization":"Bearer sk_live_REPLACE"}' } },
    generic_sig: { label: 'عام — إشعار موقَّع (HMAC)', note: 'لمزود يوفّر واجهة JSON وإشعارًا موقَّعًا. عدّل الرابط والمسارات حسب وثائقه، وأدخل السر المشترك.',
      f: { cu: 'https://api.provider.com/v1/payments', cm: 'POST', ch: '{"Authorization":"Bearer REPLACE"}',
        cb: '{"amount":"{amount}","currency":"{currency}","reference":"{reference}","description":"{description}","return_url":"{return_url}","callback_url":"{callback_url}"}',
        cp: 'data.checkout_url', pp: 'data.id', rp: 'reference', sp: 'status', sv: 'paid,success,completed', fv: 'failed,cancelled,expired', ap: 'amount', sh: 'X-Signature', se: 'hex', su: '', stp: '', stv: '', sta: '', stb: '', sth: '' } },
    generic_query: { label: 'عام — تحقق باستعلام الحالة', note: 'لمزود بلا توقيع: نتأكد من الدفع باستعلام حالة العملية مباشرة من المزود قبل القيد.',
      f: { cu: 'https://api.provider.com/v1/payments', cm: 'POST', ch: '{"Authorization":"Bearer REPLACE"}',
        cb: '{"amount":"{amount}","currency":"{currency}","reference":"{reference}","return_url":"{return_url}","callback_url":"{callback_url}"}',
        cp: 'data.checkout_url', pp: 'data.id', rp: 'reference', sp: 'status', sv: 'paid', fv: 'failed', ap: 'amount',
        su: 'https://api.provider.com/v1/payments/{provider_ref}', sm: 'GET', stp: 'data.status', stv: 'paid', sta: 'data.amount', stb: '', sth: '{"Authorization":"Bearer REPLACE"}' } }
  };
  var sel = document.getElementById('gwPreset'); if (!sel) return;
  Object.keys(P).forEach(function (k) { var o = document.createElement('option'); o.value = k; o.textContent = P[k].label; sel.appendChild(o); });
  sel.addEventListener('change', function () {
    var p = P[sel.value], note = document.getElementById('gwNote'); if (!p) return;
    Object.keys(p.f).forEach(function (id) { var e = document.getElementById(id); if (e) e.value = p.f[id]; });
    if (note) note.textContent = p.note + ' بعد الحفظ انسخ «رابط الإشعار» إلى لوحة المزود، ثم جرّب دفعة صغيرة قبل الاعتماد.';
  });
})();
