-- ============================================================
--  TNEB Feedback Management System - Database
-- ============================================================

CREATE DATABASE IF NOT EXISTS `student_feedback_system`
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `student_feedback_system`;

-- Drop old table if exists
DROP TABLE IF EXISTS `feedbacks`;
DROP TABLE IF EXISTS `admin_users`;

-- ---- FEEDBACKS TABLE ----
CREATE TABLE `feedbacks` (
  `id`                    INT(11)       NOT NULL AUTO_INCREMENT,
  `submission_id`         VARCHAR(20)   NOT NULL UNIQUE COMMENT 'Auto-generated unique ID e.g. TNEB2024-00001',
  `student_name`          VARCHAR(150)  NOT NULL,
  `register_number`       VARCHAR(25)   NOT NULL,
  `college_name`          VARCHAR(200)  NOT NULL,
  `department`            VARCHAR(100)  NOT NULL,
  `year`                  VARCHAR(10)   NOT NULL,
  `section`               VARCHAR(5)    NOT NULL,
  `email`                 VARCHAR(150)  NOT NULL,
  `phone`                 VARCHAR(15)   NOT NULL,
  `faculty_name`          VARCHAR(100)  NOT NULL,
  `subject_name`          VARCHAR(100)  NOT NULL,
  `internship_start`      DATE          NOT NULL,
  `internship_end`        DATE          NOT NULL,
  `internship_duration`   INT(5)        NOT NULL COMMENT 'Duration in days',
  `photo_path`            VARCHAR(255)  DEFAULT NULL COMMENT 'Passport photo path',
  `bonafide_path`         VARCHAR(255)  DEFAULT NULL COMMENT 'Bonafide PDF path',
  `teaching_quality`      TINYINT(1)    NOT NULL DEFAULT 0,
  `subject_knowledge`     TINYINT(1)    NOT NULL DEFAULT 0,
  `communication_skills`  TINYINT(1)    NOT NULL DEFAULT 0,
  `doubt_clarification`   TINYINT(1)    NOT NULL DEFAULT 0,
  `classroom_interaction` TINYINT(1)    NOT NULL DEFAULT 0,
  `punctuality`           TINYINT(1)    NOT NULL DEFAULT 0,
  `strengths`             TEXT          DEFAULT NULL,
  `improvements`          TEXT          DEFAULT NULL,
  `feedback`              TEXT          DEFAULT NULL,
  `suggestions`           TEXT          DEFAULT NULL,
  `submitted_at`          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_department`  (`department`),
  KEY `idx_faculty`     (`faculty_name`),
  KEY `idx_subject`     (`subject_name`),
  KEY `idx_submitted`   (`submitted_at`),
  KEY `idx_college`     (`college_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---- ADMIN USERS TABLE ----
CREATE TABLE `admin_users` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `username`   VARCHAR(50)  NOT NULL UNIQUE,
  `password`   VARCHAR(255) NOT NULL,
  `full_name`  VARCHAR(100) NOT NULL,
  `last_login` DATETIME     DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default admin (password = admin123, auto-updated on first login)
INSERT INTO `admin_users` (`username`, `password`, `full_name`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator');

-- ---- SAMPLE DATA ----
INSERT INTO `feedbacks`
  (submission_id, student_name, register_number, college_name, department, year, section,
   email, phone, faculty_name, subject_name,
   internship_start, internship_end, internship_duration,
   photo_path, bonafide_path,
   teaching_quality, subject_knowledge, communication_skills,
   doubt_clarification, classroom_interaction, punctuality,
   strengths, improvements, feedback, suggestions, submitted_at)
VALUES
('TNEB2024-00001','Arun Kumar','22AI001','Sri Venkateswara College of Engineering','AI & Data Science','2nd','A',
 'arun@college.edu','9876543210','Er. Priya Sharma','Power Systems',
 '2024-06-01','2024-07-31',61,NULL,NULL,
 5,5,4,5,4,5,'Excellent at explaining concepts','More practical sessions','Best mentor for internship','More hands-on training','2024-08-01 10:00:00'),

('TNEB2024-00002','Priya Devi','22CS002','PSG College of Technology','Computer Science','3rd','B',
 'priya@college.edu','9876543211','Er. Rajesh Kumar','Electrical Distribution',
 '2024-05-01','2024-06-30',61,NULL,NULL,
 4,5,4,4,5,4,'Deep knowledge','More doubt sessions','Very knowledgeable mentor','Weekend lab access','2024-07-05 11:30:00'),

('TNEB2024-00003','Karthik R','21IT004','Anna University','Information Technology','4th','A',
 'karthik@college.edu','9876543212','Er. Suresh Babu','Substation Operations',
 '2024-07-01','2024-08-31',62,NULL,NULL,
 5,5,5,5,5,5,'Outstanding in every aspect','Nothing to improve','Best internship experience!','Keep it up','2024-09-01 14:00:00');
