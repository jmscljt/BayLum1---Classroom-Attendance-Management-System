-- Cleaned backup for BayLum1 Classroom Attendance Management System
-- Host-specific dump metadata and destructive DROP TABLE statements removed.
-- Existing system and attendance data are preserved.

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;


-- ------------------------------------------------------







CREATE TABLE IF NOT EXISTS `admins` (
  `admin_id` int unsigned NOT NULL,
  `full_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(254) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`admin_id`) /*T![clustered_index] NONCLUSTERED */,
  UNIQUE KEY `uq_admins_username` (`username`),
  UNIQUE KEY `uq_admins_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;




INSERT INTO `admins` VALUES (1,'James Gabriel Calajate','Jaime','jcalajate.2006@gmail.com','$2y$10$9/0mmceOpQrlOebmfvx4me3XcpsL6NlmGXl8E7bJzwtmfgVQeTZ/a');




CREATE TABLE IF NOT EXISTS `attendance_logs` (
  `log_id` int NOT NULL,
  `log_date` date NOT NULL,
  `student_id` bigint NOT NULL,
  `time_in` int DEFAULT NULL,
  `time_out` int DEFAULT NULL,
  `attendance_status` enum('Present','Absent','Excused') COLLATE utf8mb4_general_ci NOT NULL,
  `remarks` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`log_id`) /*T![clustered_index] NONCLUSTERED */,
  KEY `fk_attendance_logs_student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;




INSERT INTO `attendance_logs` VALUES (1,'2026-11-10',107989120052,NULL,NULL,'Excused','Medical appointment'),(2,'2026-11-07',107989120052,NULL,NULL,'Excused','Family emergency'),(3,'2026-11-10',107989120055,NULL,NULL,'Absent','No time-in was recorded before attendance closed.'),(4,'2026-11-11',107989120055,NULL,NULL,'Absent','No time-in was recorded before attendance closed.');












CREATE TABLE IF NOT EXISTS `manual_attendance_records` (
  `id` bigint unsigned NOT NULL,
  `session_id` bigint unsigned NOT NULL,
  `teacher_id` int NOT NULL,
  `section_id` int NOT NULL,
  `student_id` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `attendance_date` date NOT NULL,
  `attendance_type` enum('time_in','time_out') COLLATE utf8mb4_general_ci NOT NULL,
  `recorded_at` datetime NOT NULL,
  `attendance_method` enum('Manual','Fingerprint') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Manual',
  PRIMARY KEY (`id`) /*T![clustered_index] NONCLUSTERED */,
  UNIQUE KEY `uq_manual_attendance_daily` (`teacher_id`,`attendance_date`,`attendance_type`,`student_id`),
  KEY `idx_manual_attendance_teacher_date` (`teacher_id`,`section_id`,`attendance_date`),
  KEY `fk_manual_attendance_session` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;




INSERT INTO `manual_attendance_records` VALUES (1,1,1,1,'107989120053','2026-11-07','time_in','2026-11-07 12:53:25','Manual'),(2,3,1,1,'107989120053','2026-11-07','time_out','2026-11-07 21:06:06','Manual'),(3,4,1,1,'107989120053','2026-11-10','time_in','2026-11-10 19:49:13','Manual'),(4,5,1,1,'107989120053','2026-11-11','time_in','2026-11-11 09:41:46','Manual'),(5,6,1,1,'107989120052','2026-11-11','time_in','2026-11-11 09:42:32','Manual'),(6,7,1,1,'107989120054','2026-11-11','time_in','2026-11-11 09:43:59','Manual'),(7,8,1,1,'107989120056','2026-11-11','time_in','2026-11-11 09:44:31','Manual'),(9,10,1,1,'107989120052','2026-11-14','time_out','2026-11-14 14:35:58','Manual'),(10,15,1,1,'107989120052','2026-11-15','time_in','2026-11-15 20:41:42','Fingerprint'),(12,16,1,1,'107989120052','2026-11-15','time_out','2026-11-15 20:42:59','Fingerprint'),(15,17,1,1,'107989120053','2026-11-15','time_in','2026-11-15 20:48:03','Manual'),(19,20,1,1,'107989120052','2026-11-16','time_in','2026-11-16 17:43:59','Fingerprint'),(20,20,1,1,'107989120055','2026-11-16','time_in','2026-11-16 17:44:33','Fingerprint'),(25,21,1,1,'107989120055','2026-11-16','time_out','2026-11-16 17:57:34','Fingerprint'),(26,21,1,1,'107989120052','2026-11-16','time_out','2026-11-16 17:57:47','Fingerprint'),(27,22,2,2,'107989120030','2026-11-16','time_in','2026-11-16 18:25:13','Fingerprint'),(28,23,1,1,'107989120052','2026-11-18','time_in','2026-11-18 12:30:57','Fingerprint'),(29,23,1,1,'107989120055','2026-11-18','time_in','2026-11-18 12:31:39','Fingerprint'),(30,25,1,1,'107989120057','2026-11-18','time_in','2026-11-18 12:55:01','Fingerprint'),(32,26,1,1,'107989120052','2026-11-20','time_in','2026-11-20 13:37:27','Fingerprint'),(33,27,1,1,'107989120052','2026-11-20','time_out','2026-11-20 13:37:56','Fingerprint'),(34,28,1,1,'107989120052','2026-11-22','time_in','2026-11-22 12:31:57','Manual'),(35,28,1,1,'107989120053','2026-11-22','time_in','2026-11-22 12:32:32','Manual'),(37,29,1,1,'107989120054','2026-11-22','time_in','2026-11-22 12:34:22','Manual'),(38,29,1,1,'107989120055','2026-11-22','time_in','2026-11-22 12:34:29','Manual'),(39,29,1,1,'107989120056','2026-11-22','time_in','2026-11-22 12:34:38','Manual'),(40,29,1,1,'107989120057','2026-11-22','time_in','2026-11-22 12:35:10','Manual'),(41,30,1,1,'107989120052','2026-11-22','time_out','2026-11-22 14:11:22','Manual'),(42,30,1,1,'107989120053','2026-11-22','time_out','2026-11-22 14:11:32','Manual'),(43,30,1,1,'107989120056','2026-11-22','time_out','2026-11-22 14:11:38','Manual'),(44,30,1,1,'107989120057','2026-11-22','time_out','2026-11-22 14:11:45','Manual'),(45,30,1,1,'107989120054','2026-11-22','time_out','2026-11-22 14:11:48','Manual'),(46,30,1,1,'107989120055','2026-11-22','time_out','2026-11-22 14:11:51','Manual');




CREATE TABLE IF NOT EXISTS `manual_attendance_sessions` (
  `id` bigint unsigned NOT NULL,
  `teacher_id` int NOT NULL,
  `section_id` int NOT NULL,
  `attendance_type` enum('time_in','time_out') COLLATE utf8mb4_general_ci NOT NULL,
  `attendance_date` date NOT NULL,
  `opened_at` datetime NOT NULL,
  `closed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) /*T![clustered_index] NONCLUSTERED */,
  KEY `idx_manual_session_teacher_open` (`teacher_id`,`closed_at`),
  KEY `idx_manual_session_date` (`teacher_id`,`section_id`,`attendance_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;




INSERT INTO `manual_attendance_sessions` VALUES (1,1,1,'time_in','2026-11-07','2026-11-07 12:53:08','2026-11-07 12:54:28'),(2,2,2,'time_out','2026-11-07','2026-11-07 14:00:04','2026-11-07 14:00:07'),(3,1,1,'time_out','2026-11-07','2026-11-07 21:05:56','2026-11-07 21:06:14'),(4,1,1,'time_in','2026-11-10','2026-11-10 19:48:55','2026-11-10 19:49:19'),(5,1,1,'time_in','2026-11-11','2026-11-11 09:41:36','2026-11-11 09:41:50'),(6,1,1,'time_in','2026-11-11','2026-11-11 09:42:27','2026-11-11 09:42:36'),(7,1,1,'time_in','2026-11-11','2026-11-11 09:43:55','2026-11-11 09:44:22'),(8,1,1,'time_in','2026-11-11','2026-11-11 09:44:27','2026-11-11 09:44:46'),(9,1,1,'time_out','2026-11-11','2026-11-11 20:31:37','2026-11-11 20:32:20'),(10,1,1,'time_out','2026-11-14','2026-11-14 14:35:17','2026-11-14 14:37:42'),(11,1,1,'time_in','2026-11-15','2026-11-15 16:01:56','2026-11-15 16:20:01'),(12,1,1,'time_in','2026-11-15','2026-11-15 16:20:13','2026-11-15 16:20:20'),(13,1,1,'time_in','2026-11-15','2026-11-15 16:23:40','2026-11-15 16:27:37'),(14,1,1,'time_in','2026-11-15','2026-11-15 20:30:31','2026-11-15 20:41:30'),(15,1,1,'time_in','2026-11-15','2026-11-15 20:41:36','2026-11-15 20:42:32'),(16,1,1,'time_out','2026-11-15','2026-11-15 20:42:51','2026-11-15 20:47:49'),(17,1,1,'time_in','2026-11-15','2026-11-15 20:47:56','2026-11-15 20:48:08'),(18,1,1,'time_in','2026-11-15','2026-11-15 21:47:59','2026-11-16 15:32:24'),(19,1,1,'time_in','2026-11-16','2026-11-16 15:32:28','2026-11-16 15:32:33'),(20,1,1,'time_in','2026-11-16','2026-11-16 17:43:36','2026-11-16 17:57:27'),(21,1,1,'time_out','2026-11-16','2026-11-16 17:57:30','2026-11-18 12:29:59'),(22,2,2,'time_in','2026-11-16','2026-11-16 18:24:56','2026-11-16 18:25:31'),(23,1,1,'time_in','2026-11-18','2026-11-18 12:30:46','2026-11-18 12:50:44'),(24,1,1,'time_in','2026-11-18','2026-11-18 12:50:49','2026-11-18 12:54:29'),(25,1,1,'time_in','2026-11-18','2026-11-18 12:54:32','2026-11-20 13:36:54'),(26,1,1,'time_in','2026-11-20','2026-11-20 13:37:14','2026-11-20 13:37:42'),(27,1,1,'time_out','2026-11-20','2026-11-20 13:37:48','2026-11-22 12:31:49'),(28,1,1,'time_in','2026-11-22','2026-11-22 12:31:49','2026-11-22 12:32:35'),(29,1,1,'time_in','2026-11-22','2026-11-22 12:34:12','2026-11-22 14:11:01'),(30,1,1,'time_out','2026-11-22','2026-11-22 14:11:07','2026-11-22 14:11:56'),(31,1,1,'time_in','2026-11-22','2026-11-22 14:13:57','2026-11-25 19:14:32');




CREATE TABLE IF NOT EXISTS `sections` (
  `section_id` int NOT NULL,
  `grade_level` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `section_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`section_id`) /*T![clustered_index] NONCLUSTERED */
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;




INSERT INTO `sections` VALUES (1,'Grade 4','Mabait'),(2,'Grade 5','Magalang'),(5,'Grade 4','Magalang'),(8,'Grade 4','Masipag'),(9,'Grade 4','Matapat'),(10,'Grade 4','Masayahin'),(11,'Grade 5','Mabait'),(12,'Grade 5','Masipag'),(13,'Grade 5','Masayahin'),(14,'Grade 6','Mabait'),(15,'Grade 6','Magalang'),(16,'Grade 6','Masipag'),(17,'Grade 6','Masayahin');




CREATE TABLE IF NOT EXISTS `students` (
  `status` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `student_id` bigint NOT NULL,
  `first_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `middle_name` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `last_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `suffix` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `gender` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `section_id` int NOT NULL,
  `fingerprint_id` smallint unsigned DEFAULT NULL,
  PRIMARY KEY (`student_id`) /*T![clustered_index] NONCLUSTERED */,
  UNIQUE KEY `uq_students_fingerprint_id` (`fingerprint_id`),
  KEY `idx_students_section_active` (`section_id`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;




INSERT INTO `students` VALUES (NULL,1,107989120030,'Kapten',NULL,'Barbel',NULL,'Male',2,3),(NULL,1,107989120052,'James Gabriel','Villalva','Calajate',NULL,'Male',1,1),(NULL,1,107989120053,'Bat',NULL,'Man',NULL,'Male',1,NULL),(NULL,1,107989120054,'Lastik',NULL,'Man',NULL,'Male',1,NULL),(NULL,1,107989120055,'Cedrick','Destreza','Manzanilla',NULL,'Male',1,4),(NULL,1,107989120056,'Reahlyn','Gultian','Ramos',NULL,'Female',1,5),(NULL,1,107989120057,'Angelica',NULL,'Tomas',NULL,'Female',1,13),(NULL,1,107989130050,'Juan',NULL,'Dela Crush',NULL,'Male',2,NULL),(NULL,1,107989140030,'Pedro',NULL,'Penduko',NULL,'Male',2,NULL);




CREATE TABLE IF NOT EXISTS `teacher_attendance_codes` (
  `teacher_id` int NOT NULL,
  `attendance_type` enum('time_in','time_out') COLLATE utf8mb4_general_ci NOT NULL,
  `code` char(4) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`teacher_id`,`attendance_type`) /*T![clustered_index] NONCLUSTERED */,
  UNIQUE KEY `uq_teacher_attendance_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;




INSERT INTO `teacher_attendance_codes` VALUES (2,'time_in','3624'),(1,'time_out','4154'),(4,'time_out','4269'),(4,'time_in','5785'),(1,'time_in','8652'),(2,'time_out','9056'),(8,'time_in','2073'),(8,'time_out','7526');




CREATE TABLE IF NOT EXISTS `teacher_id_sequence` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`) /*T![clustered_index] CLUSTERED */
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin AUTO_INCREMENT=1437647;




INSERT INTO `teacher_id_sequence` VALUES (5),(6),(7),(8),(30006);




CREATE TABLE IF NOT EXISTS `teachers` (
  `id` int NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `user_id` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `first_name` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `last_name` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `section_id` int NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) /*T![clustered_index] NONCLUSTERED */,
  UNIQUE KEY `uq_teachers_user_id` (`user_id`),
  KEY `fk_teacher_section` (`section_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;




INSERT INTO `teachers` VALUES (1,1,'BL1es-20263098','James Gabriel Calajate','James Gabriel','Calajate',1,'jcalajate.2006@gmail.com','$2y$12$u7kIFhSfrfQo7Fju8vDwZ.gdWBD9.oe9SDbkD.49Nv74HoZijOYIe','2026-11-06 13:32:28'),(2,1,'BL1es-20265616','Cedrick Manzanilla','Cedrick','Manzanilla',2,'cedrickmanzanilla@gmail.com','$2y$10$GmWQFqn4nZKu6zp4YfJRsucX0tp9rAc/WmcbhIigcPfycfbsSiR3q','2026-11-07 02:29:04'),(4,1,'BL1es-20262683','James Bond','James','Bond',5,'bond007@gmail.com','$2y$10$YhW6K6t6Ne/YnkVzIVIlnueW6SGnYMF3OM23VE8qhTjg2rx6i8NFG','2026-11-14 12:47:48'),(6,1,'BL1es-20263238','Marian Rivera','Marian','Rivera',16,'riveramarian@gmail.com','$2y$12$GqmE042Aou2skYgsVWww6enU6pEvxi1VYSEmyoCNzWIoxihsFOBiO','2026-11-25 01:52:28'),(7,1,'BL1es-20261636','Karen Margaren','Karen','Margaren',12,'margaren001@gmail.com','$2y$12$HDcxcwPv.4yJW5iUnsFEBeAWmvI4G/DcblS2O0FZgjCUv42wVUKRe','2026-11-25 01:54:23'),(8,1,'BL1es-20263350','Reahlyn Ramos','Reahlyn','Ramos',17,'eyaramos2@gmail.com','$2y$12$achgMOAi6vFAx..pjwgZ8u.l63d6n4Jmsd2p6weX36Vm2R8ESRn72','2026-11-25 02:10:16'),(30006,1,'BL1es-20263524','Cedrick Manzanilla','Cedrick','Manzanilla',10,'cid@gmail.com','$2y$12$iWviC6S2UVB580lTZ1O4X.W8bxIfzc0DmKkVwesipQw9btBTFGx7K','2026-11-25 10:01:38');





SET FOREIGN_KEY_CHECKS = 1;
