-- phpMyAdmin SQL Dump
-- Phiên bản: 5.2.1
-- Gộp và làm sạch xung đột Git ngày 03/05/2026

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------
-- Cấu trúc bảng cho bảng `admins`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Cấu trúc bảng cho bảng `banners`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `banners`;
CREATE TABLE `banners` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `image_url` varchar(255) NOT NULL,
  `title` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `font_family` varchar(50) DEFAULT 'Poppins',
  `text_color` varchar(20) DEFAULT '#ffffff',
  `text_align` varchar(20) DEFAULT 'center',
  `font_style` varchar(50) DEFAULT 'normal',
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `desc_color` varchar(20) DEFAULT '#eeeeee',
  `desc_font_family` varchar(100) DEFAULT '''Poppins'', sans-serif',
  `desc_font_style` varchar(50) DEFAULT 'normal',
  `title_font_size` int(11) DEFAULT 48,
  `desc_font_size` int(11) DEFAULT 24,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Cấu trúc bảng cho bảng `bookings` (Dữ liệu từ nhánh main)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `bookings`;
CREATE TABLE `bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_name` varchar(100) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `customer_email` varchar(100) DEFAULT NULL,
  `booking_date` datetime NOT NULL,
  `number_of_guests` int(11) NOT NULL DEFAULT 1,
  `table_id` int(11) DEFAULT NULL,
  `status` enum('pending','confirmed','completed','cancelled') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Cấu trúc bảng cho bảng `footer_settings` (Dữ liệu từ nhánh feature)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `footer_settings`;
CREATE TABLE `footer_settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `footer_settings` (`setting_key`, `setting_value`) VALUES
('address', '123 Đường ABC, Quận 1, TP. HCM'),[cite: 1, 2]
('copyright_text', '© 2026 Restaurantly. All Rights Reserved.'),[cite: 1, 2]
('email', 'contact@restaurantly.com'),[cite: 1, 2]
('footer_bg_color', '#0c0b09'),[cite: 2]
('footer_description', 'Trải nghiệm ẩm thực đẳng cấp giữa lòng thành phố.'),[cite: 1, 2]
('footer_text_color', '#ffffff'),[cite: 1, 2]
('opening_hours', '08:00 AM - 10:00 PM'),[cite: 1, 2]
('phone', '0901 234 567'),[cite: 1, 2]
('restaurant_name', 'Restaurantly'),[cite: 1, 2]
('show_map', '1'),[cite: 1, 2]
('show_newsletter', '1'),[cite: 2]
('show_social', '1');[cite: 2]

-- --------------------------------------------------------
-- Cấu trúc bảng cho bảng `inventory`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `inventory`;
CREATE TABLE `inventory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `unit_name` varchar(50) DEFAULT NULL,
  `stock_quantity` decimal(10,2) DEFAULT 0.00,
  `cost_price` decimal(15,2) DEFAULT 0.00,
  `supplier_id` int(11) DEFAULT NULL,
  `entry_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `revenue` decimal(15,2) DEFAULT 0.00,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `min_stock` float DEFAULT 5,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `inventory` (`id`, `item_name`, `category`, `unit_name`, `stock_quantity`, `cost_price`) VALUES
(1, 'rựu', 'Đồ uống', 'chai', 191.00, 20000000.00),[cite: 1]
(2, 'thịt bò', 'Thịt', 'kg', 28.50, 10000000.00);[cite: 1]

-- --------------------------------------------------------
-- Cấu trúc bảng cho bảng `users`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','staff') DEFAULT 'staff',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `google_id` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `google_id`) VALUES
(1, 'Huỳnh đức thông', '28huynhducthong@gmail.com', '$2y$10$f7cOFOOX2paiJ8e4J2OnneWhZZ87.3CioY3mCximsFWmC7wbAyzni', 'admin', '107664704264935131673'),
(8, 'Long Hoang', 'hoanglongduongle@gmail.com', '', 'staff', '109729244014013210980');

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;