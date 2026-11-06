-- المرحلة 7: سجل أحداث الأمان (فشل الدخول، رفض الصلاحيات 403، 401، CSRF، تجاوز المعدل). يُنظَّف بعد 180 يومًا.
CREATE TABLE security_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  school_id INT UNSIGNED NULL, user_id BIGINT UNSIGNED NULL, user_type VARCHAR(20) NULL,
  type VARCHAR(30) NOT NULL, status SMALLINT UNSIGNED NULL, method VARCHAR(8) NULL, path VARCHAR(255) NULL, perm VARCHAR(80) NULL,
  detail VARCHAR(255) NULL, ip VARCHAR(45) NULL, ua VARCHAR(160) NULL,
  INDEX (created_at), INDEX (school_id, created_at), INDEX (type, created_at), INDEX (user_id, created_at)
) ENGINE=InnoDB;
