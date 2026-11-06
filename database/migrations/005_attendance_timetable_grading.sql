-- الجدول والحضور والتقييم
CREATE TABLE timetable_slots (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, academic_year_id INT UNSIGNED NOT NULL,
  section_subject_id INT UNSIGNED NOT NULL, section_id INT UNSIGNED NOT NULL, staff_id INT UNSIGNED NOT NULL, room_id INT UNSIGNED NULL,
  day_of_week TINYINT UNSIGNED NOT NULL, period_no TINYINT UNSIGNED NOT NULL, starts_at TIME NULL, ends_at TIME NULL,
  UNIQUE KEY uq_t_section (academic_year_id, section_id, day_of_week, period_no),
  UNIQUE KEY uq_t_teacher (academic_year_id, staff_id, day_of_week, period_no),
  FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (academic_year_id) REFERENCES academic_years(id),
  FOREIGN KEY (section_subject_id) REFERENCES section_subjects(id), FOREIGN KEY (section_id) REFERENCES sections(id),
  FOREIGN KEY (staff_id) REFERENCES staff(id), FOREIGN KEY (room_id) REFERENCES rooms(id)
) ENGINE=InnoDB;
-- القاعة: التعارض يُفحص في الخدمة لأن room_id قد يكون NULL (UNIQUE يتجاهل NULL فيعمل أيضًا)
ALTER TABLE timetable_slots ADD UNIQUE KEY uq_t_room (academic_year_id, room_id, day_of_week, period_no);

CREATE TABLE attendance_sessions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, timetable_slot_id INT UNSIGNED NULL,
  section_id INT UNSIGNED NOT NULL, section_subject_id INT UNSIGNED NULL, session_date DATE NOT NULL, period_no TINYINT UNSIGNED NOT NULL DEFAULT 0,
  taken_by BIGINT UNSIGNED NULL, kind ENUM('class','daily','live') NOT NULL DEFAULT 'class', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_as (section_id, session_date, period_no, kind), INDEX (school_id, session_date),
  FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (section_id) REFERENCES sections(id)
) ENGINE=InnoDB;
CREATE TABLE attendance_records (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, session_id BIGINT UNSIGNED NOT NULL, enrollment_id BIGINT UNSIGNED NOT NULL,
  status ENUM('present','absent','late','excused') NOT NULL, note VARCHAR(255) NULL, client_uuid CHAR(36) NULL,
  updated_by BIGINT UNSIGNED NULL, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_ar (session_id, enrollment_id), UNIQUE KEY uq_ar_uuid (client_uuid), INDEX (school_id, enrollment_id),
  FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (session_id) REFERENCES attendance_sessions(id) ON DELETE CASCADE,
  FOREIGN KEY (enrollment_id) REFERENCES student_enrollments(id)
) ENGINE=InnoDB;

CREATE TABLE grading_schemes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, stage_id INT UNSIGNED NULL, name VARCHAR(100) NOT NULL,
  max_score DECIMAL(6,2) NOT NULL DEFAULT 100, pass_score DECIMAL(6,2) NOT NULL DEFAULT 50,
  average_method ENUM('weighted','arithmetic') NOT NULL DEFAULT 'weighted', rounding TINYINT UNSIGNED NOT NULL DEFAULT 2,
  is_default TINYINT(1) NOT NULL DEFAULT 0, FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (stage_id) REFERENCES stages(id)
) ENGINE=InnoDB;
CREATE TABLE assessment_types (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, scheme_id INT UNSIGNED NOT NULL, name VARCHAR(100) NOT NULL,
  weight DECIMAL(6,2) NOT NULL, max_score DECIMAL(6,2) NOT NULL, sort_order SMALLINT NOT NULL DEFAULT 0,
  FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (scheme_id) REFERENCES grading_schemes(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE grade_scales (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, scheme_id INT UNSIGNED NOT NULL, label VARCHAR(50) NOT NULL,
  min_percent DECIMAL(5,2) NOT NULL, gpa_points DECIMAL(4,2) NULL,
  FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (scheme_id) REFERENCES grading_schemes(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE assessments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, term_id INT UNSIGNED NOT NULL, section_subject_id INT UNSIGNED NOT NULL,
  type_id INT UNSIGNED NOT NULL, title VARCHAR(150) NOT NULL, max_score DECIMAL(6,2) NOT NULL, held_on DATE NULL,
  status ENUM('open','approved','locked') NOT NULL DEFAULT 'open', approved_by BIGINT UNSIGNED NULL, locked_at DATETIME NULL,
  created_by BIGINT UNSIGNED NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (school_id, term_id, section_subject_id), FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (term_id) REFERENCES terms(id),
  FOREIGN KEY (section_subject_id) REFERENCES section_subjects(id), FOREIGN KEY (type_id) REFERENCES assessment_types(id)
) ENGINE=InnoDB;
CREATE TABLE grades (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, assessment_id BIGINT UNSIGNED NOT NULL, enrollment_id BIGINT UNSIGNED NOT NULL,
  score DECIMAL(6,2) NULL, is_absent TINYINT(1) NOT NULL DEFAULT 0, note VARCHAR(255) NULL, client_uuid CHAR(36) NULL,
  updated_by BIGINT UNSIGNED NULL, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_g (assessment_id, enrollment_id), INDEX (school_id, enrollment_id),
  FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (assessment_id) REFERENCES assessments(id) ON DELETE CASCADE,
  FOREIGN KEY (enrollment_id) REFERENCES student_enrollments(id)
) ENGINE=InnoDB;
CREATE TABLE report_cards (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, enrollment_id BIGINT UNSIGNED NOT NULL, term_id INT UNSIGNED NULL,
  kind ENUM('term','final') NOT NULL, snapshot JSON NOT NULL, average DECIMAL(6,2) NULL, rank_in_section SMALLINT UNSIGNED NULL, result VARCHAR(30) NULL,
  issued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, issued_by BIGINT UNSIGNED NULL,
  UNIQUE KEY uq_rc (enrollment_id, term_id, kind), FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (enrollment_id) REFERENCES student_enrollments(id)
) ENGINE=InnoDB;
