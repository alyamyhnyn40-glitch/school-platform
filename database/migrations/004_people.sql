-- الموظفون والطلاب وأولياء الأمور
CREATE TABLE staff (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, user_id BIGINT UNSIGNED NULL,
  employee_no VARCHAR(30) NULL, full_name VARCHAR(200) NOT NULL, gender ENUM('male','female') NULL, birth_date DATE NULL,
  national_id_enc TEXT NULL, phone VARCHAR(30) NULL, email VARCHAR(190) NULL, address VARCHAR(255) NULL,
  job_title VARCHAR(100) NULL, specialization VARCHAR(150) NULL, is_teacher TINYINT(1) NOT NULL DEFAULT 0, hire_date DATE NULL,
  status ENUM('active','on_leave','terminated') NOT NULL DEFAULT 'active', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL, deleted_at DATETIME NULL,
  UNIQUE KEY uq_emp (school_id, employee_no), FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;
ALTER TABLE sections ADD FOREIGN KEY (homeroom_staff_id) REFERENCES staff(id);
CREATE TABLE staff_qualifications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, staff_id INT UNSIGNED NOT NULL,
  degree VARCHAR(100) NOT NULL, major VARCHAR(150) NULL, institution VARCHAR(200) NULL, year SMALLINT NULL,
  FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (staff_id) REFERENCES staff(id)
) ENGINE=InnoDB;
CREATE TABLE staff_attendance (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, staff_id INT UNSIGNED NOT NULL, att_date DATE NOT NULL,
  status ENUM('present','absent','late','excused') NOT NULL, check_in TIME NULL, check_out TIME NULL, note VARCHAR(255) NULL, recorded_by BIGINT UNSIGNED NULL,
  UNIQUE KEY uq_sa (staff_id, att_date), FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (staff_id) REFERENCES staff(id)
) ENGINE=InnoDB;
CREATE TABLE teacher_assignments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, section_subject_id INT UNSIGNED NOT NULL, staff_id INT UNSIGNED NOT NULL,
  is_primary TINYINT(1) NOT NULL DEFAULT 1, UNIQUE KEY uq_ta (section_subject_id, staff_id),
  FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (section_subject_id) REFERENCES section_subjects(id), FOREIGN KEY (staff_id) REFERENCES staff(id)
) ENGINE=InnoDB;

-- هوية الطالب العامة (D-02): بيانات تعريف دنيا فقط
CREATE TABLE student_identities (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, platform_uid CHAR(16) NOT NULL UNIQUE,
  full_name VARCHAR(200) NOT NULL, birth_date DATE NULL, gender ENUM('male','female') NULL,
  national_id_hash CHAR(64) NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX (national_id_hash)
) ENGINE=InnoDB;
CREATE TABLE students (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, identity_id BIGINT UNSIGNED NOT NULL, user_id BIGINT UNSIGNED NULL,
  student_no VARCHAR(30) NOT NULL, full_name VARCHAR(200) NOT NULL, gender ENUM('male','female') NOT NULL, birth_date DATE NULL, birth_place VARCHAR(120) NULL,
  nationality VARCHAR(60) NULL, national_id_enc TEXT NULL, address VARCHAR(255) NULL, phone VARCHAR(30) NULL,
  blood_type VARCHAR(5) NULL, health_notes_enc TEXT NULL, special_needs VARCHAR(255) NULL, previous_school VARCHAR(200) NULL,
  admission_date DATE NULL, photo_file_id BIGINT UNSIGNED NULL,
  status ENUM('active','promoted','repeated','withdrawn','transferred','graduated') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NULL, deleted_at DATETIME NULL,
  UNIQUE KEY uq_sno (school_id, student_no), INDEX (school_id, status), INDEX (school_id, full_name),
  FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (identity_id) REFERENCES student_identities(id), FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;
CREATE TABLE student_enrollments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, student_id BIGINT UNSIGNED NOT NULL,
  academic_year_id INT UNSIGNED NOT NULL, section_id INT UNSIGNED NOT NULL, roll_no SMALLINT UNSIGNED NULL,
  status ENUM('active','promoted','repeated','withdrawn','transferred') NOT NULL DEFAULT 'active', enrolled_on DATE NULL, left_on DATE NULL,
  UNIQUE KEY uq_enr (student_id, academic_year_id), INDEX (school_id, section_id),
  FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (student_id) REFERENCES students(id),
  FOREIGN KEY (academic_year_id) REFERENCES academic_years(id), FOREIGN KEY (section_id) REFERENCES sections(id)
) ENGINE=InnoDB;
CREATE TABLE guardians (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NULL UNIQUE, full_name VARCHAR(200) NOT NULL,
  phone VARCHAR(30) NULL, whatsapp VARCHAR(30) NULL, email VARCHAR(190) NULL, job VARCHAR(120) NULL, address VARCHAR(255) NULL,
  national_id_enc TEXT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, deleted_at DATETIME NULL,
  INDEX (phone), FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;
CREATE TABLE student_guardians (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, student_id BIGINT UNSIGNED NOT NULL, guardian_id BIGINT UNSIGNED NOT NULL,
  relation ENUM('father','mother','brother','sister','uncle','aunt','grandfather','grandmother','other') NOT NULL DEFAULT 'father',
  is_primary TINYINT(1) NOT NULL DEFAULT 0, can_view TINYINT(1) NOT NULL DEFAULT 1, can_pickup TINYINT(1) NOT NULL DEFAULT 1,
  restriction_note VARCHAR(255) NULL, financially_responsible TINYINT(1) NOT NULL DEFAULT 0,
  UNIQUE KEY uq_sg (student_id, guardian_id), FOREIGN KEY (school_id) REFERENCES schools(id),
  FOREIGN KEY (student_id) REFERENCES students(id), FOREIGN KEY (guardian_id) REFERENCES guardians(id)
) ENGINE=InnoDB;
CREATE TABLE student_status_history (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, student_id BIGINT UNSIGNED NOT NULL,
  academic_year_id INT UNSIGNED NULL, from_status VARCHAR(20) NULL, to_status VARCHAR(20) NOT NULL, reason VARCHAR(255) NULL,
  changed_by BIGINT UNSIGNED NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (student_id) REFERENCES students(id)
) ENGINE=InnoDB;
CREATE TABLE discipline_records (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, student_id BIGINT UNSIGNED NOT NULL,
  incident_date DATE NOT NULL, kind ENUM('violation','warning','commendation') NOT NULL, description TEXT NOT NULL, action_taken VARCHAR(255) NULL,
  recorded_by BIGINT UNSIGNED NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, deleted_at DATETIME NULL,
  FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (student_id) REFERENCES students(id)
) ENGINE=InnoDB;
CREATE TABLE student_transfers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, identity_id BIGINT UNSIGNED NOT NULL, from_school_id INT UNSIGNED NOT NULL, to_school_id INT UNSIGNED NULL,
  status ENUM('requested','approved','completed','rejected') NOT NULL DEFAULT 'requested', guardian_consent TINYINT(1) NOT NULL DEFAULT 0,
  summary_json JSON NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (identity_id) REFERENCES student_identities(id), FOREIGN KEY (from_school_id) REFERENCES schools(id)
) ENGINE=InnoDB;
CREATE TABLE consents (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, student_id BIGINT UNSIGNED NOT NULL, guardian_id BIGINT UNSIGNED NOT NULL,
  kind VARCHAR(50) NOT NULL, granted TINYINT(1) NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (school_id) REFERENCES schools(id)
) ENGINE=InnoDB;
