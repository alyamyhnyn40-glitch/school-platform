<?php
// php bin/create-admin.php "الاسم" email@example.com "كلمة_المرور" [هاتف]  — إنشاء/استرجاع حساب مشغّل المنصة
declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';
App\Core\Env::load();
[$_, $name, $email, $pass] = array_pad($argv, 4, null);
$phone = $argv[4] ?? null;
if (!$name || !$email || !$pass) { fwrite(STDERR, "الاستخدام: php bin/create-admin.php الاسم البريد كلمة_المرور [الهاتف]\n"); exit(1); }
if ($err = App\Services\PasswordHasher::validate($pass)) { fwrite(STDERR, "$err\n"); exit(1); }
$hash = App\Services\PasswordHasher::hash($pass);
$phone = $phone ? App\Services\Phone::normalize($phone) : null;
$u = App\Core\DB::one('SELECT id FROM users WHERE email = ?', [mb_strtolower($email)]);
if ($u) { App\Core\DB::query('UPDATE users SET password_hash = ?, status = "active", failed_attempts = 0, locked_until = NULL WHERE id = ?', [$hash, $u['id']]); echo "تم تحديث كلمة المرور\n"; exit(0); }
App\Core\DB::query("INSERT INTO users (user_type, full_name, email, phone, password_hash) VALUES ('platform', ?, ?, ?, ?)", [$name, mb_strtolower($email), $phone, $hash]);
App\Core\DB::query('INSERT INTO platform_admins (user_id, is_owner) VALUES (?, 1)', [App\Core\DB::lastId()]);
echo "تم إنشاء مشغّل المنصة\n";
