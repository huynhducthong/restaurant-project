-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th5 02, 2026 lúc 02:30 PM
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
-- Cấu trúc bảng cho bảng `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `banners`
--

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
  `desc_font_size` int(11) DEFAULT 24
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `banners`
--

INSERT INTO `banners` (`id`, `image_url`, `title`, `description`, `font_family`, `text_color`, `text_align`, `font_style`, `display_order`, `created_at`, `desc_color`, `desc_font_family`, `desc_font_style`, `title_font_size`, `desc_font_size`) VALUES
(2, '1776687242_hero-bg.jpg', 'retauranlly ', 'ăn ngon', '\'Playfair Display\', serif', '#240a0a', 'center', 'bold', 1, '2026-04-20 12:14:02', '#c18b8b', '\'Poppins\', sans-serif', 'normal', 48, 24),
(7, '1776687610_hero-bg-2.jpg', 'huhf', 'nbzbn', '\'Playfair Display\', serif', '#d71d1d', 'left', 'normal', 2, '2026-04-20 12:20:10', '#eeeeee', '\'Poppins\', sans-serif', 'normal', 48, 24);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(1, 'Khai vị'),
(5, 'Món ăn kèm'),
(2, 'Món chính'),
(3, 'Tráng miệng'),
(4, 'Đồ uống');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chefs`
--

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

CREATE TABLE `combos` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(15,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `combos`
--

INSERT INTO `combos` (`id`, `name`, `description`, `price`, `image`, `status`, `is_active`) VALUES
(1, 'combo gia đình', 'btrdn', 1000000.00, '1775395639_Screenshot 2026-04-05 200746.png', 1, 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `combo_items`
--

CREATE TABLE `combo_items` (
  `id` int(11) NOT NULL,
  `combo_id` int(11) NOT NULL,
  `food_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `combo_items`
--

INSERT INTO `combo_items` (`id`, `combo_id`, `food_id`) VALUES
(7, 1, 2),
(8, 1, 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `foods`
--

CREATE TABLE `foods` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `price` decimal(15,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `foods`
--

INSERT INTO `foods` (`id`, `category_id`, `name`, `price`, `image`, `description`, `status`, `is_active`) VALUES
(1, 4, 'rựu vang', 500000.00, '1775141620_Screenshot 2026-04-02 213852.png', 'dfb', 1, 1),
(2, 2, 'Bò bít tết', 800000.00, '1775392540_Screenshot 2026-04-03 121754.png', 'vgfnbwd', 1, 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `food_recipes`
--

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
(1, 1, 1, 1.000, 'chai'),
(2, 2, 2, 0.500, 'kg');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `footer_links`
--

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
('footer_bg_color', '#43413d'),
('footer_bg_image', ''),
('footer_description', 'Trải nghiệm ẩm thực đẳng cấp giữa lòng thành phố.'),
('footer_logo', ''),
('footer_text_color', '#ffffff'),
('google_map_iframe', '<iframe src=\"https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15668.013649197086!2d106.94819840000001!3d10.96311425!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3174e74704fe730b%3A0xe983a7428fb01ae8!2zTmjDoCB0aOG7nSBHacOhbyB44bupIFRow6FpIEjDsmE!5e0!3m2!1svi!2s!4v1777337782502!5m2!1svi!2s\" width=\"600\" height=\"450\" style=\"border:0;\" allowfullscreen=\"\" loading=\"lazy\" referrerpolicy=\"no-referrer-when-downgrade\"></iframe>'),
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

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
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
  `min_stock` float DEFAULT 5
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `inventory`
--

INSERT INTO `inventory` (`id`, `item_name`, `category`, `unit_name`, `stock_quantity`, `cost_price`, `supplier_id`, `entry_date`, `expiry_date`, `revenue`, `updated_at`, `min_stock`) VALUES
(1, 'rựu', 'Đồ uống', 'chai', 99.00, 1200000.00, NULL, NULL, NULL, 0.00, '2026-04-02 08:27:06', 5),
(2, 'thịt bò', 'Thịt', 'kg', 10.00, 10000000.00, NULL, NULL, NULL, 0.00, '2026-04-05 05:31:10', 5);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `inventory_categories`
--

CREATE TABLE `inventory_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `inventory_categories`
--

INSERT INTO `inventory_categories` (`id`, `name`) VALUES
(3, 'Gia vị'),
(5, 'rau'),
(2, 'Rau củ'),
(1, 'Thịt'),
(4, 'Đồ uống');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `inventory_history`
--

CREATE TABLE `inventory_history` (
  `id` int(11) NOT NULL,
  `ingredient_id` int(11) DEFAULT NULL,
  `type` enum('import','export','loss') NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `inventory_history`
--

INSERT INTO `inventory_history` (`id`, `ingredient_id`, `type`, `quantity`, `created_at`) VALUES
(1, 1, 'import', 100.00, '2026-04-02 07:53:10'),
(2, 1, 'export', 1.00, '2026-04-02 08:27:06'),
(3, 2, 'import', 10.00, '2026-04-05 05:31:10');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `inventory_receipts`
--

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
-- Cấu trúc bảng cho bảng `inventory_units`
--

CREATE TABLE `inventory_units` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `inventory_units`
--

INSERT INTO `inventory_units` (`id`, `name`) VALUES
(5, 'cái'),
(6, 'chai'),
(2, 'gram'),
(1, 'kg'),
(3, 'lít');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `navigation_menu`
--

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

CREATE TABLE `newsletters` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `restaurant_tables`
--

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

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `service_name` varchar(255) DEFAULT NULL,
  `price` decimal(15,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `service_bookings`
--

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

--
-- Đang đổ dữ liệu cho bảng `service_bookings`
--

INSERT INTO `service_bookings` (`id`, `customer_name`, `customer_phone`, `booking_date`, `service_type`, `table_id`, `combo_id`, `guests`, `message`, `total_amount`, `deposit_amount`, `status`, `created_at`) VALUES
(5, 'Huỳnh đức thông', '109876512345', '2026-04-04 22:25:00', 'table', NULL, NULL, 2, '', 500000.00, 150000.00, 'Confirmed', '2026-04-02 08:25:35');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `settings`
--

CREATE TABLE `settings` (
  `key_name` varchar(50) NOT NULL,
  `key_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `settings`
--

INSERT INTO `settings` (`key_name`, `key_value`) VALUES
('address', 'biên hòa '),
('hotline', '0456789124'),
('logo_url', 'public/assets/img/logo.png'),
('name_position', 'left'),
('open_days', 'Thứ 3 - Chủ Nhật'),
('open_time', '09:00 AM - 11:00 PM'),
('restaurant_name', 'Restaurantly');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `email` varchar(100) DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','staff') DEFAULT 'staff',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `google_id` varchar(100) DEFAULT NULL COMMENT 'Lưu ID từ Google',
  `reset_token` varchar(255) DEFAULT NULL COMMENT 'Mã token để đổi mật khẩu',
  `reset_token_expire` datetime DEFAULT NULL COMMENT 'Thời gian hết hạn của mã token',
  `remember_token` varchar(255) DEFAULT NULL COMMENT 'Mã token cho tính năng Ghi nhớ đăng nhập'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `created_at`, `google_id`, `reset_token`, `reset_token_expire`, `remember_token`) VALUES
(1, 'Huỳnh đức thông', '28huynhducthong@gmail.com', '$2y$10$f7cOFOOX2paiJ8e4J2OnneWhZZ87.3CioY3mCximsFWmC7wbAyzni', 'admin', '2026-04-02 07:19:22', '107664704264935131673', NULL, NULL, NULL),
(8, 'Long Hoang', 'hoanglongduongle@gmail.com', '', '', '2026-04-25 15:18:13', '109729244014013210980', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `videos`
--

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
(1, 'youtube', 'dQw4w9WgXcQ', NULL, '2026-04-02 07:36:16');

--
-- Chỉ mục cho các bảng đã đổ
--

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
-- Chỉ mục cho bảng `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

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
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_combo` (`combo_id`),
  ADD KEY `fk_food_in_combo` (`food_id`);

--
-- Chỉ mục cho bảng `foods`
--
ALTER TABLE `foods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_food_category` (`category_id`);

--
-- Chỉ mục cho bảng `food_recipes`
--
ALTER TABLE `food_recipes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_recipe_food` (`food_id`),
  ADD KEY `fk_recipe_ing` (`ingredient_id`);

--
-- Chỉ mục cho bảng `footer_links`
--
ALTER TABLE `footer_links`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `footer_settings`
--
ALTER TABLE `footer_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Chỉ mục cho bảng `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_inv_supplier` (`supplier_id`);

--
-- Chỉ mục cho bảng `inventory_categories`
--
ALTER TABLE `inventory_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Chỉ mục cho bảng `inventory_history`
--
ALTER TABLE `inventory_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inventory_history_ibfk_1` (`ingredient_id`);

--
-- Chỉ mục cho bảng `inventory_receipts`
--
ALTER TABLE `inventory_receipts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_receipt_inv` (`ingredient_id`),
  ADD KEY `fk_receipt_supplier` (`supplier_id`);

--
-- Chỉ mục cho bảng `inventory_units`
--
ALTER TABLE `inventory_units`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Chỉ mục cho bảng `navigation_menu`
--
ALTER TABLE `navigation_menu`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `newsletters`
--
ALTER TABLE `newsletters`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

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
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_book_table` (`table_id`),
  ADD KEY `fk_book_combo` (`combo_id`);

--
-- Chỉ mục cho bảng `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`key_name`);

--
-- Chỉ mục cho bảng `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Chỉ mục cho bảng `videos`
--
ALTER TABLE `videos`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `combo_items`
--
ALTER TABLE `combo_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT cho bảng `foods`
--
ALTER TABLE `foods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `food_recipes`
--
ALTER TABLE `food_recipes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `footer_links`
--
ALTER TABLE `footer_links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `inventory_categories`
--
ALTER TABLE `inventory_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `inventory_history`
--
ALTER TABLE `inventory_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `inventory_receipts`
--
ALTER TABLE `inventory_receipts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT cho bảng `videos`
--
ALTER TABLE `videos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `combo_items`
--
ALTER TABLE `combo_items`
  ADD CONSTRAINT `fk_combo` FOREIGN KEY (`combo_id`) REFERENCES `combos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_food_in_combo` FOREIGN KEY (`food_id`) REFERENCES `foods` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `foods`
--
ALTER TABLE `foods`
  ADD CONSTRAINT `fk_food_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `food_recipes`
--
ALTER TABLE `food_recipes`
  ADD CONSTRAINT `fk_recipe_food` FOREIGN KEY (`food_id`) REFERENCES `foods` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_recipe_ing` FOREIGN KEY (`ingredient_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `inventory`
--
ALTER TABLE `inventory`
  ADD CONSTRAINT `fk_inv_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `inventory_history`
--
ALTER TABLE `inventory_history`
  ADD CONSTRAINT `inventory_history_ibfk_1` FOREIGN KEY (`ingredient_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `inventory_receipts`
--
ALTER TABLE `inventory_receipts`
  ADD CONSTRAINT `fk_receipt_inv` FOREIGN KEY (`ingredient_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_receipt_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `service_bookings`
--
ALTER TABLE `service_bookings`
  ADD CONSTRAINT `fk_book_combo` FOREIGN KEY (`combo_id`) REFERENCES `combos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_book_table` FOREIGN KEY (`table_id`) REFERENCES `restaurant_tables` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
