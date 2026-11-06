-- المرحلة 7: إدارة طرق الدفع وظهورها عند الدفع (بوابة إلكترونية + طرق يدوية بتعليمات) وإشعارات التحويل التي يراجعها المحاسب
CREATE TABLE checkout_methods (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, kind ENUM('gateway','manual') NOT NULL DEFAULT 'manual',
  label VARCHAR(100) NOT NULL, instructions TEXT NULL, is_visible TINYINT(1) NOT NULL DEFAULT 1, sort_order SMALLINT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX (school_id, is_visible, sort_order), FOREIGN KEY (school_id) REFERENCES schools(id)
) ENGINE=InnoDB;
CREATE TABLE payment_claims (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, student_fee_id BIGINT UNSIGNED NOT NULL, guardian_user_id BIGINT UNSIGNED NOT NULL,
  method_id INT UNSIGNED NOT NULL, amount DECIMAL(14,2) NOT NULL, currency CHAR(3) NOT NULL, reference_no VARCHAR(100) NOT NULL, paid_on DATE NOT NULL, note VARCHAR(255) NULL,
  receipt_file_id BIGINT UNSIGNED NULL, status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending', decided_by BIGINT UNSIGNED NULL, decided_at DATETIME NULL,
  decision_note VARCHAR(255) NULL, payment_id BIGINT UNSIGNED NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (school_id, status, created_at), INDEX (student_fee_id), INDEX (school_id, reference_no),
  FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (student_fee_id) REFERENCES student_fees(id), FOREIGN KEY (method_id) REFERENCES checkout_methods(id)
) ENGINE=InnoDB;
