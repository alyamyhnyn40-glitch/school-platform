/*M!999999\- enable the sandbox mode */ 

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `academic_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `academic_records` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `enrollment_id` bigint(20) unsigned NOT NULL,
  `academic_year_id` int(10) unsigned NOT NULL,
  `term_id` int(10) unsigned DEFAULT NULL COMMENT 'NULL = النتيجة السنوية',
  `subject_id` int(10) unsigned DEFAULT NULL COMMENT 'NULL = سطر الملخص',
  `year_name` varchar(50) NOT NULL,
  `term_name` varchar(100) DEFAULT NULL,
  `grade_name` varchar(100) DEFAULT NULL,
  `section_name` varchar(100) DEFAULT NULL,
  `subject_name` varchar(150) DEFAULT NULL,
  `percent` decimal(6,2) DEFAULT NULL,
  `total` decimal(7,2) DEFAULT NULL,
  `max_score` decimal(6,2) DEFAULT NULL,
  `letter` varchar(50) DEFAULT NULL,
  `gpa_points` decimal(4,2) DEFAULT NULL,
  `passed` tinyint(1) DEFAULT NULL,
  `credit_hours` decimal(4,1) DEFAULT NULL,
  `rank_in_section` int(10) unsigned DEFAULT NULL,
  `result` varchar(100) DEFAULT NULL,
  `frozen_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`,`academic_year_id`),
  KEY `school_id` (`school_id`,`academic_year_id`),
  KEY `enrollment_id` (`enrollment_id`),
  CONSTRAINT `academic_records_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `academic_records_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  CONSTRAINT `academic_records_ibfk_3` FOREIGN KEY (`enrollment_id`) REFERENCES `student_enrollments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `academic_records` WRITE;
/*!40000 ALTER TABLE `academic_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `academic_records` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `academic_years`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `academic_years` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `name` varchar(60) NOT NULL,
  `starts_on` date NOT NULL,
  `ends_on` date NOT NULL,
  `status` enum('draft','active','closed') NOT NULL DEFAULT 'draft',
  `closed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_year` (`school_id`,`name`),
  CONSTRAINT `academic_years_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `academic_years` WRITE;
/*!40000 ALTER TABLE `academic_years` DISABLE KEYS */;
INSERT INTO `academic_years` VALUES
(1,1,'السنة التجريبية','2026-07-28','2027-05-24','active',NULL,'2026-11-05 00:14:19');
/*!40000 ALTER TABLE `academic_years` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `admission_applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `admission_applications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `applicant_name` varchar(200) NOT NULL,
  `birth_date` date DEFAULT NULL,
  `gender` enum('male','female') DEFAULT NULL,
  `grade_level_id` int(10) unsigned DEFAULT NULL,
  `guardian_name` varchar(200) DEFAULT NULL,
  `guardian_phone` varchar(30) DEFAULT NULL,
  `status` enum('new','review','accepted','rejected','enrolled') NOT NULL DEFAULT 'new',
  `notes` text DEFAULT NULL,
  `student_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `national_id_enc` text DEFAULT NULL,
  `previous_school` varchar(200) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `guardian_relation` varchar(20) DEFAULT NULL,
  `academic_year_id` int(10) unsigned DEFAULT NULL,
  `decision_note` varchar(255) DEFAULT NULL,
  `decided_by` bigint(20) unsigned DEFAULT NULL,
  `decided_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_adm` (`school_id`,`status`),
  CONSTRAINT `admission_applications_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `admission_applications` WRITE;
/*!40000 ALTER TABLE `admission_applications` DISABLE KEYS */;
INSERT INTO `admission_applications` VALUES
(1,1,'إبراهيم ماجد الزبيري','2018-05-10','male',1,'ماجد الزبيري','+967771111001','new','طلب جديد',NULL,'2026-11-05 00:14:19',NULL,NULL,'صنعاء','father',1,NULL,NULL,NULL),
(2,1,'سارة وليد الأكوع','2017-09-02','female',2,'وليد الأكوع','+967771111002','review','وثائقها قيد المراجعة',NULL,'2026-11-05 00:14:19',NULL,'مدرسة النور','صنعاء','father',1,NULL,NULL,NULL),
(3,1,'كريم صادق النهمي','2018-01-20','male',1,'صادق النهمي','+967771111003','accepted',NULL,NULL,'2026-11-05 00:14:19',NULL,NULL,'عدن','father',1,'مستوفٍ للشروط',2,'2026-11-04 08:00:00'),
(4,1,'وفاء نبيل الجبري','2017-03-14','female',2,'نبيل الجبري','+967771111004','rejected',NULL,NULL,'2026-11-05 00:14:19',NULL,NULL,'تعز','father',1,'لا توجد مقاعد شاغرة',2,'2026-11-03 08:00:00');
/*!40000 ALTER TABLE `admission_applications` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `admission_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `admission_documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `application_id` bigint(20) unsigned NOT NULL,
  `title` varchar(150) NOT NULL,
  `file_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `application_id` (`application_id`),
  CONSTRAINT `admission_documents_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `admission_applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `admission_documents` WRITE;
/*!40000 ALTER TABLE `admission_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `admission_documents` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `announcements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `title` varchar(200) NOT NULL,
  `body` text NOT NULL,
  `audience` enum('all','staff','students','guardians','section') NOT NULL DEFAULT 'all',
  `section_id` int(10) unsigned DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`),
  CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `announcements` WRITE;
/*!40000 ALTER TABLE `announcements` DISABLE KEYS */;
INSERT INTO `announcements` VALUES
(1,1,'بداية الفصل الدراسي','نرحب بجميع الطلاب وأولياء الأمور ونتمنى عامًا دراسيًا موفقًا.','all',NULL,'2026-10-06 08:00:00',2,NULL),
(2,1,'اجتماع أولياء الأمور','يُعقد اجتماع أولياء الأمور يوم الخميس القادم الساعة 10 صباحًا.','guardians',NULL,'2026-11-03 08:00:00',2,NULL),
(3,1,'رحلة علمية','رحلة علمية لطلاب الصف الأول/أ إلى المتحف الوطني.','section',1,'2026-11-04 08:00:00',3,NULL);
/*!40000 ALTER TABLE `announcements` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `assessment_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `assessment_types` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `scheme_id` int(10) unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `weight` decimal(6,2) NOT NULL,
  `max_score` decimal(6,2) NOT NULL,
  `sort_order` smallint(6) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`),
  KEY `scheme_id` (`scheme_id`),
  CONSTRAINT `assessment_types_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `assessment_types_ibfk_2` FOREIGN KEY (`scheme_id`) REFERENCES `grading_schemes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `assessment_types` WRITE;
/*!40000 ALTER TABLE `assessment_types` DISABLE KEYS */;
INSERT INTO `assessment_types` VALUES
(1,1,1,'أعمال الفصل',30.00,30.00,1),
(2,1,1,'اختبار نهاية الفصل',70.00,70.00,2);
/*!40000 ALTER TABLE `assessment_types` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `assessments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `assessments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `term_id` int(10) unsigned NOT NULL,
  `section_subject_id` int(10) unsigned NOT NULL,
  `type_id` int(10) unsigned NOT NULL,
  `title` varchar(150) NOT NULL,
  `max_score` decimal(6,2) NOT NULL,
  `held_on` date DEFAULT NULL,
  `status` enum('open','approved','locked') NOT NULL DEFAULT 'open',
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `locked_at` datetime DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`,`term_id`,`section_subject_id`),
  KEY `term_id` (`term_id`),
  KEY `section_subject_id` (`section_subject_id`),
  KEY `type_id` (`type_id`),
  CONSTRAINT `assessments_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `assessments_ibfk_2` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`),
  CONSTRAINT `assessments_ibfk_3` FOREIGN KEY (`section_subject_id`) REFERENCES `section_subjects` (`id`),
  CONSTRAINT `assessments_ibfk_4` FOREIGN KEY (`type_id`) REFERENCES `assessment_types` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `assessments` WRITE;
/*!40000 ALTER TABLE `assessments` DISABLE KEYS */;
INSERT INTO `assessments` VALUES
(1,1,1,1,1,'أعمال الفصل',30.00,'2026-10-16','approved',2,NULL,3,'2026-11-05 00:14:19'),
(2,1,1,1,2,'اختبار نهاية الفصل',70.00,'2026-10-26','approved',2,NULL,3,'2026-11-05 00:14:19'),
(3,1,1,2,1,'أعمال الفصل',30.00,'2026-10-16','approved',2,NULL,4,'2026-11-05 00:14:19'),
(4,1,1,2,2,'اختبار نهاية الفصل',70.00,'2026-10-26','approved',2,NULL,4,'2026-11-05 00:14:19'),
(5,1,1,3,1,'أعمال الفصل',30.00,'2026-10-16','approved',2,NULL,5,'2026-11-05 00:14:19'),
(6,1,1,3,2,'اختبار نهاية الفصل',70.00,'2026-10-26','approved',2,NULL,5,'2026-11-05 00:14:19'),
(7,1,1,4,1,'أعمال الفصل',30.00,'2026-10-16','approved',2,NULL,6,'2026-11-05 00:14:19'),
(8,1,1,4,2,'اختبار نهاية الفصل',70.00,'2026-10-26','approved',2,NULL,6,'2026-11-05 00:14:19'),
(9,1,1,5,1,'أعمال الفصل',30.00,'2026-10-16','approved',2,NULL,3,'2026-11-05 00:14:19'),
(10,1,1,5,2,'اختبار نهاية الفصل',70.00,'2026-10-26','approved',2,NULL,3,'2026-11-05 00:14:19'),
(11,1,1,6,1,'أعمال الفصل',30.00,'2026-10-16','approved',2,NULL,4,'2026-11-05 00:14:19'),
(12,1,1,6,2,'اختبار نهاية الفصل',70.00,'2026-10-26','approved',2,NULL,4,'2026-11-05 00:14:19'),
(13,1,1,7,1,'أعمال الفصل',30.00,'2026-10-16','approved',2,NULL,5,'2026-11-05 00:14:19'),
(14,1,1,7,2,'اختبار نهاية الفصل',70.00,'2026-10-26','approved',2,NULL,5,'2026-11-05 00:14:19'),
(15,1,1,8,1,'أعمال الفصل',30.00,'2026-10-16','approved',2,NULL,6,'2026-11-05 00:14:19'),
(16,1,1,8,2,'اختبار نهاية الفصل',70.00,'2026-10-26','approved',2,NULL,6,'2026-11-05 00:14:19'),
(17,1,1,9,1,'أعمال الفصل',30.00,'2026-10-16','approved',2,NULL,3,'2026-11-05 00:14:19'),
(18,1,1,9,2,'اختبار نهاية الفصل',70.00,'2026-10-26','approved',2,NULL,3,'2026-11-05 00:14:19'),
(19,1,1,10,1,'أعمال الفصل',30.00,'2026-10-16','approved',2,NULL,4,'2026-11-05 00:14:19'),
(20,1,1,10,2,'اختبار نهاية الفصل',70.00,'2026-10-26','approved',2,NULL,4,'2026-11-05 00:14:19'),
(21,1,1,11,1,'أعمال الفصل',30.00,'2026-10-16','approved',2,NULL,5,'2026-11-05 00:14:19'),
(22,1,1,11,2,'اختبار نهاية الفصل',70.00,'2026-10-26','approved',2,NULL,5,'2026-11-05 00:14:19'),
(23,1,1,12,1,'أعمال الفصل',30.00,'2026-10-16','approved',2,NULL,6,'2026-11-05 00:14:19'),
(24,1,1,12,2,'اختبار نهاية الفصل',70.00,'2026-10-26','approved',2,NULL,6,'2026-11-05 00:14:19');
/*!40000 ALTER TABLE `assessments` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `assignments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `section_subject_id` int(10) unsigned NOT NULL,
  `title` varchar(200) NOT NULL,
  `instructions` text DEFAULT NULL,
  `due_at` datetime NOT NULL,
  `max_score` decimal(6,2) DEFAULT NULL,
  `allow_late` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`),
  KEY `section_subject_id` (`section_subject_id`),
  CONSTRAINT `assignments_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `assignments_ibfk_2` FOREIGN KEY (`section_subject_id`) REFERENCES `section_subjects` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `assignments` WRITE;
/*!40000 ALTER TABLE `assignments` DISABLE KEYS */;
INSERT INTO `assignments` VALUES
(1,1,1,'واجب المعادلات','حل التمارين 1-10 من الكتاب','2026-11-08 23:59:00',10.00,1,3,'2026-11-05 00:14:19',NULL),
(2,1,2,'تعبير كتابي','اكتب فقرة عن وطنك','2026-11-10 23:59:00',10.00,0,4,'2026-11-05 00:14:19',NULL);
/*!40000 ALTER TABLE `assignments` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `assistant_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `assistant_usage` (
  `user_id` bigint(20) unsigned NOT NULL,
  `day` date NOT NULL,
  `n` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`user_id`,`day`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `assistant_usage` WRITE;
/*!40000 ALTER TABLE `assistant_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `assistant_usage` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `attendance_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `attendance_records` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `session_id` bigint(20) unsigned NOT NULL,
  `enrollment_id` bigint(20) unsigned NOT NULL,
  `status` enum('present','absent','late','excused') NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `client_uuid` char(36) DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ar` (`session_id`,`enrollment_id`),
  UNIQUE KEY `uq_ar_uuid` (`client_uuid`),
  KEY `school_id` (`school_id`,`enrollment_id`),
  KEY `enrollment_id` (`enrollment_id`),
  CONSTRAINT `attendance_records_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `attendance_records_ibfk_2` FOREIGN KEY (`session_id`) REFERENCES `attendance_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_records_ibfk_3` FOREIGN KEY (`enrollment_id`) REFERENCES `student_enrollments` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=121 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `attendance_records` WRITE;
/*!40000 ALTER TABLE `attendance_records` DISABLE KEYS */;
INSERT INTO `attendance_records` VALUES
(1,1,1,1,'present',NULL,NULL,3,'2026-11-05 00:14:19'),
(2,1,1,2,'present',NULL,NULL,3,'2026-11-05 00:14:19'),
(3,1,1,3,'present',NULL,NULL,3,'2026-11-05 00:14:19'),
(4,1,1,4,'present',NULL,NULL,3,'2026-11-05 00:14:19'),
(5,1,2,1,'present',NULL,NULL,3,'2026-11-05 00:14:19'),
(6,1,2,2,'present',NULL,NULL,3,'2026-11-05 00:14:19'),
(7,1,2,3,'present',NULL,NULL,3,'2026-11-05 00:14:19'),
(8,1,2,4,'present',NULL,NULL,3,'2026-11-05 00:14:19'),
(9,1,3,1,'present',NULL,NULL,3,'2026-11-05 00:14:19'),
(10,1,3,2,'present',NULL,NULL,3,'2026-11-05 00:14:19'),
(11,1,3,3,'present',NULL,NULL,3,'2026-11-05 00:14:19'),
(12,1,3,4,'present',NULL,NULL,3,'2026-11-05 00:14:19'),
(13,1,4,1,'present',NULL,NULL,3,'2026-11-05 00:14:19'),
(14,1,4,2,'present',NULL,NULL,3,'2026-11-05 00:14:19'),
(15,1,4,3,'present',NULL,NULL,3,'2026-11-05 00:14:19'),
(16,1,4,4,'present',NULL,NULL,3,'2026-11-05 00:14:19'),
(17,1,5,1,'present',NULL,NULL,3,'2026-11-05 00:14:19'),
(18,1,5,2,'absent',NULL,NULL,3,'2026-11-05 00:14:19'),
(19,1,5,3,'present',NULL,NULL,3,'2026-11-05 00:14:19'),
(20,1,5,4,'present',NULL,NULL,3,'2026-11-05 00:14:19'),
(21,1,6,1,'absent',NULL,NULL,3,'2026-11-05 00:14:19'),
(22,1,6,2,'present',NULL,NULL,3,'2026-11-05 00:14:19'),
(23,1,6,3,'present',NULL,NULL,3,'2026-11-05 00:14:19'),
(24,1,6,4,'present',NULL,NULL,3,'2026-11-05 00:14:19'),
(25,1,7,1,'present',NULL,NULL,3,'2026-11-05 00:14:19'),
(26,1,7,2,'present',NULL,NULL,3,'2026-11-05 00:14:19'),
(27,1,7,3,'present',NULL,NULL,3,'2026-11-05 00:14:19'),
(28,1,7,4,'present',NULL,NULL,3,'2026-11-05 00:14:19'),
(29,1,8,1,'present',NULL,NULL,3,'2026-11-05 00:14:19'),
(30,1,8,2,'present',NULL,NULL,3,'2026-11-05 00:14:19'),
(31,1,8,3,'present',NULL,NULL,3,'2026-11-05 00:14:19'),
(32,1,8,4,'present',NULL,NULL,3,'2026-11-05 00:14:19'),
(33,1,9,1,'present',NULL,NULL,3,'2026-11-05 00:14:19'),
(34,1,9,2,'present',NULL,NULL,3,'2026-11-05 00:14:19'),
(35,1,9,3,'present',NULL,NULL,3,'2026-11-05 00:14:19'),
(36,1,9,4,'present',NULL,NULL,3,'2026-11-05 00:14:19'),
(37,1,10,1,'present',NULL,NULL,3,'2026-11-05 00:14:19'),
(38,1,10,2,'present',NULL,NULL,3,'2026-11-05 00:14:19'),
(39,1,10,3,'present',NULL,NULL,3,'2026-11-05 00:14:19'),
(40,1,10,4,'present',NULL,NULL,3,'2026-11-05 00:14:19'),
(41,1,11,5,'present',NULL,NULL,4,'2026-11-05 00:14:19'),
(42,1,11,6,'present',NULL,NULL,4,'2026-11-05 00:14:19'),
(43,1,11,7,'absent',NULL,NULL,4,'2026-11-05 00:14:19'),
(44,1,11,8,'present',NULL,NULL,4,'2026-11-05 00:14:19'),
(45,1,12,5,'present',NULL,NULL,4,'2026-11-05 00:14:19'),
(46,1,12,6,'present',NULL,NULL,4,'2026-11-05 00:14:19'),
(47,1,12,7,'absent',NULL,NULL,4,'2026-11-05 00:14:19'),
(48,1,12,8,'present',NULL,NULL,4,'2026-11-05 00:14:19'),
(49,1,13,5,'present',NULL,NULL,4,'2026-11-05 00:14:19'),
(50,1,13,6,'present',NULL,NULL,4,'2026-11-05 00:14:19'),
(51,1,13,7,'absent',NULL,NULL,4,'2026-11-05 00:14:19'),
(52,1,13,8,'present',NULL,NULL,4,'2026-11-05 00:14:19'),
(53,1,14,5,'present',NULL,NULL,4,'2026-11-05 00:14:19'),
(54,1,14,6,'excused',NULL,NULL,4,'2026-11-05 00:14:19'),
(55,1,14,7,'absent',NULL,NULL,4,'2026-11-05 00:14:19'),
(56,1,14,8,'present',NULL,NULL,4,'2026-11-05 00:14:19'),
(57,1,15,5,'present',NULL,NULL,4,'2026-11-05 00:14:19'),
(58,1,15,6,'present',NULL,NULL,4,'2026-11-05 00:14:19'),
(59,1,15,7,'absent',NULL,NULL,4,'2026-11-05 00:14:19'),
(60,1,15,8,'present',NULL,NULL,4,'2026-11-05 00:14:19'),
(61,1,16,5,'present',NULL,NULL,4,'2026-11-05 00:14:19'),
(62,1,16,6,'present',NULL,NULL,4,'2026-11-05 00:14:19'),
(63,1,16,7,'absent',NULL,NULL,4,'2026-11-05 00:14:19'),
(64,1,16,8,'present',NULL,NULL,4,'2026-11-05 00:14:19'),
(65,1,17,5,'present',NULL,NULL,4,'2026-11-05 00:14:19'),
(66,1,17,6,'present',NULL,NULL,4,'2026-11-05 00:14:19'),
(67,1,17,7,'present',NULL,NULL,4,'2026-11-05 00:14:19'),
(68,1,17,8,'present',NULL,NULL,4,'2026-11-05 00:14:19'),
(69,1,18,5,'present',NULL,NULL,4,'2026-11-05 00:14:19'),
(70,1,18,6,'present',NULL,NULL,4,'2026-11-05 00:14:19'),
(71,1,18,7,'present',NULL,NULL,4,'2026-11-05 00:14:19'),
(72,1,18,8,'present',NULL,NULL,4,'2026-11-05 00:14:19'),
(73,1,19,5,'present',NULL,NULL,4,'2026-11-05 00:14:19'),
(74,1,19,6,'present',NULL,NULL,4,'2026-11-05 00:14:19'),
(75,1,19,7,'present',NULL,NULL,4,'2026-11-05 00:14:19'),
(76,1,19,8,'present',NULL,NULL,4,'2026-11-05 00:14:19'),
(77,1,20,5,'absent',NULL,NULL,4,'2026-11-05 00:14:19'),
(78,1,20,6,'present',NULL,NULL,4,'2026-11-05 00:14:19'),
(79,1,20,7,'present',NULL,NULL,4,'2026-11-05 00:14:19'),
(80,1,20,8,'present',NULL,NULL,4,'2026-11-05 00:14:19'),
(81,1,21,9,'present',NULL,NULL,5,'2026-11-05 00:14:19'),
(82,1,21,10,'present',NULL,NULL,5,'2026-11-05 00:14:19'),
(83,1,21,11,'present',NULL,NULL,5,'2026-11-05 00:14:19'),
(84,1,21,12,'excused',NULL,NULL,5,'2026-11-05 00:14:19'),
(85,1,22,9,'present',NULL,NULL,5,'2026-11-05 00:14:19'),
(86,1,22,10,'present',NULL,NULL,5,'2026-11-05 00:14:19'),
(87,1,22,11,'present',NULL,NULL,5,'2026-11-05 00:14:19'),
(88,1,22,12,'present',NULL,NULL,5,'2026-11-05 00:14:19'),
(89,1,23,9,'present',NULL,NULL,5,'2026-11-05 00:14:19'),
(90,1,23,10,'present',NULL,NULL,5,'2026-11-05 00:14:19'),
(91,1,23,11,'present',NULL,NULL,5,'2026-11-05 00:14:19'),
(92,1,23,12,'present',NULL,NULL,5,'2026-11-05 00:14:19'),
(93,1,24,9,'present',NULL,NULL,5,'2026-11-05 00:14:19'),
(94,1,24,10,'present',NULL,NULL,5,'2026-11-05 00:14:19'),
(95,1,24,11,'present',NULL,NULL,5,'2026-11-05 00:14:19'),
(96,1,24,12,'present',NULL,NULL,5,'2026-11-05 00:14:19'),
(97,1,25,9,'present',NULL,NULL,5,'2026-11-05 00:14:19'),
(98,1,25,10,'present',NULL,NULL,5,'2026-11-05 00:14:19'),
(99,1,25,11,'present',NULL,NULL,5,'2026-11-05 00:14:19'),
(100,1,25,12,'present',NULL,NULL,5,'2026-11-05 00:14:19'),
(101,1,26,9,'present',NULL,NULL,5,'2026-11-05 00:14:19'),
(102,1,26,10,'late',NULL,NULL,5,'2026-11-05 00:14:19'),
(103,1,26,11,'present',NULL,NULL,5,'2026-11-05 00:14:19'),
(104,1,26,12,'present',NULL,NULL,5,'2026-11-05 00:14:19'),
(105,1,27,9,'present',NULL,NULL,5,'2026-11-05 00:14:19'),
(106,1,27,10,'present',NULL,NULL,5,'2026-11-05 00:14:19'),
(107,1,27,11,'present',NULL,NULL,5,'2026-11-05 00:14:19'),
(108,1,27,12,'late',NULL,NULL,5,'2026-11-05 00:14:19'),
(109,1,28,9,'absent',NULL,NULL,5,'2026-11-05 00:14:19'),
(110,1,28,10,'present',NULL,NULL,5,'2026-11-05 00:14:19'),
(111,1,28,11,'present',NULL,NULL,5,'2026-11-05 00:14:19'),
(112,1,28,12,'present',NULL,NULL,5,'2026-11-05 00:14:19'),
(113,1,29,9,'present',NULL,NULL,5,'2026-11-05 00:14:19'),
(114,1,29,10,'present',NULL,NULL,5,'2026-11-05 00:14:19'),
(115,1,29,11,'present',NULL,NULL,5,'2026-11-05 00:14:19'),
(116,1,29,12,'present',NULL,NULL,5,'2026-11-05 00:14:19'),
(117,1,30,9,'present',NULL,NULL,5,'2026-11-05 00:14:19'),
(118,1,30,10,'present',NULL,NULL,5,'2026-11-05 00:14:19'),
(119,1,30,11,'present',NULL,NULL,5,'2026-11-05 00:14:19'),
(120,1,30,12,'present',NULL,NULL,5,'2026-11-05 00:14:19');
/*!40000 ALTER TABLE `attendance_records` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `attendance_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `attendance_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `timetable_slot_id` int(10) unsigned DEFAULT NULL,
  `section_id` int(10) unsigned NOT NULL,
  `section_subject_id` int(10) unsigned DEFAULT NULL,
  `session_date` date NOT NULL,
  `period_no` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `taken_by` bigint(20) unsigned DEFAULT NULL,
  `kind` enum('class','daily','live') NOT NULL DEFAULT 'class',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_as` (`section_id`,`session_date`,`period_no`,`kind`),
  KEY `school_id` (`school_id`,`session_date`),
  CONSTRAINT `attendance_sessions_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `attendance_sessions_ibfk_2` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `attendance_sessions` WRITE;
/*!40000 ALTER TABLE `attendance_sessions` DISABLE KEYS */;
INSERT INTO `attendance_sessions` VALUES
(1,1,NULL,1,NULL,'2026-11-04',0,3,'daily','2026-11-05 00:14:19'),
(2,1,NULL,1,NULL,'2026-11-03',0,3,'daily','2026-11-05 00:14:19'),
(3,1,NULL,1,NULL,'2026-10-31',0,3,'daily','2026-11-05 00:14:19'),
(4,1,NULL,1,NULL,'2026-10-30',0,3,'daily','2026-11-05 00:14:19'),
(5,1,NULL,1,NULL,'2026-10-29',0,3,'daily','2026-11-05 00:14:19'),
(6,1,NULL,1,NULL,'2026-10-28',0,3,'daily','2026-11-05 00:14:19'),
(7,1,NULL,1,NULL,'2026-10-27',0,3,'daily','2026-11-05 00:14:19'),
(8,1,NULL,1,NULL,'2026-10-24',0,3,'daily','2026-11-05 00:14:19'),
(9,1,NULL,1,NULL,'2026-10-23',0,3,'daily','2026-11-05 00:14:19'),
(10,1,NULL,1,NULL,'2026-10-22',0,3,'daily','2026-11-05 00:14:19'),
(11,1,NULL,2,NULL,'2026-11-04',0,4,'daily','2026-11-05 00:14:19'),
(12,1,NULL,2,NULL,'2026-11-03',0,4,'daily','2026-11-05 00:14:19'),
(13,1,NULL,2,NULL,'2026-10-31',0,4,'daily','2026-11-05 00:14:19'),
(14,1,NULL,2,NULL,'2026-10-30',0,4,'daily','2026-11-05 00:14:19'),
(15,1,NULL,2,NULL,'2026-10-29',0,4,'daily','2026-11-05 00:14:19'),
(16,1,NULL,2,NULL,'2026-10-28',0,4,'daily','2026-11-05 00:14:19'),
(17,1,NULL,2,NULL,'2026-10-27',0,4,'daily','2026-11-05 00:14:19'),
(18,1,NULL,2,NULL,'2026-10-24',0,4,'daily','2026-11-05 00:14:19'),
(19,1,NULL,2,NULL,'2026-10-23',0,4,'daily','2026-11-05 00:14:19'),
(20,1,NULL,2,NULL,'2026-10-22',0,4,'daily','2026-11-05 00:14:19'),
(21,1,NULL,3,NULL,'2026-11-04',0,5,'daily','2026-11-05 00:14:19'),
(22,1,NULL,3,NULL,'2026-11-03',0,5,'daily','2026-11-05 00:14:19'),
(23,1,NULL,3,NULL,'2026-10-31',0,5,'daily','2026-11-05 00:14:19'),
(24,1,NULL,3,NULL,'2026-10-30',0,5,'daily','2026-11-05 00:14:19'),
(25,1,NULL,3,NULL,'2026-10-29',0,5,'daily','2026-11-05 00:14:19'),
(26,1,NULL,3,NULL,'2026-10-28',0,5,'daily','2026-11-05 00:14:19'),
(27,1,NULL,3,NULL,'2026-10-27',0,5,'daily','2026-11-05 00:14:19'),
(28,1,NULL,3,NULL,'2026-10-24',0,5,'daily','2026-11-05 00:14:19'),
(29,1,NULL,3,NULL,'2026-10-23',0,5,'daily','2026-11-05 00:14:19'),
(30,1,NULL,3,NULL,'2026-10-22',0,5,'daily','2026-11-05 00:14:19');
/*!40000 ALTER TABLE `attendance_sessions` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(60) NOT NULL,
  `entity` varchar(60) NOT NULL,
  `entity_id` bigint(20) unsigned DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `via_support` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`,`entity`,`entity_id`),
  KEY `school_id_2` (`school_id`,`created_at`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `checkout_methods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `checkout_methods` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `kind` enum('gateway','manual') NOT NULL DEFAULT 'manual',
  `label` varchar(100) NOT NULL,
  `instructions` text DEFAULT NULL,
  `is_visible` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` smallint(6) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`,`is_visible`,`sort_order`),
  CONSTRAINT `checkout_methods_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `checkout_methods` WRITE;
/*!40000 ALTER TABLE `checkout_methods` DISABLE KEYS */;
/*!40000 ALTER TABLE `checkout_methods` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `consents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `consents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `guardian_id` bigint(20) unsigned NOT NULL,
  `kind` varchar(50) NOT NULL,
  `granted` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`),
  CONSTRAINT `consents_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `consents` WRITE;
/*!40000 ALTER TABLE `consents` DISABLE KEYS */;
INSERT INTO `consents` VALUES
(1,1,1,1,'photo',1,'2026-11-05 00:14:19'),
(2,1,1,1,'trips',1,'2026-11-05 00:14:19'),
(3,1,3,2,'photo',0,'2026-11-05 00:14:19');
/*!40000 ALTER TABLE `consents` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `contracts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contracts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `staff_id` int(10) unsigned NOT NULL,
  `kind` varchar(50) NOT NULL,
  `starts_on` date NOT NULL,
  `ends_on` date DEFAULT NULL,
  `base_salary` decimal(14,2) NOT NULL,
  `currency` char(3) NOT NULL,
  `status` enum('active','ended') NOT NULL DEFAULT 'active',
  `notes` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`),
  KEY `staff_id` (`staff_id`),
  CONSTRAINT `contracts_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `contracts_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `contracts` WRITE;
/*!40000 ALTER TABLE `contracts` DISABLE KEYS */;
INSERT INTO `contracts` VALUES
(1,1,1,'دائم','2024-11-05',NULL,150000.00,'YER','active',NULL,'2026-11-05 00:14:19'),
(2,1,2,'دائم','2024-10-06',NULL,140000.00,'YER','active',NULL,'2026-11-05 00:14:19'),
(3,1,3,'دائم','2024-09-06',NULL,140000.00,'YER','active',NULL,'2026-11-05 00:14:19'),
(4,1,4,'دائم','2024-08-07',NULL,130000.00,'YER','active',NULL,'2026-11-05 00:14:19'),
(5,1,5,'دائم','2024-07-08',NULL,180000.00,'YER','active',NULL,'2026-11-05 00:14:19'),
(6,1,6,'دائم','2024-06-08',NULL,170000.00,'YER','active',NULL,'2026-11-05 00:14:19');
/*!40000 ALTER TABLE `contracts` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `counseling_cases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `counseling_cases` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `counselor_user_id` bigint(20) unsigned NOT NULL,
  `title` varchar(200) NOT NULL,
  `notes_enc` text DEFAULT NULL,
  `status` enum('open','follow_up','closed') NOT NULL DEFAULT 'open',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `kind` enum('academic','career','behavior','social','other') NOT NULL DEFAULT 'other',
  `risk_flag_id` bigint(20) unsigned DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `counseling_cases_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `counseling_cases_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `counseling_cases` WRITE;
/*!40000 ALTER TABLE `counseling_cases` DISABLE KEYS */;
INSERT INTO `counseling_cases` VALUES
(1,1,7,8,'متابعة الغياب (تجريبي)',NULL,'open','2026-11-05 00:14:19','social',NULL,'2026-11-05 08:00:00'),
(2,1,4,8,'توجيه دراسي (تجريبي)',NULL,'closed','2026-11-05 00:14:19','academic',NULL,'2026-10-31 08:00:00');
/*!40000 ALTER TABLE `counseling_cases` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `counseling_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `counseling_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `case_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `note_enc` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `case_id` (`case_id`),
  KEY `school_id` (`school_id`),
  CONSTRAINT `counseling_notes_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `counseling_notes_ibfk_2` FOREIGN KEY (`case_id`) REFERENCES `counseling_cases` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `counseling_notes` WRITE;
/*!40000 ALTER TABLE `counseling_notes` DISABLE KEYS */;
INSERT INTO `counseling_notes` VALUES
(1,1,1,8,'','2026-11-05 00:14:19');
/*!40000 ALTER TABLE `counseling_notes` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `counters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `counters` (
  `school_id` int(10) unsigned NOT NULL,
  `name` varchar(50) NOT NULL,
  `year` smallint(5) unsigned NOT NULL DEFAULT 0,
  `value` bigint(20) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`school_id`,`name`,`year`),
  CONSTRAINT `counters_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `counters` WRITE;
/*!40000 ALTER TABLE `counters` DISABLE KEYS */;
INSERT INTO `counters` VALUES
(1,'invoice',2026,3),
(1,'receipt',2026,7),
(1,'student_no',2026,0);
/*!40000 ALTER TABLE `counters` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `credentials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `credentials` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `title` varchar(200) NOT NULL,
  `verify_code` char(20) NOT NULL,
  `issued_on` date NOT NULL,
  `issued_by` bigint(20) unsigned DEFAULT NULL,
  `revoked_at` datetime DEFAULT NULL,
  `kind` varchar(30) NOT NULL DEFAULT 'completion',
  `notes` varchar(255) DEFAULT NULL,
  `revoke_reason` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `verify_code` (`verify_code`),
  KEY `school_id` (`school_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `credentials_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `credentials_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `credentials` WRITE;
/*!40000 ALTER TABLE `credentials` DISABLE KEYS */;
INSERT INTO `credentials` VALUES
(1,1,1,'شهادة تفوق دراسي','DEMO-AAAA-2222','2026-11-02',2,NULL,'excellence',NULL,NULL),
(2,1,2,'شهادة مشاركة','DEMO-BBBB-3333','2026-10-28',2,NULL,'participation',NULL,NULL);
/*!40000 ALTER TABLE `credentials` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `currencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `currencies` (
  `code` char(3) NOT NULL,
  `name` varchar(60) NOT NULL,
  `symbol` varchar(10) NOT NULL,
  `decimals` tinyint(3) unsigned NOT NULL DEFAULT 2,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `currencies` WRITE;
/*!40000 ALTER TABLE `currencies` DISABLE KEYS */;
INSERT INTO `currencies` VALUES
('SAR','ريال سعودي','ر.س',2),
('USD','دولار أمريكي','$',2),
('YER','ريال يمني','ر.ي',2);
/*!40000 ALTER TABLE `currencies` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `discipline_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `discipline_records` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `incident_date` date NOT NULL,
  `kind` enum('violation','warning','commendation') NOT NULL,
  `description` text NOT NULL,
  `action_taken` varchar(255) DEFAULT NULL,
  `recorded_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `discipline_records_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `discipline_records_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `discipline_records` WRITE;
/*!40000 ALTER TABLE `discipline_records` DISABLE KEYS */;
INSERT INTO `discipline_records` VALUES
(1,1,7,'2026-10-31','violation','تأخر متكرر عن الطابور الصباحي','تنبيه شفهي وإبلاغ ولي الأمر',2,'2026-11-05 00:14:19',NULL),
(2,1,7,'2026-11-02','violation','عدم إحضار الكتب','تنبيه',2,'2026-11-05 00:14:19',NULL),
(3,1,1,'2026-11-01','commendation','مشاركة متميزة في الإذاعة المدرسية',NULL,2,'2026-11-05 00:14:19',NULL);
/*!40000 ALTER TABLE `discipline_records` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `discounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `discounts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `name` varchar(120) NOT NULL,
  `kind` enum('percent','fixed') NOT NULL,
  `value` decimal(14,2) NOT NULL,
  `reason` enum('sibling','scholarship','staff_child','other') NOT NULL DEFAULT 'other',
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`),
  CONSTRAINT `discounts_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `discounts` WRITE;
/*!40000 ALTER TABLE `discounts` DISABLE KEYS */;
INSERT INTO `discounts` VALUES
(1,1,'خصم الأخوة 10%','percent',10.00,'sibling'),
(2,1,'منحة تفوق','fixed',50000.00,'scholarship');
/*!40000 ALTER TABLE `discounts` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `email_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_codes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `email` varchar(190) NOT NULL,
  `purpose` varchar(20) NOT NULL DEFAULT 'reset',
  `code_hash` char(64) NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `email` (`email`,`purpose`,`created_at`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `email_codes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `email_codes` WRITE;
/*!40000 ALTER TABLE `email_codes` DISABLE KEYS */;
/*!40000 ALTER TABLE `email_codes` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `exchange_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `exchange_rates` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `currency` char(3) NOT NULL,
  `rate_to_base` decimal(18,6) NOT NULL,
  `effective_from` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`,`currency`,`effective_from`),
  KEY `currency` (`currency`),
  CONSTRAINT `exchange_rates_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `exchange_rates_ibfk_2` FOREIGN KEY (`currency`) REFERENCES `currencies` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `exchange_rates` WRITE;
/*!40000 ALTER TABLE `exchange_rates` DISABLE KEYS */;
INSERT INTO `exchange_rates` VALUES
(1,1,'USD',250.000000,'2026-11-05 00:14:19',2),
(2,1,'SAR',66.500000,'2026-11-05 00:14:19',2);
/*!40000 ALTER TABLE `exchange_rates` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `facilities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `facilities` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `name` varchar(120) NOT NULL,
  `kind` varchar(50) DEFAULT NULL,
  `capacity` smallint(5) unsigned DEFAULT NULL,
  `status` enum('available','maintenance') NOT NULL DEFAULT 'available',
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`),
  CONSTRAINT `facilities_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `facilities` WRITE;
/*!40000 ALTER TABLE `facilities` DISABLE KEYS */;
INSERT INTO `facilities` VALUES
(1,1,'مختبر الحاسوب (تجريبي)','lab',25,'available'),
(2,1,'المكتبة (تجريبي)','library',40,'available'),
(3,1,'الملعب (تجريبي)','sports',100,'maintenance');
/*!40000 ALTER TABLE `facilities` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `facility_bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `facility_bookings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `facility_id` int(10) unsigned NOT NULL,
  `booked_by` bigint(20) unsigned NOT NULL,
  `starts_at` datetime NOT NULL,
  `ends_at` datetime NOT NULL,
  `purpose` varchar(200) DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `facility_id` (`facility_id`,`starts_at`),
  KEY `school_id` (`school_id`),
  CONSTRAINT `facility_bookings_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `facility_bookings_ibfk_2` FOREIGN KEY (`facility_id`) REFERENCES `facilities` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `facility_bookings` WRITE;
/*!40000 ALTER TABLE `facility_bookings` DISABLE KEYS */;
INSERT INTO `facility_bookings` VALUES
(1,1,1,3,'2026-11-06 09:00:00','2026-11-06 10:00:00','حصة حاسوب',NULL),
(2,1,2,4,'2026-11-07 11:00:00','2026-11-07 12:00:00','نشاط قراءة',NULL),
(3,1,1,5,'2026-11-08 09:00:00','2026-11-08 11:00:00','اختبار عملي',NULL);
/*!40000 ALTER TABLE `facility_bookings` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fee_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `fee_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `structure_id` int(10) unsigned NOT NULL,
  `name` varchar(120) NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `installments_count` tinyint(3) unsigned NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`),
  KEY `structure_id` (`structure_id`),
  CONSTRAINT `fee_items_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `fee_items_ibfk_2` FOREIGN KEY (`structure_id`) REFERENCES `fee_structures` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fee_items` WRITE;
/*!40000 ALTER TABLE `fee_items` DISABLE KEYS */;
INSERT INTO `fee_items` VALUES
(1,1,1,'الرسوم الدراسية',250000.00,3),
(2,1,1,'الكتب والأدوات',50000.00,3),
(3,1,2,'الرسوم الدراسية',300000.00,3),
(4,1,2,'الكتب والأدوات',60000.00,3);
/*!40000 ALTER TABLE `fee_items` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fee_structures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `fee_structures` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `academic_year_id` int(10) unsigned NOT NULL,
  `grade_level_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(120) NOT NULL,
  `currency` char(3) NOT NULL,
  `installments_count` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `first_due_date` date DEFAULT NULL,
  `interval_months` tinyint(3) unsigned NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`),
  KEY `academic_year_id` (`academic_year_id`),
  CONSTRAINT `fee_structures_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `fee_structures_ibfk_2` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fee_structures` WRITE;
/*!40000 ALTER TABLE `fee_structures` DISABLE KEYS */;
INSERT INTO `fee_structures` VALUES
(1,1,1,1,'رسوم الصف الأول','YER',3,'2026-09-06',1),
(2,1,1,2,'رسوم الصف الثاني','YER',3,'2026-09-06',1);
/*!40000 ALTER TABLE `fee_structures` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `files` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned DEFAULT NULL,
  `owner_user_id` bigint(20) unsigned DEFAULT NULL,
  `context` varchar(50) NOT NULL,
  `context_id` bigint(20) unsigned DEFAULT NULL,
  `original_name` varchar(255) NOT NULL,
  `stored_name` varchar(100) NOT NULL,
  `mime` varchar(100) NOT NULL,
  `size_bytes` bigint(20) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`,`context`,`context_id`),
  CONSTRAINT `files_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `files` WRITE;
/*!40000 ALTER TABLE `files` DISABLE KEYS */;
/*!40000 ALTER TABLE `files` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `grade_levels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `grade_levels` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `stage_id` int(10) unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `sort_order` smallint(6) NOT NULL DEFAULT 0,
  `next_grade_level_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_grade` (`school_id`,`name`),
  KEY `stage_id` (`stage_id`),
  KEY `next_grade_level_id` (`next_grade_level_id`),
  CONSTRAINT `grade_levels_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `grade_levels_ibfk_2` FOREIGN KEY (`stage_id`) REFERENCES `stages` (`id`),
  CONSTRAINT `grade_levels_ibfk_3` FOREIGN KEY (`next_grade_level_id`) REFERENCES `grade_levels` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `grade_levels` WRITE;
/*!40000 ALTER TABLE `grade_levels` DISABLE KEYS */;
INSERT INTO `grade_levels` VALUES
(1,1,1,'الصف الأول (تجريبي)',1,2),
(2,1,1,'الصف الثاني (تجريبي)',2,NULL);
/*!40000 ALTER TABLE `grade_levels` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `grade_scales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `grade_scales` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `scheme_id` int(10) unsigned NOT NULL,
  `label` varchar(50) NOT NULL,
  `min_percent` decimal(5,2) NOT NULL,
  `gpa_points` decimal(4,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`),
  KEY `scheme_id` (`scheme_id`),
  CONSTRAINT `grade_scales_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `grade_scales_ibfk_2` FOREIGN KEY (`scheme_id`) REFERENCES `grading_schemes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `grade_scales` WRITE;
/*!40000 ALTER TABLE `grade_scales` DISABLE KEYS */;
INSERT INTO `grade_scales` VALUES
(1,1,1,'ممتاز',90.00,NULL),
(2,1,1,'جيد جدًا',80.00,NULL),
(3,1,1,'جيد',65.00,NULL),
(4,1,1,'مقبول',50.00,NULL),
(5,1,1,'ضعيف',0.00,NULL);
/*!40000 ALTER TABLE `grade_scales` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `grades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `grades` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `assessment_id` bigint(20) unsigned NOT NULL,
  `enrollment_id` bigint(20) unsigned NOT NULL,
  `score` decimal(6,2) DEFAULT NULL,
  `is_absent` tinyint(1) NOT NULL DEFAULT 0,
  `note` varchar(255) DEFAULT NULL,
  `client_uuid` char(36) DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_g` (`assessment_id`,`enrollment_id`),
  KEY `school_id` (`school_id`,`enrollment_id`),
  KEY `enrollment_id` (`enrollment_id`),
  CONSTRAINT `grades_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `grades_ibfk_2` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `grades_ibfk_3` FOREIGN KEY (`enrollment_id`) REFERENCES `student_enrollments` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=97 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `grades` WRITE;
/*!40000 ALTER TABLE `grades` DISABLE KEYS */;
INSERT INTO `grades` VALUES
(1,1,1,1,29.40,0,NULL,NULL,3,'2026-11-05 00:14:19'),
(2,1,1,2,23.60,0,NULL,NULL,3,'2026-11-05 00:14:19'),
(3,1,1,3,22.90,0,NULL,NULL,3,'2026-11-05 00:14:19'),
(4,1,1,4,29.20,0,NULL,NULL,3,'2026-11-05 00:14:19'),
(5,1,2,1,67.50,0,NULL,NULL,3,'2026-11-05 00:14:19'),
(6,1,2,2,48.90,0,NULL,NULL,3,'2026-11-05 00:14:19'),
(7,1,2,3,43.00,0,NULL,NULL,3,'2026-11-05 00:14:19'),
(8,1,2,4,35.50,0,NULL,NULL,3,'2026-11-05 00:14:19'),
(9,1,3,1,17.50,0,NULL,NULL,4,'2026-11-05 00:14:19'),
(10,1,3,2,18.60,0,NULL,NULL,4,'2026-11-05 00:14:19'),
(11,1,3,3,14.80,0,NULL,NULL,4,'2026-11-05 00:14:19'),
(12,1,3,4,17.10,0,NULL,NULL,4,'2026-11-05 00:14:19'),
(13,1,4,1,43.40,0,NULL,NULL,4,'2026-11-05 00:14:19'),
(14,1,4,2,52.60,0,NULL,NULL,4,'2026-11-05 00:14:19'),
(15,1,4,3,44.20,0,NULL,NULL,4,'2026-11-05 00:14:19'),
(16,1,4,4,45.00,0,NULL,NULL,4,'2026-11-05 00:14:19'),
(17,1,5,1,29.60,0,NULL,NULL,5,'2026-11-05 00:14:19'),
(18,1,5,2,22.30,0,NULL,NULL,5,'2026-11-05 00:14:19'),
(19,1,5,3,15.70,0,NULL,NULL,5,'2026-11-05 00:14:19'),
(20,1,5,4,19.70,0,NULL,NULL,5,'2026-11-05 00:14:19'),
(21,1,6,1,59.10,0,NULL,NULL,5,'2026-11-05 00:14:19'),
(22,1,6,2,44.20,0,NULL,NULL,5,'2026-11-05 00:14:19'),
(23,1,6,3,50.10,0,NULL,NULL,5,'2026-11-05 00:14:19'),
(24,1,6,4,49.20,0,NULL,NULL,5,'2026-11-05 00:14:19'),
(25,1,7,1,25.50,0,NULL,NULL,6,'2026-11-05 00:14:19'),
(26,1,7,2,28.30,0,NULL,NULL,6,'2026-11-05 00:14:19'),
(27,1,7,3,19.20,0,NULL,NULL,6,'2026-11-05 00:14:19'),
(28,1,7,4,19.90,0,NULL,NULL,6,'2026-11-05 00:14:19'),
(29,1,8,1,57.30,0,NULL,NULL,6,'2026-11-05 00:14:19'),
(30,1,8,2,45.10,0,NULL,NULL,6,'2026-11-05 00:14:19'),
(31,1,8,3,67.80,0,NULL,NULL,6,'2026-11-05 00:14:19'),
(32,1,8,4,67.00,0,NULL,NULL,6,'2026-11-05 00:14:19'),
(33,1,9,5,28.00,0,NULL,NULL,3,'2026-11-05 00:14:19'),
(34,1,9,6,19.40,0,NULL,NULL,3,'2026-11-05 00:14:19'),
(35,1,9,7,9.80,0,NULL,NULL,3,'2026-11-05 00:14:19'),
(36,1,9,8,14.60,0,NULL,NULL,3,'2026-11-05 00:14:19'),
(37,1,10,5,44.00,0,NULL,NULL,3,'2026-11-05 00:14:19'),
(38,1,10,6,64.00,0,NULL,NULL,3,'2026-11-05 00:14:19'),
(39,1,10,7,23.90,0,NULL,NULL,3,'2026-11-05 00:14:19'),
(40,1,10,8,70.00,0,NULL,NULL,3,'2026-11-05 00:14:19'),
(41,1,11,5,27.90,0,NULL,NULL,4,'2026-11-05 00:14:19'),
(42,1,11,6,18.00,0,NULL,NULL,4,'2026-11-05 00:14:19'),
(43,1,11,7,9.00,0,NULL,NULL,4,'2026-11-05 00:14:19'),
(44,1,11,8,19.70,0,NULL,NULL,4,'2026-11-05 00:14:19'),
(45,1,12,5,68.80,0,NULL,NULL,4,'2026-11-05 00:14:19'),
(46,1,12,6,54.30,0,NULL,NULL,4,'2026-11-05 00:14:19'),
(47,1,12,7,27.90,0,NULL,NULL,4,'2026-11-05 00:14:19'),
(48,1,12,8,42.80,0,NULL,NULL,4,'2026-11-05 00:14:19'),
(49,1,13,5,29.30,0,NULL,NULL,5,'2026-11-05 00:14:19'),
(50,1,13,6,25.80,0,NULL,NULL,5,'2026-11-05 00:14:19'),
(51,1,13,7,9.00,0,NULL,NULL,5,'2026-11-05 00:14:19'),
(52,1,13,8,25.60,0,NULL,NULL,5,'2026-11-05 00:14:19'),
(53,1,14,5,67.30,0,NULL,NULL,5,'2026-11-05 00:14:19'),
(54,1,14,6,49.00,0,NULL,NULL,5,'2026-11-05 00:14:19'),
(55,1,14,7,29.50,0,NULL,NULL,5,'2026-11-05 00:14:19'),
(56,1,14,8,56.10,0,NULL,NULL,5,'2026-11-05 00:14:19'),
(57,1,15,5,16.40,0,NULL,NULL,6,'2026-11-05 00:14:19'),
(58,1,15,6,29.40,0,NULL,NULL,6,'2026-11-05 00:14:19'),
(59,1,15,7,8.80,0,NULL,NULL,6,'2026-11-05 00:14:19'),
(60,1,15,8,29.20,0,NULL,NULL,6,'2026-11-05 00:14:19'),
(61,1,16,5,56.10,0,NULL,NULL,6,'2026-11-05 00:14:19'),
(62,1,16,6,50.50,0,NULL,NULL,6,'2026-11-05 00:14:19'),
(63,1,16,7,19.10,0,NULL,NULL,6,'2026-11-05 00:14:19'),
(64,1,16,8,68.70,0,NULL,NULL,6,'2026-11-05 00:14:19'),
(65,1,17,9,25.20,0,NULL,NULL,3,'2026-11-05 00:14:19'),
(66,1,17,10,23.80,0,NULL,NULL,3,'2026-11-05 00:14:19'),
(67,1,17,11,25.50,0,NULL,NULL,3,'2026-11-05 00:14:19'),
(68,1,17,12,18.70,0,NULL,NULL,3,'2026-11-05 00:14:19'),
(69,1,18,9,46.50,0,NULL,NULL,3,'2026-11-05 00:14:19'),
(70,1,18,10,45.20,0,NULL,NULL,3,'2026-11-05 00:14:19'),
(71,1,18,11,46.90,0,NULL,NULL,3,'2026-11-05 00:14:19'),
(72,1,18,12,48.40,0,NULL,NULL,3,'2026-11-05 00:14:19'),
(73,1,19,9,25.80,0,NULL,NULL,4,'2026-11-05 00:14:19'),
(74,1,19,10,23.80,0,NULL,NULL,4,'2026-11-05 00:14:19'),
(75,1,19,11,23.00,0,NULL,NULL,4,'2026-11-05 00:14:19'),
(76,1,19,12,20.80,0,NULL,NULL,4,'2026-11-05 00:14:19'),
(77,1,20,9,47.90,0,NULL,NULL,4,'2026-11-05 00:14:19'),
(78,1,20,10,42.00,0,NULL,NULL,4,'2026-11-05 00:14:19'),
(79,1,20,11,43.30,0,NULL,NULL,4,'2026-11-05 00:14:19'),
(80,1,20,12,60.50,0,NULL,NULL,4,'2026-11-05 00:14:19'),
(81,1,21,9,20.70,0,NULL,NULL,5,'2026-11-05 00:14:19'),
(82,1,21,10,24.90,0,NULL,NULL,5,'2026-11-05 00:14:19'),
(83,1,21,11,18.10,0,NULL,NULL,5,'2026-11-05 00:14:19'),
(84,1,21,12,18.60,0,NULL,NULL,5,'2026-11-05 00:14:19'),
(85,1,22,9,61.30,0,NULL,NULL,5,'2026-11-05 00:14:19'),
(86,1,22,10,57.90,0,NULL,NULL,5,'2026-11-05 00:14:19'),
(87,1,22,11,64.70,0,NULL,NULL,5,'2026-11-05 00:14:19'),
(88,1,22,12,56.60,0,NULL,NULL,5,'2026-11-05 00:14:19'),
(89,1,23,9,23.90,0,NULL,NULL,6,'2026-11-05 00:14:19'),
(90,1,23,10,22.40,0,NULL,NULL,6,'2026-11-05 00:14:19'),
(91,1,23,11,24.60,0,NULL,NULL,6,'2026-11-05 00:14:19'),
(92,1,23,12,27.10,0,NULL,NULL,6,'2026-11-05 00:14:19'),
(93,1,24,9,63.50,0,NULL,NULL,6,'2026-11-05 00:14:19'),
(94,1,24,10,59.90,0,NULL,NULL,6,'2026-11-05 00:14:19'),
(95,1,24,11,59.10,0,NULL,NULL,6,'2026-11-05 00:14:19'),
(96,1,24,12,41.40,0,NULL,NULL,6,'2026-11-05 00:14:19');
/*!40000 ALTER TABLE `grades` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `grading_schemes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `grading_schemes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `stage_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `max_score` decimal(6,2) NOT NULL DEFAULT 100.00,
  `pass_score` decimal(6,2) NOT NULL DEFAULT 50.00,
  `average_method` enum('weighted','arithmetic') NOT NULL DEFAULT 'weighted',
  `rounding` tinyint(3) unsigned NOT NULL DEFAULT 2,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`),
  KEY `stage_id` (`stage_id`),
  CONSTRAINT `grading_schemes_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `grading_schemes_ibfk_2` FOREIGN KEY (`stage_id`) REFERENCES `stages` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `grading_schemes` WRITE;
/*!40000 ALTER TABLE `grading_schemes` DISABLE KEYS */;
INSERT INTO `grading_schemes` VALUES
(1,1,NULL,'النظام الافتراضي',100.00,50.00,'weighted',2,1);
/*!40000 ALTER TABLE `grading_schemes` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `guardians`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `guardians` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `full_name` varchar(200) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `whatsapp` varchar(30) DEFAULT NULL,
  `email` varchar(190) DEFAULT NULL,
  `job` varchar(120) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `national_id_enc` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `phone` (`phone`),
  CONSTRAINT `guardians_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `guardians` WRITE;
/*!40000 ALTER TABLE `guardians` DISABLE KEYS */;
INSERT INTO `guardians` VALUES
(1,9,'أحمد صالح العمراني','+967773001001','+967773001001',NULL,'موظف','صنعاء - حي التحرير',NULL,'2026-11-05 00:14:19',NULL),
(2,10,'سالم عمر باوزير','+967773001002','+967773001002',NULL,'موظف','صنعاء - حي التحرير',NULL,'2026-11-05 00:14:19',NULL),
(3,11,'محمد قاسم الحكيمي','+967773001003','+967773001003',NULL,'موظف','صنعاء - حي التحرير',NULL,'2026-11-05 00:14:19',NULL),
(4,12,'علي حمود الوصابي','+967773001004','+967773001004',NULL,'موظف','صنعاء - حي التحرير',NULL,'2026-11-05 00:14:19',NULL),
(5,13,'حسين طاهر الأهدل','+967773001005','+967773001005',NULL,'موظف','صنعاء - حي التحرير',NULL,'2026-11-05 00:14:19',NULL),
(6,14,'فهد ناصر المنصوب','+967773001006','+967773001006',NULL,'موظف','صنعاء - حي التحرير',NULL,'2026-11-05 00:14:19',NULL),
(7,15,'عبدالرحمن خالد الكبسي','+967773001007','+967773001007',NULL,'موظف','صنعاء - حي التحرير',NULL,'2026-11-05 00:14:19',NULL),
(8,16,'ياسر محمد الصلوي','+967773001008','+967773001008',NULL,'موظف','صنعاء - حي التحرير',NULL,'2026-11-05 00:14:19',NULL),
(9,17,'جمال أمين القاضي','+967773001009','+967773001009',NULL,'موظف','صنعاء - حي التحرير',NULL,'2026-11-05 00:14:19',NULL),
(10,18,'منير سعيد الشامي','+967773001010','+967773001010',NULL,'موظف','صنعاء - حي التحرير',NULL,'2026-11-05 00:14:19',NULL);
/*!40000 ALTER TABLE `guardians` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `import_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `import_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `kind` enum('students','guardians','staff','grades','subjects','sections','scores') NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `stored_name` varchar(100) NOT NULL,
  `status` enum('uploaded','previewed','committed','failed') NOT NULL DEFAULT 'uploaded',
  `total_rows` int(10) unsigned NOT NULL DEFAULT 0,
  `ok_rows` int(10) unsigned NOT NULL DEFAULT 0,
  `error_rows` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`),
  CONSTRAINT `import_jobs_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `import_jobs` WRITE;
/*!40000 ALTER TABLE `import_jobs` DISABLE KEYS */;
INSERT INTO `import_jobs` VALUES
(1,1,2,'students','طلاب-تجريبي.xlsx','demo-import-none','committed',3,2,1,'2026-11-05 00:14:19');
/*!40000 ALTER TABLE `import_jobs` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `import_rows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `import_rows` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `job_id` bigint(20) unsigned NOT NULL,
  `row_no` int(10) unsigned NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`data`)),
  `status` enum('ok','error','imported') NOT NULL,
  `errors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`errors`)),
  PRIMARY KEY (`id`),
  KEY `job_id` (`job_id`),
  CONSTRAINT `import_rows_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `import_jobs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `import_rows` WRITE;
/*!40000 ALTER TABLE `import_rows` DISABLE KEYS */;
INSERT INTO `import_rows` VALUES
(1,1,2,'{\"full_name\": \"طالب مستورد\"}','imported',NULL),
(2,1,3,'{\"full_name\": \"\"}','error','[\"الاسم مطلوب\"]');
/*!40000 ALTER TABLE `import_rows` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `installments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `installments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `student_fee_id` bigint(20) unsigned NOT NULL,
  `seq` tinyint(3) unsigned NOT NULL,
  `due_date` date NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `paid_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','partial','paid') NOT NULL DEFAULT 'pending',
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`,`due_date`,`status`),
  KEY `student_fee_id` (`student_fee_id`),
  CONSTRAINT `installments_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `installments_ibfk_2` FOREIGN KEY (`student_fee_id`) REFERENCES `student_fees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `installments` WRITE;
/*!40000 ALTER TABLE `installments` DISABLE KEYS */;
INSERT INTO `installments` VALUES
(1,1,1,1,'2026-09-06',100000.00,100000.00,'paid'),
(2,1,1,2,'2026-10-06',100000.00,0.00,'pending'),
(3,1,1,3,'2026-11-05',100000.00,0.00,'pending'),
(4,1,2,1,'2026-09-06',90000.00,90000.00,'paid'),
(5,1,2,2,'2026-10-06',90000.00,40000.00,'partial'),
(6,1,2,3,'2026-11-05',90000.00,0.00,'pending'),
(7,1,3,1,'2026-09-06',100000.00,100000.00,'paid'),
(8,1,3,2,'2026-10-06',100000.00,0.00,'pending'),
(9,1,3,3,'2026-11-05',100000.00,0.00,'pending'),
(10,1,4,1,'2026-09-06',100000.00,0.00,'pending'),
(11,1,4,2,'2026-10-06',100000.00,0.00,'pending'),
(12,1,4,3,'2026-11-05',100000.00,0.00,'pending'),
(13,1,5,1,'2026-09-06',100000.00,100000.00,'paid'),
(14,1,5,2,'2026-10-06',100000.00,100000.00,'paid'),
(15,1,5,3,'2026-11-05',100000.00,0.00,'pending'),
(16,1,6,1,'2026-09-06',100000.00,0.00,'pending'),
(17,1,6,2,'2026-10-06',100000.00,0.00,'pending'),
(18,1,6,3,'2026-11-05',100000.00,0.00,'pending'),
(19,1,7,1,'2026-09-06',100000.00,0.00,'pending'),
(20,1,7,2,'2026-10-06',100000.00,0.00,'pending'),
(21,1,7,3,'2026-11-05',100000.00,0.00,'pending'),
(22,1,8,1,'2026-09-06',100000.00,0.00,'pending'),
(23,1,8,2,'2026-10-06',100000.00,0.00,'pending'),
(24,1,8,3,'2026-11-05',100000.00,0.00,'pending'),
(25,1,9,1,'2026-09-06',120000.00,30000.00,'partial'),
(26,1,9,2,'2026-10-06',120000.00,0.00,'pending'),
(27,1,9,3,'2026-11-05',120000.00,0.00,'pending'),
(28,1,10,1,'2026-09-06',120000.00,0.00,'pending'),
(29,1,10,2,'2026-10-06',120000.00,0.00,'pending'),
(30,1,10,3,'2026-11-05',120000.00,0.00,'pending'),
(31,1,11,1,'2026-09-06',120000.00,0.00,'pending'),
(32,1,11,2,'2026-10-06',120000.00,0.00,'pending'),
(33,1,11,3,'2026-11-05',120000.00,0.00,'pending'),
(34,1,12,1,'2026-09-06',120000.00,0.00,'pending'),
(35,1,12,2,'2026-10-06',120000.00,0.00,'pending'),
(36,1,12,3,'2026-11-05',120000.00,0.00,'pending');
/*!40000 ALTER TABLE `installments` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `invoice_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoice_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `description` varchar(200) NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_id` (`invoice_id`),
  CONSTRAINT `invoice_lines_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `invoice_lines` WRITE;
/*!40000 ALTER TABLE `invoice_lines` DISABLE KEYS */;
INSERT INTO `invoice_lines` VALUES
(1,1,'الرسوم الدراسية',250000.00),
(2,1,'الكتب والأدوات',50000.00),
(3,2,'الرسوم الدراسية بعد الخصم',270000.00),
(4,3,'الرسوم الدراسية والكتب',300000.00);
/*!40000 ALTER TABLE `invoice_lines` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `number` varchar(30) NOT NULL,
  `guardian_id` bigint(20) unsigned DEFAULT NULL,
  `student_fee_id` bigint(20) unsigned DEFAULT NULL,
  `currency` char(3) NOT NULL,
  `rate_to_base` decimal(18,6) NOT NULL,
  `subtotal` decimal(14,2) NOT NULL,
  `tax_percent` decimal(5,2) DEFAULT NULL,
  `tax_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total` decimal(14,2) NOT NULL,
  `status` enum('issued','partial','paid','cancelled') NOT NULL DEFAULT 'issued',
  `issued_on` date NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_reason` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_inv` (`school_id`,`number`),
  CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `invoices` WRITE;
/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;
INSERT INTO `invoices` VALUES
(1,1,'INV-2026-000001',1,1,'YER',1.000000,300000.00,NULL,0.00,300000.00,'partial','2026-09-26',2,NULL),
(2,1,'INV-2026-000002',1,2,'YER',1.000000,270000.00,NULL,0.00,270000.00,'partial','2026-09-26',2,NULL),
(3,1,'INV-2026-000003',2,3,'YER',1.000000,300000.00,NULL,0.00,300000.00,'partial','2026-09-26',2,NULL);
/*!40000 ALTER TABLE `invoices` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `lessons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lessons` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `section_subject_id` int(10) unsigned NOT NULL,
  `term_id` int(10) unsigned DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `body` mediumtext DEFAULT NULL,
  `sort_order` smallint(6) NOT NULL DEFAULT 0,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`),
  KEY `section_subject_id` (`section_subject_id`),
  CONSTRAINT `lessons_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `lessons_ibfk_2` FOREIGN KEY (`section_subject_id`) REFERENCES `section_subjects` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `lessons` WRITE;
/*!40000 ALTER TABLE `lessons` DISABLE KEYS */;
INSERT INTO `lessons` VALUES
(1,1,1,1,'المعادلات من الدرجة الأولى','شرح مبسّط لحل المعادلات مع أمثلة.',1,1,3,'2026-11-05 00:14:19',NULL),
(2,1,1,1,'النسبة والتناسب','تعريف النسبة وخصائص التناسب.',2,1,3,'2026-11-05 00:14:19',NULL),
(3,1,2,1,'الجملة الاسمية والفعلية','قواعد وأمثلة.',1,1,4,'2026-11-05 00:14:19',NULL),
(4,1,3,1,'دورة الماء في الطبيعة','درس علوم مسودة.',1,0,5,'2026-11-05 00:14:19',NULL);
/*!40000 ALTER TABLE `lessons` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `live_attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `live_attendance` (
  `live_session_id` bigint(20) unsigned NOT NULL,
  `enrollment_id` bigint(20) unsigned NOT NULL,
  `joined_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`live_session_id`,`enrollment_id`),
  CONSTRAINT `live_attendance_ibfk_1` FOREIGN KEY (`live_session_id`) REFERENCES `live_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `live_attendance` WRITE;
/*!40000 ALTER TABLE `live_attendance` DISABLE KEYS */;
INSERT INTO `live_attendance` VALUES
(2,1,'2026-11-03 16:02:00'),
(2,2,'2026-11-03 16:05:00');
/*!40000 ALTER TABLE `live_attendance` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `live_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `live_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `section_subject_id` int(10) unsigned NOT NULL,
  `title` varchar(200) NOT NULL,
  `provider` enum('zoom','meet','jitsi','teams','bbb','other') NOT NULL,
  `join_url` varchar(500) NOT NULL,
  `starts_at` datetime NOT NULL,
  `ends_at` datetime DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`),
  KEY `section_subject_id` (`section_subject_id`),
  CONSTRAINT `live_sessions_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `live_sessions_ibfk_2` FOREIGN KEY (`section_subject_id`) REFERENCES `section_subjects` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `live_sessions` WRITE;
/*!40000 ALTER TABLE `live_sessions` DISABLE KEYS */;
INSERT INTO `live_sessions` VALUES
(1,1,1,'مراجعة الفصل','jitsi','https://meet.jit.si/madrasati-demo-review-2026','2026-11-06 17:00:00','2026-11-06 18:00:00',3,'حصة مراجعة مباشرة'),
(2,1,2,'درس النحو','meet','https://meet.google.com/demo-nahw-xyz','2026-11-03 16:00:00','2026-11-03 17:00:00',4,NULL);
/*!40000 ALTER TABLE `live_sessions` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_attempts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `identifier` varchar(190) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `success` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `identifier` (`identifier`,`created_at`),
  KEY `ip` (`ip`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `login_attempts` WRITE;
/*!40000 ALTER TABLE `login_attempts` DISABLE KEYS */;
/*!40000 ALTER TABLE `login_attempts` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `materials` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `lesson_id` bigint(20) unsigned DEFAULT NULL,
  `section_subject_id` int(10) unsigned NOT NULL,
  `kind` enum('file','link','video') NOT NULL,
  `title` varchar(200) NOT NULL,
  `file_id` bigint(20) unsigned DEFAULT NULL,
  `url` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`),
  KEY `section_subject_id` (`section_subject_id`),
  CONSTRAINT `materials_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `materials_ibfk_2` FOREIGN KEY (`section_subject_id`) REFERENCES `section_subjects` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `materials` WRITE;
/*!40000 ALTER TABLE `materials` DISABLE KEYS */;
INSERT INTO `materials` VALUES
(1,1,1,1,'video','شرح فيديو: حل المعادلات',NULL,'https://www.youtube.com/watch?v=demo1','2026-11-05 00:14:19',NULL),
(2,1,2,1,'link','تمارين تفاعلية',NULL,'https://example.com/ratio-practice','2026-11-05 00:14:19',NULL),
(3,1,3,2,'link','ملخص القواعد',NULL,'https://example.com/arabic-grammar','2026-11-05 00:14:19',NULL);
/*!40000 ALTER TABLE `materials` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `message_recipients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `message_recipients` (
  `message_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `read_at` datetime DEFAULT NULL,
  PRIMARY KEY (`message_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `message_recipients_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `message_recipients_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `message_recipients` WRITE;
/*!40000 ALTER TABLE `message_recipients` DISABLE KEYS */;
INSERT INTO `message_recipients` VALUES
(1,9,'2026-11-05 08:00:00'),
(2,3,NULL),
(3,3,NULL),
(3,4,NULL);
/*!40000 ALTER TABLE `message_recipients` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `sender_user_id` bigint(20) unsigned NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `body` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `thread_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`),
  KEY `sender_user_id` (`sender_user_id`),
  KEY `idx_thread` (`thread_id`),
  CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`sender_user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
INSERT INTO `messages` VALUES
(1,1,3,NULL,'نتيجة الاختبار','أود إبلاغكم بتحسن مستوى يوسف في الرياضيات.','2026-11-05 00:14:19',1),
(2,1,9,1,'رد: نتيجة الاختبار','شكرًا أستاذ خالد على اهتمامكم.','2026-11-05 00:14:19',1),
(3,1,2,NULL,'تنبيه إداري','يرجى تسليم كشوف الدرجات قبل نهاية الأسبوع.','2026-11-05 00:14:19',3);
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `name` varchar(190) NOT NULL,
  `ran_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES
('001_platform_identity.sql','2026-11-05 00:14:17'),
('002_general.sql','2026-11-05 00:14:17'),
('003_academic.sql','2026-11-05 00:14:18'),
('004_people.sql','2026-11-05 00:14:18'),
('005_attendance_timetable_grading.sql','2026-11-05 00:14:18'),
('006_learning_comms.sql','2026-11-05 00:14:18'),
('007_finance.sql','2026-11-05 00:14:18'),
('008_extended.sql','2026-11-05 00:14:18'),
('009_portal.sql','2026-11-05 00:14:18'),
('010_finance_extras.sql','2026-11-05 00:14:18'),
('011_lms.sql','2026-11-05 00:14:18'),
('012_extended.sql','2026-11-05 00:14:18'),
('013_platform_settings.sql','2026-11-05 00:14:18'),
('014_online_payments.sql','2026-11-05 00:14:18'),
('015_security_events.sql','2026-11-05 00:14:18'),
('016_email_codes.sql','2026-11-05 00:14:18'),
('017_school_requests.sql','2026-11-05 00:14:18'),
('018_subject_grades.sql','2026-11-05 00:14:18'),
('019_question_images.sql','2026-11-05 00:14:18'),
('020_checkout_methods.sql','2026-11-05 00:14:18'),
('021_assistant.sql','2026-11-05 00:14:18'),
('022_school_backups.sql','2026-11-05 00:14:18'),
('023_study_plans_records.sql','2026-11-05 00:14:18'),
('024_import_kinds.sql','2026-11-05 00:14:18');
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `notification_channels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_channels` (
  `user_id` bigint(20) unsigned NOT NULL,
  `channel` enum('inapp','sms','whatsapp','email') NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`user_id`,`channel`),
  CONSTRAINT `notification_channels_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `notification_channels` WRITE;
/*!40000 ALTER TABLE `notification_channels` DISABLE KEYS */;
INSERT INTO `notification_channels` VALUES
(3,'inapp',1),
(3,'sms',0),
(9,'inapp',1),
(9,'sms',1);
/*!40000 ALTER TABLE `notification_channels` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `notification_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_queue` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned DEFAULT NULL,
  `channel` enum('sms','whatsapp','email') NOT NULL,
  `recipient` varchar(190) NOT NULL,
  `body` text NOT NULL,
  `status` enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `attempts` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `last_error` varchar(255) DEFAULT NULL,
  `run_after` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `sent_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`,`run_after`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `notification_queue` WRITE;
/*!40000 ALTER TABLE `notification_queue` DISABLE KEYS */;
INSERT INTO `notification_queue` VALUES
(1,1,'sms','+9677730010000','تذكير: اجتماع أولياء الأمور يوم الخميس','pending',0,NULL,'2026-11-05 00:14:19','2026-11-05 00:14:19',NULL),
(2,1,'sms','+9677730010020','رسالة تجريبية','failed',5,'لا يوجد مزود مضبوط','2026-11-05 00:14:19','2026-11-05 00:14:19',NULL);
/*!40000 ALTER TABLE `notification_queue` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `title` varchar(200) NOT NULL,
  `body` varchar(500) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`,`read_at`),
  KEY `idx_notif_user` (`user_id`,`created_at`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES
(1,1,19,'واجب جديد','واجب المعادلات: موعد التسليم بعد 3 أيام','/learn',NULL,'2026-11-05 00:14:19'),
(2,1,9,'رسالة جديدة','لديك رسالة من أ. خالد','/messages',NULL,'2026-11-05 00:14:19'),
(3,1,3,'إعلان جديد','اجتماع أولياء الأمور','/announcements','2026-11-05 08:00:00','2026-11-05 00:14:19');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `online_payment_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `online_payment_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `online_payment_id` bigint(20) unsigned DEFAULT NULL,
  `kind` varchar(20) NOT NULL,
  `ok` tinyint(1) NOT NULL,
  `detail` varchar(255) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`,`created_at`),
  KEY `online_payment_id` (`online_payment_id`),
  CONSTRAINT `online_payment_events_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `online_payment_events` WRITE;
/*!40000 ALTER TABLE `online_payment_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `online_payment_events` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `online_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `online_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `reference` char(24) NOT NULL,
  `student_fee_id` bigint(20) unsigned NOT NULL,
  `guardian_user_id` bigint(20) unsigned DEFAULT NULL,
  `amount` decimal(14,2) NOT NULL,
  `currency` char(3) NOT NULL,
  `status` enum('pending','paid','failed','cancelled','expired','review') NOT NULL DEFAULT 'pending',
  `driver` varchar(20) NOT NULL,
  `provider_ref` varchar(120) DEFAULT NULL,
  `checkout_url` varchar(1000) DEFAULT NULL,
  `payment_id` bigint(20) unsigned DEFAULT NULL,
  `failure_reason` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference` (`reference`),
  KEY `school_id` (`school_id`,`status`),
  KEY `student_fee_id` (`student_fee_id`),
  CONSTRAINT `online_payments_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `online_payments_ibfk_2` FOREIGN KEY (`student_fee_id`) REFERENCES `student_fees` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `online_payments` WRITE;
/*!40000 ALTER TABLE `online_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `online_payments` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `otp_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `otp_codes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `phone` varchar(30) NOT NULL,
  `code_hash` varchar(255) NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `phone` (`phone`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `otp_codes` WRITE;
/*!40000 ALTER TABLE `otp_codes` DISABLE KEYS */;
/*!40000 ALTER TABLE `otp_codes` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `token_hash` (`token_hash`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `payment_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_allocations` (
  `payment_id` bigint(20) unsigned NOT NULL,
  `installment_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  PRIMARY KEY (`payment_id`,`installment_id`),
  KEY `installment_id` (`installment_id`),
  CONSTRAINT `payment_allocations_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`),
  CONSTRAINT `payment_allocations_ibfk_2` FOREIGN KEY (`installment_id`) REFERENCES `installments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `payment_allocations` WRITE;
/*!40000 ALTER TABLE `payment_allocations` DISABLE KEYS */;
INSERT INTO `payment_allocations` VALUES
(1,1,100000.00),
(2,4,90000.00),
(3,5,40000.00),
(4,7,100000.00),
(5,13,100000.00),
(6,14,100000.00),
(7,25,30000.00);
/*!40000 ALTER TABLE `payment_allocations` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `payment_claims`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_claims` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `student_fee_id` bigint(20) unsigned NOT NULL,
  `guardian_user_id` bigint(20) unsigned NOT NULL,
  `method_id` int(10) unsigned NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `currency` char(3) NOT NULL,
  `reference_no` varchar(100) NOT NULL,
  `paid_on` date NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `receipt_file_id` bigint(20) unsigned DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `decided_by` bigint(20) unsigned DEFAULT NULL,
  `decided_at` datetime DEFAULT NULL,
  `decision_note` varchar(255) DEFAULT NULL,
  `payment_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`,`status`,`created_at`),
  KEY `student_fee_id` (`student_fee_id`),
  KEY `school_id_2` (`school_id`,`reference_no`),
  KEY `method_id` (`method_id`),
  CONSTRAINT `payment_claims_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `payment_claims_ibfk_2` FOREIGN KEY (`student_fee_id`) REFERENCES `student_fees` (`id`),
  CONSTRAINT `payment_claims_ibfk_3` FOREIGN KEY (`method_id`) REFERENCES `checkout_methods` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `payment_claims` WRITE;
/*!40000 ALTER TABLE `payment_claims` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_claims` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `payment_methods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_methods` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `kind` enum('cash','bank','wallet','exchange') NOT NULL,
  `name` varchar(100) NOT NULL,
  `account_info` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`),
  CONSTRAINT `payment_methods_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `payment_methods` WRITE;
/*!40000 ALTER TABLE `payment_methods` DISABLE KEYS */;
INSERT INTO `payment_methods` VALUES
(1,1,'cash','نقدًا',NULL,1),
(2,1,'bank','تحويل بنكي (تجريبي)','حساب رقم 123456',1),
(3,1,'wallet','محفظة إلكترونية (تجريبي)','0770000000',1);
/*!40000 ALTER TABLE `payment_methods` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  `installment_id` bigint(20) unsigned DEFAULT NULL,
  `student_fee_id` bigint(20) unsigned DEFAULT NULL,
  `method_id` int(10) unsigned DEFAULT NULL,
  `currency` char(3) NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `rate_to_base` decimal(18,6) NOT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `paid_on` date NOT NULL,
  `received_by` bigint(20) unsigned DEFAULT NULL,
  `voided_at` datetime DEFAULT NULL,
  `void_reason` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `fee_currency` char(3) DEFAULT NULL,
  `fee_rate_to_base` decimal(18,6) DEFAULT NULL,
  `applied_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `idx_pay_fee` (`school_id`,`student_fee_id`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES
(1,1,NULL,1,1,NULL,'YER',100000.00,1.000000,NULL,'2026-10-27',2,NULL,NULL,'2026-11-05 00:14:19','YER',1.000000,100000.00),
(2,1,NULL,4,2,NULL,'YER',90000.00,1.000000,NULL,'2026-10-28',2,NULL,NULL,'2026-11-05 00:14:19','YER',1.000000,90000.00),
(3,1,NULL,5,2,NULL,'YER',40000.00,1.000000,NULL,'2026-10-29',2,NULL,NULL,'2026-11-05 00:14:19','YER',1.000000,40000.00),
(4,1,NULL,7,3,2,'YER',100000.00,1.000000,'TRF-0004','2026-10-30',2,NULL,NULL,'2026-11-05 00:14:19','YER',1.000000,100000.00),
(5,1,NULL,13,5,NULL,'YER',100000.00,1.000000,NULL,'2026-10-31',2,NULL,NULL,'2026-11-05 00:14:19','YER',1.000000,100000.00),
(6,1,NULL,14,5,NULL,'YER',100000.00,1.000000,NULL,'2026-11-01',2,NULL,NULL,'2026-11-05 00:14:19','YER',1.000000,100000.00),
(7,1,NULL,25,9,NULL,'USD',120.00,250.000000,NULL,'2026-11-02',2,NULL,NULL,'2026-11-05 00:14:19','YER',1.000000,30000.00);
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `payroll_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payroll_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `run_id` int(10) unsigned NOT NULL,
  `staff_id` int(10) unsigned NOT NULL,
  `base` decimal(14,2) NOT NULL,
  `allowances` decimal(14,2) NOT NULL DEFAULT 0.00,
  `deductions` decimal(14,2) NOT NULL DEFAULT 0.00,
  `net` decimal(14,2) NOT NULL,
  `currency` char(3) NOT NULL,
  `contract_id` int(10) unsigned DEFAULT NULL,
  `days_worked` tinyint(3) unsigned DEFAULT NULL,
  `days_in_month` tinyint(3) unsigned DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `run_id` (`run_id`),
  KEY `staff_id` (`staff_id`),
  CONSTRAINT `payroll_items_ibfk_1` FOREIGN KEY (`run_id`) REFERENCES `payroll_runs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payroll_items_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `payroll_items` WRITE;
/*!40000 ALTER TABLE `payroll_items` DISABLE KEYS */;
INSERT INTO `payroll_items` VALUES
(1,1,1,150000.00,5000.00,0.00,155000.00,'YER',1,31,31,NULL),
(2,1,2,140000.00,5000.00,0.00,145000.00,'YER',2,31,31,NULL),
(3,1,3,140000.00,5000.00,0.00,145000.00,'YER',3,31,31,NULL),
(4,1,4,130000.00,5000.00,0.00,135000.00,'YER',4,31,31,NULL),
(5,1,5,180000.00,0.00,0.00,180000.00,'YER',5,31,31,NULL),
(6,1,6,170000.00,0.00,0.00,170000.00,'YER',6,31,31,NULL);
/*!40000 ALTER TABLE `payroll_items` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `payroll_runs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payroll_runs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `period` char(7) NOT NULL,
  `status` enum('draft','approved','paid') NOT NULL DEFAULT 'draft',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `paid_on` date DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pr` (`school_id`,`period`),
  CONSTRAINT `payroll_runs_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `payroll_runs` WRITE;
/*!40000 ALTER TABLE `payroll_runs` DISABLE KEYS */;
INSERT INTO `payroll_runs` VALUES
(1,1,'2026-10','paid',2,'2026-10-26',2,'2026-10-22 08:00:00','2026-11-05 00:14:19');
/*!40000 ALTER TABLE `payroll_runs` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(80) NOT NULL,
  `module` varchar(50) NOT NULL,
  `label` varchar(150) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES
(1,'portal.view','portal','بوابة الطالب وولي الأمر'),
(2,'facilities.book','extended','حجز المرافق'),
(3,'analytics.view','extended','التحليلات المتقدمة'),
(4,'platform.settings','platform','إعدادات المنصة (مزود الرسائل)'),
(5,'fees.gateway','fees','إعداد الدفع الإلكتروني'),
(6,'platform.schools','platform','إدارة المدارس'),
(7,'platform.plans','platform','إدارة خطط الاشتراك'),
(8,'platform.support','platform','الدخول للدعم الفني'),
(9,'platform.audit','platform','سجل تدقيق المنصة'),
(11,'school.settings','school','إعدادات المدرسة'),
(12,'rbac.manage','school','إدارة الأدوار والمستخدمين'),
(13,'audit.view','school','عرض سجل التدقيق'),
(14,'backup.manage','school','النسخ الاحتياطي للمدرسة'),
(15,'academic.view','academic','عرض الهيكل الأكاديمي'),
(16,'academic.manage','academic','إدارة السنوات والصفوف والشعب والمواد'),
(17,'academic.promote','academic','الترفيع وإغلاق السنة'),
(18,'students.view','students','عرض الطلاب'),
(19,'students.manage','students','إضافة وتعديل الطلاب'),
(20,'students.lifecycle','students','تغيير حالة الطالب'),
(21,'students.discipline','students','سجل الانضباط'),
(22,'guardians.manage','students','إدارة أولياء الأمور'),
(23,'staff.view','staff','عرض الموظفين'),
(24,'staff.manage','staff','إدارة الموظفين والمعلمين'),
(25,'staff.attendance','staff','حضور الموظفين'),
(26,'import.run','import','الاستيراد الجماعي'),
(27,'timetable.view','timetable','عرض الجدول'),
(28,'timetable.manage','timetable','إدارة الجدول الدراسي'),
(29,'attendance.view','attendance','عرض الحضور'),
(30,'attendance.take','attendance','تسجيل الحضور'),
(31,'attendance.edit','attendance','تعديل الحضور بعد التسجيل'),
(32,'grades.view','grades','عرض الدرجات'),
(33,'grades.edit','grades','إدخال الدرجات'),
(34,'grades.approve','grades','اعتماد وإقفال الدرجات'),
(35,'grades.config','grades','تهيئة نظام الدرجات'),
(36,'reportcards.issue','grades','إصدار الشهادات'),
(37,'reports.view','reports','التقارير'),
(38,'reports.export','reports','تصدير التقارير'),
(39,'fees.view','fees','عرض الرسوم'),
(40,'fees.manage','fees','إدارة هياكل الرسوم'),
(41,'fees.collect','fees','تحصيل الدفعات'),
(42,'fees.void','fees','إلغاء السندات'),
(44,'lms.view','lms','عرض المحتوى التعليمي'),
(45,'lms.manage','lms','إدارة المحتوى والاختبارات'),
(46,'comms.view','comms','الرسائل'),
(47,'comms.announce','comms','نشر الإعلانات'),
(48,'comms.notify','comms','إرسال إشعارات جماعية'),
(50,'admissions.manage','extended','القبول والتسجيل'),
(51,'risk.view','extended','الإنذار المبكر'),
(52,'counseling.manage','extended','الإرشاد'),
(53,'payroll.manage','extended','الرواتب والعقود'),
(54,'facilities.manage','extended','المرافق');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `platform_admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `platform_admins` (
  `user_id` bigint(20) unsigned NOT NULL,
  `is_owner` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `platform_admins_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `platform_admins` WRITE;
/*!40000 ALTER TABLE `platform_admins` DISABLE KEYS */;
INSERT INTO `platform_admins` VALUES
(1,1);
/*!40000 ALTER TABLE `platform_admins` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `question_bank`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `question_bank` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `subject_id` int(10) unsigned NOT NULL,
  `kind` enum('mcq','true_false','short','essay') NOT NULL,
  `body` text NOT NULL,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options`)),
  `answer` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`answer`)),
  `points` decimal(5,2) NOT NULL DEFAULT 1.00,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `image_file_id` bigint(20) unsigned DEFAULT NULL,
  `option_images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`option_images`)),
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`),
  KEY `subject_id` (`subject_id`),
  CONSTRAINT `question_bank_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `question_bank_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `question_bank` WRITE;
/*!40000 ALTER TABLE `question_bank` DISABLE KEYS */;
INSERT INTO `question_bank` VALUES
(1,1,1,'mcq','ما ناتج 3 × 4؟','[\"10\", \"12\", \"14\"]','1',1.00,3,NULL,NULL,NULL),
(2,1,1,'true_false','العدد 7 عدد أولي',NULL,'true',1.00,3,NULL,NULL,NULL),
(3,1,1,'short','ما ناتج 15 ÷ 3؟',NULL,'[\"5\", \"\\u062e\\u0645\\u0633\\u0629\"]',1.00,3,NULL,NULL,NULL),
(4,1,1,'essay','اشرح طريقة حل معادلة من الدرجة الأولى.',NULL,NULL,3.00,3,NULL,NULL,NULL);
/*!40000 ALTER TABLE `question_bank` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `quiz_answers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `quiz_answers` (
  `attempt_id` bigint(20) unsigned NOT NULL,
  `question_id` bigint(20) unsigned NOT NULL,
  `answer` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`answer`)),
  `score` decimal(5,2) DEFAULT NULL,
  PRIMARY KEY (`attempt_id`,`question_id`),
  CONSTRAINT `quiz_answers_ibfk_1` FOREIGN KEY (`attempt_id`) REFERENCES `quiz_attempts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `quiz_answers` WRITE;
/*!40000 ALTER TABLE `quiz_answers` DISABLE KEYS */;
INSERT INTO `quiz_answers` VALUES
(1,1,'1',1.00),
(1,2,'true',1.00),
(1,3,'\"5\"',1.00),
(1,4,'\"\\u0623\\u0646\\u0642\\u0644 \\u0627\\u0644\\u062d\\u062f\\u0648\\u062f \\u0648\\u0623\\u0642\\u0633\\u0645.\"',NULL),
(2,1,'1',1.00),
(2,2,'true',1.00);
/*!40000 ALTER TABLE `quiz_answers` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `quiz_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `quiz_attempts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `quiz_id` bigint(20) unsigned NOT NULL,
  `enrollment_id` bigint(20) unsigned NOT NULL,
  `started_at` datetime NOT NULL DEFAULT current_timestamp(),
  `submitted_at` datetime DEFAULT NULL,
  `question_order` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`question_order`)),
  `score` decimal(6,2) DEFAULT NULL,
  `status` enum('in_progress','submitted','graded') NOT NULL DEFAULT 'in_progress',
  `deadline_at` datetime DEFAULT NULL,
  `graded_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`),
  KEY `enrollment_id` (`enrollment_id`),
  KEY `idx_qa` (`quiz_id`,`enrollment_id`),
  CONSTRAINT `quiz_attempts_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `quiz_attempts_ibfk_2` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`),
  CONSTRAINT `quiz_attempts_ibfk_3` FOREIGN KEY (`enrollment_id`) REFERENCES `student_enrollments` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `quiz_attempts` WRITE;
/*!40000 ALTER TABLE `quiz_attempts` DISABLE KEYS */;
INSERT INTO `quiz_attempts` VALUES
(1,1,1,1,'2026-11-04 10:00:00','2026-11-04 10:10:00','[2, 1, 4, 3]',3.00,'submitted','2026-11-04 10:15:00',NULL),
(2,1,1,2,'2026-11-04 11:00:00','2026-11-04 11:12:00','[1, 3, 2, 4]',4.00,'graded','2026-11-04 11:15:00','2026-11-05 08:00:00');
/*!40000 ALTER TABLE `quiz_attempts` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `quiz_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `quiz_questions` (
  `quiz_id` bigint(20) unsigned NOT NULL,
  `question_id` bigint(20) unsigned NOT NULL,
  `sort_order` smallint(6) NOT NULL DEFAULT 0,
  `points` decimal(5,2) DEFAULT NULL,
  PRIMARY KEY (`quiz_id`,`question_id`),
  KEY `question_id` (`question_id`),
  CONSTRAINT `quiz_questions_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `quiz_questions_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `question_bank` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `quiz_questions` WRITE;
/*!40000 ALTER TABLE `quiz_questions` DISABLE KEYS */;
INSERT INTO `quiz_questions` VALUES
(1,1,1,NULL),
(1,2,2,NULL),
(1,3,3,NULL),
(1,4,4,NULL);
/*!40000 ALTER TABLE `quiz_questions` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `quizzes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `quizzes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `section_subject_id` int(10) unsigned NOT NULL,
  `title` varchar(200) NOT NULL,
  `opens_at` datetime DEFAULT NULL,
  `closes_at` datetime DEFAULT NULL,
  `duration_min` smallint(5) unsigned DEFAULT NULL,
  `shuffle_questions` tinyint(1) NOT NULL DEFAULT 1,
  `max_attempts` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `description` text DEFAULT NULL,
  `show_answers` enum('after_submit','after_close','never') NOT NULL DEFAULT 'after_submit',
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`),
  KEY `section_subject_id` (`section_subject_id`),
  CONSTRAINT `quizzes_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `quizzes_ibfk_2` FOREIGN KEY (`section_subject_id`) REFERENCES `section_subjects` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `quizzes` WRITE;
/*!40000 ALTER TABLE `quizzes` DISABLE KEYS */;
INSERT INTO `quizzes` VALUES
(1,1,1,'اختبار قصير: الأعداد','2026-11-03 08:00:00','2026-11-10 08:00:00',15,1,1,1,3,NULL,'اختبار تجريبي من 4 أسئلة','after_submit');
/*!40000 ALTER TABLE `quizzes` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `rate_limits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rate_limits` (
  `rkey` varchar(190) NOT NULL,
  `hits` int(10) unsigned NOT NULL DEFAULT 0,
  `window_start` datetime NOT NULL,
  PRIMARY KEY (`rkey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `rate_limits` WRITE;
/*!40000 ALTER TABLE `rate_limits` DISABLE KEYS */;
/*!40000 ALTER TABLE `rate_limits` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `receipts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `receipts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `payment_id` bigint(20) unsigned NOT NULL,
  `number` varchar(30) NOT NULL,
  `issued_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_rcpt` (`school_id`,`number`),
  UNIQUE KEY `uq_rcpt_pay` (`payment_id`),
  CONSTRAINT `receipts_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `receipts_ibfk_2` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `receipts` WRITE;
/*!40000 ALTER TABLE `receipts` DISABLE KEYS */;
INSERT INTO `receipts` VALUES
(1,1,1,'RC-2026-000001','2026-11-05 00:14:19'),
(2,1,2,'RC-2026-000002','2026-11-05 00:14:19'),
(3,1,3,'RC-2026-000003','2026-11-05 00:14:19'),
(4,1,4,'RC-2026-000004','2026-11-05 00:14:19'),
(5,1,5,'RC-2026-000005','2026-11-05 00:14:19'),
(6,1,6,'RC-2026-000006','2026-11-05 00:14:19'),
(7,1,7,'RC-2026-000007','2026-11-05 00:14:19');
/*!40000 ALTER TABLE `receipts` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `report_cards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `report_cards` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `enrollment_id` bigint(20) unsigned NOT NULL,
  `term_id` int(10) unsigned DEFAULT NULL,
  `kind` enum('term','final') NOT NULL,
  `snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`snapshot`)),
  `average` decimal(6,2) DEFAULT NULL,
  `rank_in_section` smallint(5) unsigned DEFAULT NULL,
  `result` varchar(30) DEFAULT NULL,
  `issued_at` datetime NOT NULL DEFAULT current_timestamp(),
  `issued_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_rc` (`enrollment_id`,`term_id`,`kind`),
  KEY `school_id` (`school_id`),
  CONSTRAINT `report_cards_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `report_cards_ibfk_2` FOREIGN KEY (`enrollment_id`) REFERENCES `student_enrollments` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `report_cards` WRITE;
/*!40000 ALTER TABLE `report_cards` DISABLE KEYS */;
INSERT INTO `report_cards` VALUES
(1,1,1,1,'term','{\"school\": {\"name\": \"مدرسة (تجريبي)\", \"city\": \"\", \"phone\": \"\"}, \"year\": \"السنة التجريبية\", \"term\": \"الفصل الأول\", \"is_final\": false, \"section\": \"الصف الأول (تجريبي) / أ\", \"student\": {\"name\": \"يوسف أحمد العمراني\", \"no\": \"D0001\"}, \"scheme\": {\"name\": \"النظام الافتراضي\", \"max_score\": 100, \"pass_score\": 50}, \"subjects\": [{\"name\": \"الرياضيات (تجريبي)\", \"total\": 96.9, \"percent\": 96.9, \"letter\": \"ممتاز\", \"passed\": true}, {\"name\": \"اللغة العربية (تجريبي)\", \"total\": 60.9, \"percent\": 60.9, \"letter\": \"مقبول\", \"passed\": true}, {\"name\": \"العلوم (تجريبي)\", \"total\": 88.7, \"percent\": 88.7, \"letter\": \"جيد جدًا\", \"passed\": true}, {\"name\": \"اللغة الإنجليزية (تجريبي)\", \"total\": 82.8, \"percent\": 82.8, \"letter\": \"جيد جدًا\", \"passed\": true}], \"average\": 82.33, \"letter\": \"جيد جدًا\", \"rank\": 1, \"class_size\": 4, \"result\": \"ناجح\", \"attendance\": {\"absent\": 0, \"late\": 0, \"excused\": 0}, \"incomplete\": false, \"issued_at\": \"2026-11-03\"}',82.33,1,'ناجح','2026-11-05 00:14:19',2),
(2,1,2,1,'term','{\"school\": {\"name\": \"مدرسة (تجريبي)\", \"city\": \"\", \"phone\": \"\"}, \"year\": \"السنة التجريبية\", \"term\": \"الفصل الأول\", \"is_final\": false, \"section\": \"الصف الأول (تجريبي) / أ\", \"student\": {\"name\": \"مريم أحمد العمراني\", \"no\": \"D0002\"}, \"scheme\": {\"name\": \"النظام الافتراضي\", \"max_score\": 100, \"pass_score\": 50}, \"subjects\": [{\"name\": \"الرياضيات (تجريبي)\", \"total\": 72.5, \"percent\": 72.5, \"letter\": \"جيد\", \"passed\": true}, {\"name\": \"اللغة العربية (تجريبي)\", \"total\": 71.2, \"percent\": 71.2, \"letter\": \"جيد\", \"passed\": true}, {\"name\": \"العلوم (تجريبي)\", \"total\": 66.5, \"percent\": 66.5, \"letter\": \"جيد\", \"passed\": true}, {\"name\": \"اللغة الإنجليزية (تجريبي)\", \"total\": 73.4, \"percent\": 73.4, \"letter\": \"جيد\", \"passed\": true}], \"average\": 70.9, \"letter\": \"جيد\", \"rank\": 2, \"class_size\": 4, \"result\": \"ناجح\", \"attendance\": {\"absent\": 0, \"late\": 0, \"excused\": 0}, \"incomplete\": false, \"issued_at\": \"2026-11-03\"}',70.90,2,'ناجح','2026-11-05 00:14:19',2),
(3,1,4,1,'term','{\"school\": {\"name\": \"مدرسة (تجريبي)\", \"city\": \"\", \"phone\": \"\"}, \"year\": \"السنة التجريبية\", \"term\": \"الفصل الأول\", \"is_final\": false, \"section\": \"الصف الأول (تجريبي) / أ\", \"student\": {\"name\": \"نور محمد الحكيمي\", \"no\": \"D0004\"}, \"scheme\": {\"name\": \"النظام الافتراضي\", \"max_score\": 100, \"pass_score\": 50}, \"subjects\": [{\"name\": \"الرياضيات (تجريبي)\", \"total\": 64.7, \"percent\": 64.7, \"letter\": \"مقبول\", \"passed\": true}, {\"name\": \"اللغة العربية (تجريبي)\", \"total\": 62.1, \"percent\": 62.1, \"letter\": \"مقبول\", \"passed\": true}, {\"name\": \"العلوم (تجريبي)\", \"total\": 68.9, \"percent\": 68.9, \"letter\": \"جيد\", \"passed\": true}, {\"name\": \"اللغة الإنجليزية (تجريبي)\", \"total\": 86.9, \"percent\": 86.9, \"letter\": \"جيد جدًا\", \"passed\": true}], \"average\": 70.65, \"letter\": \"جيد\", \"rank\": 3, \"class_size\": 4, \"result\": \"ناجح\", \"attendance\": {\"absent\": 0, \"late\": 0, \"excused\": 0}, \"incomplete\": false, \"issued_at\": \"2026-11-03\"}',70.65,3,'ناجح','2026-11-05 00:14:19',2),
(4,1,3,1,'term','{\"school\": {\"name\": \"مدرسة (تجريبي)\", \"city\": \"\", \"phone\": \"\"}, \"year\": \"السنة التجريبية\", \"term\": \"الفصل الأول\", \"is_final\": false, \"section\": \"الصف الأول (تجريبي) / أ\", \"student\": {\"name\": \"عمر سالم باوزير\", \"no\": \"D0003\"}, \"scheme\": {\"name\": \"النظام الافتراضي\", \"max_score\": 100, \"pass_score\": 50}, \"subjects\": [{\"name\": \"الرياضيات (تجريبي)\", \"total\": 65.9, \"percent\": 65.9, \"letter\": \"جيد\", \"passed\": true}, {\"name\": \"اللغة العربية (تجريبي)\", \"total\": 59.0, \"percent\": 59.0, \"letter\": \"مقبول\", \"passed\": true}, {\"name\": \"العلوم (تجريبي)\", \"total\": 65.8, \"percent\": 65.8, \"letter\": \"جيد\", \"passed\": true}, {\"name\": \"اللغة الإنجليزية (تجريبي)\", \"total\": 87.0, \"percent\": 87.0, \"letter\": \"جيد جدًا\", \"passed\": true}], \"average\": 69.42, \"letter\": \"جيد\", \"rank\": 4, \"class_size\": 4, \"result\": \"ناجح\", \"attendance\": {\"absent\": 0, \"late\": 0, \"excused\": 0}, \"incomplete\": false, \"issued_at\": \"2026-11-03\"}',69.42,4,'ناجح','2026-11-05 00:14:19',2),
(5,1,5,1,'term','{\"school\": {\"name\": \"مدرسة (تجريبي)\", \"city\": \"\", \"phone\": \"\"}, \"year\": \"السنة التجريبية\", \"term\": \"الفصل الأول\", \"is_final\": false, \"section\": \"الصف الأول (تجريبي) / ب\", \"student\": {\"name\": \"أسامة علي الوصابي\", \"no\": \"D0005\"}, \"scheme\": {\"name\": \"النظام الافتراضي\", \"max_score\": 100, \"pass_score\": 50}, \"subjects\": [{\"name\": \"الرياضيات (تجريبي)\", \"total\": 72.0, \"percent\": 72.0, \"letter\": \"جيد\", \"passed\": true}, {\"name\": \"اللغة العربية (تجريبي)\", \"total\": 96.7, \"percent\": 96.7, \"letter\": \"ممتاز\", \"passed\": true}, {\"name\": \"العلوم (تجريبي)\", \"total\": 96.6, \"percent\": 96.6, \"letter\": \"ممتاز\", \"passed\": true}, {\"name\": \"اللغة الإنجليزية (تجريبي)\", \"total\": 72.5, \"percent\": 72.5, \"letter\": \"جيد\", \"passed\": true}], \"average\": 84.45, \"letter\": \"جيد جدًا\", \"rank\": 1, \"class_size\": 4, \"result\": \"ناجح\", \"attendance\": {\"absent\": 0, \"late\": 0, \"excused\": 0}, \"incomplete\": false, \"issued_at\": \"2026-11-03\"}',84.45,1,'ناجح','2026-11-05 00:14:19',2),
(6,1,8,1,'term','{\"school\": {\"name\": \"مدرسة (تجريبي)\", \"city\": \"\", \"phone\": \"\"}, \"year\": \"السنة التجريبية\", \"term\": \"الفصل الأول\", \"is_final\": false, \"section\": \"الصف الأول (تجريبي) / ب\", \"student\": {\"name\": \"ديما فهد المنصوب\", \"no\": \"D0008\"}, \"scheme\": {\"name\": \"النظام الافتراضي\", \"max_score\": 100, \"pass_score\": 50}, \"subjects\": [{\"name\": \"الرياضيات (تجريبي)\", \"total\": 84.6, \"percent\": 84.6, \"letter\": \"جيد جدًا\", \"passed\": true}, {\"name\": \"اللغة العربية (تجريبي)\", \"total\": 62.5, \"percent\": 62.5, \"letter\": \"مقبول\", \"passed\": true}, {\"name\": \"العلوم (تجريبي)\", \"total\": 81.7, \"percent\": 81.7, \"letter\": \"جيد جدًا\", \"passed\": true}, {\"name\": \"اللغة الإنجليزية (تجريبي)\", \"total\": 97.9, \"percent\": 97.9, \"letter\": \"ممتاز\", \"passed\": true}], \"average\": 81.67, \"letter\": \"جيد جدًا\", \"rank\": 2, \"class_size\": 4, \"result\": \"ناجح\", \"attendance\": {\"absent\": 0, \"late\": 0, \"excused\": 0}, \"incomplete\": false, \"issued_at\": \"2026-11-03\"}',81.67,2,'ناجح','2026-11-05 00:14:19',2),
(7,1,6,1,'term','{\"school\": {\"name\": \"مدرسة (تجريبي)\", \"city\": \"\", \"phone\": \"\"}, \"year\": \"السنة التجريبية\", \"term\": \"الفصل الأول\", \"is_final\": false, \"section\": \"الصف الأول (تجريبي) / ب\", \"student\": {\"name\": \"لينا علي الوصابي\", \"no\": \"D0006\"}, \"scheme\": {\"name\": \"النظام الافتراضي\", \"max_score\": 100, \"pass_score\": 50}, \"subjects\": [{\"name\": \"الرياضيات (تجريبي)\", \"total\": 83.4, \"percent\": 83.4, \"letter\": \"جيد جدًا\", \"passed\": true}, {\"name\": \"اللغة العربية (تجريبي)\", \"total\": 72.3, \"percent\": 72.3, \"letter\": \"جيد\", \"passed\": true}, {\"name\": \"العلوم (تجريبي)\", \"total\": 74.8, \"percent\": 74.8, \"letter\": \"جيد\", \"passed\": true}, {\"name\": \"اللغة الإنجليزية (تجريبي)\", \"total\": 79.9, \"percent\": 79.9, \"letter\": \"جيد\", \"passed\": true}], \"average\": 77.6, \"letter\": \"جيد\", \"rank\": 3, \"class_size\": 4, \"result\": \"ناجح\", \"attendance\": {\"absent\": 0, \"late\": 0, \"excused\": 0}, \"incomplete\": false, \"issued_at\": \"2026-11-03\"}',77.60,3,'ناجح','2026-11-05 00:14:19',2),
(8,1,7,1,'term','{\"school\": {\"name\": \"مدرسة (تجريبي)\", \"city\": \"\", \"phone\": \"\"}, \"year\": \"السنة التجريبية\", \"term\": \"الفصل الأول\", \"is_final\": false, \"section\": \"الصف الأول (تجريبي) / ب\", \"student\": {\"name\": \"طارق حسين الأهدل\", \"no\": \"D0007\"}, \"scheme\": {\"name\": \"النظام الافتراضي\", \"max_score\": 100, \"pass_score\": 50}, \"subjects\": [{\"name\": \"الرياضيات (تجريبي)\", \"total\": 33.7, \"percent\": 33.7, \"letter\": \"ضعيف\", \"passed\": false}, {\"name\": \"اللغة العربية (تجريبي)\", \"total\": 36.9, \"percent\": 36.9, \"letter\": \"ضعيف\", \"passed\": false}, {\"name\": \"العلوم (تجريبي)\", \"total\": 38.5, \"percent\": 38.5, \"letter\": \"ضعيف\", \"passed\": false}, {\"name\": \"اللغة الإنجليزية (تجريبي)\", \"total\": 27.9, \"percent\": 27.9, \"letter\": \"ضعيف\", \"passed\": false}], \"average\": 34.25, \"letter\": \"ضعيف\", \"rank\": 4, \"class_size\": 4, \"result\": \"مكمل\", \"attendance\": {\"absent\": 0, \"late\": 0, \"excused\": 0}, \"incomplete\": false, \"issued_at\": \"2026-11-03\"}',34.25,4,'مكمل','2026-11-05 00:14:19',2),
(9,1,9,1,'term','{\"school\": {\"name\": \"مدرسة (تجريبي)\", \"city\": \"\", \"phone\": \"\"}, \"year\": \"السنة التجريبية\", \"term\": \"الفصل الأول\", \"is_final\": false, \"section\": \"الصف الثاني (تجريبي) / أ\", \"student\": {\"name\": \"زيد عبدالرحمن الكبسي\", \"no\": \"D0009\"}, \"scheme\": {\"name\": \"النظام الافتراضي\", \"max_score\": 100, \"pass_score\": 50}, \"subjects\": [{\"name\": \"الرياضيات (تجريبي)\", \"total\": 71.7, \"percent\": 71.7, \"letter\": \"جيد\", \"passed\": true}, {\"name\": \"اللغة العربية (تجريبي)\", \"total\": 73.7, \"percent\": 73.7, \"letter\": \"جيد\", \"passed\": true}, {\"name\": \"العلوم (تجريبي)\", \"total\": 82.0, \"percent\": 82.0, \"letter\": \"جيد جدًا\", \"passed\": true}, {\"name\": \"اللغة الإنجليزية (تجريبي)\", \"total\": 87.4, \"percent\": 87.4, \"letter\": \"جيد جدًا\", \"passed\": true}], \"average\": 78.7, \"letter\": \"جيد\", \"rank\": 1, \"class_size\": 4, \"result\": \"ناجح\", \"attendance\": {\"absent\": 0, \"late\": 0, \"excused\": 0}, \"incomplete\": false, \"issued_at\": \"2026-11-03\"}',78.70,1,'ناجح','2026-11-05 00:14:19',2),
(10,1,11,1,'term','{\"school\": {\"name\": \"مدرسة (تجريبي)\", \"city\": \"\", \"phone\": \"\"}, \"year\": \"السنة التجريبية\", \"term\": \"الفصل الأول\", \"is_final\": false, \"section\": \"الصف الثاني (تجريبي) / أ\", \"student\": {\"name\": \"بسام جمال القاضي\", \"no\": \"D0011\"}, \"scheme\": {\"name\": \"النظام الافتراضي\", \"max_score\": 100, \"pass_score\": 50}, \"subjects\": [{\"name\": \"الرياضيات (تجريبي)\", \"total\": 72.4, \"percent\": 72.4, \"letter\": \"جيد\", \"passed\": true}, {\"name\": \"اللغة العربية (تجريبي)\", \"total\": 66.3, \"percent\": 66.3, \"letter\": \"جيد\", \"passed\": true}, {\"name\": \"العلوم (تجريبي)\", \"total\": 82.8, \"percent\": 82.8, \"letter\": \"جيد جدًا\", \"passed\": true}, {\"name\": \"اللغة الإنجليزية (تجريبي)\", \"total\": 83.7, \"percent\": 83.7, \"letter\": \"جيد جدًا\", \"passed\": true}], \"average\": 76.3, \"letter\": \"جيد\", \"rank\": 2, \"class_size\": 4, \"result\": \"ناجح\", \"attendance\": {\"absent\": 0, \"late\": 0, \"excused\": 0}, \"incomplete\": false, \"issued_at\": \"2026-11-03\"}',76.30,2,'ناجح','2026-11-05 00:14:19',2),
(11,1,10,1,'term','{\"school\": {\"name\": \"مدرسة (تجريبي)\", \"city\": \"\", \"phone\": \"\"}, \"year\": \"السنة التجريبية\", \"term\": \"الفصل الأول\", \"is_final\": false, \"section\": \"الصف الثاني (تجريبي) / أ\", \"student\": {\"name\": \"رنا ياسر الصلوي\", \"no\": \"D0010\"}, \"scheme\": {\"name\": \"النظام الافتراضي\", \"max_score\": 100, \"pass_score\": 50}, \"subjects\": [{\"name\": \"الرياضيات (تجريبي)\", \"total\": 69.0, \"percent\": 69.0, \"letter\": \"جيد\", \"passed\": true}, {\"name\": \"اللغة العربية (تجريبي)\", \"total\": 65.8, \"percent\": 65.8, \"letter\": \"جيد\", \"passed\": true}, {\"name\": \"العلوم (تجريبي)\", \"total\": 82.8, \"percent\": 82.8, \"letter\": \"جيد جدًا\", \"passed\": true}, {\"name\": \"اللغة الإنجليزية (تجريبي)\", \"total\": 82.3, \"percent\": 82.3, \"letter\": \"جيد جدًا\", \"passed\": true}], \"average\": 74.97, \"letter\": \"جيد\", \"rank\": 3, \"class_size\": 4, \"result\": \"ناجح\", \"attendance\": {\"absent\": 0, \"late\": 0, \"excused\": 0}, \"incomplete\": false, \"issued_at\": \"2026-11-03\"}',74.97,3,'ناجح','2026-11-05 00:14:19',2),
(12,1,12,1,'term','{\"school\": {\"name\": \"مدرسة (تجريبي)\", \"city\": \"\", \"phone\": \"\"}, \"year\": \"السنة التجريبية\", \"term\": \"الفصل الأول\", \"is_final\": false, \"section\": \"الصف الثاني (تجريبي) / أ\", \"student\": {\"name\": \"هالة منير الشامي\", \"no\": \"D0012\"}, \"scheme\": {\"name\": \"النظام الافتراضي\", \"max_score\": 100, \"pass_score\": 50}, \"subjects\": [{\"name\": \"الرياضيات (تجريبي)\", \"total\": 67.1, \"percent\": 67.1, \"letter\": \"جيد\", \"passed\": true}, {\"name\": \"اللغة العربية (تجريبي)\", \"total\": 81.3, \"percent\": 81.3, \"letter\": \"جيد جدًا\", \"passed\": true}, {\"name\": \"العلوم (تجريبي)\", \"total\": 75.2, \"percent\": 75.2, \"letter\": \"جيد\", \"passed\": true}, {\"name\": \"اللغة الإنجليزية (تجريبي)\", \"total\": 68.5, \"percent\": 68.5, \"letter\": \"جيد\", \"passed\": true}], \"average\": 73.03, \"letter\": \"جيد\", \"rank\": 4, \"class_size\": 4, \"result\": \"ناجح\", \"attendance\": {\"absent\": 0, \"late\": 0, \"excused\": 0}, \"incomplete\": false, \"issued_at\": \"2026-11-03\"}',73.03,4,'ناجح','2026-11-05 00:14:19',2);
/*!40000 ALTER TABLE `report_cards` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `risk_flags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `risk_flags` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `indicator_code` varchar(50) NOT NULL,
  `explanation` varchar(500) NOT NULL,
  `status` enum('open','reviewed','dismissed','case_opened') NOT NULL DEFAULT 'open',
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `note` varchar(500) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `metric` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `idx_rf` (`school_id`,`student_id`,`indicator_code`,`status`),
  CONSTRAINT `risk_flags_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `risk_flags_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `risk_flags` WRITE;
/*!40000 ALTER TABLE `risk_flags` DISABLE KEYS */;
INSERT INTO `risk_flags` VALUES
(1,1,7,'absence_rate','غاب 6 من 10 أيام مسجّلة (60%) خلال آخر 30 يومًا — الحد 20%','open',NULL,'2026-11-05 00:14:19',NULL,NULL,60.00),
(2,1,7,'low_average','معدل «الفصل الأول» أقل من الحد 50%','reviewed',8,'2026-11-05 00:14:19','تم التواصل مع ولي الأمر','2026-11-04 08:00:00',38.50);
/*!40000 ALTER TABLE `risk_flags` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `risk_indicators`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `risk_indicators` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `code` varchar(50) NOT NULL,
  `label` varchar(150) NOT NULL,
  `threshold` decimal(8,2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ri` (`school_id`,`code`),
  CONSTRAINT `risk_indicators_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `risk_indicators` WRITE;
/*!40000 ALTER TABLE `risk_indicators` DISABLE KEYS */;
INSERT INTO `risk_indicators` VALUES
(1,1,'absence_rate','نسبة الغياب',20.00,1),
(2,1,'consecutive_absence','أيام الغياب المتتالية',3.00,1),
(3,1,'low_average','انخفاض المعدل',50.00,1),
(4,1,'grade_drop','هبوط المعدل بين الفصول',15.00,1),
(5,1,'discipline','عدد المخالفات',3.00,1),
(6,1,'fee_arrears','تأخر الرسوم (أيام)',60.00,1);
/*!40000 ALTER TABLE `risk_indicators` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_permissions` (
  `role_id` int(10) unsigned NOT NULL,
  `permission_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`permission_id`),
  KEY `permission_id` (`permission_id`),
  CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `role_permissions` WRITE;
/*!40000 ALTER TABLE `role_permissions` DISABLE KEYS */;
INSERT INTO `role_permissions` VALUES
(1,2),
(1,3),
(1,5),
(1,11),
(1,12),
(1,13),
(1,14),
(1,15),
(1,16),
(1,17),
(1,18),
(1,19),
(1,20),
(1,21),
(1,22),
(1,23),
(1,24),
(1,25),
(1,26),
(1,27),
(1,28),
(1,29),
(1,30),
(1,31),
(1,32),
(1,33),
(1,34),
(1,35),
(1,36),
(1,37),
(1,38),
(1,39),
(1,40),
(1,41),
(1,42),
(1,44),
(1,45),
(1,46),
(1,47),
(1,48),
(1,50),
(1,51),
(1,52),
(1,53),
(1,54),
(2,2),
(2,3),
(2,13),
(2,15),
(2,18),
(2,19),
(2,20),
(2,21),
(2,22),
(2,23),
(2,26),
(2,27),
(2,28),
(2,29),
(2,31),
(2,32),
(2,34),
(2,36),
(2,37),
(2,38),
(2,44),
(2,46),
(2,47),
(3,18),
(3,21),
(3,29),
(3,32),
(3,37),
(3,46),
(3,51),
(3,52),
(4,18),
(4,37),
(4,38),
(4,39),
(4,40),
(4,41),
(4,42),
(4,46),
(5,2),
(5,15),
(5,18),
(5,27),
(5,29),
(5,30),
(5,32),
(5,33),
(5,44),
(5,45),
(5,46),
(5,47),
(6,1),
(6,44),
(6,46),
(7,1),
(7,46);
/*!40000 ALTER TABLE `role_permissions` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned DEFAULT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(120) NOT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `scope` enum('platform','school') NOT NULL DEFAULT 'school',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_role` (`school_id`,`code`),
  CONSTRAINT `roles_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES
(1,1,'school_admin','مدير المدرسة',1,'school','2026-11-05 00:14:19'),
(2,1,'vice_principal','وكيل المدرسة',1,'school','2026-11-05 00:14:19'),
(3,1,'counselor','المرشد الطلابي',1,'school','2026-11-05 00:14:19'),
(4,1,'accountant','المحاسب',1,'school','2026-11-05 00:14:19'),
(5,1,'teacher','معلم',1,'school','2026-11-05 00:14:19'),
(6,1,'student','طالب',1,'school','2026-11-05 00:14:19'),
(7,1,'guardian','ولي أمر',1,'school','2026-11-05 00:14:19');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `rooms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rooms` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `capacity` smallint(5) unsigned DEFAULT NULL,
  `kind` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_room` (`school_id`,`name`),
  CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `rooms` WRITE;
/*!40000 ALTER TABLE `rooms` DISABLE KEYS */;
INSERT INTO `rooms` VALUES
(1,1,'قاعة 1 (تجريبي)',30,'classroom'),
(2,1,'قاعة 2 (تجريبي)',30,'classroom'),
(3,1,'مختبر العلوم (تجريبي)',25,'lab');
/*!40000 ALTER TABLE `rooms` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `school_backups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `school_backups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `filename` varchar(120) NOT NULL,
  `size_bytes` bigint(20) unsigned NOT NULL DEFAULT 0,
  `kind` enum('manual','auto') NOT NULL DEFAULT 'manual',
  `encrypted` tinyint(1) NOT NULL DEFAULT 0,
  `includes_files` tinyint(1) NOT NULL DEFAULT 1,
  `row_count` int(10) unsigned NOT NULL DEFAULT 0,
  `app_version` varchar(20) DEFAULT NULL,
  `cloud_status` enum('none','ok','failed') NOT NULL DEFAULT 'none',
  `cloud_error` varchar(255) DEFAULT NULL,
  `cloud_at` datetime DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`,`created_at`),
  CONSTRAINT `school_backups_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `school_backups` WRITE;
/*!40000 ALTER TABLE `school_backups` DISABLE KEYS */;
/*!40000 ALTER TABLE `school_backups` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `school_modules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `school_modules` (
  `school_id` int(10) unsigned NOT NULL,
  `module` varchar(50) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`school_id`,`module`),
  CONSTRAINT `school_modules_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `school_modules` WRITE;
/*!40000 ALTER TABLE `school_modules` DISABLE KEYS */;
INSERT INTO `school_modules` VALUES
(1,'comms',1),
(1,'core',1),
(1,'extended',1),
(1,'fees',1),
(1,'lms',1);
/*!40000 ALTER TABLE `school_modules` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `school_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `school_requests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `school_name` varchar(200) NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `governorate` varchar(100) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `gender_policy` enum('mixed','boys','girls') NOT NULL DEFAULT 'mixed',
  `stages` varchar(200) DEFAULT NULL,
  `students_estimate` int(10) unsigned DEFAULT NULL,
  `contact_name` varchar(200) NOT NULL,
  `contact_email` varchar(190) NOT NULL,
  `contact_phone` varchar(30) DEFAULT NULL,
  `notes` varchar(1000) DEFAULT NULL,
  `logo_file` varchar(60) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `decided_by` bigint(20) unsigned DEFAULT NULL,
  `decided_at` datetime DEFAULT NULL,
  `decision_note` varchar(500) DEFAULT NULL,
  `school_id` int(10) unsigned DEFAULT NULL,
  `plan_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`,`created_at`),
  KEY `contact_email` (`contact_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `school_requests` WRITE;
/*!40000 ALTER TABLE `school_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `school_requests` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `school_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `school_subscriptions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `plan_id` int(10) unsigned NOT NULL,
  `starts_on` date NOT NULL,
  `ends_on` date DEFAULT NULL,
  `status` enum('active','expired','cancelled') NOT NULL DEFAULT 'active',
  `amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `plan_id` (`plan_id`),
  KEY `school_id` (`school_id`,`status`),
  CONSTRAINT `school_subscriptions_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `school_subscriptions_ibfk_2` FOREIGN KEY (`plan_id`) REFERENCES `subscription_plans` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `school_subscriptions` WRITE;
/*!40000 ALTER TABLE `school_subscriptions` DISABLE KEYS */;
INSERT INTO `school_subscriptions` VALUES
(1,1,1,'2026-11-05',NULL,'active',0.00,NULL,'2026-11-05 00:14:19');
/*!40000 ALTER TABLE `school_subscriptions` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `schools`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `schools` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) NOT NULL,
  `name` varchar(200) NOT NULL,
  `name_en` varchar(200) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `governorate` varchar(100) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(190) DEFAULT NULL,
  `logo_file_id` bigint(20) unsigned DEFAULT NULL,
  `gender_policy` enum('mixed','boys','girls') NOT NULL DEFAULT 'mixed',
  `base_currency` char(3) NOT NULL DEFAULT 'YER',
  `timezone` varchar(50) NOT NULL DEFAULT 'Asia/Aden',
  `status` enum('active','suspended','archived') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `schools` WRITE;
/*!40000 ALTER TABLE `schools` DISABLE KEYS */;
INSERT INTO `schools` VALUES
(1,'S6B7B54','مدرسة الأمل التجريبية',NULL,'صنعاء','أمانة العاصمة',NULL,NULL,NULL,NULL,'mixed','YER','Asia/Aden','active','2026-11-05 00:14:19',NULL,NULL);
/*!40000 ALTER TABLE `schools` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `section_subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `section_subjects` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `section_id` int(10) unsigned NOT NULL,
  `subject_id` int(10) unsigned NOT NULL,
  `term_id` int(10) unsigned DEFAULT NULL,
  `weekly_periods` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `max_score` decimal(6,2) DEFAULT NULL,
  `credit_hours` decimal(4,1) NOT NULL DEFAULT 1.0,
  `is_elective` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ss` (`section_id`,`subject_id`),
  KEY `school_id` (`school_id`),
  KEY `subject_id` (`subject_id`),
  KEY `fk_ss_term` (`term_id`),
  CONSTRAINT `fk_ss_term` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`),
  CONSTRAINT `section_subjects_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `section_subjects_ibfk_2` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`),
  CONSTRAINT `section_subjects_ibfk_3` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `section_subjects` WRITE;
/*!40000 ALTER TABLE `section_subjects` DISABLE KEYS */;
INSERT INTO `section_subjects` VALUES
(1,1,1,1,NULL,5,100.00,1.0,0),
(2,1,1,2,NULL,5,100.00,1.0,0),
(3,1,1,3,NULL,3,100.00,1.0,0),
(4,1,1,4,NULL,3,100.00,1.0,0),
(5,1,2,1,NULL,5,100.00,1.0,0),
(6,1,2,2,NULL,5,100.00,1.0,0),
(7,1,2,3,NULL,3,100.00,1.0,0),
(8,1,2,4,NULL,3,100.00,1.0,0),
(9,1,3,1,NULL,5,100.00,1.0,0),
(10,1,3,2,NULL,5,100.00,1.0,0),
(11,1,3,3,NULL,3,100.00,1.0,0),
(12,1,3,4,NULL,3,100.00,1.0,0);
/*!40000 ALTER TABLE `section_subjects` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `sections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sections` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `academic_year_id` int(10) unsigned NOT NULL,
  `grade_level_id` int(10) unsigned NOT NULL,
  `name` varchar(60) NOT NULL,
  `gender` enum('mixed','boys','girls') NOT NULL DEFAULT 'mixed',
  `capacity` smallint(5) unsigned DEFAULT NULL,
  `homeroom_staff_id` int(10) unsigned DEFAULT NULL,
  `room_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_section` (`academic_year_id`,`grade_level_id`,`name`),
  KEY `school_id` (`school_id`),
  KEY `grade_level_id` (`grade_level_id`),
  KEY `room_id` (`room_id`),
  KEY `homeroom_staff_id` (`homeroom_staff_id`),
  CONSTRAINT `sections_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `sections_ibfk_2` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`),
  CONSTRAINT `sections_ibfk_3` FOREIGN KEY (`grade_level_id`) REFERENCES `grade_levels` (`id`),
  CONSTRAINT `sections_ibfk_4` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`),
  CONSTRAINT `sections_ibfk_5` FOREIGN KEY (`homeroom_staff_id`) REFERENCES `staff` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `sections` WRITE;
/*!40000 ALTER TABLE `sections` DISABLE KEYS */;
INSERT INTO `sections` VALUES
(1,1,1,1,'أ','mixed',30,1,1),
(2,1,1,1,'ب','mixed',30,2,2),
(3,1,1,2,'أ','mixed',30,3,1);
/*!40000 ALTER TABLE `sections` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `security_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `security_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `school_id` int(10) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `user_type` varchar(20) DEFAULT NULL,
  `type` varchar(30) NOT NULL,
  `status` smallint(5) unsigned DEFAULT NULL,
  `method` varchar(8) DEFAULT NULL,
  `path` varchar(255) DEFAULT NULL,
  `perm` varchar(80) DEFAULT NULL,
  `detail` varchar(255) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `ua` varchar(160) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `created_at` (`created_at`),
  KEY `school_id` (`school_id`,`created_at`),
  KEY `type` (`type`,`created_at`),
  KEY `user_id` (`user_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `security_events` WRITE;
/*!40000 ALTER TABLE `security_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `security_events` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned DEFAULT NULL,
  `skey` varchar(100) NOT NULL,
  `svalue` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_setting` (`school_id`,`skey`),
  CONSTRAINT `settings_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES
(1,1,'fees.invoice_prefix','INV'),
(2,1,'fees.receipt_prefix','RC');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `staff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `employee_no` varchar(30) DEFAULT NULL,
  `full_name` varchar(200) NOT NULL,
  `gender` enum('male','female') DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `national_id_enc` text DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(190) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `job_title` varchar(100) DEFAULT NULL,
  `specialization` varchar(150) DEFAULT NULL,
  `is_teacher` tinyint(1) NOT NULL DEFAULT 0,
  `hire_date` date DEFAULT NULL,
  `status` enum('active','on_leave','terminated') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_emp` (`school_id`,`employee_no`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `staff_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `staff_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `staff` WRITE;
/*!40000 ALTER TABLE `staff` DISABLE KEYS */;
INSERT INTO `staff` VALUES
(1,1,3,'EMP-001','خالد أحمد المقطري','male','1985-01-15',NULL,'+967771000001','khaled.demo@school.test',NULL,'معلم رياضيات','الرياضيات',1,'2024-11-05','active','2026-11-05 00:14:19',NULL,NULL),
(2,1,4,'EMP-002','سلمى علي الحداد','female','1985-02-15',NULL,'+967771000002','salma.demo@school.test',NULL,'معلمة لغة عربية','اللغة العربية',1,'2024-10-06','active','2026-11-05 00:14:19',NULL,NULL),
(3,1,5,'EMP-003','محمد ناصر العولقي','male','1985-03-15',NULL,'+967771000003','mohammed.demo@school.test',NULL,'معلم علوم','العلوم',1,'2024-09-06','active','2026-11-05 00:14:19',NULL,NULL),
(4,1,6,'EMP-004','هدى صالح بن سالم','female','1985-04-15',NULL,'+967771000004','huda.demo@school.test',NULL,'معلمة لغة إنجليزية','اللغة الإنجليزية',1,'2024-08-07','active','2026-11-05 00:14:19',NULL,NULL),
(5,1,7,'EMP-005','عبدالله يحيى الشرجبي','male','1985-05-15',NULL,'+967771000005','acc.demo@school.test',NULL,'محاسب','المحاسبة',0,'2024-07-08','active','2026-11-05 00:14:19',NULL,NULL),
(6,1,8,'EMP-006','ريم عبدالله القدسي','female','1985-06-15',NULL,'+967771000006','counselor.demo@school.test',NULL,'مرشدة طلابية','الإرشاد النفسي',0,'2024-06-08','active','2026-11-05 00:14:19',NULL,NULL);
/*!40000 ALTER TABLE `staff` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `staff_attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff_attendance` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `staff_id` int(10) unsigned NOT NULL,
  `att_date` date NOT NULL,
  `status` enum('present','absent','late','excused') NOT NULL,
  `check_in` time DEFAULT NULL,
  `check_out` time DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `recorded_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sa` (`staff_id`,`att_date`),
  KEY `school_id` (`school_id`),
  CONSTRAINT `staff_attendance_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `staff_attendance_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `staff_attendance` WRITE;
/*!40000 ALTER TABLE `staff_attendance` DISABLE KEYS */;
INSERT INTO `staff_attendance` VALUES
(1,1,1,'2026-11-04','present','07:30:00','13:30:00',NULL,2),
(2,1,2,'2026-11-04','present','07:30:00','13:30:00',NULL,2),
(3,1,3,'2026-11-04','late','07:30:00','13:30:00',NULL,2),
(4,1,4,'2026-11-04','present','07:30:00','13:30:00',NULL,2),
(5,1,5,'2026-11-04','present','07:30:00','13:30:00',NULL,2),
(6,1,6,'2026-11-04','present','07:30:00','13:30:00',NULL,2),
(7,1,1,'2026-11-03','present','07:30:00','13:30:00',NULL,2),
(8,1,2,'2026-11-03','present','07:30:00','13:30:00',NULL,2),
(9,1,3,'2026-11-03','late','07:30:00','13:30:00',NULL,2),
(10,1,4,'2026-11-03','present','07:30:00','13:30:00',NULL,2),
(11,1,5,'2026-11-03','present','07:30:00','13:30:00',NULL,2),
(12,1,6,'2026-11-03','present','07:30:00','13:30:00',NULL,2),
(13,1,1,'2026-11-02','present','07:30:00','13:30:00',NULL,2),
(14,1,2,'2026-11-02','present','07:30:00','13:30:00',NULL,2),
(15,1,3,'2026-11-02','late','07:30:00','13:30:00',NULL,2),
(16,1,4,'2026-11-02','present','07:30:00','13:30:00',NULL,2),
(17,1,5,'2026-11-02','present','07:30:00','13:30:00',NULL,2),
(18,1,6,'2026-11-02','present','07:30:00','13:30:00',NULL,2);
/*!40000 ALTER TABLE `staff_attendance` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `staff_qualifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff_qualifications` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `staff_id` int(10) unsigned NOT NULL,
  `degree` varchar(100) NOT NULL,
  `major` varchar(150) DEFAULT NULL,
  `institution` varchar(200) DEFAULT NULL,
  `year` smallint(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`),
  KEY `staff_id` (`staff_id`),
  CONSTRAINT `staff_qualifications_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `staff_qualifications_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `staff_qualifications` WRITE;
/*!40000 ALTER TABLE `staff_qualifications` DISABLE KEYS */;
INSERT INTO `staff_qualifications` VALUES
(1,1,1,'بكالوريوس','رياضيات','جامعة صنعاء',2008),
(2,1,2,'بكالوريوس','لغة عربية','جامعة عدن',2010),
(3,1,6,'ماجستير','علم النفس','جامعة صنعاء',2015);
/*!40000 ALTER TABLE `staff_qualifications` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `stages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `sort_order` smallint(6) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_stage` (`school_id`,`name`),
  CONSTRAINT `stages_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `stages` WRITE;
/*!40000 ALTER TABLE `stages` DISABLE KEYS */;
INSERT INTO `stages` VALUES
(1,1,'المرحلة الأساسية (تجريبي)',1);
/*!40000 ALTER TABLE `stages` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `student_electives`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_electives` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `enrollment_id` bigint(20) unsigned NOT NULL,
  `section_subject_id` int(10) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_el` (`enrollment_id`,`section_subject_id`),
  KEY `section_subject_id` (`section_subject_id`),
  KEY `school_id` (`school_id`),
  CONSTRAINT `student_electives_ibfk_1` FOREIGN KEY (`enrollment_id`) REFERENCES `student_enrollments` (`id`),
  CONSTRAINT `student_electives_ibfk_2` FOREIGN KEY (`section_subject_id`) REFERENCES `section_subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `student_electives` WRITE;
/*!40000 ALTER TABLE `student_electives` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_electives` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `student_enrollments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_enrollments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `academic_year_id` int(10) unsigned NOT NULL,
  `section_id` int(10) unsigned NOT NULL,
  `roll_no` smallint(5) unsigned DEFAULT NULL,
  `status` enum('active','promoted','repeated','withdrawn','transferred') NOT NULL DEFAULT 'active',
  `enrolled_on` date DEFAULT NULL,
  `left_on` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_enr` (`student_id`,`academic_year_id`),
  KEY `school_id` (`school_id`,`section_id`),
  KEY `academic_year_id` (`academic_year_id`),
  KEY `section_id` (`section_id`),
  CONSTRAINT `student_enrollments_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `student_enrollments_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  CONSTRAINT `student_enrollments_ibfk_3` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`),
  CONSTRAINT `student_enrollments_ibfk_4` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `student_enrollments` WRITE;
/*!40000 ALTER TABLE `student_enrollments` DISABLE KEYS */;
INSERT INTO `student_enrollments` VALUES
(1,1,1,1,1,1,'active','2026-08-07',NULL),
(2,1,2,1,1,2,'active','2026-08-07',NULL),
(3,1,3,1,1,3,'active','2026-08-07',NULL),
(4,1,4,1,1,4,'active','2026-08-07',NULL),
(5,1,5,1,2,1,'active','2026-08-07',NULL),
(6,1,6,1,2,2,'active','2026-08-07',NULL),
(7,1,7,1,2,3,'active','2026-08-07',NULL),
(8,1,8,1,2,4,'active','2026-08-07',NULL),
(9,1,9,1,3,1,'active','2026-08-07',NULL),
(10,1,10,1,3,2,'active','2026-08-07',NULL),
(11,1,11,1,3,3,'active','2026-08-07',NULL),
(12,1,12,1,3,4,'active','2026-08-07',NULL);
/*!40000 ALTER TABLE `student_enrollments` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `student_fees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_fees` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `enrollment_id` bigint(20) unsigned NOT NULL,
  `structure_id` int(10) unsigned NOT NULL,
  `currency` char(3) NOT NULL,
  `gross` decimal(14,2) NOT NULL,
  `discount_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `net` decimal(14,2) NOT NULL,
  `discount_id` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `tax_percent` decimal(5,2) DEFAULT NULL,
  `tax_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `payable` decimal(14,2) NOT NULL DEFAULT 0.00,
  `status` enum('active','cancelled') NOT NULL DEFAULT 'active',
  `cancel_reason` varchar(255) DEFAULT NULL,
  `discount_reason` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sf` (`enrollment_id`,`structure_id`),
  KEY `school_id` (`school_id`),
  KEY `structure_id` (`structure_id`),
  CONSTRAINT `student_fees_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `student_fees_ibfk_2` FOREIGN KEY (`enrollment_id`) REFERENCES `student_enrollments` (`id`),
  CONSTRAINT `student_fees_ibfk_3` FOREIGN KEY (`structure_id`) REFERENCES `fee_structures` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `student_fees` WRITE;
/*!40000 ALTER TABLE `student_fees` DISABLE KEYS */;
INSERT INTO `student_fees` VALUES
(1,1,1,1,'YER',300000.00,0.00,300000.00,NULL,'2026-11-05 00:14:19',NULL,0.00,300000.00,'active',NULL,NULL),
(2,1,2,1,'YER',300000.00,30000.00,270000.00,1,'2026-11-05 00:14:19',NULL,0.00,270000.00,'active',NULL,'خصم الأخوة'),
(3,1,3,1,'YER',300000.00,0.00,300000.00,NULL,'2026-11-05 00:14:19',NULL,0.00,300000.00,'active',NULL,NULL),
(4,1,4,1,'YER',300000.00,0.00,300000.00,NULL,'2026-11-05 00:14:19',NULL,0.00,300000.00,'active',NULL,NULL),
(5,1,5,1,'YER',300000.00,0.00,300000.00,NULL,'2026-11-05 00:14:19',NULL,0.00,300000.00,'active',NULL,NULL),
(6,1,6,1,'YER',300000.00,0.00,300000.00,NULL,'2026-11-05 00:14:19',NULL,0.00,300000.00,'active',NULL,NULL),
(7,1,7,1,'YER',300000.00,0.00,300000.00,NULL,'2026-11-05 00:14:19',NULL,0.00,300000.00,'active',NULL,NULL),
(8,1,8,1,'YER',300000.00,0.00,300000.00,NULL,'2026-11-05 00:14:19',NULL,0.00,300000.00,'active',NULL,NULL),
(9,1,9,2,'YER',360000.00,0.00,360000.00,NULL,'2026-11-05 00:14:19',NULL,0.00,360000.00,'active',NULL,NULL),
(10,1,10,2,'YER',360000.00,0.00,360000.00,NULL,'2026-11-05 00:14:19',NULL,0.00,360000.00,'active',NULL,NULL),
(11,1,11,2,'YER',360000.00,0.00,360000.00,NULL,'2026-11-05 00:14:19',NULL,0.00,360000.00,'active',NULL,NULL),
(12,1,12,2,'YER',360000.00,0.00,360000.00,NULL,'2026-11-05 00:14:19',NULL,0.00,360000.00,'active',NULL,NULL);
/*!40000 ALTER TABLE `student_fees` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `student_guardians`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_guardians` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `guardian_id` bigint(20) unsigned NOT NULL,
  `relation` enum('father','mother','brother','sister','uncle','aunt','grandfather','grandmother','other') NOT NULL DEFAULT 'father',
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `can_view` tinyint(1) NOT NULL DEFAULT 1,
  `can_pickup` tinyint(1) NOT NULL DEFAULT 1,
  `restriction_note` varchar(255) DEFAULT NULL,
  `financially_responsible` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sg` (`student_id`,`guardian_id`),
  KEY `school_id` (`school_id`),
  KEY `guardian_id` (`guardian_id`),
  CONSTRAINT `student_guardians_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `student_guardians_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  CONSTRAINT `student_guardians_ibfk_3` FOREIGN KEY (`guardian_id`) REFERENCES `guardians` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `student_guardians` WRITE;
/*!40000 ALTER TABLE `student_guardians` DISABLE KEYS */;
INSERT INTO `student_guardians` VALUES
(1,1,1,1,'father',1,1,1,NULL,1),
(2,1,2,1,'father',1,1,1,NULL,1),
(3,1,3,2,'father',1,1,1,NULL,1),
(4,1,4,3,'father',1,1,1,NULL,1),
(5,1,5,4,'father',1,1,1,NULL,1),
(6,1,6,4,'father',1,1,1,NULL,1),
(7,1,7,5,'father',1,1,1,NULL,1),
(8,1,8,6,'father',1,1,1,NULL,1),
(9,1,9,7,'father',1,1,1,NULL,1),
(10,1,10,8,'father',1,1,1,NULL,1),
(11,1,11,9,'father',1,1,1,NULL,1),
(12,1,12,10,'father',1,1,1,NULL,1);
/*!40000 ALTER TABLE `student_guardians` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `student_identities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_identities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `platform_uid` char(16) NOT NULL,
  `full_name` varchar(200) NOT NULL,
  `birth_date` date DEFAULT NULL,
  `gender` enum('male','female') DEFAULT NULL,
  `national_id_hash` char(64) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `platform_uid` (`platform_uid`),
  KEY `national_id_hash` (`national_id_hash`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `student_identities` WRITE;
/*!40000 ALTER TABLE `student_identities` DISABLE KEYS */;
INSERT INTO `student_identities` VALUES
(1,'DEMO000000000001','يوسف أحمد العمراني','2013-02-11','male',NULL,'2026-11-05 00:14:19'),
(2,'DEMO000000000002','مريم أحمد العمراني','2014-03-12','female',NULL,'2026-11-05 00:14:19'),
(3,'DEMO000000000003','عمر سالم باوزير','2012-04-13','male',NULL,'2026-11-05 00:14:19'),
(4,'DEMO000000000004','نور محمد الحكيمي','2013-05-14','female',NULL,'2026-11-05 00:14:19'),
(5,'DEMO000000000005','أسامة علي الوصابي','2014-06-15','male',NULL,'2026-11-05 00:14:19'),
(6,'DEMO000000000006','لينا علي الوصابي','2012-07-16','female',NULL,'2026-11-05 00:14:19'),
(7,'DEMO000000000007','طارق حسين الأهدل','2013-08-17','male',NULL,'2026-11-05 00:14:19'),
(8,'DEMO000000000008','ديما فهد المنصوب','2014-09-18','female',NULL,'2026-11-05 00:14:19'),
(9,'DEMO000000000009','زيد عبدالرحمن الكبسي','2012-01-10','male',NULL,'2026-11-05 00:14:19'),
(10,'DEMO000000000010','رنا ياسر الصلوي','2013-02-11','female',NULL,'2026-11-05 00:14:19'),
(11,'DEMO000000000011','بسام جمال القاضي','2014-03-12','male',NULL,'2026-11-05 00:14:19'),
(12,'DEMO000000000012','هالة منير الشامي','2012-04-13','female',NULL,'2026-11-05 00:14:19');
/*!40000 ALTER TABLE `student_identities` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `student_status_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_status_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `academic_year_id` int(10) unsigned DEFAULT NULL,
  `from_status` varchar(20) DEFAULT NULL,
  `to_status` varchar(20) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `changed_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `student_status_history_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `student_status_history_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `student_status_history` WRITE;
/*!40000 ALTER TABLE `student_status_history` DISABLE KEYS */;
INSERT INTO `student_status_history` VALUES
(1,1,1,1,NULL,'active','قيد جديد',2,'2026-11-05 00:14:19'),
(2,1,2,1,NULL,'active','قيد جديد',2,'2026-11-05 00:14:19'),
(3,1,3,1,NULL,'active','قيد جديد',2,'2026-11-05 00:14:19');
/*!40000 ALTER TABLE `student_status_history` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `student_transfers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_transfers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `identity_id` bigint(20) unsigned NOT NULL,
  `from_school_id` int(10) unsigned NOT NULL,
  `to_school_id` int(10) unsigned DEFAULT NULL,
  `status` enum('requested','approved','completed','rejected') NOT NULL DEFAULT 'requested',
  `guardian_consent` tinyint(1) NOT NULL DEFAULT 0,
  `summary_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`summary_json`)),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `identity_id` (`identity_id`),
  KEY `from_school_id` (`from_school_id`),
  CONSTRAINT `student_transfers_ibfk_1` FOREIGN KEY (`identity_id`) REFERENCES `student_identities` (`id`),
  CONSTRAINT `student_transfers_ibfk_2` FOREIGN KEY (`from_school_id`) REFERENCES `schools` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `student_transfers` WRITE;
/*!40000 ALTER TABLE `student_transfers` DISABLE KEYS */;
INSERT INTO `student_transfers` VALUES
(1,12,1,NULL,'requested',1,'{\"note\": \"طلب نقل تجريبي\"}','2026-11-05 00:14:19');
/*!40000 ALTER TABLE `student_transfers` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `students` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `identity_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `student_no` varchar(30) NOT NULL,
  `full_name` varchar(200) NOT NULL,
  `gender` enum('male','female') NOT NULL,
  `birth_date` date DEFAULT NULL,
  `birth_place` varchar(120) DEFAULT NULL,
  `nationality` varchar(60) DEFAULT NULL,
  `national_id_enc` text DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `blood_type` varchar(5) DEFAULT NULL,
  `health_notes_enc` text DEFAULT NULL,
  `special_needs` varchar(255) DEFAULT NULL,
  `previous_school` varchar(200) DEFAULT NULL,
  `admission_date` date DEFAULT NULL,
  `photo_file_id` bigint(20) unsigned DEFAULT NULL,
  `status` enum('active','promoted','repeated','withdrawn','transferred','graduated') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sno` (`school_id`,`student_no`),
  KEY `school_id` (`school_id`,`status`),
  KEY `school_id_2` (`school_id`,`full_name`),
  KEY `identity_id` (`identity_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `students_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `students_ibfk_2` FOREIGN KEY (`identity_id`) REFERENCES `student_identities` (`id`),
  CONSTRAINT `students_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `students` WRITE;
/*!40000 ALTER TABLE `students` DISABLE KEYS */;
INSERT INTO `students` VALUES
(1,1,1,19,'D0001','يوسف أحمد العمراني','male','2013-02-11','صنعاء','يمني',NULL,'صنعاء',NULL,NULL,NULL,NULL,NULL,'2026-08-07',NULL,'active','2026-11-05 00:14:19',NULL,NULL),
(2,1,2,20,'D0002','مريم أحمد العمراني','female','2014-03-12','صنعاء','يمني',NULL,'صنعاء',NULL,NULL,NULL,NULL,NULL,'2026-08-07',NULL,'active','2026-11-05 00:14:19',NULL,NULL),
(3,1,3,21,'D0003','عمر سالم باوزير','male','2012-04-13','صنعاء','يمني',NULL,'صنعاء',NULL,NULL,NULL,NULL,NULL,'2026-08-07',NULL,'active','2026-11-05 00:14:19',NULL,NULL),
(4,1,4,22,'D0004','نور محمد الحكيمي','female','2013-05-14','صنعاء','يمني',NULL,'صنعاء',NULL,NULL,NULL,NULL,NULL,'2026-08-07',NULL,'active','2026-11-05 00:14:19',NULL,NULL),
(5,1,5,23,'D0005','أسامة علي الوصابي','male','2014-06-15','صنعاء','يمني',NULL,'صنعاء',NULL,NULL,NULL,NULL,NULL,'2026-08-07',NULL,'active','2026-11-05 00:14:19',NULL,NULL),
(6,1,6,24,'D0006','لينا علي الوصابي','female','2012-07-16','صنعاء','يمني',NULL,'صنعاء',NULL,NULL,NULL,NULL,NULL,'2026-08-07',NULL,'active','2026-11-05 00:14:19',NULL,NULL),
(7,1,7,25,'D0007','طارق حسين الأهدل','male','2013-08-17','صنعاء','يمني',NULL,'صنعاء',NULL,NULL,NULL,NULL,NULL,'2026-08-07',NULL,'active','2026-11-05 00:14:19',NULL,NULL),
(8,1,8,26,'D0008','ديما فهد المنصوب','female','2014-09-18','صنعاء','يمني',NULL,'صنعاء',NULL,NULL,NULL,NULL,NULL,'2026-08-07',NULL,'active','2026-11-05 00:14:19',NULL,NULL),
(9,1,9,27,'D0009','زيد عبدالرحمن الكبسي','male','2012-01-10','صنعاء','يمني',NULL,'صنعاء',NULL,NULL,NULL,NULL,NULL,'2026-08-07',NULL,'active','2026-11-05 00:14:19',NULL,NULL),
(10,1,10,28,'D0010','رنا ياسر الصلوي','female','2013-02-11','صنعاء','يمني',NULL,'صنعاء',NULL,NULL,NULL,NULL,NULL,'2026-08-07',NULL,'active','2026-11-05 00:14:19',NULL,NULL),
(11,1,11,29,'D0011','بسام جمال القاضي','male','2014-03-12','صنعاء','يمني',NULL,'صنعاء',NULL,NULL,NULL,NULL,NULL,'2026-08-07',NULL,'active','2026-11-05 00:14:19',NULL,NULL),
(12,1,12,30,'D0012','هالة منير الشامي','female','2012-04-13','صنعاء','يمني',NULL,'صنعاء',NULL,NULL,NULL,NULL,NULL,'2026-08-07',NULL,'active','2026-11-05 00:14:19',NULL,NULL);
/*!40000 ALTER TABLE `students` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `study_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `study_plans` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `academic_year_id` int(10) unsigned NOT NULL,
  `grade_level_id` int(10) unsigned NOT NULL,
  `subject_id` int(10) unsigned NOT NULL,
  `term_id` int(10) unsigned DEFAULT NULL COMMENT 'NULL = كل فصول السنة',
  `weekly_periods` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `max_score` decimal(6,2) DEFAULT NULL,
  `credit_hours` decimal(4,1) NOT NULL DEFAULT 1.0,
  `is_elective` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` smallint(6) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_plan` (`academic_year_id`,`grade_level_id`,`subject_id`),
  KEY `school_id` (`school_id`),
  KEY `grade_level_id` (`grade_level_id`),
  KEY `subject_id` (`subject_id`),
  KEY `term_id` (`term_id`),
  CONSTRAINT `study_plans_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `study_plans_ibfk_2` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`),
  CONSTRAINT `study_plans_ibfk_3` FOREIGN KEY (`grade_level_id`) REFERENCES `grade_levels` (`id`),
  CONSTRAINT `study_plans_ibfk_4` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`),
  CONSTRAINT `study_plans_ibfk_5` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `study_plans` WRITE;
/*!40000 ALTER TABLE `study_plans` DISABLE KEYS */;
INSERT INTO `study_plans` VALUES
(1,1,1,1,1,NULL,5,100.00,1.0,0,0),
(2,1,1,1,2,NULL,5,100.00,1.0,0,0),
(3,1,1,1,3,NULL,3,100.00,1.0,0,0),
(4,1,1,1,4,NULL,3,100.00,1.0,0,0),
(5,1,1,2,1,NULL,5,100.00,1.0,0,0),
(6,1,1,2,2,NULL,5,100.00,1.0,0,0),
(7,1,1,2,3,NULL,3,100.00,1.0,0,0),
(8,1,1,2,4,NULL,3,100.00,1.0,0,0);
/*!40000 ALTER TABLE `study_plans` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `subject_grade_levels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `subject_grade_levels` (
  `subject_id` int(10) unsigned NOT NULL,
  `grade_level_id` int(10) unsigned NOT NULL,
  `school_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`subject_id`,`grade_level_id`),
  KEY `grade_level_id` (`grade_level_id`),
  KEY `school_id` (`school_id`),
  CONSTRAINT `subject_grade_levels_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `subject_grade_levels_ibfk_2` FOREIGN KEY (`grade_level_id`) REFERENCES `grade_levels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `subject_grade_levels_ibfk_3` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `subject_grade_levels` WRITE;
/*!40000 ALTER TABLE `subject_grade_levels` DISABLE KEYS */;
INSERT INTO `subject_grade_levels` VALUES
(1,1,1),
(1,2,1),
(2,1,1),
(2,2,1),
(3,1,1),
(3,2,1),
(4,1,1),
(4,2,1);
/*!40000 ALTER TABLE `subject_grade_levels` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `subjects` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_subject` (`school_id`,`name`),
  CONSTRAINT `subjects_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `subjects` WRITE;
/*!40000 ALTER TABLE `subjects` DISABLE KEYS */;
INSERT INTO `subjects` VALUES
(1,1,'الرياضيات (تجريبي)','MATH'),
(2,1,'اللغة العربية (تجريبي)','ARAB'),
(3,1,'العلوم (تجريبي)','SCI'),
(4,1,'اللغة الإنجليزية (تجريبي)','ENG');
/*!40000 ALTER TABLE `subjects` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `submissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `assignment_id` bigint(20) unsigned NOT NULL,
  `enrollment_id` bigint(20) unsigned NOT NULL,
  `body` text DEFAULT NULL,
  `file_id` bigint(20) unsigned DEFAULT NULL,
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `score` decimal(6,2) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `graded_by` bigint(20) unsigned DEFAULT NULL,
  `graded_at` datetime DEFAULT NULL,
  `is_late` tinyint(1) NOT NULL DEFAULT 0,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sub` (`assignment_id`,`enrollment_id`),
  KEY `school_id` (`school_id`),
  KEY `enrollment_id` (`enrollment_id`),
  CONSTRAINT `submissions_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `submissions_ibfk_2` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`),
  CONSTRAINT `submissions_ibfk_3` FOREIGN KEY (`enrollment_id`) REFERENCES `student_enrollments` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `submissions` WRITE;
/*!40000 ALTER TABLE `submissions` DISABLE KEYS */;
INSERT INTO `submissions` VALUES
(1,1,1,1,'حللت التمارين كلها.',NULL,'2026-11-04 18:00:00',9.00,'عمل ممتاز',3,'2026-11-05 09:00:00',0,NULL),
(2,1,1,2,'أجبت عن 8 تمارين.',NULL,'2026-11-04 20:00:00',NULL,NULL,NULL,NULL,0,NULL),
(3,1,2,3,'وطني اليمن، بلد الحضارة والتاريخ…',NULL,'2026-11-05 10:00:00',NULL,NULL,NULL,NULL,0,NULL);
/*!40000 ALTER TABLE `submissions` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `subscription_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscription_plans` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `max_students` int(10) unsigned DEFAULT NULL,
  `max_storage_mb` int(10) unsigned DEFAULT NULL,
  `modules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`modules`)),
  `price` decimal(14,2) NOT NULL DEFAULT 0.00,
  `currency` char(3) NOT NULL DEFAULT 'USD',
  `billing_period` enum('monthly','yearly') NOT NULL DEFAULT 'yearly',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `subscription_plans` WRITE;
/*!40000 ALTER TABLE `subscription_plans` DISABLE KEYS */;
INSERT INTO `subscription_plans` VALUES
(1,'الخطة الأساسية',500,2048,'[\"core\",\"fees\",\"lms\",\"comms\",\"extended\"]',0.00,'USD','yearly',1,'2026-11-05 00:14:18');
/*!40000 ALTER TABLE `subscription_plans` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `support_access_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `support_access_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `admin_user_id` bigint(20) unsigned NOT NULL,
  `school_id` int(10) unsigned NOT NULL,
  `reason` varchar(255) NOT NULL,
  `started_at` datetime NOT NULL DEFAULT current_timestamp(),
  `ended_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `admin_user_id` (`admin_user_id`),
  KEY `school_id` (`school_id`),
  CONSTRAINT `support_access_logs_ibfk_1` FOREIGN KEY (`admin_user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `support_access_logs_ibfk_2` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `support_access_logs` WRITE;
/*!40000 ALTER TABLE `support_access_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `support_access_logs` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `tax_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tax_settings` (
  `school_id` int(10) unsigned NOT NULL,
  `tax_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `rate_percent` decimal(5,2) DEFAULT NULL,
  `tax_label` varchar(60) DEFAULT NULL,
  `tax_number` varchar(60) DEFAULT NULL,
  `exempt_note` varchar(255) DEFAULT NULL,
  `prices_include_tax` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`school_id`),
  CONSTRAINT `tax_settings_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `tax_settings` WRITE;
/*!40000 ALTER TABLE `tax_settings` DISABLE KEYS */;
INSERT INTO `tax_settings` VALUES
(1,0,NULL,'ضريبة (غير مفعّلة)',NULL,NULL,0);
/*!40000 ALTER TABLE `tax_settings` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `teacher_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `teacher_assignments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `section_subject_id` int(10) unsigned NOT NULL,
  `staff_id` int(10) unsigned NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ta` (`section_subject_id`,`staff_id`),
  KEY `school_id` (`school_id`),
  KEY `staff_id` (`staff_id`),
  CONSTRAINT `teacher_assignments_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `teacher_assignments_ibfk_2` FOREIGN KEY (`section_subject_id`) REFERENCES `section_subjects` (`id`),
  CONSTRAINT `teacher_assignments_ibfk_3` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `teacher_assignments` WRITE;
/*!40000 ALTER TABLE `teacher_assignments` DISABLE KEYS */;
INSERT INTO `teacher_assignments` VALUES
(1,1,1,1,1),
(2,1,2,2,1),
(3,1,3,3,1),
(4,1,4,4,1),
(5,1,5,1,1),
(6,1,6,2,1),
(7,1,7,3,1),
(8,1,8,4,1),
(9,1,9,1,1),
(10,1,10,2,1),
(11,1,11,3,1),
(12,1,12,4,1);
/*!40000 ALTER TABLE `teacher_assignments` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `terms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `terms` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `academic_year_id` int(10) unsigned NOT NULL,
  `name` varchar(60) NOT NULL,
  `term_no` tinyint(3) unsigned NOT NULL,
  `starts_on` date NOT NULL,
  `ends_on` date NOT NULL,
  `is_final` tinyint(1) NOT NULL DEFAULT 0,
  `grades_locked` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_term` (`academic_year_id`,`term_no`),
  KEY `school_id` (`school_id`),
  CONSTRAINT `terms_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `terms_ibfk_2` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `terms` WRITE;
/*!40000 ALTER TABLE `terms` DISABLE KEYS */;
INSERT INTO `terms` VALUES
(1,1,1,'الفصل الأول',1,'2026-07-28','2026-12-15',0,0),
(2,1,1,'الفصل الثاني',2,'2026-12-16','2027-05-24',1,0);
/*!40000 ALTER TABLE `terms` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `timetable_slots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `timetable_slots` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned NOT NULL,
  `academic_year_id` int(10) unsigned NOT NULL,
  `section_subject_id` int(10) unsigned NOT NULL,
  `section_id` int(10) unsigned NOT NULL,
  `staff_id` int(10) unsigned NOT NULL,
  `room_id` int(10) unsigned DEFAULT NULL,
  `day_of_week` tinyint(3) unsigned NOT NULL,
  `period_no` tinyint(3) unsigned NOT NULL,
  `starts_at` time DEFAULT NULL,
  `ends_at` time DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_t_section` (`academic_year_id`,`section_id`,`day_of_week`,`period_no`),
  UNIQUE KEY `uq_t_teacher` (`academic_year_id`,`staff_id`,`day_of_week`,`period_no`),
  UNIQUE KEY `uq_t_room` (`academic_year_id`,`room_id`,`day_of_week`,`period_no`),
  KEY `school_id` (`school_id`),
  KEY `section_subject_id` (`section_subject_id`),
  KEY `section_id` (`section_id`),
  KEY `staff_id` (`staff_id`),
  KEY `room_id` (`room_id`),
  CONSTRAINT `timetable_slots_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `timetable_slots_ibfk_2` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`),
  CONSTRAINT `timetable_slots_ibfk_3` FOREIGN KEY (`section_subject_id`) REFERENCES `section_subjects` (`id`),
  CONSTRAINT `timetable_slots_ibfk_4` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`),
  CONSTRAINT `timetable_slots_ibfk_5` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`),
  CONSTRAINT `timetable_slots_ibfk_6` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `timetable_slots` WRITE;
/*!40000 ALTER TABLE `timetable_slots` DISABLE KEYS */;
INSERT INTO `timetable_slots` VALUES
(1,1,1,1,1,1,1,0,1,'07:30:00','08:15:00'),
(2,1,1,2,1,2,1,0,2,'08:20:00','09:05:00'),
(3,1,1,3,1,3,1,0,3,'09:25:00','10:10:00'),
(4,1,1,4,1,4,1,0,4,'10:15:00','11:00:00'),
(5,1,1,2,1,2,1,1,1,'07:30:00','08:15:00'),
(6,1,1,3,1,3,1,1,2,'08:20:00','09:05:00'),
(7,1,1,4,1,4,1,1,3,'09:25:00','10:10:00'),
(8,1,1,1,1,1,1,1,4,'10:15:00','11:00:00'),
(9,1,1,3,1,3,1,2,1,'07:30:00','08:15:00'),
(10,1,1,4,1,4,1,2,2,'08:20:00','09:05:00'),
(11,1,1,1,1,1,1,2,3,'09:25:00','10:10:00'),
(12,1,1,2,1,2,1,2,4,'10:15:00','11:00:00'),
(13,1,1,4,1,4,1,3,1,'07:30:00','08:15:00'),
(14,1,1,1,1,1,1,3,2,'08:20:00','09:05:00'),
(15,1,1,2,1,2,1,3,3,'09:25:00','10:10:00'),
(16,1,1,3,1,3,1,3,4,'10:15:00','11:00:00'),
(17,1,1,1,1,1,1,4,1,'07:30:00','08:15:00'),
(18,1,1,2,1,2,1,4,2,'08:20:00','09:05:00'),
(19,1,1,3,1,3,1,4,3,'09:25:00','10:10:00'),
(20,1,1,4,1,4,1,4,4,'10:15:00','11:00:00'),
(21,1,1,6,2,2,2,0,1,'07:30:00','08:15:00'),
(22,1,1,7,2,3,2,0,2,'08:20:00','09:05:00'),
(23,1,1,8,2,4,2,0,3,'09:25:00','10:10:00'),
(24,1,1,5,2,1,2,0,4,'10:15:00','11:00:00'),
(25,1,1,7,2,3,2,1,1,'07:30:00','08:15:00'),
(26,1,1,8,2,4,2,1,2,'08:20:00','09:05:00'),
(27,1,1,5,2,1,2,1,3,'09:25:00','10:10:00'),
(28,1,1,6,2,2,2,1,4,'10:15:00','11:00:00'),
(29,1,1,8,2,4,2,2,1,'07:30:00','08:15:00'),
(30,1,1,5,2,1,2,2,2,'08:20:00','09:05:00'),
(31,1,1,6,2,2,2,2,3,'09:25:00','10:10:00'),
(32,1,1,7,2,3,2,2,4,'10:15:00','11:00:00'),
(33,1,1,5,2,1,2,3,1,'07:30:00','08:15:00'),
(34,1,1,6,2,2,2,3,2,'08:20:00','09:05:00'),
(35,1,1,7,2,3,2,3,3,'09:25:00','10:10:00'),
(36,1,1,8,2,4,2,3,4,'10:15:00','11:00:00'),
(37,1,1,6,2,2,2,4,1,'07:30:00','08:15:00'),
(38,1,1,7,2,3,2,4,2,'08:20:00','09:05:00'),
(39,1,1,8,2,4,2,4,3,'09:25:00','10:10:00'),
(40,1,1,5,2,1,2,4,4,'10:15:00','11:00:00'),
(41,1,1,11,3,3,NULL,0,1,'07:30:00','08:15:00'),
(42,1,1,12,3,4,NULL,0,2,'08:20:00','09:05:00'),
(43,1,1,9,3,1,NULL,0,3,'09:25:00','10:10:00'),
(44,1,1,10,3,2,NULL,0,4,'10:15:00','11:00:00'),
(45,1,1,12,3,4,NULL,1,1,'07:30:00','08:15:00'),
(46,1,1,9,3,1,NULL,1,2,'08:20:00','09:05:00'),
(47,1,1,10,3,2,NULL,1,3,'09:25:00','10:10:00'),
(48,1,1,11,3,3,NULL,1,4,'10:15:00','11:00:00'),
(49,1,1,9,3,1,NULL,2,1,'07:30:00','08:15:00'),
(50,1,1,10,3,2,NULL,2,2,'08:20:00','09:05:00'),
(51,1,1,11,3,3,NULL,2,3,'09:25:00','10:10:00'),
(52,1,1,12,3,4,NULL,2,4,'10:15:00','11:00:00'),
(53,1,1,10,3,2,NULL,3,1,'07:30:00','08:15:00'),
(54,1,1,11,3,3,NULL,3,2,'08:20:00','09:05:00'),
(55,1,1,12,3,4,NULL,3,3,'09:25:00','10:10:00'),
(56,1,1,9,3,1,NULL,3,4,'10:15:00','11:00:00'),
(57,1,1,11,3,3,NULL,4,1,'07:30:00','08:15:00'),
(58,1,1,12,3,4,NULL,4,2,'08:20:00','09:05:00'),
(59,1,1,9,3,1,NULL,4,3,'09:25:00','10:10:00'),
(60,1,1,10,3,2,NULL,4,4,'10:15:00','11:00:00');
/*!40000 ALTER TABLE `timetable_slots` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `user_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_permissions` (
  `user_id` bigint(20) unsigned NOT NULL,
  `permission_id` int(10) unsigned NOT NULL,
  `school_id` int(10) unsigned NOT NULL,
  `effect` enum('allow','deny') NOT NULL,
  `granted_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`,`permission_id`),
  KEY `school_id` (`school_id`),
  KEY `permission_id` (`permission_id`),
  CONSTRAINT `user_permissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_permissions_ibfk_3` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `user_permissions` WRITE;
/*!40000 ALTER TABLE `user_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_permissions` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `user_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_roles` (
  `user_id` bigint(20) unsigned NOT NULL,
  `role_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`user_id`,`role_id`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `user_roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `user_roles` WRITE;
/*!40000 ALTER TABLE `user_roles` DISABLE KEYS */;
INSERT INTO `user_roles` VALUES
(2,1),
(3,5),
(4,5),
(5,5),
(6,5),
(7,4),
(8,3),
(9,7),
(10,7),
(11,7),
(12,7),
(13,7),
(14,7),
(15,7),
(16,7),
(17,7),
(18,7),
(19,6),
(20,6),
(21,6),
(22,6),
(23,6),
(24,6),
(25,6),
(26,6),
(27,6),
(28,6),
(29,6),
(30,6);
/*!40000 ALTER TABLE `user_roles` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int(10) unsigned DEFAULT NULL,
  `user_type` enum('platform','staff','student','guardian') NOT NULL,
  `full_name` varchar(200) NOT NULL,
  `email` varchar(190) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `username` varchar(60) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `must_change_password` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('active','disabled') NOT NULL DEFAULT 'active',
  `locale` varchar(5) NOT NULL DEFAULT 'ar',
  `theme` enum('light','dark','auto') NOT NULL DEFAULT 'auto',
  `failed_attempts` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email` (`email`),
  UNIQUE KEY `uq_phone` (`phone`),
  UNIQUE KEY `uq_username` (`username`),
  KEY `school_id` (`school_id`,`user_type`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,NULL,'platform','مشغّل المنصة','admin@example.com',NULL,NULL,'$argon2id$v=19$m=65536,t=4,p=1$RW5VaUc2UU1nVEtRRFR2SQ$KSH3G/NcsoAv8jvPnqH+00QmaPhsNGn3dvr8fMvkS/M',0,'active','ar','auto',0,NULL,'2026-11-05 00:14:19','2026-11-05 00:14:18',NULL,NULL),
(2,1,'staff','مدير المدرسة','director@demo.test',NULL,NULL,'$argon2id$v=19$m=65536,t=4,p=1$aklJRnFKUkxjZVBmN1BZQw$NmB35TI7OOgyk6/lkJhxV0jp5OcObZkRdxPkoHhSlHs',0,'active','ar','auto',0,NULL,NULL,'2026-11-05 00:14:19',NULL,NULL),
(3,1,'staff','خالد أحمد المقطري','khaled.demo@school.test','+967771000001',NULL,'$argon2id$v=19$m=65536,t=4,p=1$aklJRnFKUkxjZVBmN1BZQw$NmB35TI7OOgyk6/lkJhxV0jp5OcObZkRdxPkoHhSlHs',0,'active','ar','auto',0,NULL,NULL,'2026-11-05 00:14:19',NULL,NULL),
(4,1,'staff','سلمى علي الحداد','salma.demo@school.test','+967771000002',NULL,'$argon2id$v=19$m=65536,t=4,p=1$aklJRnFKUkxjZVBmN1BZQw$NmB35TI7OOgyk6/lkJhxV0jp5OcObZkRdxPkoHhSlHs',0,'active','ar','auto',0,NULL,NULL,'2026-11-05 00:14:19',NULL,NULL),
(5,1,'staff','محمد ناصر العولقي','mohammed.demo@school.test','+967771000003',NULL,'$argon2id$v=19$m=65536,t=4,p=1$aklJRnFKUkxjZVBmN1BZQw$NmB35TI7OOgyk6/lkJhxV0jp5OcObZkRdxPkoHhSlHs',0,'active','ar','auto',0,NULL,NULL,'2026-11-05 00:14:19',NULL,NULL),
(6,1,'staff','هدى صالح بن سالم','huda.demo@school.test','+967771000004',NULL,'$argon2id$v=19$m=65536,t=4,p=1$aklJRnFKUkxjZVBmN1BZQw$NmB35TI7OOgyk6/lkJhxV0jp5OcObZkRdxPkoHhSlHs',0,'active','ar','auto',0,NULL,NULL,'2026-11-05 00:14:19',NULL,NULL),
(7,1,'staff','عبدالله يحيى الشرجبي','acc.demo@school.test','+967771000005',NULL,'$argon2id$v=19$m=65536,t=4,p=1$aklJRnFKUkxjZVBmN1BZQw$NmB35TI7OOgyk6/lkJhxV0jp5OcObZkRdxPkoHhSlHs',0,'active','ar','auto',0,NULL,NULL,'2026-11-05 00:14:19',NULL,NULL),
(8,1,'staff','ريم عبدالله القدسي','counselor.demo@school.test','+967771000006',NULL,'$argon2id$v=19$m=65536,t=4,p=1$aklJRnFKUkxjZVBmN1BZQw$NmB35TI7OOgyk6/lkJhxV0jp5OcObZkRdxPkoHhSlHs',0,'active','ar','auto',0,NULL,NULL,'2026-11-05 00:14:19',NULL,NULL),
(9,1,'guardian','أحمد صالح العمراني',NULL,'+967773001001',NULL,'$argon2id$v=19$m=65536,t=4,p=1$aklJRnFKUkxjZVBmN1BZQw$NmB35TI7OOgyk6/lkJhxV0jp5OcObZkRdxPkoHhSlHs',0,'active','ar','auto',0,NULL,NULL,'2026-11-05 00:14:19',NULL,NULL),
(10,1,'guardian','سالم عمر باوزير',NULL,'+967773001002',NULL,'$argon2id$v=19$m=65536,t=4,p=1$aklJRnFKUkxjZVBmN1BZQw$NmB35TI7OOgyk6/lkJhxV0jp5OcObZkRdxPkoHhSlHs',0,'active','ar','auto',0,NULL,NULL,'2026-11-05 00:14:19',NULL,NULL),
(11,1,'guardian','محمد قاسم الحكيمي',NULL,'+967773001003',NULL,'$argon2id$v=19$m=65536,t=4,p=1$aklJRnFKUkxjZVBmN1BZQw$NmB35TI7OOgyk6/lkJhxV0jp5OcObZkRdxPkoHhSlHs',0,'active','ar','auto',0,NULL,NULL,'2026-11-05 00:14:19',NULL,NULL),
(12,1,'guardian','علي حمود الوصابي',NULL,'+967773001004',NULL,'$argon2id$v=19$m=65536,t=4,p=1$aklJRnFKUkxjZVBmN1BZQw$NmB35TI7OOgyk6/lkJhxV0jp5OcObZkRdxPkoHhSlHs',0,'active','ar','auto',0,NULL,NULL,'2026-11-05 00:14:19',NULL,NULL),
(13,1,'guardian','حسين طاهر الأهدل',NULL,'+967773001005',NULL,'$argon2id$v=19$m=65536,t=4,p=1$aklJRnFKUkxjZVBmN1BZQw$NmB35TI7OOgyk6/lkJhxV0jp5OcObZkRdxPkoHhSlHs',0,'active','ar','auto',0,NULL,NULL,'2026-11-05 00:14:19',NULL,NULL),
(14,1,'guardian','فهد ناصر المنصوب',NULL,'+967773001006',NULL,'$argon2id$v=19$m=65536,t=4,p=1$aklJRnFKUkxjZVBmN1BZQw$NmB35TI7OOgyk6/lkJhxV0jp5OcObZkRdxPkoHhSlHs',0,'active','ar','auto',0,NULL,NULL,'2026-11-05 00:14:19',NULL,NULL),
(15,1,'guardian','عبدالرحمن خالد الكبسي',NULL,'+967773001007',NULL,'$argon2id$v=19$m=65536,t=4,p=1$aklJRnFKUkxjZVBmN1BZQw$NmB35TI7OOgyk6/lkJhxV0jp5OcObZkRdxPkoHhSlHs',0,'active','ar','auto',0,NULL,NULL,'2026-11-05 00:14:19',NULL,NULL),
(16,1,'guardian','ياسر محمد الصلوي',NULL,'+967773001008',NULL,'$argon2id$v=19$m=65536,t=4,p=1$aklJRnFKUkxjZVBmN1BZQw$NmB35TI7OOgyk6/lkJhxV0jp5OcObZkRdxPkoHhSlHs',0,'active','ar','auto',0,NULL,NULL,'2026-11-05 00:14:19',NULL,NULL),
(17,1,'guardian','جمال أمين القاضي',NULL,'+967773001009',NULL,'$argon2id$v=19$m=65536,t=4,p=1$aklJRnFKUkxjZVBmN1BZQw$NmB35TI7OOgyk6/lkJhxV0jp5OcObZkRdxPkoHhSlHs',0,'active','ar','auto',0,NULL,NULL,'2026-11-05 00:14:19',NULL,NULL),
(18,1,'guardian','منير سعيد الشامي',NULL,'+967773001010',NULL,'$argon2id$v=19$m=65536,t=4,p=1$aklJRnFKUkxjZVBmN1BZQw$NmB35TI7OOgyk6/lkJhxV0jp5OcObZkRdxPkoHhSlHs',0,'active','ar','auto',0,NULL,NULL,'2026-11-05 00:14:19',NULL,NULL),
(19,1,'student','يوسف أحمد العمراني',NULL,NULL,'d0001','$argon2id$v=19$m=65536,t=4,p=1$aklJRnFKUkxjZVBmN1BZQw$NmB35TI7OOgyk6/lkJhxV0jp5OcObZkRdxPkoHhSlHs',0,'active','ar','auto',0,NULL,NULL,'2026-11-05 00:14:19',NULL,NULL),
(20,1,'student','مريم أحمد العمراني',NULL,NULL,'d0002','$argon2id$v=19$m=65536,t=4,p=1$aklJRnFKUkxjZVBmN1BZQw$NmB35TI7OOgyk6/lkJhxV0jp5OcObZkRdxPkoHhSlHs',0,'active','ar','auto',0,NULL,NULL,'2026-11-05 00:14:19',NULL,NULL),
(21,1,'student','عمر سالم باوزير',NULL,NULL,'d0003','$argon2id$v=19$m=65536,t=4,p=1$aklJRnFKUkxjZVBmN1BZQw$NmB35TI7OOgyk6/lkJhxV0jp5OcObZkRdxPkoHhSlHs',0,'active','ar','auto',0,NULL,NULL,'2026-11-05 00:14:19',NULL,NULL),
(22,1,'student','نور محمد الحكيمي',NULL,NULL,'d0004','$argon2id$v=19$m=65536,t=4,p=1$aklJRnFKUkxjZVBmN1BZQw$NmB35TI7OOgyk6/lkJhxV0jp5OcObZkRdxPkoHhSlHs',0,'active','ar','auto',0,NULL,NULL,'2026-11-05 00:14:19',NULL,NULL),
(23,1,'student','أسامة علي الوصابي',NULL,NULL,'d0005','$argon2id$v=19$m=65536,t=4,p=1$aklJRnFKUkxjZVBmN1BZQw$NmB35TI7OOgyk6/lkJhxV0jp5OcObZkRdxPkoHhSlHs',0,'active','ar','auto',0,NULL,NULL,'2026-11-05 00:14:19',NULL,NULL),
(24,1,'student','لينا علي الوصابي',NULL,NULL,'d0006','$argon2id$v=19$m=65536,t=4,p=1$aklJRnFKUkxjZVBmN1BZQw$NmB35TI7OOgyk6/lkJhxV0jp5OcObZkRdxPkoHhSlHs',0,'active','ar','auto',0,NULL,NULL,'2026-11-05 00:14:19',NULL,NULL),
(25,1,'student','طارق حسين الأهدل',NULL,NULL,'d0007','$argon2id$v=19$m=65536,t=4,p=1$aklJRnFKUkxjZVBmN1BZQw$NmB35TI7OOgyk6/lkJhxV0jp5OcObZkRdxPkoHhSlHs',0,'active','ar','auto',0,NULL,NULL,'2026-11-05 00:14:19',NULL,NULL),
(26,1,'student','ديما فهد المنصوب',NULL,NULL,'d0008','$argon2id$v=19$m=65536,t=4,p=1$aklJRnFKUkxjZVBmN1BZQw$NmB35TI7OOgyk6/lkJhxV0jp5OcObZkRdxPkoHhSlHs',0,'active','ar','auto',0,NULL,NULL,'2026-11-05 00:14:19',NULL,NULL),
(27,1,'student','زيد عبدالرحمن الكبسي',NULL,NULL,'d0009','$argon2id$v=19$m=65536,t=4,p=1$aklJRnFKUkxjZVBmN1BZQw$NmB35TI7OOgyk6/lkJhxV0jp5OcObZkRdxPkoHhSlHs',0,'active','ar','auto',0,NULL,NULL,'2026-11-05 00:14:19',NULL,NULL),
(28,1,'student','رنا ياسر الصلوي',NULL,NULL,'d0010','$argon2id$v=19$m=65536,t=4,p=1$aklJRnFKUkxjZVBmN1BZQw$NmB35TI7OOgyk6/lkJhxV0jp5OcObZkRdxPkoHhSlHs',0,'active','ar','auto',0,NULL,NULL,'2026-11-05 00:14:19',NULL,NULL),
(29,1,'student','بسام جمال القاضي',NULL,NULL,'d0011','$argon2id$v=19$m=65536,t=4,p=1$aklJRnFKUkxjZVBmN1BZQw$NmB35TI7OOgyk6/lkJhxV0jp5OcObZkRdxPkoHhSlHs',0,'active','ar','auto',0,NULL,NULL,'2026-11-05 00:14:19',NULL,NULL),
(30,1,'student','هالة منير الشامي',NULL,NULL,'d0012','$argon2id$v=19$m=65536,t=4,p=1$aklJRnFKUkxjZVBmN1BZQw$NmB35TI7OOgyk6/lkJhxV0jp5OcObZkRdxPkoHhSlHs',0,'active','ar','auto',0,NULL,NULL,'2026-11-05 00:14:19',NULL,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

