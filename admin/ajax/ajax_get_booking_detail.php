<?php
session_start();
if (!isset($_SESSION['user_id'])) { 
    http_response_code(403); 
    echo json_encode(['error'=>'Unauthorized']); 
    exit; 
}

// admin/ajax/ajax_get_booking_detail.php
// admin/ajax/ajax_get_booking_detail.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/inventory_helper.php';
header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing ID']);
    exit;
}

$id = (int)$_GET['id'];
$db = (new Database())->getConnection();

try {
    // 1. Lấy thông tin cơ bản của đơn đặt bàn
    $stmt = $db->prepare("SELECT * FROM service_bookings WHERE id = ?");
    $stmt->execute([$id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        echo json_encode(['status' => 'error', 'message' => 'Booking not found']);
        exit;
    }

    // 2. Lấy danh sách món ăn đã đặt
    $stmt_items = $db->prepare("
        SELECT bd.*, f.name as food_name, f.price
        FROM booking_details bd
        JOIN foods f ON bd.menu_id = f.id
        WHERE bd.booking_id = ?
    ");
    $stmt_items->execute([$id]);
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    // 3. Lấy định mức nguyên liệu và kiểm tra tồn kho
    $required_ingredients = [];
    if (!empty($items)) {
        $food_ids = array_column($items, 'menu_id');
        $placeholders = implode(',', array_fill(0, count($food_ids), '?'));

        $stmt_recipe = $db->prepare("
            SELECT r.*, i.item_name, i.unit_name as inventory_unit, i.category,
                   ic.default_warehouse_id, w.name as target_warehouse_name
            FROM food_recipes r
            JOIN inventory i ON r.ingredient_id = i.id
            LEFT JOIN inventory_categories ic ON i.category = ic.name
            LEFT JOIN warehouses w ON ic.default_warehouse_id = w.id
            WHERE r.food_id IN ($placeholders)
        ");
        $stmt_recipe->execute($food_ids);
        $all_recipes = $stmt_recipe->fetchAll(PDO::FETCH_ASSOC);

        $recipes_by_food = [];
        foreach ($all_recipes as $recipe) {
            $recipes_by_food[$recipe['food_id']][] = $recipe;
        }

        foreach ($items as &$item) {
            $item_recipes = $recipes_by_food[$item['menu_id']] ?? [];
            $item['recipes'] = $item_recipes;
            
            // Tính toán tổng nguyên liệu cần thiết cho cả đơn hàng
            foreach ($item_recipes as $rcp) {
                $ing_id = $rcp['ingredient_id'];
                $qty_req = (float)$rcp['quantity_required'] * (float)$item['quantity'];
                $base_qty = convert_to_base_unit($qty_req, $rcp['unit'], $rcp['inventory_unit']);
                
                if (!isset($required_ingredients[$ing_id])) {
                    $def_w_id = (int)($rcp['default_warehouse_id'] ?: 2);
                    $def_w_name = $rcp['target_warehouse_name'] ?: 'Bếp';

                    $required_ingredients[$ing_id] = [
                        'id' => $ing_id,
                        'name' => $rcp['item_name'],
                        'total_required' => 0,
                        'unit' => $rcp['inventory_unit'],
                        'category' => $rcp['category'],
                        'target_warehouse_id' => $def_w_id,
                        'target_warehouse_name' => $def_w_name
                    ];
                }
                $required_ingredients[$ing_id]['total_required'] += $base_qty;
            }
        }
        
        // 4. Kiểm tra số lượng tồn thực tế ở các kho
        foreach ($required_ingredients as &$ing) {
            // Tồn tại kho đích (Bếp/Bar)
            $stmt_target = $db->prepare("SELECT quantity FROM inventory_stocks WHERE ingredient_id = ? AND warehouse_id = ?");
            $stmt_target->execute([$ing['id'], $ing['target_warehouse_id']]);
            $ing['stock_target'] = (float)($stmt_target->fetchColumn() ?: 0);
            
            // Tồn tại kho tổng (Warehouse 1)
            $stmt_main = $db->prepare("SELECT quantity FROM inventory_stocks WHERE ingredient_id = ? AND warehouse_id = 1");
            $stmt_main->execute([$ing['id']]);
            $ing['stock_main'] = (float)($stmt_main->fetchColumn() ?: 0);
            
            $ing['is_sufficient'] = ($ing['stock_target'] >= $ing['total_required']);
            $ing['missing_qty'] = max(0, $ing['total_required'] - $ing['stock_target']);
            $ing['can_transfer'] = ($ing['stock_main'] >= $ing['missing_qty']);
        }
    }

    $booking['foods'] = $items;
    $booking['inventory_check'] = array_values($required_ingredients);
    echo json_encode($booking);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}
?>