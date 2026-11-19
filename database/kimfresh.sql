-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 19, 2026 at 04:50 AM
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
(211, 1, 'Driver Test Driver updated Order #32 to DELIVERED', '2026-11-09 16:27:04', 'admin', NULL),
(212, 2, 'Logged in', '2026-11-11 07:34:42', 'admin', NULL),
(213, 2, 'Logged in', '2026-11-13 10:30:31', 'admin', NULL),
(214, 2, 'Approved Order #1', '2026-11-13 10:32:37', 'admin', NULL),
(215, 2, 'Manually assigned Order #1 to Driver #1', '2026-11-13 10:33:02', 'admin', NULL),
(216, 1, 'Driver Test Driver updated Order #1 to PICKED_UP', '2026-11-13 10:33:14', 'admin', NULL),
(217, 1, 'Driver Test Driver updated Order #1 to IN_TRANSIT', '2026-11-13 10:33:20', 'admin', NULL),
(218, 2, 'Manually Closed Store', '2026-11-13 11:31:52', 'admin', NULL),
(219, 2, 'Manually Opened Store', '2026-11-13 11:32:15', 'admin', NULL),
(220, 1, 'Driver Test Driver updated Order #1 to DELIVERED', '2026-11-13 11:32:52', 'admin', NULL),
(221, 2, 'Approved Order #33', '2026-11-13 11:32:59', 'admin', NULL),
(222, 1, 'Driver Test Driver updated Order #33 to PICKED_UP', '2026-11-13 11:33:16', 'admin', NULL),
(223, 1, 'Driver Test Driver updated Order #33 to IN_TRANSIT', '2026-11-13 11:33:21', 'admin', NULL),
(224, 1, 'Driver Test Driver updated Order #33 to DELIVERED', '2026-11-13 11:33:25', 'admin', NULL),
(225, 2, 'Logged in', '2026-11-13 14:36:44', 'admin', NULL),
(226, 2, 'Approved Order #34', '2026-11-13 14:36:55', 'admin', NULL),
(227, 1, 'Driver Test Driver updated Order #34 to PICKED_UP', '2026-11-13 14:37:58', 'admin', NULL),
(228, 1, 'Driver Test Driver updated Order #34 to IN_TRANSIT', '2026-11-13 14:38:00', 'admin', NULL),
(229, 1, 'Driver Test Driver updated Order #34 to DELIVERED', '2026-11-13 14:38:01', 'admin', NULL),
(230, 2, 'Admin logged out', '2026-11-13 15:35:58', 'admin', NULL),
(231, 1, 'Logged in', '2026-11-13 15:36:10', 'admin', NULL),
(232, 1, 'Admin logged out', '2026-11-13 20:17:24', 'admin', NULL),
(233, 1, 'Logged in', '2026-11-13 20:30:29', 'admin', NULL),
(234, 1, 'Admin logged out', '2026-11-13 20:33:20', 'admin', NULL),
(235, 1, 'Logged in', '2026-11-13 20:35:56', 'admin', NULL),
(236, 1, 'Admin logged out', '2026-11-13 20:35:57', 'admin', NULL),
(237, 1, 'Logged in', '2026-11-13 20:38:43', 'admin', NULL),
(238, 1, 'Admin logged out', '2026-11-13 20:38:46', 'admin', NULL),
(239, 2, 'Logged in', '2026-11-13 21:10:45', 'admin', NULL),
(240, 2, 'Logged in', '2026-11-15 07:48:55', 'admin', NULL),
(241, 2, 'Manually Closed Store', '2026-11-15 07:59:48', 'admin', NULL),
(242, 2, 'Manually Opened Store', '2026-11-15 08:00:09', 'admin', NULL),
(243, 2, 'Logged in', '2026-11-15 18:53:01', 'admin', NULL),
(244, 2, 'Manually Closed Store', '2026-11-15 18:56:40', 'admin', NULL),
(245, 2, 'Manually Opened Store', '2026-11-15 18:56:41', 'admin', NULL),
(246, 2, 'Manually Closed Store', '2026-11-15 18:57:14', 'admin', NULL),
(247, 2, 'Exported Products to PDF', '2026-11-15 19:13:17', 'admin', NULL),
(248, 2, 'Manually Opened Store', '2026-11-15 19:13:37', 'admin', NULL),
(249, 2, 'Manually Closed Store', '2026-11-15 19:13:38', 'admin', NULL),
(250, 2, 'Updated product: kylee', '2026-11-15 19:16:31', 'admin', NULL),
(251, 2, 'Added new product: asdasdasdasd', '2026-11-15 19:25:49', 'admin', NULL),
(252, 2, 'Permanently Deleted Product: asdasdasdasd', '2026-11-15 19:25:53', 'admin', NULL),
(253, 2, 'Updated product: Kkakdugii', '2026-11-15 20:11:34', 'admin', NULL),
(254, 2, 'Admin logged out', '2026-11-15 20:19:34', 'admin', NULL),
(255, 2, 'Logged in', '2026-11-15 20:19:38', 'admin', NULL),
(256, 2, 'Logged in', '2026-11-15 20:19:49', 'admin', NULL),
(257, 2, 'Manually Opened Store', '2026-11-15 20:37:06', 'admin', NULL),
(258, 2, 'Reset Store to Auto Schedule', '2026-11-15 20:37:09', 'admin', NULL),
(259, 2, 'Admin logged out', '2026-11-15 20:46:42', 'admin', NULL),
(260, 1, 'Logged in', '2026-11-15 20:46:50', 'admin', NULL),
(261, 1, 'Admin logged out', '2026-11-15 20:46:54', 'admin', NULL),
(262, 2, 'Logged in', '2026-11-15 20:47:01', 'admin', NULL),
(263, 2, 'Exported Products to PDF', '2026-11-15 20:50:30', 'admin', NULL),
(264, 2, 'Exported Products to CSV', '2026-11-15 20:50:32', 'admin', NULL),
(265, 2, 'Exported Drivers to PDF', '2026-11-15 20:50:37', 'admin', NULL),
(266, 2, 'Exported Orders to PDF', '2026-11-15 20:51:38', 'admin', NULL),
(267, 2, 'Exported Drivers to PDF', '2026-11-15 21:09:07', 'admin', NULL),
(268, 2, 'Exported Orders to PDF', '2026-11-15 21:09:16', 'admin', NULL),
(269, 2, 'Admin logged out', '2026-11-15 21:13:09', 'admin', NULL),
(270, 2, 'Logged in', '2026-11-15 21:14:07', 'admin', NULL),
(271, 2, 'Exported Order #34 Receipt to PDF', '2026-11-15 21:23:35', 'admin', NULL),
(272, 2, 'Admin logged out', '2026-11-15 22:03:09', 'admin', NULL),
(273, 2, 'Logged in', '2026-11-15 23:05:57', 'admin', NULL),
(274, 2, 'Manually Closed Store', '2026-11-15 23:06:27', 'admin', NULL),
(275, 2, 'Manually Opened Store', '2026-11-15 23:06:28', 'admin', NULL),
(276, 2, 'Manually Closed Store', '2026-11-15 23:06:29', 'admin', NULL),
(277, 2, 'Manually Opened Store', '2026-11-15 23:06:33', 'admin', NULL),
(278, 2, 'Manually Closed Store', '2026-11-15 23:08:02', 'admin', NULL),
(279, 2, 'Manually Opened Store', '2026-11-15 23:08:03', 'admin', NULL),
(280, 2, 'Manually Closed Store', '2026-11-15 23:08:04', 'admin', NULL),
(281, 2, 'Exported Activity Logs to PDF', '2026-11-15 23:13:39', 'admin', NULL),
(282, 2, 'Manually Opened Store', '2026-11-15 23:18:18', 'admin', NULL),
(283, 2, 'Approved Order #35', '2026-11-15 23:24:25', 'admin', NULL),
(284, 1, 'Driver Test Driver updated Order #35 to PICKED_UP', '2026-11-15 23:25:15', 'admin', NULL),
(285, 1, 'Driver Test Driver updated Order #35 to IN_TRANSIT', '2026-11-15 23:25:19', 'admin', NULL),
(286, 1, 'Driver Test Driver updated Order #35 to DELIVERED', '2026-11-15 23:25:21', 'admin', NULL),
(287, 2, 'Logged in', '2026-11-16 20:09:36', 'admin', NULL),
(288, 2, 'Logged in', '2026-11-17 16:31:34', 'admin', NULL),
(289, 2, 'Logged in', '2026-11-18 18:45:28', 'admin', NULL),
(290, 2, 'Approved Order #36', '2026-11-18 18:46:04', 'admin', NULL),
(291, 1, 'Driver Test Driver updated Order #36 to PICKED_UP', '2026-11-18 18:48:19', 'admin', NULL),
(292, 1, 'Driver Test Driver updated Order #36 to IN_TRANSIT', '2026-11-18 18:48:21', 'admin', NULL),
(293, 1, 'Driver Test Driver uploaded proof of delivery for Order #36', '2026-11-18 19:00:29', 'admin', NULL),
(294, 2, 'Approved Order #37', '2026-11-18 20:05:45', 'admin', NULL),
(295, 1, 'Driver Test Driver updated Order #37 to PICKED_UP', '2026-11-18 20:30:57', 'admin', NULL),
(296, 1, 'Driver Test Driver updated Order #37 to IN_TRANSIT', '2026-11-18 20:32:41', 'admin', NULL),
(297, 1, 'Driver Test Driver uploaded proof of delivery for Order #37', '2026-11-18 20:33:01', 'admin', NULL),
(298, 2, 'Approved Order #38', '2026-11-18 20:34:33', 'admin', NULL),
(299, 1, 'Driver Test Driver updated Order #38 to PICKED_UP', '2026-11-18 20:35:21', 'admin', NULL),
(300, 1, 'Driver Test Driver updated Order #38 to IN_TRANSIT', '2026-11-18 20:35:23', 'admin', NULL),
(301, 1, 'Driver Test Driver uploaded proof of delivery for Order #38', '2026-11-18 20:35:36', 'admin', NULL),
(302, 2, 'Manually Closed Store', '2026-11-18 20:43:16', 'admin', NULL),
(303, 2, 'Manually Opened Store', '2026-11-18 20:43:37', 'admin', NULL),
(304, 2, 'Approved Order #39', '2026-11-18 20:45:06', 'admin', NULL),
(305, 1, 'Driver Test Driver updated Order #39 to PICKED_UP', '2026-11-18 20:46:02', 'admin', NULL),
(306, 1, 'Driver Test Driver updated Order #39 to IN_TRANSIT', '2026-11-18 20:46:08', 'admin', NULL),
(307, 1, 'Driver Test Driver uploaded proof of delivery for Order #39', '2026-11-19 00:55:17', 'admin', NULL),
(308, 2, 'Approved Order #40', '2026-11-19 01:18:51', 'admin', NULL),
(309, 1, 'Driver Test Driver updated Order #40 to PICKED_UP', '2026-11-19 01:19:35', 'admin', NULL),
(310, 1, 'Driver Test Driver updated Order #40 to PICKED_UP', '2026-11-19 01:19:40', 'admin', NULL),
(311, 1, 'Driver Test Driver updated Order #40 to IN_TRANSIT', '2026-11-19 01:19:42', 'admin', NULL),
(312, 2, 'Logged in', '2026-11-19 09:47:33', 'admin', NULL),
(313, 2, 'Logged in', '2026-11-19 09:48:52', 'admin', NULL),
(314, 2, 'Admin logged out', '2026-11-19 09:50:48', 'admin', NULL);

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
  `delivery_status` varchar(20) NOT NULL DEFAULT 'assigned',
  `proof_photo` varchar(255) DEFAULT NULL,
  `proof_uploaded_at` datetime DEFAULT NULL,
  `picked_up_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `delivery`
--

INSERT INTO `delivery` (`delivery_id`, `order_id`, `driver_id`, `delivery_date`, `delivery_status`, `proof_photo`, `proof_uploaded_at`, `picked_up_at`, `delivered_at`) VALUES
(1, 7, 1, '2026-11-02 20:44:51', 'delivered', NULL, NULL, NULL, NULL),
(2, 16, 1, '2026-11-02 21:38:07', 'delivered', NULL, NULL, NULL, NULL),
(3, 8, 1, '2026-11-07 12:55:22', 'delivered', NULL, NULL, NULL, NULL),
(4, 6, 1, '2026-11-07 13:14:13', 'delivered', NULL, NULL, NULL, NULL),
(5, 17, 1, '2026-11-09 07:58:03', 'delivered', NULL, NULL, NULL, NULL),
(6, 30, 1, '2026-11-09 15:30:56', 'delivered', NULL, NULL, NULL, NULL),
(7, 31, 1, '2026-11-09 15:46:56', 'delivered', NULL, NULL, NULL, NULL),
(8, 32, 1, '2026-11-09 16:11:56', 'delivered', NULL, NULL, NULL, NULL),
(9, 1, 1, '2026-11-13 10:33:02', 'delivered', NULL, NULL, NULL, NULL),
(10, 33, 1, '2026-11-13 11:33:09', 'delivered', NULL, NULL, NULL, NULL),
(11, 34, 1, '2026-11-13 14:37:42', 'delivered', NULL, NULL, NULL, NULL),
(12, 35, 1, '2026-11-15 23:25:06', 'delivered', NULL, NULL, NULL, NULL),
(13, 36, 1, '2026-11-18 18:46:11', 'delivered', '/php-web/public/uploads/proofs/delivery_13_1794999629.jpg', '2026-11-18 19:00:29', NULL, NULL),
(14, 37, 1, '2026-11-18 20:30:33', 'delivered', '/php-web/public/uploads/proofs/delivery_14_1795005181.jpg', '2026-11-18 20:33:01', NULL, NULL),
(15, 38, 1, '2026-11-18 20:35:00', 'delivered', '/php-web/public/uploads/proofs/delivery_15_1795005336.jpg', '2026-11-18 20:35:36', NULL, NULL),
(16, 39, 1, '2026-11-18 20:45:36', 'delivered', '/php-web/public/uploads/proofs/delivery_16_1795020917.jpg', '2026-11-19 00:55:17', NULL, NULL),
(17, 40, 1, '2026-11-19 01:19:22', 'in_transit', NULL, NULL, '2026-11-19 01:19:40', NULL);

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
(1, 'Test Driver', 'driver@kimfresh.com', '09123456789', '$2y$10$ico/2Bz.4Svntdx/dDxSg.W/CXjxzJY0DN/HuTKDzwuKEdkif81vq', 'N01-23-456789', 'offline', 'approved', NULL, NULL, NULL, '2026-11-02 11:45:57');

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

--
-- Dumping data for table `driver_locations`
--

INSERT INTO `driver_locations` (`id`, `order_id`, `lat`, `lng`, `created_at`) VALUES
(1, 39, 10.6760000, 122.9840000, '2026-11-19 00:50:45'),
(2, 39, 10.6743500, 122.9834500, '2026-11-19 01:03:41'),
(3, 40, 10.6743022, 122.9834148, '2026-11-19 01:24:22'),
(4, 40, 10.6743000, 122.9834166, '2026-11-19 01:25:03'),
(5, 40, 10.6743108, 122.9834195, '2026-11-19 01:25:15'),
(6, 40, 10.6743025, 122.9834172, '2026-11-19 01:25:25'),
(7, 40, 10.6743032, 122.9834157, '2026-11-19 01:25:35'),
(8, 40, 10.6743033, 122.9834152, '2026-11-19 01:25:45'),
(9, 40, 10.6743039, 122.9834161, '2026-11-19 01:25:55'),
(10, 40, 10.6743035, 122.9834160, '2026-11-19 01:26:05'),
(11, 40, 10.6743030, 122.9834154, '2026-11-19 01:26:15'),
(12, 40, 10.6743031, 122.9834156, '2026-11-19 01:26:25'),
(13, 40, 10.6743031, 122.9834153, '2026-11-19 01:26:35'),
(14, 40, 10.6743031, 122.9834152, '2026-11-19 01:26:45'),
(15, 40, 10.6743030, 122.9834150, '2026-11-19 01:26:55'),
(16, 40, 10.6743028, 122.9834150, '2026-11-19 01:27:05'),
(17, 40, 10.6743031, 122.9834150, '2026-11-19 01:27:15'),
(18, 40, 10.6743017, 122.9834145, '2026-11-19 01:27:22'),
(19, 40, 10.6743037, 122.9834198, '2026-11-19 01:27:35'),
(20, 40, 10.6743030, 122.9834237, '2026-11-19 01:28:17'),
(21, 40, 10.6743099, 122.9834233, '2026-11-19 01:28:29'),
(22, 40, 10.6743194, 122.9834319, '2026-11-19 01:28:40'),
(23, 40, 10.6743230, 122.9834313, '2026-11-19 01:28:49'),
(24, 40, 10.6743180, 122.9834229, '2026-11-19 01:28:59'),
(25, 40, 10.6743161, 122.9834231, '2026-11-19 01:29:09'),
(26, 40, 10.6743142, 122.9834222, '2026-11-19 01:29:20'),
(27, 40, 10.6743136, 122.9834222, '2026-11-19 01:29:29'),
(28, 40, 10.6743135, 122.9834221, '2026-11-19 01:29:39'),
(29, 40, 10.6743131, 122.9834221, '2026-11-19 01:29:49'),
(30, 40, 10.6743124, 122.9834219, '2026-11-19 01:29:59'),
(31, 40, 10.6743121, 122.9834220, '2026-11-19 01:30:09'),
(32, 40, 10.6743114, 122.9834211, '2026-11-19 01:30:19'),
(33, 40, 10.6743112, 122.9834208, '2026-11-19 01:30:29'),
(34, 40, 10.6743098, 122.9834201, '2026-11-19 01:30:39'),
(35, 40, 10.6743089, 122.9834198, '2026-11-19 01:30:49'),
(36, 40, 10.6743086, 122.9834199, '2026-11-19 01:30:59'),
(37, 40, 10.6743086, 122.9834200, '2026-11-19 01:31:09'),
(38, 40, 10.6743088, 122.9834201, '2026-11-19 01:31:19'),
(39, 40, 10.6743088, 122.9834202, '2026-11-19 01:31:29'),
(40, 40, 10.6743083, 122.9834200, '2026-11-19 01:31:39'),
(41, 40, 10.6743081, 122.9834201, '2026-11-19 01:31:49'),
(42, 40, 10.6743080, 122.9834201, '2026-11-19 01:31:59'),
(43, 40, 10.6743079, 122.9834200, '2026-11-19 01:32:09'),
(44, 40, 10.6743078, 122.9834200, '2026-11-19 01:32:20'),
(45, 40, 10.6743075, 122.9834200, '2026-11-19 01:32:29'),
(46, 40, 10.6743074, 122.9834199, '2026-11-19 01:32:39'),
(47, 40, 10.6743073, 122.9834200, '2026-11-19 01:32:49'),
(48, 40, 10.6743071, 122.9834200, '2026-11-19 01:32:59'),
(49, 40, 10.6743067, 122.9834199, '2026-11-19 01:33:09'),
(50, 40, 10.6743065, 122.9834198, '2026-11-19 01:33:19'),
(51, 40, 10.6743065, 122.9834197, '2026-11-19 01:33:29'),
(52, 40, 10.6743064, 122.9834195, '2026-11-19 01:33:39'),
(53, 40, 10.6743062, 122.9834195, '2026-11-19 01:33:49'),
(54, 40, 10.6743061, 122.9834194, '2026-11-19 01:33:59'),
(55, 40, 10.6743060, 122.9834194, '2026-11-19 01:34:09'),
(56, 40, 10.6743061, 122.9834194, '2026-11-19 01:34:19'),
(57, 40, 10.6743062, 122.9834194, '2026-11-19 01:34:29'),
(58, 40, 10.6743061, 122.9834194, '2026-11-19 01:34:39'),
(59, 40, 10.6743060, 122.9834193, '2026-11-19 01:34:49'),
(60, 40, 10.6743059, 122.9834192, '2026-11-19 01:34:59'),
(61, 40, 10.6743058, 122.9834192, '2026-11-19 01:35:09'),
(62, 40, 10.6743057, 122.9834192, '2026-11-19 01:35:19'),
(63, 40, 10.6743056, 122.9834191, '2026-11-19 01:35:29'),
(64, 40, 10.6743054, 122.9834192, '2026-11-19 01:35:40'),
(65, 40, 10.6743052, 122.9834192, '2026-11-19 01:35:49'),
(66, 40, 10.6743051, 122.9834192, '2026-11-19 01:35:59'),
(67, 40, 10.6743051, 122.9834192, '2026-11-19 01:36:09'),
(68, 40, 10.6743050, 122.9834192, '2026-11-19 01:36:19'),
(69, 40, 10.6743048, 122.9834193, '2026-11-19 01:36:29'),
(70, 40, 10.6743045, 122.9834192, '2026-11-19 01:36:39'),
(71, 40, 10.6743044, 122.9834192, '2026-11-19 01:36:49'),
(72, 40, 10.6743043, 122.9834192, '2026-11-19 01:37:00'),
(73, 40, 10.6743044, 122.9834192, '2026-11-19 01:37:10'),
(74, 40, 10.6743044, 122.9834190, '2026-11-19 01:37:20'),
(75, 40, 10.6743044, 122.9834190, '2026-11-19 01:37:29'),
(76, 40, 10.6743043, 122.9834190, '2026-11-19 01:37:39'),
(77, 40, 10.6743042, 122.9834191, '2026-11-19 01:37:49'),
(78, 40, 10.6743039, 122.9834189, '2026-11-19 01:37:59'),
(79, 40, 10.6743037, 122.9834189, '2026-11-19 01:38:09'),
(80, 40, 10.6743037, 122.9834189, '2026-11-19 01:38:19'),
(81, 40, 10.6743037, 122.9834189, '2026-11-19 01:38:29'),
(82, 40, 10.6743036, 122.9834188, '2026-11-19 01:38:39'),
(83, 40, 10.6743035, 122.9834187, '2026-11-19 01:38:49'),
(84, 40, 10.6743034, 122.9834186, '2026-11-19 01:38:59'),
(85, 40, 10.6743034, 122.9834186, '2026-11-19 01:39:09'),
(86, 40, 10.6743033, 122.9834185, '2026-11-19 01:39:19'),
(87, 40, 10.6743032, 122.9834184, '2026-11-19 01:39:29'),
(88, 40, 10.6743031, 122.9834183, '2026-11-19 01:39:39'),
(89, 40, 10.6743030, 122.9834183, '2026-11-19 01:39:49'),
(90, 40, 10.6743030, 122.9834184, '2026-11-19 01:39:59'),
(91, 40, 10.6743027, 122.9834183, '2026-11-19 01:40:10'),
(92, 40, 10.6743027, 122.9834183, '2026-11-19 01:40:19'),
(93, 40, 10.6743026, 122.9834183, '2026-11-19 01:40:29'),
(94, 40, 10.6743025, 122.9834184, '2026-11-19 01:40:39'),
(95, 40, 10.6743025, 122.9834184, '2026-11-19 01:40:49'),
(96, 40, 10.6743024, 122.9834184, '2026-11-19 01:40:59'),
(97, 40, 10.6743023, 122.9834185, '2026-11-19 01:41:09'),
(98, 40, 10.6743023, 122.9834185, '2026-11-19 01:41:19'),
(99, 40, 10.6743021, 122.9834184, '2026-11-19 01:41:29'),
(100, 40, 10.6743022, 122.9834183, '2026-11-19 01:41:39'),
(101, 40, 10.6743019, 122.9834183, '2026-11-19 01:41:49'),
(102, 40, 10.6743018, 122.9834183, '2026-11-19 01:41:59'),
(103, 40, 10.6743019, 122.9834182, '2026-11-19 01:42:09'),
(104, 40, 10.6743019, 122.9834182, '2026-11-19 01:42:19'),
(105, 40, 10.6743019, 122.9834182, '2026-11-19 01:42:29'),
(106, 40, 10.6743014, 122.9834184, '2026-11-19 01:42:39'),
(107, 40, 10.6743014, 122.9834158, '2026-11-19 01:42:49'),
(108, 40, 10.6743003, 122.9834207, '2026-11-19 01:42:59'),
(109, 40, 10.6743096, 122.9834172, '2026-11-19 01:43:09'),
(110, 40, 10.6745121, 122.9833516, '2026-11-19 01:43:19'),
(111, 40, 10.6743145, 122.9834178, '2026-11-19 01:43:33'),
(112, 40, 10.6743145, 122.9834178, '2026-11-19 01:43:35'),
(113, 40, 10.6743007, 122.9834165, '2026-11-19 01:43:49'),
(114, 40, 10.6742961, 122.9834139, '2026-11-19 01:43:59'),
(115, 40, 10.6743873, 122.9833044, '2026-11-19 01:44:09'),
(116, 40, 10.6743357, 122.9833766, '2026-11-19 01:44:20'),
(117, 40, 10.6743299, 122.9833730, '2026-11-19 01:44:29'),
(118, 40, 10.6743348, 122.9833568, '2026-11-19 01:44:38'),
(119, 40, 10.6743619, 122.9833170, '2026-11-19 01:44:49'),
(120, 40, 10.6743681, 122.9833106, '2026-11-19 01:44:58'),
(121, 40, 10.6743670, 122.9833146, '2026-11-19 01:45:09'),
(122, 40, 10.6743326, 122.9833506, '2026-11-19 01:45:21'),
(123, 40, 10.6743326, 122.9833506, '2026-11-19 01:45:25'),
(124, 40, 10.6742983, 122.9834215, '2026-11-19 01:45:39'),
(125, 40, 10.6742940, 122.9834250, '2026-11-19 01:45:49'),
(126, 40, 10.6743023, 122.9834242, '2026-11-19 01:45:59'),
(127, 40, 10.6742977, 122.9834214, '2026-11-19 01:46:09'),
(128, 40, 10.6742979, 122.9834213, '2026-11-19 01:46:19'),
(129, 40, 10.6742978, 122.9834214, '2026-11-19 01:46:29'),
(130, 40, 10.6742980, 122.9834213, '2026-11-19 01:46:40'),
(131, 40, 10.6742979, 122.9834208, '2026-11-19 01:46:49'),
(132, 40, 10.6742979, 122.9834213, '2026-11-19 01:46:59'),
(133, 40, 10.6742975, 122.9834204, '2026-11-19 01:47:09'),
(134, 40, 10.6742972, 122.9834197, '2026-11-19 01:47:19'),
(135, 40, 10.6742971, 122.9834193, '2026-11-19 01:47:29');

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
(61, 'admin@kimfresh.com', '::1', 0, '2026-11-19 01:51:19'),
(62, 'admin@kimfresh.com', '::1', 0, '2026-11-19 01:51:28'),
(47, 'admin@kimfresh.com', '::1', 1, '2026-11-15 12:46:50'),
(39, 'kylefcelis@gmail.com', '::1', 1, '2026-11-14 23:48:55'),
(41, 'kylefcelis@gmail.com', '::1', 1, '2026-11-15 10:53:01'),
(45, 'kylefcelis@gmail.com', '::1', 1, '2026-11-15 12:19:38'),
(46, 'kylefcelis@gmail.com', '::1', 1, '2026-11-15 12:19:49'),
(48, 'kylefcelis@gmail.com', '::1', 1, '2026-11-15 12:47:01'),
(49, 'kylefcelis@gmail.com', '::1', 1, '2026-11-15 13:14:07'),
(50, 'kylefcelis@gmail.com', '::1', 1, '2026-11-15 15:05:57'),
(52, 'kylefcelis@gmail.com', '::1', 1, '2026-11-16 12:09:36'),
(54, 'kylefcelis@gmail.com', '::1', 1, '2026-11-17 08:31:34'),
(56, 'kylefcelis@gmail.com', '::1', 1, '2026-11-18 10:45:28'),
(59, 'kylefcelis@gmail.com', '::1', 1, '2026-11-19 01:47:33'),
(60, 'kylefcelis@gmail.com', '::1', 1, '2026-11-19 01:48:52');

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
(1, 1, '2026-11-02 16:33:14', 295.00, 'delivered', NULL, NULL, 10.3157000, 122.9500000),
(2, 1, '2026-11-02 17:01:07', 500.00, 'rejected', NULL, NULL, 10.3157000, 122.9500000),
(3, 1, '2026-11-02 17:04:53', 295.00, 'rejected', NULL, NULL, 10.3157000, 122.9500000),
(4, 1, '2026-11-02 17:12:06', 175.00, 'rejected', NULL, NULL, 10.3157000, 122.9500000),
(5, 1, '2026-11-02 17:15:30', 120.00, 'rejected', NULL, NULL, 10.3157000, 122.9500000),
(6, 1, '2026-11-02 17:17:26', 120.00, 'delivered', NULL, NULL, 10.3157000, 122.9500000),
(7, 1, '2026-11-02 17:19:10', 120.00, 'delivered', NULL, NULL, 10.3157000, 122.9500000),
(8, 1, '2026-11-02 21:06:13', 24.00, 'delivered', NULL, NULL, NULL, NULL),
(9, 1, '2026-11-02 21:09:08', 120.00, 'delivered', NULL, NULL, NULL, NULL),
(10, 1, '2026-11-02 21:09:08', 525.00, 'delivered', NULL, NULL, NULL, NULL),
(11, 1, '2026-11-01 21:09:08', 12.00, 'delivered', NULL, NULL, NULL, NULL),
(12, 1, '2026-10-30 21:09:08', 24.00, 'delivered', NULL, NULL, NULL, NULL),
(13, 1, '2026-10-28 21:09:08', 12.00, 'delivered', NULL, NULL, NULL, NULL),
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
(32, 1, '2026-11-09 16:11:45', 295.00, 'delivered', 14.5995000, 120.9842000, 14.6877017, 121.0647283),
(33, 1, '2026-11-13 11:32:24', 187.00, 'delivered', NULL, NULL, 10.4815638, 122.9051994),
(34, 1, '2026-11-13 14:36:12', 415.00, 'delivered', NULL, NULL, 10.4815674, 122.9052169),
(35, 1, '2026-11-15 23:19:42', 187.00, 'delivered', NULL, NULL, 14.6877017, 121.0647283),
(36, 1, '2026-11-15 23:24:13', 72.00, 'delivered', 10.6742911, 122.9834134, 14.6877017, 121.0647283),
(37, 1, '2026-11-18 20:05:03', 525.00, 'delivered', 10.6743086, 122.9834243, 10.6743045, 122.9834195),
(38, 1, '2026-11-18 20:34:05', 295.00, 'delivered', 10.6743164, 122.9834276, 10.6743351, 122.9834443),
(39, 1, '2026-11-18 20:43:57', 343.00, 'delivered', 10.6743098, 122.9834241, 10.6743007, 122.9834162),
(40, 1, '2026-11-19 01:17:55', 295.00, 'in_transit', 10.6743036, 122.9834149, 10.6743079, 122.9834247);

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
(60, 32, 17, 1, 120.00, NULL),
(61, 33, 15, 1, 12.00, NULL),
(62, 33, 16, 1, 175.00, NULL),
(63, 34, 17, 2, 120.00, NULL),
(64, 34, 16, 1, 175.00, NULL),
(65, 35, 15, 1, 12.00, NULL),
(66, 35, 16, 1, 175.00, NULL),
(67, 36, 15, 6, 12.00, NULL),
(68, 37, 16, 3, 175.00, NULL),
(69, 38, 16, 1, 175.00, NULL),
(70, 38, 17, 1, 120.00, NULL),
(71, 39, 16, 1, 175.00, NULL),
(72, 39, 15, 4, 12.00, NULL),
(73, 39, 17, 1, 120.00, NULL),
(74, 40, 16, 1, 175.00, NULL),
(75, 40, 17, 1, 120.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

CREATE TABLE `product` (
  `product_id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
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
(15, 'asdasd', '', '', 12.00, 10, 12, 'available', '2026-11-02 18:48:21', 30, '2026-12-09'),
(16, 'Baechu Kimchi', NULL, 'KIM-001', 175.00, 32, 10, 'available', '2026-11-02 18:48:26', 30, '2026-12-07'),
(17, 'Kkakdugii', '', '', 120.00, 3, 8, 'available', '2026-11-02 18:48:26', 30, '2026-12-15'),
(22, 'kylee', 'qwewqeqwesad', '', 29.00, 0, 30, 'expired', '2026-11-07 11:15:22', 30, '2026-12-15');

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
(1, 'Fresh Retailer', 'test@kimfresh.com', '09123456789', '$2y$10$CXlccgBnx9RbDR47KMkJ3.PcqgQpBSPAx583t9QtlFZ9uM9CfM/4u', 'Bacolod City', '888888', 10.6743079, 122.9834247, 'approved', NULL, NULL, '2026-11-02 11:45:57');

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

--
-- Dumping data for table `sensor_data`
--

INSERT INTO `sensor_data` (`sensor_id`, `delivery_id`, `temperature`, `humidity`, `is_alert`, `reading_timestamp`, `synced_at`, `client_dedup_key`) VALUES
(1, 9, 32.00, 68.70, 1, '2026-11-13 10:40:21', '2026-11-13 10:40:21', NULL),
(2, 9, 32.00, 68.70, 1, '2026-11-13 10:40:41', '2026-11-13 10:40:41', NULL),
(3, 9, 32.10, 68.60, 1, '2026-11-13 10:41:02', '2026-11-13 10:41:02', NULL),
(4, 9, 32.00, 68.60, 1, '2026-11-13 10:41:22', '2026-11-13 10:41:22', NULL),
(5, 9, 32.00, 68.60, 1, '2026-11-13 10:41:42', '2026-11-13 10:41:42', NULL),
(6, 9, 32.00, 68.60, 1, '2026-11-13 10:42:03', '2026-11-13 10:42:03', NULL),
(7, 9, 32.00, 68.50, 1, '2026-11-13 10:42:23', '2026-11-13 10:42:23', NULL),
(8, 9, 32.10, 68.60, 1, '2026-11-13 10:42:44', '2026-11-13 10:42:44', NULL),
(9, 9, 32.00, 68.60, 1, '2026-11-13 10:43:04', '2026-11-13 10:43:04', NULL),
(10, 9, 32.00, 68.60, 1, '2026-11-13 10:43:24', '2026-11-13 10:43:24', NULL),
(11, 9, 32.10, 68.60, 1, '2026-11-13 10:43:45', '2026-11-13 10:43:45', NULL),
(12, 9, 32.10, 68.60, 1, '2026-11-13 10:44:05', '2026-11-13 10:44:05', NULL),
(13, 9, 32.00, 68.70, 1, '2026-11-13 10:44:25', '2026-11-13 10:44:25', NULL),
(14, 9, 32.10, 68.70, 1, '2026-11-13 10:44:45', '2026-11-13 10:44:45', NULL),
(15, 9, 32.00, 68.70, 1, '2026-11-13 10:45:06', '2026-11-13 10:45:06', NULL),
(16, 9, 32.00, 68.70, 1, '2026-11-13 10:45:26', '2026-11-13 10:45:26', NULL),
(17, 9, 32.00, 68.80, 1, '2026-11-13 10:45:46', '2026-11-13 10:45:46', NULL),
(18, 9, 32.00, 68.80, 1, '2026-11-13 10:46:07', '2026-11-13 10:46:07', NULL),
(19, 9, 32.00, 68.80, 1, '2026-11-13 10:46:27', '2026-11-13 10:46:27', NULL),
(20, 9, 32.00, 68.90, 1, '2026-11-13 10:46:48', '2026-11-13 10:46:48', NULL),
(21, 9, 32.00, 68.80, 1, '2026-11-13 10:47:08', '2026-11-13 10:47:08', NULL),
(22, 9, 32.10, 68.80, 1, '2026-11-13 10:47:28', '2026-11-13 10:47:28', NULL),
(23, 9, 32.10, 68.90, 1, '2026-11-13 10:47:49', '2026-11-13 10:47:49', NULL),
(24, 9, 32.00, 68.90, 1, '2026-11-13 10:48:09', '2026-11-13 10:48:09', NULL),
(25, 9, 32.10, 68.90, 1, '2026-11-13 10:48:29', '2026-11-13 10:48:29', NULL),
(26, 9, 32.10, 68.90, 1, '2026-11-13 10:48:50', '2026-11-13 10:48:50', NULL),
(27, 9, 32.10, 68.90, 1, '2026-11-13 10:49:10', '2026-11-13 10:49:10', NULL),
(28, 9, 32.10, 68.90, 1, '2026-11-13 10:49:31', '2026-11-13 10:49:31', NULL),
(29, 9, 32.00, 68.90, 1, '2026-11-13 10:49:51', '2026-11-13 10:49:51', NULL),
(30, 9, 32.00, 68.90, 1, '2026-11-13 10:50:11', '2026-11-13 10:50:11', NULL),
(31, 9, 32.10, 68.90, 1, '2026-11-13 10:50:31', '2026-11-13 10:50:31', NULL),
(32, 9, 32.10, 68.90, 1, '2026-11-13 10:50:52', '2026-11-13 10:50:52', NULL),
(33, 9, 32.10, 69.00, 1, '2026-11-13 10:51:12', '2026-11-13 10:51:12', NULL),
(34, 9, 32.10, 69.00, 1, '2026-11-13 10:51:32', '2026-11-13 10:51:32', NULL),
(35, 9, 32.10, 68.90, 1, '2026-11-13 10:51:53', '2026-11-13 10:51:53', NULL),
(36, 9, 32.10, 68.90, 1, '2026-11-13 10:52:13', '2026-11-13 10:52:13', NULL),
(37, 9, 32.10, 68.90, 1, '2026-11-13 10:52:34', '2026-11-13 10:52:34', NULL),
(38, 9, 32.10, 68.90, 1, '2026-11-13 10:52:54', '2026-11-13 10:52:54', NULL),
(39, 9, 32.10, 68.90, 1, '2026-11-13 10:53:14', '2026-11-13 10:53:14', NULL),
(40, 16, 31.50, 73.60, 1, '2026-11-18 22:43:49', '2026-11-18 22:43:49', NULL),
(41, 16, 31.50, 73.50, 1, '2026-11-18 22:44:10', '2026-11-18 22:44:10', NULL),
(42, 16, 31.50, 73.50, 1, '2026-11-18 22:44:10', '2026-11-18 22:44:10', NULL),
(43, 16, 31.50, 73.50, 1, '2026-11-18 22:44:30', '2026-11-18 22:44:30', NULL),
(44, 16, 31.50, 73.50, 1, '2026-11-18 22:44:30', '2026-11-18 22:44:30', NULL),
(45, 16, 31.50, 73.50, 1, '2026-11-18 22:44:50', '2026-11-18 22:44:50', NULL),
(46, 16, 31.50, 73.50, 1, '2026-11-18 22:44:50', '2026-11-18 22:44:50', NULL),
(47, 16, 31.50, 73.60, 1, '2026-11-18 22:45:11', '2026-11-18 22:45:11', NULL),
(48, 16, 31.50, 73.60, 1, '2026-11-18 22:45:11', '2026-11-18 22:45:11', NULL),
(49, 16, 31.50, 73.60, 1, '2026-11-18 22:45:31', '2026-11-18 22:45:31', NULL),
(50, 16, 31.50, 73.60, 1, '2026-11-18 22:45:31', '2026-11-18 22:45:31', NULL),
(51, 16, 31.50, 73.60, 1, '2026-11-18 22:45:51', '2026-11-18 22:45:51', NULL),
(52, 16, 31.50, 73.60, 1, '2026-11-18 22:45:51', '2026-11-18 22:45:51', NULL),
(53, 16, 31.50, 73.60, 1, '2026-11-18 22:46:12', '2026-11-18 22:46:12', NULL),
(54, 16, 31.50, 73.60, 1, '2026-11-18 22:46:12', '2026-11-18 22:46:12', NULL),
(55, 16, 31.50, 73.70, 1, '2026-11-18 22:46:32', '2026-11-18 22:46:32', NULL),
(56, 16, 31.50, 73.70, 1, '2026-11-18 22:46:32', '2026-11-18 22:46:32', NULL),
(57, 16, 31.50, 73.60, 1, '2026-11-18 22:46:52', '2026-11-18 22:46:52', NULL),
(58, 16, 31.50, 73.60, 1, '2026-11-18 22:46:52', '2026-11-18 22:46:52', NULL),
(59, 16, 31.50, 73.70, 1, '2026-11-18 22:47:13', '2026-11-18 22:47:13', NULL),
(60, 16, 31.50, 73.70, 1, '2026-11-18 22:47:13', '2026-11-18 22:47:13', NULL),
(61, 16, 31.50, 73.70, 1, '2026-11-18 22:47:33', '2026-11-18 22:47:33', NULL),
(62, 16, 31.50, 73.70, 1, '2026-11-18 22:47:33', '2026-11-18 22:47:33', NULL),
(63, 16, 31.50, 73.70, 1, '2026-11-18 22:47:54', '2026-11-18 22:47:54', NULL),
(64, 16, 31.50, 73.70, 1, '2026-11-18 22:47:54', '2026-11-18 22:47:54', NULL),
(65, 16, 31.60, 73.70, 1, '2026-11-18 22:48:14', '2026-11-18 22:48:14', NULL),
(66, 16, 31.60, 73.70, 1, '2026-11-18 22:48:14', '2026-11-18 22:48:14', NULL),
(67, 16, 31.50, 73.70, 1, '2026-11-18 22:48:34', '2026-11-18 22:48:34', NULL),
(68, 16, 31.50, 73.70, 1, '2026-11-18 22:48:34', '2026-11-18 22:48:34', NULL),
(69, 16, 31.60, 73.60, 1, '2026-11-18 22:48:55', '2026-11-18 22:48:55', NULL),
(70, 16, 31.60, 73.60, 1, '2026-11-18 22:48:55', '2026-11-18 22:48:55', NULL),
(71, 16, 31.60, 73.60, 1, '2026-11-18 22:49:15', '2026-11-18 22:49:15', NULL),
(72, 16, 31.60, 73.60, 1, '2026-11-18 22:49:15', '2026-11-18 22:49:15', NULL),
(73, 16, 31.60, 73.60, 1, '2026-11-18 22:49:35', '2026-11-18 22:49:35', NULL),
(74, 16, 31.60, 73.60, 1, '2026-11-18 22:49:35', '2026-11-18 22:49:35', NULL),
(75, 16, 31.60, 73.60, 1, '2026-11-18 22:49:56', '2026-11-18 22:49:56', NULL),
(76, 16, 31.60, 73.60, 1, '2026-11-18 22:49:56', '2026-11-18 22:49:56', NULL),
(77, 16, 31.60, 73.60, 1, '2026-11-18 22:50:16', '2026-11-18 22:50:16', NULL),
(78, 16, 31.60, 73.60, 1, '2026-11-18 22:50:16', '2026-11-18 22:50:16', NULL),
(79, 16, 31.60, 73.60, 1, '2026-11-18 22:50:36', '2026-11-18 22:50:36', NULL),
(80, 16, 31.60, 73.60, 1, '2026-11-18 22:50:36', '2026-11-18 22:50:36', NULL),
(81, 16, 31.60, 73.40, 1, '2026-11-18 22:50:57', '2026-11-18 22:50:57', NULL),
(82, 16, 31.60, 73.40, 1, '2026-11-18 22:50:57', '2026-11-18 22:50:57', NULL),
(83, 16, 31.60, 73.40, 1, '2026-11-18 22:51:17', '2026-11-18 22:51:17', NULL),
(84, 16, 31.60, 73.40, 1, '2026-11-18 22:51:17', '2026-11-18 22:51:17', NULL),
(85, 16, 31.60, 73.40, 1, '2026-11-18 22:51:38', '2026-11-18 22:51:38', NULL),
(86, 16, 31.60, 73.40, 1, '2026-11-18 22:51:38', '2026-11-18 22:51:38', NULL),
(87, 16, 31.60, 73.40, 1, '2026-11-18 22:51:58', '2026-11-18 22:51:58', NULL),
(88, 16, 31.60, 73.40, 1, '2026-11-18 22:51:58', '2026-11-18 22:51:58', NULL),
(89, 16, 31.60, 73.50, 1, '2026-11-18 22:52:18', '2026-11-18 22:52:18', NULL),
(90, 16, 31.60, 73.50, 1, '2026-11-18 22:52:18', '2026-11-18 22:52:18', NULL),
(91, 16, 31.60, 73.50, 1, '2026-11-18 22:52:38', '2026-11-18 22:52:38', NULL),
(92, 16, 31.60, 73.50, 1, '2026-11-18 22:52:38', '2026-11-18 22:52:38', NULL),
(93, 16, 31.60, 73.80, 1, '2026-11-18 23:00:47', '2026-11-18 23:00:47', NULL),
(94, 16, 31.60, 73.80, 1, '2026-11-18 23:00:47', '2026-11-18 23:00:47', NULL),
(95, 16, 31.60, 73.80, 1, '2026-11-18 23:01:07', '2026-11-18 23:01:07', NULL),
(96, 16, 31.60, 73.80, 1, '2026-11-18 23:01:08', '2026-11-18 23:01:08', NULL),
(97, 16, 31.60, 73.70, 1, '2026-11-18 23:01:28', '2026-11-18 23:01:28', NULL),
(98, 16, 31.60, 73.70, 1, '2026-11-18 23:01:28', '2026-11-18 23:01:28', NULL),
(99, 16, 31.60, 73.70, 1, '2026-11-18 23:01:48', '2026-11-18 23:01:48', NULL),
(100, 16, 31.60, 73.70, 1, '2026-11-18 23:01:48', '2026-11-18 23:01:48', NULL),
(101, 16, 31.60, 73.80, 1, '2026-11-18 23:02:09', '2026-11-18 23:02:09', NULL),
(102, 16, 31.60, 73.80, 1, '2026-11-18 23:02:09', '2026-11-18 23:02:09', NULL),
(103, 16, 31.60, 73.80, 1, '2026-11-18 23:02:29', '2026-11-18 23:02:29', NULL),
(104, 16, 31.60, 73.80, 1, '2026-11-18 23:02:29', '2026-11-18 23:02:29', NULL),
(105, 16, 31.60, 73.80, 1, '2026-11-18 23:02:49', '2026-11-18 23:02:49', NULL),
(106, 16, 31.60, 73.80, 1, '2026-11-18 23:02:49', '2026-11-18 23:02:49', NULL),
(107, 16, 31.50, 73.80, 1, '2026-11-18 23:05:53', '2026-11-18 23:05:53', NULL),
(108, 16, 31.50, 73.80, 1, '2026-11-18 23:05:53', '2026-11-18 23:05:53', NULL),
(109, 16, 31.50, 73.80, 1, '2026-11-18 23:06:13', '2026-11-18 23:06:13', NULL),
(110, 16, 31.50, 73.80, 1, '2026-11-18 23:06:13', '2026-11-18 23:06:13', NULL),
(111, 16, 31.50, 73.90, 1, '2026-11-18 23:06:33', '2026-11-18 23:06:33', NULL),
(112, 16, 31.50, 73.90, 1, '2026-11-18 23:06:33', '2026-11-18 23:06:33', NULL),
(113, 16, 31.50, 71.80, 1, '2026-11-18 23:06:53', '2026-11-18 23:06:53', NULL),
(114, 16, 31.50, 71.80, 1, '2026-11-18 23:06:53', '2026-11-18 23:06:53', NULL),
(115, 16, 31.20, 31.00, 1, '2026-11-18 23:07:14', '2026-11-18 23:07:14', NULL),
(116, 16, 31.20, 31.00, 1, '2026-11-18 23:07:14', '2026-11-18 23:07:14', NULL),
(117, 16, 30.30, 24.80, 1, '2026-11-18 23:07:34', '2026-11-18 23:07:34', NULL),
(118, 16, 30.30, 24.80, 1, '2026-11-18 23:07:34', '2026-11-18 23:07:34', NULL),
(119, 16, 29.20, 22.70, 1, '2026-11-18 23:07:54', '2026-11-18 23:07:54', NULL),
(120, 16, 29.20, 22.70, 1, '2026-11-18 23:07:55', '2026-11-18 23:07:55', NULL),
(121, 16, 28.00, 21.90, 1, '2026-11-18 23:08:15', '2026-11-18 23:08:15', NULL),
(122, 16, 28.00, 21.90, 1, '2026-11-18 23:08:15', '2026-11-18 23:08:15', NULL),
(123, 16, 26.70, 21.50, 1, '2026-11-18 23:08:35', '2026-11-18 23:08:35', NULL),
(124, 16, 26.70, 21.50, 1, '2026-11-18 23:08:35', '2026-11-18 23:08:35', NULL),
(125, 16, 25.40, 21.40, 1, '2026-11-18 23:08:55', '2026-11-18 23:08:55', NULL),
(126, 16, 25.40, 21.40, 1, '2026-11-18 23:08:55', '2026-11-18 23:08:55', NULL),
(127, 16, 24.10, 21.60, 1, '2026-11-18 23:09:16', '2026-11-18 23:09:16', NULL),
(128, 16, 24.10, 21.60, 1, '2026-11-18 23:09:16', '2026-11-18 23:09:16', NULL),
(129, 16, 22.80, 21.80, 1, '2026-11-18 23:09:36', '2026-11-18 23:09:36', NULL),
(130, 16, 22.80, 21.80, 1, '2026-11-18 23:09:37', '2026-11-18 23:09:37', NULL),
(131, 16, 21.60, 22.10, 1, '2026-11-18 23:09:57', '2026-11-18 23:09:57', NULL),
(132, 16, 21.60, 22.10, 1, '2026-11-18 23:09:57', '2026-11-18 23:09:57', NULL),
(133, 16, 20.40, 22.60, 1, '2026-11-18 23:10:17', '2026-11-18 23:10:17', NULL),
(134, 16, 20.40, 22.60, 1, '2026-11-18 23:10:17', '2026-11-18 23:10:17', NULL),
(135, 16, 19.20, 23.20, 1, '2026-11-18 23:10:38', '2026-11-18 23:10:38', NULL),
(136, 16, 19.20, 23.20, 1, '2026-11-18 23:10:38', '2026-11-18 23:10:38', NULL),
(137, 16, 18.10, 23.80, 1, '2026-11-18 23:10:58', '2026-11-18 23:10:58', NULL),
(138, 16, 18.10, 23.80, 1, '2026-11-18 23:10:58', '2026-11-18 23:10:58', NULL),
(139, 16, 17.10, 24.40, 1, '2026-11-18 23:11:18', '2026-11-18 23:11:18', NULL),
(140, 16, 17.10, 24.40, 1, '2026-11-18 23:11:18', '2026-11-18 23:11:18', NULL),
(141, 16, 16.10, 25.20, 1, '2026-11-18 23:11:39', '2026-11-18 23:11:39', NULL),
(142, 16, 16.10, 25.20, 1, '2026-11-18 23:11:39', '2026-11-18 23:11:39', NULL),
(143, 16, 15.10, 26.00, 1, '2026-11-18 23:11:59', '2026-11-18 23:11:59', NULL),
(144, 16, 15.10, 26.00, 1, '2026-11-18 23:11:59', '2026-11-18 23:11:59', NULL),
(145, 16, 14.20, 28.50, 1, '2026-11-18 23:12:19', '2026-11-18 23:12:19', NULL),
(146, 16, 14.20, 28.50, 1, '2026-11-18 23:12:19', '2026-11-18 23:12:19', NULL),
(147, 16, 13.40, 38.30, 1, '2026-11-18 23:12:40', '2026-11-18 23:12:40', NULL),
(148, 16, 13.40, 38.30, 1, '2026-11-18 23:12:40', '2026-11-18 23:12:40', NULL),
(149, 16, 12.70, 35.00, 1, '2026-11-18 23:13:00', '2026-11-18 23:13:00', NULL),
(150, 16, 12.70, 35.00, 1, '2026-11-18 23:13:00', '2026-11-18 23:13:00', NULL),
(151, 16, 12.00, 37.10, 1, '2026-11-18 23:13:20', '2026-11-18 23:13:20', NULL),
(152, 16, 12.00, 37.10, 1, '2026-11-18 23:13:20', '2026-11-18 23:13:20', NULL),
(153, 16, 11.40, 35.90, 1, '2026-11-18 23:13:40', '2026-11-18 23:13:40', NULL),
(154, 16, 11.40, 35.90, 1, '2026-11-18 23:13:40', '2026-11-18 23:13:40', NULL),
(155, 16, 10.70, 35.10, 1, '2026-11-18 23:14:01', '2026-11-18 23:14:01', NULL),
(156, 16, 10.70, 35.10, 1, '2026-11-18 23:14:01', '2026-11-18 23:14:01', NULL),
(157, 16, 10.20, 35.10, 1, '2026-11-18 23:14:21', '2026-11-18 23:14:21', NULL),
(158, 16, 10.20, 35.10, 1, '2026-11-18 23:14:21', '2026-11-18 23:14:21', NULL),
(159, 16, 9.60, 35.10, 1, '2026-11-18 23:14:41', '2026-11-18 23:14:41', NULL),
(160, 16, 9.60, 35.10, 1, '2026-11-18 23:14:42', '2026-11-18 23:14:42', NULL),
(161, 16, 9.00, 35.40, 1, '2026-11-18 23:15:02', '2026-11-18 23:15:02', NULL),
(162, 16, 9.00, 35.40, 1, '2026-11-18 23:15:02', '2026-11-18 23:15:02', NULL),
(163, 16, 8.50, 35.70, 1, '2026-11-18 23:15:22', '2026-11-18 23:15:22', NULL),
(164, 16, 8.50, 35.70, 1, '2026-11-18 23:15:22', '2026-11-18 23:15:22', NULL),
(165, 16, 7.90, 35.90, 1, '2026-11-18 23:15:43', '2026-11-18 23:15:43', NULL),
(166, 16, 7.90, 35.90, 1, '2026-11-18 23:15:43', '2026-11-18 23:15:43', NULL),
(167, 16, 7.40, 36.30, 1, '2026-11-18 23:16:03', '2026-11-18 23:16:03', NULL),
(168, 16, 7.40, 36.30, 1, '2026-11-18 23:16:03', '2026-11-18 23:16:03', NULL),
(169, 16, 6.90, 36.70, 1, '2026-11-18 23:16:23', '2026-11-18 23:16:23', NULL),
(170, 16, 6.90, 36.70, 1, '2026-11-18 23:16:23', '2026-11-18 23:16:23', NULL),
(171, 16, 6.30, 37.20, 1, '2026-11-18 23:16:44', '2026-11-18 23:16:44', NULL),
(172, 16, 6.30, 37.20, 1, '2026-11-18 23:16:44', '2026-11-18 23:16:44', NULL),
(173, 16, 2.70, 40.80, 0, '2026-11-18 23:19:27', '2026-11-18 23:19:27', NULL),
(174, 16, 2.20, 41.30, 0, '2026-11-18 23:19:47', '2026-11-18 23:19:47', NULL),
(175, 16, 1.90, 41.80, 0, '2026-11-18 23:20:08', '2026-11-18 23:20:08', NULL),
(176, 16, 1.50, 42.20, 0, '2026-11-18 23:20:28', '2026-11-18 23:20:28', NULL),
(177, 16, 1.10, 42.80, 0, '2026-11-18 23:20:48', '2026-11-18 23:20:48', NULL),
(178, 16, 0.80, 43.20, 0, '2026-11-18 23:21:09', '2026-11-18 23:21:09', NULL),
(179, 16, 0.40, 43.60, 0, '2026-11-18 23:21:29', '2026-11-18 23:21:29', NULL),
(180, 16, 0.10, 44.00, 0, '2026-11-18 23:21:49', '2026-11-18 23:21:49', NULL),
(181, 16, 999.99, 44.50, 1, '2026-11-18 23:22:10', '2026-11-18 23:22:10', NULL),
(182, 16, 999.99, 44.90, 1, '2026-11-18 23:22:30', '2026-11-18 23:22:30', NULL),
(183, 16, 999.99, 44.90, 1, '2026-11-18 23:22:30', '2026-11-18 23:22:30', NULL),
(184, 16, 17.20, 95.00, 1, '2026-11-18 23:25:27', '2026-11-18 23:25:27', NULL),
(185, 16, 17.20, 95.00, 1, '2026-11-18 23:25:27', '2026-11-18 23:25:27', NULL),
(186, 16, 23.50, 30.20, 1, '2026-11-19 00:41:05', '2026-11-19 00:41:05', NULL),
(187, 16, 23.50, 30.20, 1, '2026-11-19 00:41:05', '2026-11-19 00:41:05', NULL),
(188, 16, 22.40, 30.50, 1, '2026-11-19 00:41:25', '2026-11-19 00:41:25', NULL),
(189, 16, 22.40, 30.50, 1, '2026-11-19 00:41:25', '2026-11-19 00:41:25', NULL),
(190, 16, 21.20, 31.20, 1, '2026-11-19 00:41:45', '2026-11-19 00:41:45', NULL),
(191, 16, 21.20, 31.20, 1, '2026-11-19 00:41:45', '2026-11-19 00:41:45', NULL),
(192, 16, 20.10, 31.60, 1, '2026-11-19 00:42:05', '2026-11-19 00:42:05', NULL),
(193, 16, 20.10, 31.60, 1, '2026-11-19 00:42:05', '2026-11-19 00:42:05', NULL),
(194, 16, 19.00, 32.50, 1, '2026-11-19 00:42:26', '2026-11-19 00:42:26', NULL),
(195, 16, 19.00, 32.50, 1, '2026-11-19 00:42:26', '2026-11-19 00:42:26', NULL),
(196, 16, 18.10, 32.90, 1, '2026-11-19 00:42:46', '2026-11-19 00:42:46', NULL),
(197, 16, 18.10, 32.90, 1, '2026-11-19 00:42:46', '2026-11-19 00:42:46', NULL),
(198, 16, 17.10, 33.60, 1, '2026-11-19 00:43:06', '2026-11-19 00:43:06', NULL),
(199, 16, 17.10, 33.60, 1, '2026-11-19 00:43:07', '2026-11-19 00:43:07', NULL),
(200, 16, 16.20, 34.20, 1, '2026-11-19 00:43:27', '2026-11-19 00:43:27', NULL),
(201, 16, 16.20, 34.20, 1, '2026-11-19 00:43:27', '2026-11-19 00:43:27', NULL),
(202, 16, 15.30, 34.90, 1, '2026-11-19 00:43:47', '2026-11-19 00:43:47', NULL),
(203, 16, 15.30, 34.90, 1, '2026-11-19 00:43:47', '2026-11-19 00:43:47', NULL),
(204, 16, 14.50, 35.70, 1, '2026-11-19 00:44:08', '2026-11-19 00:44:08', NULL),
(205, 16, 14.50, 35.70, 1, '2026-11-19 00:44:08', '2026-11-19 00:44:08', NULL),
(206, 16, 13.70, 36.60, 1, '2026-11-19 00:44:28', '2026-11-19 00:44:28', NULL),
(207, 16, 13.70, 36.60, 1, '2026-11-19 00:44:28', '2026-11-19 00:44:28', NULL),
(208, 16, 12.90, 37.60, 1, '2026-11-19 00:44:48', '2026-11-19 00:44:48', NULL),
(209, 16, 12.90, 37.60, 1, '2026-11-19 00:44:48', '2026-11-19 00:44:48', NULL),
(210, 16, 12.20, 38.60, 1, '2026-11-19 00:45:09', '2026-11-19 00:45:09', NULL),
(211, 16, 12.20, 38.60, 1, '2026-11-19 00:45:09', '2026-11-19 00:45:09', NULL),
(212, 16, 11.50, 39.40, 1, '2026-11-19 00:45:29', '2026-11-19 00:45:29', NULL),
(213, 16, 11.50, 39.40, 1, '2026-11-19 00:45:29', '2026-11-19 00:45:29', NULL),
(214, 16, 10.80, 40.40, 1, '2026-11-19 00:45:49', '2026-11-19 00:45:49', NULL),
(215, 16, 10.80, 40.40, 1, '2026-11-19 00:45:49', '2026-11-19 00:45:49', NULL),
(216, 16, 10.20, 41.20, 1, '2026-11-19 00:46:10', '2026-11-19 00:46:10', NULL),
(217, 16, 10.20, 41.20, 1, '2026-11-19 00:46:10', '2026-11-19 00:46:10', NULL),
(218, 16, 9.60, 42.20, 1, '2026-11-19 00:46:30', '2026-11-19 00:46:30', NULL),
(219, 16, 9.60, 42.20, 1, '2026-11-19 00:46:30', '2026-11-19 00:46:30', NULL),
(220, 16, 9.00, 43.20, 1, '2026-11-19 00:46:50', '2026-11-19 00:46:50', NULL),
(221, 16, 9.00, 43.20, 1, '2026-11-19 00:46:50', '2026-11-19 00:46:50', NULL),
(222, 16, 8.50, 44.10, 1, '2026-11-19 00:47:11', '2026-11-19 00:47:11', NULL),
(223, 16, 8.50, 44.10, 1, '2026-11-19 00:47:11', '2026-11-19 00:47:11', NULL),
(224, 16, 8.00, 45.10, 1, '2026-11-19 00:47:31', '2026-11-19 00:47:31', NULL),
(225, 16, 8.00, 45.10, 1, '2026-11-19 00:47:31', '2026-11-19 00:47:31', NULL),
(226, 16, 7.50, 46.00, 1, '2026-11-19 00:47:52', '2026-11-19 00:47:52', NULL),
(227, 16, 7.50, 46.00, 1, '2026-11-19 00:47:52', '2026-11-19 00:47:52', NULL),
(228, 16, 7.00, 46.90, 1, '2026-11-19 00:48:12', '2026-11-19 00:48:12', NULL),
(229, 16, 7.00, 46.90, 1, '2026-11-19 00:48:12', '2026-11-19 00:48:12', NULL),
(230, 16, 6.60, 47.70, 1, '2026-11-19 00:48:32', '2026-11-19 00:48:32', NULL),
(231, 16, 6.60, 47.70, 1, '2026-11-19 00:48:32', '2026-11-19 00:48:32', NULL),
(232, 16, 6.20, 48.60, 1, '2026-11-19 00:48:53', '2026-11-19 00:48:53', NULL),
(233, 16, 5.70, 49.40, 1, '2026-11-19 00:49:13', '2026-11-19 00:49:13', NULL),
(234, 16, 5.30, 50.30, 1, '2026-11-19 00:49:34', '2026-11-19 00:49:34', NULL),
(235, 16, 5.00, 51.10, 1, '2026-11-19 00:49:54', '2026-11-19 00:49:54', NULL),
(236, 16, 4.70, 51.90, 0, '2026-11-19 00:50:14', '2026-11-19 00:50:14', NULL),
(237, 16, 4.30, 52.70, 0, '2026-11-19 00:50:35', '2026-11-19 00:50:35', NULL),
(238, 16, 3.90, 53.50, 0, '2026-11-19 00:50:55', '2026-11-19 00:50:55', NULL),
(239, 16, 3.70, 54.20, 0, '2026-11-19 00:51:15', '2026-11-19 00:51:15', NULL),
(240, 16, 3.40, 54.90, 0, '2026-11-19 00:51:35', '2026-11-19 00:51:35', NULL),
(241, 16, 3.10, 55.60, 0, '2026-11-19 00:51:56', '2026-11-19 00:51:56', NULL),
(242, 16, 2.80, 56.30, 0, '2026-11-19 00:52:16', '2026-11-19 00:52:16', NULL),
(243, 16, 2.50, 57.00, 0, '2026-11-19 00:52:36', '2026-11-19 00:52:36', NULL),
(244, 16, 2.30, 57.70, 0, '2026-11-19 00:52:57', '2026-11-19 00:52:57', NULL),
(245, 16, 2.10, 58.40, 0, '2026-11-19 00:53:17', '2026-11-19 00:53:17', NULL),
(246, 16, 1.80, 59.00, 0, '2026-11-19 00:53:38', '2026-11-19 00:53:38', NULL),
(247, 16, 1.60, 59.60, 0, '2026-11-19 00:53:58', '2026-11-19 00:53:58', NULL),
(248, 16, 1.40, 60.30, 0, '2026-11-19 00:54:18', '2026-11-19 00:54:18', NULL),
(249, 16, 1.20, 60.80, 0, '2026-11-19 00:54:39', '2026-11-19 00:54:39', NULL),
(250, 16, 1.00, 61.40, 0, '2026-11-19 00:54:59', '2026-11-19 00:54:59', NULL),
(251, 17, -5.80, 80.40, 0, '2026-11-19 01:28:34', '2026-11-19 01:28:34', NULL),
(252, 17, -5.80, 80.40, 0, '2026-11-19 01:28:34', '2026-11-19 01:28:34', NULL),
(253, 17, -5.70, 80.50, 0, '2026-11-19 01:28:55', '2026-11-19 01:28:55', NULL),
(254, 17, -5.70, 80.50, 0, '2026-11-19 01:28:55', '2026-11-19 01:28:55', NULL),
(255, 17, -5.70, 80.60, 0, '2026-11-19 01:29:15', '2026-11-19 01:29:15', NULL),
(256, 17, -5.70, 80.60, 0, '2026-11-19 01:29:15', '2026-11-19 01:29:15', NULL),
(257, 17, -5.70, 80.70, 0, '2026-11-19 01:29:35', '2026-11-19 01:29:35', NULL),
(258, 17, -5.70, 80.70, 0, '2026-11-19 01:29:35', '2026-11-19 01:29:35', NULL),
(259, 17, -5.70, 80.80, 0, '2026-11-19 01:29:56', '2026-11-19 01:29:56', NULL),
(260, 17, -5.70, 80.80, 0, '2026-11-19 01:29:56', '2026-11-19 01:29:56', NULL),
(261, 17, -5.60, 80.90, 0, '2026-11-19 01:30:16', '2026-11-19 01:30:16', NULL),
(262, 17, -5.60, 80.90, 0, '2026-11-19 01:30:16', '2026-11-19 01:30:16', NULL),
(263, 17, -5.60, 81.00, 0, '2026-11-19 01:30:36', '2026-11-19 01:30:36', NULL),
(264, 17, -5.60, 81.00, 0, '2026-11-19 01:30:36', '2026-11-19 01:30:36', NULL),
(265, 17, -5.60, 81.10, 0, '2026-11-19 01:30:57', '2026-11-19 01:30:57', NULL),
(266, 17, -5.60, 81.10, 0, '2026-11-19 01:30:57', '2026-11-19 01:30:57', NULL),
(267, 17, -5.50, 81.10, 0, '2026-11-19 01:31:17', '2026-11-19 01:31:17', NULL),
(268, 17, -5.50, 81.10, 0, '2026-11-19 01:31:17', '2026-11-19 01:31:17', NULL),
(269, 17, -5.50, 81.20, 0, '2026-11-19 01:31:38', '2026-11-19 01:31:38', NULL),
(270, 17, -5.50, 81.20, 0, '2026-11-19 01:31:38', '2026-11-19 01:31:38', NULL),
(271, 17, -5.50, 81.30, 0, '2026-11-19 01:31:58', '2026-11-19 01:31:58', NULL),
(272, 17, -5.50, 81.30, 0, '2026-11-19 01:31:58', '2026-11-19 01:31:58', NULL),
(273, 17, -5.50, 81.40, 0, '2026-11-19 01:32:18', '2026-11-19 01:32:18', NULL),
(274, 17, -5.50, 81.40, 0, '2026-11-19 01:32:18', '2026-11-19 01:32:18', NULL),
(275, 17, -5.40, 81.50, 0, '2026-11-19 01:32:38', '2026-11-19 01:32:38', NULL),
(276, 17, -5.40, 81.50, 0, '2026-11-19 01:32:39', '2026-11-19 01:32:39', NULL),
(277, 17, -5.40, 81.60, 0, '2026-11-19 01:32:59', '2026-11-19 01:32:59', NULL),
(278, 17, -5.40, 81.60, 0, '2026-11-19 01:32:59', '2026-11-19 01:32:59', NULL),
(279, 17, -5.40, 81.60, 0, '2026-11-19 01:33:19', '2026-11-19 01:33:19', NULL),
(280, 17, -5.40, 81.60, 0, '2026-11-19 01:33:19', '2026-11-19 01:33:19', NULL),
(281, 17, -5.30, 81.70, 0, '2026-11-19 01:33:40', '2026-11-19 01:33:40', NULL),
(282, 17, -5.30, 81.70, 0, '2026-11-19 01:33:40', '2026-11-19 01:33:40', NULL),
(283, 17, -5.30, 81.80, 0, '2026-11-19 01:34:00', '2026-11-19 01:34:00', NULL),
(284, 17, -5.30, 81.80, 0, '2026-11-19 01:34:00', '2026-11-19 01:34:00', NULL),
(285, 17, -5.30, 81.80, 0, '2026-11-19 01:34:20', '2026-11-19 01:34:20', NULL),
(286, 17, -5.30, 81.80, 0, '2026-11-19 01:34:20', '2026-11-19 01:34:20', NULL),
(287, 17, -5.30, 81.90, 0, '2026-11-19 01:34:41', '2026-11-19 01:34:41', NULL),
(288, 17, -5.30, 81.90, 0, '2026-11-19 01:34:41', '2026-11-19 01:34:41', NULL),
(289, 17, -5.20, 82.00, 0, '2026-11-19 01:35:01', '2026-11-19 01:35:01', NULL),
(290, 17, -5.20, 82.00, 0, '2026-11-19 01:35:01', '2026-11-19 01:35:01', NULL),
(291, 17, -5.20, 82.00, 0, '2026-11-19 01:35:21', '2026-11-19 01:35:21', NULL),
(292, 17, -5.20, 82.00, 0, '2026-11-19 01:35:21', '2026-11-19 01:35:21', NULL),
(293, 17, -5.20, 82.10, 0, '2026-11-19 01:35:42', '2026-11-19 01:35:42', NULL),
(294, 17, -5.20, 82.10, 0, '2026-11-19 01:35:42', '2026-11-19 01:35:42', NULL),
(295, 17, -5.20, 82.10, 0, '2026-11-19 01:36:02', '2026-11-19 01:36:02', NULL),
(296, 17, -5.20, 82.10, 0, '2026-11-19 01:36:02', '2026-11-19 01:36:02', NULL),
(297, 17, -5.10, 82.20, 0, '2026-11-19 01:36:22', '2026-11-19 01:36:22', NULL),
(298, 17, -5.10, 82.20, 0, '2026-11-19 01:36:22', '2026-11-19 01:36:22', NULL),
(299, 17, -5.10, 82.20, 0, '2026-11-19 01:36:43', '2026-11-19 01:36:43', NULL),
(300, 17, -5.10, 82.20, 0, '2026-11-19 01:36:43', '2026-11-19 01:36:43', NULL),
(301, 17, -5.10, 82.20, 0, '2026-11-19 01:37:03', '2026-11-19 01:37:03', NULL),
(302, 17, -5.10, 82.20, 0, '2026-11-19 01:37:03', '2026-11-19 01:37:03', NULL),
(303, 17, -5.10, 82.10, 0, '2026-11-19 01:37:23', '2026-11-19 01:37:23', NULL),
(304, 17, -5.10, 82.10, 0, '2026-11-19 01:37:23', '2026-11-19 01:37:23', NULL),
(305, 17, -5.10, 82.00, 0, '2026-11-19 01:37:44', '2026-11-19 01:37:44', NULL),
(306, 17, -5.10, 82.00, 0, '2026-11-19 01:37:44', '2026-11-19 01:37:44', NULL),
(307, 17, -5.00, 81.90, 0, '2026-11-19 01:38:04', '2026-11-19 01:38:04', NULL),
(308, 17, -5.00, 81.90, 0, '2026-11-19 01:38:04', '2026-11-19 01:38:04', NULL),
(309, 17, -5.10, 81.70, 0, '2026-11-19 01:38:24', '2026-11-19 01:38:24', NULL),
(310, 17, -5.10, 81.70, 0, '2026-11-19 01:38:24', '2026-11-19 01:38:24', NULL),
(311, 17, -5.10, 81.40, 0, '2026-11-19 01:38:45', '2026-11-19 01:38:45', NULL),
(312, 17, -5.10, 81.40, 0, '2026-11-19 01:38:45', '2026-11-19 01:38:45', NULL),
(313, 17, -5.10, 81.10, 0, '2026-11-19 01:39:05', '2026-11-19 01:39:05', NULL),
(314, 17, -5.10, 81.10, 0, '2026-11-19 01:39:05', '2026-11-19 01:39:05', NULL),
(315, 17, -5.10, 80.70, 0, '2026-11-19 01:39:25', '2026-11-19 01:39:25', NULL),
(316, 17, -5.10, 80.70, 0, '2026-11-19 01:39:25', '2026-11-19 01:39:25', NULL),
(317, 17, -5.10, 80.30, 0, '2026-11-19 01:39:46', '2026-11-19 01:39:46', NULL),
(318, 17, -5.10, 80.30, 0, '2026-11-19 01:39:46', '2026-11-19 01:39:46', NULL),
(319, 17, -5.20, 79.80, 0, '2026-11-19 01:40:06', '2026-11-19 01:40:06', NULL),
(320, 17, -5.20, 79.80, 0, '2026-11-19 01:40:06', '2026-11-19 01:40:06', NULL),
(321, 17, -5.20, 79.20, 0, '2026-11-19 01:40:27', '2026-11-19 01:40:27', NULL),
(322, 17, -5.20, 79.20, 0, '2026-11-19 01:40:27', '2026-11-19 01:40:27', NULL),
(323, 17, -5.20, 78.80, 0, '2026-11-19 01:40:47', '2026-11-19 01:40:47', NULL),
(324, 17, -5.20, 78.80, 0, '2026-11-19 01:40:47', '2026-11-19 01:40:47', NULL),
(325, 17, -5.30, 78.30, 0, '2026-11-19 01:41:07', '2026-11-19 01:41:07', NULL),
(326, 17, -5.30, 78.30, 0, '2026-11-19 01:41:07', '2026-11-19 01:41:07', NULL),
(327, 17, -5.30, 77.80, 0, '2026-11-19 01:41:28', '2026-11-19 01:41:28', NULL),
(328, 17, -5.30, 77.80, 0, '2026-11-19 01:41:28', '2026-11-19 01:41:28', NULL),
(329, 17, -5.40, 77.40, 0, '2026-11-19 01:41:48', '2026-11-19 01:41:48', NULL),
(330, 17, -5.40, 77.40, 0, '2026-11-19 01:41:48', '2026-11-19 01:41:48', NULL),
(331, 17, -5.40, 76.90, 0, '2026-11-19 01:42:08', '2026-11-19 01:42:08', NULL),
(332, 17, -5.40, 76.90, 0, '2026-11-19 01:42:08', '2026-11-19 01:42:08', NULL),
(333, 17, -5.50, 76.50, 0, '2026-11-19 01:42:29', '2026-11-19 01:42:29', NULL),
(334, 17, -5.50, 76.50, 0, '2026-11-19 01:42:29', '2026-11-19 01:42:29', NULL),
(335, 17, -5.60, 76.10, 0, '2026-11-19 01:42:49', '2026-11-19 01:42:49', NULL),
(336, 17, -5.60, 76.10, 0, '2026-11-19 01:42:49', '2026-11-19 01:42:49', NULL),
(337, 17, -5.60, 75.80, 0, '2026-11-19 01:43:09', '2026-11-19 01:43:09', NULL),
(338, 17, -5.60, 75.80, 0, '2026-11-19 01:43:09', '2026-11-19 01:43:09', NULL),
(339, 17, -5.70, 75.50, 0, '2026-11-19 01:43:30', '2026-11-19 01:43:30', NULL),
(340, 17, -5.70, 75.50, 0, '2026-11-19 01:43:30', '2026-11-19 01:43:30', NULL),
(341, 17, -5.80, 75.30, 0, '2026-11-19 01:43:50', '2026-11-19 01:43:50', NULL),
(342, 17, -5.80, 75.30, 0, '2026-11-19 01:43:50', '2026-11-19 01:43:50', NULL),
(343, 17, -5.80, 75.10, 0, '2026-11-19 01:44:11', '2026-11-19 01:44:11', NULL),
(344, 17, -5.80, 75.10, 0, '2026-11-19 01:44:11', '2026-11-19 01:44:11', NULL),
(345, 17, -5.90, 74.80, 0, '2026-11-19 01:44:31', '2026-11-19 01:44:31', NULL),
(346, 17, -5.90, 74.80, 0, '2026-11-19 01:44:31', '2026-11-19 01:44:31', NULL),
(347, 17, -5.90, 74.60, 0, '2026-11-19 01:44:51', '2026-11-19 01:44:51', NULL),
(348, 17, -5.90, 74.60, 0, '2026-11-19 01:44:51', '2026-11-19 01:44:51', NULL),
(349, 17, -6.00, 74.40, 0, '2026-11-19 01:45:12', '2026-11-19 01:45:12', NULL),
(350, 17, -6.00, 74.40, 0, '2026-11-19 01:45:12', '2026-11-19 01:45:12', NULL),
(351, 17, -6.10, 74.30, 0, '2026-11-19 01:45:32', '2026-11-19 01:45:32', NULL),
(352, 17, -6.10, 74.30, 0, '2026-11-19 01:45:32', '2026-11-19 01:45:32', NULL),
(353, 17, -6.10, 74.20, 0, '2026-11-19 01:45:52', '2026-11-19 01:45:52', NULL),
(354, 17, -6.10, 74.20, 0, '2026-11-19 01:45:52', '2026-11-19 01:45:52', NULL),
(355, 17, -6.20, 74.10, 0, '2026-11-19 01:46:13', '2026-11-19 01:46:13', NULL),
(356, 17, -6.20, 74.10, 0, '2026-11-19 01:46:13', '2026-11-19 01:46:13', NULL),
(357, 17, -6.30, 74.00, 0, '2026-11-19 01:46:33', '2026-11-19 01:46:33', NULL),
(358, 17, -6.30, 74.00, 0, '2026-11-19 01:46:33', '2026-11-19 01:46:33', NULL),
(359, 17, -6.30, 73.90, 0, '2026-11-19 01:46:53', '2026-11-19 01:46:53', NULL),
(360, 17, -6.30, 73.90, 0, '2026-11-19 01:46:53', '2026-11-19 01:46:53', NULL),
(361, 17, -6.40, 73.80, 0, '2026-11-19 01:47:14', '2026-11-19 01:47:14', NULL),
(362, 17, -6.40, 73.80, 0, '2026-11-19 01:47:14', '2026-11-19 01:47:14', NULL),
(363, 17, -6.50, 73.70, 0, '2026-11-19 01:47:34', '2026-11-19 01:47:34', NULL),
(364, 17, -6.50, 73.70, 0, '2026-11-19 01:47:34', '2026-11-19 01:47:34', NULL),
(365, 17, -6.50, 73.60, 0, '2026-11-19 01:47:55', '2026-11-19 01:47:55', NULL),
(366, 17, -6.50, 73.60, 0, '2026-11-19 01:47:55', '2026-11-19 01:47:55', NULL),
(367, 17, -6.60, 73.70, 0, '2026-11-19 01:48:15', '2026-11-19 01:48:15', NULL),
(368, 17, -6.60, 73.70, 0, '2026-11-19 01:48:15', '2026-11-19 01:48:15', NULL),
(369, 17, -6.70, 73.80, 0, '2026-11-19 01:48:35', '2026-11-19 01:48:35', NULL),
(370, 17, -6.70, 73.80, 0, '2026-11-19 01:48:35', '2026-11-19 01:48:35', NULL);

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
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=315;

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `delivery`
--
ALTER TABLE `delivery`
  MODIFY `delivery_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `driver`
--
ALTER TABLE `driver`
  MODIFY `driver_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `driver_locations`
--
ALTER TABLE `driver_locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=136;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `order`
--
ALTER TABLE `order`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `order_item`
--
ALTER TABLE `order_item`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `retailer`
--
ALTER TABLE `retailer`
  MODIFY `retailer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sensor_data`
--
ALTER TABLE `sensor_data`
  MODIFY `sensor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=371;

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
