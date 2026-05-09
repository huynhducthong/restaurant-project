-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th5 09, 2026 lúc 04:24 PM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `restaurant_db`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `about_categories`
--

DROP TABLE IF EXISTS `about_categories`;
CREATE TABLE `about_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `about_categories`
--

INSERT INTO `about_categories` (`id`, `name`, `slug`) VALUES
(1, 'Câu chuyện', 'cau-chuyen'),
(2, 'Đội ngũ', 'doi-ngu');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `about_content`
--

DROP TABLE IF EXISTS `about_content`;
CREATE TABLE `about_content` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` longtext DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_pinned` tinyint(1) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `publish_date` date DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `about_content`
--

INSERT INTO `about_content` (`id`, `category_id`, `title`, `slug`, `content`, `thumbnail`, `display_order`, `is_pinned`, `status`, `created_at`, `publish_date`) VALUES
(1, 2, 'ĐỘI NGŨ TÀI NĂNG', 'doi-ngu-tai-nang', '<p>Trong một tập thể, sức mạnh kh&ocirc;ng chỉ đến từ kỹ năng c&aacute; nh&acirc;n m&agrave; c&ograve;n từ sự gắn kết v&agrave; c&aacute; t&iacute;nh độc đ&aacute;o của từng th&agrave;nh vi&ecirc;n. Nh&oacute;m 5 người gồm <strong>Th&ocirc;ng, Long, Ph&aacute;t, Ch&iacute;nh, Dương</strong> ch&iacute;nh l&agrave; minh chứng sống động cho điều đ&oacute;.</p>\r\n\r\n<p>🌟 Th&ocirc;ng &ndash; Người dẫn đường</p>\r\n\r\n<p>Th&ocirc;ng nổi bật với khả năng ph&acirc;n t&iacute;ch v&agrave; định hướng. Anh giống như &ldquo;bộ n&atilde;o&rdquo; của đội, lu&ocirc;n đưa ra chiến lược r&otilde; r&agrave;ng v&agrave; gi&uacute;p cả nh&oacute;m đi đ&uacute;ng hướng.</p>\r\n\r\n<p>🎭 Long &ndash; Qu&yacute; bửu (wibu)</p>\r\n\r\n<p>Long l&agrave; nh&acirc;n tố đặc biệt, một &ldquo;qu&yacute; bửu&rdquo; với niềm đam m&ecirc; văn h&oacute;a Nhật Bản. Sự s&aacute;ng tạo v&agrave; kh&aacute;c biệt của Long mang lại m&agrave;u sắc mới mẻ cho cả đội, đ&ocirc;i khi ch&iacute;nh sự &ldquo;dị biệt&rdquo; ấy lại l&agrave; nguồn cảm hứng để nh&oacute;m t&igrave;m ra &yacute; tưởng độc đ&aacute;o.</p>\r\n\r\n<p>⚡ Ph&aacute;t &ndash; Người truyền năng lượng</p>\r\n\r\n<p>Ph&aacute;t lu&ocirc;n tr&agrave;n đầy nhiệt huyết, l&agrave; &ldquo;động cơ&rdquo; th&uacute;c đẩy tinh thần cả nh&oacute;m. Khi mọi người mệt mỏi, ch&iacute;nh Ph&aacute;t l&agrave; người kh&iacute;ch lệ v&agrave; k&eacute;o mọi người trở lại với mục ti&ecirc;u.</p>\r\n\r\n<p>🛠 Ch&iacute;nh &ndash; Người thực thi</p>\r\n\r\n<p>Ch&iacute;nh c&oacute; khả năng biến &yacute; tưởng th&agrave;nh h&agrave;nh động. Anh l&agrave; người tỉ mỉ, ki&ecirc;n nhẫn, đảm bảo mọi kế hoạch được triển khai một c&aacute;ch chắc chắn v&agrave; hiệu quả.</p>\r\n\r\n<p>🌍 Dương &ndash; Người kết nối</p>\r\n\r\n<p>Dương l&agrave; cầu nối giữa c&aacute;c th&agrave;nh vi&ecirc;n, lu&ocirc;n tạo ra sự h&ograve;a hợp v&agrave; gắn kết. Anh gi&uacute;p nh&oacute;m duy tr&igrave; tinh thần đồng đội, biến sự kh&aacute;c biệt th&agrave;nh sức mạnh chung.</p>\r\n\r\n<h3>✨ Kết luận</h3>\r\n\r\n<p>Năm c&aacute; t&iacute;nh, năm thế mạnh kh&aacute;c nhau, nhưng khi kết hợp lại, họ tạo th&agrave;nh một đội ngũ t&agrave;i năng đầy s&aacute;ng tạo v&agrave; nhiệt huyết. V&agrave; tất nhi&ecirc;n, sự g&oacute;p mặt của Long &ndash; &ldquo;qu&yacute; bửu wibu&rdquo; &ndash; khiến tập thể n&agrave;y c&agrave;ng th&ecirc;m th&uacute; vị, độc đ&aacute;o v&agrave; kh&oacute; qu&ecirc;n.</p>\r\n', '1778061665_2345.png', 1, 1, 1, '2026-04-27 14:46:46', '2026-04-30');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `admins`
--

DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `banners`
--

DROP TABLE IF EXISTS `banners`;
CREATE TABLE `banners` (
  `id` int(11) NOT NULL,
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
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `button_text` varchar(255) DEFAULT NULL,
  `button_link` varchar(255) DEFAULT NULL,
  `button_color` varchar(20) DEFAULT '#cda45e',
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `banners`
--

INSERT INTO `banners` (`id`, `image_url`, `title`, `description`, `font_family`, `text_color`, `text_align`, `font_style`, `display_order`, `created_at`, `desc_color`, `desc_font_family`, `desc_font_style`, `title_font_size`, `desc_font_size`, `is_active`, `button_text`, `button_link`, `button_color`, `start_date`, `end_date`) VALUES
(2, '1776687242_hero-bg.jpg', 'retauranlly', 'ăn ngon', '\'Playfair Display\', serif', '#240a0a', 'center', 'bold', 1, '2026-04-20 12:14:02', '#c18b8b', '\'Poppins\', sans-serif', 'normal', 48, 24, 1, 'đặt bàn', 'http://localhost/restaurant-project/booking_service.php?type=table', '#cda45e', NULL, NULL),
(7, '1776687610_hero-bg-2.jpg', 'huhf', 'nbzbn', '\'Playfair Display\', serif', '#d71d1d', 'left', 'normal', 2, '2026-04-20 12:20:10', '#eeeeee', '\'Poppins\', sans-serif', 'normal', 48, 24, 1, NULL, NULL, '#cda45e', NULL, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `bookings`
--

DROP TABLE IF EXISTS `bookings`;
CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `customer_email` varchar(100) DEFAULT NULL,
  `booking_date` datetime NOT NULL,
  `number_of_guests` int(11) NOT NULL DEFAULT 1,
  `table_id` int(11) DEFAULT NULL,
  `status` enum('pending','confirmed','completed','cancelled') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `booking_details`
--

DROP TABLE IF EXISTS `booking_details`;
CREATE TABLE `booking_details` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `item_type` enum('food','combo','service') NOT NULL DEFAULT 'food',
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `books`
--

DROP TABLE IF EXISTS `books`;
CREATE TABLE `books` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `author` varchar(150) DEFAULT '',
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `image` varchar(255) DEFAULT '',
  `stock` int(11) NOT NULL DEFAULT 0,
  `category` varchar(100) DEFAULT 'Sách nấu ăn',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `books`
--

INSERT INTO `books` (`id`, `title`, `author`, `description`, `price`, `image`, `stock`, `category`, `is_active`, `created_at`) VALUES
(1, 'Sách nấu ăn chuyên nghiệp', 'nguyên văn b', 'dfbgfnhnz', 500000.00, 'b1080097d12f0c035d4c.png', 96, 'Sách nấu ăn', 1, '2026-05-05 12:32:25');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `book_orders`
--

DROP TABLE IF EXISTS `book_orders`;
CREATE TABLE `book_orders` (
  `id` int(11) NOT NULL,
  `order_code` varchar(20) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text DEFAULT NULL,
  `delivery_method` enum('ship','pickup') NOT NULL DEFAULT 'pickup',
  `note` text DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('new','confirmed','shipping','done','cancelled') NOT NULL DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `book_orders`
--

INSERT INTO `book_orders` (`id`, `order_code`, `customer_name`, `phone`, `address`, `delivery_method`, `note`, `total_amount`, `status`, `created_at`, `updated_at`) VALUES
(1, 'BK260505D504', 'Huỳnh đức thông', '0901 234 567', '', 'pickup', 'ngbv', 500000.00, 'new', '2026-05-05 12:33:32', '2026-05-05 12:33:32'),
(2, 'BK260505C331', 'Test User', '0912345678', '', 'pickup', '', 1500000.00, 'new', '2026-05-05 12:49:50', '2026-05-05 12:49:50');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `book_order_items`
--

DROP TABLE IF EXISTS `book_order_items`;
CREATE TABLE `book_order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `book_title` varchar(200) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `book_order_items`
--

INSERT INTO `book_order_items` (`id`, `order_id`, `book_id`, `book_title`, `quantity`, `price`) VALUES
(1, 1, 1, 'Sách nấu ăn chuyên nghiệp', 1, 500000.00),
(2, 2, 1, 'Sách nấu ăn chuyên nghiệp', 3, 500000.00);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(1, 'Khai vị'),
(2, 'Món chính'),
(3, 'Tráng miệng'),
(4, 'Đồ uống'),
(5, 'Món ăn kèm');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chefs`
--

DROP TABLE IF EXISTS `chefs`;
CREATE TABLE `chefs` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `combos`
--

DROP TABLE IF EXISTS `combos`;
CREATE TABLE `combos` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(15,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `combo_items`
--

DROP TABLE IF EXISTS `combo_items`;
CREATE TABLE `combo_items` (
  `id` int(11) NOT NULL,
  `combo_id` int(11) NOT NULL,
  `food_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `employees`
--

DROP TABLE IF EXISTS `employees`;
CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `identity_card` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT 'other',
  `position` varchar(100) DEFAULT NULL,
  `salary` decimal(15,2) DEFAULT 0.00,
  `status` enum('working','on_leave','resigned') DEFAULT 'working',
  `avatar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `foods`
--

DROP TABLE IF EXISTS `foods`;
CREATE TABLE `foods` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `price` decimal(15,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `foods`
--

INSERT INTO `foods` (`id`, `category_id`, `name`, `price`, `image`, `description`, `status`, `is_active`) VALUES
(1, 2, 'bit tết', 400000.00, '7d76786780be41b26cea039d.jpg', 'đạm đà', 1, 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `food_recipes`
--

DROP TABLE IF EXISTS `food_recipes`;
CREATE TABLE `food_recipes` (
  `id` int(11) NOT NULL,
  `food_id` int(11) NOT NULL,
  `ingredient_id` int(11) NOT NULL,
  `quantity_required` decimal(10,3) NOT NULL,
  `unit` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `food_recipes`
--

INSERT INTO `food_recipes` (`id`, `food_id`, `ingredient_id`, `quantity_required`, `unit`) VALUES
(1, 1, 3, 0.500, 'kg');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `footer_links`
--

DROP TABLE IF EXISTS `footer_links`;
CREATE TABLE `footer_links` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `priority` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `footer_settings`
--

DROP TABLE IF EXISTS `footer_settings`;
CREATE TABLE `footer_settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `footer_settings`
--

INSERT INTO `footer_settings` (`setting_key`, `setting_value`) VALUES
('address', '123 Đường ABC, Quận 1, TP. HCM'),
('copyright_text', '© 2026 Restaurantly. All Rights Reserved.'),
('email', 'contact@restaurantly.com'),
('facebook_url', '#'),
('footer_bg_color', '#1f6f65'),
('footer_description', 'Trải nghiệm ẩm thực đẳng cấp giữa lòng thành phố.'),
('footer_logo', ''),
('footer_text_color', '#ffffff'),
('google_map_iframe', ''),
('instagram_url', '#'),
('opening_hours', '08:00 AM - 10:00 PM'),
('phone', '0901 234 567'),
('restaurant_name', 'Restaurantly'),
('show_map', '1'),
('show_newsletter', '0'),
('show_social', '0'),
('tiktok_url', '#');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `inventory`
--

DROP TABLE IF EXISTS `inventory`;
CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `unit_name` varchar(50) DEFAULT NULL,
  `cost_price` decimal(15,2) DEFAULT 0.00,
  `supplier_id` int(11) DEFAULT NULL,
  `entry_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `revenue` decimal(15,2) DEFAULT 0.00,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `min_stock` float DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `storage_zone` varchar(50) DEFAULT 'Kho khô'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `inventory`
--

INSERT INTO `inventory` (`id`, `item_name`, `category`, `unit_name`, `cost_price`, `supplier_id`, `entry_date`, `expiry_date`, `revenue`, `updated_at`, `min_stock`, `is_active`, `storage_zone`) VALUES
(3, 'thịt bò', 'Thịt', 'kg', 2000000.00, NULL, NULL, '2026-05-13', 0.00, '2026-05-09 07:18:03', 5, 1, 'Kho khô');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `inventory_audits`
--

DROP TABLE IF EXISTS `inventory_audits`;
CREATE TABLE `inventory_audits` (
  `id` int(11) NOT NULL,
  `audit_date` datetime DEFAULT current_timestamp(),
  `performed_by` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `inventory_audits`
--

INSERT INTO `inventory_audits` (`id`, `audit_date`, `performed_by`, `notes`) VALUES
(1, '2026-04-29 20:12:52', 'Admin', ''),
(2, '2026-04-29 20:13:38', 'Admin', ''),
(3, '2026-05-05 11:37:52', 'Admin', ''),
(4, '2026-05-07 09:38:23', 'Admin', ''),
(5, '2026-05-07 09:50:36', 'Admin', '');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `inventory_audit_details`
--

DROP TABLE IF EXISTS `inventory_audit_details`;
CREATE TABLE `inventory_audit_details` (
  `id` int(11) NOT NULL,
  `audit_id` int(11) DEFAULT NULL,
  `ingredient_id` int(11) DEFAULT NULL,
  `system_qty` float DEFAULT NULL,
  `physical_qty` float DEFAULT NULL,
  `variance` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `inventory_audit_details`
--

INSERT INTO `inventory_audit_details` (`id`, `audit_id`, `ingredient_id`, `system_qty`, `physical_qty`, `variance`) VALUES
(1, 1, 1, 200, 200, 0),
(2, 1, 2, 29, 29, 0),
(3, 2, 1, 192, 192, 0),
(4, 2, 2, 29, 29, 0),
(5, 3, 1, 187, 187, 0),
(6, 3, 3, 60, 60, 0),
(7, 4, 4, 1, 1, 0),
(8, 4, 1, 170, 170, 0),
(9, 4, 3, 70, 70, 0),
(10, 5, 4, 0, 0, 0),
(11, 5, 1, 10, 10, 0),
(12, 5, 3, 0, 0, 0);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `inventory_categories`
--

DROP TABLE IF EXISTS `inventory_categories`;
CREATE TABLE `inventory_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `inventory_categories`
--

INSERT INTO `inventory_categories` (`id`, `name`) VALUES
(1, 'Thịt'),
(2, 'Rau củ'),
(3, 'Gia vị'),
(4, 'Đồ uống'),
(5, 'rau');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `inventory_history`
--

DROP TABLE IF EXISTS `inventory_history`;
CREATE TABLE `inventory_history` (
  `id` int(11) NOT NULL,
  `ingredient_id` int(11) DEFAULT NULL,
  `warehouse_id` int(11) DEFAULT NULL,
  `type` enum('import','export','loss') NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `performed_by` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `inventory_history`
--

INSERT INTO `inventory_history` (`id`, `ingredient_id`, `warehouse_id`, `type`, `quantity`, `created_at`, `performed_by`) VALUES
(1, 1, 1, 'import', 100.00, '2026-04-02 07:53:10', NULL),
(2, 1, 1, 'export', 1.00, '2026-04-02 08:27:06', NULL),
(3, 1, 1, 'import', 100.00, '2026-04-26 13:37:33', 'Admin'),
(4, 1, 1, 'import', 100.00, '2026-04-26 13:38:03', 'Admin'),
(5, 1, 1, 'export', 99.00, '2026-04-26 13:38:24', 'Admin'),
(6, 1, 1, 'export', 8.00, '2026-04-29 13:13:35', 'Admin'),
(7, 1, 1, 'export', 1.00, '2026-05-01 13:28:30', NULL),
(8, 1, 1, 'export', 1.00, '2026-05-02 10:08:15', 'Admin'),
(9, 1, 1, 'export', 1.00, '2026-05-04 03:34:03', NULL),
(10, 1, 1, 'export', 1.00, '2026-05-04 03:37:13', 'Admin'),
(11, 1, 1, 'export', 1.00, '2026-05-04 03:43:33', NULL),
(12, 1, 1, 'export', 1.00, '2026-05-05 04:42:52', NULL),
(13, 1, 1, 'export', 6.00, '2026-05-06 08:05:01', 'Admin'),
(14, 1, 1, 'export', 10.00, '2026-05-07 02:55:19', 'Admin (Chuyển đi #2)'),
(15, 1, 3, 'import', 10.00, '2026-05-07 02:55:19', 'Admin (Nhận từ #2)'),
(16, 1, 1, 'export', 10.00, '2026-05-07 02:55:23', 'Admin (Chuyển đi #1)'),
(17, 1, 3, 'import', 10.00, '2026-05-07 02:55:23', 'Admin (Nhận từ #1)'),
(18, 6, 1, 'import', 10.00, '2026-05-07 07:37:19', 'Admin (Nhận hàng từ PO #3)'),
(19, 6, 1, 'import', 10.00, '2026-05-07 07:38:15', 'Admin'),
(20, 6, 1, 'export', 10.00, '2026-05-07 07:39:21', 'Admin (Chuyển đi #3)'),
(21, 6, 2, 'import', 10.00, '2026-05-07 07:39:21', 'Admin (Nhận từ #3)'),
(22, 1, 1, 'export', 10.00, '2026-05-07 07:39:21', 'Admin (Chuyển đi #3)'),
(23, 1, 2, 'import', 10.00, '2026-05-07 07:39:21', 'Admin (Nhận từ #3)'),
(24, 1, 1, 'export', 10.00, '2026-05-07 07:45:20', 'Admin (Chuyển đi #4)'),
(25, 1, 3, 'import', 10.00, '2026-05-07 07:45:20', 'Admin (Nhận từ #4)'),
(26, 1, 1, 'export', 10.00, '2026-05-09 03:51:45', 'Admin (Chuyển đi #0)'),
(27, 1, 3, 'import', 10.00, '2026-05-09 03:51:45', 'Admin (Nhận từ #0)'),
(28, 1, 1, 'export', 10.00, '2026-05-09 04:22:07', 'Admin (Chuyển đi #0)'),
(29, 1, 3, 'import', 10.00, '2026-05-09 04:22:07', 'Admin (Nhận từ #0)'),
(30, 6, 1, 'export', 5.00, '2026-05-09 04:22:07', 'Admin (Chuyển đi #0)'),
(31, 6, 3, 'import', 5.00, '2026-05-09 04:22:07', 'Admin (Nhận từ #0)'),
(32, 6, 1, 'loss', 5.00, '2026-05-09 04:22:39', 'Admin'),
(33, 1, 1, 'export', 20.00, '2026-05-09 06:39:20', 'Admin'),
(34, 1, 1, 'loss', 20.00, '2026-05-09 06:39:34', 'Admin'),
(35, 1, 1, 'loss', 70.00, '2026-05-09 06:39:43', 'Admin'),
(36, 1, 3, 'loss', 30.00, '2026-05-09 06:39:52', 'Admin'),
(37, 1, 2, 'loss', 10.00, '2026-05-09 06:39:59', 'Admin'),
(38, 1, 1, 'import', 40.00, '2026-05-09 06:40:22', 'Admin'),
(39, 1, 1, 'export', 5.00, '2026-05-09 06:52:19', 'Admin (Chuyển đi #9)'),
(40, 1, 3, 'import', 5.00, '2026-05-09 06:52:19', 'Admin (Nhận từ #9)'),
(41, 2, 1, 'import', 40.00, '2026-05-09 07:02:56', 'Admin'),
(42, 2, 1, 'import', 40.00, '2026-05-09 07:17:21', 'Admin'),
(43, 3, 1, 'import', 40.00, '2026-05-09 07:18:03', 'Admin'),
(44, 3, 1, 'export', 15.00, '2026-05-09 07:18:24', 'Admin (Chuyển đi #10)'),
(45, 3, 3, 'import', 15.00, '2026-05-09 07:18:24', 'Admin (Nhận từ #10)'),
(46, 3, 3, 'export', 15.00, '2026-05-09 07:18:56', 'Admin (Chuyển đi #11)'),
(47, 3, 2, 'import', 15.00, '2026-05-09 07:18:56', 'Admin (Nhận từ #11)');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `inventory_receipts`
--

DROP TABLE IF EXISTS `inventory_receipts`;
CREATE TABLE `inventory_receipts` (
  `id` int(11) NOT NULL,
  `ingredient_id` int(11) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `import_price` decimal(15,2) NOT NULL,
  `entry_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `inventory_stocks`
--

DROP TABLE IF EXISTS `inventory_stocks`;
CREATE TABLE `inventory_stocks` (
  `id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `ingredient_id` int(11) NOT NULL,
  `quantity` decimal(10,2) DEFAULT 0.00,
  `last_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `inventory_stocks`
--

INSERT INTO `inventory_stocks` (`id`, `warehouse_id`, `ingredient_id`, `quantity`, `last_updated`) VALUES
(1, 1, 1, 35.00, '2026-05-09 13:52:19'),
(8, 3, 1, 5.00, '2026-05-09 13:52:19'),
(15, 2, 1, 0.00, '2026-05-09 13:39:59'),
(20, 1, 2, 80.00, '2026-05-09 14:17:21'),
(22, 1, 3, 25.00, '2026-05-09 14:18:24'),
(23, 3, 3, 0.00, '2026-05-09 14:18:56'),
(24, 2, 3, 15.00, '2026-05-09 14:18:56');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `inventory_transfers`
--

DROP TABLE IF EXISTS `inventory_transfers`;
CREATE TABLE `inventory_transfers` (
  `id` int(11) NOT NULL,
  `from_warehouse_id` int(11) NOT NULL,
  `to_warehouse_id` int(11) NOT NULL,
  `performed_by` varchar(100) DEFAULT NULL,
  `transfer_date` datetime DEFAULT current_timestamp(),
  `note` text DEFAULT NULL,
  `status` enum('pending','completed','cancelled') DEFAULT 'pending',
  `approved_by` varchar(100) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `inventory_transfers`
--

INSERT INTO `inventory_transfers` (`id`, `from_warehouse_id`, `to_warehouse_id`, `performed_by`, `transfer_date`, `note`, `status`, `approved_by`, `approved_at`) VALUES
(1, 1, 3, 'Admin', '2026-05-06 14:17:26', 'Chuyển kho nội bộ', 'completed', 'Admin', '2026-05-07 09:55:23'),
(2, 1, 3, 'Admin', '2026-05-07 09:52:53', 'Yêu cầu chuyển kho nội bộ', 'completed', 'Admin', '2026-05-07 09:55:19'),
(3, 1, 2, 'Admin', '2026-05-07 14:39:15', 'Yêu cầu chuyển kho nội bộ (2 mặt hàng)', 'completed', 'Admin', '2026-05-07 14:39:21'),
(4, 1, 3, 'Admin', '2026-05-07 14:45:07', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-05-07 14:45:20'),
(5, 1, 3, 'Admin', '2026-05-09 10:51:42', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'cancelled', 'Admin', '2026-05-09 11:22:07'),
(6, 1, 3, 'Admin', '2026-05-09 11:22:03', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'cancelled', 'Admin', '2026-05-09 11:22:07'),
(7, 1, 3, 'Admin', '2026-05-09 13:41:38', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'cancelled', NULL, NULL),
(8, 1, 2, 'Admin', '2026-05-09 13:42:09', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'cancelled', NULL, NULL),
(9, 1, 3, 'Admin', '2026-05-09 13:52:16', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-05-09 13:52:19'),
(10, 1, 3, 'Admin', '2026-05-09 14:18:19', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-05-09 14:18:24'),
(11, 3, 2, 'Admin', '2026-05-09 14:18:51', 'Yêu cầu chuyển kho nội bộ (1 mặt hàng)', 'completed', 'Admin', '2026-05-09 14:18:56');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `inventory_units`
--

DROP TABLE IF EXISTS `inventory_units`;
CREATE TABLE `inventory_units` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `inventory_units`
--

INSERT INTO `inventory_units` (`id`, `name`) VALUES
(1, 'kg'),
(2, 'gram'),
(3, 'lít'),
(5, 'cái'),
(6, 'chai');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `navigation_menu`
--

DROP TABLE IF EXISTS `navigation_menu`;
CREATE TABLE `navigation_menu` (
  `id` int(11) NOT NULL,
  `title` varchar(100) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `position` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `newsletters`
--

DROP TABLE IF EXISTS `newsletters`;
CREATE TABLE `newsletters` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `order_items`
--

DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `food_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `purchase_orders`
--

DROP TABLE IF EXISTS `purchase_orders`;
CREATE TABLE `purchase_orders` (
  `id` int(11) NOT NULL,
  `po_code` varchar(50) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `status` enum('pending','completed','cancelled') DEFAULT 'pending',
  `total_amount` decimal(15,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `purchase_orders`
--

INSERT INTO `purchase_orders` (`id`, `po_code`, `supplier_id`, `created_by`, `created_at`, `status`, `total_amount`, `notes`) VALUES
(2, 'PO-20260506092315', 1, NULL, '2026-05-06 14:23:15', 'completed', 1500000.00, NULL),
(3, 'PO-20260507093712', 1, NULL, '2026-05-07 14:37:12', 'completed', 20000000.00, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `purchase_order_details`
--

DROP TABLE IF EXISTS `purchase_order_details`;
CREATE TABLE `purchase_order_details` (
  `id` int(11) NOT NULL,
  `po_id` int(11) NOT NULL,
  `ingredient_id` int(11) NOT NULL,
  `expected_qty` decimal(10,2) NOT NULL,
  `expected_price` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `purchase_order_details`
--

INSERT INTO `purchase_order_details` (`id`, `po_id`, `ingredient_id`, `expected_qty`, `expected_price`) VALUES
(2, 3, 6, 10.00, 2000000.00);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `restaurant_tables`
--

DROP TABLE IF EXISTS `restaurant_tables`;
CREATE TABLE `restaurant_tables` (
  `id` int(11) NOT NULL,
  `table_code` varchar(20) NOT NULL,
  `table_number` varchar(10) DEFAULT NULL,
  `room_type` varchar(50) DEFAULT NULL,
  `category` enum('open','room') DEFAULT 'open',
  `capacity` int(11) DEFAULT 16,
  `price` decimal(15,2) DEFAULT 0.00,
  `status` varchar(20) DEFAULT 'available',
  `is_available` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `restaurant_tables`
--

INSERT INTO `restaurant_tables` (`id`, `table_code`, `table_number`, `room_type`, `category`, `capacity`, `price`, `status`, `is_available`) VALUES
(1, 'T1', '1', NULL, 'open', 6, 0.00, 'available', 1),
(2, 'T2', '2', NULL, 'open', 6, 0.00, 'available', 1),
(3, 'T3', '3', NULL, 'open', 6, 0.00, 'available', 1),
(4, 'T4', '4', NULL, 'open', 6, 0.00, 'available', 1),
(5, 'T5', '5', NULL, 'open', 6, 0.00, 'available', 1),
(6, 'T6', '6', NULL, 'open', 6, 0.00, 'available', 1),
(7, 'T7', '7', NULL, 'open', 6, 0.00, 'available', 1),
(8, 'T8', '8', NULL, 'open', 6, 0.00, 'available', 1),
(9, 'T9', '9', NULL, 'open', 6, 0.00, 'available', 1),
(10, 'T10', '10', NULL, 'open', 6, 0.00, 'available', 1),
(11, 'T11', '11', NULL, 'open', 6, 0.00, 'available', 1),
(12, 'T12', '12', NULL, 'open', 6, 0.00, 'available', 1),
(13, 'T13', '13', NULL, 'open', 6, 0.00, 'available', 1),
(14, 'T14', '14', NULL, 'open', 6, 0.00, 'available', 1),
(15, 'T15', '15', NULL, 'open', 6, 0.00, 'available', 1),
(16, 'T16', '16', NULL, 'open', 6, 0.00, 'available', 1),
(17, 'VIP1', '101', NULL, 'room', 16, 0.00, 'available', 1),
(18, 'VIP2', '102', NULL, 'room', 16, 0.00, 'available', 1),
(19, 'VIP3', '103', NULL, 'room', 16, 0.00, 'available', 1),
(20, 'VIP4', '104', NULL, 'room', 16, 0.00, 'available', 1),
(21, 'VIP5', '105', NULL, 'room', 16, 0.00, 'available', 1),
(22, 'VIP6', '106', NULL, 'room', 16, 0.00, 'available', 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `services`
--

DROP TABLE IF EXISTS `services`;
CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `service_name` varchar(255) DEFAULT NULL,
  `price` decimal(15,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `service_bookings`
--

DROP TABLE IF EXISTS `service_bookings`;
CREATE TABLE `service_bookings` (
  `id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `booking_date` datetime NOT NULL,
  `service_type` enum('table','birthday','chef') DEFAULT 'table',
  `table_id` int(11) DEFAULT NULL,
  `combo_id` int(11) DEFAULT NULL,
  `guests` int(11) DEFAULT 1,
  `message` text DEFAULT NULL,
  `total_amount` decimal(15,2) DEFAULT 0.00,
  `deposit_amount` decimal(15,2) DEFAULT 0.00,
  `status` enum('Pending','Confirmed','Completed','Cancelled') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `settings`
--

DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `key_name` varchar(50) NOT NULL,
  `key_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `settings`
--

INSERT INTO `settings` (`key_name`, `key_value`) VALUES
('address', 'biên hòa'),
('email', ''),
('facebook_url', ''),
('footer_text', '© 2024 Restaurantly. All Rights Reserved.'),
('hotline', '0456789124'),
('logo_url', 'assets/img/logo.png'),
('logo_ver', '1778058575'),
('maps_embed', ''),
('meta_desc', ''),
('name_position', 'left'),
('open_days', 'Thứ 3 - Chủ Nhật'),
('open_time', '09:00 AM - 11:00 PM'),
('restaurant_name', 'Restaurantly'),
('zalo_url', '');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `email` varchar(100) DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `phone`, `address`, `created_at`, `email`, `contact_person`) VALUES
(1, 'công ty fpt', '012345678', 'bvhb', '2026-04-26 13:37:03', 'long@gmail.com', 'long');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `transfer_details`
--

DROP TABLE IF EXISTS `transfer_details`;
CREATE TABLE `transfer_details` (
  `id` int(11) NOT NULL,
  `transfer_id` int(11) NOT NULL,
  `ingredient_id` int(11) NOT NULL,
  `quantity` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `transfer_details`
--

INSERT INTO `transfer_details` (`id`, `transfer_id`, `ingredient_id`, `quantity`) VALUES
(1, 1, 1, 10.00),
(2, 2, 1, 10.00),
(3, 3, 6, 10.00),
(4, 3, 1, 10.00),
(5, 4, 1, 10.00),
(10, 9, 1, 5.00),
(11, 10, 3, 15.00),
(12, 11, 3, 15.00);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `role` enum('admin','cashier','chef','waiter') DEFAULT 'waiter',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `phone`, `email`, `google_id`, `role`, `is_active`, `created_at`) VALUES
(2, 'Huỳnh Đức Thông', '', 'Quản trị viên', NULL, '28huynhducthong@gmail.com', '107664704264935131673', 'admin', 1, '2026-05-06 10:49:37'),
(4, 'Thong Duc', '', '', NULL, 'thongd342@gmail.com', '100631379832642815829', '', 1, '2026-05-06 15:59:00'),
(5, 'Huỳnh Dương', '', '', NULL, 'www.huynhqduong@gmail.com', '112739013180770480868', 'admin', 1, '2026-05-09 21:03:14');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `videos`
--

DROP TABLE IF EXISTS `videos`;
CREATE TABLE `videos` (
  `id` int(11) NOT NULL,
  `video_type` enum('youtube','local') DEFAULT 'youtube',
  `video_url` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `videos`
--

INSERT INTO `videos` (`id`, `video_type`, `video_url`, `file_path`, `created_at`) VALUES
(1, 'youtube', 'dQw4w9WgXcQ', '', '2026-04-02 07:36:16');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `warehouses`
--

DROP TABLE IF EXISTS `warehouses`;
CREATE TABLE `warehouses` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('main','kitchen','bar') DEFAULT 'main',
  `status` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `warehouses`
--

INSERT INTO `warehouses` (`id`, `name`, `type`, `status`) VALUES
(1, 'Kho Tổng (Tiếp nhận hàng)', 'main', 1),
(2, 'Kho Bếp (Chế biến thức ăn)', 'kitchen', 1),
(3, 'Kho Bar (Pha chế đồ uống)', 'bar', 1);

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `about_categories`
--
ALTER TABLE `about_categories`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `about_content`
--
ALTER TABLE `about_content`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Chỉ mục cho bảng `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `banners`
--
ALTER TABLE `banners`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `booking_details`
--
ALTER TABLE `booking_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_booking_service` (`booking_id`);

--
-- Chỉ mục cho bảng `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `book_orders`
--
ALTER TABLE `book_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_code` (`order_code`),
  ADD KEY `idx_book_orders_status` (`status`),
  ADD KEY `idx_book_orders_created` (`created_at`);

--
-- Chỉ mục cho bảng `book_order_items`
--
ALTER TABLE `book_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Chỉ mục cho bảng `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `chefs`
--
ALTER TABLE `chefs`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `combos`
--
ALTER TABLE `combos`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `combo_items`
--
ALTER TABLE `combo_items`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `foods`
--
ALTER TABLE `foods`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `food_recipes`
--
ALTER TABLE `food_recipes`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `footer_links`
--
ALTER TABLE `footer_links`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `inventory_audits`
--
ALTER TABLE `inventory_audits`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `inventory_audit_details`
--
ALTER TABLE `inventory_audit_details`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `inventory_categories`
--
ALTER TABLE `inventory_categories`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `inventory_history`
--
ALTER TABLE `inventory_history`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `inventory_receipts`
--
ALTER TABLE `inventory_receipts`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `inventory_stocks`
--
ALTER TABLE `inventory_stocks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_wh_ing` (`warehouse_id`,`ingredient_id`);

--
-- Chỉ mục cho bảng `inventory_transfers`
--
ALTER TABLE `inventory_transfers`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `inventory_units`
--
ALTER TABLE `inventory_units`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `navigation_menu`
--
ALTER TABLE `navigation_menu`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `newsletters`
--
ALTER TABLE `newsletters`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `purchase_order_details`
--
ALTER TABLE `purchase_order_details`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `restaurant_tables`
--
ALTER TABLE `restaurant_tables`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `service_bookings`
--
ALTER TABLE `service_bookings`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `transfer_details`
--
ALTER TABLE `transfer_details`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `videos`
--
ALTER TABLE `videos`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `warehouses`
--
ALTER TABLE `warehouses`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `about_categories`
--
ALTER TABLE `about_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `about_content`
--
ALTER TABLE `about_content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `banners`
--
ALTER TABLE `banners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT cho bảng `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `booking_details`
--
ALTER TABLE `booking_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `books`
--
ALTER TABLE `books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `book_orders`
--
ALTER TABLE `book_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `book_order_items`
--
ALTER TABLE `book_order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `chefs`
--
ALTER TABLE `chefs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `combos`
--
ALTER TABLE `combos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `combo_items`
--
ALTER TABLE `combo_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `foods`
--
ALTER TABLE `foods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `food_recipes`
--
ALTER TABLE `food_recipes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `footer_links`
--
ALTER TABLE `footer_links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `inventory_audits`
--
ALTER TABLE `inventory_audits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `inventory_audit_details`
--
ALTER TABLE `inventory_audit_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT cho bảng `inventory_categories`
--
ALTER TABLE `inventory_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `inventory_history`
--
ALTER TABLE `inventory_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT cho bảng `inventory_receipts`
--
ALTER TABLE `inventory_receipts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `inventory_stocks`
--
ALTER TABLE `inventory_stocks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT cho bảng `inventory_transfers`
--
ALTER TABLE `inventory_transfers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT cho bảng `inventory_units`
--
ALTER TABLE `inventory_units`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `navigation_menu`
--
ALTER TABLE `navigation_menu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `newsletters`
--
ALTER TABLE `newsletters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `purchase_order_details`
--
ALTER TABLE `purchase_order_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `restaurant_tables`
--
ALTER TABLE `restaurant_tables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT cho bảng `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `service_bookings`
--
ALTER TABLE `service_bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `transfer_details`
--
ALTER TABLE `transfer_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `videos`
--
ALTER TABLE `videos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `warehouses`
--
ALTER TABLE `warehouses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `book_order_items`
--
ALTER TABLE `book_order_items`
  ADD CONSTRAINT `book_order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `book_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `book_order_items_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
