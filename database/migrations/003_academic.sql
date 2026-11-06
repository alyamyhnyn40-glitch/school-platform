-- الهيكل الأكاديمي
CREATE TABLE academic_years (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, name VARCHAR(60) NOT NULL,
  starts_on DATE NOT NULL, ends_on DATE NOT NULL, status ENUM('draft','active','closed') NOT NULL DEFAULT 'draft',
  closed_at DATETIME NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_year (school_id, name), FOREIGN KEY (school_id) REFERENCES schools(id)
) ENGINE=InnoDB;
CREATE TABLE terms (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, academic_year_id INT UNSIGNED NOT NULL,
  name VARCHAR(60) NOT NULL, term_no TINYINT UNSIGNED NOT NULL, starts_on DATE NOT NULL, ends_on DATE NOT NULL,
  is_final TINYINT(1) NOT NULL DEFAULT 0, grades_locked TINYINT(1) NOT NULL DEFAULT 0,
  UNIQUE KEY uq_term (academic_year_id, term_no), FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (academic_year_id) REFERENCES academic_years(id)
) ENGINE=InnoDB;
CREATE TABLE stages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, name VARCHAR(100) NOT NULL, sort_order SMALLINT NOT NULL DEFAULT 0,
  UNIQUE KEY uq_stage (school_id, name), FOREIGN KEY (school_id) REFERENCES schools(id)
) ENGINE=InnoDB;
CREATE TABLE grade_levels (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, stage_id INT UNSIGNED NOT NULL,
  name VARCHAR(100) NOT NULL, sort_order SMALLINT NOT NULL DEFAULT 0, next_grade_level_id INT UNSIGNED NULL,
  UNIQUE KEY uq_grade (school_id, name), FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (stage_id) REFERENCES stages(id),
  FOREIGN KEY (next_grade_level_id) REFERENCES grade_levels(id)
) ENGINE=InnoDB;
CREATE TABLE rooms (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, name VARCHAR(100) NOT NULL, capacity SMALLINT UNSIGNED NULL,
  kind VARCHAR(40) NULL, UNIQUE KEY uq_room (school_id, name), FOREIGN KEY (school_id) REFERENCES schools(id)
) ENGINE=InnoDB;
CREATE TABLE sections (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, academic_year_id INT UNSIGNED NOT NULL,
  grade_level_id INT UNSIGNED NOT NULL, name VARCHAR(60) NOT NULL, gender ENUM('mixed','boys','girls') NOT NULL DEFAULT 'mixed',
  capacity SMALLINT UNSIGNED NULL, homeroom_staff_id INT UNSIGNED NULL, room_id INT UNSIGNED NULL,
  UNIQUE KEY uq_section (academic_year_id, grade_level_id, name),
  FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (academic_year_id) REFERENCES academic_years(id),
  FOREIGN KEY (grade_level_id) REFERENCES grade_levels(id), FOREIGN KEY (room_id) REFERENCES rooms(id)
) ENGINE=InnoDB;
CREATE TABLE subjects (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, name VARCHAR(100) NOT NULL, code VARCHAR(30) NULL,
  UNIQUE KEY uq_subject (school_id, name), FOREIGN KEY (school_id) REFERENCES schools(id)
) ENGINE=InnoDB;
CREATE TABLE section_subjects (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, section_id INT UNSIGNED NOT NULL, subject_id INT UNSIGNED NOT NULL,
  weekly_periods TINYINT UNSIGNED NOT NULL DEFAULT 1, max_score DECIMAL(6,2) NULL,
  UNIQUE KEY uq_ss (section_id, subject_id), FOREIGN KEY (school_id) REFERENCES schools(id),
  FOREIGN KEY (section_id) REFERENCES sections(id), FOREIGN KEY (subject_id) REFERENCES subjects(id)
) ENGINE=InnoDB;
