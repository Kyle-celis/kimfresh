-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 11, 2026 at 12:19 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `kimfresh`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `log_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  `actor_type` varchar(20) DEFAULT 'admin',
  `actor_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`log_id`, `admin_id`, `action`, `timestamp`, `actor_type`, `actor_id`) VALUES
(1, 1, 'Logged in', '2026-11-02 11:48:07', 'admin', NULL),
(2, 1, 'Created new Admin: kylefcelis@gmail.com', '2026-11-02 11:52:59', 'admin', NULL),
(3, 1, 'Admin logged out', '2026-11-02 11:53:02', 'admin', NULL),
(4, 2, 'Logged in', '2026-11-02 11:53:08', 'admin', NULL),
(5, 1, 'Logged in', '2026-11-02 16:29:44', 'admin', NULL),
(6, 1, 'Admin logged out', '2026-11-02 16:29:47', 'admin', NULL),
(7, 2, 'Logged in', '2026-11-02 16:29:55', 'admin', NULL),
(8, 2, 'Manually Closed Store', '2026-11-02 16:30:07', 'admin', NULL),
(9, 2, 'Manually Opened Store', '2026-11-02 16:30:07', 'admin', NULL),
(10, 2, 'Exported Products to CSV', '2026-11-02 16:30:09', 'admin', NULL),
(11, 2, 'Exported Products to PDF', '2026-11-02 16:30:11', 'admin', NULL),
(12, 2, 'Admin logged out', '2026-11-02 16:30:19', 'admin', NULL),
(13, 2, 'Logged in', '2026-11-02 16:30:24', 'admin', NULL),
(14, 2, 'Exported Products to CSV', '2026-11-02 16:41:42', 'admin', NULL),
(15, 2, 'Exported Products to PDF', '2026-11-02 16:41:45', 'admin', NULL),
(16, 2, 'Added new product: asesa', '2026-11-02 16:42:03', 'admin', NULL),
(17, 2, 'Exported Products to PDF', '2026-11-02 16:42:10', 'admin', NULL),
(18, 2, 'Exported Products to CSV', '2026-11-02 16:42:15', 'admin', NULL),
(19, 2, 'Manually Closed Store', '2026-11-02 16:42:22', 'admin', NULL),
(20, 2, 'Permanently Deleted Product: Baechu Kimchi', '2026-11-02 16:42:26', 'admin', NULL),
(21, 2, 'Permanently Deleted Product: Kkakdugi', '2026-11-02 16:42:27', 'admin', NULL),
(22, 2, 'Permanently Deleted Product: asesa', '2026-11-02 16:42:29', 'admin', NULL),
(23, 2, 'Imported CSV: 0 added, 3 updated', '2026-11-02 16:42:37', 'admin', NULL),
(24, 2, 'Permanently Deleted Product: Baechu Kimchi', '2026-11-02 16:46:30', 'admin', NULL),
(25, 2, 'Permanently Deleted Product: Kkakdugi', '2026-11-02 16:46:31', 'admin', NULL),
(26, 2, 'Permanently Deleted Product: Test Kimchi', '2026-11-02 16:46:32', 'admin', NULL),
(27, 2, 'Added new product: qwewqewq', '2026-11-02 16:49:54', 'admin', NULL),
(28, 2, 'Permanently Deleted Product: qwewqewq', '2026-11-02 16:50:03', 'admin', NULL),
(29, 2, 'Exported Products to CSV', '2026-11-02 16:50:05', 'admin', NULL),
(30, 2, 'Permanently Deleted Product: Baechu Kimchi', '2026-11-02 16:50:12', 'admin', NULL),
(31, 2, 'Permanently Deleted Product: Kkakdugi', '2026-11-02 16:50:13', 'admin', NULL),
(32, 2, 'Permanently Deleted Product: Test Kimchi', '2026-11-02 16:50:15', 'admin', NULL),
(33, 2, 'Imported CSV: 0 added, 3 updated', '2026-11-02 16:50:19', 'admin', NULL),
(34, 2, 'Exported Products to CSV', '2026-11-02 16:52:09', 'admin', NULL),
(35, 2, 'Exported Products to CSV', '2026-11-02 16:52:18', 'admin', NULL),
(36, 2, 'Permanently Deleted Product: Kkakdugi', '2026-11-02 16:52:22', 'admin', NULL),
(37, 2, 'Imported CSV: 0 added, 0 updated', '2026-11-02 16:54:50', 'admin', NULL),
(38, 2, 'Permanently Deleted Product: Baechu Kimchi', '2026-11-02 16:56:05', 'admin', NULL),
(39, 2, 'Permanently Deleted Product: Kkakdugi', '2026-11-02 16:56:07', 'admin', NULL),
(40, 2, 'Permanently Deleted Product: Baechu Kimchi', '2026-11-02 16:56:08', 'admin', NULL),
(41, 2, 'Imported CSV: 2 processed', '2026-11-02 16:56:12', 'admin', NULL),
(42, 2, 'Exported Drivers to PDF', '2026-11-02 16:56:22', 'admin', NULL),
(43, 2, 'Bulk reject: 2 success, 0 failed', '2026-11-02 17:04:23', 'admin', NULL),
(44, 2, 'Bulk reject: 2 success, 0 failed', '2026-11-02 17:15:08', 'admin', NULL),
(45, 2, 'Exported Order #7 Receipt to PDF', '2026-11-02 17:20:47', 'admin', NULL),
(46, 2, 'Exported Products to PDF', '2026-11-02 18:36:54', 'admin', NULL),
(47, 2, 'Exported Products to CSV', '2026-11-02 18:46:21', 'admin', NULL),
(48, 2, 'Permanently Deleted Product: Baechu Kimchi', '2026-11-02 18:48:12', 'admin', NULL),
(49, 2, 'Permanently Deleted Product: Kkakdugi', '2026-11-02 18:48:15', 'admin', NULL),
(50, 2, 'Added new product: asdasd', '2026-11-02 18:48:21', 'admin', NULL),
(51, 2, 'Imported CSV: 2 processed', '2026-11-02 18:48:26', 'admin', NULL),
(52, 2, 'Reset Store to Auto Schedule', '2026-11-02 18:48:38', 'admin', NULL),
(53, 2, 'Manually Closed Store', '2026-11-02 18:48:40', 'admin', NULL),
(54, 2, 'Exported Drivers to PDF', '2026-11-02 18:55:15', 'admin', NULL),
(55, 2, 'Deactivated Driver #1', '2026-11-02 18:55:17', 'admin', NULL),
(56, 2, 'Activated Driver #1', '2026-11-02 18:55:21', 'admin', NULL),
(57, 2, 'Admin logged out', '2026-11-02 20:09:08', 'admin', NULL),
(58, 1, 'Logged in', '2026-11-02 20:09:35', 'admin', NULL),
(59, 1, 'Admin logged out', '2026-11-02 20:17:00', 'admin', NULL),
(60, 1, 'Logged in', '2026-11-02 20:17:06', 'admin', NULL),
(61, 1, 'Deactivated Admin #2', '2026-11-02 20:23:38', 'admin', NULL),
(62, 1, 'Activated Admin #2', '2026-11-02 20:23:40', 'admin', NULL),
(63, 1, 'Deactivated Admin #2', '2026-11-02 20:23:58', 'admin', NULL),
(64, 1, 'Activated Admin #2', '2026-11-02 20:23:59', 'admin', NULL),
(65, 1, 'Created new Admin: johan@gmail.com', '2026-11-02 20:24:27', 'admin', NULL),
(66, 1, 'Deactivated Admin #3', '2026-11-02 20:24:31', 'admin', NULL),
(67, 1, 'Admin logged out', '2026-11-02 20:25:06', 'admin', NULL),
(68, 2, 'Logged in', '2026-11-02 20:25:13', 'admin', NULL),
(69, 2, 'Exported Order #7 Receipt to PDF', '2026-11-02 20:44:43', 'admin', NULL),
(70, 2, 'Approved Order #7 (from View) and Auto-Assigned to Driver #1', '2026-11-02 20:44:51', 'admin', NULL),
(71, 2, 'Manually Opened Store', '2026-11-02 20:48:08', 'admin', NULL),
(72, 2, 'Updated admin settings', '2026-11-02 20:52:02', 'admin', NULL),
(73, 2, 'Updated admin settings', '2026-11-02 20:52:05', 'admin', NULL),
(74, 2, 'Updated admin settings', '2026-11-02 20:52:40', 'admin', NULL),
(75, 2, 'Admin logged out', '2026-11-02 21:14:23', 'admin', NULL),
(76, 1, 'Logged in', '2026-11-02 21:14:41', 'admin', NULL),
(77, 1, 'Admin logged out', '2026-11-02 21:16:34', 'admin', NULL),
(78, 2, 'Logged in', '2026-11-02 21:24:03', 'admin', NULL),
(79, 2, 'Exported Drivers to PDF', '2026-11-02 21:36:16', 'admin', NULL),
(80, 2, 'Deactivated Driver #1', '2026-11-02 21:38:18', 'admin', NULL),
(81, 2, 'Activated Driver #1', '2026-11-02 21:38:23', 'admin', NULL),
(82, 2, 'Logged in', '2026-11-03 22:26:36', 'admin', NULL),
(83, 2, 'Logged in', '2026-11-05 20:21:55', 'admin', NULL),
(84, 2, 'Admin logged out', '2026-11-05 20:48:13', 'admin', NULL),
(85, 2, 'Logged in', '2026-11-05 21:10:19', 'admin', NULL),
(86, 2, 'Logged in', '2026-11-05 21:11:46', 'admin', NULL),
(87, 2, 'Logged in', '2026-11-05 21:28:09', 'admin', NULL),
(88, 2, 'Logged in', '2026-11-05 21:34:28', 'admin', NULL),
(89, 2, 'Logged in', '2026-11-05 21:35:54', 'admin', NULL),
(90, 2, 'Logged in', '2026-11-05 21:39:34', 'admin', NULL),
(91, 2, 'Logged in', '2026-11-06 16:49:21', 'admin', NULL),
(92, 2, 'Updated admin settings', '2026-11-06 16:49:53', 'admin', NULL),
(93, 2, 'Logged in', '2026-11-07 10:40:41', 'admin', NULL),
(94, 2, 'Added new product: Celis Kyle', '2026-11-07 10:43:40', 'admin', NULL),
(95, 2, 'Added new product: asdas', '2026-11-07 10:45:49', 'admin', NULL),
(96, 2, 'Manually Closed Store', '2026-11-07 10:49:27', 'admin', NULL),
(97, 2, 'Manually Opened Store', '2026-11-07 10:49:28', 'admin', NULL),
(98, 2, 'Manually Closed Store', '2026-11-07 10:50:03', 'admin', NULL),
(99, 2, 'Manually Opened Store', '2026-11-07 10:50:06', 'admin', NULL),
(100, 2, 'Manually Closed Store', '2026-11-07 10:51:55', 'admin', NULL),
(101, 2, 'Permanently Deleted Product: Celis Kyle', '2026-11-07 10:52:02', 'admin', NULL),
(102, 2, 'Updated product: asdas', '2026-11-07 10:52:17', 'admin', NULL),
(103, 2, 'Updated product: asdas', '2026-11-07 10:52:24', 'admin', NULL),
(104, 2, 'Added new product: xcxcvcxv', '2026-11-07 10:52:40', 'admin', NULL),
(105, 2, 'Permanently Deleted Product: xcxcvcxv', '2026-11-07 10:53:31', 'admin', NULL),
(106, 2, 'Updated product: asdas', '2026-11-07 10:54:43', 'admin', NULL),
(107, 2, 'Permanently Deleted Product: asdas', '2026-11-07 10:55:13', 'admin', NULL),
(108, 2, 'Added new product: kyle', '2026-11-07 10:56:05', 'admin', NULL),
(109, 2, 'Exported Products to PDF', '2026-11-07 10:56:11', 'admin', NULL),
(110, 2, 'Exported Products to PDF', '2026-11-07 10:57:31', 'admin', NULL),
(111, 2, 'Exported Products to PDF', '2026-11-07 10:58:49', 'admin', NULL),
(112, 2, 'Exported Products to CSV', '2026-11-07 10:59:01', 'admin', NULL),
(113, 2, 'Exported Products to PDF', '2026-11-07 11:00:37', 'admin', NULL),
(114, 2, 'Exported Products to PDF', '2026-11-07 11:02:01', 'admin', NULL),
(115, 2, 'Manually Opened Store', '2026-11-07 11:02:35', 'admin', NULL),
(116, 2, 'Exported Products to PDF', '2026-11-07 11:02:40', 'admin', NULL),
(117, 2, 'Exported Products to PDF', '2026-11-07 11:03:06', 'admin', NULL),
(118, 2, 'Exported Products to PDF', '2026-11-07 11:04:04', 'admin', NULL),
(119, 2, 'Exported Products to PDF', '2026-11-07 11:05:24', 'admin', NULL),
(120, 2, 'Exported Products to PDF', '2026-11-07 11:06:39', 'admin', NULL),
(121, 2, 'Manually Closed Store', '2026-11-07 11:06:47', 'admin', NULL),
(122, 2, 'Exported Products to PDF', '2026-11-07 11:06:48', 'admin', NULL),
(123, 2, 'Exported Products to PDF', '2026-11-07 11:10:29', 'admin', NULL),
(124, 2, 'Exported Products to PDF', '2026-11-07 11:10:38', 'admin', NULL),
(125, 2, 'Exported Products to PDF', '2026-11-07 11:11:21', 'admin', NULL),
(126, 2, 'Updated product: kyle', '2026-11-07 11:12:03', 'admin', NULL),
(127, 2, 'Exported Products to PDF', '2026-11-07 11:12:07', 'admin', NULL),
(128, 2, 'Exported Products to PDF', '2026-11-07 11:13:36', 'admin', NULL),
(129, 2, 'Permanently Deleted Product: kyle', '2026-11-07 11:15:08', 'admin', NULL),
(130, 2, 'Added new product: kyle', '2026-11-07 11:15:22', 'admin', NULL),
(131, 2, 'Exported Products to PDF', '2026-11-07 11:38:05', 'admin', NULL),
(132, 1, 'Driver Test Driver updated Order #7 to PICKED_UP', '2026-11-07 12:51:41', 'admin', NULL),
(133, 1, 'Driver Test Driver updated Order #7 to IN_TRANSIT', '2026-11-07 12:51:45', 'admin', NULL),
(134, 1, 'Driver Test Driver updated Order #7 to DELIVERED', '2026-11-07 12:51:51', 'admin', NULL),
(135, 2, 'Manually Opened Store', '2026-11-07 12:52:09', 'admin', NULL),
(136, 2, 'Approved Order #8 (from View) and Auto-Assigned to Driver #1', '2026-11-07 12:55:22', 'admin', NULL),
(137, 2, 'Bulk reject: 2 success, 0 failed', '2026-11-07 13:10:12', 'admin', NULL),
(138, 2, 'Approved Order #6', '2026-11-07 13:10:22', 'admin', NULL),
(139, 2, 'Rejected Order #5', '2026-11-07 13:10:30', 'admin', NULL),
(140, 1, 'Driver Test Driver updated Order #8 to PICKED_UP', '2026-11-07 13:12:53', 'admin', NULL),
(141, 1, 'Driver Test Driver updated Order #8 to IN_TRANSIT', '2026-11-07 13:12:56', 'admin', NULL),
(142, 1, 'Driver Test Driver updated Order #8 to DELIVERED', '2026-11-07 13:13:00', 'admin', NULL),
(143, 1, 'Driver Test Driver updated Order #6 to PICKED_UP', '2026-11-07 13:14:24', 'admin', NULL),
(144, 1, 'Driver Test Driver updated Order #6 to IN_TRANSIT', '2026-11-07 13:14:27', 'admin', NULL),
(145, 1, 'Driver Test Driver updated Order #6 to DELIVERED', '2026-11-07 13:14:30', 'admin', NULL),
(146, 2, 'Logged in', '2026-11-07 19:00:28', 'admin', NULL),
(147, 2, 'Manually Closed Store', '2026-11-07 19:05:38', 'admin', NULL),
(148, 2, 'Manually Opened Store', '2026-11-07 19:08:12', 'admin', NULL),
(149, 2, 'Manually Closed Store', '2026-11-07 19:10:56', 'admin', NULL),
(150, 2, 'Manually Opened Store', '2026-11-07 19:10:56', 'admin', NULL),
(151, 2, 'Manually Closed Store', '2026-11-07 19:10:57', 'admin', NULL),
(152, 2, 'Manually Opened Store', '2026-11-07 19:11:02', 'admin', NULL),
(153, 2, 'Manually Closed Store', '2026-11-07 19:11:08', 'admin', NULL),
(154, 2, 'Manually Opened Store', '2026-11-07 19:27:12', 'admin', NULL),
(155, 2, 'Approved Order #17', '2026-11-07 19:39:55', 'admin', NULL),
(156, 1, 'Logged in', '2026-11-09 07:38:05', 'admin', NULL),
(157, 1, 'Admin logged out', '2026-11-09 07:38:27', 'admin', NULL),
(158, 2, 'Logged in', '2026-11-09 07:38:44', 'admin', NULL),
(159, 2, 'Manually Closed Store', '2026-11-09 07:40:18', 'admin', NULL),
(160, 2, 'Manually Opened Store', '2026-11-09 07:45:16', 'admin', NULL),
(161, 2, 'Manually Closed Store', '2026-11-09 07:45:29', 'admin', NULL),
(162, 2, 'Updated product: asdasd', '2026-11-09 07:45:34', 'admin', NULL),
(163, 2, 'Admin logged out', '2026-11-09 07:47:06', 'admin', NULL),
(164, 1, 'Logged in', '2026-11-09 07:47:49', 'admin', NULL),
(165, 1, 'Admin logged out', '2026-11-09 07:48:05', 'admin', NULL),
(166, 2, 'Logged in', '2026-11-09 07:48:19', 'admin', NULL),
(167, 2, 'Manually Opened Store', '2026-11-09 07:54:53', 'admin', NULL),
(168, 2, 'Manually Closed Store', '2026-11-09 07:57:32', 'admin', NULL),
(169, 2, 'Updated product: Kkakdugii', '2026-11-09 07:57:35', 'admin', NULL),
(170, 2, 'Manually Opened Store', '2026-11-09 07:57:44', 'admin', NULL),
(171, 2, 'Suspended/Deactivated Driver #1', '2026-11-09 07:57:57', 'admin', NULL),
(172, 2, 'Manually Set Available Driver #1', '2026-11-09 07:58:03', 'admin', NULL),
(173, 2, 'Deactivated Driver #1', '2026-11-09 07:59:51', 'admin', NULL),
(174, 2, 'Activated Driver #1 (Set to Offline)', '2026-11-09 07:59:53', 'admin', NULL),
(175, 2, 'Manually Closed Store', '2026-11-09 08:01:49', 'admin', NULL),
(176, 2, 'Updated product: kylee', '2026-11-09 08:01:52', 'admin', NULL),
(177, 2, 'Manually Opened Store', '2026-11-09 08:15:32', 'admin', NULL),
(178, 2, 'Manually Closed Store', '2026-11-09 08:15:37', 'admin', NULL),
(179, 2, 'Bulk reject: 6 success, 0 failed', '2026-11-09 08:23:51', 'admin', NULL),
(180, 2, 'Manually Opened Store', '2026-11-09 08:23:55', 'admin', NULL),
(181, 2, 'Manually Closed Store', '2026-11-09 08:25:02', 'admin', NULL),
(182, 2, 'Bulk reject: 3 success, 0 failed', '2026-11-09 08:44:53', 'admin', NULL),
(183, 2, 'Manually Opened Store', '2026-11-09 08:45:20', 'admin', NULL),
(184, 2, 'Manually Closed Store', '2026-11-09 08:48:37', 'admin', NULL),
(185, 2, 'Manually Opened Store', '2026-11-09 08:50:32', 'admin', NULL),
(186, 2, 'Manually Closed Store', '2026-11-09 08:55:44', 'admin', NULL),
(187, 2, 'Manually Opened Store', '2026-11-09 09:00:39', 'admin', NULL),
(188, 2, 'Manually Closed Store', '2026-11-09 09:00:51', 'admin', NULL),
(189, 2, 'Manually Opened Store', '2026-11-09 09:02:30', 'admin', NULL),
(190, 2, 'Bulk reject: 1 success, 0 failed', '2026-11-09 09:02:40', 'admin', NULL),
(191, 2, 'Manually Closed Store', '2026-11-09 09:02:53', 'admin', NULL),
(192, 2, 'Manually Opened Store', '2026-11-09 09:03:10', 'admin', NULL),
(193, 1, 'Driver Test Driver updated Order #17 to PICKED_UP', '2026-11-09 09:20:25', 'admin', NULL),
(194, 1, 'Driver Test Driver updated Order #17 to IN_TRANSIT', '2026-11-09 09:20:29', 'admin', NULL),
(195, 1, 'Driver Test Driver updated Order #17 to DELIVERED', '2026-11-09 09:21:23', 'admin', NULL),
(196, 2, 'Logged in', '2026-11-09 15:28:07', 'admin', NULL),
(197, 2, 'Rejected Order #29', '2026-11-09 15:30:38', 'admin', NULL),
(198, 2, 'Approved Order #30', '2026-11-09 15:30:40', 'admin', NULL),
(199, 2, 'Manually assigned Order #30 to Driver #1', '2026-11-09 15:30:56', 'admin', NULL),
(200, 1, 'Driver Test Driver updated Order #30 to PICKED_UP', '2026-11-09 15:31:04', 'admin', NULL),
(201, 1, 'Driver Test Driver updated Order #30 to IN_TRANSIT', '2026-11-09 15:31:30', 'admin', NULL),
(202, 1, 'Driver Test Driver updated Order #30 to DELIVERED', '2026-11-09 15:45:01', 'admin', NULL),
(203, 2, 'Approved Order #31 (from View, No drivers)', '2026-11-09 15:46:50', 'admin', NULL),
(204, 1, 'Driver Test Driver updated Order #31 to PICKED_UP', '2026-11-09 15:47:05', 'admin', NULL),
(205, 1, 'Driver Test Driver updated Order #31 to IN_TRANSIT', '2026-11-09 15:47:10', 'admin', NULL),
(206, 1, 'Driver Test Driver updated Order #31 to DELIVERED', '2026-11-09 16:10:28', 'admin', NULL),
(207, 2, 'Approved Order #32 (from View) and Auto-Assigned to Driver #1', '2026-11-09 16:11:56', 'admin', NULL),
(208, 1, 'Driver Test Driver updated Order #32 to PICKED_UP', '2026-11-09 16:12:06', 'admin', NULL),
(209, 1, 'Driver Test Driver updated Order #32 to PICKED_UP', '2026-11-09 16:12:12', 'admin', NULL),
(210, 1, 'Driver Test Driver updated Order #32 to IN_TRANSIT', '2026-11-09 16:12:33', 'admin', NULL),
(211, 1, 'Driver Test Driver updated Order #32 to DELIVERED', '2026-11-09 16:27:04', 'admin', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `is_superadmin` tinyint(1) NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `name`, `email`, `password_hash`, `is_superadmin`, `status`, `created_at`) VALUES
(1, 'Administrator', 'admin@kimfresh.com', '$argon2id$v=19$m=65536,t=4,p=1$LmVvVG1iWUprbnkuYmo5RA$6v9fC3vOa2Y6pVwXnVPAk+ZxS7WenwDk5RhEOprD6UU', 1, 'active', '2026-11-02 11:45:57'),
(2, 'Celis Kyle', 'kylefcelis@gmail.com', '$2y$10$B0K5gMAR3O3ZbOhF/uTtce9xIFfmjnkPwzo13uLbRpgUFkNFG3dcO', 0, 'active', '2026-11-02 11:52:59'),
(3, 'johan salazar', 'johan@gmail.com', '$2y$10$pdUu7I26xiVTS2s0Va/a6ex.8gOkc/cNpsZA2qtMm5fcbU8SJNGnu', 0, 'inactive', '2026-11-02 20:24:27');

-- --------------------------------------------------------

--
-- Table structure for table `delivery`
--

CREATE TABLE `delivery` (
  `delivery_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `driver_id` int(11) DEFAULT NULL,
  `delivery_date` datetime DEFAULT current_timestamp(),
  `delivery_status` varchar(20) NOT NULL DEFAULT 'assigned'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `delivery`
--

INSERT INTO `delivery` (`delivery_id`, `order_id`, `driver_id`, `delivery_date`, `delivery_status`) VALUES
(1, 7, 1, '2026-11-02 20:44:51', 'delivered'),
(2, 16, 1, '2026-11-02 21:38:07', 'delivered'),
(3, 8, 1, '2026-11-07 12:55:22', 'delivered'),
(4, 6, 1, '2026-11-07 13:14:13', 'delivered'),
(5, 17, 1, '2026-11-09 07:58:03', 'delivered'),
(6, 30, 1, '2026-11-09 15:30:56', 'delivered'),
(7, 31, 1, '2026-11-09 15:46:56', 'delivered'),
(8, 32, 1, '2026-11-09 16:11:56', 'delivered');

-- --------------------------------------------------------

--
-- Table structure for table `driver`
--

CREATE TABLE `driver` (
  `driver_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `license_number` varchar(50) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'offline',
  `account_status` varchar(20) NOT NULL DEFAULT 'approved',
  `otp_code` varchar(6) DEFAULT NULL,
  `otp_expires_at` datetime DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `driver`
--

INSERT INTO `driver` (`driver_id`, `name`, `email`, `phone`, `password_hash`, `license_number`, `status`, `account_status`, `otp_code`, `otp_expires_at`, `rejected_at`, `created_at`) VALUES
(1, 'Test Driver', 'driver@kimfresh.com', '09123456789', '$2y$10$ico/2Bz.4Svntdx/dDxSg.W/CXjxzJY0DN/HuTKDzwuKEdkif81vq', 'N01-23-456789', 'available', 'approved', NULL, NULL, NULL, '2026-11-02 11:45:57');

-- --------------------------------------------------------

--
-- Table structure for table `driver_locations`
--

CREATE TABLE `driver_locations` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `lat` decimal(10,7) NOT NULL,
  `lng` decimal(10,7) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `attempted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `email`, `ip`, `success`, `attempted_at`) VALUES
(12, 'admin@kimfresh.com', '::1', 1, '2026-11-08 23:38:05'),
(16, 'admin@kimfresh.com', '::1', 1, '2026-11-08 23:47:49'),
(6, 'kylefcelis@gmail.com', '::1', 1, '2026-11-05 13:34:28'),
(7, 'kylefcelis@gmail.com', '::1', 1, '2026-11-05 13:35:54'),
(8, 'kylefcelis@gmail.com', '::1', 1, '2026-11-05 13:39:34'),
(9, 'kylefcelis@gmail.com', '::1', 1, '2026-11-06 08:49:21'),
(10, 'kylefcelis@gmail.com', '::1', 1, '2026-11-07 02:40:41'),
(11, 'kylefcelis@gmail.com', '::1', 1, '2026-11-07 11:00:28'),
(15, 'kylefcelis@gmail.com', '::1', 1, '2026-11-08 23:38:44'),
(17, 'kylefcelis@gmail.com', '::1', 1, '2026-11-08 23:48:19'),
(19, 'kylefcelis@gmail.com', '::1', 1, '2026-11-09 07:28:07');

-- --------------------------------------------------------

--
-- Table structure for table `order`
--

CREATE TABLE `order` (
  `order_id` int(11) NOT NULL,
  `retailer_id` int(11) DEFAULT NULL,
  `order_date` datetime DEFAULT current_timestamp(),
  `total_amount` decimal(10,2) DEFAULT NULL,
  `order_status` enum('pending','approved','rejected','cancelled','waiting_for_pickup','in_transit','delivered') NOT NULL DEFAULT 'pending',
  `driver_lat` decimal(10,7) DEFAULT NULL,
  `driver_lng` decimal(10,7) DEFAULT NULL,
  `retailer_lat` decimal(10,7) DEFAULT NULL,
  `retailer_lng` decimal(10,7) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order`
--

INSERT INTO `order` (`order_id`, `retailer_id`, `order_date`, `total_amount`, `order_status`, `driver_lat`, `driver_lng`, `retailer_lat`, `retailer_lng`) VALUES
(1, 1, '2026-11-02 16:33:14', 295.00, 'rejected', NULL, NULL, 10.3157000, 122.9500000),
(2, 1, '2026-11-02 17:01:07', 500.00, 'rejected', NULL, NULL, 10.3157000, 122.9500000),
(3, 1, '2026-11-02 17:04:53', 295.00, 'rejected', NULL, NULL, 10.3157000, 122.9500000),
(4, 1, '2026-11-02 17:12:06', 175.00, 'rejected', NULL, NULL, 10.3157000, 122.9500000),
(5, 1, '2026-11-02 17:15:30', 120.00, 'rejected', NULL, NULL, 10.3157000, 122.9500000),
(6, 1, '2026-11-02 17:17:26', 120.00, 'delivered', NULL, NULL, 10.3157000, 122.9500000),
(7, 1, '2026-11-02 17:19:10', 120.00, 'delivered', NULL, NULL, 10.3157000, 122.9500000),
(8, 1, '2026-11-02 21:06:13', 24.00, 'delivered', NULL, NULL, NULL, NULL),
(9, 1, '2026-11-02 21:09:08', 120.00, '', NULL, NULL, NULL, NULL),
(10, 1, '2026-11-02 21:09:08', 525.00, '', NULL, NULL, NULL, NULL),
(11, 1, '2026-11-01 21:09:08', 12.00, '', NULL, NULL, NULL, NULL),
(12, 1, '2026-10-30 21:09:08', 24.00, '', NULL, NULL, NULL, NULL),
(13, 1, '2026-10-28 21:09:08', 12.00, '', NULL, NULL, NULL, NULL),
(14, 1, '2026-11-02 21:11:20', 12.00, 'rejected', NULL, NULL, NULL, NULL),
(15, 1, '2026-11-02 21:12:00', 24.00, 'rejected', NULL, NULL, NULL, NULL),
(16, 1, '2026-11-02 21:12:19', 48.00, 'delivered', NULL, NULL, NULL, NULL),
(17, 1, '2026-11-07 19:39:11', 120.00, 'delivered', NULL, NULL, 14.5995000, 120.9842000),
(18, 1, '2026-11-07 19:46:08', 415.00, 'rejected', NULL, NULL, 14.5995000, 120.9842000),
(19, 1, '2026-11-09 08:15:46', 29.00, 'rejected', NULL, NULL, 14.5995000, 120.9842000),
(20, 1, '2026-11-09 08:16:00', 29.00, 'rejected', NULL, NULL, 14.5995000, 120.9842000),
(21, 1, '2026-11-09 08:19:55', 132.00, 'rejected', NULL, NULL, 14.5995000, 120.9842000),
(22, 1, '2026-11-09 08:21:14', 295.00, 'rejected', NULL, NULL, 14.5995000, 120.9842000),
(23, 1, '2026-11-09 08:23:22', 295.00, 'rejected', NULL, NULL, 14.5995000, 120.9842000),
(24, 1, '2026-11-09 08:25:14', 149.00, 'rejected', NULL, NULL, 14.5995000, 120.9842000),
(25, 1, '2026-11-09 08:25:56', 307.00, 'rejected', NULL, NULL, 14.5995000, 120.9842000),
(26, 1, '2026-11-09 08:28:17', 149.00, 'rejected', NULL, NULL, 14.5995000, 120.9842000),
(27, 1, '2026-11-09 08:45:34', 120.00, 'rejected', NULL, NULL, 14.5995000, 120.9842000),
(28, 1, '2026-11-09 09:03:19', 12.00, 'cancelled', NULL, NULL, 14.5995000, 120.9842000),
(29, 1, '2026-11-09 09:18:54', 187.00, 'rejected', NULL, NULL, 14.5995000, 120.9842000),
(30, 1, '2026-11-09 15:30:28', 295.00, 'delivered', NULL, NULL, 14.6877017, 121.0647283),
(31, 1, '2026-11-09 15:46:43', 295.00, 'delivered', NULL, NULL, 14.6877017, 121.0647283),
(32, 1, '2026-11-09 16:11:45', 295.00, 'delivered', 14.5995000, 120.9842000, 14.6877017, 121.0647283);

-- --------------------------------------------------------

--
-- Table structure for table `order_item`
--

CREATE TABLE `order_item` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_item`
--

INSERT INTO `order_item` (`order_item_id`, `order_id`, `product_id`, `quantity`, `unit_price`, `subtotal`) VALUES
(14, 7, 17, 1, 120.00, 120.00),
(15, 6, 17, 1, 120.00, 120.00),
(16, 5, 17, 1, 120.00, 120.00),
(17, 4, 16, 1, 175.00, 175.00),
(18, 3, 16, 1, 175.00, 175.00),
(19, 3, 17, 1, 120.00, 120.00),
(20, 2, 16, 2, 175.00, 350.00),
(21, 2, 17, 1, 120.00, 120.00),
(22, 1, 16, 1, 175.00, 175.00),
(23, 1, 17, 1, 120.00, 120.00),
(24, 8, 15, 2, 12.00, 24.00),
(25, 9, 17, 1, 120.00, 120.00),
(26, 10, 16, 3, 175.00, 525.00),
(27, 11, 15, 1, 12.00, 12.00),
(28, 12, 15, 2, 12.00, 24.00),
(29, 13, 15, 1, 12.00, 12.00),
(30, 14, 15, 1, 12.00, 12.00),
(31, 15, 15, 2, 12.00, 24.00),
(32, 16, 15, 4, 12.00, 48.00),
(33, 17, 17, 1, 120.00, NULL),
(34, 18, 16, 1, 175.00, NULL),
(35, 18, 17, 2, 120.00, NULL),
(36, 19, 22, 1, 29.00, NULL),
(37, 20, 22, 1, 29.00, NULL),
(38, 21, 15, 1, 12.00, NULL),
(39, 21, 17, 1, 120.00, NULL),
(40, 22, 16, 1, 175.00, NULL),
(41, 22, 17, 1, 120.00, NULL),
(42, 23, 16, 1, 175.00, NULL),
(43, 23, 17, 1, 120.00, NULL),
(44, 24, 17, 1, 120.00, NULL),
(45, 24, 22, 1, 29.00, NULL),
(46, 25, 15, 1, 12.00, NULL),
(47, 25, 16, 1, 175.00, NULL),
(48, 25, 17, 1, 120.00, NULL),
(49, 26, 22, 1, 29.00, NULL),
(50, 26, 17, 1, 120.00, NULL),
(51, 27, 17, 1, 120.00, NULL),
(52, 28, 15, 1, 12.00, NULL),
(53, 29, 15, 1, 12.00, NULL),
(54, 29, 16, 1, 175.00, NULL),
(55, 30, 16, 1, 175.00, NULL),
(56, 30, 17, 1, 120.00, NULL),
(57, 31, 17, 1, 120.00, NULL),
(58, 31, 16, 1, 175.00, NULL),
(59, 32, 16, 1, 175.00, NULL),
(60, 32, 17, 1, 120.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

CREATE TABLE `product` (
  `product_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `sku` varchar(50) DEFAULT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `reorder_level` int(11) NOT NULL DEFAULT 5,
  `status` varchar(20) NOT NULL DEFAULT 'available',
  `created_at` datetime DEFAULT current_timestamp(),
  `shelf_life_days` int(11) DEFAULT 30,
  `expiry_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product`
--

INSERT INTO `product` (`product_id`, `name`, `description`, `sku`, `unit_price`, `stock_quantity`, `reorder_level`, `status`, `created_at`, `shelf_life_days`, `expiry_date`) VALUES
(15, 'asdasd', '', '', 12.00, 22, 12, 'available', '2026-11-02 18:48:21', 30, '2026-12-09'),
(16, 'Baechu Kimchi', NULL, 'KIM-001', 175.00, 42, 10, 'available', '2026-11-02 18:48:26', 30, '2026-12-07'),
(17, 'Kkakdugii', '', '', 120.00, 42, 8, 'available', '2026-11-02 18:48:26', 30, '2026-12-09'),
(22, 'kylee', 'qwewqeqwesad', '', 29.00, 10, 30, 'expired', '2026-11-07 11:15:22', 30, '2026-12-09');

-- --------------------------------------------------------

--
-- Table structure for table `retailer`
--

CREATE TABLE `retailer` (
  `retailer_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `customer_code` varchar(10) DEFAULT NULL,
  `lat` decimal(10,7) DEFAULT NULL,
  `lng` decimal(10,7) DEFAULT NULL,
  `account_status` varchar(20) DEFAULT 'approved',
  `otp_code` varchar(6) DEFAULT NULL,
  `otp_expires_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `retailer`
--

INSERT INTO `retailer` (`retailer_id`, `name`, `email`, `phone`, `password_hash`, `address`, `customer_code`, `lat`, `lng`, `account_status`, `otp_code`, `otp_expires_at`, `created_at`) VALUES
(1, 'Fresh Retailer', 'test@kimfresh.com', '09123456789', '$2y$10$CXlccgBnx9RbDR47KMkJ3.PcqgQpBSPAx583t9QtlFZ9uM9CfM/4u', 'Bacolod City', '888888', 10.3157000, 123.8854000, 'approved', NULL, NULL, '2026-11-02 11:45:57');

-- --------------------------------------------------------

--
-- Table structure for table `sensor_data`
--

CREATE TABLE `sensor_data` (
  `sensor_id` int(11) NOT NULL,
  `delivery_id` int(11) DEFAULT NULL,
  `temperature` decimal(5,2) DEFAULT NULL,
  `humidity` decimal(5,2) DEFAULT NULL,
  `is_alert` tinyint(1) NOT NULL DEFAULT 0,
  `reading_timestamp` datetime DEFAULT current_timestamp(),
  `synced_at` datetime DEFAULT current_timestamp(),
  `client_dedup_key` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `store_settings`
--

CREATE TABLE `store_settings` (
  `id` int(11) NOT NULL DEFAULT 1,
  `open_time` time NOT NULL DEFAULT '08:00:00',
  `close_time` time NOT NULL DEFAULT '17:00:00',
  `manual_override` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `store_settings`
--

INSERT INTO `store_settings` (`id`, `open_time`, `close_time`, `manual_override`) VALUES
(1, '00:00:00', '23:59:59', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`log_id`);

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `uq_admin_email` (`email`);

--
-- Indexes for table `delivery`
--
ALTER TABLE `delivery`
  ADD PRIMARY KEY (`delivery_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `driver_id` (`driver_id`);

--
-- Indexes for table `driver`
--
ALTER TABLE `driver`
  ADD PRIMARY KEY (`driver_id`),
  ADD UNIQUE KEY `uq_driver_email` (`email`);

--
-- Indexes for table `driver_locations`
--
ALTER TABLE `driver_locations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lookup` (`email`,`ip`,`success`,`attempted_at`),
  ADD KEY `idx_cleanup` (`attempted_at`);

--
-- Indexes for table `order`
--
ALTER TABLE `order`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `retailer_id` (`retailer_id`);

--
-- Indexes for table `order_item`
--
ALTER TABLE `order_item`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`product_id`);

--
-- Indexes for table `retailer`
--
ALTER TABLE `retailer`
  ADD PRIMARY KEY (`retailer_id`),
  ADD UNIQUE KEY `uq_retailer_email` (`email`);

--
-- Indexes for table `sensor_data`
--
ALTER TABLE `sensor_data`
  ADD PRIMARY KEY (`sensor_id`),
  ADD UNIQUE KEY `client_dedup_key` (`client_dedup_key`),
  ADD KEY `delivery_id` (`delivery_id`);

--
-- Indexes for table `store_settings`
--
ALTER TABLE `store_settings`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=212;

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `delivery`
--
ALTER TABLE `delivery`
  MODIFY `delivery_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `driver`
--
ALTER TABLE `driver`
  MODIFY `driver_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `driver_locations`
--
ALTER TABLE `driver_locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `order`
--
ALTER TABLE `order`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `order_item`
--
ALTER TABLE `order_item`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `retailer`
--
ALTER TABLE `retailer`
  MODIFY `retailer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sensor_data`
--
ALTER TABLE `sensor_data`
  MODIFY `sensor_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `delivery`
--
ALTER TABLE `delivery`
  ADD CONSTRAINT `delivery_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `order` (`order_id`),
  ADD CONSTRAINT `delivery_ibfk_2` FOREIGN KEY (`driver_id`) REFERENCES `driver` (`driver_id`);

--
-- Constraints for table `order`
--
ALTER TABLE `order`
  ADD CONSTRAINT `order_ibfk_1` FOREIGN KEY (`retailer_id`) REFERENCES `retailer` (`retailer_id`);

--
-- Constraints for table `order_item`
--
ALTER TABLE `order_item`
  ADD CONSTRAINT `order_item_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `order` (`order_id`),
  ADD CONSTRAINT `order_item_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`);

--
-- Constraints for table `sensor_data`
--
ALTER TABLE `sensor_data`
  ADD CONSTRAINT `sensor_data_ibfk_1` FOREIGN KEY (`delivery_id`) REFERENCES `delivery` (`delivery_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
