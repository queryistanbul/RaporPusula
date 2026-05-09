-- MySQL Administrator dump 1.4
--
-- ------------------------------------------------------
-- Server version	5.5.5-10.4.32-MariaDB


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;


--
-- Create schema a_airapor
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ a_airapor;
USE a_airapor;

--
-- Table structure for table `a_airapor`.`ai_process_logs`
--

DROP TABLE IF EXISTS `ai_process_logs`;
CREATE TABLE `ai_process_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `step_name` varchar(100) NOT NULL,
  `content` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_request` (`request_id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=104 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `a_airapor`.`ai_process_logs`
--

/*!40000 ALTER TABLE `ai_process_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai_process_logs` ENABLE KEYS */;


--
-- Table structure for table `a_airapor`.`auth_app_settings`
--

DROP TABLE IF EXISTS `auth_app_settings`;
CREATE TABLE `auth_app_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `a_airapor`.`auth_app_settings`
--

/*!40000 ALTER TABLE `auth_app_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `auth_app_settings` ENABLE KEYS */;


--
-- Table structure for table `a_airapor`.`auth_user_connections`
--

DROP TABLE IF EXISTS `auth_user_connections`;
CREATE TABLE `auth_user_connections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `connection_name` varchar(200) NOT NULL,
  `db_type` enum('mysql','mssql','postgresql','oracle') NOT NULL DEFAULT 'mysql',
  `host` varchar(255) NOT NULL DEFAULT 'localhost',
  `port` int(11) DEFAULT NULL,
  `db_user` varchar(200) NOT NULL DEFAULT '',
  `db_password_enc` text NOT NULL,
  `db_name` varchar(200) NOT NULL DEFAULT '',
  `business_rules` text DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `auth_user_connections_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `auth_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `a_airapor`.`auth_user_connections`
--

/*!40000 ALTER TABLE `auth_user_connections` DISABLE KEYS */;
/*!40000 ALTER TABLE `auth_user_connections` ENABLE KEYS */;


--
-- Table structure for table `a_airapor`.`auth_users`
--

DROP TABLE IF EXISTS `auth_users`;
CREATE TABLE `auth_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `display_name` varchar(200) NOT NULL DEFAULT '',
  `role` enum('admin','analyst','viewer') NOT NULL DEFAULT 'viewer',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `must_change_password` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `a_airapor`.`auth_users`
--

/*!40000 ALTER TABLE `auth_users` DISABLE KEYS */;
INSERT INTO `auth_users` (`id`,`username`,`password_hash`,`display_name`,`role`,`is_active`,`must_change_password`,`created_at`,`last_login`) VALUES 
 (1,'admin','$2b$12$8yj0M7kuT3wC7mZplwphVuJJL4kE/gc.bHprAdLzlkGkFJM3Cp0va','Sistem Yöneticisi','admin',1,0,'2026-02-13 13:10:27','2026-03-12 23:11:14'),
 (2,'erhan','$2b$12$ooFZeFFhpOrg6xTaMDghQ.SqsjneU9w3irldeeiln.5iC3Ax31EyG','Erhan Gürsoy','viewer',1,0,'2026-02-13 13:31:18','2026-03-11 15:29:59'),
 (3,'gursoy','$2b$12$5xZJcJMSqSvqAeZr0p22xeCxze00rK/rwZoTxoC4dekx9exCHgWNm','gursoy','analyst',1,0,'2026-02-13 14:15:20','2026-02-17 21:35:53'),
 (4,'baki','$2y$10$wYinJ4e9taf7JOTCvnQ9wOUEXzLzb88mdMy4Cu9N13UuFEZ7nzLjm','Baki Koçak','viewer',1,0,'2026-02-17 22:25:57',NULL);
/*!40000 ALTER TABLE `auth_users` ENABLE KEYS */;


--
-- Table structure for table `a_airapor`.`chat_history`
--

DROP TABLE IF EXISTS `chat_history`;
CREATE TABLE `chat_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `request_id` varchar(50) DEFAULT NULL,
  `prompt` text NOT NULL,
  `sql_query` text DEFAULT NULL,
  `response` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_chat_request` (`request_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `a_airapor`.`chat_history`
--

/*!40000 ALTER TABLE `chat_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `chat_history` ENABLE KEYS */;


--
-- Table structure for table `a_airapor`.`dashboard_widgets`
--

DROP TABLE IF EXISTS `dashboard_widgets`;
CREATE TABLE `dashboard_widgets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(300) NOT NULL,
  `question` text NOT NULL,
  `widget_type` enum('kpi','bar','line','pie','table') NOT NULL DEFAULT 'bar',
  `sql_query` text DEFAULT NULL,
  `pos_x` int(11) NOT NULL DEFAULT 0,
  `pos_y` int(11) NOT NULL DEFAULT 0,
  `width` int(11) NOT NULL DEFAULT 6,
  `height` int(11) NOT NULL DEFAULT 4,
  `refresh_minutes` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `dashboard_widgets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `auth_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `a_airapor`.`dashboard_widgets`
--

/*!40000 ALTER TABLE `dashboard_widgets` DISABLE KEYS */;
/*!40000 ALTER TABLE `dashboard_widgets` ENABLE KEYS */;


--
-- Table structure for table `a_airapor`.`viewer_assignments`
--

DROP TABLE IF EXISTS `viewer_assignments`;
CREATE TABLE `viewer_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `viewer_user_id` int(11) NOT NULL,
  `connection_id` int(11) NOT NULL,
  `assigned_by` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_viewer_multi` (`viewer_user_id`,`connection_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `a_airapor`.`viewer_assignments`
--

/*!40000 ALTER TABLE `viewer_assignments` DISABLE KEYS */;
/*!40000 ALTER TABLE `viewer_assignments` ENABLE KEYS */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
