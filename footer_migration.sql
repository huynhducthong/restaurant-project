-- ============================================================
-- FOOTER MANAGEMENT SYSTEM - Migration Script
-- Chạy file này để thêm các bảng và cài đặt footer mới
-- ============================================================

USE `restaurant_db`;

-- 1. Bảng quản lý liên kết nhanh (Quick Links) trong footer
CREATE TABLE IF NOT EXISTS `footer_links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(150) NOT NULL COMMENT 'Tên hiển thị của liên kết',
  `url` varchar(255) NOT NULL DEFAULT '#' COMMENT 'Đường dẫn URL',
  `priority` int(11) DEFAULT 0 COMMENT 'Thứ tự sắp xếp (số nhỏ = lên trên)',
  `is_active` tinyint(1) DEFAULT 1 COMMENT '1=Hiện, 0=Ẩn',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dữ liệu mẫu cho liên kết nhanh
INSERT INTO `footer_links` (`title`, `url`, `display_order`, `is_active`) VALUES
('Trang Chủ', 'index.php', 1, 1),
('Thực Đơn', 'menu.php', 2, 1),
('Dịch Vụ', 'services.php', 3, 1),
('Đặt Bàn', 'booking_service.php', 4, 1),
('Liên Hệ', '#contact', 5, 1);

-- 2. Bảng đăng ký nhận tin (Newsletter)
CREATE TABLE IF NOT EXISTS `newsletter_subscribers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(150) NOT NULL,
  `subscribed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Thêm các cài đặt footer vào bảng settings hiện có
-- (Dùng INSERT IGNORE để tránh lỗi nếu key đã tồn tại)

-- Thương hiệu & Mô tả
INSERT IGNORE INTO `settings` (`key_name`, `key_value`) VALUES ('footer_brand_desc', 'Trải nghiệm ẩm thực đỉnh cao tại trung tâm thành phố. Chúng tôi mang đến hương vị và không gian sang trọng khó quên.');

-- Thông tin liên hệ (kế thừa từ address, hotline, open_time đã có)
INSERT IGNORE INTO `settings` (`key_name`, `key_value`) VALUES ('footer_email', 'contact@restaurantly.vn');

-- Mạng xã hội
INSERT IGNORE INTO `settings` (`key_name`, `key_value`) VALUES ('footer_facebook', 'https://facebook.com/restaurantly');
INSERT IGNORE INTO `settings` (`key_name`, `key_value`) VALUES ('footer_instagram', 'https://instagram.com/restaurantly');
INSERT IGNORE INTO `settings` (`key_name`, `key_value`) VALUES ('footer_tiktok', '');
INSERT IGNORE INTO `settings` (`key_name`, `key_value`) VALUES ('footer_youtube', '');
INSERT IGNORE INTO `settings` (`key_name`, `key_value`) VALUES ('footer_show_facebook', '1');
INSERT IGNORE INTO `settings` (`key_name`, `key_value`) VALUES ('footer_show_instagram', '1');
INSERT IGNORE INTO `settings` (`key_name`, `key_value`) VALUES ('footer_show_tiktok', '0');
INSERT IGNORE INTO `settings` (`key_name`, `key_value`) VALUES ('footer_show_youtube', '0');

-- Newsletter
INSERT IGNORE INTO `settings` (`key_name`, `key_value`) VALUES ('footer_newsletter_enabled', '1');
INSERT IGNORE INTO `settings` (`key_name`, `key_value`) VALUES ('footer_newsletter_title', 'Đăng Ký Nhận Ưu Đãi');
INSERT IGNORE INTO `settings` (`key_name`, `key_value`) VALUES ('footer_newsletter_desc', 'Nhận thông tin về thực đơn mới và ưu đãi đặc biệt.');

-- Google Maps
INSERT IGNORE INTO `settings` (`key_name`, `key_value`) VALUES ('footer_show_map', '1');
INSERT IGNORE INTO `settings` (`key_name`, `key_value`) VALUES ('footer_map_iframe', '');

-- Bản quyền
INSERT IGNORE INTO `settings` (`key_name`, `key_value`) VALUES ('footer_copyright', '© 2025 Restaurantly. All Rights Reserved.');

-- Cấu hình hiển thị (bật/tắt từng section)
INSERT IGNORE INTO `settings` (`key_name`, `key_value`) VALUES ('footer_show_brand', '1');
INSERT IGNORE INTO `settings` (`key_name`, `key_value`) VALUES ('footer_show_links', '1');
INSERT IGNORE INTO `settings` (`key_name`, `key_value`) VALUES ('footer_show_contact', '1');
INSERT IGNORE INTO `settings` (`key_name`, `key_value`) VALUES ('footer_show_social', '1');

-- Layout (3col / 4col)
INSERT IGNORE INTO `settings` (`key_name`, `key_value`) VALUES ('footer_layout', '4col');

-- Màu sắc tùy chỉnh
INSERT IGNORE INTO `settings` (`key_name`, `key_value`) VALUES ('footer_bg_color', '#0c0b09');
INSERT IGNORE INTO `settings` (`key_name`, `key_value`) VALUES ('footer_accent_color', '#cda45e');
INSERT IGNORE INTO `settings` (`key_name`, `key_value`) VALUES ('footer_text_color', '#adb5bd');

SELECT 'Footer migration completed successfully!' AS Status;