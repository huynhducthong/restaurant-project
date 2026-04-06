-- 1. Khởi tạo Database sạch
DROP DATABASE IF EXISTS restaurant_db;
CREATE DATABASE restaurant_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE restaurant_db;

-- 2. Hệ thống người dùng & Phân quyền
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff') DEFAULT 'staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 3. Quản lý Thực đơn (Foods & Categories)
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE foods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(15, 2) NOT NULL,
    image VARCHAR(255),
    description TEXT,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 4. Hệ thống Kho nguyên liệu (Inventory)
CREATE TABLE inventory_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE inventory_units (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(255) NOT NULL,
    category VARCHAR(100),
    unit_name VARCHAR(50),
    stock_quantity DECIMAL(10, 2) DEFAULT 0.00,
    cost_price DECIMAL(15, 2) DEFAULT 0.00,
    revenue DECIMAL(15, 2) DEFAULT 0.00, -- Lưu chi phí tiêu hao tích lũy cho báo cáo
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 5. Định mức món ăn (Food Recipes) - Quan trọng để trừ kho
CREATE TABLE food_recipes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    food_id INT,
    ingredient_id INT,
    quantity_required DECIMAL(10, 2) NOT NULL,
    unit VARCHAR(20),
    FOREIGN KEY (food_id) REFERENCES foods(id) ON DELETE CASCADE,
    FOREIGN KEY (ingredient_id) REFERENCES inventory(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6. Lịch sử kho (Inventory History) - Phục vụ Dashboard Nhập/Xuất/Hao hụt
CREATE TABLE inventory_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ingredient_id INT,
    type ENUM('import', 'export', 'loss') NOT NULL,
    quantity DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ingredient_id) REFERENCES inventory(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 7. Các bảng bổ trợ khác (Dựa trên danh sách hiện có của bạn)
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50),
    password VARCHAR(255)
) ENGINE=InnoDB;

CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(100),
    booking_date DATETIME,
    status VARCHAR(50)
) ENGINE=InnoDB;

CREATE TABLE booking_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT,
    food_id INT,
    quantity INT,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE chefs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    position VARCHAR(100),
    image VARCHAR(255)
) ENGINE=InnoDB;

CREATE TABLE restaurant_tables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_number VARCHAR(10),
    status VARCHAR(20) DEFAULT 'available'
) ENGINE=InnoDB;

CREATE TABLE services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(255),
    price DECIMAL(15, 2)
) ENGINE=InnoDB;

-- 8. Chèn dữ liệu mẫu cơ bản
INSERT INTO users (username, password, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'); -- Mật khẩu: password

INSERT INTO inventory_units (name) VALUES ('kg'), ('gram'), ('lít'), ('chai'), ('cái');
INSERT INTO inventory_categories (name) VALUES ('Thịt'), ('Rau củ'), ('Gia vị'), ('Đồ uống');
INSERT INTO categories (name) VALUES ('Khai vị'), ('Món chính'), ('Tráng miệng'), ('Đồ uống');