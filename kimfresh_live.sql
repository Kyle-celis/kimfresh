-- MySQL dump 10.13  Distrib 8.0.46, for Linux (x86_64)
--
-- Host: localhost    Database: kimfresh
-- ------------------------------------------------------
-- Server version	8.0.46-0ubuntu0.24.04.4

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_logs` (
  `log_id` int NOT NULL AUTO_INCREMENT,
  `admin_id` int DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `timestamp` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB AUTO_INCREMENT=86 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES (1,1,'Admin logged out','2026-10-22 01:53:45'),(2,1,'Admin logged in','2026-10-22 01:55:47'),(3,1,'Added new product: kyle','2026-10-22 02:20:42'),(4,1,'Hidden Product #10','2026-10-22 02:22:21'),(5,1,'Hidden Product #13','2026-10-22 02:22:23'),(6,1,'Updated admin settings','2026-10-22 02:29:33'),(7,1,'Exported Activity Logs to CSV','2026-10-22 02:41:46'),(8,1,'Hidden Driver #10','2026-10-22 02:57:32'),(9,1,'Hidden Driver #1','2026-10-22 02:57:35'),(10,2,'Admin logged in','2026-10-22 11:07:11'),(11,2,'Admin logged out','2026-10-22 11:07:49'),(12,1,'Admin logged in','2026-10-22 11:07:58'),(13,1,'Deactivated Admin #2','2026-10-22 11:10:15'),(14,1,'Admin logged out','2026-10-22 11:10:17'),(15,2,'Admin logged in','2026-10-22 11:10:28'),(16,2,'Admin logged out','2026-10-22 11:10:43'),(17,1,'Admin logged in','2026-10-22 11:12:50'),(18,1,'Admin logged out','2026-10-22 11:13:07'),(19,2,'Admin logged in','2026-10-22 11:13:17'),(20,2,'Admin logged out','2026-10-22 11:16:19'),(21,1,'Admin logged in','2026-10-22 11:16:42'),(22,1,'Activated Admin #2','2026-10-22 11:19:38'),(23,1,'Admin logged out','2026-10-22 11:19:43'),(24,1,'Admin logged in','2026-10-22 11:19:49'),(25,1,'Admin logged out','2026-10-22 11:21:50'),(26,1,'Admin logged in','2026-10-22 11:21:56'),(27,1,'Admin logged out','2026-10-22 11:21:59'),(28,2,'Admin logged in','2026-10-22 11:22:19'),(29,2,'Exported Drivers to CSV','2026-10-22 11:44:28'),(30,2,'Exported Products to CSV','2026-10-22 11:44:33'),(31,2,'Exported Orders to CSV','2026-10-22 11:44:37'),(32,2,'Admin logged out','2026-10-22 11:45:36'),(33,1,'Admin logged in','2026-10-22 11:45:43'),(34,1,'Admin logged out','2026-10-22 11:45:52'),(35,2,'Admin logged in','2026-10-22 11:51:25'),(36,2,'Admin logged in','2026-10-22 19:55:16'),(37,2,'Closed the Store for Updates','2026-10-22 20:57:37'),(38,2,'Opened the Store','2026-10-22 20:57:43'),(39,2,'Closed the Store for Updates','2026-10-22 21:02:12'),(40,2,'Permanently Deleted Product: asad','2026-10-22 21:02:17'),(41,2,'Manually Opened Store','2026-10-22 21:17:16'),(42,2,'Reset Store to Auto Schedule','2026-10-22 21:17:18'),(43,2,'Manually Opened Store','2026-10-22 21:17:20'),(44,2,'Manually Closed Store','2026-10-22 21:17:20'),(45,2,'Manually Opened Store','2026-10-22 21:17:21'),(46,2,'Manually Closed Store','2026-10-22 21:17:21'),(47,2,'Reset Store to Auto Schedule','2026-10-22 21:17:23'),(48,2,'Manually Opened Store','2026-10-22 21:17:23'),(49,2,'Reset Store to Auto Schedule','2026-10-22 21:17:24'),(50,2,'Manually Opened Store','2026-10-22 21:17:25'),(51,2,'Reset Store to Auto Schedule','2026-10-22 21:17:26'),(52,2,'Manually Opened Store','2026-10-22 21:17:27'),(53,2,'Manually Closed Store','2026-10-22 21:17:58'),(54,2,'Reset Store to Auto Schedule','2026-10-22 21:17:58'),(55,2,'Manually Opened Store','2026-10-22 21:18:00'),(56,2,'Reset Store to Auto Schedule','2026-10-22 21:18:01'),(57,2,'Manually Opened Store','2026-10-22 21:18:02'),(58,2,'Manually Closed Store','2026-10-22 21:18:03'),(59,2,'Updated Store Schedule to 09:00 - 16:00','2026-10-22 21:20:24'),(60,2,'Manually Opened Store','2026-10-22 21:20:37'),(61,2,'Manually Closed Store','2026-10-22 21:20:42'),(62,2,'Manually Opened Store','2026-10-22 21:20:49'),(63,2,'Reset Store to Auto Schedule','2026-10-22 21:20:51'),(64,2,'Updated Store Schedule to 21:00 - 04:00','2026-10-22 21:21:17'),(65,2,'Updated Store Schedule to 21:00:00 - 22:00','2026-10-22 21:21:39'),(66,2,'Exported Products to PDF','2026-10-22 21:21:51'),(67,2,'Exported Orders to PDF','2026-10-22 21:23:13'),(68,2,'Exported Products to PDF','2026-10-22 21:25:06'),(69,2,'Exported Products to PDF','2026-10-22 21:27:44'),(70,2,'Exported Order #6 Receipt to PDF','2026-10-22 21:27:50'),(71,2,'Exported Drivers to PDF','2026-10-22 21:27:58'),(72,2,'Approved Order #1 and Auto-Assigned to Driver #1','2026-10-22 22:28:13'),(73,2,'Deactivated Driver #11','2026-10-22 22:43:23'),(74,2,'Deactivated Driver #10','2026-10-22 22:43:30'),(75,2,'Deactivated Driver #12','2026-10-22 22:43:32'),(76,2,'Deactivated Driver #13','2026-10-22 22:43:33'),(77,2,'Approved Order #3 (No drivers available)','2026-10-22 22:43:38'),(78,2,'Activated Driver #13','2026-10-22 22:44:04'),(79,2,'Permanently Deleted Product: Test Kimchi Auth','2026-10-22 23:10:19'),(80,2,'Permanently Deleted Product: kyle','2026-10-22 23:10:22'),(81,2,'Deactivated Driver #13','2026-10-22 23:10:55'),(82,2,'Activated Driver #13','2026-10-22 23:10:56'),(83,2,'Exported Order #2 Receipt to PDF','2026-10-22 23:18:36'),(84,2,'Exported Order #2 Receipt to PDF','2026-10-22 23:19:11'),(85,2,'Exported Order #2 Receipt to PDF','2026-10-22 23:19:14');
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin` (
  `admin_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `password_hash` varchar(255) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin`
--

LOCK TABLES `admin` WRITE;
/*!40000 ALTER TABLE `admin` DISABLE KEYS */;
INSERT INTO `admin` VALUES (1,'Administrator','admin@kimfresh.com','09123456789','Super Admin','2026-09-11 08:30:52','$argon2id$v=19$m=65536,t=3,p=4$C6KO/gQkW3eruyIQ3GWBFg$8R+2KhUywvdfS0my5hVnuHLbSXufp6B2cWobM0fHyo8','active'),(2,'Kyle Aaa','kylefcelis@gmail.com',NULL,NULL,'2026-10-22 11:06:46','$2y$10$Y673m8HfvE0HlOIA1bJEOuEwp.kb.LLUuRLYk1LeFmdMi4huE1GS.','active');
/*!40000 ALTER TABLE `admin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `delivery`
--

DROP TABLE IF EXISTS `delivery`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `delivery` (
  `delivery_id` int NOT NULL AUTO_INCREMENT,
  `order_id` int DEFAULT NULL,
  `driver_id` int DEFAULT NULL,
  `delivery_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `delivery_status` varchar(20) DEFAULT 'assigned',
  PRIMARY KEY (`delivery_id`),
  KEY `order_id` (`order_id`),
  KEY `driver_id` (`driver_id`),
  CONSTRAINT `delivery_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `order` (`order_id`),
  CONSTRAINT `delivery_ibfk_2` FOREIGN KEY (`driver_id`) REFERENCES `driver` (`driver_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `delivery`
--

LOCK TABLES `delivery` WRITE;
/*!40000 ALTER TABLE `delivery` DISABLE KEYS */;
INSERT INTO `delivery` VALUES (1,1,1,'2026-10-22 22:28:13','assigned'),(2,3,13,'2026-10-22 23:10:56','assigned');
/*!40000 ALTER TABLE `delivery` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `driver`
--

DROP TABLE IF EXISTS `driver`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `driver` (
  `driver_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `license_number` varchar(50) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'available',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `account_status` varchar(20) DEFAULT 'approved',
  `rejected_at` datetime DEFAULT NULL,
  PRIMARY KEY (`driver_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `driver`
--

LOCK TABLES `driver` WRITE;
/*!40000 ALTER TABLE `driver` DISABLE KEYS */;
INSERT INTO `driver` VALUES (1,'Test Driver Updated','driver@kimfresh.com','09999999999','$argon2id$v=19$m=65536,t=3,p=4$C6KO/gQkW3eruyIQ3GWBFg$8R+2KhUywvdfS0my5hVnuHLbSXufp6B2cWobM0fHyo8','LIC-001','on_delivery','2026-09-06 21:58:35','approved',NULL),(10,'Test Driver XYZ','testdriverxyz@kimfresh.com','09111111111',NULL,'TEST-XYZ','hidden','2026-09-29 05:33:21','approved',NULL),(11,'Pending Driver','pending@kimfresh.com','09999999999','$argon2id$v=19$m=65536,t=3,p=4$5+BYJi/iYka3KGf7xl9xfg$aEF9AW9EcFH31CMRkEe0s3PfaCPHkiEE4+hF5g8V5dc','LIC-PEND','hidden','2026-10-14 21:07:28','rejected','2026-10-14 21:50:08'),(12,'kyle','kyle@gmail.com','','$argon2id$v=19$m=65536,t=3,p=4$/1IlHcoBJjxK0JZiKkUnQA$GgF8wNHC4fEBZQ7Mk57pNlLVXxQA4g4RT/HWSQV4S+E','','hidden','2026-10-14 21:38:20','approved',NULL),(13,'sher','sherwin@gmail.com','09662367706','$argon2id$v=19$m=65536,t=3,p=4$JELwIoYlrLtdbgBwbLbgWw$6KX26dZCRKZ+p+MmrXKOtFd4xUTQpD0ibi7iITn1nU8','123213123213213','on_delivery','2026-10-19 22:56:17','approved',NULL);
/*!40000 ALTER TABLE `driver` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order`
--

DROP TABLE IF EXISTS `order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order` (
  `order_id` int NOT NULL AUTO_INCREMENT,
  `retailer_id` int DEFAULT NULL,
  `order_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `order_status` varchar(20) DEFAULT 'pending',
  PRIMARY KEY (`order_id`),
  KEY `retailer_id` (`retailer_id`),
  CONSTRAINT `order_ibfk_1` FOREIGN KEY (`retailer_id`) REFERENCES `retailer` (`retailer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order`
--

LOCK TABLES `order` WRITE;
/*!40000 ALTER TABLE `order` DISABLE KEYS */;
INSERT INTO `order` VALUES (1,1,'2026-10-22 22:27:49',295.00,'approved'),(2,1,'2026-10-22 22:39:39',295.00,'pending'),(3,1,'2026-10-22 22:39:39',350.00,'approved');
/*!40000 ALTER TABLE `order` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_item`
--

DROP TABLE IF EXISTS `order_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_item` (
  `order_item_id` int NOT NULL AUTO_INCREMENT,
  `order_id` int DEFAULT NULL,
  `product_id` int DEFAULT NULL,
  `quantity` int DEFAULT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`order_item_id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `order_item_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `order` (`order_id`),
  CONSTRAINT `order_item_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_item`
--

LOCK TABLES `order_item` WRITE;
/*!40000 ALTER TABLE `order_item` DISABLE KEYS */;
INSERT INTO `order_item` VALUES (1,1,1,1,175.00,175.00),(2,1,2,1,120.00,120.00),(3,2,1,1,175.00,175.00),(4,2,2,1,120.00,120.00),(5,3,1,2,175.00,350.00);
/*!40000 ALTER TABLE `order_item` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product`
--

DROP TABLE IF EXISTS `product`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product` (
  `product_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `sku` varchar(50) DEFAULT NULL,
  `description` text,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `reorder_level` int DEFAULT '5',
  `status` varchar(20) DEFAULT 'available',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `stock_quantity` int DEFAULT '0',
  PRIMARY KEY (`product_id`),
  UNIQUE KEY `sku` (`sku`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product`
--

LOCK TABLES `product` WRITE;
/*!40000 ALTER TABLE `product` DISABLE KEYS */;
INSERT INTO `product` VALUES (1,'Baechu Kimchi','KIM-001','Traditional cabbage kimchi',175.00,10,'available','2026-09-11 09:37:57',33),(2,'Kkakdugi','KIM-002','Radish kimchi',120.00,8,'available','2026-09-11 09:37:57',42),(3,'Oi Sobagi','KIM-003','Cucumber kimchi',130.00,15,'available','2026-09-11 09:37:57',48);
/*!40000 ALTER TABLE `product` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `retailer`
--

DROP TABLE IF EXISTS `retailer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `retailer` (
  `retailer_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `address` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `customer_code` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`retailer_id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `customer_code` (`customer_code`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `retailer`
--

LOCK TABLES `retailer` WRITE;
/*!40000 ALTER TABLE `retailer` DISABLE KEYS */;
INSERT INTO `retailer` VALUES (1,'Test Retailer','retailer@kimfresh.com','09123456789','$argon2id$v=19$m=65536,t=3,p=4$C6KO/gQkW3eruyIQ3GWBFg$8R+2KhUywvdfS0my5hVnuHLbSXufp6B2cWobM0fHyo8','Bacolod City','2026-09-06 21:58:35','942478'),(2,'kyle','kylefcelis@gmail.com','',NULL,'','2026-09-13 09:18:33','789203'),(3,'Johan','johan@gmail.com','09949072247','$argon2id$v=19$m=65536,t=3,p=4$gA3/z9G6r84PiECB2dTG3w$jaVQy4fCf+BtQBpVRFPGOsGCDIugIXyKC2hwJVaXBjE','villamonte','2026-10-20 00:15:16','325486');
/*!40000 ALTER TABLE `retailer` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sensor_data`
--

DROP TABLE IF EXISTS `sensor_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sensor_data` (
  `sensor_id` int NOT NULL AUTO_INCREMENT,
  `delivery_id` int DEFAULT NULL,
  `temperature` decimal(5,2) DEFAULT NULL,
  `humidity` decimal(5,2) DEFAULT NULL,
  `is_alert` tinyint(1) DEFAULT '0',
  `reading_timestamp` datetime DEFAULT CURRENT_TIMESTAMP,
  `synced_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`sensor_id`),
  KEY `delivery_id` (`delivery_id`),
  CONSTRAINT `sensor_data_ibfk_1` FOREIGN KEY (`delivery_id`) REFERENCES `delivery` (`delivery_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sensor_data`
--

LOCK TABLES `sensor_data` WRITE;
/*!40000 ALTER TABLE `sensor_data` DISABLE KEYS */;
/*!40000 ALTER TABLE `sensor_data` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `store_settings`
--

DROP TABLE IF EXISTS `store_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `store_settings` (
  `id` int NOT NULL DEFAULT '1',
  `is_open` tinyint(1) DEFAULT '1',
  `open_time` time DEFAULT '08:00:00',
  `close_time` time DEFAULT '17:00:00',
  `manual_override` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `store_settings`
--

LOCK TABLES `store_settings` WRITE;
/*!40000 ALTER TABLE `store_settings` DISABLE KEYS */;
INSERT INTO `store_settings` VALUES (1,0,'21:00:00','22:00:00',NULL);
/*!40000 ALTER TABLE `store_settings` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-22 23:58:36
