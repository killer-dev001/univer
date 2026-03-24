-- URSS Database — Final Version
-- Passwords encrypted with bcrypt (password_hash PHP)
-- Run this in phpMyAdmin to set up the database

CREATE DATABASE IF NOT EXISTS `urss` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `urss`;

-- USERS TABLE
CREATE TABLE IF NOT EXISTS `users` (
  `Sn`       int(8)      NOT NULL AUTO_INCREMENT,
  `Fullname` varchar(50) NOT NULL,
  `Username` varchar(100) NOT NULL,
  `Password` varchar(255) NOT NULL,  -- bcrypt hash (255 chars needed)
  `Role`     varchar(10) NOT NULL,
  PRIMARY KEY (`Sn`),
  UNIQUE KEY `Username` (`Username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- PAPERS TABLE
CREATE TABLE IF NOT EXISTS `papers` (
  `Sn`          int(8)        NOT NULL AUTO_INCREMENT,
  `Title`       varchar(70)   NOT NULL,
  `Owner`       varchar(70)   NOT NULL,
  `Supervisor`  varchar(70)   NOT NULL,
  `Original`    varchar(120)  NOT NULL,
  `Updated`     varchar(120)  NOT NULL,
  `Percentage`  varchar(10)   NOT NULL DEFAULT 'NA',
  `Keywords`    varchar(255)  NOT NULL DEFAULT '',
  `Abstract`    text          NOT NULL,
  `Lastupdate`  datetime      NOT NULL,
  `Lastcomment` varchar(255)  NOT NULL DEFAULT '',
  `Status`      varchar(15)   NOT NULL DEFAULT 'For Review',
  PRIMARY KEY (`Sn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- SUPERVISES TABLE
CREATE TABLE IF NOT EXISTS `supervises` (
  `Sn`         int(8)      NOT NULL AUTO_INCREMENT,
  `Student`    varchar(70) NOT NULL,
  `Supervisor` varchar(70) NOT NULL,
  `Research`   varchar(70) NOT NULL,
  `Status`     varchar(10) NOT NULL DEFAULT 'Active',
  PRIMARY KEY (`Sn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- DEFAULT USERS
-- Passwords are bcrypt hashed:
-- Admin    password: DDAP
-- Supervisor password: pastor
-- Student1 password: john
-- Student2 password: info
-- ============================================================

INSERT INTO `users` (`Fullname`, `Username`, `Password`, `Role`) VALUES
('Bello Abimbola',  'admin@dap.babcock.edu.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin'),
('Ekeoma Pastor',   'EKEOMA@babcock.edu.ng',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Supervisor'),
('Moses John',      'mosesj@babcock.edu.ng',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Student'),
('Abayomi Ajayi',   'abayomia@gmail.com',       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Student');

-- NOTE: All default passwords above are hashed to 'password'
-- Run this PHP snippet once to generate correct hashes for your passwords:
-- echo password_hash('DDAP',   PASSWORD_BCRYPT);
-- echo password_hash('pastor', PASSWORD_BCRYPT);
-- echo password_hash('john',   PASSWORD_BCRYPT);
-- echo password_hash('info',   PASSWORD_BCRYPT);
-- Then UPDATE users SET Password = '[hash]' WHERE Username = '[email]';

-- SAMPLE PAPERS
INSERT INTO `papers` (`Title`, `Owner`, `Supervisor`, `Original`, `Updated`, `Percentage`, `Keywords`, `Abstract`, `Lastupdate`, `Lastcomment`, `Status`) VALUES
('Natural Language Processing using Transformer', 'Moses John', 'Ekeoma Pastor', 'uploads/nlp.pdf', 'uploads/nlp1.pdf', '56', 'NLP,transformer,AI', 'Research on transformer models for NLP tasks.', '2026-03-21 10:31:09', 'Not too good pls reference your work properly', 'Reviewed'),
('Machine Learning Optimisation', 'Moses John', 'Ekeoma Pastor', 'uploads/ml.pdf', 'uploads/ml.pdf', 'NA', 'ML,optimization,neural networks', 'Study of optimization techniques in machine learning.', '2026-03-21 19:13:53', '', 'For Review');
