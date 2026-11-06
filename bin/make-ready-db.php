<?php
// php bin/make-ready-db.php <db> <enc_key_base64> — يبني قاعدة جاهزة للتجربة: هيكل + بيانات أساسية + مشغّل منصة + مدرسة تجريبية (للمطوّر، لتوليد database/ready/madrasati-ready.sql)
declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';
use App\Core\{DB, Env, Migrator};
use App\Services\{PasswordHasher, SeedService, DemoService};
[$_, $db, $key] = $argv + [null, 'madrasati_ready', ''];
Env::load(); Env::set('DB_NAME', $db); Env::set('APP_ENC_KEY', $key); Env::set('APP_ENV', 'local'); DB::reset();
Migrator::run(); SeedService::seedBase();
DB::query("INSERT INTO users (school_id, user_type, full_name, email, password_hash, must_change_password) VALUES (NULL, 'platform', 'مشغّل المنصة', 'admin@example.com', ?, 0)", [PasswordHasher::hash('Admin12345')]);
DB::query('INSERT INTO platform_admins (user_id, is_owner) VALUES (?, 1)', [DB::lastId()]);
$sid = DemoService::load('admin@example.com', 'Admin12345');
DB::query("DELETE FROM security_events"); DB::query("DELETE FROM login_attempts"); DB::query("DELETE FROM rate_limits"); DB::query("DELETE FROM audit_logs");
echo "جاهزة: مدرسة #$sid\n";
