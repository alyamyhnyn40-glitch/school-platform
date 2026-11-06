-- عام: ملفات، إعدادات، تدقيق، عدادات، استيراد
CREATE TABLE files (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NULL,
  owner_user_id BIGINT UNSIGNED NULL, context VARCHAR(50) NOT NULL, context_id BIGINT UNSIGNED NULL,
  original_name VARCHAR(255) NOT NULL, stored_name VARCHAR(100) NOT NULL, mime VARCHAR(100) NOT NULL, size_bytes BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, deleted_at DATETIME NULL,
  INDEX (school_id, context, context_id), FOREIGN KEY (school_id) REFERENCES schools(id)
) ENGINE=InnoDB;

CREATE TABLE settings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NULL, skey VARCHAR(100) NOT NULL, svalue TEXT NULL,
  UNIQUE KEY uq_setting (school_id, skey), FOREIGN KEY (school_id) REFERENCES schools(id)
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NULL, user_id BIGINT UNSIGNED NULL,
  action VARCHAR(60) NOT NULL, entity VARCHAR(60) NOT NULL, entity_id BIGINT UNSIGNED NULL,
  old_values JSON NULL, new_values JSON NULL, ip VARCHAR(45) NULL, user_agent VARCHAR(255) NULL, via_support TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (school_id, entity, entity_id), INDEX (school_id, created_at), INDEX (user_id)
) ENGINE=InnoDB;

CREATE TABLE counters (
  school_id INT UNSIGNED NOT NULL, name VARCHAR(50) NOT NULL, year SMALLINT UNSIGNED NOT NULL DEFAULT 0, value BIGINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (school_id, name, year), FOREIGN KEY (school_id) REFERENCES schools(id)
) ENGINE=InnoDB;

CREATE TABLE import_jobs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, user_id BIGINT UNSIGNED NOT NULL,
  kind ENUM('students','guardians','staff') NOT NULL, original_name VARCHAR(255) NOT NULL, stored_name VARCHAR(100) NOT NULL,
  status ENUM('uploaded','previewed','committed','failed') NOT NULL DEFAULT 'uploaded',
  total_rows INT UNSIGNED NOT NULL DEFAULT 0, ok_rows INT UNSIGNED NOT NULL DEFAULT 0, error_rows INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (school_id) REFERENCES schools(id)
) ENGINE=InnoDB;

CREATE TABLE import_rows (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, job_id BIGINT UNSIGNED NOT NULL, row_no INT UNSIGNED NOT NULL,
  data JSON NOT NULL, status ENUM('ok','error','imported') NOT NULL, errors JSON NULL,
  INDEX (job_id), FOREIGN KEY (job_id) REFERENCES import_jobs(id) ON DELETE CASCADE
) ENGINE=InnoDB;
