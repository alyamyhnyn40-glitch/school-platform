// قوالب جاهزة لمزودي الرسائل: تملأ الرابط والترويسات والجسم؛ وبيانات الاعتماد تُدخل في حقولها المشفّرة.
(function () {
  'use strict';
  var PRESETS = {
    sms: {
      twilio: { label: 'Twilio (SMS)', url: 'https://api.twilio.com/2010-04-01/Accounts/{username}/Messages.json', method: 'POST', headers: '{"Authorization":"Basic {base64_auth}"}', body: 'To={to}&From={sender}&Body={message}', code: 201, match: '', note: 'اسم المستخدم = Account SID، كلمة المرور = Auth Token، المرسل = رقم Twilio.' },
      infobip: { label: 'Infobip', url: 'https://YOUR-ID.api.infobip.com/sms/2/text/advanced', method: 'POST', headers: '{"Authorization":"App {api_key}"}', body: '{"messages":[{"from":"{sender}","destinations":[{"to":"{to_digits}"}],"text":"{message}"}]}', code: 200, match: '', note: 'استبدل YOUR-ID بنطاق حسابك في الرابط.' },
      vonage: { label: 'Vonage (Nexmo)', url: 'https://rest.nexmo.com/sms/json', method: 'POST', headers: '{}', body: 'api_key={username}&api_secret={password}&to={to_digits}&from={sender}&text={message}', code: 200, match: '"status":"0"', note: 'اسم المستخدم = API Key، كلمة المرور = API Secret.' },
      unifonic: { label: 'Unifonic', url: 'https://el.cloud.unifonic.com/rest/SMS/messages', method: 'POST', headers: '{}', body: 'AppSid={api_key}&SenderID={sender}&Recipient={to_digits}&Body={message}', code: 200, match: '"success":true', note: 'مفتاح API = AppSid.' },
      msegat: { label: 'Msegat', url: 'https://www.msegat.com/gw/sendsms.php', method: 'POST', headers: '{}', body: '{"userName":"{username}","apiKey":"{api_key}","numbers":"{to_digits}","userSender":"{sender}","msg":"{message}","msgEncoding":"UTF8"}', code: 200, match: '', note: 'راجع رمز الاستجابة في لوحة المزود.' },
      taqnyat: { label: 'Taqnyat', url: 'https://api.taqnyat.sa/v1/messages', method: 'POST', headers: '{"Authorization":"Bearer {api_key}"}', body: '{"recipients":["{to_digits}"],"body":"{message}","sender":"{sender}"}', code: 201, match: '', note: '' },
      generic: { label: 'عام (JSON بسيط)', url: 'https://api.provider.com/sms/send', method: 'POST', headers: '{"Authorization":"Bearer {api_key}"}', body: '{"to":"{to}","message":"{message}","from":"{sender}"}', code: 200, match: '', note: 'عدّل الرابط وأسماء الحقول حسب وثائق مزودك.' }
    },
    whatsapp: {
      meta: { label: 'WhatsApp Cloud API (Meta)', url: 'https://graph.facebook.com/v19.0/{sender}/messages', method: 'POST', headers: '{"Authorization":"Bearer {api_key}"}', body: '{"messaging_product":"whatsapp","to":"{to_digits}","type":"text","text":{"body":"{message}"}}', code: 200, match: '', note: 'المرسل = Phone Number ID، مفتاح API = Access Token. المراسلة خارج نافذة 24 ساعة تحتاج قوالب معتمدة من Meta.' },
      ultramsg: { label: 'UltraMsg', url: 'https://api.ultramsg.com/{sender}/messages/chat', method: 'POST', headers: '{}', body: 'token={api_key}&to={to}&body={message}', code: 200, match: '"sent":"true"', note: 'المرسل = Instance ID، مفتاح API = Token.' },
      twilio: { label: 'Twilio (WhatsApp)', url: 'https://api.twilio.com/2010-04-01/Accounts/{username}/Messages.json', method: 'POST', headers: '{"Authorization":"Basic {base64_auth}"}', body: 'To=whatsapp:{to}&From=whatsapp:{sender}&Body={message}', code: 201, match: '', note: 'المرسل = رقم واتساب Twilio (بصيغة دولية).' },
      generic: { label: 'عام (JSON بسيط)', url: 'https://api.provider.com/whatsapp/send', method: 'POST', headers: '{"Authorization":"Bearer {api_key}"}', body: '{"to":"{to_digits}","message":"{message}"}', code: 200, match: '', note: 'عدّل الرابط وأسماء الحقول حسب وثائق مزودك.' }
    }
  };
  var EMAIL = {
    gmail: { label: 'Gmail', host: 'smtp.gmail.com', port: 587, enc: 'tls', note: 'فعّل التحقق بخطوتين وأنشئ «كلمة مرور تطبيق». المستخدم = بريدك الكامل.' },
    outlook: { label: 'Outlook / Microsoft 365', host: 'smtp.office365.com', port: 587, enc: 'tls', note: 'قد يتطلب تفعيل SMTP AUTH لصندوق البريد.' },
    zoho: { label: 'Zoho Mail', host: 'smtp.zoho.com', port: 465, enc: 'ssl', note: 'استخدم كلمة مرور تطبيق إن كان التحقق بخطوتين مفعّلًا.' },
    yahoo: { label: 'Yahoo', host: 'smtp.mail.yahoo.com', port: 465, enc: 'ssl', note: 'يتطلب كلمة مرور تطبيق.' },
    sendgrid: { label: 'SendGrid', host: 'smtp.sendgrid.net', port: 587, enc: 'tls', note: 'المستخدم = apikey (حرفيًا)، وكلمة المرور = مفتاح API.' },
    mailgun: { label: 'Mailgun', host: 'smtp.mailgun.org', port: 587, enc: 'tls', note: 'المستخدم وكلمة المرور من بيانات SMTP لنطاقك في Mailgun.' },
    brevo: { label: 'Brevo (Sendinblue)', host: 'smtp-relay.brevo.com', port: 587, enc: 'tls', note: 'المستخدم = بريد حسابك، وكلمة المرور = مفتاح SMTP.' },
    cpanel: { label: 'استضافة cPanel (بريد نطاقك)', host: 'mail.yourdomain.com', port: 465, enc: 'ssl', note: 'استبدل yourdomain.com بنطاقك؛ المستخدم = بريدك الكامل.' }
  };
  var box = document.getElementById('httpBox'), sel = document.getElementById('preset'); if (!box || !sel) return;
  if (box.getAttribute('data-channel') === 'email') {
    Object.keys(EMAIL).forEach(function (k) { var o = document.createElement('option'); o.value = k; o.textContent = EMAIL[k].label; sel.appendChild(o); });
    sel.addEventListener('change', function () { var p = EMAIL[sel.value]; if (!p) return; var set2 = function (n, v) { var e = box.querySelector('[name=' + n + ']'); if (e) e.value = v; };
      set2('host', p.host); set2('port', p.port); set2('encryption', p.enc); var n = document.getElementById('presetNote'); if (n) n.textContent = p.note; });
    var port = box.querySelector('[name=port]'), encSel = box.querySelector('[name=encryption]');
    if (port && encSel) port.addEventListener('change', function () { if (port.value === '587') encSel.value = 'tls'; else if (port.value === '465') encSel.value = 'ssl'; });
    return;
  }
  var list = PRESETS[box.getAttribute('data-channel')] || PRESETS.sms;
  Object.keys(list).forEach(function (k) { var o = document.createElement('option'); o.value = k; o.textContent = list[k].label; sel.appendChild(o); });
  function set(id, v) { var e = document.getElementById(id); if (e) e.value = v; }
  sel.addEventListener('change', function () {
    var p = list[sel.value]; var note = document.getElementById('presetNote'); if (!p) return;
    set('u', p.url); set('m', p.method); set('h', p.headers); set('b', p.body); set('sc', p.code); set('sm', p.match);
    if (note) note.textContent = (p.note ? p.note + ' ' : '') + 'راجع الحقول ثم أدخل بيانات الاعتماد واحفظ، ثم جرّب «إرسال اختبار».';
  });
})();
