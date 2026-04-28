-- Bảng cấu hình Footer linh hoạt
CREATE TABLE IF NOT EXISTS footer_settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Chèn dữ liệu mặc định tránh lỗi trống giao diện
INSERT INTO footer_settings (setting_key, setting_value) VALUES 
('restaurant_name', 'Restaurantly'),
('footer_logo', ''),
('footer_description', 'Trải nghiệm ẩm thực đẳng cấp giữa lòng thành phố.'),
('address', '123 Biên Hòa, Đồng Nai'),
('phone', '0901 234 567'),
('email', 'contact@restaurantly.com'),
('opening_hours', '08:00 AM - 10:00 PM'),
('facebook_url', '#'),
('instagram_url', '#'),
('tiktok_url', '#'),
('show_social', '1'),
('show_newsletter', '1'),
('show_map', '1'),
('google_map_iframe', ''),
('footer_bg_color', '#0c0b09'),
('footer_text_color', '#ffffff'),
('footer_bg_image', ''),
('copyright_text', '© 2026 Restaurantly. All Rights Reserved.');

-- Bảng lưu Liên kết nhanh (Quick Links)
CREATE TABLE IF NOT EXISTS footer_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    url VARCHAR(255) NOT NULL,
    priority INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;