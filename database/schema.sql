-- schema.sql: تجميع مرتب لكل migrations (يُولَّد بـ bin/build-schema.sh). للتثبيت الأنظف استخدم معالج /install
-- المنصة والهوية
CREATE TABLE schools (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(30) NOT NULL UNIQUE,
  name VARCHAR(200) NOT NULL,
  name_en VARCHAR(200) NULL,
  city VARCHAR(100) NULL, governorate VARCHAR(100) NULL, address VARCHAR(255) NULL,
  phone VARCHAR(30) NULL, email VARCHAR(190) NULL, logo_file_id BIGINT UNSIGNED NULL,
  gender_policy ENUM('mixed','boys','girls') NOT NULL DEFAULT 'mixed',
  base_currency CHAR(3) NOT NULL DEFAULT 'YER',
  timezone VARCHAR(50) NOT NULL DEFAULT 'Asia/Aden',
  status ENUM('active','suspended','archived') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NULL, deleted_at DATETIME NULL
) ENGINE=InnoDB;

CREATE TABLE subscription_plans (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL, max_students INT UNSIGNED NULL, max_storage_mb INT UNSIGNED NULL,
  modules JSON NOT NULL, price DECIMAL(14,2) NOT NULL DEFAULT 0, currency CHAR(3) NOT NULL DEFAULT 'USD',
  billing_period ENUM('monthly','yearly') NOT NULL DEFAULT 'yearly', is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE school_subscriptions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  school_id INT UNSIGNED NOT NULL, plan_id INT UNSIGNED NOT NULL,
  starts_on DATE NOT NULL, ends_on DATE NULL, status ENUM('active','expired','cancelled') NOT NULL DEFAULT 'active',
  amount DECIMAL(14,2) NOT NULL DEFAULT 0, notes VARCHAR(255) NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (plan_id) REFERENCES subscription_plans(id),
  INDEX (school_id, status)
) ENGINE=InnoDB;

CREATE TABLE school_modules (
  school_id INT UNSIGNED NOT NULL, module VARCHAR(50) NOT NULL, enabled TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (school_id, module), FOREIGN KEY (school_id) REFERENCES schools(id)
) ENGINE=InnoDB;

-- المستخدمون: school_id NULL = مشغّل المنصة
CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  school_id INT UNSIGNED NULL,
  user_type ENUM('platform','staff','student','guardian') NOT NULL,
  full_name VARCHAR(200) NOT NULL,
  email VARCHAR(190) NULL, phone VARCHAR(30) NULL,
  password_hash VARCHAR(255) NOT NULL,
  must_change_password TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('active','disabled') NOT NULL DEFAULT 'active',
  locale VARCHAR(5) NOT NULL DEFAULT 'ar', theme ENUM('light','dark','auto') NOT NULL DEFAULT 'auto',
  failed_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0, locked_until DATETIME NULL, last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NULL, deleted_at DATETIME NULL,
  UNIQUE KEY uq_email (email), UNIQUE KEY uq_phone (phone),
  INDEX (school_id, user_type), FOREIGN KEY (school_id) REFERENCES schools(id)
) ENGINE=InnoDB;

CREATE TABLE platform_admins (
  user_id BIGINT UNSIGNED PRIMARY KEY, is_owner TINYINT(1) NOT NULL DEFAULT 0, FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE permissions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, code VARCHAR(80) NOT NULL UNIQUE,
  module VARCHAR(50) NOT NULL, label VARCHAR(150) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE roles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  school_id INT UNSIGNED NULL, code VARCHAR(50) NOT NULL, name VARCHAR(120) NOT NULL,
  is_system TINYINT(1) NOT NULL DEFAULT 0, scope ENUM('platform','school') NOT NULL DEFAULT 'school',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_role (school_id, code), FOREIGN KEY (school_id) REFERENCES schools(id)
) ENGINE=InnoDB;

CREATE TABLE role_permissions (
  role_id INT UNSIGNED NOT NULL, permission_id INT UNSIGNED NOT NULL, PRIMARY KEY (role_id, permission_id),
  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE, FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE user_roles (
  user_id BIGINT UNSIGNED NOT NULL, role_id INT UNSIGNED NOT NULL, PRIMARY KEY (user_id, role_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE, FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE login_attempts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, identifier VARCHAR(190) NOT NULL, ip VARCHAR(45) NOT NULL,
  success TINYINT(1) NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX (identifier, created_at), INDEX (ip, created_at)
) ENGINE=InnoDB;

CREATE TABLE otp_codes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, phone VARCHAR(30) NOT NULL, code_hash VARCHAR(255) NOT NULL,
  attempts TINYINT UNSIGNED NOT NULL DEFAULT 0, expires_at DATETIME NOT NULL, used_at DATETIME NULL, ip VARCHAR(45) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX (phone, created_at)
) ENGINE=InnoDB;

CREATE TABLE password_resets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL, token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL, used_at DATETIME NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (token_hash), FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE rate_limits (
  rkey VARCHAR(190) PRIMARY KEY, hits INT UNSIGNED NOT NULL DEFAULT 0, window_start DATETIME NOT NULL
) ENGINE=InnoDB;

CREATE TABLE support_access_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, admin_user_id BIGINT UNSIGNED NOT NULL, school_id INT UNSIGNED NOT NULL,
  reason VARCHAR(255) NOT NULL, started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, ended_at DATETIME NULL,
  FOREIGN KEY (admin_user_id) REFERENCES users(id), FOREIGN KEY (school_id) REFERENCES schools(id)
) ENGINE=InnoDB;
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
-- المحتوى والتعليم الإلكتروني والتواصل
CREATE TABLE lessons (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, section_subject_id INT UNSIGNED NOT NULL, term_id INT UNSIGNED NULL,
  title VARCHAR(200) NOT NULL, body MEDIUMTEXT NULL, sort_order SMALLINT NOT NULL DEFAULT 0, is_published TINYINT(1) NOT NULL DEFAULT 0,
  created_by BIGINT UNSIGNED NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, deleted_at DATETIME NULL,
  FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (section_subject_id) REFERENCES section_subjects(id)
) ENGINE=InnoDB;
CREATE TABLE materials (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, lesson_id BIGINT UNSIGNED NULL, section_subject_id INT UNSIGNED NOT NULL,
  kind ENUM('file','link','video') NOT NULL, title VARCHAR(200) NOT NULL, file_id BIGINT UNSIGNED NULL, url VARCHAR(500) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, deleted_at DATETIME NULL,
  FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (section_subject_id) REFERENCES section_subjects(id)
) ENGINE=InnoDB;
CREATE TABLE assignments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, section_subject_id INT UNSIGNED NOT NULL, title VARCHAR(200) NOT NULL,
  instructions TEXT NULL, due_at DATETIME NOT NULL, max_score DECIMAL(6,2) NULL, allow_late TINYINT(1) NOT NULL DEFAULT 0,
  created_by BIGINT UNSIGNED NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, deleted_at DATETIME NULL,
  FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (section_subject_id) REFERENCES section_subjects(id)
) ENGINE=InnoDB;
CREATE TABLE submissions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, assignment_id BIGINT UNSIGNED NOT NULL, enrollment_id BIGINT UNSIGNED NOT NULL,
  body TEXT NULL, file_id BIGINT UNSIGNED NULL, submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, score DECIMAL(6,2) NULL, feedback TEXT NULL,
  graded_by BIGINT UNSIGNED NULL, graded_at DATETIME NULL, UNIQUE KEY uq_sub (assignment_id, enrollment_id),
  FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (assignment_id) REFERENCES assignments(id), FOREIGN KEY (enrollment_id) REFERENCES student_enrollments(id)
) ENGINE=InnoDB;
CREATE TABLE question_bank (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, subject_id INT UNSIGNED NOT NULL, kind ENUM('mcq','true_false','short','essay') NOT NULL,
  body TEXT NOT NULL, options JSON NULL, answer JSON NULL, points DECIMAL(5,2) NOT NULL DEFAULT 1, created_by BIGINT UNSIGNED NULL, deleted_at DATETIME NULL,
  FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (subject_id) REFERENCES subjects(id)
) ENGINE=InnoDB;
CREATE TABLE quizzes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, section_subject_id INT UNSIGNED NOT NULL, title VARCHAR(200) NOT NULL,
  opens_at DATETIME NULL, closes_at DATETIME NULL, duration_min SMALLINT UNSIGNED NULL, shuffle_questions TINYINT(1) NOT NULL DEFAULT 1,
  max_attempts TINYINT UNSIGNED NOT NULL DEFAULT 1, is_published TINYINT(1) NOT NULL DEFAULT 0, created_by BIGINT UNSIGNED NULL, deleted_at DATETIME NULL,
  FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (section_subject_id) REFERENCES section_subjects(id)
) ENGINE=InnoDB;
CREATE TABLE quiz_questions (
  quiz_id BIGINT UNSIGNED NOT NULL, question_id BIGINT UNSIGNED NOT NULL, sort_order SMALLINT NOT NULL DEFAULT 0, points DECIMAL(5,2) NULL,
  PRIMARY KEY (quiz_id, question_id), FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE, FOREIGN KEY (question_id) REFERENCES question_bank(id)
) ENGINE=InnoDB;
CREATE TABLE quiz_attempts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, quiz_id BIGINT UNSIGNED NOT NULL, enrollment_id BIGINT UNSIGNED NOT NULL,
  started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, submitted_at DATETIME NULL, question_order JSON NULL, score DECIMAL(6,2) NULL,
  status ENUM('in_progress','submitted','graded') NOT NULL DEFAULT 'in_progress',
  FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (quiz_id) REFERENCES quizzes(id), FOREIGN KEY (enrollment_id) REFERENCES student_enrollments(id)
) ENGINE=InnoDB;
CREATE TABLE quiz_answers (
  attempt_id BIGINT UNSIGNED NOT NULL, question_id BIGINT UNSIGNED NOT NULL, answer JSON NULL, score DECIMAL(5,2) NULL,
  PRIMARY KEY (attempt_id, question_id), FOREIGN KEY (attempt_id) REFERENCES quiz_attempts(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE live_sessions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, section_subject_id INT UNSIGNED NOT NULL, title VARCHAR(200) NOT NULL,
  provider ENUM('zoom','meet','jitsi','teams','bbb','other') NOT NULL, join_url VARCHAR(500) NOT NULL, starts_at DATETIME NOT NULL, ends_at DATETIME NULL,
  created_by BIGINT UNSIGNED NULL, FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (section_subject_id) REFERENCES section_subjects(id)
) ENGINE=InnoDB;
CREATE TABLE live_attendance (
  live_session_id BIGINT UNSIGNED NOT NULL, enrollment_id BIGINT UNSIGNED NOT NULL, joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (live_session_id, enrollment_id), FOREIGN KEY (live_session_id) REFERENCES live_sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE messages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, sender_user_id BIGINT UNSIGNED NOT NULL, parent_id BIGINT UNSIGNED NULL,
  subject VARCHAR(200) NULL, body TEXT NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (sender_user_id) REFERENCES users(id)
) ENGINE=InnoDB;
CREATE TABLE message_recipients (
  message_id BIGINT UNSIGNED NOT NULL, user_id BIGINT UNSIGNED NOT NULL, read_at DATETIME NULL, PRIMARY KEY (message_id, user_id),
  FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE, FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;
CREATE TABLE announcements (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, title VARCHAR(200) NOT NULL, body TEXT NOT NULL,
  audience ENUM('all','staff','students','guardians','section') NOT NULL DEFAULT 'all', section_id INT UNSIGNED NULL,
  published_at DATETIME NULL, created_by BIGINT UNSIGNED NULL, FOREIGN KEY (school_id) REFERENCES schools(id)
) ENGINE=InnoDB;
CREATE TABLE notifications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NULL, user_id BIGINT UNSIGNED NOT NULL, title VARCHAR(200) NOT NULL, body VARCHAR(500) NULL,
  url VARCHAR(255) NULL, read_at DATETIME NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX (user_id, read_at),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE notification_channels (
  user_id BIGINT UNSIGNED NOT NULL, channel ENUM('inapp','sms','whatsapp','email') NOT NULL, enabled TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (user_id, channel), FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE notification_queue (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NULL, channel ENUM('sms','whatsapp','email') NOT NULL, recipient VARCHAR(190) NOT NULL,
  body TEXT NOT NULL, status ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending', attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
  last_error VARCHAR(255) NULL, run_after DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, sent_at DATETIME NULL,
  INDEX (status, run_after)
) ENGINE=InnoDB;
-- المالية
CREATE TABLE currencies (
  code CHAR(3) PRIMARY KEY, name VARCHAR(60) NOT NULL, symbol VARCHAR(10) NOT NULL, decimals TINYINT UNSIGNED NOT NULL DEFAULT 2
) ENGINE=InnoDB;
CREATE TABLE exchange_rates (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, currency CHAR(3) NOT NULL, rate_to_base DECIMAL(18,6) NOT NULL,
  effective_from DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, created_by BIGINT UNSIGNED NULL, INDEX (school_id, currency, effective_from),
  FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (currency) REFERENCES currencies(code)
) ENGINE=InnoDB;
CREATE TABLE tax_settings (
  school_id INT UNSIGNED PRIMARY KEY, tax_enabled TINYINT(1) NOT NULL DEFAULT 0, rate_percent DECIMAL(5,2) NULL, tax_label VARCHAR(60) NULL,
  tax_number VARCHAR(60) NULL, exempt_note VARCHAR(255) NULL, FOREIGN KEY (school_id) REFERENCES schools(id)
) ENGINE=InnoDB;
CREATE TABLE payment_methods (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, kind ENUM('cash','bank','wallet','exchange') NOT NULL, name VARCHAR(100) NOT NULL,
  account_info VARCHAR(255) NULL, is_active TINYINT(1) NOT NULL DEFAULT 1, FOREIGN KEY (school_id) REFERENCES schools(id)
) ENGINE=InnoDB;
CREATE TABLE fee_structures (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, academic_year_id INT UNSIGNED NOT NULL, grade_level_id INT UNSIGNED NULL,
  name VARCHAR(120) NOT NULL, currency CHAR(3) NOT NULL, FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (academic_year_id) REFERENCES academic_years(id)
) ENGINE=InnoDB;
CREATE TABLE fee_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, structure_id INT UNSIGNED NOT NULL, name VARCHAR(120) NOT NULL,
  amount DECIMAL(14,2) NOT NULL, installments_count TINYINT UNSIGNED NOT NULL DEFAULT 1,
  FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (structure_id) REFERENCES fee_structures(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE discounts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, name VARCHAR(120) NOT NULL, kind ENUM('percent','fixed') NOT NULL,
  value DECIMAL(14,2) NOT NULL, reason ENUM('sibling','scholarship','staff_child','other') NOT NULL DEFAULT 'other', FOREIGN KEY (school_id) REFERENCES schools(id)
) ENGINE=InnoDB;
CREATE TABLE student_fees (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, enrollment_id BIGINT UNSIGNED NOT NULL, structure_id INT UNSIGNED NOT NULL,
  currency CHAR(3) NOT NULL, gross DECIMAL(14,2) NOT NULL, discount_total DECIMAL(14,2) NOT NULL DEFAULT 0, net DECIMAL(14,2) NOT NULL, discount_id INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uq_sf (enrollment_id, structure_id),
  FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (enrollment_id) REFERENCES student_enrollments(id), FOREIGN KEY (structure_id) REFERENCES fee_structures(id)
) ENGINE=InnoDB;
CREATE TABLE installments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, student_fee_id BIGINT UNSIGNED NOT NULL, seq TINYINT UNSIGNED NOT NULL,
  due_date DATE NOT NULL, amount DECIMAL(14,2) NOT NULL, paid_amount DECIMAL(14,2) NOT NULL DEFAULT 0, status ENUM('pending','partial','paid') NOT NULL DEFAULT 'pending',
  INDEX (school_id, due_date, status), FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (student_fee_id) REFERENCES student_fees(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE invoices (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, number VARCHAR(30) NOT NULL, guardian_id BIGINT UNSIGNED NULL,
  student_fee_id BIGINT UNSIGNED NULL, currency CHAR(3) NOT NULL, rate_to_base DECIMAL(18,6) NOT NULL, subtotal DECIMAL(14,2) NOT NULL,
  tax_percent DECIMAL(5,2) NULL, tax_amount DECIMAL(14,2) NOT NULL DEFAULT 0, total DECIMAL(14,2) NOT NULL,
  status ENUM('issued','partial','paid','cancelled') NOT NULL DEFAULT 'issued', issued_on DATE NOT NULL, created_by BIGINT UNSIGNED NULL,
  cancelled_reason VARCHAR(255) NULL, UNIQUE KEY uq_inv (school_id, number), FOREIGN KEY (school_id) REFERENCES schools(id)
) ENGINE=InnoDB;
CREATE TABLE invoice_lines (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, invoice_id BIGINT UNSIGNED NOT NULL, description VARCHAR(200) NOT NULL, amount DECIMAL(14,2) NOT NULL,
  FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE payments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, invoice_id BIGINT UNSIGNED NULL, installment_id BIGINT UNSIGNED NULL,
  student_fee_id BIGINT UNSIGNED NULL, method_id INT UNSIGNED NULL, currency CHAR(3) NOT NULL, amount DECIMAL(14,2) NOT NULL, rate_to_base DECIMAL(18,6) NOT NULL,
  reference_no VARCHAR(100) NULL, paid_on DATE NOT NULL, received_by BIGINT UNSIGNED NULL, voided_at DATETIME NULL, void_reason VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (school_id) REFERENCES schools(id)
) ENGINE=InnoDB;
CREATE TABLE receipts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, payment_id BIGINT UNSIGNED NOT NULL, number VARCHAR(30) NOT NULL,
  issued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uq_rcpt (school_id, number), UNIQUE KEY uq_rcpt_pay (payment_id),
  FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (payment_id) REFERENCES payments(id)
) ENGINE=InnoDB;
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
-- بوابة الطالب وولي الأمر: اسم مستخدم للدخول (رقم القيد) + صلاحية الدخول للبوابة
ALTER TABLE users ADD COLUMN username VARCHAR(60) NULL AFTER phone, ADD UNIQUE KEY uq_username (username);
INSERT IGNORE INTO permissions (code, module, label) VALUES ('portal.view', 'portal', 'بوابة الطالب وولي الأمر');
INSERT IGNORE INTO role_permissions (role_id, permission_id) SELECT r.id, p.id FROM roles r JOIN permissions p ON p.code = 'portal.view' WHERE r.code IN ('guardian', 'student');
-- المرحلة 3: الرسوم — أعمدة وجداول إضافية
ALTER TABLE fee_structures
  ADD COLUMN installments_count TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER currency,
  ADD COLUMN first_due_date DATE NULL AFTER installments_count,
  ADD COLUMN interval_months TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER first_due_date;
ALTER TABLE tax_settings ADD COLUMN prices_include_tax TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE student_fees
  ADD COLUMN tax_percent DECIMAL(5,2) NULL, ADD COLUMN tax_amount DECIMAL(14,2) NOT NULL DEFAULT 0, ADD COLUMN payable DECIMAL(14,2) NOT NULL DEFAULT 0,
  ADD COLUMN status ENUM('active','cancelled') NOT NULL DEFAULT 'active', ADD COLUMN cancel_reason VARCHAR(255) NULL, ADD COLUMN discount_reason VARCHAR(255) NULL;
ALTER TABLE payments
  ADD COLUMN fee_currency CHAR(3) NULL, ADD COLUMN fee_rate_to_base DECIMAL(18,6) NULL, ADD COLUMN applied_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  ADD INDEX idx_pay_fee (school_id, student_fee_id);
CREATE TABLE payment_allocations (
  payment_id BIGINT UNSIGNED NOT NULL, installment_id BIGINT UNSIGNED NOT NULL, amount DECIMAL(14,2) NOT NULL,
  PRIMARY KEY (payment_id, installment_id), FOREIGN KEY (payment_id) REFERENCES payments(id), FOREIGN KEY (installment_id) REFERENCES installments(id) ON DELETE CASCADE
) ENGINE=InnoDB;
-- المرحلة 4: التعليم الإلكتروني والتواصل
ALTER TABLE quizzes ADD COLUMN description TEXT NULL, ADD COLUMN show_answers ENUM('after_submit','after_close','never') NOT NULL DEFAULT 'after_submit';
ALTER TABLE quiz_attempts ADD COLUMN deadline_at DATETIME NULL, ADD COLUMN graded_at DATETIME NULL, ADD INDEX idx_qa (quiz_id, enrollment_id);
ALTER TABLE submissions ADD COLUMN is_late TINYINT(1) NOT NULL DEFAULT 0, ADD COLUMN updated_at DATETIME NULL;
ALTER TABLE live_sessions ADD COLUMN description VARCHAR(255) NULL;
ALTER TABLE announcements ADD COLUMN deleted_at DATETIME NULL;
ALTER TABLE messages ADD COLUMN thread_id BIGINT UNSIGNED NULL, ADD INDEX idx_thread (thread_id);
ALTER TABLE notifications ADD INDEX idx_notif_user (user_id, created_at);
-- المعلم ينشر إعلانات لشعبه
INSERT IGNORE INTO role_permissions (role_id, permission_id) SELECT r.id, p.id FROM roles r JOIN permissions p ON p.code = 'comms.announce' WHERE r.code = 'teacher';
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
-- المرحلة 5.1: إعدادات المنصة (مزود SMS من لوحة المشغّل) + تبديل مدارس ولي الأمر (لا تغيير في الجداول)
INSERT IGNORE INTO permissions (code, module, label) VALUES ('platform.settings', 'platform', 'إعدادات المنصة (مزود الرسائل)');
-- المرحلة 6: الدفع الإلكتروني (بوابة دفع عامة عبر HTTP + إشعار موقَّع). الإعدادات في settings (مفاتيح pay.*) والأسرار مشفّرة.
CREATE TABLE online_payments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, reference CHAR(24) NOT NULL UNIQUE,
  student_fee_id BIGINT UNSIGNED NOT NULL, guardian_user_id BIGINT UNSIGNED NULL,
  amount DECIMAL(14,2) NOT NULL, currency CHAR(3) NOT NULL,
  status ENUM('pending','paid','failed','cancelled','expired','review') NOT NULL DEFAULT 'pending',
  driver VARCHAR(20) NOT NULL, provider_ref VARCHAR(120) NULL, checkout_url VARCHAR(1000) NULL, payment_id BIGINT UNSIGNED NULL,
  failure_reason VARCHAR(255) NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NULL, paid_at DATETIME NULL,
  INDEX (school_id, status), INDEX (student_fee_id),
  FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (student_fee_id) REFERENCES student_fees(id)
) ENGINE=InnoDB;
CREATE TABLE online_payment_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, online_payment_id BIGINT UNSIGNED NULL,
  kind VARCHAR(20) NOT NULL, ok TINYINT(1) NOT NULL, detail VARCHAR(255) NULL, ip VARCHAR(45) NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (school_id, created_at), INDEX (online_payment_id), FOREIGN KEY (school_id) REFERENCES schools(id)
) ENGINE=InnoDB;
-- إعداد بوابة الدفع (مفاتيح المتجر) لمدير المدرسة فقط افتراضيًا
INSERT IGNORE INTO permissions (code, module, label) VALUES ('fees.gateway', 'fees', 'إعداد الدفع الإلكتروني');
INSERT IGNORE INTO role_permissions (role_id, permission_id) SELECT r.id, p.id FROM roles r JOIN permissions p ON p.code = 'fees.gateway' WHERE r.code = 'school_admin';
-- المرحلة 7: سجل أحداث الأمان (فشل الدخول، رفض الصلاحيات 403، 401، CSRF، تجاوز المعدل). يُنظَّف بعد 180 يومًا.
CREATE TABLE security_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  school_id INT UNSIGNED NULL, user_id BIGINT UNSIGNED NULL, user_type VARCHAR(20) NULL,
  type VARCHAR(30) NOT NULL, status SMALLINT UNSIGNED NULL, method VARCHAR(8) NULL, path VARCHAR(255) NULL, perm VARCHAR(80) NULL,
  detail VARCHAR(255) NULL, ip VARCHAR(45) NULL, ua VARCHAR(160) NULL,
  INDEX (created_at), INDEX (school_id, created_at), INDEX (type, created_at), INDEX (user_id, created_at)
) ENGINE=InnoDB;
-- المرحلة 7: رموز التحقق عبر البريد (استعادة كلمة المرور). الرمز مجزّأ ولا يُخزَّن خامًا.
CREATE TABLE email_codes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL, email VARCHAR(190) NOT NULL, purpose VARCHAR(20) NOT NULL DEFAULT 'reset',
  code_hash CHAR(64) NOT NULL, attempts TINYINT UNSIGNED NOT NULL DEFAULT 0, expires_at DATETIME NOT NULL, used_at DATETIME NULL, ip VARCHAR(45) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX (email, purpose, created_at), INDEX (user_id), FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;
-- المرحلة 7: طلبات تسجيل المدارس القادمة من صفحة الهبوط (مراجعة المشغّل: قبول/رفض).
CREATE TABLE school_requests (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  school_name VARCHAR(200) NOT NULL, city VARCHAR(100) NULL, governorate VARCHAR(100) NULL, address VARCHAR(255) NULL,
  gender_policy ENUM('mixed','boys','girls') NOT NULL DEFAULT 'mixed', stages VARCHAR(200) NULL, students_estimate INT UNSIGNED NULL,
  contact_name VARCHAR(200) NOT NULL, contact_email VARCHAR(190) NOT NULL, contact_phone VARCHAR(30) NULL, notes VARCHAR(1000) NULL,
  logo_file VARCHAR(60) NULL, ip VARCHAR(45) NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  decided_by BIGINT UNSIGNED NULL, decided_at DATETIME NULL, decision_note VARCHAR(500) NULL, school_id INT UNSIGNED NULL, plan_id INT UNSIGNED NULL,
  INDEX (status, created_at), INDEX (contact_email)
) ENGINE=InnoDB;
-- المرحلة 7: ربط المواد بالصفوف. المادة (باسمها الواحد في المدرسة) تُربط بصف أو أكثر، ولا تُسند لشعبة إلا إن كانت مرتبطة بصفها.
CREATE TABLE subject_grade_levels (
  subject_id INT UNSIGNED NOT NULL, grade_level_id INT UNSIGNED NOT NULL, school_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (subject_id, grade_level_id), INDEX (grade_level_id), INDEX (school_id),
  FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE, FOREIGN KEY (grade_level_id) REFERENCES grade_levels(id) ON DELETE CASCADE, FOREIGN KEY (school_id) REFERENCES schools(id)
) ENGINE=InnoDB;
-- ترحيل البيانات الحالية: المواد المسندة فعليًا تُربط بصفوف شعبها؛ والمواد غير المستخدمة تُربط بكل صفوف مدرستها كي لا يضيع شيء (يعدّلها المدير لاحقًا).
INSERT IGNORE INTO subject_grade_levels (subject_id, grade_level_id, school_id)
  SELECT DISTINCT ss.subject_id, sc.grade_level_id, ss.school_id FROM section_subjects ss JOIN sections sc ON sc.id = ss.section_id;
INSERT IGNORE INTO subject_grade_levels (subject_id, grade_level_id, school_id)
  SELECT s.id, g.id, s.school_id FROM subjects s JOIN grade_levels g ON g.school_id = s.school_id
  WHERE NOT EXISTS (SELECT 1 FROM subject_grade_levels x WHERE x.subject_id = s.id);
-- المرحلة 7: صور في الأسئلة وخيارات الاختيار من متعدد (ملفات في files بسياق question)
ALTER TABLE question_bank ADD COLUMN image_file_id BIGINT UNSIGNED NULL, ADD COLUMN option_images JSON NULL;
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
-- المرحلة 7: المساعد الذكي — عدّاد استخدام يومي لكل مستخدم (لا تُخزَّن نصوص المحادثات)
CREATE TABLE assistant_usage (
  user_id BIGINT UNSIGNED NOT NULL, day DATE NOT NULL, n INT UNSIGNED NOT NULL DEFAULT 0, PRIMARY KEY (user_id, day)
) ENGINE=InnoDB;
-- نسخ احتياطية لكل مدرسة (محلية وسحابية)
CREATE TABLE school_backups (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL,
  filename VARCHAR(120) NOT NULL, size_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0, kind ENUM('manual','auto') NOT NULL DEFAULT 'manual',
  encrypted TINYINT(1) NOT NULL DEFAULT 0, includes_files TINYINT(1) NOT NULL DEFAULT 1, row_count INT UNSIGNED NOT NULL DEFAULT 0, app_version VARCHAR(20) NULL,
  cloud_status ENUM('none','ok','failed') NOT NULL DEFAULT 'none', cloud_error VARCHAR(255) NULL, cloud_at DATETIME NULL,
  created_by BIGINT UNSIGNED NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (school_id, created_at), FOREIGN KEY (school_id) REFERENCES schools(id)
) ENGINE=InnoDB;
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
-- أنواع استيراد جديدة: الصفوف والمواد والشعب والدرجات
ALTER TABLE import_jobs MODIFY kind ENUM('students','guardians','staff','grades','subjects','sections','scores') NOT NULL;
