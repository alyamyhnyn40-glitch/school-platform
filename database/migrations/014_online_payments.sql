-- المرحلة 6: الدفع الإلكتروني (بوابة دفع عامة عبر HTTP + إشعار موقَّع). الإعدادات في settings (مفاتيح pay.*) والأسرار مشفّرة.
CREATE TABLE online_payments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, reference CHAR(24) NOT NULL UNIQUE,
  student_fee_id BIGINT UNSIGNED NOT NULL, guardian_user_id BIGINT UNSIGNED NULL,
  amount DECIMAL(14,2) NOT NULL, currency CHAR(3) NOT NULL,
  status ENUM('pending','paid','failed','cancelled','expired','review') NOT NULL DEFAULT 'pending',
  driver VARCHAR(20) NOT NULL, provider_ref VARCHAR(120) NULL, checkout_url VARCHAR(1000) NULL, payment_id BIGINT UNSIGNED NULL,
  failure_reason VARCHAR(255) NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NULL, paid_at DATETIME NULL,
  INDEX (school_id, status), INDEX (student_fee_id),
  FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (student_fee_id) REFERENCES student_fees(id)
) ENGINE=InnoDB;
CREATE TABLE online_payment_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, online_payment_id BIGINT UNSIGNED NULL,
  kind VARCHAR(20) NOT NULL, ok TINYINT(1) NOT NULL, detail VARCHAR(255) NULL, ip VARCHAR(45) NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (school_id, created_at), INDEX (online_payment_id), FOREIGN KEY (school_id) REFERENCES schools(id)
) ENGINE=InnoDB;
-- إعداد بوابة الدفع (مفاتيح المتجر) لمدير المدرسة فقط افتراضيًا
INSERT IGNORE INTO permissions (code, module, label) VALUES ('fees.gateway', 'fees', 'إعداد الدفع الإلكتروني');
INSERT IGNORE INTO role_permissions (role_id, permission_id) SELECT r.id, p.id FROM roles r JOIN permissions p ON p.code = 'fees.gateway' WHERE r.code = 'school_admin';
