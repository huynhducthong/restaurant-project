-- File: migration_chef.sql

CREATE TABLE IF NOT EXISTS chef_gallery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chef_id INT NOT NULL,
    image VARCHAR(255) NOT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (chef_id) REFERENCES chefs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chef_certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chef_id INT NOT NULL,
    certificate_name VARCHAR(255) NOT NULL,
    issuer VARCHAR(255) NOT NULL,
    issue_date DATE DEFAULT NULL,
    certificate_image VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (chef_id) REFERENCES chefs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
