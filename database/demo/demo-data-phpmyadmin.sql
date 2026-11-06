SET NAMES utf8mb4; SET FOREIGN_KEY_CHECKS = 0; SET @OLD_SQL_MODE = @@SQL_MODE; SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET @sid = (SELECT id FROM schools WHERE deleted_at IS NULL ORDER BY id LIMIT 1);
SET @admin = (SELECT MIN(user_id) FROM user_roles WHERE role_id = (SELECT id FROM roles WHERE school_id = @sid AND code = 'school_admin'));
SET @scheme = (SELECT id FROM grading_schemes WHERE school_id = @sid AND is_default = 1 ORDER BY id LIMIT 1);
SET @t_work = (SELECT id FROM assessment_types WHERE scheme_id = @scheme ORDER BY sort_order LIMIT 1);
SET @t_exam = (SELECT id FROM assessment_types WHERE scheme_id = @scheme ORDER BY sort_order DESC LIMIT 1);
SELECT IF(@sid IS NULL, 'خطأ: أنشئ مدرسة أولًا من لوحة المشغّل', CONCAT('سيتم الإضافة إلى المدرسة رقم ', @sid)) AS info;
SET @b_users = (SELECT COALESCE(MAX(id),0) FROM users);
SET @b_staff = (SELECT COALESCE(MAX(id),0) FROM staff);
SET @b_students = (SELECT COALESCE(MAX(id),0) FROM students);
SET @b_student_identities = (SELECT COALESCE(MAX(id),0) FROM student_identities);
SET @b_guardians = (SELECT COALESCE(MAX(id),0) FROM guardians);
SET @b_academic_years = (SELECT COALESCE(MAX(id),0) FROM academic_years);
SET @b_terms = (SELECT COALESCE(MAX(id),0) FROM terms);
SET @b_stages = (SELECT COALESCE(MAX(id),0) FROM stages);
SET @b_grade_levels = (SELECT COALESCE(MAX(id),0) FROM grade_levels);
SET @b_rooms = (SELECT COALESCE(MAX(id),0) FROM rooms);
SET @b_sections = (SELECT COALESCE(MAX(id),0) FROM sections);
SET @b_subjects = (SELECT COALESCE(MAX(id),0) FROM subjects);
SET @b_section_subjects = (SELECT COALESCE(MAX(id),0) FROM section_subjects);
SET @b_student_enrollments = (SELECT COALESCE(MAX(id),0) FROM student_enrollments);
SET @b_student_guardians = (SELECT COALESCE(MAX(id),0) FROM student_guardians);
SET @b_timetable_slots = (SELECT COALESCE(MAX(id),0) FROM timetable_slots);
SET @b_attendance_sessions = (SELECT COALESCE(MAX(id),0) FROM attendance_sessions);
SET @b_attendance_records = (SELECT COALESCE(MAX(id),0) FROM attendance_records);
SET @b_assessments = (SELECT COALESCE(MAX(id),0) FROM assessments);
SET @b_grades = (SELECT COALESCE(MAX(id),0) FROM grades);
SET @b_report_cards = (SELECT COALESCE(MAX(id),0) FROM report_cards);
SET @b_lessons = (SELECT COALESCE(MAX(id),0) FROM lessons);
SET @b_materials = (SELECT COALESCE(MAX(id),0) FROM materials);
SET @b_assignments = (SELECT COALESCE(MAX(id),0) FROM assignments);
SET @b_submissions = (SELECT COALESCE(MAX(id),0) FROM submissions);
SET @b_question_bank = (SELECT COALESCE(MAX(id),0) FROM question_bank);
SET @b_quizzes = (SELECT COALESCE(MAX(id),0) FROM quizzes);
SET @b_quiz_attempts = (SELECT COALESCE(MAX(id),0) FROM quiz_attempts);
SET @b_live_sessions = (SELECT COALESCE(MAX(id),0) FROM live_sessions);
SET @b_messages = (SELECT COALESCE(MAX(id),0) FROM messages);
SET @b_announcements = (SELECT COALESCE(MAX(id),0) FROM announcements);
SET @b_notifications = (SELECT COALESCE(MAX(id),0) FROM notifications);
SET @b_notification_queue = (SELECT COALESCE(MAX(id),0) FROM notification_queue);
SET @b_exchange_rates = (SELECT COALESCE(MAX(id),0) FROM exchange_rates);
SET @b_payment_methods = (SELECT COALESCE(MAX(id),0) FROM payment_methods);
SET @b_fee_structures = (SELECT COALESCE(MAX(id),0) FROM fee_structures);
SET @b_fee_items = (SELECT COALESCE(MAX(id),0) FROM fee_items);
SET @b_discounts = (SELECT COALESCE(MAX(id),0) FROM discounts);
SET @b_student_fees = (SELECT COALESCE(MAX(id),0) FROM student_fees);
SET @b_installments = (SELECT COALESCE(MAX(id),0) FROM installments);
SET @b_invoices = (SELECT COALESCE(MAX(id),0) FROM invoices);
SET @b_invoice_lines = (SELECT COALESCE(MAX(id),0) FROM invoice_lines);
SET @b_payments = (SELECT COALESCE(MAX(id),0) FROM payments);
SET @b_receipts = (SELECT COALESCE(MAX(id),0) FROM receipts);
SET @b_contracts = (SELECT COALESCE(MAX(id),0) FROM contracts);
SET @b_payroll_runs = (SELECT COALESCE(MAX(id),0) FROM payroll_runs);
SET @b_payroll_items = (SELECT COALESCE(MAX(id),0) FROM payroll_items);
SET @b_admission_applications = (SELECT COALESCE(MAX(id),0) FROM admission_applications);
SET @b_risk_indicators = (SELECT COALESCE(MAX(id),0) FROM risk_indicators);
SET @b_risk_flags = (SELECT COALESCE(MAX(id),0) FROM risk_flags);
SET @b_counseling_cases = (SELECT COALESCE(MAX(id),0) FROM counseling_cases);
SET @b_counseling_notes = (SELECT COALESCE(MAX(id),0) FROM counseling_notes);
SET @b_facilities = (SELECT COALESCE(MAX(id),0) FROM facilities);
SET @b_facility_bookings = (SELECT COALESCE(MAX(id),0) FROM facility_bookings);
SET @b_credentials = (SELECT COALESCE(MAX(id),0) FROM credentials);
SET @b_discipline_records = (SELECT COALESCE(MAX(id),0) FROM discipline_records);
SET @b_student_status_history = (SELECT COALESCE(MAX(id),0) FROM student_status_history);
SET @b_student_transfers = (SELECT COALESCE(MAX(id),0) FROM student_transfers);
SET @b_consents = (SELECT COALESCE(MAX(id),0) FROM consents);
SET @b_staff_qualifications = (SELECT COALESCE(MAX(id),0) FROM staff_qualifications);
SET @b_staff_attendance = (SELECT COALESCE(MAX(id),0) FROM staff_attendance);
SET @b_teacher_assignments = (SELECT COALESCE(MAX(id),0) FROM teacher_assignments);
SET @b_import_jobs = (SELECT COALESCE(MAX(id),0) FROM import_jobs);
SET @b_import_rows = (SELECT COALESCE(MAX(id),0) FROM import_rows);
SET @b_audit_logs = (SELECT COALESCE(MAX(id),0) FROM audit_logs);
SET @b_settings = (SELECT COALESCE(MAX(id),0) FROM settings);
SET @cur_year = YEAR(CURDATE());

INSERT INTO academic_years (id, school_id, name, starts_on, ends_on, status) VALUES
  (@b_academic_years+1, @sid, 'السنة التجريبية', (CURDATE() - INTERVAL 100 DAY), (CURDATE() + INTERVAL 200 DAY), 'active');
INSERT INTO terms (id, school_id, academic_year_id, name, term_no, starts_on, ends_on, is_final, grades_locked) VALUES
  (@b_terms+1, @sid, @b_academic_years+1, 'الفصل الأول', 1, (CURDATE() - INTERVAL 100 DAY), (CURDATE() + INTERVAL 40 DAY), 0, 0),
  (@b_terms+2, @sid, @b_academic_years+1, 'الفصل الثاني', 2, (CURDATE() + INTERVAL 41 DAY), (CURDATE() + INTERVAL 200 DAY), 1, 0);
INSERT INTO stages (id, school_id, name, sort_order) VALUES
  (@b_stages+1, @sid, 'المرحلة الأساسية (تجريبي)', 1);
INSERT INTO grade_levels (id, school_id, stage_id, name, sort_order, next_grade_level_id) VALUES
  (@b_grade_levels+1, @sid, @b_stages+1, 'الصف الأول (تجريبي)', 1, @b_grade_levels+2),
  (@b_grade_levels+2, @sid, @b_stages+1, 'الصف الثاني (تجريبي)', 2, NULL);
INSERT INTO rooms (id, school_id, name, capacity, kind) VALUES
  (@b_rooms+1, @sid, 'قاعة 1 (تجريبي)', 30, 'classroom'),
  (@b_rooms+2, @sid, 'قاعة 2 (تجريبي)', 30, 'classroom'),
  (@b_rooms+3, @sid, 'مختبر العلوم (تجريبي)', 25, 'lab');
INSERT INTO subjects (id, school_id, name, code) VALUES
  (@b_subjects+1, @sid, 'الرياضيات (تجريبي)', 'MATH'),
  (@b_subjects+2, @sid, 'اللغة العربية (تجريبي)', 'ARAB'),
  (@b_subjects+3, @sid, 'العلوم (تجريبي)', 'SCI'),
  (@b_subjects+4, @sid, 'اللغة الإنجليزية (تجريبي)', 'ENG');

INSERT INTO users (id, school_id, user_type, full_name, email, phone, username, password_hash, must_change_password) VALUES
  (@b_users+1, @sid, 'staff', 'خالد أحمد المقطري', 'khaled.demo@school.test', '0771000001', NULL, '$argon2id$v=19$m=65536,t=4,p=1$ZWdSdEFSTkpCdDVDUzNuQg$/V84vHWOhkddzsTJjmusnZF2ASt/oStsKB0KqE83NKg', 0),
  (@b_users+2, @sid, 'staff', 'سلمى علي الحداد', 'salma.demo@school.test', '0771000002', NULL, '$argon2id$v=19$m=65536,t=4,p=1$ZWdSdEFSTkpCdDVDUzNuQg$/V84vHWOhkddzsTJjmusnZF2ASt/oStsKB0KqE83NKg', 0),
  (@b_users+3, @sid, 'staff', 'محمد ناصر العولقي', 'mohammed.demo@school.test', '0771000003', NULL, '$argon2id$v=19$m=65536,t=4,p=1$ZWdSdEFSTkpCdDVDUzNuQg$/V84vHWOhkddzsTJjmusnZF2ASt/oStsKB0KqE83NKg', 0),
  (@b_users+4, @sid, 'staff', 'هدى صالح بن سالم', 'huda.demo@school.test', '0771000004', NULL, '$argon2id$v=19$m=65536,t=4,p=1$ZWdSdEFSTkpCdDVDUzNuQg$/V84vHWOhkddzsTJjmusnZF2ASt/oStsKB0KqE83NKg', 0),
  (@b_users+5, @sid, 'staff', 'عبدالله يحيى الشرجبي', 'acc.demo@school.test', '0771000005', NULL, '$argon2id$v=19$m=65536,t=4,p=1$ZWdSdEFSTkpCdDVDUzNuQg$/V84vHWOhkddzsTJjmusnZF2ASt/oStsKB0KqE83NKg', 0),
  (@b_users+6, @sid, 'staff', 'ريم عبدالله القدسي', 'counselor.demo@school.test', '0771000006', NULL, '$argon2id$v=19$m=65536,t=4,p=1$ZWdSdEFSTkpCdDVDUzNuQg$/V84vHWOhkddzsTJjmusnZF2ASt/oStsKB0KqE83NKg', 0);
INSERT IGNORE INTO user_roles (user_id, role_id) VALUES
  (@b_users+1, (SELECT id FROM roles WHERE school_id = @sid AND code = 'teacher')),
  (@b_users+2, (SELECT id FROM roles WHERE school_id = @sid AND code = 'teacher')),
  (@b_users+3, (SELECT id FROM roles WHERE school_id = @sid AND code = 'teacher')),
  (@b_users+4, (SELECT id FROM roles WHERE school_id = @sid AND code = 'teacher')),
  (@b_users+5, (SELECT id FROM roles WHERE school_id = @sid AND code = 'accountant')),
  (@b_users+6, (SELECT id FROM roles WHERE school_id = @sid AND code = 'counselor'));
INSERT INTO staff (id, school_id, user_id, employee_no, full_name, gender, birth_date, phone, email, job_title, specialization, is_teacher, hire_date, status) VALUES
  (@b_staff+1, @sid, @b_users+1, 'EMP-001', 'خالد أحمد المقطري', 'male', '1985-01-15', '0771000001', 'khaled.demo@school.test', 'معلم رياضيات', 'الرياضيات', 1, (CURDATE() - INTERVAL 730 DAY), 'active'),
  (@b_staff+2, @sid, @b_users+2, 'EMP-002', 'سلمى علي الحداد', 'female', '1985-02-15', '0771000002', 'salma.demo@school.test', 'معلمة لغة عربية', 'اللغة العربية', 1, (CURDATE() - INTERVAL 760 DAY), 'active'),
  (@b_staff+3, @sid, @b_users+3, 'EMP-003', 'محمد ناصر العولقي', 'male', '1985-03-15', '0771000003', 'mohammed.demo@school.test', 'معلم علوم', 'العلوم', 1, (CURDATE() - INTERVAL 790 DAY), 'active'),
  (@b_staff+4, @sid, @b_users+4, 'EMP-004', 'هدى صالح بن سالم', 'female', '1985-04-15', '0771000004', 'huda.demo@school.test', 'معلمة لغة إنجليزية', 'اللغة الإنجليزية', 1, (CURDATE() - INTERVAL 820 DAY), 'active'),
  (@b_staff+5, @sid, @b_users+5, 'EMP-005', 'عبدالله يحيى الشرجبي', 'male', '1985-05-15', '0771000005', 'acc.demo@school.test', 'محاسب', 'المحاسبة', 0, (CURDATE() - INTERVAL 850 DAY), 'active'),
  (@b_staff+6, @sid, @b_users+6, 'EMP-006', 'ريم عبدالله القدسي', 'female', '1985-06-15', '0771000006', 'counselor.demo@school.test', 'مرشدة طلابية', 'الإرشاد النفسي', 0, (CURDATE() - INTERVAL 880 DAY), 'active');
INSERT INTO staff_qualifications (school_id, staff_id, degree, major, institution, year) VALUES
  (@sid, @b_staff+1, 'بكالوريوس', 'رياضيات', 'جامعة صنعاء', 2008),
  (@sid, @b_staff+2, 'بكالوريوس', 'لغة عربية', 'جامعة عدن', 2010),
  (@sid, @b_staff+6, 'ماجستير', 'علم النفس', 'جامعة صنعاء', 2015);
INSERT INTO staff_attendance (school_id, staff_id, att_date, status, check_in, check_out, recorded_by) VALUES
  (@sid, @b_staff+1, (CURDATE() - INTERVAL 1 DAY), 'present', '07:30:00', '13:30:00', @admin),
  (@sid, @b_staff+2, (CURDATE() - INTERVAL 1 DAY), 'present', '07:30:00', '13:30:00', @admin),
  (@sid, @b_staff+3, (CURDATE() - INTERVAL 1 DAY), 'late', '07:30:00', '13:30:00', @admin),
  (@sid, @b_staff+4, (CURDATE() - INTERVAL 1 DAY), 'present', '07:30:00', '13:30:00', @admin),
  (@sid, @b_staff+5, (CURDATE() - INTERVAL 1 DAY), 'present', '07:30:00', '13:30:00', @admin),
  (@sid, @b_staff+6, (CURDATE() - INTERVAL 1 DAY), 'present', '07:30:00', '13:30:00', @admin),
  (@sid, @b_staff+1, (CURDATE() - INTERVAL 2 DAY), 'present', '07:30:00', '13:30:00', @admin),
  (@sid, @b_staff+2, (CURDATE() - INTERVAL 2 DAY), 'present', '07:30:00', '13:30:00', @admin),
  (@sid, @b_staff+3, (CURDATE() - INTERVAL 2 DAY), 'late', '07:30:00', '13:30:00', @admin),
  (@sid, @b_staff+4, (CURDATE() - INTERVAL 2 DAY), 'present', '07:30:00', '13:30:00', @admin),
  (@sid, @b_staff+5, (CURDATE() - INTERVAL 2 DAY), 'present', '07:30:00', '13:30:00', @admin),
  (@sid, @b_staff+6, (CURDATE() - INTERVAL 2 DAY), 'present', '07:30:00', '13:30:00', @admin),
  (@sid, @b_staff+1, (CURDATE() - INTERVAL 3 DAY), 'present', '07:30:00', '13:30:00', @admin),
  (@sid, @b_staff+2, (CURDATE() - INTERVAL 3 DAY), 'present', '07:30:00', '13:30:00', @admin),
  (@sid, @b_staff+3, (CURDATE() - INTERVAL 3 DAY), 'late', '07:30:00', '13:30:00', @admin),
  (@sid, @b_staff+4, (CURDATE() - INTERVAL 3 DAY), 'present', '07:30:00', '13:30:00', @admin),
  (@sid, @b_staff+5, (CURDATE() - INTERVAL 3 DAY), 'present', '07:30:00', '13:30:00', @admin),
  (@sid, @b_staff+6, (CURDATE() - INTERVAL 3 DAY), 'present', '07:30:00', '13:30:00', @admin);
INSERT INTO sections (id, school_id, academic_year_id, grade_level_id, name, gender, capacity, homeroom_staff_id, room_id) VALUES
  (@b_sections+1, @sid, @b_academic_years+1, @b_grade_levels+1, 'أ', 'mixed', 30, @b_staff+1, @b_rooms+1),
  (@b_sections+2, @sid, @b_academic_years+1, @b_grade_levels+1, 'ب', 'mixed', 30, @b_staff+2, @b_rooms+2),
  (@b_sections+3, @sid, @b_academic_years+1, @b_grade_levels+2, 'أ', 'mixed', 30, @b_staff+3, @b_rooms+1);
INSERT INTO section_subjects (id, school_id, section_id, subject_id, weekly_periods, max_score) VALUES
  (@b_section_subjects+1, @sid, @b_sections+1, @b_subjects+1, 5, 100),
  (@b_section_subjects+2, @sid, @b_sections+1, @b_subjects+2, 5, 100),
  (@b_section_subjects+3, @sid, @b_sections+1, @b_subjects+3, 3, 100),
  (@b_section_subjects+4, @sid, @b_sections+1, @b_subjects+4, 3, 100),
  (@b_section_subjects+5, @sid, @b_sections+2, @b_subjects+1, 5, 100),
  (@b_section_subjects+6, @sid, @b_sections+2, @b_subjects+2, 5, 100),
  (@b_section_subjects+7, @sid, @b_sections+2, @b_subjects+3, 3, 100),
  (@b_section_subjects+8, @sid, @b_sections+2, @b_subjects+4, 3, 100),
  (@b_section_subjects+9, @sid, @b_sections+3, @b_subjects+1, 5, 100),
  (@b_section_subjects+10, @sid, @b_sections+3, @b_subjects+2, 5, 100),
  (@b_section_subjects+11, @sid, @b_sections+3, @b_subjects+3, 3, 100),
  (@b_section_subjects+12, @sid, @b_sections+3, @b_subjects+4, 3, 100);
INSERT INTO teacher_assignments (school_id, section_subject_id, staff_id, is_primary) VALUES
  (@sid, @b_section_subjects+1, @b_staff+1, 1),
  (@sid, @b_section_subjects+2, @b_staff+2, 1),
  (@sid, @b_section_subjects+3, @b_staff+3, 1),
  (@sid, @b_section_subjects+4, @b_staff+4, 1),
  (@sid, @b_section_subjects+5, @b_staff+1, 1),
  (@sid, @b_section_subjects+6, @b_staff+2, 1),
  (@sid, @b_section_subjects+7, @b_staff+3, 1),
  (@sid, @b_section_subjects+8, @b_staff+4, 1),
  (@sid, @b_section_subjects+9, @b_staff+1, 1),
  (@sid, @b_section_subjects+10, @b_staff+2, 1),
  (@sid, @b_section_subjects+11, @b_staff+3, 1),
  (@sid, @b_section_subjects+12, @b_staff+4, 1);

INSERT INTO users (id, school_id, user_type, full_name, email, phone, username, password_hash, must_change_password) VALUES
  (@b_users+7, @sid, 'guardian', 'أحمد صالح العمراني', NULL, '0773001001', NULL, '$argon2id$v=19$m=65536,t=4,p=1$ZWdSdEFSTkpCdDVDUzNuQg$/V84vHWOhkddzsTJjmusnZF2ASt/oStsKB0KqE83NKg', 0),
  (@b_users+8, @sid, 'guardian', 'سالم عمر باوزير', NULL, '0773001002', NULL, '$argon2id$v=19$m=65536,t=4,p=1$ZWdSdEFSTkpCdDVDUzNuQg$/V84vHWOhkddzsTJjmusnZF2ASt/oStsKB0KqE83NKg', 0),
  (@b_users+9, @sid, 'guardian', 'محمد قاسم الحكيمي', NULL, '0773001003', NULL, '$argon2id$v=19$m=65536,t=4,p=1$ZWdSdEFSTkpCdDVDUzNuQg$/V84vHWOhkddzsTJjmusnZF2ASt/oStsKB0KqE83NKg', 0),
  (@b_users+10, @sid, 'guardian', 'علي حمود الوصابي', NULL, '0773001004', NULL, '$argon2id$v=19$m=65536,t=4,p=1$ZWdSdEFSTkpCdDVDUzNuQg$/V84vHWOhkddzsTJjmusnZF2ASt/oStsKB0KqE83NKg', 0),
  (@b_users+11, @sid, 'guardian', 'حسين طاهر الأهدل', NULL, '0773001005', NULL, '$argon2id$v=19$m=65536,t=4,p=1$ZWdSdEFSTkpCdDVDUzNuQg$/V84vHWOhkddzsTJjmusnZF2ASt/oStsKB0KqE83NKg', 0),
  (@b_users+12, @sid, 'guardian', 'فهد ناصر المنصوب', NULL, '0773001006', NULL, '$argon2id$v=19$m=65536,t=4,p=1$ZWdSdEFSTkpCdDVDUzNuQg$/V84vHWOhkddzsTJjmusnZF2ASt/oStsKB0KqE83NKg', 0),
  (@b_users+13, @sid, 'guardian', 'عبدالرحمن خالد الكبسي', NULL, '0773001007', NULL, '$argon2id$v=19$m=65536,t=4,p=1$ZWdSdEFSTkpCdDVDUzNuQg$/V84vHWOhkddzsTJjmusnZF2ASt/oStsKB0KqE83NKg', 0),
  (@b_users+14, @sid, 'guardian', 'ياسر محمد الصلوي', NULL, '0773001008', NULL, '$argon2id$v=19$m=65536,t=4,p=1$ZWdSdEFSTkpCdDVDUzNuQg$/V84vHWOhkddzsTJjmusnZF2ASt/oStsKB0KqE83NKg', 0),
  (@b_users+15, @sid, 'guardian', 'جمال أمين القاضي', NULL, '0773001009', NULL, '$argon2id$v=19$m=65536,t=4,p=1$ZWdSdEFSTkpCdDVDUzNuQg$/V84vHWOhkddzsTJjmusnZF2ASt/oStsKB0KqE83NKg', 0),
  (@b_users+16, @sid, 'guardian', 'منير سعيد الشامي', NULL, '0773001010', NULL, '$argon2id$v=19$m=65536,t=4,p=1$ZWdSdEFSTkpCdDVDUzNuQg$/V84vHWOhkddzsTJjmusnZF2ASt/oStsKB0KqE83NKg', 0);
INSERT IGNORE INTO user_roles (user_id, role_id) VALUES
  (@b_users+7, (SELECT id FROM roles WHERE school_id = @sid AND code = 'guardian')),
  (@b_users+8, (SELECT id FROM roles WHERE school_id = @sid AND code = 'guardian')),
  (@b_users+9, (SELECT id FROM roles WHERE school_id = @sid AND code = 'guardian')),
  (@b_users+10, (SELECT id FROM roles WHERE school_id = @sid AND code = 'guardian')),
  (@b_users+11, (SELECT id FROM roles WHERE school_id = @sid AND code = 'guardian')),
  (@b_users+12, (SELECT id FROM roles WHERE school_id = @sid AND code = 'guardian')),
  (@b_users+13, (SELECT id FROM roles WHERE school_id = @sid AND code = 'guardian')),
  (@b_users+14, (SELECT id FROM roles WHERE school_id = @sid AND code = 'guardian')),
  (@b_users+15, (SELECT id FROM roles WHERE school_id = @sid AND code = 'guardian')),
  (@b_users+16, (SELECT id FROM roles WHERE school_id = @sid AND code = 'guardian'));
INSERT INTO guardians (id, user_id, full_name, phone, whatsapp, job, address) VALUES
  (@b_guardians+1, @b_users+7, 'أحمد صالح العمراني', '0773001001', '0773001001', 'موظف', 'صنعاء - حي التحرير'),
  (@b_guardians+2, @b_users+8, 'سالم عمر باوزير', '0773001002', '0773001002', 'موظف', 'صنعاء - حي التحرير'),
  (@b_guardians+3, @b_users+9, 'محمد قاسم الحكيمي', '0773001003', '0773001003', 'موظف', 'صنعاء - حي التحرير'),
  (@b_guardians+4, @b_users+10, 'علي حمود الوصابي', '0773001004', '0773001004', 'موظف', 'صنعاء - حي التحرير'),
  (@b_guardians+5, @b_users+11, 'حسين طاهر الأهدل', '0773001005', '0773001005', 'موظف', 'صنعاء - حي التحرير'),
  (@b_guardians+6, @b_users+12, 'فهد ناصر المنصوب', '0773001006', '0773001006', 'موظف', 'صنعاء - حي التحرير'),
  (@b_guardians+7, @b_users+13, 'عبدالرحمن خالد الكبسي', '0773001007', '0773001007', 'موظف', 'صنعاء - حي التحرير'),
  (@b_guardians+8, @b_users+14, 'ياسر محمد الصلوي', '0773001008', '0773001008', 'موظف', 'صنعاء - حي التحرير'),
  (@b_guardians+9, @b_users+15, 'جمال أمين القاضي', '0773001009', '0773001009', 'موظف', 'صنعاء - حي التحرير'),
  (@b_guardians+10, @b_users+16, 'منير سعيد الشامي', '0773001010', '0773001010', 'موظف', 'صنعاء - حي التحرير');
INSERT INTO users (id, school_id, user_type, full_name, email, phone, username, password_hash, must_change_password) VALUES
  (@b_users+17, @sid, 'student', 'يوسف أحمد العمراني', NULL, NULL, 'd0001', '$argon2id$v=19$m=65536,t=4,p=1$ZWdSdEFSTkpCdDVDUzNuQg$/V84vHWOhkddzsTJjmusnZF2ASt/oStsKB0KqE83NKg', 0),
  (@b_users+18, @sid, 'student', 'مريم أحمد العمراني', NULL, NULL, 'd0002', '$argon2id$v=19$m=65536,t=4,p=1$ZWdSdEFSTkpCdDVDUzNuQg$/V84vHWOhkddzsTJjmusnZF2ASt/oStsKB0KqE83NKg', 0),
  (@b_users+19, @sid, 'student', 'عمر سالم باوزير', NULL, NULL, 'd0003', '$argon2id$v=19$m=65536,t=4,p=1$ZWdSdEFSTkpCdDVDUzNuQg$/V84vHWOhkddzsTJjmusnZF2ASt/oStsKB0KqE83NKg', 0),
  (@b_users+20, @sid, 'student', 'نور محمد الحكيمي', NULL, NULL, 'd0004', '$argon2id$v=19$m=65536,t=4,p=1$ZWdSdEFSTkpCdDVDUzNuQg$/V84vHWOhkddzsTJjmusnZF2ASt/oStsKB0KqE83NKg', 0),
  (@b_users+21, @sid, 'student', 'أسامة علي الوصابي', NULL, NULL, 'd0005', '$argon2id$v=19$m=65536,t=4,p=1$ZWdSdEFSTkpCdDVDUzNuQg$/V84vHWOhkddzsTJjmusnZF2ASt/oStsKB0KqE83NKg', 0),
  (@b_users+22, @sid, 'student', 'لينا علي الوصابي', NULL, NULL, 'd0006', '$argon2id$v=19$m=65536,t=4,p=1$ZWdSdEFSTkpCdDVDUzNuQg$/V84vHWOhkddzsTJjmusnZF2ASt/oStsKB0KqE83NKg', 0),
  (@b_users+23, @sid, 'student', 'طارق حسين الأهدل', NULL, NULL, 'd0007', '$argon2id$v=19$m=65536,t=4,p=1$ZWdSdEFSTkpCdDVDUzNuQg$/V84vHWOhkddzsTJjmusnZF2ASt/oStsKB0KqE83NKg', 0),
  (@b_users+24, @sid, 'student', 'ديما فهد المنصوب', NULL, NULL, 'd0008', '$argon2id$v=19$m=65536,t=4,p=1$ZWdSdEFSTkpCdDVDUzNuQg$/V84vHWOhkddzsTJjmusnZF2ASt/oStsKB0KqE83NKg', 0),
  (@b_users+25, @sid, 'student', 'زيد عبدالرحمن الكبسي', NULL, NULL, 'd0009', '$argon2id$v=19$m=65536,t=4,p=1$ZWdSdEFSTkpCdDVDUzNuQg$/V84vHWOhkddzsTJjmusnZF2ASt/oStsKB0KqE83NKg', 0),
  (@b_users+26, @sid, 'student', 'رنا ياسر الصلوي', NULL, NULL, 'd0010', '$argon2id$v=19$m=65536,t=4,p=1$ZWdSdEFSTkpCdDVDUzNuQg$/V84vHWOhkddzsTJjmusnZF2ASt/oStsKB0KqE83NKg', 0),
  (@b_users+27, @sid, 'student', 'بسام جمال القاضي', NULL, NULL, 'd0011', '$argon2id$v=19$m=65536,t=4,p=1$ZWdSdEFSTkpCdDVDUzNuQg$/V84vHWOhkddzsTJjmusnZF2ASt/oStsKB0KqE83NKg', 0),
  (@b_users+28, @sid, 'student', 'هالة منير الشامي', NULL, NULL, 'd0012', '$argon2id$v=19$m=65536,t=4,p=1$ZWdSdEFSTkpCdDVDUzNuQg$/V84vHWOhkddzsTJjmusnZF2ASt/oStsKB0KqE83NKg', 0);
INSERT IGNORE INTO user_roles (user_id, role_id) VALUES
  (@b_users+17, (SELECT id FROM roles WHERE school_id = @sid AND code = 'student')),
  (@b_users+18, (SELECT id FROM roles WHERE school_id = @sid AND code = 'student')),
  (@b_users+19, (SELECT id FROM roles WHERE school_id = @sid AND code = 'student')),
  (@b_users+20, (SELECT id FROM roles WHERE school_id = @sid AND code = 'student')),
  (@b_users+21, (SELECT id FROM roles WHERE school_id = @sid AND code = 'student')),
  (@b_users+22, (SELECT id FROM roles WHERE school_id = @sid AND code = 'student')),
  (@b_users+23, (SELECT id FROM roles WHERE school_id = @sid AND code = 'student')),
  (@b_users+24, (SELECT id FROM roles WHERE school_id = @sid AND code = 'student')),
  (@b_users+25, (SELECT id FROM roles WHERE school_id = @sid AND code = 'student')),
  (@b_users+26, (SELECT id FROM roles WHERE school_id = @sid AND code = 'student')),
  (@b_users+27, (SELECT id FROM roles WHERE school_id = @sid AND code = 'student')),
  (@b_users+28, (SELECT id FROM roles WHERE school_id = @sid AND code = 'student'));
INSERT INTO student_identities (id, platform_uid, full_name, birth_date, gender) VALUES
  (@b_student_identities+1, 'DEMO000000000001', 'يوسف أحمد العمراني', '2013-02-11', 'male'),
  (@b_student_identities+2, 'DEMO000000000002', 'مريم أحمد العمراني', '2014-03-12', 'female'),
  (@b_student_identities+3, 'DEMO000000000003', 'عمر سالم باوزير', '2012-04-13', 'male'),
  (@b_student_identities+4, 'DEMO000000000004', 'نور محمد الحكيمي', '2013-05-14', 'female'),
  (@b_student_identities+5, 'DEMO000000000005', 'أسامة علي الوصابي', '2014-06-15', 'male'),
  (@b_student_identities+6, 'DEMO000000000006', 'لينا علي الوصابي', '2012-07-16', 'female'),
  (@b_student_identities+7, 'DEMO000000000007', 'طارق حسين الأهدل', '2013-08-17', 'male'),
  (@b_student_identities+8, 'DEMO000000000008', 'ديما فهد المنصوب', '2014-09-18', 'female'),
  (@b_student_identities+9, 'DEMO000000000009', 'زيد عبدالرحمن الكبسي', '2012-01-10', 'male'),
  (@b_student_identities+10, 'DEMO000000000010', 'رنا ياسر الصلوي', '2013-02-11', 'female'),
  (@b_student_identities+11, 'DEMO000000000011', 'بسام جمال القاضي', '2014-03-12', 'male'),
  (@b_student_identities+12, 'DEMO000000000012', 'هالة منير الشامي', '2012-04-13', 'female');
INSERT INTO students (id, school_id, identity_id, user_id, student_no, full_name, gender, birth_date, birth_place, nationality, address, admission_date, status) VALUES
  (@b_students+1, @sid, @b_student_identities+1, @b_users+17, 'D0001', 'يوسف أحمد العمراني', 'male', '2013-02-11', 'صنعاء', 'يمني', 'صنعاء', (CURDATE() - INTERVAL 90 DAY), 'active'),
  (@b_students+2, @sid, @b_student_identities+2, @b_users+18, 'D0002', 'مريم أحمد العمراني', 'female', '2014-03-12', 'صنعاء', 'يمني', 'صنعاء', (CURDATE() - INTERVAL 90 DAY), 'active'),
  (@b_students+3, @sid, @b_student_identities+3, @b_users+19, 'D0003', 'عمر سالم باوزير', 'male', '2012-04-13', 'صنعاء', 'يمني', 'صنعاء', (CURDATE() - INTERVAL 90 DAY), 'active'),
  (@b_students+4, @sid, @b_student_identities+4, @b_users+20, 'D0004', 'نور محمد الحكيمي', 'female', '2013-05-14', 'صنعاء', 'يمني', 'صنعاء', (CURDATE() - INTERVAL 90 DAY), 'active'),
  (@b_students+5, @sid, @b_student_identities+5, @b_users+21, 'D0005', 'أسامة علي الوصابي', 'male', '2014-06-15', 'صنعاء', 'يمني', 'صنعاء', (CURDATE() - INTERVAL 90 DAY), 'active'),
  (@b_students+6, @sid, @b_student_identities+6, @b_users+22, 'D0006', 'لينا علي الوصابي', 'female', '2012-07-16', 'صنعاء', 'يمني', 'صنعاء', (CURDATE() - INTERVAL 90 DAY), 'active'),
  (@b_students+7, @sid, @b_student_identities+7, @b_users+23, 'D0007', 'طارق حسين الأهدل', 'male', '2013-08-17', 'صنعاء', 'يمني', 'صنعاء', (CURDATE() - INTERVAL 90 DAY), 'active'),
  (@b_students+8, @sid, @b_student_identities+8, @b_users+24, 'D0008', 'ديما فهد المنصوب', 'female', '2014-09-18', 'صنعاء', 'يمني', 'صنعاء', (CURDATE() - INTERVAL 90 DAY), 'active'),
  (@b_students+9, @sid, @b_student_identities+9, @b_users+25, 'D0009', 'زيد عبدالرحمن الكبسي', 'male', '2012-01-10', 'صنعاء', 'يمني', 'صنعاء', (CURDATE() - INTERVAL 90 DAY), 'active'),
  (@b_students+10, @sid, @b_student_identities+10, @b_users+26, 'D0010', 'رنا ياسر الصلوي', 'female', '2013-02-11', 'صنعاء', 'يمني', 'صنعاء', (CURDATE() - INTERVAL 90 DAY), 'active'),
  (@b_students+11, @sid, @b_student_identities+11, @b_users+27, 'D0011', 'بسام جمال القاضي', 'male', '2014-03-12', 'صنعاء', 'يمني', 'صنعاء', (CURDATE() - INTERVAL 90 DAY), 'active'),
  (@b_students+12, @sid, @b_student_identities+12, @b_users+28, 'D0012', 'هالة منير الشامي', 'female', '2012-04-13', 'صنعاء', 'يمني', 'صنعاء', (CURDATE() - INTERVAL 90 DAY), 'active');
INSERT INTO student_enrollments (id, school_id, student_id, academic_year_id, section_id, roll_no, status, enrolled_on) VALUES
  (@b_student_enrollments+1, @sid, @b_students+1, @b_academic_years+1, @b_sections+1, 1, 'active', (CURDATE() - INTERVAL 90 DAY)),
  (@b_student_enrollments+2, @sid, @b_students+2, @b_academic_years+1, @b_sections+1, 2, 'active', (CURDATE() - INTERVAL 90 DAY)),
  (@b_student_enrollments+3, @sid, @b_students+3, @b_academic_years+1, @b_sections+1, 3, 'active', (CURDATE() - INTERVAL 90 DAY)),
  (@b_student_enrollments+4, @sid, @b_students+4, @b_academic_years+1, @b_sections+1, 4, 'active', (CURDATE() - INTERVAL 90 DAY)),
  (@b_student_enrollments+5, @sid, @b_students+5, @b_academic_years+1, @b_sections+2, 1, 'active', (CURDATE() - INTERVAL 90 DAY)),
  (@b_student_enrollments+6, @sid, @b_students+6, @b_academic_years+1, @b_sections+2, 2, 'active', (CURDATE() - INTERVAL 90 DAY)),
  (@b_student_enrollments+7, @sid, @b_students+7, @b_academic_years+1, @b_sections+2, 3, 'active', (CURDATE() - INTERVAL 90 DAY)),
  (@b_student_enrollments+8, @sid, @b_students+8, @b_academic_years+1, @b_sections+2, 4, 'active', (CURDATE() - INTERVAL 90 DAY)),
  (@b_student_enrollments+9, @sid, @b_students+9, @b_academic_years+1, @b_sections+3, 1, 'active', (CURDATE() - INTERVAL 90 DAY)),
  (@b_student_enrollments+10, @sid, @b_students+10, @b_academic_years+1, @b_sections+3, 2, 'active', (CURDATE() - INTERVAL 90 DAY)),
  (@b_student_enrollments+11, @sid, @b_students+11, @b_academic_years+1, @b_sections+3, 3, 'active', (CURDATE() - INTERVAL 90 DAY)),
  (@b_student_enrollments+12, @sid, @b_students+12, @b_academic_years+1, @b_sections+3, 4, 'active', (CURDATE() - INTERVAL 90 DAY));
INSERT INTO student_guardians (school_id, student_id, guardian_id, relation, is_primary, can_view, can_pickup, financially_responsible) VALUES
  (@sid, @b_students+1, @b_guardians+1, 'father', 1, 1, 1, 1),
  (@sid, @b_students+2, @b_guardians+1, 'father', 1, 1, 1, 1),
  (@sid, @b_students+3, @b_guardians+2, 'father', 1, 1, 1, 1),
  (@sid, @b_students+4, @b_guardians+3, 'father', 1, 1, 1, 1),
  (@sid, @b_students+5, @b_guardians+4, 'father', 1, 1, 1, 1),
  (@sid, @b_students+6, @b_guardians+4, 'father', 1, 1, 1, 1),
  (@sid, @b_students+7, @b_guardians+5, 'father', 1, 1, 1, 1),
  (@sid, @b_students+8, @b_guardians+6, 'father', 1, 1, 1, 1),
  (@sid, @b_students+9, @b_guardians+7, 'father', 1, 1, 1, 1),
  (@sid, @b_students+10, @b_guardians+8, 'father', 1, 1, 1, 1),
  (@sid, @b_students+11, @b_guardians+9, 'father', 1, 1, 1, 1),
  (@sid, @b_students+12, @b_guardians+10, 'father', 1, 1, 1, 1);
INSERT INTO student_status_history (school_id, student_id, academic_year_id, from_status, to_status, reason, changed_by) VALUES
  (@sid, @b_students+1, @b_academic_years+1, NULL, 'active', 'قيد جديد', @admin),
  (@sid, @b_students+2, @b_academic_years+1, NULL, 'active', 'قيد جديد', @admin),
  (@sid, @b_students+3, @b_academic_years+1, NULL, 'active', 'قيد جديد', @admin);
INSERT INTO discipline_records (school_id, student_id, incident_date, kind, description, action_taken, recorded_by) VALUES
  (@sid, @b_students+7, (CURDATE() - INTERVAL 5 DAY), 'violation', 'تأخر متكرر عن الطابور الصباحي', 'تنبيه شفهي وإبلاغ ولي الأمر', @admin),
  (@sid, @b_students+7, (CURDATE() - INTERVAL 3 DAY), 'violation', 'عدم إحضار الكتب', 'تنبيه', @admin),
  (@sid, @b_students+1, (CURDATE() - INTERVAL 4 DAY), 'commendation', 'مشاركة متميزة في الإذاعة المدرسية', NULL, @admin);
INSERT INTO student_transfers (identity_id, from_school_id, to_school_id, status, guardian_consent, summary_json) VALUES
  (@b_student_identities+12, @sid, NULL, 'requested', 1, '{"note": "طلب نقل تجريبي"}');
INSERT INTO consents (school_id, student_id, guardian_id, kind, granted) VALUES
  (@sid, @b_students+1, @b_guardians+1, 'photo', 1),
  (@sid, @b_students+1, @b_guardians+1, 'trips', 1),
  (@sid, @b_students+3, @b_guardians+2, 'photo', 0);
INSERT INTO counters (school_id, name, year, value) VALUES (@sid,'student_no',@cur_year,0) ON DUPLICATE KEY UPDATE value = value;

INSERT INTO timetable_slots (id, school_id, academic_year_id, section_subject_id, section_id, staff_id, room_id, day_of_week, period_no, starts_at, ends_at) VALUES
  (@b_timetable_slots+1, @sid, @b_academic_years+1, @b_section_subjects+1, @b_sections+1, @b_staff+1, @b_rooms+1, 0, 1, '07:30:00', '08:15:00'),
  (@b_timetable_slots+2, @sid, @b_academic_years+1, @b_section_subjects+2, @b_sections+1, @b_staff+2, @b_rooms+1, 0, 2, '08:20:00', '09:05:00'),
  (@b_timetable_slots+3, @sid, @b_academic_years+1, @b_section_subjects+3, @b_sections+1, @b_staff+3, @b_rooms+1, 0, 3, '09:25:00', '10:10:00'),
  (@b_timetable_slots+4, @sid, @b_academic_years+1, @b_section_subjects+4, @b_sections+1, @b_staff+4, @b_rooms+1, 0, 4, '10:15:00', '11:00:00'),
  (@b_timetable_slots+5, @sid, @b_academic_years+1, @b_section_subjects+2, @b_sections+1, @b_staff+2, @b_rooms+1, 1, 1, '07:30:00', '08:15:00'),
  (@b_timetable_slots+6, @sid, @b_academic_years+1, @b_section_subjects+3, @b_sections+1, @b_staff+3, @b_rooms+1, 1, 2, '08:20:00', '09:05:00'),
  (@b_timetable_slots+7, @sid, @b_academic_years+1, @b_section_subjects+4, @b_sections+1, @b_staff+4, @b_rooms+1, 1, 3, '09:25:00', '10:10:00'),
  (@b_timetable_slots+8, @sid, @b_academic_years+1, @b_section_subjects+1, @b_sections+1, @b_staff+1, @b_rooms+1, 1, 4, '10:15:00', '11:00:00'),
  (@b_timetable_slots+9, @sid, @b_academic_years+1, @b_section_subjects+3, @b_sections+1, @b_staff+3, @b_rooms+1, 2, 1, '07:30:00', '08:15:00'),
  (@b_timetable_slots+10, @sid, @b_academic_years+1, @b_section_subjects+4, @b_sections+1, @b_staff+4, @b_rooms+1, 2, 2, '08:20:00', '09:05:00'),
  (@b_timetable_slots+11, @sid, @b_academic_years+1, @b_section_subjects+1, @b_sections+1, @b_staff+1, @b_rooms+1, 2, 3, '09:25:00', '10:10:00'),
  (@b_timetable_slots+12, @sid, @b_academic_years+1, @b_section_subjects+2, @b_sections+1, @b_staff+2, @b_rooms+1, 2, 4, '10:15:00', '11:00:00'),
  (@b_timetable_slots+13, @sid, @b_academic_years+1, @b_section_subjects+4, @b_sections+1, @b_staff+4, @b_rooms+1, 3, 1, '07:30:00', '08:15:00'),
  (@b_timetable_slots+14, @sid, @b_academic_years+1, @b_section_subjects+1, @b_sections+1, @b_staff+1, @b_rooms+1, 3, 2, '08:20:00', '09:05:00'),
  (@b_timetable_slots+15, @sid, @b_academic_years+1, @b_section_subjects+2, @b_sections+1, @b_staff+2, @b_rooms+1, 3, 3, '09:25:00', '10:10:00'),
  (@b_timetable_slots+16, @sid, @b_academic_years+1, @b_section_subjects+3, @b_sections+1, @b_staff+3, @b_rooms+1, 3, 4, '10:15:00', '11:00:00'),
  (@b_timetable_slots+17, @sid, @b_academic_years+1, @b_section_subjects+1, @b_sections+1, @b_staff+1, @b_rooms+1, 4, 1, '07:30:00', '08:15:00'),
  (@b_timetable_slots+18, @sid, @b_academic_years+1, @b_section_subjects+2, @b_sections+1, @b_staff+2, @b_rooms+1, 4, 2, '08:20:00', '09:05:00'),
  (@b_timetable_slots+19, @sid, @b_academic_years+1, @b_section_subjects+3, @b_sections+1, @b_staff+3, @b_rooms+1, 4, 3, '09:25:00', '10:10:00'),
  (@b_timetable_slots+20, @sid, @b_academic_years+1, @b_section_subjects+4, @b_sections+1, @b_staff+4, @b_rooms+1, 4, 4, '10:15:00', '11:00:00'),
  (@b_timetable_slots+21, @sid, @b_academic_years+1, @b_section_subjects+6, @b_sections+2, @b_staff+2, @b_rooms+2, 0, 1, '07:30:00', '08:15:00'),
  (@b_timetable_slots+22, @sid, @b_academic_years+1, @b_section_subjects+7, @b_sections+2, @b_staff+3, @b_rooms+2, 0, 2, '08:20:00', '09:05:00'),
  (@b_timetable_slots+23, @sid, @b_academic_years+1, @b_section_subjects+8, @b_sections+2, @b_staff+4, @b_rooms+2, 0, 3, '09:25:00', '10:10:00'),
  (@b_timetable_slots+24, @sid, @b_academic_years+1, @b_section_subjects+5, @b_sections+2, @b_staff+1, @b_rooms+2, 0, 4, '10:15:00', '11:00:00'),
  (@b_timetable_slots+25, @sid, @b_academic_years+1, @b_section_subjects+7, @b_sections+2, @b_staff+3, @b_rooms+2, 1, 1, '07:30:00', '08:15:00'),
  (@b_timetable_slots+26, @sid, @b_academic_years+1, @b_section_subjects+8, @b_sections+2, @b_staff+4, @b_rooms+2, 1, 2, '08:20:00', '09:05:00'),
  (@b_timetable_slots+27, @sid, @b_academic_years+1, @b_section_subjects+5, @b_sections+2, @b_staff+1, @b_rooms+2, 1, 3, '09:25:00', '10:10:00'),
  (@b_timetable_slots+28, @sid, @b_academic_years+1, @b_section_subjects+6, @b_sections+2, @b_staff+2, @b_rooms+2, 1, 4, '10:15:00', '11:00:00'),
  (@b_timetable_slots+29, @sid, @b_academic_years+1, @b_section_subjects+8, @b_sections+2, @b_staff+4, @b_rooms+2, 2, 1, '07:30:00', '08:15:00'),
  (@b_timetable_slots+30, @sid, @b_academic_years+1, @b_section_subjects+5, @b_sections+2, @b_staff+1, @b_rooms+2, 2, 2, '08:20:00', '09:05:00'),
  (@b_timetable_slots+31, @sid, @b_academic_years+1, @b_section_subjects+6, @b_sections+2, @b_staff+2, @b_rooms+2, 2, 3, '09:25:00', '10:10:00'),
  (@b_timetable_slots+32, @sid, @b_academic_years+1, @b_section_subjects+7, @b_sections+2, @b_staff+3, @b_rooms+2, 2, 4, '10:15:00', '11:00:00'),
  (@b_timetable_slots+33, @sid, @b_academic_years+1, @b_section_subjects+5, @b_sections+2, @b_staff+1, @b_rooms+2, 3, 1, '07:30:00', '08:15:00'),
  (@b_timetable_slots+34, @sid, @b_academic_years+1, @b_section_subjects+6, @b_sections+2, @b_staff+2, @b_rooms+2, 3, 2, '08:20:00', '09:05:00'),
  (@b_timetable_slots+35, @sid, @b_academic_years+1, @b_section_subjects+7, @b_sections+2, @b_staff+3, @b_rooms+2, 3, 3, '09:25:00', '10:10:00'),
  (@b_timetable_slots+36, @sid, @b_academic_years+1, @b_section_subjects+8, @b_sections+2, @b_staff+4, @b_rooms+2, 3, 4, '10:15:00', '11:00:00'),
  (@b_timetable_slots+37, @sid, @b_academic_years+1, @b_section_subjects+6, @b_sections+2, @b_staff+2, @b_rooms+2, 4, 1, '07:30:00', '08:15:00'),
  (@b_timetable_slots+38, @sid, @b_academic_years+1, @b_section_subjects+7, @b_sections+2, @b_staff+3, @b_rooms+2, 4, 2, '08:20:00', '09:05:00'),
  (@b_timetable_slots+39, @sid, @b_academic_years+1, @b_section_subjects+8, @b_sections+2, @b_staff+4, @b_rooms+2, 4, 3, '09:25:00', '10:10:00'),
  (@b_timetable_slots+40, @sid, @b_academic_years+1, @b_section_subjects+5, @b_sections+2, @b_staff+1, @b_rooms+2, 4, 4, '10:15:00', '11:00:00'),
  (@b_timetable_slots+41, @sid, @b_academic_years+1, @b_section_subjects+11, @b_sections+3, @b_staff+3, NULL, 0, 1, '07:30:00', '08:15:00'),
  (@b_timetable_slots+42, @sid, @b_academic_years+1, @b_section_subjects+12, @b_sections+3, @b_staff+4, NULL, 0, 2, '08:20:00', '09:05:00'),
  (@b_timetable_slots+43, @sid, @b_academic_years+1, @b_section_subjects+9, @b_sections+3, @b_staff+1, NULL, 0, 3, '09:25:00', '10:10:00'),
  (@b_timetable_slots+44, @sid, @b_academic_years+1, @b_section_subjects+10, @b_sections+3, @b_staff+2, NULL, 0, 4, '10:15:00', '11:00:00'),
  (@b_timetable_slots+45, @sid, @b_academic_years+1, @b_section_subjects+12, @b_sections+3, @b_staff+4, NULL, 1, 1, '07:30:00', '08:15:00'),
  (@b_timetable_slots+46, @sid, @b_academic_years+1, @b_section_subjects+9, @b_sections+3, @b_staff+1, NULL, 1, 2, '08:20:00', '09:05:00'),
  (@b_timetable_slots+47, @sid, @b_academic_years+1, @b_section_subjects+10, @b_sections+3, @b_staff+2, NULL, 1, 3, '09:25:00', '10:10:00'),
  (@b_timetable_slots+48, @sid, @b_academic_years+1, @b_section_subjects+11, @b_sections+3, @b_staff+3, NULL, 1, 4, '10:15:00', '11:00:00'),
  (@b_timetable_slots+49, @sid, @b_academic_years+1, @b_section_subjects+9, @b_sections+3, @b_staff+1, NULL, 2, 1, '07:30:00', '08:15:00'),
  (@b_timetable_slots+50, @sid, @b_academic_years+1, @b_section_subjects+10, @b_sections+3, @b_staff+2, NULL, 2, 2, '08:20:00', '09:05:00'),
  (@b_timetable_slots+51, @sid, @b_academic_years+1, @b_section_subjects+11, @b_sections+3, @b_staff+3, NULL, 2, 3, '09:25:00', '10:10:00'),
  (@b_timetable_slots+52, @sid, @b_academic_years+1, @b_section_subjects+12, @b_sections+3, @b_staff+4, NULL, 2, 4, '10:15:00', '11:00:00'),
  (@b_timetable_slots+53, @sid, @b_academic_years+1, @b_section_subjects+10, @b_sections+3, @b_staff+2, NULL, 3, 1, '07:30:00', '08:15:00'),
  (@b_timetable_slots+54, @sid, @b_academic_years+1, @b_section_subjects+11, @b_sections+3, @b_staff+3, NULL, 3, 2, '08:20:00', '09:05:00'),
  (@b_timetable_slots+55, @sid, @b_academic_years+1, @b_section_subjects+12, @b_sections+3, @b_staff+4, NULL, 3, 3, '09:25:00', '10:10:00'),
  (@b_timetable_slots+56, @sid, @b_academic_years+1, @b_section_subjects+9, @b_sections+3, @b_staff+1, NULL, 3, 4, '10:15:00', '11:00:00'),
  (@b_timetable_slots+57, @sid, @b_academic_years+1, @b_section_subjects+11, @b_sections+3, @b_staff+3, NULL, 4, 1, '07:30:00', '08:15:00'),
  (@b_timetable_slots+58, @sid, @b_academic_years+1, @b_section_subjects+12, @b_sections+3, @b_staff+4, NULL, 4, 2, '08:20:00', '09:05:00'),
  (@b_timetable_slots+59, @sid, @b_academic_years+1, @b_section_subjects+9, @b_sections+3, @b_staff+1, NULL, 4, 3, '09:25:00', '10:10:00'),
  (@b_timetable_slots+60, @sid, @b_academic_years+1, @b_section_subjects+10, @b_sections+3, @b_staff+2, NULL, 4, 4, '10:15:00', '11:00:00');

INSERT INTO attendance_sessions (id, school_id, section_id, session_date, period_no, taken_by, kind) VALUES
  (@b_attendance_sessions+1, @sid, @b_sections+1, (CURDATE() - INTERVAL 1 DAY), 0, @b_users+1, 'daily'),
  (@b_attendance_sessions+2, @sid, @b_sections+1, (CURDATE() - INTERVAL 2 DAY), 0, @b_users+1, 'daily'),
  (@b_attendance_sessions+3, @sid, @b_sections+1, (CURDATE() - INTERVAL 5 DAY), 0, @b_users+1, 'daily'),
  (@b_attendance_sessions+4, @sid, @b_sections+1, (CURDATE() - INTERVAL 6 DAY), 0, @b_users+1, 'daily'),
  (@b_attendance_sessions+5, @sid, @b_sections+1, (CURDATE() - INTERVAL 7 DAY), 0, @b_users+1, 'daily'),
  (@b_attendance_sessions+6, @sid, @b_sections+1, (CURDATE() - INTERVAL 8 DAY), 0, @b_users+1, 'daily'),
  (@b_attendance_sessions+7, @sid, @b_sections+1, (CURDATE() - INTERVAL 9 DAY), 0, @b_users+1, 'daily'),
  (@b_attendance_sessions+8, @sid, @b_sections+1, (CURDATE() - INTERVAL 12 DAY), 0, @b_users+1, 'daily'),
  (@b_attendance_sessions+9, @sid, @b_sections+1, (CURDATE() - INTERVAL 13 DAY), 0, @b_users+1, 'daily'),
  (@b_attendance_sessions+10, @sid, @b_sections+1, (CURDATE() - INTERVAL 14 DAY), 0, @b_users+1, 'daily'),
  (@b_attendance_sessions+11, @sid, @b_sections+2, (CURDATE() - INTERVAL 1 DAY), 0, @b_users+2, 'daily'),
  (@b_attendance_sessions+12, @sid, @b_sections+2, (CURDATE() - INTERVAL 2 DAY), 0, @b_users+2, 'daily'),
  (@b_attendance_sessions+13, @sid, @b_sections+2, (CURDATE() - INTERVAL 5 DAY), 0, @b_users+2, 'daily'),
  (@b_attendance_sessions+14, @sid, @b_sections+2, (CURDATE() - INTERVAL 6 DAY), 0, @b_users+2, 'daily'),
  (@b_attendance_sessions+15, @sid, @b_sections+2, (CURDATE() - INTERVAL 7 DAY), 0, @b_users+2, 'daily'),
  (@b_attendance_sessions+16, @sid, @b_sections+2, (CURDATE() - INTERVAL 8 DAY), 0, @b_users+2, 'daily'),
  (@b_attendance_sessions+17, @sid, @b_sections+2, (CURDATE() - INTERVAL 9 DAY), 0, @b_users+2, 'daily'),
  (@b_attendance_sessions+18, @sid, @b_sections+2, (CURDATE() - INTERVAL 12 DAY), 0, @b_users+2, 'daily'),
  (@b_attendance_sessions+19, @sid, @b_sections+2, (CURDATE() - INTERVAL 13 DAY), 0, @b_users+2, 'daily'),
  (@b_attendance_sessions+20, @sid, @b_sections+2, (CURDATE() - INTERVAL 14 DAY), 0, @b_users+2, 'daily'),
  (@b_attendance_sessions+21, @sid, @b_sections+3, (CURDATE() - INTERVAL 1 DAY), 0, @b_users+3, 'daily'),
  (@b_attendance_sessions+22, @sid, @b_sections+3, (CURDATE() - INTERVAL 2 DAY), 0, @b_users+3, 'daily'),
  (@b_attendance_sessions+23, @sid, @b_sections+3, (CURDATE() - INTERVAL 5 DAY), 0, @b_users+3, 'daily'),
  (@b_attendance_sessions+24, @sid, @b_sections+3, (CURDATE() - INTERVAL 6 DAY), 0, @b_users+3, 'daily'),
  (@b_attendance_sessions+25, @sid, @b_sections+3, (CURDATE() - INTERVAL 7 DAY), 0, @b_users+3, 'daily'),
  (@b_attendance_sessions+26, @sid, @b_sections+3, (CURDATE() - INTERVAL 8 DAY), 0, @b_users+3, 'daily'),
  (@b_attendance_sessions+27, @sid, @b_sections+3, (CURDATE() - INTERVAL 9 DAY), 0, @b_users+3, 'daily'),
  (@b_attendance_sessions+28, @sid, @b_sections+3, (CURDATE() - INTERVAL 12 DAY), 0, @b_users+3, 'daily'),
  (@b_attendance_sessions+29, @sid, @b_sections+3, (CURDATE() - INTERVAL 13 DAY), 0, @b_users+3, 'daily'),
  (@b_attendance_sessions+30, @sid, @b_sections+3, (CURDATE() - INTERVAL 14 DAY), 0, @b_users+3, 'daily');
INSERT INTO attendance_records (school_id, session_id, enrollment_id, status, updated_by) VALUES
  (@sid, @b_attendance_sessions+1, @b_student_enrollments+1, 'present', @b_users+1),
  (@sid, @b_attendance_sessions+1, @b_student_enrollments+2, 'present', @b_users+1),
  (@sid, @b_attendance_sessions+1, @b_student_enrollments+3, 'present', @b_users+1),
  (@sid, @b_attendance_sessions+1, @b_student_enrollments+4, 'present', @b_users+1),
  (@sid, @b_attendance_sessions+2, @b_student_enrollments+1, 'present', @b_users+1),
  (@sid, @b_attendance_sessions+2, @b_student_enrollments+2, 'present', @b_users+1),
  (@sid, @b_attendance_sessions+2, @b_student_enrollments+3, 'present', @b_users+1),
  (@sid, @b_attendance_sessions+2, @b_student_enrollments+4, 'present', @b_users+1),
  (@sid, @b_attendance_sessions+3, @b_student_enrollments+1, 'present', @b_users+1),
  (@sid, @b_attendance_sessions+3, @b_student_enrollments+2, 'present', @b_users+1),
  (@sid, @b_attendance_sessions+3, @b_student_enrollments+3, 'present', @b_users+1),
  (@sid, @b_attendance_sessions+3, @b_student_enrollments+4, 'present', @b_users+1),
  (@sid, @b_attendance_sessions+4, @b_student_enrollments+1, 'present', @b_users+1),
  (@sid, @b_attendance_sessions+4, @b_student_enrollments+2, 'present', @b_users+1),
  (@sid, @b_attendance_sessions+4, @b_student_enrollments+3, 'present', @b_users+1),
  (@sid, @b_attendance_sessions+4, @b_student_enrollments+4, 'present', @b_users+1),
  (@sid, @b_attendance_sessions+5, @b_student_enrollments+1, 'present', @b_users+1),
  (@sid, @b_attendance_sessions+5, @b_student_enrollments+2, 'absent', @b_users+1),
  (@sid, @b_attendance_sessions+5, @b_student_enrollments+3, 'present', @b_users+1),
  (@sid, @b_attendance_sessions+5, @b_student_enrollments+4, 'present', @b_users+1),
  (@sid, @b_attendance_sessions+6, @b_student_enrollments+1, 'absent', @b_users+1),
  (@sid, @b_attendance_sessions+6, @b_student_enrollments+2, 'present', @b_users+1),
  (@sid, @b_attendance_sessions+6, @b_student_enrollments+3, 'present', @b_users+1),
  (@sid, @b_attendance_sessions+6, @b_student_enrollments+4, 'present', @b_users+1),
  (@sid, @b_attendance_sessions+7, @b_student_enrollments+1, 'present', @b_users+1),
  (@sid, @b_attendance_sessions+7, @b_student_enrollments+2, 'present', @b_users+1),
  (@sid, @b_attendance_sessions+7, @b_student_enrollments+3, 'present', @b_users+1),
  (@sid, @b_attendance_sessions+7, @b_student_enrollments+4, 'present', @b_users+1),
  (@sid, @b_attendance_sessions+8, @b_student_enrollments+1, 'present', @b_users+1),
  (@sid, @b_attendance_sessions+8, @b_student_enrollments+2, 'present', @b_users+1),
  (@sid, @b_attendance_sessions+8, @b_student_enrollments+3, 'present', @b_users+1),
  (@sid, @b_attendance_sessions+8, @b_student_enrollments+4, 'present', @b_users+1),
  (@sid, @b_attendance_sessions+9, @b_student_enrollments+1, 'present', @b_users+1),
  (@sid, @b_attendance_sessions+9, @b_student_enrollments+2, 'present', @b_users+1),
  (@sid, @b_attendance_sessions+9, @b_student_enrollments+3, 'present', @b_users+1),
  (@sid, @b_attendance_sessions+9, @b_student_enrollments+4, 'present', @b_users+1),
  (@sid, @b_attendance_sessions+10, @b_student_enrollments+1, 'present', @b_users+1),
  (@sid, @b_attendance_sessions+10, @b_student_enrollments+2, 'present', @b_users+1),
  (@sid, @b_attendance_sessions+10, @b_student_enrollments+3, 'present', @b_users+1),
  (@sid, @b_attendance_sessions+10, @b_student_enrollments+4, 'present', @b_users+1),
  (@sid, @b_attendance_sessions+11, @b_student_enrollments+5, 'present', @b_users+2),
  (@sid, @b_attendance_sessions+11, @b_student_enrollments+6, 'present', @b_users+2),
  (@sid, @b_attendance_sessions+11, @b_student_enrollments+7, 'absent', @b_users+2),
  (@sid, @b_attendance_sessions+11, @b_student_enrollments+8, 'present', @b_users+2),
  (@sid, @b_attendance_sessions+12, @b_student_enrollments+5, 'present', @b_users+2),
  (@sid, @b_attendance_sessions+12, @b_student_enrollments+6, 'present', @b_users+2),
  (@sid, @b_attendance_sessions+12, @b_student_enrollments+7, 'absent', @b_users+2),
  (@sid, @b_attendance_sessions+12, @b_student_enrollments+8, 'present', @b_users+2),
  (@sid, @b_attendance_sessions+13, @b_student_enrollments+5, 'present', @b_users+2),
  (@sid, @b_attendance_sessions+13, @b_student_enrollments+6, 'present', @b_users+2),
  (@sid, @b_attendance_sessions+13, @b_student_enrollments+7, 'absent', @b_users+2),
  (@sid, @b_attendance_sessions+13, @b_student_enrollments+8, 'present', @b_users+2),
  (@sid, @b_attendance_sessions+14, @b_student_enrollments+5, 'present', @b_users+2),
  (@sid, @b_attendance_sessions+14, @b_student_enrollments+6, 'excused', @b_users+2),
  (@sid, @b_attendance_sessions+14, @b_student_enrollments+7, 'absent', @b_users+2),
  (@sid, @b_attendance_sessions+14, @b_student_enrollments+8, 'present', @b_users+2),
  (@sid, @b_attendance_sessions+15, @b_student_enrollments+5, 'present', @b_users+2),
  (@sid, @b_attendance_sessions+15, @b_student_enrollments+6, 'present', @b_users+2),
  (@sid, @b_attendance_sessions+15, @b_student_enrollments+7, 'absent', @b_users+2),
  (@sid, @b_attendance_sessions+15, @b_student_enrollments+8, 'present', @b_users+2),
  (@sid, @b_attendance_sessions+16, @b_student_enrollments+5, 'present', @b_users+2),
  (@sid, @b_attendance_sessions+16, @b_student_enrollments+6, 'present', @b_users+2),
  (@sid, @b_attendance_sessions+16, @b_student_enrollments+7, 'absent', @b_users+2),
  (@sid, @b_attendance_sessions+16, @b_student_enrollments+8, 'present', @b_users+2),
  (@sid, @b_attendance_sessions+17, @b_student_enrollments+5, 'present', @b_users+2),
  (@sid, @b_attendance_sessions+17, @b_student_enrollments+6, 'present', @b_users+2),
  (@sid, @b_attendance_sessions+17, @b_student_enrollments+7, 'present', @b_users+2),
  (@sid, @b_attendance_sessions+17, @b_student_enrollments+8, 'present', @b_users+2),
  (@sid, @b_attendance_sessions+18, @b_student_enrollments+5, 'present', @b_users+2),
  (@sid, @b_attendance_sessions+18, @b_student_enrollments+6, 'present', @b_users+2),
  (@sid, @b_attendance_sessions+18, @b_student_enrollments+7, 'present', @b_users+2),
  (@sid, @b_attendance_sessions+18, @b_student_enrollments+8, 'present', @b_users+2),
  (@sid, @b_attendance_sessions+19, @b_student_enrollments+5, 'present', @b_users+2),
  (@sid, @b_attendance_sessions+19, @b_student_enrollments+6, 'present', @b_users+2),
  (@sid, @b_attendance_sessions+19, @b_student_enrollments+7, 'present', @b_users+2),
  (@sid, @b_attendance_sessions+19, @b_student_enrollments+8, 'present', @b_users+2),
  (@sid, @b_attendance_sessions+20, @b_student_enrollments+5, 'absent', @b_users+2),
  (@sid, @b_attendance_sessions+20, @b_student_enrollments+6, 'present', @b_users+2),
  (@sid, @b_attendance_sessions+20, @b_student_enrollments+7, 'present', @b_users+2),
  (@sid, @b_attendance_sessions+20, @b_student_enrollments+8, 'present', @b_users+2),
  (@sid, @b_attendance_sessions+21, @b_student_enrollments+9, 'present', @b_users+3),
  (@sid, @b_attendance_sessions+21, @b_student_enrollments+10, 'present', @b_users+3),
  (@sid, @b_attendance_sessions+21, @b_student_enrollments+11, 'present', @b_users+3),
  (@sid, @b_attendance_sessions+21, @b_student_enrollments+12, 'excused', @b_users+3),
  (@sid, @b_attendance_sessions+22, @b_student_enrollments+9, 'present', @b_users+3),
  (@sid, @b_attendance_sessions+22, @b_student_enrollments+10, 'present', @b_users+3),
  (@sid, @b_attendance_sessions+22, @b_student_enrollments+11, 'present', @b_users+3),
  (@sid, @b_attendance_sessions+22, @b_student_enrollments+12, 'present', @b_users+3),
  (@sid, @b_attendance_sessions+23, @b_student_enrollments+9, 'present', @b_users+3),
  (@sid, @b_attendance_sessions+23, @b_student_enrollments+10, 'present', @b_users+3),
  (@sid, @b_attendance_sessions+23, @b_student_enrollments+11, 'present', @b_users+3),
  (@sid, @b_attendance_sessions+23, @b_student_enrollments+12, 'present', @b_users+3),
  (@sid, @b_attendance_sessions+24, @b_student_enrollments+9, 'present', @b_users+3),
  (@sid, @b_attendance_sessions+24, @b_student_enrollments+10, 'present', @b_users+3),
  (@sid, @b_attendance_sessions+24, @b_student_enrollments+11, 'present', @b_users+3),
  (@sid, @b_attendance_sessions+24, @b_student_enrollments+12, 'present', @b_users+3),
  (@sid, @b_attendance_sessions+25, @b_student_enrollments+9, 'present', @b_users+3),
  (@sid, @b_attendance_sessions+25, @b_student_enrollments+10, 'present', @b_users+3),
  (@sid, @b_attendance_sessions+25, @b_student_enrollments+11, 'present', @b_users+3),
  (@sid, @b_attendance_sessions+25, @b_student_enrollments+12, 'present', @b_users+3),
  (@sid, @b_attendance_sessions+26, @b_student_enrollments+9, 'present', @b_users+3),
  (@sid, @b_attendance_sessions+26, @b_student_enrollments+10, 'late', @b_users+3),
  (@sid, @b_attendance_sessions+26, @b_student_enrollments+11, 'present', @b_users+3),
  (@sid, @b_attendance_sessions+26, @b_student_enrollments+12, 'present', @b_users+3),
  (@sid, @b_attendance_sessions+27, @b_student_enrollments+9, 'present', @b_users+3),
  (@sid, @b_attendance_sessions+27, @b_student_enrollments+10, 'present', @b_users+3),
  (@sid, @b_attendance_sessions+27, @b_student_enrollments+11, 'present', @b_users+3),
  (@sid, @b_attendance_sessions+27, @b_student_enrollments+12, 'late', @b_users+3),
  (@sid, @b_attendance_sessions+28, @b_student_enrollments+9, 'absent', @b_users+3),
  (@sid, @b_attendance_sessions+28, @b_student_enrollments+10, 'present', @b_users+3),
  (@sid, @b_attendance_sessions+28, @b_student_enrollments+11, 'present', @b_users+3),
  (@sid, @b_attendance_sessions+28, @b_student_enrollments+12, 'present', @b_users+3),
  (@sid, @b_attendance_sessions+29, @b_student_enrollments+9, 'present', @b_users+3),
  (@sid, @b_attendance_sessions+29, @b_student_enrollments+10, 'present', @b_users+3),
  (@sid, @b_attendance_sessions+29, @b_student_enrollments+11, 'present', @b_users+3),
  (@sid, @b_attendance_sessions+29, @b_student_enrollments+12, 'present', @b_users+3),
  (@sid, @b_attendance_sessions+30, @b_student_enrollments+9, 'present', @b_users+3),
  (@sid, @b_attendance_sessions+30, @b_student_enrollments+10, 'present', @b_users+3),
  (@sid, @b_attendance_sessions+30, @b_student_enrollments+11, 'present', @b_users+3),
  (@sid, @b_attendance_sessions+30, @b_student_enrollments+12, 'present', @b_users+3);

INSERT INTO assessments (id, school_id, term_id, section_subject_id, type_id, title, max_score, held_on, status, approved_by, created_by) VALUES
  (@b_assessments+1, @sid, @b_terms+1, @b_section_subjects+1, @t_work, 'أعمال الفصل', 30, (CURDATE() - INTERVAL 20 DAY), 'approved', @admin, @b_users+1),
  (@b_assessments+2, @sid, @b_terms+1, @b_section_subjects+1, @t_exam, 'اختبار نهاية الفصل', 70, (CURDATE() - INTERVAL 10 DAY), 'approved', @admin, @b_users+1),
  (@b_assessments+3, @sid, @b_terms+1, @b_section_subjects+2, @t_work, 'أعمال الفصل', 30, (CURDATE() - INTERVAL 20 DAY), 'approved', @admin, @b_users+2),
  (@b_assessments+4, @sid, @b_terms+1, @b_section_subjects+2, @t_exam, 'اختبار نهاية الفصل', 70, (CURDATE() - INTERVAL 10 DAY), 'approved', @admin, @b_users+2),
  (@b_assessments+5, @sid, @b_terms+1, @b_section_subjects+3, @t_work, 'أعمال الفصل', 30, (CURDATE() - INTERVAL 20 DAY), 'approved', @admin, @b_users+3),
  (@b_assessments+6, @sid, @b_terms+1, @b_section_subjects+3, @t_exam, 'اختبار نهاية الفصل', 70, (CURDATE() - INTERVAL 10 DAY), 'approved', @admin, @b_users+3),
  (@b_assessments+7, @sid, @b_terms+1, @b_section_subjects+4, @t_work, 'أعمال الفصل', 30, (CURDATE() - INTERVAL 20 DAY), 'approved', @admin, @b_users+4),
  (@b_assessments+8, @sid, @b_terms+1, @b_section_subjects+4, @t_exam, 'اختبار نهاية الفصل', 70, (CURDATE() - INTERVAL 10 DAY), 'approved', @admin, @b_users+4),
  (@b_assessments+9, @sid, @b_terms+1, @b_section_subjects+5, @t_work, 'أعمال الفصل', 30, (CURDATE() - INTERVAL 20 DAY), 'approved', @admin, @b_users+1),
  (@b_assessments+10, @sid, @b_terms+1, @b_section_subjects+5, @t_exam, 'اختبار نهاية الفصل', 70, (CURDATE() - INTERVAL 10 DAY), 'approved', @admin, @b_users+1),
  (@b_assessments+11, @sid, @b_terms+1, @b_section_subjects+6, @t_work, 'أعمال الفصل', 30, (CURDATE() - INTERVAL 20 DAY), 'approved', @admin, @b_users+2),
  (@b_assessments+12, @sid, @b_terms+1, @b_section_subjects+6, @t_exam, 'اختبار نهاية الفصل', 70, (CURDATE() - INTERVAL 10 DAY), 'approved', @admin, @b_users+2),
  (@b_assessments+13, @sid, @b_terms+1, @b_section_subjects+7, @t_work, 'أعمال الفصل', 30, (CURDATE() - INTERVAL 20 DAY), 'approved', @admin, @b_users+3),
  (@b_assessments+14, @sid, @b_terms+1, @b_section_subjects+7, @t_exam, 'اختبار نهاية الفصل', 70, (CURDATE() - INTERVAL 10 DAY), 'approved', @admin, @b_users+3),
  (@b_assessments+15, @sid, @b_terms+1, @b_section_subjects+8, @t_work, 'أعمال الفصل', 30, (CURDATE() - INTERVAL 20 DAY), 'approved', @admin, @b_users+4),
  (@b_assessments+16, @sid, @b_terms+1, @b_section_subjects+8, @t_exam, 'اختبار نهاية الفصل', 70, (CURDATE() - INTERVAL 10 DAY), 'approved', @admin, @b_users+4),
  (@b_assessments+17, @sid, @b_terms+1, @b_section_subjects+9, @t_work, 'أعمال الفصل', 30, (CURDATE() - INTERVAL 20 DAY), 'approved', @admin, @b_users+1),
  (@b_assessments+18, @sid, @b_terms+1, @b_section_subjects+9, @t_exam, 'اختبار نهاية الفصل', 70, (CURDATE() - INTERVAL 10 DAY), 'approved', @admin, @b_users+1),
  (@b_assessments+19, @sid, @b_terms+1, @b_section_subjects+10, @t_work, 'أعمال الفصل', 30, (CURDATE() - INTERVAL 20 DAY), 'approved', @admin, @b_users+2),
  (@b_assessments+20, @sid, @b_terms+1, @b_section_subjects+10, @t_exam, 'اختبار نهاية الفصل', 70, (CURDATE() - INTERVAL 10 DAY), 'approved', @admin, @b_users+2),
  (@b_assessments+21, @sid, @b_terms+1, @b_section_subjects+11, @t_work, 'أعمال الفصل', 30, (CURDATE() - INTERVAL 20 DAY), 'approved', @admin, @b_users+3),
  (@b_assessments+22, @sid, @b_terms+1, @b_section_subjects+11, @t_exam, 'اختبار نهاية الفصل', 70, (CURDATE() - INTERVAL 10 DAY), 'approved', @admin, @b_users+3),
  (@b_assessments+23, @sid, @b_terms+1, @b_section_subjects+12, @t_work, 'أعمال الفصل', 30, (CURDATE() - INTERVAL 20 DAY), 'approved', @admin, @b_users+4),
  (@b_assessments+24, @sid, @b_terms+1, @b_section_subjects+12, @t_exam, 'اختبار نهاية الفصل', 70, (CURDATE() - INTERVAL 10 DAY), 'approved', @admin, @b_users+4);
INSERT INTO grades (school_id, assessment_id, enrollment_id, score, is_absent, updated_by) VALUES
  (@sid, @b_assessments+1, @b_student_enrollments+1, 29.4, 0, @b_users+1),
  (@sid, @b_assessments+1, @b_student_enrollments+2, 23.6, 0, @b_users+1),
  (@sid, @b_assessments+1, @b_student_enrollments+3, 22.9, 0, @b_users+1),
  (@sid, @b_assessments+1, @b_student_enrollments+4, 29.2, 0, @b_users+1),
  (@sid, @b_assessments+2, @b_student_enrollments+1, 67.5, 0, @b_users+1),
  (@sid, @b_assessments+2, @b_student_enrollments+2, 48.9, 0, @b_users+1),
  (@sid, @b_assessments+2, @b_student_enrollments+3, 43.0, 0, @b_users+1),
  (@sid, @b_assessments+2, @b_student_enrollments+4, 35.5, 0, @b_users+1),
  (@sid, @b_assessments+3, @b_student_enrollments+1, 17.5, 0, @b_users+2),
  (@sid, @b_assessments+3, @b_student_enrollments+2, 18.6, 0, @b_users+2),
  (@sid, @b_assessments+3, @b_student_enrollments+3, 14.8, 0, @b_users+2),
  (@sid, @b_assessments+3, @b_student_enrollments+4, 17.1, 0, @b_users+2),
  (@sid, @b_assessments+4, @b_student_enrollments+1, 43.4, 0, @b_users+2),
  (@sid, @b_assessments+4, @b_student_enrollments+2, 52.6, 0, @b_users+2),
  (@sid, @b_assessments+4, @b_student_enrollments+3, 44.2, 0, @b_users+2),
  (@sid, @b_assessments+4, @b_student_enrollments+4, 45.0, 0, @b_users+2),
  (@sid, @b_assessments+5, @b_student_enrollments+1, 29.6, 0, @b_users+3),
  (@sid, @b_assessments+5, @b_student_enrollments+2, 22.3, 0, @b_users+3),
  (@sid, @b_assessments+5, @b_student_enrollments+3, 15.7, 0, @b_users+3),
  (@sid, @b_assessments+5, @b_student_enrollments+4, 19.7, 0, @b_users+3),
  (@sid, @b_assessments+6, @b_student_enrollments+1, 59.1, 0, @b_users+3),
  (@sid, @b_assessments+6, @b_student_enrollments+2, 44.2, 0, @b_users+3),
  (@sid, @b_assessments+6, @b_student_enrollments+3, 50.1, 0, @b_users+3),
  (@sid, @b_assessments+6, @b_student_enrollments+4, 49.2, 0, @b_users+3),
  (@sid, @b_assessments+7, @b_student_enrollments+1, 25.5, 0, @b_users+4),
  (@sid, @b_assessments+7, @b_student_enrollments+2, 28.3, 0, @b_users+4),
  (@sid, @b_assessments+7, @b_student_enrollments+3, 19.2, 0, @b_users+4),
  (@sid, @b_assessments+7, @b_student_enrollments+4, 19.9, 0, @b_users+4),
  (@sid, @b_assessments+8, @b_student_enrollments+1, 57.3, 0, @b_users+4),
  (@sid, @b_assessments+8, @b_student_enrollments+2, 45.1, 0, @b_users+4),
  (@sid, @b_assessments+8, @b_student_enrollments+3, 67.8, 0, @b_users+4),
  (@sid, @b_assessments+8, @b_student_enrollments+4, 67.0, 0, @b_users+4),
  (@sid, @b_assessments+9, @b_student_enrollments+5, 28.0, 0, @b_users+1),
  (@sid, @b_assessments+9, @b_student_enrollments+6, 19.4, 0, @b_users+1),
  (@sid, @b_assessments+9, @b_student_enrollments+7, 9.8, 0, @b_users+1),
  (@sid, @b_assessments+9, @b_student_enrollments+8, 14.6, 0, @b_users+1),
  (@sid, @b_assessments+10, @b_student_enrollments+5, 44.0, 0, @b_users+1),
  (@sid, @b_assessments+10, @b_student_enrollments+6, 64.0, 0, @b_users+1),
  (@sid, @b_assessments+10, @b_student_enrollments+7, 23.9, 0, @b_users+1),
  (@sid, @b_assessments+10, @b_student_enrollments+8, 70, 0, @b_users+1),
  (@sid, @b_assessments+11, @b_student_enrollments+5, 27.9, 0, @b_users+2),
  (@sid, @b_assessments+11, @b_student_enrollments+6, 18.0, 0, @b_users+2),
  (@sid, @b_assessments+11, @b_student_enrollments+7, 9.0, 0, @b_users+2),
  (@sid, @b_assessments+11, @b_student_enrollments+8, 19.7, 0, @b_users+2),
  (@sid, @b_assessments+12, @b_student_enrollments+5, 68.8, 0, @b_users+2),
  (@sid, @b_assessments+12, @b_student_enrollments+6, 54.3, 0, @b_users+2),
  (@sid, @b_assessments+12, @b_student_enrollments+7, 27.9, 0, @b_users+2),
  (@sid, @b_assessments+12, @b_student_enrollments+8, 42.8, 0, @b_users+2),
  (@sid, @b_assessments+13, @b_student_enrollments+5, 29.3, 0, @b_users+3),
  (@sid, @b_assessments+13, @b_student_enrollments+6, 25.8, 0, @b_users+3),
  (@sid, @b_assessments+13, @b_student_enrollments+7, 9.0, 0, @b_users+3),
  (@sid, @b_assessments+13, @b_student_enrollments+8, 25.6, 0, @b_users+3),
  (@sid, @b_assessments+14, @b_student_enrollments+5, 67.3, 0, @b_users+3),
  (@sid, @b_assessments+14, @b_student_enrollments+6, 49.0, 0, @b_users+3),
  (@sid, @b_assessments+14, @b_student_enrollments+7, 29.5, 0, @b_users+3),
  (@sid, @b_assessments+14, @b_student_enrollments+8, 56.1, 0, @b_users+3),
  (@sid, @b_assessments+15, @b_student_enrollments+5, 16.4, 0, @b_users+4),
  (@sid, @b_assessments+15, @b_student_enrollments+6, 29.4, 0, @b_users+4),
  (@sid, @b_assessments+15, @b_student_enrollments+7, 8.8, 0, @b_users+4),
  (@sid, @b_assessments+15, @b_student_enrollments+8, 29.2, 0, @b_users+4),
  (@sid, @b_assessments+16, @b_student_enrollments+5, 56.1, 0, @b_users+4),
  (@sid, @b_assessments+16, @b_student_enrollments+6, 50.5, 0, @b_users+4),
  (@sid, @b_assessments+16, @b_student_enrollments+7, 19.1, 0, @b_users+4),
  (@sid, @b_assessments+16, @b_student_enrollments+8, 68.7, 0, @b_users+4),
  (@sid, @b_assessments+17, @b_student_enrollments+9, 25.2, 0, @b_users+1),
  (@sid, @b_assessments+17, @b_student_enrollments+10, 23.8, 0, @b_users+1),
  (@sid, @b_assessments+17, @b_student_enrollments+11, 25.5, 0, @b_users+1),
  (@sid, @b_assessments+17, @b_student_enrollments+12, 18.7, 0, @b_users+1),
  (@sid, @b_assessments+18, @b_student_enrollments+9, 46.5, 0, @b_users+1),
  (@sid, @b_assessments+18, @b_student_enrollments+10, 45.2, 0, @b_users+1),
  (@sid, @b_assessments+18, @b_student_enrollments+11, 46.9, 0, @b_users+1),
  (@sid, @b_assessments+18, @b_student_enrollments+12, 48.4, 0, @b_users+1),
  (@sid, @b_assessments+19, @b_student_enrollments+9, 25.8, 0, @b_users+2),
  (@sid, @b_assessments+19, @b_student_enrollments+10, 23.8, 0, @b_users+2),
  (@sid, @b_assessments+19, @b_student_enrollments+11, 23.0, 0, @b_users+2),
  (@sid, @b_assessments+19, @b_student_enrollments+12, 20.8, 0, @b_users+2),
  (@sid, @b_assessments+20, @b_student_enrollments+9, 47.9, 0, @b_users+2),
  (@sid, @b_assessments+20, @b_student_enrollments+10, 42.0, 0, @b_users+2),
  (@sid, @b_assessments+20, @b_student_enrollments+11, 43.3, 0, @b_users+2),
  (@sid, @b_assessments+20, @b_student_enrollments+12, 60.5, 0, @b_users+2),
  (@sid, @b_assessments+21, @b_student_enrollments+9, 20.7, 0, @b_users+3),
  (@sid, @b_assessments+21, @b_student_enrollments+10, 24.9, 0, @b_users+3),
  (@sid, @b_assessments+21, @b_student_enrollments+11, 18.1, 0, @b_users+3),
  (@sid, @b_assessments+21, @b_student_enrollments+12, 18.6, 0, @b_users+3),
  (@sid, @b_assessments+22, @b_student_enrollments+9, 61.3, 0, @b_users+3),
  (@sid, @b_assessments+22, @b_student_enrollments+10, 57.9, 0, @b_users+3),
  (@sid, @b_assessments+22, @b_student_enrollments+11, 64.7, 0, @b_users+3),
  (@sid, @b_assessments+22, @b_student_enrollments+12, 56.6, 0, @b_users+3),
  (@sid, @b_assessments+23, @b_student_enrollments+9, 23.9, 0, @b_users+4),
  (@sid, @b_assessments+23, @b_student_enrollments+10, 22.4, 0, @b_users+4),
  (@sid, @b_assessments+23, @b_student_enrollments+11, 24.6, 0, @b_users+4),
  (@sid, @b_assessments+23, @b_student_enrollments+12, 27.1, 0, @b_users+4),
  (@sid, @b_assessments+24, @b_student_enrollments+9, 63.5, 0, @b_users+4),
  (@sid, @b_assessments+24, @b_student_enrollments+10, 59.9, 0, @b_users+4),
  (@sid, @b_assessments+24, @b_student_enrollments+11, 59.1, 0, @b_users+4),
  (@sid, @b_assessments+24, @b_student_enrollments+12, 41.4, 0, @b_users+4);
INSERT INTO report_cards (school_id, enrollment_id, term_id, kind, snapshot, average, rank_in_section, result, issued_by) VALUES
  (@sid, @b_student_enrollments+1, @b_terms+1, 'term', '{"school": {"name": "مدرسة (تجريبي)", "city": "", "phone": ""}, "year": "السنة التجريبية", "term": "الفصل الأول", "is_final": false, "section": "الصف الأول (تجريبي) / أ", "student": {"name": "يوسف أحمد العمراني", "no": "D0001"}, "scheme": {"name": "النظام الافتراضي", "max_score": 100, "pass_score": 50}, "subjects": [{"name": "الرياضيات (تجريبي)", "total": 96.9, "percent": 96.9, "letter": "ممتاز", "passed": true}, {"name": "اللغة العربية (تجريبي)", "total": 60.9, "percent": 60.9, "letter": "مقبول", "passed": true}, {"name": "العلوم (تجريبي)", "total": 88.7, "percent": 88.7, "letter": "جيد جدًا", "passed": true}, {"name": "اللغة الإنجليزية (تجريبي)", "total": 82.8, "percent": 82.8, "letter": "جيد جدًا", "passed": true}], "average": 82.33, "letter": "جيد جدًا", "rank": 1, "class_size": 4, "result": "ناجح", "attendance": {"absent": 0, "late": 0, "excused": 0}, "incomplete": false, "issued_at": "2026-11-03"}', 82.33, 1, 'ناجح', @admin),
  (@sid, @b_student_enrollments+2, @b_terms+1, 'term', '{"school": {"name": "مدرسة (تجريبي)", "city": "", "phone": ""}, "year": "السنة التجريبية", "term": "الفصل الأول", "is_final": false, "section": "الصف الأول (تجريبي) / أ", "student": {"name": "مريم أحمد العمراني", "no": "D0002"}, "scheme": {"name": "النظام الافتراضي", "max_score": 100, "pass_score": 50}, "subjects": [{"name": "الرياضيات (تجريبي)", "total": 72.5, "percent": 72.5, "letter": "جيد", "passed": true}, {"name": "اللغة العربية (تجريبي)", "total": 71.2, "percent": 71.2, "letter": "جيد", "passed": true}, {"name": "العلوم (تجريبي)", "total": 66.5, "percent": 66.5, "letter": "جيد", "passed": true}, {"name": "اللغة الإنجليزية (تجريبي)", "total": 73.4, "percent": 73.4, "letter": "جيد", "passed": true}], "average": 70.9, "letter": "جيد", "rank": 2, "class_size": 4, "result": "ناجح", "attendance": {"absent": 0, "late": 0, "excused": 0}, "incomplete": false, "issued_at": "2026-11-03"}', 70.9, 2, 'ناجح', @admin),
  (@sid, @b_student_enrollments+4, @b_terms+1, 'term', '{"school": {"name": "مدرسة (تجريبي)", "city": "", "phone": ""}, "year": "السنة التجريبية", "term": "الفصل الأول", "is_final": false, "section": "الصف الأول (تجريبي) / أ", "student": {"name": "نور محمد الحكيمي", "no": "D0004"}, "scheme": {"name": "النظام الافتراضي", "max_score": 100, "pass_score": 50}, "subjects": [{"name": "الرياضيات (تجريبي)", "total": 64.7, "percent": 64.7, "letter": "مقبول", "passed": true}, {"name": "اللغة العربية (تجريبي)", "total": 62.1, "percent": 62.1, "letter": "مقبول", "passed": true}, {"name": "العلوم (تجريبي)", "total": 68.9, "percent": 68.9, "letter": "جيد", "passed": true}, {"name": "اللغة الإنجليزية (تجريبي)", "total": 86.9, "percent": 86.9, "letter": "جيد جدًا", "passed": true}], "average": 70.65, "letter": "جيد", "rank": 3, "class_size": 4, "result": "ناجح", "attendance": {"absent": 0, "late": 0, "excused": 0}, "incomplete": false, "issued_at": "2026-11-03"}', 70.65, 3, 'ناجح', @admin),
  (@sid, @b_student_enrollments+3, @b_terms+1, 'term', '{"school": {"name": "مدرسة (تجريبي)", "city": "", "phone": ""}, "year": "السنة التجريبية", "term": "الفصل الأول", "is_final": false, "section": "الصف الأول (تجريبي) / أ", "student": {"name": "عمر سالم باوزير", "no": "D0003"}, "scheme": {"name": "النظام الافتراضي", "max_score": 100, "pass_score": 50}, "subjects": [{"name": "الرياضيات (تجريبي)", "total": 65.9, "percent": 65.9, "letter": "جيد", "passed": true}, {"name": "اللغة العربية (تجريبي)", "total": 59.0, "percent": 59.0, "letter": "مقبول", "passed": true}, {"name": "العلوم (تجريبي)", "total": 65.8, "percent": 65.8, "letter": "جيد", "passed": true}, {"name": "اللغة الإنجليزية (تجريبي)", "total": 87.0, "percent": 87.0, "letter": "جيد جدًا", "passed": true}], "average": 69.42, "letter": "جيد", "rank": 4, "class_size": 4, "result": "ناجح", "attendance": {"absent": 0, "late": 0, "excused": 0}, "incomplete": false, "issued_at": "2026-11-03"}', 69.42, 4, 'ناجح', @admin),
  (@sid, @b_student_enrollments+5, @b_terms+1, 'term', '{"school": {"name": "مدرسة (تجريبي)", "city": "", "phone": ""}, "year": "السنة التجريبية", "term": "الفصل الأول", "is_final": false, "section": "الصف الأول (تجريبي) / ب", "student": {"name": "أسامة علي الوصابي", "no": "D0005"}, "scheme": {"name": "النظام الافتراضي", "max_score": 100, "pass_score": 50}, "subjects": [{"name": "الرياضيات (تجريبي)", "total": 72.0, "percent": 72.0, "letter": "جيد", "passed": true}, {"name": "اللغة العربية (تجريبي)", "total": 96.7, "percent": 96.7, "letter": "ممتاز", "passed": true}, {"name": "العلوم (تجريبي)", "total": 96.6, "percent": 96.6, "letter": "ممتاز", "passed": true}, {"name": "اللغة الإنجليزية (تجريبي)", "total": 72.5, "percent": 72.5, "letter": "جيد", "passed": true}], "average": 84.45, "letter": "جيد جدًا", "rank": 1, "class_size": 4, "result": "ناجح", "attendance": {"absent": 0, "late": 0, "excused": 0}, "incomplete": false, "issued_at": "2026-11-03"}', 84.45, 1, 'ناجح', @admin),
  (@sid, @b_student_enrollments+8, @b_terms+1, 'term', '{"school": {"name": "مدرسة (تجريبي)", "city": "", "phone": ""}, "year": "السنة التجريبية", "term": "الفصل الأول", "is_final": false, "section": "الصف الأول (تجريبي) / ب", "student": {"name": "ديما فهد المنصوب", "no": "D0008"}, "scheme": {"name": "النظام الافتراضي", "max_score": 100, "pass_score": 50}, "subjects": [{"name": "الرياضيات (تجريبي)", "total": 84.6, "percent": 84.6, "letter": "جيد جدًا", "passed": true}, {"name": "اللغة العربية (تجريبي)", "total": 62.5, "percent": 62.5, "letter": "مقبول", "passed": true}, {"name": "العلوم (تجريبي)", "total": 81.7, "percent": 81.7, "letter": "جيد جدًا", "passed": true}, {"name": "اللغة الإنجليزية (تجريبي)", "total": 97.9, "percent": 97.9, "letter": "ممتاز", "passed": true}], "average": 81.67, "letter": "جيد جدًا", "rank": 2, "class_size": 4, "result": "ناجح", "attendance": {"absent": 0, "late": 0, "excused": 0}, "incomplete": false, "issued_at": "2026-11-03"}', 81.67, 2, 'ناجح', @admin),
  (@sid, @b_student_enrollments+6, @b_terms+1, 'term', '{"school": {"name": "مدرسة (تجريبي)", "city": "", "phone": ""}, "year": "السنة التجريبية", "term": "الفصل الأول", "is_final": false, "section": "الصف الأول (تجريبي) / ب", "student": {"name": "لينا علي الوصابي", "no": "D0006"}, "scheme": {"name": "النظام الافتراضي", "max_score": 100, "pass_score": 50}, "subjects": [{"name": "الرياضيات (تجريبي)", "total": 83.4, "percent": 83.4, "letter": "جيد جدًا", "passed": true}, {"name": "اللغة العربية (تجريبي)", "total": 72.3, "percent": 72.3, "letter": "جيد", "passed": true}, {"name": "العلوم (تجريبي)", "total": 74.8, "percent": 74.8, "letter": "جيد", "passed": true}, {"name": "اللغة الإنجليزية (تجريبي)", "total": 79.9, "percent": 79.9, "letter": "جيد", "passed": true}], "average": 77.6, "letter": "جيد", "rank": 3, "class_size": 4, "result": "ناجح", "attendance": {"absent": 0, "late": 0, "excused": 0}, "incomplete": false, "issued_at": "2026-11-03"}', 77.6, 3, 'ناجح', @admin),
  (@sid, @b_student_enrollments+7, @b_terms+1, 'term', '{"school": {"name": "مدرسة (تجريبي)", "city": "", "phone": ""}, "year": "السنة التجريبية", "term": "الفصل الأول", "is_final": false, "section": "الصف الأول (تجريبي) / ب", "student": {"name": "طارق حسين الأهدل", "no": "D0007"}, "scheme": {"name": "النظام الافتراضي", "max_score": 100, "pass_score": 50}, "subjects": [{"name": "الرياضيات (تجريبي)", "total": 33.7, "percent": 33.7, "letter": "ضعيف", "passed": false}, {"name": "اللغة العربية (تجريبي)", "total": 36.9, "percent": 36.9, "letter": "ضعيف", "passed": false}, {"name": "العلوم (تجريبي)", "total": 38.5, "percent": 38.5, "letter": "ضعيف", "passed": false}, {"name": "اللغة الإنجليزية (تجريبي)", "total": 27.9, "percent": 27.9, "letter": "ضعيف", "passed": false}], "average": 34.25, "letter": "ضعيف", "rank": 4, "class_size": 4, "result": "مكمل", "attendance": {"absent": 0, "late": 0, "excused": 0}, "incomplete": false, "issued_at": "2026-11-03"}', 34.25, 4, 'مكمل', @admin),
  (@sid, @b_student_enrollments+9, @b_terms+1, 'term', '{"school": {"name": "مدرسة (تجريبي)", "city": "", "phone": ""}, "year": "السنة التجريبية", "term": "الفصل الأول", "is_final": false, "section": "الصف الثاني (تجريبي) / أ", "student": {"name": "زيد عبدالرحمن الكبسي", "no": "D0009"}, "scheme": {"name": "النظام الافتراضي", "max_score": 100, "pass_score": 50}, "subjects": [{"name": "الرياضيات (تجريبي)", "total": 71.7, "percent": 71.7, "letter": "جيد", "passed": true}, {"name": "اللغة العربية (تجريبي)", "total": 73.7, "percent": 73.7, "letter": "جيد", "passed": true}, {"name": "العلوم (تجريبي)", "total": 82.0, "percent": 82.0, "letter": "جيد جدًا", "passed": true}, {"name": "اللغة الإنجليزية (تجريبي)", "total": 87.4, "percent": 87.4, "letter": "جيد جدًا", "passed": true}], "average": 78.7, "letter": "جيد", "rank": 1, "class_size": 4, "result": "ناجح", "attendance": {"absent": 0, "late": 0, "excused": 0}, "incomplete": false, "issued_at": "2026-11-03"}', 78.7, 1, 'ناجح', @admin),
  (@sid, @b_student_enrollments+11, @b_terms+1, 'term', '{"school": {"name": "مدرسة (تجريبي)", "city": "", "phone": ""}, "year": "السنة التجريبية", "term": "الفصل الأول", "is_final": false, "section": "الصف الثاني (تجريبي) / أ", "student": {"name": "بسام جمال القاضي", "no": "D0011"}, "scheme": {"name": "النظام الافتراضي", "max_score": 100, "pass_score": 50}, "subjects": [{"name": "الرياضيات (تجريبي)", "total": 72.4, "percent": 72.4, "letter": "جيد", "passed": true}, {"name": "اللغة العربية (تجريبي)", "total": 66.3, "percent": 66.3, "letter": "جيد", "passed": true}, {"name": "العلوم (تجريبي)", "total": 82.8, "percent": 82.8, "letter": "جيد جدًا", "passed": true}, {"name": "اللغة الإنجليزية (تجريبي)", "total": 83.7, "percent": 83.7, "letter": "جيد جدًا", "passed": true}], "average": 76.3, "letter": "جيد", "rank": 2, "class_size": 4, "result": "ناجح", "attendance": {"absent": 0, "late": 0, "excused": 0}, "incomplete": false, "issued_at": "2026-11-03"}', 76.3, 2, 'ناجح', @admin),
  (@sid, @b_student_enrollments+10, @b_terms+1, 'term', '{"school": {"name": "مدرسة (تجريبي)", "city": "", "phone": ""}, "year": "السنة التجريبية", "term": "الفصل الأول", "is_final": false, "section": "الصف الثاني (تجريبي) / أ", "student": {"name": "رنا ياسر الصلوي", "no": "D0010"}, "scheme": {"name": "النظام الافتراضي", "max_score": 100, "pass_score": 50}, "subjects": [{"name": "الرياضيات (تجريبي)", "total": 69.0, "percent": 69.0, "letter": "جيد", "passed": true}, {"name": "اللغة العربية (تجريبي)", "total": 65.8, "percent": 65.8, "letter": "جيد", "passed": true}, {"name": "العلوم (تجريبي)", "total": 82.8, "percent": 82.8, "letter": "جيد جدًا", "passed": true}, {"name": "اللغة الإنجليزية (تجريبي)", "total": 82.3, "percent": 82.3, "letter": "جيد جدًا", "passed": true}], "average": 74.97, "letter": "جيد", "rank": 3, "class_size": 4, "result": "ناجح", "attendance": {"absent": 0, "late": 0, "excused": 0}, "incomplete": false, "issued_at": "2026-11-03"}', 74.97, 3, 'ناجح', @admin),
  (@sid, @b_student_enrollments+12, @b_terms+1, 'term', '{"school": {"name": "مدرسة (تجريبي)", "city": "", "phone": ""}, "year": "السنة التجريبية", "term": "الفصل الأول", "is_final": false, "section": "الصف الثاني (تجريبي) / أ", "student": {"name": "هالة منير الشامي", "no": "D0012"}, "scheme": {"name": "النظام الافتراضي", "max_score": 100, "pass_score": 50}, "subjects": [{"name": "الرياضيات (تجريبي)", "total": 67.1, "percent": 67.1, "letter": "جيد", "passed": true}, {"name": "اللغة العربية (تجريبي)", "total": 81.3, "percent": 81.3, "letter": "جيد جدًا", "passed": true}, {"name": "العلوم (تجريبي)", "total": 75.2, "percent": 75.2, "letter": "جيد", "passed": true}, {"name": "اللغة الإنجليزية (تجريبي)", "total": 68.5, "percent": 68.5, "letter": "جيد", "passed": true}], "average": 73.03, "letter": "جيد", "rank": 4, "class_size": 4, "result": "ناجح", "attendance": {"absent": 0, "late": 0, "excused": 0}, "incomplete": false, "issued_at": "2026-11-03"}', 73.03, 4, 'ناجح', @admin);

INSERT INTO lessons (id, school_id, section_subject_id, term_id, title, body, sort_order, is_published, created_by) VALUES
  (@b_lessons+1, @sid, @b_section_subjects+1, @b_terms+1, 'المعادلات من الدرجة الأولى', 'شرح مبسّط لحل المعادلات مع أمثلة.', 1, 1, @b_users+1),
  (@b_lessons+2, @sid, @b_section_subjects+1, @b_terms+1, 'النسبة والتناسب', 'تعريف النسبة وخصائص التناسب.', 2, 1, @b_users+1),
  (@b_lessons+3, @sid, @b_section_subjects+2, @b_terms+1, 'الجملة الاسمية والفعلية', 'قواعد وأمثلة.', 1, 1, @b_users+2),
  (@b_lessons+4, @sid, @b_section_subjects+3, @b_terms+1, 'دورة الماء في الطبيعة', 'درس علوم مسودة.', 1, 0, @b_users+3);
INSERT INTO materials (school_id, lesson_id, section_subject_id, kind, title, url) VALUES
  (@sid, @b_lessons+1, @b_section_subjects+1, 'video', 'شرح فيديو: حل المعادلات', 'https://www.youtube.com/watch?v=demo1'),
  (@sid, @b_lessons+2, @b_section_subjects+1, 'link', 'تمارين تفاعلية', 'https://example.com/ratio-practice'),
  (@sid, @b_lessons+3, @b_section_subjects+2, 'link', 'ملخص القواعد', 'https://example.com/arabic-grammar');
INSERT INTO assignments (id, school_id, section_subject_id, title, instructions, due_at, max_score, allow_late, created_by) VALUES
  (@b_assignments+1, @sid, @b_section_subjects+1, 'واجب المعادلات', 'حل التمارين 1-10 من الكتاب', TIMESTAMP(CURDATE() + INTERVAL 3 DAY, '23:59:00'), 10, 1, @b_users+1),
  (@b_assignments+2, @sid, @b_section_subjects+2, 'تعبير كتابي', 'اكتب فقرة عن وطنك', TIMESTAMP(CURDATE() + INTERVAL 5 DAY, '23:59:00'), 10, 0, @b_users+2);
INSERT INTO submissions (school_id, assignment_id, enrollment_id, body, submitted_at, score, feedback, graded_by, graded_at, is_late) VALUES
  (@sid, @b_assignments+1, @b_student_enrollments+1, 'حللت التمارين كلها.', TIMESTAMP(CURDATE() - INTERVAL 1 DAY, '18:00:00'), 9, 'عمل ممتاز', @b_users+1, TIMESTAMP(CURDATE() - INTERVAL 0 DAY, '09:00:00'), 0),
  (@sid, @b_assignments+1, @b_student_enrollments+2, 'أجبت عن 8 تمارين.', TIMESTAMP(CURDATE() - INTERVAL 1 DAY, '20:00:00'), NULL, NULL, NULL, NULL, 0),
  (@sid, @b_assignments+2, @b_student_enrollments+3, 'وطني اليمن، بلد الحضارة والتاريخ…', TIMESTAMP(CURDATE() - INTERVAL 0 DAY, '10:00:00'), NULL, NULL, NULL, NULL, 0);
INSERT INTO question_bank (id, school_id, subject_id, kind, body, options, answer, points, created_by) VALUES
  (@b_question_bank+1, @sid, @b_subjects+1, 'mcq', 'ما ناتج 3 × 4؟', '["10", "12", "14"]', '1', 1, @b_users+1),
  (@b_question_bank+2, @sid, @b_subjects+1, 'true_false', 'العدد 7 عدد أولي', NULL, 'true', 1, @b_users+1),
  (@b_question_bank+3, @sid, @b_subjects+1, 'short', 'ما ناتج 15 ÷ 3؟', NULL, '["5", "\\u062e\\u0645\\u0633\\u0629"]', 1, @b_users+1),
  (@b_question_bank+4, @sid, @b_subjects+1, 'essay', 'اشرح طريقة حل معادلة من الدرجة الأولى.', NULL, NULL, 3, @b_users+1);
INSERT INTO quizzes (id, school_id, section_subject_id, title, opens_at, closes_at, duration_min, shuffle_questions, max_attempts, is_published, created_by, description, show_answers) VALUES
  (@b_quizzes+1, @sid, @b_section_subjects+1, 'اختبار قصير: الأعداد', TIMESTAMP(CURDATE() - INTERVAL 2 DAY, '08:00:00'), TIMESTAMP(CURDATE() + INTERVAL 5 DAY, '08:00:00'), 15, 1, 1, 1, @b_users+1, 'اختبار تجريبي من 4 أسئلة', 'after_submit');
INSERT INTO quiz_questions (quiz_id, question_id, sort_order, points) VALUES
  (@b_quizzes+1, @b_question_bank+1, 1, NULL),
  (@b_quizzes+1, @b_question_bank+2, 2, NULL),
  (@b_quizzes+1, @b_question_bank+3, 3, NULL),
  (@b_quizzes+1, @b_question_bank+4, 4, NULL);
INSERT INTO quiz_attempts (id, school_id, quiz_id, enrollment_id, started_at, submitted_at, question_order, score, status, deadline_at, graded_at) VALUES
  (@b_quiz_attempts+1, @sid, @b_quizzes+1, @b_student_enrollments+1, TIMESTAMP(CURDATE() - INTERVAL 1 DAY, '10:00:00'), TIMESTAMP(CURDATE() - INTERVAL 1 DAY, '10:10:00'), '[2, 1, 4, 3]', 3, 'submitted', TIMESTAMP(CURDATE() - INTERVAL 1 DAY, '10:15:00'), NULL),
  (@b_quiz_attempts+2, @sid, @b_quizzes+1, @b_student_enrollments+2, TIMESTAMP(CURDATE() - INTERVAL 1 DAY, '11:00:00'), TIMESTAMP(CURDATE() - INTERVAL 1 DAY, '11:12:00'), '[1, 3, 2, 4]', 4, 'graded', TIMESTAMP(CURDATE() - INTERVAL 1 DAY, '11:15:00'), TIMESTAMP(CURDATE() - INTERVAL 0 DAY, '08:00:00'));
INSERT INTO quiz_answers (attempt_id, question_id, answer, score) VALUES
  (@b_quiz_attempts+1, @b_question_bank+1, '1', 1),
  (@b_quiz_attempts+1, @b_question_bank+2, 'true', 1),
  (@b_quiz_attempts+1, @b_question_bank+3, '"5"', 1),
  (@b_quiz_attempts+1, @b_question_bank+4, '"\\u0623\\u0646\\u0642\\u0644 \\u0627\\u0644\\u062d\\u062f\\u0648\\u062f \\u0648\\u0623\\u0642\\u0633\\u0645."', NULL),
  (@b_quiz_attempts+2, @b_question_bank+1, '1', 1),
  (@b_quiz_attempts+2, @b_question_bank+2, 'true', 1);
INSERT INTO live_sessions (id, school_id, section_subject_id, title, provider, join_url, starts_at, ends_at, created_by, description) VALUES
  (@b_live_sessions+1, @sid, @b_section_subjects+1, 'مراجعة الفصل', 'jitsi', 'https://meet.jit.si/madrasati-demo-review-2026', TIMESTAMP(CURDATE() + INTERVAL 1 DAY, '17:00:00'), TIMESTAMP(CURDATE() + INTERVAL 1 DAY, '18:00:00'), @b_users+1, 'حصة مراجعة مباشرة'),
  (@b_live_sessions+2, @sid, @b_section_subjects+2, 'درس النحو', 'meet', 'https://meet.google.com/demo-nahw-xyz', TIMESTAMP(CURDATE() - INTERVAL 2 DAY, '16:00:00'), TIMESTAMP(CURDATE() - INTERVAL 2 DAY, '17:00:00'), @b_users+2, NULL);
INSERT INTO live_attendance (live_session_id, enrollment_id, joined_at) VALUES
  (@b_live_sessions+2, @b_student_enrollments+1, TIMESTAMP(CURDATE() - INTERVAL 2 DAY, '16:02:00')),
  (@b_live_sessions+2, @b_student_enrollments+2, TIMESTAMP(CURDATE() - INTERVAL 2 DAY, '16:05:00'));

INSERT INTO announcements (id, school_id, title, body, audience, section_id, published_at, created_by) VALUES
  (@b_announcements+1, @sid, 'بداية الفصل الدراسي', 'نرحب بجميع الطلاب وأولياء الأمور ونتمنى عامًا دراسيًا موفقًا.', 'all', NULL, TIMESTAMP(CURDATE() - INTERVAL 30 DAY, '08:00:00'), @admin),
  (@b_announcements+2, @sid, 'اجتماع أولياء الأمور', 'يُعقد اجتماع أولياء الأمور يوم الخميس القادم الساعة 10 صباحًا.', 'guardians', NULL, TIMESTAMP(CURDATE() - INTERVAL 2 DAY, '08:00:00'), @admin),
  (@b_announcements+3, @sid, 'رحلة علمية', 'رحلة علمية لطلاب الصف الأول/أ إلى المتحف الوطني.', 'section', @b_sections+1, TIMESTAMP(CURDATE() - INTERVAL 1 DAY, '08:00:00'), @b_users+1);
INSERT INTO messages (id, school_id, sender_user_id, parent_id, subject, body, thread_id) VALUES
  (@b_messages+1, @sid, @b_users+1, NULL, 'نتيجة الاختبار', 'أود إبلاغكم بتحسن مستوى يوسف في الرياضيات.', @b_messages+1),
  (@b_messages+2, @sid, @b_users+7, @b_messages+1, 'رد: نتيجة الاختبار', 'شكرًا أستاذ خالد على اهتمامكم.', @b_messages+1),
  (@b_messages+3, @sid, @admin, NULL, 'تنبيه إداري', 'يرجى تسليم كشوف الدرجات قبل نهاية الأسبوع.', @b_messages+3);
INSERT INTO message_recipients (message_id, user_id, read_at) VALUES
  (@b_messages+1, @b_users+7, TIMESTAMP(CURDATE() - INTERVAL 0 DAY, '08:00:00')),
  (@b_messages+2, @b_users+1, NULL),
  (@b_messages+3, @b_users+1, NULL),
  (@b_messages+3, @b_users+2, NULL);
INSERT INTO notifications (school_id, user_id, title, body, url, read_at) VALUES
  (@sid, @b_users+17, 'واجب جديد', 'واجب المعادلات: موعد التسليم بعد 3 أيام', '/learn', NULL),
  (@sid, @b_users+7, 'رسالة جديدة', 'لديك رسالة من أ. خالد', '/messages', NULL),
  (@sid, @b_users+1, 'إعلان جديد', 'اجتماع أولياء الأمور', '/announcements', TIMESTAMP(CURDATE() - INTERVAL 0 DAY, '08:00:00'));
INSERT INTO notification_channels (user_id, channel, enabled) VALUES
  (@b_users+1, 'inapp', 1),
  (@b_users+1, 'sms', 0),
  (@b_users+7, 'inapp', 1),
  (@b_users+7, 'sms', 1);
INSERT INTO notification_queue (school_id, channel, recipient, body, status, attempts, last_error) VALUES
  (@sid, 'sms', '+9677730010000', 'تذكير: اجتماع أولياء الأمور يوم الخميس', 'pending', 0, NULL),
  (@sid, 'sms', '+9677730010020', 'رسالة تجريبية', 'failed', 5, 'لا يوجد مزود مضبوط');

INSERT INTO exchange_rates (school_id, currency, rate_to_base, created_by) VALUES
  (@sid, 'USD', 250, @admin),
  (@sid, 'SAR', 66.5, @admin);
UPDATE tax_settings SET tax_enabled = 0, tax_label = 'ضريبة (غير مفعّلة)' WHERE school_id = @sid;
INSERT INTO payment_methods (id, school_id, kind, name, account_info) VALUES
  (@b_payment_methods+1, @sid, 'bank', 'تحويل بنكي (تجريبي)', 'حساب رقم 123456'),
  (@b_payment_methods+2, @sid, 'wallet', 'محفظة إلكترونية (تجريبي)', '0770000000');
INSERT INTO discounts (id, school_id, name, kind, value, reason) VALUES
  (@b_discounts+1, @sid, 'خصم الأخوة 10%', 'percent', 10, 'sibling'),
  (@b_discounts+2, @sid, 'منحة تفوق', 'fixed', 50000, 'scholarship');
INSERT INTO fee_structures (id, school_id, academic_year_id, grade_level_id, name, currency, installments_count, first_due_date, interval_months) VALUES
  (@b_fee_structures+1, @sid, @b_academic_years+1, @b_grade_levels+1, 'رسوم الصف الأول', 'YER', 3, (CURDATE() - INTERVAL 60 DAY), 1),
  (@b_fee_structures+2, @sid, @b_academic_years+1, @b_grade_levels+2, 'رسوم الصف الثاني', 'YER', 3, (CURDATE() - INTERVAL 60 DAY), 1);
INSERT INTO fee_items (school_id, structure_id, name, amount, installments_count) VALUES
  (@sid, @b_fee_structures+1, 'الرسوم الدراسية', 250000, 3),
  (@sid, @b_fee_structures+1, 'الكتب والأدوات', 50000, 3),
  (@sid, @b_fee_structures+2, 'الرسوم الدراسية', 300000, 3),
  (@sid, @b_fee_structures+2, 'الكتب والأدوات', 60000, 3);
INSERT INTO student_fees (id, school_id, enrollment_id, structure_id, currency, gross, discount_total, net, discount_id, tax_percent, tax_amount, payable, status, discount_reason) VALUES
  (@b_student_fees+1, @sid, @b_student_enrollments+1, @b_fee_structures+1, 'YER', 300000, 0, 300000, NULL, NULL, 0, 300000, 'active', NULL),
  (@b_student_fees+2, @sid, @b_student_enrollments+2, @b_fee_structures+1, 'YER', 300000, 30000, 270000, @b_discounts+1, NULL, 0, 270000, 'active', 'خصم الأخوة'),
  (@b_student_fees+3, @sid, @b_student_enrollments+3, @b_fee_structures+1, 'YER', 300000, 0, 300000, NULL, NULL, 0, 300000, 'active', NULL),
  (@b_student_fees+4, @sid, @b_student_enrollments+4, @b_fee_structures+1, 'YER', 300000, 0, 300000, NULL, NULL, 0, 300000, 'active', NULL),
  (@b_student_fees+5, @sid, @b_student_enrollments+5, @b_fee_structures+1, 'YER', 300000, 0, 300000, NULL, NULL, 0, 300000, 'active', NULL),
  (@b_student_fees+6, @sid, @b_student_enrollments+6, @b_fee_structures+1, 'YER', 300000, 0, 300000, NULL, NULL, 0, 300000, 'active', NULL),
  (@b_student_fees+7, @sid, @b_student_enrollments+7, @b_fee_structures+1, 'YER', 300000, 0, 300000, NULL, NULL, 0, 300000, 'active', NULL),
  (@b_student_fees+8, @sid, @b_student_enrollments+8, @b_fee_structures+1, 'YER', 300000, 0, 300000, NULL, NULL, 0, 300000, 'active', NULL),
  (@b_student_fees+9, @sid, @b_student_enrollments+9, @b_fee_structures+2, 'YER', 360000, 0, 360000, NULL, NULL, 0, 360000, 'active', NULL),
  (@b_student_fees+10, @sid, @b_student_enrollments+10, @b_fee_structures+2, 'YER', 360000, 0, 360000, NULL, NULL, 0, 360000, 'active', NULL),
  (@b_student_fees+11, @sid, @b_student_enrollments+11, @b_fee_structures+2, 'YER', 360000, 0, 360000, NULL, NULL, 0, 360000, 'active', NULL),
  (@b_student_fees+12, @sid, @b_student_enrollments+12, @b_fee_structures+2, 'YER', 360000, 0, 360000, NULL, NULL, 0, 360000, 'active', NULL);
INSERT INTO installments (id, school_id, student_fee_id, seq, due_date, amount, paid_amount, status) VALUES
  (@b_installments+1, @sid, @b_student_fees+1, 1, (CURDATE() - INTERVAL 60 DAY), 100000, 100000, 'paid'),
  (@b_installments+2, @sid, @b_student_fees+1, 2, (CURDATE() - INTERVAL 30 DAY), 100000, 0, 'pending'),
  (@b_installments+3, @sid, @b_student_fees+1, 3, (CURDATE() - INTERVAL 0 DAY), 100000, 0, 'pending'),
  (@b_installments+4, @sid, @b_student_fees+2, 1, (CURDATE() - INTERVAL 60 DAY), 90000, 90000, 'paid'),
  (@b_installments+5, @sid, @b_student_fees+2, 2, (CURDATE() - INTERVAL 30 DAY), 90000, 40000, 'partial'),
  (@b_installments+6, @sid, @b_student_fees+2, 3, (CURDATE() - INTERVAL 0 DAY), 90000, 0, 'pending'),
  (@b_installments+7, @sid, @b_student_fees+3, 1, (CURDATE() - INTERVAL 60 DAY), 100000, 100000, 'paid'),
  (@b_installments+8, @sid, @b_student_fees+3, 2, (CURDATE() - INTERVAL 30 DAY), 100000, 0, 'pending'),
  (@b_installments+9, @sid, @b_student_fees+3, 3, (CURDATE() - INTERVAL 0 DAY), 100000, 0, 'pending'),
  (@b_installments+10, @sid, @b_student_fees+4, 1, (CURDATE() - INTERVAL 60 DAY), 100000, 0, 'pending'),
  (@b_installments+11, @sid, @b_student_fees+4, 2, (CURDATE() - INTERVAL 30 DAY), 100000, 0, 'pending'),
  (@b_installments+12, @sid, @b_student_fees+4, 3, (CURDATE() - INTERVAL 0 DAY), 100000, 0, 'pending'),
  (@b_installments+13, @sid, @b_student_fees+5, 1, (CURDATE() - INTERVAL 60 DAY), 100000, 100000, 'paid'),
  (@b_installments+14, @sid, @b_student_fees+5, 2, (CURDATE() - INTERVAL 30 DAY), 100000, 100000, 'paid'),
  (@b_installments+15, @sid, @b_student_fees+5, 3, (CURDATE() - INTERVAL 0 DAY), 100000, 0, 'pending'),
  (@b_installments+16, @sid, @b_student_fees+6, 1, (CURDATE() - INTERVAL 60 DAY), 100000, 0, 'pending'),
  (@b_installments+17, @sid, @b_student_fees+6, 2, (CURDATE() - INTERVAL 30 DAY), 100000, 0, 'pending'),
  (@b_installments+18, @sid, @b_student_fees+6, 3, (CURDATE() - INTERVAL 0 DAY), 100000, 0, 'pending'),
  (@b_installments+19, @sid, @b_student_fees+7, 1, (CURDATE() - INTERVAL 60 DAY), 100000, 0, 'pending'),
  (@b_installments+20, @sid, @b_student_fees+7, 2, (CURDATE() - INTERVAL 30 DAY), 100000, 0, 'pending'),
  (@b_installments+21, @sid, @b_student_fees+7, 3, (CURDATE() - INTERVAL 0 DAY), 100000, 0, 'pending'),
  (@b_installments+22, @sid, @b_student_fees+8, 1, (CURDATE() - INTERVAL 60 DAY), 100000, 0, 'pending'),
  (@b_installments+23, @sid, @b_student_fees+8, 2, (CURDATE() - INTERVAL 30 DAY), 100000, 0, 'pending'),
  (@b_installments+24, @sid, @b_student_fees+8, 3, (CURDATE() - INTERVAL 0 DAY), 100000, 0, 'pending'),
  (@b_installments+25, @sid, @b_student_fees+9, 1, (CURDATE() - INTERVAL 60 DAY), 120000, 30000, 'partial'),
  (@b_installments+26, @sid, @b_student_fees+9, 2, (CURDATE() - INTERVAL 30 DAY), 120000, 0, 'pending'),
  (@b_installments+27, @sid, @b_student_fees+9, 3, (CURDATE() - INTERVAL 0 DAY), 120000, 0, 'pending'),
  (@b_installments+28, @sid, @b_student_fees+10, 1, (CURDATE() - INTERVAL 60 DAY), 120000, 0, 'pending'),
  (@b_installments+29, @sid, @b_student_fees+10, 2, (CURDATE() - INTERVAL 30 DAY), 120000, 0, 'pending'),
  (@b_installments+30, @sid, @b_student_fees+10, 3, (CURDATE() - INTERVAL 0 DAY), 120000, 0, 'pending'),
  (@b_installments+31, @sid, @b_student_fees+11, 1, (CURDATE() - INTERVAL 60 DAY), 120000, 0, 'pending'),
  (@b_installments+32, @sid, @b_student_fees+11, 2, (CURDATE() - INTERVAL 30 DAY), 120000, 0, 'pending'),
  (@b_installments+33, @sid, @b_student_fees+11, 3, (CURDATE() - INTERVAL 0 DAY), 120000, 0, 'pending'),
  (@b_installments+34, @sid, @b_student_fees+12, 1, (CURDATE() - INTERVAL 60 DAY), 120000, 0, 'pending'),
  (@b_installments+35, @sid, @b_student_fees+12, 2, (CURDATE() - INTERVAL 30 DAY), 120000, 0, 'pending'),
  (@b_installments+36, @sid, @b_student_fees+12, 3, (CURDATE() - INTERVAL 0 DAY), 120000, 0, 'pending');
INSERT INTO payments (id, school_id, invoice_id, installment_id, student_fee_id, method_id, currency, amount, rate_to_base, reference_no, paid_on, received_by, fee_currency, fee_rate_to_base, applied_amount) VALUES
  (@b_payments+1, @sid, NULL, @b_installments+1, @b_student_fees+1, NULL, 'YER', 100000, 1, NULL, (CURDATE() - INTERVAL 9 DAY), @admin, 'YER', 1, 100000),
  (@b_payments+2, @sid, NULL, @b_installments+4, @b_student_fees+2, NULL, 'YER', 90000, 1, NULL, (CURDATE() - INTERVAL 8 DAY), @admin, 'YER', 1, 90000),
  (@b_payments+3, @sid, NULL, @b_installments+5, @b_student_fees+2, NULL, 'YER', 40000, 1, NULL, (CURDATE() - INTERVAL 7 DAY), @admin, 'YER', 1, 40000),
  (@b_payments+4, @sid, NULL, @b_installments+7, @b_student_fees+3, @b_payment_methods+1, 'YER', 100000, 1, 'TRF-0004', (CURDATE() - INTERVAL 6 DAY), @admin, 'YER', 1, 100000),
  (@b_payments+5, @sid, NULL, @b_installments+13, @b_student_fees+5, NULL, 'YER', 100000, 1, NULL, (CURDATE() - INTERVAL 5 DAY), @admin, 'YER', 1, 100000),
  (@b_payments+6, @sid, NULL, @b_installments+14, @b_student_fees+5, NULL, 'YER', 100000, 1, NULL, (CURDATE() - INTERVAL 4 DAY), @admin, 'YER', 1, 100000),
  (@b_payments+7, @sid, NULL, @b_installments+25, @b_student_fees+9, NULL, 'USD', 120, 250, NULL, (CURDATE() - INTERVAL 3 DAY), @admin, 'YER', 1, 30000);
INSERT INTO payment_allocations (payment_id, installment_id, amount) VALUES
  (@b_payments+1, @b_installments+1, 100000),
  (@b_payments+2, @b_installments+4, 90000),
  (@b_payments+3, @b_installments+5, 40000),
  (@b_payments+4, @b_installments+7, 100000),
  (@b_payments+5, @b_installments+13, 100000),
  (@b_payments+6, @b_installments+14, 100000),
  (@b_payments+7, @b_installments+25, 30000);
INSERT INTO receipts (school_id, payment_id, number) VALUES
  (@sid, @b_payments+1, 'RC-2026-000001'),
  (@sid, @b_payments+2, 'RC-2026-000002'),
  (@sid, @b_payments+3, 'RC-2026-000003'),
  (@sid, @b_payments+4, 'RC-2026-000004'),
  (@sid, @b_payments+5, 'RC-2026-000005'),
  (@sid, @b_payments+6, 'RC-2026-000006'),
  (@sid, @b_payments+7, 'RC-2026-000007');
INSERT INTO counters (school_id, name, year, value) VALUES (@sid,'receipt',@cur_year,7) ON DUPLICATE KEY UPDATE value = 7;
INSERT INTO invoices (id, school_id, number, guardian_id, student_fee_id, currency, rate_to_base, subtotal, tax_percent, tax_amount, total, status, issued_on, created_by) VALUES
  (@b_invoices+1, @sid, 'INV-2026-000001', @b_guardians+1, @b_student_fees+1, 'YER', 1, 300000, NULL, 0, 300000, 'partial', (CURDATE() - INTERVAL 40 DAY), @admin),
  (@b_invoices+2, @sid, 'INV-2026-000002', @b_guardians+1, @b_student_fees+2, 'YER', 1, 270000, NULL, 0, 270000, 'partial', (CURDATE() - INTERVAL 40 DAY), @admin),
  (@b_invoices+3, @sid, 'INV-2026-000003', @b_guardians+2, @b_student_fees+3, 'YER', 1, 300000, NULL, 0, 300000, 'partial', (CURDATE() - INTERVAL 40 DAY), @admin);
INSERT INTO invoice_lines (invoice_id, description, amount) VALUES
  (@b_invoices+1, 'الرسوم الدراسية', 250000),
  (@b_invoices+1, 'الكتب والأدوات', 50000),
  (@b_invoices+2, 'الرسوم الدراسية بعد الخصم', 270000),
  (@b_invoices+3, 'الرسوم الدراسية والكتب', 300000);
INSERT INTO counters (school_id, name, year, value) VALUES (@sid,'invoice',@cur_year,3) ON DUPLICATE KEY UPDATE value = 3;

INSERT INTO contracts (id, school_id, staff_id, kind, starts_on, ends_on, base_salary, currency, status) VALUES
  (@b_contracts+1, @sid, @b_staff+1, 'دائم', (CURDATE() - INTERVAL 730 DAY), NULL, 150000, 'YER', 'active'),
  (@b_contracts+2, @sid, @b_staff+2, 'دائم', (CURDATE() - INTERVAL 760 DAY), NULL, 140000, 'YER', 'active'),
  (@b_contracts+3, @sid, @b_staff+3, 'دائم', (CURDATE() - INTERVAL 790 DAY), NULL, 140000, 'YER', 'active'),
  (@b_contracts+4, @sid, @b_staff+4, 'دائم', (CURDATE() - INTERVAL 820 DAY), NULL, 130000, 'YER', 'active'),
  (@b_contracts+5, @sid, @b_staff+5, 'دائم', (CURDATE() - INTERVAL 850 DAY), NULL, 180000, 'YER', 'active'),
  (@b_contracts+6, @sid, @b_staff+6, 'دائم', (CURDATE() - INTERVAL 880 DAY), NULL, 170000, 'YER', 'active');
INSERT INTO payroll_runs (id, school_id, period, status, created_by, approved_by, approved_at, paid_on) VALUES
  (@b_payroll_runs+1, @sid, '2026-10', 'paid', @admin, @admin, TIMESTAMP(CURDATE() - INTERVAL 14 DAY, '08:00:00'), (CURDATE() - INTERVAL 10 DAY));
INSERT INTO payroll_items (run_id, staff_id, base, allowances, deductions, net, currency, contract_id, days_worked, days_in_month) VALUES
  (@b_payroll_runs+1, @b_staff+1, 150000, 5000, 0, 155000, 'YER', @b_contracts+1, 31, 31),
  (@b_payroll_runs+1, @b_staff+2, 140000, 5000, 0, 145000, 'YER', @b_contracts+2, 31, 31),
  (@b_payroll_runs+1, @b_staff+3, 140000, 5000, 0, 145000, 'YER', @b_contracts+3, 31, 31),
  (@b_payroll_runs+1, @b_staff+4, 130000, 5000, 0, 135000, 'YER', @b_contracts+4, 31, 31),
  (@b_payroll_runs+1, @b_staff+5, 180000, 0, 0, 180000, 'YER', @b_contracts+5, 31, 31),
  (@b_payroll_runs+1, @b_staff+6, 170000, 0, 0, 170000, 'YER', @b_contracts+6, 31, 31);

INSERT INTO admission_applications (id, school_id, applicant_name, birth_date, gender, grade_level_id, guardian_name, guardian_phone, status, notes, previous_school, address, guardian_relation, academic_year_id, decision_note, decided_by, decided_at) VALUES
  (@b_admission_applications+1, @sid, 'إبراهيم ماجد الزبيري', '2018-05-10', 'male', @b_grade_levels+1, 'ماجد الزبيري', '+967771111001', 'new', 'طلب جديد', NULL, 'صنعاء', 'father', @b_academic_years+1, NULL, NULL, NULL),
  (@b_admission_applications+2, @sid, 'سارة وليد الأكوع', '2017-09-02', 'female', @b_grade_levels+2, 'وليد الأكوع', '+967771111002', 'review', 'وثائقها قيد المراجعة', 'مدرسة النور', 'صنعاء', 'father', @b_academic_years+1, NULL, NULL, NULL),
  (@b_admission_applications+3, @sid, 'كريم صادق النهمي', '2018-01-20', 'male', @b_grade_levels+1, 'صادق النهمي', '+967771111003', 'accepted', NULL, NULL, 'عدن', 'father', @b_academic_years+1, 'مستوفٍ للشروط', @admin, TIMESTAMP(CURDATE() - INTERVAL 1 DAY, '08:00:00')),
  (@b_admission_applications+4, @sid, 'وفاء نبيل الجبري', '2017-03-14', 'female', @b_grade_levels+2, 'نبيل الجبري', '+967771111004', 'rejected', NULL, NULL, 'تعز', 'father', @b_academic_years+1, 'لا توجد مقاعد شاغرة', @admin, TIMESTAMP(CURDATE() - INTERVAL 2 DAY, '08:00:00'));
INSERT IGNORE INTO risk_indicators (school_id, code, label, threshold, is_active) VALUES
  (@sid, 'absence_rate', 'نسبة الغياب', 20, 1),
  (@sid, 'consecutive_absence', 'أيام الغياب المتتالية', 3, 1),
  (@sid, 'low_average', 'انخفاض المعدل', 50, 1),
  (@sid, 'grade_drop', 'هبوط المعدل بين الفصول', 15, 1),
  (@sid, 'discipline', 'عدد المخالفات', 3, 1),
  (@sid, 'fee_arrears', 'تأخر الرسوم (أيام)', 60, 1);
INSERT INTO risk_flags (school_id, student_id, indicator_code, explanation, status, reviewed_by, note, reviewed_at, metric) VALUES
  (@sid, @b_students+7, 'absence_rate', 'غاب 6 من 10 أيام مسجّلة (60%) خلال آخر 30 يومًا — الحد 20%', 'open', NULL, NULL, NULL, 60),
  (@sid, @b_students+7, 'low_average', 'معدل «الفصل الأول» أقل من الحد 50%', 'reviewed', @b_users+6, 'تم التواصل مع ولي الأمر', TIMESTAMP(CURDATE() - INTERVAL 1 DAY, '08:00:00'), 38.5);
INSERT INTO counseling_cases (id, school_id, student_id, counselor_user_id, title, notes_enc, status, kind, risk_flag_id, updated_at) VALUES
  (@b_counseling_cases+1, @sid, @b_students+7, @b_users+6, 'متابعة الغياب (تجريبي)', NULL, 'open', 'social', NULL, TIMESTAMP(CURDATE() - INTERVAL 0 DAY, '08:00:00')),
  (@b_counseling_cases+2, @sid, @b_students+4, @b_users+6, 'توجيه دراسي (تجريبي)', NULL, 'closed', 'academic', NULL, TIMESTAMP(CURDATE() - INTERVAL 5 DAY, '08:00:00'));
INSERT INTO counseling_notes (school_id, case_id, user_id, note_enc) VALUES
  (@sid, @b_counseling_cases+1, @b_users+6, '');

INSERT INTO facilities (id, school_id, name, kind, capacity, status) VALUES
  (@b_facilities+1, @sid, 'مختبر الحاسوب (تجريبي)', 'lab', 25, 'available'),
  (@b_facilities+2, @sid, 'المكتبة (تجريبي)', 'library', 40, 'available'),
  (@b_facilities+3, @sid, 'الملعب (تجريبي)', 'sports', 100, 'maintenance');
INSERT INTO facility_bookings (school_id, facility_id, booked_by, starts_at, ends_at, purpose) VALUES
  (@sid, @b_facilities+1, @b_users+1, TIMESTAMP(CURDATE() + INTERVAL 1 DAY, '09:00:00'), TIMESTAMP(CURDATE() + INTERVAL 1 DAY, '10:00:00'), 'حصة حاسوب'),
  (@sid, @b_facilities+2, @b_users+2, TIMESTAMP(CURDATE() + INTERVAL 2 DAY, '11:00:00'), TIMESTAMP(CURDATE() + INTERVAL 2 DAY, '12:00:00'), 'نشاط قراءة'),
  (@sid, @b_facilities+1, @b_users+3, TIMESTAMP(CURDATE() + INTERVAL 3 DAY, '09:00:00'), TIMESTAMP(CURDATE() + INTERVAL 3 DAY, '11:00:00'), 'اختبار عملي');
INSERT INTO credentials (school_id, student_id, title, verify_code, issued_on, issued_by, kind, notes) VALUES
  (@sid, @b_students+1, 'شهادة تفوق دراسي', 'DEMO-AAAA-2222', (CURDATE() - INTERVAL 3 DAY), @admin, 'excellence', NULL),
  (@sid, @b_students+2, 'شهادة مشاركة', 'DEMO-BBBB-3333', (CURDATE() - INTERVAL 8 DAY), @admin, 'participation', NULL);

INSERT INTO import_jobs (id, school_id, user_id, kind, original_name, stored_name, status, total_rows, ok_rows, error_rows) VALUES
  (@b_import_jobs+1, @sid, @admin, 'students', 'طلاب-تجريبي.xlsx', 'demo-import-none', 'committed', 3, 2, 1);
INSERT INTO import_rows (job_id, row_no, data, status, errors) VALUES
  (@b_import_jobs+1, 2, '{"full_name": "طالب مستورد"}', 'imported', NULL),
  (@b_import_jobs+1, 3, '{"full_name": ""}', 'error', '["الاسم مطلوب"]');
INSERT INTO audit_logs (school_id, user_id, action, entity, entity_id, new_values, ip) VALUES
  (@sid, @admin, 'demo.seed', 'school', @sid, '{"note": "تحميل بيانات تجريبية"}', '127.0.0.1');
INSERT IGNORE INTO settings (school_id, skey, svalue) VALUES
  (@sid, 'fees.invoice_prefix', 'INV'),
  (@sid, 'fees.receipt_prefix', 'RC');

INSERT IGNORE INTO subject_grade_levels (subject_id, grade_level_id, school_id)
  SELECT DISTINCT ss.subject_id, sc.grade_level_id, ss.school_id FROM section_subjects ss JOIN sections sc ON sc.id = ss.section_id WHERE ss.school_id = @sid;
INSERT IGNORE INTO subject_grade_levels (subject_id, grade_level_id, school_id)
  SELECT s.id, g.id, s.school_id FROM subjects s JOIN grade_levels g ON g.school_id = s.school_id
  WHERE s.school_id = @sid AND NOT EXISTS (SELECT 1 FROM subject_grade_levels x WHERE x.subject_id = s.id);

UPDATE users SET phone = CONCAT('+967', SUBSTRING(phone, 2)) WHERE school_id = @sid AND phone REGEXP '^0[0-9]{9}$';
UPDATE staff SET phone = CONCAT('+967', SUBSTRING(phone, 2)) WHERE school_id = @sid AND phone REGEXP '^0[0-9]{9}$';
UPDATE students SET phone = CONCAT('+967', SUBSTRING(phone, 2)) WHERE school_id = @sid AND phone REGEXP '^0[0-9]{9}$';
UPDATE guardians SET phone = CONCAT('+967', SUBSTRING(phone, 2)) WHERE phone REGEXP '^0[0-9]{9}$' AND id IN (SELECT guardian_id FROM student_guardians WHERE school_id = @sid);
UPDATE guardians SET whatsapp = CONCAT('+967', SUBSTRING(whatsapp, 2)) WHERE whatsapp REGEXP '^0[0-9]{9}$' AND id IN (SELECT guardian_id FROM student_guardians WHERE school_id = @sid);

SET FOREIGN_KEY_CHECKS = 1; SET SQL_MODE = @OLD_SQL_MODE;
SELECT 'تم تحميل البيانات التجريبية. كلمة مرور الحسابات: Demo12345' AS result;
