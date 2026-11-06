-- بوابة الطالب وولي الأمر: اسم مستخدم للدخول (رقم القيد) + صلاحية الدخول للبوابة
ALTER TABLE users ADD COLUMN username VARCHAR(60) NULL AFTER phone, ADD UNIQUE KEY uq_username (username);
INSERT IGNORE INTO permissions (code, module, label) VALUES ('portal.view', 'portal', 'بوابة الطالب وولي الأمر');
INSERT IGNORE INTO role_permissions (role_id, permission_id) SELECT r.id, p.id FROM roles r JOIN permissions p ON p.code = 'portal.view' WHERE r.code IN ('guardian', 'student');
