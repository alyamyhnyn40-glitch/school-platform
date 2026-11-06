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
