<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing ID']);
    exit;
}

$id = (int)$_GET['id'];
$db = (new Database())->getConnection();

try {
    $stmt = $db->prepare("
        SELECT sb.*, 
               rt.table_code, 
               c.name as combo_name 
        FROM service_bookings sb
        LEFT JOIN restaurant_tables rt ON sb.table_id = rt.id
        LEFT JOIN combos c ON sb.combo_id = c.id
        WHERE sb.id = ?
    ");
    $stmt->execute([$id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($booking) {
        // Lấy danh sách món ăn
        $stmt_foods = $db->prepare("
            SELECT f.name, bd.quantity 
            FROM booking_details bd 
            JOIN foods f ON bd.menu_id = f.id 
            WHERE bd.booking_id = ?
        ");
        $stmt_foods->execute([$id]);
        $booking['foods'] = $stmt_foods->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        echo json_encode($booking);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Booking not found']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
