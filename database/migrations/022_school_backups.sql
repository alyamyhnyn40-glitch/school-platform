-- نسخ احتياطية لكل مدرسة (محلية وسحابية)
CREATE TABLE school_backups (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL,
  filename VARCHAR(120) NOT NULL, size_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0, kind ENUM('manual','auto') NOT NULL DEFAULT 'manual',
  encrypted TINYINT(1) NOT NULL DEFAULT 0, includes_files TINYINT(1) NOT NULL DEFAULT 1, row_count INT UNSIGNED NOT NULL DEFAULT 0, app_version VARCHAR(20) NULL,
  cloud_status ENUM('none','ok','failed') NOT NULL DEFAULT 'none', cloud_error VARCHAR(255) NULL, cloud_at DATETIME NULL,
  created_by BIGINT UNSIGNED NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (school_id, created_at), FOREIGN KEY (school_id) REFERENCES schools(id)
) ENGINE=InnoDB;
