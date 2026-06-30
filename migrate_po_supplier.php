<?php
require_once __DIR__ . '/config/database.php';
$db = (new Database())->getConnection();

try {
    $db->beginTransaction();

    // 1. Thêm cột origin_country, transport_conditions vào suppliers
    $db->exec("ALTER TABLE suppliers 
        ADD COLUMN origin_country VARCHAR(100) DEFAULT NULL,
        ADD COLUMN transport_conditions VARCHAR(255) DEFAULT NULL
    ");

    // 2. Tạo bảng supplier_certificates
    $db->exec("CREATE TABLE IF NOT EXISTS supplier_certificates (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        supplier_id INT(11) NOT NULL,
        cert_type VARCHAR(50) NOT NULL,
        cert_name VARCHAR(100) DEFAULT NULL,
        cert_number VARCHAR(100) DEFAULT NULL,
        issue_date DATE DEFAULT NULL,
        expiry_date DATE DEFAULT NULL,
        file_path VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // 3. Migrate ATVSTP cũ sang supplier_certificates
    $stmt = $db->query("SELECT id, atvstp_file, atvstp_expiry FROM suppliers WHERE atvstp_file IS NOT NULL OR atvstp_expiry IS NOT NULL");
    $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $insertCert = $db->prepare("INSERT INTO supplier_certificates (supplier_id, cert_type, cert_name, expiry_date, file_path) VALUES (?, 'ATVSTP', 'Chứng nhận ATVSTP', ?, ?)");
    foreach ($suppliers as $s) {
        $insertCert->execute([
            $s['id'],
            $s['atvstp_expiry'],
            $s['atvstp_file']
        ]);
    }

    // 4. Xóa cột cũ khỏi suppliers
    $db->exec("ALTER TABLE suppliers DROP COLUMN atvstp_file, DROP COLUMN atvstp_expiry");

    // 5. Thêm supplier_batch_number vào inventory_batches
    $db->exec("ALTER TABLE inventory_batches 
        ADD COLUMN supplier_batch_number VARCHAR(100) DEFAULT NULL
    ");

    // 6. Cập nhật các lô hàng cũ (dùng batch_code làm supplier_batch_number mặc định)
    $db->exec("UPDATE inventory_batches SET supplier_batch_number = batch_code WHERE supplier_batch_number IS NULL");

    // 7. Tạo bảng po_receipt_inspections
    $db->exec("CREATE TABLE IF NOT EXISTS po_receipt_inspections (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        po_id INT(11) NOT NULL,
        ingredient_id INT(11) NOT NULL,
        image_path VARCHAR(255) DEFAULT NULL,
        check_packaging TINYINT(1) DEFAULT 0,
        check_color TINYINT(1) DEFAULT 0,
        check_odor TINYINT(1) DEFAULT 0,
        check_freshness TINYINT(1) DEFAULT 0,
        check_size TINYINT(1) DEFAULT 0,
        check_weight TINYINT(1) DEFAULT 0,
        notes TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (po_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
        FOREIGN KEY (ingredient_id) REFERENCES inventory(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    $db->commit();
    echo "Migration completed successfully!\n";

} catch (Exception $e) {
    $db->rollBack();
    echo "Migration failed: " . $e->getMessage() . "\n";
}
