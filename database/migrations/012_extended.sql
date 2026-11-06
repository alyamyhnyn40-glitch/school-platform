-- المرحلة 5: القبول، الإنذار المبكر، الإرشاد، الرواتب، المرافق، الشهادات الرقمية، التحليلات
ALTER TABLE admission_applications
  ADD COLUMN national_id_enc TEXT NULL, ADD COLUMN previous_school VARCHAR(200) NULL, ADD COLUMN address VARCHAR(255) NULL, ADD COLUMN guardian_relation VARCHAR(20) NULL,
  ADD COLUMN academic_year_id INT UNSIGNED NULL, ADD COLUMN decision_note VARCHAR(255) NULL, ADD COLUMN decided_by BIGINT UNSIGNED NULL, ADD COLUMN decided_at DATETIME NULL, ADD INDEX idx_adm (school_id, status);
ALTER TABLE risk_flags ADD COLUMN note VARCHAR(500) NULL, ADD COLUMN reviewed_at DATETIME NULL, ADD COLUMN metric DECIMAL(10,2) NULL, ADD INDEX idx_rf (school_id, student_id, indicator_code, status);
ALTER TABLE counseling_cases ADD COLUMN kind ENUM('academic','career','behavior','social','other') NOT NULL DEFAULT 'other', ADD COLUMN risk_flag_id BIGINT UNSIGNED NULL, ADD COLUMN updated_at DATETIME NULL;
CREATE TABLE counseling_notes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, case_id BIGINT UNSIGNED NOT NULL, user_id BIGINT UNSIGNED NOT NULL, note_enc TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX (case_id), FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (case_id) REFERENCES counseling_cases(id)
) ENGINE=InnoDB;
ALTER TABLE contracts ADD COLUMN notes VARCHAR(255) NULL, ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE payroll_runs ADD COLUMN paid_on DATE NULL, ADD COLUMN approved_by BIGINT UNSIGNED NULL, ADD COLUMN approved_at DATETIME NULL, ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE payroll_items ADD COLUMN contract_id INT UNSIGNED NULL, ADD COLUMN days_worked TINYINT UNSIGNED NULL, ADD COLUMN days_in_month TINYINT UNSIGNED NULL, ADD COLUMN note VARCHAR(255) NULL;
ALTER TABLE facility_bookings ADD COLUMN cancelled_at DATETIME NULL;
ALTER TABLE credentials ADD COLUMN kind VARCHAR(30) NOT NULL DEFAULT 'completion', ADD COLUMN notes VARCHAR(255) NULL, ADD COLUMN revoke_reason VARCHAR(255) NULL;
INSERT IGNORE INTO permissions (code, module, label) VALUES ('facilities.book', 'extended', 'حجز المرافق'), ('analytics.view', 'extended', 'التحليلات المتقدمة');
INSERT IGNORE INTO role_permissions (role_id, permission_id) SELECT r.id, p.id FROM roles r JOIN permissions p ON p.code IN ('facilities.book', 'analytics.view') WHERE r.code = 'school_admin';
INSERT IGNORE INTO role_permissions (role_id, permission_id) SELECT r.id, p.id FROM roles r JOIN permissions p ON p.code IN ('facilities.book', 'analytics.view') WHERE r.code = 'vice_principal';
INSERT IGNORE INTO role_permissions (role_id, permission_id) SELECT r.id, p.id FROM roles r JOIN permissions p ON p.code = 'facilities.book' WHERE r.code = 'teacher';
-- المحاسب يصل للرواتب؟ لا: حساسة، تُمنح بالدور عند الحاجة. مدير المدرسة يملك payroll.manage أصلًا.
