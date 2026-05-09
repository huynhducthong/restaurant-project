<<<<<<< HEAD
<<<<<<< HEAD
﻿<?php
session_start();
if (!isset($_SESSION['user_id'])) { http_response_code(403); echo json_encode(['error'=>'Unauthorized']); exit; }
=======
<?php
>>>>>>> 01a15dc (feat: Add full HR Management system (Employees, Shifts, Payroll))
=======
﻿<?php
session_start();
if (!isset($_SESSION['user_id'])) { http_response_code(403); echo json_encode(['error'=>'Unauthorized']); exit; }
>>>>>>> main
// admin/ajax/ajax_get_booking_detail.php
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing ID']);
    exit;
}

$id = (int)$_GET['id'];
$db = (new Database())->getConnection();

try {
<<<<<<< HEAD
<<<<<<< HEAD
    // 1. Láº¥y thÃ´ng tin cÆ¡ báº£n cá»§a Ä‘Æ¡n Ä‘áº·t bÃ n
=======
    // 1. Lấy thông tin cơ bản của đơn đặt bàn
>>>>>>> 01a15dc (feat: Add full HR Management system (Employees, Shifts, Payroll))
=======
    // 1. Láº¥y thÃ´ng tin cÆ¡ báº£n cá»§a Ä‘Æ¡n Ä‘áº·t bÃ n
>>>>>>> main
    $stmt = $db->prepare("SELECT * FROM service_bookings WHERE id = ?");
    $stmt->execute([$id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        echo json_encode(['status' => 'error', 'message' => 'Booking not found']);
        exit;
    }

<<<<<<< HEAD
<<<<<<< HEAD
    // 2. Láº¥y danh sÃ¡ch mÃ³n Äƒn Ä‘Ã£ Ä‘áº·t
=======
    // 2. Lấy danh sách món ăn đã đặt
>>>>>>> 01a15dc (feat: Add full HR Management system (Employees, Shifts, Payroll))
=======
    // 2. Láº¥y danh sÃ¡ch mÃ³n Äƒn Ä‘Ã£ Ä‘áº·t
>>>>>>> main
    $stmt_items = $db->prepare("
        SELECT bd.*, f.name as food_name, f.unit_name as food_unit, f.price
        FROM booking_details bd
        JOIN foods f ON bd.menu_id = f.id
        WHERE bd.booking_id = ?
    ");
    $stmt_items->execute([$id]);
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

<<<<<<< HEAD
<<<<<<< HEAD
    // 3. Vá»›i má»—i mÃ³n Äƒn, láº¥y Ä‘á»‹nh má»©c nguyÃªn liá»‡u (Recipe)
=======
    // 3. Với mỗi món ăn, lấy định mức nguyên liệu (Recipe)
>>>>>>> 01a15dc (feat: Add full HR Management system (Employees, Shifts, Payroll))
=======
    // 3. Vá»›i má»—i mÃ³n Äƒn, láº¥y Ä‘á»‹nh má»©c nguyÃªn liá»‡u (Recipe)
>>>>>>> main
    foreach ($items as &$item) {
        $stmt_recipe = $db->prepare("
            SELECT r.*, i.item_name, i.unit_name as inventory_unit
            FROM food_recipes r
            JOIN inventory i ON r.ingredient_id = i.id
            WHERE r.food_id = ?
        ");
        $stmt_recipe->execute([$item['menu_id']]);
        $item['recipes'] = $stmt_recipe->fetchAll(PDO::FETCH_ASSOC);
    }

    $booking['items'] = $items;

    echo json_encode($booking);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
<<<<<<< HEAD
<<<<<<< HEAD

=======
>>>>>>> 01a15dc (feat: Add full HR Management system (Employees, Shifts, Payroll))
=======

>>>>>>> main
