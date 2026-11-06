# مدرستي — منصة إدارة المدارس والتعليم عن بُعد

منصة متعددة المدارس (Multi-Tenant) مبنية بـ PHP 8.2+ (MVC يدوي) وMySQL 8/MariaDB 10.6+ وBootstrap RTL. تعمل على استضافة cPanel المشتركة وعلى VPS دون root.

## المتطلبات
- PHP ‏8.2 أو أحدث مع: `pdo_mysql`, `mbstring`, `sodium`, `json`, `curl`, `gd`, `zip`, `intl`
- MySQL ‏8 أو MariaDB ‏10.6+ (ترميز utf8mb4)
- HTTPS (إلزامي في الإنتاج)
- مجلد `vendor` مضمّن في الحزمة، فلا حاجة لـ Composer على الاستضافة.

## النشر على cPanel
1. أنشئ قاعدة بيانات ومستخدمًا من «MySQL Databases» وامنحه كل الصلاحيات.
2. ارفع محتوى الحزمة المضغوطة وفك الضغط (يفضّل خارج `public_html`، مثل `~/school-platform`).
3. اضبط **Document Root** للدومين على المجلد `public` (Domains → Manage). إن تعذر، اترك الجذر على `public_html` وانسخ المشروع داخله: ملف `.htaccess` في الجذر يوجّه الطلبات إلى `public` ويحجب بقية المجلدات.
4. تأكد أن `storage/` قابل للكتابة (755 أو 775).
5. افتح `https://موقعك/install` واملأ بيانات القاعدة وحساب المشغّل. يقفل المعالج نفسه بعد النجاح.

## النشر على VPS
**Apache:** فعّل `mod_rewrite` وأشر `DocumentRoot` إلى `public` مع `AllowOverride All`.

**Nginx:**
```nginx
root /var/www/school-platform/public;
index index.php;
location / { try_files $uri /index.php?$query_string; }
location ~ \.php$ { include fastcgi_params; fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; fastcgi_pass unix:/run/php/php8.2-fpm.sock; }
location ~ /\.(?!well-known) { deny all; }
```
ثم افتح `/install`.

## ملف `.env`
يُنشأ تلقائيًا بالمعالج. انظر `.env.example`. لا ترفعه إلى أي مستودع. **احتفظ بنسخة من `APP_ENC_KEY`**: فقدانه يعني فقدان قراءة الحقول المشفّرة.

## ربط مزود SMS
في `.env`: `SMS_DRIVER=http` ثم اضبط `SMS_HTTP_URL` و`SMS_HTTP_HEADERS` و`SMS_HTTP_BODY` (يدعم `{to}` و`{message}`) وفق توثيق مزودك. للتجربة استخدم `SMS_DRIVER=log` وستجد الرسائل في `storage/logs/sms.log`.

## أوامر مفيدة
```bash
php bin/migrate.php                                   # تشغيل migrations جديدة بعد التحديث
php bin/create-admin.php "الاسم" email "كلمة_المرور"   # إنشاء/استرجاع مشغّل المنصة
vendor/bin/phpunit                                    # الاختبارات (قاعدة *_test فقط)
php -S localhost:8000 -t public public/index.php      # تشغيل محلي
```

## تحديث نسخة قائمة
1. خذ نسخة احتياطية (`php bin/backup.php`).
2. ارفع الملفات الجديدة فوق القديمة **دون استبدال** `.env` أو `storage/`.
3. شغّل `php bin/migrate.php` (يطبّق الـ migrations الجديدة فقط).
4. امسح كاش المتصفح أو أعد تحميل الصفحة بقوة مرة (لتحديث Service Worker).

## المهام المجدولة (Cron)
```
30 2 * * *  php /مسار/المشروع/bin/backup.php >> /مسار/المشروع/storage/logs/backup.log 2>&1
* * * * *   php /مسار/المشروع/bin/cron-notifications.php >> /مسار/المشروع/storage/logs/notify.log 2>&1
15 5 * * *   php /مسار/المشروع/bin/cron-risk.php >> /مسار/المشروع/storage/logs/risk.log 2>&1
```
النسخ الاحتياطية في `storage/backups` (قاعدة + ملفات). حمّل نسخة دورية خارج الخادم. الاستعادة: فك الضغط ثم `mysql DB < db-*.sql`.

## الاختبارات
أنشئ قاعدة `school_platform_test` ثم `vendor/bin/phpunit`. تُعاد بناؤها من الصفر في كل تشغيل، ويرفض المشغّل العمل على قاعدة لا ينتهي اسمها بـ `_test`.

## التوثيق
`docs/PLAN.md` الخطة والمعمارية · `docs/DECISIONS.md` القرارات · `docs/PROGRESS.md` التقدم.
