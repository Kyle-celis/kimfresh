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
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin`
--

LOCK TABLES `admin` WRITE;
/*!40000 ALTER TABLE `admin` DISABLE KEYS */;
INSERT INTO `admin` VALUES (1,'Admin User','admin@kimfresh.com','09123456789','Super Admin','2026-09-11 08:30:52','$argon2id$v=19$m=65536,t=3,p=4$C6KO/gQkW3eruyIQ3GWBFg$8R+2KhUywvdfS0my5hVnuHLbSXufp6B2cWobM0fHyo8');
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `delivery`
--

LOCK TABLES `delivery` WRITE;
/*!40000 ALTER TABLE `delivery` DISABLE KEYS */;
INSERT INTO `delivery` VALUES (1,1,1,'2026-10-14 07:36:43','delivered'),(2,2,1,'2026-10-14 07:38:52','assigned'),(3,3,12,'2026-10-14 22:02:52','delivered'),(4,4,13,'2026-10-20 11:24:05','assigned'),(5,5,10,'2026-10-20 11:24:05','assigned'),(6,6,12,'2026-10-20 11:24:05','assigned');
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
INSERT INTO `driver` VALUES (1,'Test Driver Updated','driver@kimfresh.com','09999999999','$argon2id$v=19$m=65536,t=3,p=4$C6KO/gQkW3eruyIQ3GWBFg$8R+2KhUywvdfS0my5hVnuHLbSXufp6B2cWobM0fHyo8','LIC-001','on_delivery','2026-09-06 21:58:35','approved',NULL),(10,'Test Driver XYZ','testdriverxyz@kimfresh.com','09111111111',NULL,'TEST-XYZ','on_delivery','2026-09-29 05:33:21','approved',NULL),(11,'Pending Driver','pending@kimfresh.com','09999999999','$argon2id$v=19$m=65536,t=3,p=4$5+BYJi/iYka3KGf7xl9xfg$aEF9AW9EcFH31CMRkEe0s3PfaCPHkiEE4+hF5g8V5dc','LIC-PEND','rejected','2026-10-14 21:07:28','rejected','2026-10-14 21:50:08'),(12,'kyle','kyle@gmail.com','','$argon2id$v=19$m=65536,t=3,p=4$/1IlHcoBJjxK0JZiKkUnQA$GgF8wNHC4fEBZQ7Mk57pNlLVXxQA4g4RT/HWSQV4S+E','','on_delivery','2026-10-14 21:38:20','approved',NULL),(13,'sher','sherwin@gmail.com','09662367706','$argon2id$v=19$m=65536,t=3,p=4$JELwIoYlrLtdbgBwbLbgWw$6KX26dZCRKZ+p+MmrXKOtFd4xUTQpD0ibi7iITn1nU8','123213123213213','on_delivery','2026-10-19 22:56:17','approved',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order`
--

LOCK TABLES `order` WRITE;
/*!40000 ALTER TABLE `order` DISABLE KEYS */;
INSERT INTO `order` VALUES (1,1,'2026-10-14 07:36:37',175.00,'delivered'),(2,1,'2026-10-14 07:38:47',175.00,'approved'),(3,1,'2026-10-14 22:02:34',229.99,'delivered'),(4,3,'2026-10-20 00:18:18',720.00,'approved'),(5,3,'2026-10-20 00:31:10',295.00,'approved'),(6,3,'2026-10-20 00:33:18',295.00,'approved');
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
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_item`
--

LOCK TABLES `order_item` WRITE;
/*!40000 ALTER TABLE `order_item` DISABLE KEYS */;
INSERT INTO `order_item` VALUES (9,3,3,1,130.00,130.00),(10,3,10,1,99.99,99.99),(11,4,3,1,130.00,130.00),(12,4,2,2,120.00,240.00),(13,4,1,2,175.00,350.00),(14,5,1,1,175.00,175.00),(15,5,2,1,120.00,120.00),(16,6,1,1,175.00,175.00),(17,6,2,1,120.00,120.00);
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
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product`
--

LOCK TABLES `product` WRITE;
/*!40000 ALTER TABLE `product` DISABLE KEYS */;
INSERT INTO `product` VALUES (1,'Baechu Kimchi','KIM-001','Traditional cabbage kimchi',175.00,10,'available','2026-09-11 09:37:57',37),(2,'Kkakdugi','KIM-002','Radish kimchi',120.00,8,'available','2026-09-11 09:37:57',44),(3,'Oi Sobagi','KIM-003','Cucumber kimchi',130.00,15,'available','2026-09-11 09:37:57',48),(10,'Test Kimchi Auth','KIM-004','',99.99,10,'inactive','2026-09-29 05:33:22',5),(12,'asad','KIM-005','',222.00,5,'inactive','2026-10-20 10:45:05',50);
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
) ENGINE=InnoDB AUTO_INCREMENT=1217 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sensor_data`
--

LOCK TABLES `sensor_data` WRITE;
/*!40000 ALTER TABLE `sensor_data` DISABLE KEYS */;
INSERT INTO `sensor_data` VALUES (1083,2,30.50,71.50,1,'2026-10-17 04:46:21','2026-10-17 04:46:21'),(1084,2,30.50,71.50,1,'2026-10-17 04:46:42','2026-10-17 04:46:42'),(1085,2,30.50,71.50,1,'2026-10-17 04:47:01','2026-10-17 04:47:01'),(1086,2,30.50,71.50,1,'2026-10-17 04:47:22','2026-10-17 04:47:22'),(1087,2,30.50,71.50,1,'2026-10-17 04:47:42','2026-10-17 04:47:42'),(1088,2,30.50,71.50,1,'2026-10-17 04:48:02','2026-10-17 04:48:02'),(1089,2,30.50,71.50,1,'2026-10-17 04:48:22','2026-10-17 04:48:22'),(1090,2,30.50,71.50,1,'2026-10-17 04:48:42','2026-10-17 04:48:42'),(1091,2,30.40,71.50,1,'2026-10-17 04:49:02','2026-10-17 04:49:02'),(1092,2,30.50,71.60,1,'2026-10-17 04:49:22','2026-10-17 04:49:22'),(1093,2,30.40,71.60,1,'2026-10-17 04:49:42','2026-10-17 04:49:42'),(1094,2,30.40,71.60,1,'2026-10-17 04:50:02','2026-10-17 04:50:02'),(1095,2,30.40,71.60,1,'2026-10-17 04:50:22','2026-10-17 04:50:22'),(1096,2,30.40,71.60,1,'2026-10-17 04:50:42','2026-10-17 04:50:42'),(1097,2,30.40,71.70,1,'2026-10-17 04:51:02','2026-10-17 04:51:02'),(1098,2,30.40,71.70,1,'2026-10-17 04:51:22','2026-10-17 04:51:22'),(1099,2,30.40,71.70,1,'2026-10-17 04:51:42','2026-10-17 04:51:42'),(1100,2,30.40,71.70,1,'2026-10-17 04:52:02','2026-10-17 04:52:02'),(1101,2,30.40,71.70,1,'2026-10-17 04:52:22','2026-10-17 04:52:22'),(1102,2,30.40,71.70,1,'2026-10-17 04:52:42','2026-10-17 04:52:42'),(1103,2,30.40,71.70,1,'2026-10-17 04:53:02','2026-10-17 04:53:02'),(1104,2,30.40,71.70,1,'2026-10-17 04:53:22','2026-10-17 04:53:22'),(1105,2,30.40,71.70,1,'2026-10-17 04:53:42','2026-10-17 04:53:42'),(1106,2,30.40,71.70,1,'2026-10-17 04:54:02','2026-10-17 04:54:02'),(1107,2,30.40,71.80,1,'2026-10-17 04:54:22','2026-10-17 04:54:22'),(1108,2,30.40,71.80,1,'2026-10-17 04:54:42','2026-10-17 04:54:42'),(1109,2,30.40,71.80,1,'2026-10-17 04:55:02','2026-10-17 04:55:02'),(1110,2,30.30,71.80,1,'2026-10-17 04:55:22','2026-10-17 04:55:22'),(1111,2,30.30,71.80,1,'2026-10-17 04:55:42','2026-10-17 04:55:42'),(1112,2,30.30,71.80,1,'2026-10-17 04:56:02','2026-10-17 04:56:02'),(1113,2,30.30,71.80,1,'2026-10-17 04:56:22','2026-10-17 04:56:22'),(1114,2,30.30,71.80,1,'2026-10-17 04:56:42','2026-10-17 04:56:42'),(1115,2,30.30,71.80,1,'2026-10-17 04:57:02','2026-10-17 04:57:02'),(1116,2,30.30,71.80,1,'2026-10-17 04:57:22','2026-10-17 04:57:22'),(1117,2,30.30,71.80,1,'2026-10-17 04:57:42','2026-10-17 04:57:42'),(1118,2,30.40,71.80,1,'2026-10-17 04:58:02','2026-10-17 04:58:02'),(1119,2,30.30,71.80,1,'2026-10-17 04:58:22','2026-10-17 04:58:22'),(1120,2,30.30,71.80,1,'2026-10-17 04:58:42','2026-10-17 04:58:42'),(1121,2,30.30,71.80,1,'2026-10-17 04:59:02','2026-10-17 04:59:02'),(1122,2,30.30,71.80,1,'2026-10-17 04:59:22','2026-10-17 04:59:22'),(1123,2,30.30,71.80,1,'2026-10-17 04:59:42','2026-10-17 04:59:42'),(1124,2,30.30,71.80,1,'2026-10-17 05:00:02','2026-10-17 05:00:02'),(1125,2,30.30,71.80,1,'2026-10-17 05:00:22','2026-10-17 05:00:22'),(1126,2,30.30,71.80,1,'2026-10-17 05:00:42','2026-10-17 05:00:42'),(1127,2,30.30,71.80,1,'2026-10-17 05:01:02','2026-10-17 05:01:02'),(1128,2,30.30,71.90,1,'2026-10-17 05:01:22','2026-10-17 05:01:22'),(1129,2,30.30,71.90,1,'2026-10-17 05:01:43','2026-10-17 05:01:43'),(1130,2,30.30,71.90,1,'2026-10-17 05:02:02','2026-10-17 05:02:02'),(1131,2,30.30,71.90,1,'2026-10-17 05:02:22','2026-10-17 05:02:22'),(1132,2,30.30,71.90,1,'2026-10-17 05:02:42','2026-10-17 05:02:42'),(1133,2,30.30,71.90,1,'2026-10-17 05:03:02','2026-10-17 05:03:02'),(1134,2,30.30,71.90,1,'2026-10-17 05:03:22','2026-10-17 05:03:22'),(1135,2,30.30,71.90,1,'2026-10-17 05:03:43','2026-10-17 05:03:43'),(1136,2,30.30,72.00,1,'2026-10-17 05:04:03','2026-10-17 05:04:03'),(1137,2,30.30,71.90,1,'2026-10-17 05:04:23','2026-10-17 05:04:23'),(1138,2,30.20,72.00,1,'2026-10-17 05:04:43','2026-10-17 05:04:43'),(1139,2,30.30,72.00,1,'2026-10-17 05:05:03','2026-10-17 05:05:03'),(1140,2,30.30,72.00,1,'2026-10-17 05:05:23','2026-10-17 05:05:23'),(1141,2,30.30,72.00,1,'2026-10-17 05:05:43','2026-10-17 05:05:43'),(1142,2,30.20,72.00,1,'2026-10-17 05:06:03','2026-10-17 05:06:03'),(1143,2,30.20,72.00,1,'2026-10-17 05:06:23','2026-10-17 05:06:23'),(1144,2,30.20,72.00,1,'2026-10-17 05:06:43','2026-10-17 05:06:43'),(1145,2,30.20,72.00,1,'2026-10-17 05:07:03','2026-10-17 05:07:03'),(1146,2,30.20,72.00,1,'2026-10-17 05:07:23','2026-10-17 05:07:23'),(1147,2,30.20,72.00,1,'2026-10-17 05:07:43','2026-10-17 05:07:43'),(1148,2,30.20,72.00,1,'2026-10-17 05:08:03','2026-10-17 05:08:03'),(1149,2,30.20,72.10,1,'2026-10-17 05:13:03','2026-10-17 05:13:03'),(1150,2,30.30,72.00,1,'2026-10-17 05:13:23','2026-10-17 05:13:23'),(1151,2,30.20,72.00,1,'2026-10-17 05:13:43','2026-10-17 05:13:43'),(1152,2,30.20,72.00,1,'2026-10-17 05:14:03','2026-10-17 05:14:03'),(1153,2,30.30,72.00,1,'2026-10-17 05:14:23','2026-10-17 05:14:23'),(1154,2,30.30,72.00,1,'2026-10-17 05:14:43','2026-10-17 05:14:43'),(1155,2,30.20,72.00,1,'2026-10-17 05:15:03','2026-10-17 05:15:03'),(1156,2,30.30,71.90,1,'2026-10-17 05:15:23','2026-10-17 05:15:23'),(1157,2,30.30,71.90,1,'2026-10-17 05:15:43','2026-10-17 05:15:43'),(1158,2,30.30,71.90,1,'2026-10-17 05:16:03','2026-10-17 05:16:03'),(1159,2,30.30,71.90,1,'2026-10-17 05:16:23','2026-10-17 05:16:23'),(1160,2,30.30,71.80,1,'2026-10-17 05:16:44','2026-10-17 05:16:44'),(1161,2,30.30,71.80,1,'2026-10-17 05:17:03','2026-10-17 05:17:03'),(1162,2,30.30,71.80,1,'2026-10-17 05:17:23','2026-10-17 05:17:23'),(1163,2,30.30,71.80,1,'2026-10-17 05:17:44','2026-10-17 05:17:44'),(1164,2,30.30,71.70,1,'2026-10-17 05:18:04','2026-10-17 05:18:04'),(1165,2,30.30,71.70,1,'2026-10-17 05:18:24','2026-10-17 05:18:24'),(1166,2,30.30,71.70,1,'2026-10-17 05:18:44','2026-10-17 05:18:44'),(1167,2,30.30,71.70,1,'2026-10-17 05:19:04','2026-10-17 05:19:04'),(1168,2,30.30,71.70,1,'2026-10-17 05:19:24','2026-10-17 05:19:24'),(1169,2,30.30,71.60,1,'2026-10-17 05:19:44','2026-10-17 05:19:44'),(1170,2,30.30,71.60,1,'2026-10-17 05:20:04','2026-10-17 05:20:04'),(1171,2,30.30,71.60,1,'2026-10-17 05:20:24','2026-10-17 05:20:24'),(1172,2,30.30,71.60,1,'2026-10-17 05:20:44','2026-10-17 05:20:44'),(1173,2,30.30,71.60,1,'2026-10-17 05:21:04','2026-10-17 05:21:04'),(1174,2,30.30,71.60,1,'2026-10-17 05:21:24','2026-10-17 05:21:24'),(1175,2,30.30,71.60,1,'2026-10-17 05:21:44','2026-10-17 05:21:44'),(1176,2,30.30,71.60,1,'2026-10-17 05:22:04','2026-10-17 05:22:04'),(1177,2,30.30,71.60,1,'2026-10-17 05:22:24','2026-10-17 05:22:24'),(1178,2,30.30,71.60,1,'2026-10-17 05:22:44','2026-10-17 05:22:44'),(1179,2,30.30,71.60,1,'2026-10-17 05:23:04','2026-10-17 05:23:04'),(1180,2,30.30,71.60,1,'2026-10-17 05:23:24','2026-10-17 05:23:24'),(1181,2,30.30,71.60,1,'2026-10-17 05:23:44','2026-10-17 05:23:44'),(1182,2,30.30,71.60,1,'2026-10-17 05:24:04','2026-10-17 05:24:04'),(1183,2,30.30,71.50,1,'2026-10-17 05:24:24','2026-10-17 05:24:24'),(1184,2,30.30,71.50,1,'2026-10-17 05:24:44','2026-10-17 05:24:44'),(1185,2,30.30,71.40,1,'2026-10-17 05:25:04','2026-10-17 05:25:04'),(1186,2,30.30,71.40,1,'2026-10-17 05:25:24','2026-10-17 05:25:24'),(1187,2,30.30,71.30,1,'2026-10-17 05:25:44','2026-10-17 05:25:44'),(1188,2,30.30,71.30,1,'2026-10-17 05:26:04','2026-10-17 05:26:04'),(1189,2,30.30,71.30,1,'2026-10-17 05:26:24','2026-10-17 05:26:24'),(1190,2,30.30,71.20,1,'2026-10-17 05:26:44','2026-10-17 05:26:44'),(1191,2,30.30,71.20,1,'2026-10-17 05:27:04','2026-10-17 05:27:04'),(1192,2,30.30,71.10,1,'2026-10-17 05:27:24','2026-10-17 05:27:24'),(1193,2,30.30,71.20,1,'2026-10-17 05:27:44','2026-10-17 05:27:44'),(1194,2,30.30,71.10,1,'2026-10-17 05:28:04','2026-10-17 05:28:04'),(1195,2,30.30,71.10,1,'2026-10-17 05:28:24','2026-10-17 05:28:24'),(1196,2,30.30,71.10,1,'2026-10-17 05:28:44','2026-10-17 05:28:44'),(1197,2,30.30,71.10,1,'2026-10-17 05:29:04','2026-10-17 05:29:04'),(1198,2,30.30,71.10,1,'2026-10-17 05:29:24','2026-10-17 05:29:24'),(1199,2,30.20,71.10,1,'2026-10-17 05:29:44','2026-10-17 05:29:44'),(1200,2,30.30,71.10,1,'2026-10-17 05:30:04','2026-10-17 05:30:04'),(1201,2,30.20,71.10,1,'2026-10-17 05:30:24','2026-10-17 05:30:24'),(1202,2,30.30,71.10,1,'2026-10-17 05:30:44','2026-10-17 05:30:44'),(1203,2,30.30,71.10,1,'2026-10-17 05:31:04','2026-10-17 05:31:04'),(1204,2,30.30,71.10,1,'2026-10-17 05:31:24','2026-10-17 05:31:24'),(1205,2,30.30,71.00,1,'2026-10-17 05:31:44','2026-10-17 05:31:44'),(1206,2,30.30,71.00,1,'2026-10-17 05:32:04','2026-10-17 05:32:04'),(1207,2,30.20,70.90,1,'2026-10-17 05:32:24','2026-10-17 05:32:24'),(1208,2,30.20,70.90,1,'2026-10-17 05:32:44','2026-10-17 05:32:44'),(1209,2,30.30,70.90,1,'2026-10-17 05:33:05','2026-10-17 05:33:05'),(1210,2,30.30,70.90,1,'2026-10-17 05:33:25','2026-10-17 05:33:25'),(1211,2,30.30,70.80,1,'2026-10-17 05:34:05','2026-10-17 05:34:05'),(1212,2,30.30,70.90,1,'2026-10-17 05:34:25','2026-10-17 05:34:25'),(1213,2,30.20,70.90,1,'2026-10-17 05:34:45','2026-10-17 05:34:45'),(1214,2,30.30,71.00,1,'2026-10-17 05:35:05','2026-10-17 05:35:05'),(1215,2,30.30,71.00,1,'2026-10-17 05:35:25','2026-10-17 05:35:25'),(1216,1,4.50,85.00,0,'2026-10-20 21:18:35','2026-10-20 21:18:35');
/*!40000 ALTER TABLE `sensor_data` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-21 22:19:09
