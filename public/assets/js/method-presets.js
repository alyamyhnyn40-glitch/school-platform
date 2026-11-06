// يملأ اسم طريقة الدفع وقالب التعليمات عند اختيارها من القائمة
(function () {
  'use strict';
  var sel = document.getElementById('np'), label = document.getElementById('nl'), ins = document.getElementById('ni'); if (!sel || !label || !ins) return;
  var T = { bank: function (n) { return 'حوّل المبلغ إلى حساب المدرسة في ' + n + ':\nرقم الحساب: ........\nاسم المستفيد: ........\nثم أرسل إشعار التحويل (المبلغ ورقم العملية) من صفحة الرسوم.'; },
            wallet: function (n) { return 'أرسل المبلغ عبر ' + n + ' إلى الرقم/الحساب: ........\nباسم: ........\nثم أرسل إشعار التحويل (المبلغ ورقم العملية) من صفحة الرسوم.'; },
            cash: function () { return 'ادفع نقدًا في صندوق المدرسة خلال أوقات الدوام، واحتفظ بسند القبض.'; } };
  sel.addEventListener('change', function () { var v = sel.value.split('|'); if (v.length < 2) return; label.value = v[1]; ins.value = (T[v[0]] || T.cash)(v[1]); });
})();
