-- المرحلة 7: طلبات تسجيل المدارس القادمة من صفحة الهبوط (مراجعة المشغّل: قبول/رفض).
CREATE TABLE school_requests (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  school_name VARCHAR(200) NOT NULL, city VARCHAR(100) NULL, governorate VARCHAR(100) NULL, address VARCHAR(255) NULL,
  gender_policy ENUM('mixed','boys','girls') NOT NULL DEFAULT 'mixed', stages VARCHAR(200) NULL, students_estimate INT UNSIGNED NULL,
  contact_name VARCHAR(200) NOT NULL, contact_email VARCHAR(190) NOT NULL, contact_phone VARCHAR(30) NULL, notes VARCHAR(1000) NULL,
  logo_file VARCHAR(60) NULL, ip VARCHAR(45) NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  decided_by BIGINT UNSIGNED NULL, decided_at DATETIME NULL, decision_note VARCHAR(500) NULL, school_id INT UNSIGNED NULL, plan_id INT UNSIGNED NULL,
  INDEX (status, created_at), INDEX (contact_email)
) ENGINE=InnoDB;
