-- المستويات 3-4: رواتب، عقود، قبول، إنذار مبكر، مرافق، إرشاد، شهادات رقمية
CREATE TABLE contracts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, staff_id INT UNSIGNED NOT NULL, kind VARCHAR(50) NOT NULL,
  starts_on DATE NOT NULL, ends_on DATE NULL, base_salary DECIMAL(14,2) NOT NULL, currency CHAR(3) NOT NULL, status ENUM('active','ended') NOT NULL DEFAULT 'active',
  FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (staff_id) REFERENCES staff(id)
) ENGINE=InnoDB;
CREATE TABLE payroll_runs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, period CHAR(7) NOT NULL, status ENUM('draft','approved','paid') NOT NULL DEFAULT 'draft',
  created_by BIGINT UNSIGNED NULL, UNIQUE KEY uq_pr (school_id, period), FOREIGN KEY (school_id) REFERENCES schools(id)
) ENGINE=InnoDB;
CREATE TABLE payroll_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, run_id INT UNSIGNED NOT NULL, staff_id INT UNSIGNED NOT NULL, base DECIMAL(14,2) NOT NULL,
  allowances DECIMAL(14,2) NOT NULL DEFAULT 0, deductions DECIMAL(14,2) NOT NULL DEFAULT 0, net DECIMAL(14,2) NOT NULL, currency CHAR(3) NOT NULL,
  FOREIGN KEY (run_id) REFERENCES payroll_runs(id) ON DELETE CASCADE, FOREIGN KEY (staff_id) REFERENCES staff(id)
) ENGINE=InnoDB;
CREATE TABLE admission_applications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, applicant_name VARCHAR(200) NOT NULL, birth_date DATE NULL,
  gender ENUM('male','female') NULL, grade_level_id INT UNSIGNED NULL, guardian_name VARCHAR(200) NULL, guardian_phone VARCHAR(30) NULL,
  status ENUM('new','review','accepted','rejected','enrolled') NOT NULL DEFAULT 'new', notes TEXT NULL, student_id BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (school_id) REFERENCES schools(id)
) ENGINE=InnoDB;
CREATE TABLE admission_documents (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, application_id BIGINT UNSIGNED NOT NULL, title VARCHAR(150) NOT NULL, file_id BIGINT UNSIGNED NOT NULL,
  FOREIGN KEY (application_id) REFERENCES admission_applications(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE risk_indicators (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, code VARCHAR(50) NOT NULL, label VARCHAR(150) NOT NULL,
  threshold DECIMAL(8,2) NOT NULL, is_active TINYINT(1) NOT NULL DEFAULT 1, UNIQUE KEY uq_ri (school_id, code), FOREIGN KEY (school_id) REFERENCES schools(id)
) ENGINE=InnoDB;
CREATE TABLE risk_flags (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, student_id BIGINT UNSIGNED NOT NULL, indicator_code VARCHAR(50) NOT NULL,
  explanation VARCHAR(500) NOT NULL, status ENUM('open','reviewed','dismissed','case_opened') NOT NULL DEFAULT 'open', reviewed_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (student_id) REFERENCES students(id)
) ENGINE=InnoDB;
CREATE TABLE counseling_cases (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, student_id BIGINT UNSIGNED NOT NULL, counselor_user_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(200) NOT NULL, notes_enc TEXT NULL, status ENUM('open','follow_up','closed') NOT NULL DEFAULT 'open', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (student_id) REFERENCES students(id)
) ENGINE=InnoDB;
CREATE TABLE facilities (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, name VARCHAR(120) NOT NULL, kind VARCHAR(50) NULL, capacity SMALLINT UNSIGNED NULL,
  status ENUM('available','maintenance') NOT NULL DEFAULT 'available', FOREIGN KEY (school_id) REFERENCES schools(id)
) ENGINE=InnoDB;
CREATE TABLE facility_bookings (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, facility_id INT UNSIGNED NOT NULL, booked_by BIGINT UNSIGNED NOT NULL,
  starts_at DATETIME NOT NULL, ends_at DATETIME NOT NULL, purpose VARCHAR(200) NULL, INDEX (facility_id, starts_at),
  FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (facility_id) REFERENCES facilities(id)
) ENGINE=InnoDB;
CREATE TABLE credentials (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, student_id BIGINT UNSIGNED NOT NULL, title VARCHAR(200) NOT NULL,
  verify_code CHAR(20) NOT NULL UNIQUE, issued_on DATE NOT NULL, issued_by BIGINT UNSIGNED NULL, revoked_at DATETIME NULL,
  FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (student_id) REFERENCES students(id)
) ENGINE=InnoDB;
