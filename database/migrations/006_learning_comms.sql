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
