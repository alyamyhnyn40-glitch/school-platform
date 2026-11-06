-- المرحلة 7: رموز التحقق عبر البريد (استعادة كلمة المرور). الرمز مجزّأ ولا يُخزَّن خامًا.
CREATE TABLE email_codes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL, email VARCHAR(190) NOT NULL, purpose VARCHAR(20) NOT NULL DEFAULT 'reset',
  code_hash CHAR(64) NOT NULL, attempts TINYINT UNSIGNED NOT NULL DEFAULT 0, expires_at DATETIME NOT NULL, used_at DATETIME NULL, ip VARCHAR(45) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX (email, purpose, created_at), INDEX (user_id), FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;
