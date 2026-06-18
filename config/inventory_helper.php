<?php
/**
 * File: config/inventory_helper.php
 * Chứa các hàm hỗ trợ quản lý kho theo lô (Batches) và FEFO
 */

/**
 * Chuẩn hóa chuỗi đơn vị để so khớp / quy đổi.
 */
function inventory_normalize_unit_string($unit) {
    $u = strtolower(trim((string) $unit));
    $u = preg_replace('/\s+/u', '', $u);
    $aliases = [
        'ký' => 'kg', 'kilogram' => 'kg', 'kilograms' => 'kg',
        'gram' => 'g', 'grams' => 'g', 'gr' => 'g',
        'lit' => 'l', 'liter' => 'l', 'litre' => 'l', 'lít' => 'l',
        'mililít' => 'ml', 'mililiter' => 'ml', 'milliliter' => 'ml',
        'chai' => 'chai', 'lon' => 'lon', 'cái' => 'cai', 'cai' => 'cai',
        'phần' => 'phan', 'phan' => 'phan',
    ];
    return $aliases[$u] ?? $u;
}

/**
 * Meta đơn vị: khối lượng (g), thể tích (ml), hoặc đếm (không quy đổi chéo).
 *
 * @return array{kind:string,mul:float}
 */
function inventory_unit_meta($unit) {
    $u = inventory_normalize_unit_string($unit);
    // Cấp số nhân mặc định: 1g = 1ml = 1, 1kg = 1l = 1000
    // Quy ước ngầm để có thể trừ kho từ chai/lon: 1 chai = 750ml, 1 lon = 330ml
    $mass_volume = [
        'g' => 1.0, 'kg' => 1000.0, 
        'ml' => 1.0, 'l' => 1000.0, 
        'chai' => 750.0, 'lon' => 330.0
    ];
    
    if (isset($mass_volume[$u])) {
        return ['kind' => 'mass_volume', 'mul' => $mass_volume[$u]];
    }
    return ['kind' => 'count', 'mul' => 1.0];
}

/**
 * Quy đổi số lượng từ đơn vị định mức (recipe) sang đơn vị tồn kho (inventory).
 *
 * @param float|int|string $qty
 */
function convert_to_base_unit($qty, $from_unit, $to_unit) {
    $qty = (float) $qty;
    $fromU = inventory_normalize_unit_string($from_unit);
    $toU = inventory_normalize_unit_string($to_unit);
    if ($fromU === $toU) {
        return $qty;
    }
    $from = inventory_unit_meta($from_unit);
    $to = inventory_unit_meta($to_unit);
    
    // Nếu cả hai đều thuộc nhóm có thể quy đổi (khối lượng hoặc thể tích)
    if ($from['kind'] === $to['kind'] && $from['kind'] === 'mass_volume') {
        $base = $qty * $from['mul'];
        return $base / $to['mul'];
    }
    return $qty;
}

/**
 * Trừ tồn kho theo nguyên lý FEFO (Hết hạn trước, Xuất trước)
 * 
 * @param PDO $db Kết nối database
 * @param int $ingredient_id ID nguyên liệu
 * @param int $warehouse_id ID kho xuất
 * @param float $quantity_to_deduct Số lượng cần trừ
 * @param string $performed_by Người thực hiện (để ghi lịch sử nếu cần)
 * @param string $type Loại giao dịch (export, loss...)
 * @return bool Trả về true nếu thành công
 */
function deductStockFEFO($db, $ingredient_id, $warehouse_id, $quantity_to_deduct, $performed_by, $type = 'export') {
    // 1. Lấy các lô hàng còn hàng trong kho này, ưu tiên lô hết hạn sớm nhất
    // Lưu ý: Lô không có HSD (NULL) sẽ được xếp xuống cuối
    $stmt = $db->prepare("
        SELECT id, quantity, expiry_date 
        FROM inventory_batches 
        WHERE ingredient_id = ? AND warehouse_id = ? AND quantity > 0 
        ORDER BY (expiry_date IS NULL), expiry_date ASC, created_at ASC
    ");
    $stmt->execute([$ingredient_id, $warehouse_id]);
    $batches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $remaining = (float)$quantity_to_deduct;
    
    foreach ($batches as $batch) {
        if ($remaining <= 0) break;
        
        $batch_qty = (float)$batch['quantity'];
        $take = min($remaining, $batch_qty);
        
        // Trừ vào lô
        $db->prepare("UPDATE inventory_batches SET quantity = quantity - ? WHERE id = ?")
           ->execute([$take, $batch['id']]);
           
        $remaining -= $take;
    }
    
    // Nếu vẫn còn dư (tức là tổng các lô không đủ, mặc dù inventory_stocks báo đủ) 
    // thì trừ vào lô cuối cùng hoặc xử lý ngoại lệ. 
    // Tuy nhiên thực tế inventory_stocks và tổng inventory_batches phải khớp nhau.

    // 2. Cập nhật HSD tổng cho nguyên liệu (Lấy ngày sớm nhất của các lô CÒN HÀNG)
    $stmt_min = $db->prepare("SELECT MIN(expiry_date) FROM inventory_batches WHERE ingredient_id = ? AND quantity > 0 AND expiry_date IS NOT NULL");
    $stmt_min->execute([$ingredient_id]);
    $next_hsd = $stmt_min->fetchColumn();
    
    $db->prepare("UPDATE inventory SET expiry_date = ? WHERE id = ?")->execute([$next_hsd ?: null, $ingredient_id]);

    return true;
}

/**
 * Tạo lô hàng mới khi nhập hàng
 */
function createBatch($db, $ingredient_id, $warehouse_id, $quantity, $expiry_date, $cost_price, $batch_code = null) {
    $stmt = $db->prepare("INSERT INTO inventory_batches (ingredient_id, warehouse_id, quantity, expiry_date, cost_price, batch_code) VALUES (?, ?, ?, ?, ?, ?)");
    return $stmt->execute([$ingredient_id, $warehouse_id, $quantity, $expiry_date ?: null, $cost_price, $batch_code]);
}

/**
 * Tính toán số lượng tồn kho khả dụng của món ăn dựa trên nguyên liệu trong kho
 */
function getFoodInventory($db, $food_id) {
    $stmt = $db->prepare("
        SELECT fr.ingredient_id, fr.quantity_required, fr.unit as recipe_unit,
               i.unit_name as inv_unit,
               IFNULL((SELECT SUM(quantity) FROM inventory_stocks WHERE ingredient_id = fr.ingredient_id), 0) as total_stock
        FROM food_recipes fr
        JOIN inventory i ON fr.ingredient_id = i.id
        WHERE fr.food_id = ?
    ");
    $stmt->execute([$food_id]);
    $recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($recipes)) {
        return 99; // Mặc định không giới hạn
    }

    $min_stock = 999999;
    foreach ($recipes as $r) {
        $req_qty = convert_to_base_unit($r['quantity_required'], $r['recipe_unit'], $r['inv_unit']);
        if ($req_qty <= 0) continue;
        $possible = floor((float)$r['total_stock'] / $req_qty);
        if ($possible < $min_stock) {
            $min_stock = $possible;
        }
    }

    return (int)max(0, $min_stock);
}
?>
