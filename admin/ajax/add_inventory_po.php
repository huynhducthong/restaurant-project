<?php
$db = new PDO('mysql:host=localhost;dbname=restaurant_db;charset=utf8mb4', 'root', '');
$items = [
    ['name' => 'Phô mai Burrata', 'cat' => 'Thực phẩm', 'unit' => 'viên', 'zone' => 'Kho mát', 'temp' => 'Mát (2-4°C)', 'price' => 50000, 'qty' => 100, 'hsd' => '2026-06-20'],
    ['name' => 'Cồi sò điệp', 'cat' => 'Hải sản', 'unit' => 'kg', 'zone' => 'Kho đông', 'temp' => 'Đông (-18°C)', 'price' => 800000, 'qty' => 5, 'hsd' => '2026-12-08'],
    ['name' => 'Trứng cá hồi', 'cat' => 'Hải sản', 'unit' => 'kg', 'zone' => 'Kho mát', 'temp' => 'Mát (2-4°C)', 'price' => 1500000, 'qty' => 1, 'hsd' => '2026-07-08'],
    ['name' => 'Nấm mỡ tươi (Button Mushroom)', 'cat' => 'Rau củ', 'unit' => 'kg', 'zone' => 'Kho mát', 'temp' => 'Mát (4-8°C)', 'price' => 120000, 'qty' => 10, 'hsd' => '2026-06-15'],
    ['name' => 'Kem tươi', 'cat' => 'Thực phẩm', 'unit' => 'lít', 'zone' => 'Kho mát', 'temp' => 'Mát (2-4°C)', 'price' => 150000, 'qty' => 10, 'hsd' => '2026-06-25'],
    ['name' => 'Hành tây', 'cat' => 'Rau củ', 'unit' => 'kg', 'zone' => 'Kho mát', 'temp' => 'Mát (8-15°C)', 'price' => 25000, 'qty' => 4, 'hsd' => '2026-07-08'],
    ['name' => 'Mỳ Ý', 'cat' => 'Thực phẩm', 'unit' => 'kg', 'zone' => 'Kho khô', 'temp' => 'Khô (Nhiệt độ phòng)', 'price' => 80000, 'qty' => 15, 'hsd' => '2027-06-08'],
    ['name' => 'Sốt cà chua nền', 'cat' => 'Gia vị', 'unit' => 'lít', 'zone' => 'Kho mát', 'temp' => 'Mát (2-4°C)', 'price' => 100000, 'qty' => 4, 'hsd' => '2026-06-25'],
];

// Create PO
$po_code = 'PO-' . date('YmdHis');
$stmt_po = $db->prepare("INSERT INTO purchase_orders (po_code, total_amount, status, created_by) VALUES (?, 0, 'completed', 1)");
$stmt_po->execute([$po_code]);
$po_id = $db->lastInsertId();

$total_po = 0;

foreach($items as $i) {
    // Check if item exists
    $stmt = $db->prepare("SELECT id FROM inventory WHERE item_name = ?");
    $stmt->execute([$i['name']]);
    $inv_id = $stmt->fetchColumn();
    
    if(!$inv_id) {
        $stmt_ins = $db->prepare("INSERT INTO inventory (item_name, category, unit_name, cost_price, expiry_date, storage_zone, storage_temperature, min_stock, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 5, 1)");
        $stmt_ins->execute([$i['name'], $i['cat'], $i['unit'], $i['price'], $i['hsd'], $i['zone'], $i['temp']]);
        $inv_id = $db->lastInsertId();
    } else {
        // Update price and hsd
        $db->prepare("UPDATE inventory SET cost_price=?, expiry_date=?, storage_temperature=? WHERE id=?")->execute([$i['price'], $i['hsd'], $i['temp'], $inv_id]);
    }
    
    // Add to PO details
    $subtotal = $i['price'] * $i['qty'];
    $total_po += $subtotal;
    $db->prepare("INSERT INTO purchase_order_details (po_id, ingredient_id, expected_qty, expected_price) VALUES (?, ?, ?, ?)")->execute([$po_id, $inv_id, $i['qty'], $i['price']]);
    
    // Add to inventory_stocks (Kho Tổng)
    $w_stmt = $db->prepare("SELECT id FROM inventory_stocks WHERE ingredient_id = ? AND warehouse_id = 1");
    $w_stmt->execute([$inv_id]);
    $ws_id = $w_stmt->fetchColumn();
    if($ws_id) {
        $db->prepare("UPDATE inventory_stocks SET quantity = quantity + ?, last_updated = NOW() WHERE id = ?")->execute([$i['qty'], $ws_id]);
    } else {
        $db->prepare("INSERT INTO inventory_stocks (ingredient_id, warehouse_id, quantity) VALUES (?, 1, ?)")->execute([$inv_id, $i['qty']]);
    }
}

$db->prepare("UPDATE purchase_orders SET total_amount = ? WHERE id = ?")->execute([$total_po, $po_id]);
echo 'SUCCESS PO ID: ' . $po_id;
?>
