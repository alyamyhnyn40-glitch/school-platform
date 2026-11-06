-- أنواع استيراد جديدة: الصفوف والمواد والشعب والدرجات
ALTER TABLE import_jobs MODIFY kind ENUM('students','guardians','staff','grades','subjects','sections','scores') NOT NULL;
