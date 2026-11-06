-- المرحلة 8: الخطة الدراسية (مواد لكل سنة/فصل/صف)، المواد الاختيارية والساعات المعتمدة، والسجل الأكاديمي المجمَّد
ALTER TABLE section_subjects
  ADD COLUMN term_id INT UNSIGNED NULL AFTER subject_id,
  ADD COLUMN credit_hours DECIMAL(4,1) NOT NULL DEFAULT 1.0 AFTER max_score,
  ADD COLUMN is_elective TINYINT(1) NOT NULL DEFAULT 0 AFTER credit_hours,
  ADD CONSTRAINT fk_ss_term FOREIGN KEY (term_id) REFERENCES terms(id);

CREATE TABLE study_plans (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL,
  academic_year_id INT UNSIGNED NOT NULL, grade_level_id INT UNSIGNED NOT NULL, subject_id INT UNSIGNED NOT NULL,
  term_id INT UNSIGNED NULL COMMENT 'NULL = كل فصول السنة',
  weekly_periods TINYINT UNSIGNED NOT NULL DEFAULT 1, max_score DECIMAL(6,2) NULL, credit_hours DECIMAL(4,1) NOT NULL DEFAULT 1.0,
  is_elective TINYINT(1) NOT NULL DEFAULT 0, sort_order SMALLINT NOT NULL DEFAULT 0,
  UNIQUE KEY uq_plan (academic_year_id, grade_level_id, subject_id), INDEX (school_id),
  FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (academic_year_id) REFERENCES academic_years(id),
  FOREIGN KEY (grade_level_id) REFERENCES grade_levels(id), FOREIGN KEY (subject_id) REFERENCES subjects(id), FOREIGN KEY (term_id) REFERENCES terms(id)
) ENGINE=InnoDB;

-- الخطة الحالية تُستنتج من مواد الشعب الموجودة
INSERT IGNORE INTO study_plans (school_id, academic_year_id, grade_level_id, subject_id, weekly_periods, max_score)
  SELECT ss.school_id, sc.academic_year_id, sc.grade_level_id, ss.subject_id, MAX(ss.weekly_periods), MAX(ss.max_score)
  FROM section_subjects ss JOIN sections sc ON sc.id = ss.section_id GROUP BY ss.school_id, sc.academic_year_id, sc.grade_level_id, ss.subject_id;

-- تسجيل الطلاب في المواد الاختيارية
CREATE TABLE student_electives (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL,
  enrollment_id BIGINT UNSIGNED NOT NULL, section_subject_id INT UNSIGNED NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_el (enrollment_id, section_subject_id), INDEX (section_subject_id), INDEX (school_id),
  FOREIGN KEY (enrollment_id) REFERENCES student_enrollments(id), FOREIGN KEY (section_subject_id) REFERENCES section_subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- السجل الأكاديمي المجمَّد: لقطة نهائية لنتائج كل فصل وسنة عند إغلاق السنة (لا تتغير بتعديل أنظمة الدرجات لاحقًا)
CREATE TABLE academic_records (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL,
  student_id BIGINT UNSIGNED NOT NULL, enrollment_id BIGINT UNSIGNED NOT NULL, academic_year_id INT UNSIGNED NOT NULL,
  term_id INT UNSIGNED NULL COMMENT 'NULL = النتيجة السنوية', subject_id INT UNSIGNED NULL COMMENT 'NULL = سطر الملخص',
  year_name VARCHAR(50) NOT NULL, term_name VARCHAR(100) NULL, grade_name VARCHAR(100) NULL, section_name VARCHAR(100) NULL, subject_name VARCHAR(150) NULL,
  percent DECIMAL(6,2) NULL, total DECIMAL(7,2) NULL, max_score DECIMAL(6,2) NULL, letter VARCHAR(50) NULL, gpa_points DECIMAL(4,2) NULL, passed TINYINT(1) NULL,
  credit_hours DECIMAL(4,1) NULL, rank_in_section INT UNSIGNED NULL, result VARCHAR(100) NULL, frozen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (student_id, academic_year_id), INDEX (school_id, academic_year_id), INDEX (enrollment_id),
  FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (student_id) REFERENCES students(id), FOREIGN KEY (enrollment_id) REFERENCES student_enrollments(id)
) ENGINE=InnoDB;

-- صلاحيات مباشرة للمستخدم (منح/حجب) فوق صلاحيات أدواره
CREATE TABLE user_permissions (
  user_id BIGINT UNSIGNED NOT NULL, permission_id INT UNSIGNED NOT NULL, school_id INT UNSIGNED NOT NULL, effect ENUM('allow','deny') NOT NULL,
  granted_by BIGINT UNSIGNED NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, permission_id), INDEX (school_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE, FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE, FOREIGN KEY (school_id) REFERENCES schools(id)
) ENGINE=InnoDB;
