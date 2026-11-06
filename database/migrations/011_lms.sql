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
