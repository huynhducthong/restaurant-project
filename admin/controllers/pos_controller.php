<?php
session_start();
require_once '../../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Cần kiểm tra quyền admin/nhân viên ở đây
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? ($_GET['action'] ?? '');

header('Content-Type: application/json');

try {
    switch ($action) {
        case 'get_tables':
            // Lấy danh sách bàn và thông tin order hiện tại nếu có
            $stmt = $db->query("
                SELECT t.*, 
                       o.id as current_order_id, 
                       o.status as order_status, 
                       o.total_amount,
                       (SELECT sb.id FROM service_bookings sb 
                        WHERE sb.table_id = t.id 
                        AND sb.status IN ('Pending', 'Confirmed') 
                        AND DATE(sb.booking_date) = CURDATE()
                        ORDER BY sb.booking_date ASC LIMIT 1
                       ) as upcoming_booking_id
                FROM restaurant_tables t
                LEFT JOIN pos_orders o ON t.id = o.table_id AND o.status = 'open'
                ORDER BY t.category ASC, CAST(t.table_number AS UNSIGNED) ASC
            ");
            $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $tables]);
            break;

        case 'get_menu':
            // Lấy danh mục món lẻ
            $categories = $db->query("SELECT id, name FROM categories")->fetchAll(PDO::FETCH_ASSOC);
            
            // Lấy danh sách món lẻ
            $foods = $db->query("
                SELECT f.id, f.name, f.price, f.image, f.category_id, f.description, 
                       f.is_chef_recommended, f.allergens, f.chef_note, c.name as category_name,
                       (
                           SELECT GROUP_CONCAT(CONCAT(inv.item_name, ': ', TRIM(TRAILING '.' FROM TRIM(TRAILING '0' FROM CAST(fr.quantity_required AS CHAR))), ' ', COALESCE(fr.unit, inv.unit_name)) SEPARATOR '||')
                           FROM food_recipes fr
                           JOIN inventory inv ON fr.ingredient_id = inv.id
                           WHERE fr.food_id = f.id
                       ) as recipe_list,
                       (
                           SELECT GROUP_CONCAT(CONCAT(t.name, ' (+', FORMAT(t.price, 0), 'đ)') SEPARATOR '||')
                           FROM food_toppings ft
                           JOIN toppings t ON ft.topping_id = t.id
                           WHERE ft.food_id = f.id AND t.status = 1
                       ) as topping_list
                FROM foods f 
                LEFT JOIN categories c ON f.category_id = c.id 
                WHERE f.is_active = 1
            ")->fetchAll(PDO::FETCH_ASSOC);
            
            // Lấy danh sách Combo
            $combos = $db->query("
                SELECT c.id, c.name, c.price, c.image, c.description,
                (
                    SELECT GROUP_CONCAT(f.name SEPARATOR ', ')
                    FROM combo_items ci
                    JOIN foods f ON ci.food_id = f.id
                    WHERE ci.combo_id = c.id
                ) as combo_items_list
                FROM combos c WHERE c.is_active = 1
            ")->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => [
                'categories' => $categories,
                'foods' => $foods,
                'combos' => $combos
            ]]);
            break;

        case 'checkin_booking':
            $booking_id = $_POST['booking_id'] ?? 0;
            $table_id = $_POST['table_id'] ?? 0;
            if (!$booking_id || !$table_id) throw new Exception('Thiếu thông tin');

            $db->beginTransaction();

            $stmt = $db->prepare("SELECT * FROM service_bookings WHERE id = ?");
            $stmt->execute([$booking_id]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$booking) throw new Exception('Không tìm thấy booking');

            $db->prepare("INSERT INTO pos_orders (table_id, status, booking_id, deposit_amount) VALUES (?, 'open', ?, ?)")
               ->execute([$table_id, $booking_id, $booking['deposit_amount']]);
            $pos_order_id = $db->lastInsertId();

            $db->prepare("UPDATE restaurant_tables SET status = 'occupied' WHERE id = ?")->execute([$table_id]);

            $b_details = $db->prepare("SELECT * FROM booking_details WHERE booking_id = ?");
            $b_details->execute([$booking_id]);
            $details = $b_details->fetchAll(PDO::FETCH_ASSOC);

            $ins_item = $db->prepare("INSERT INTO pos_order_items (pos_order_id, item_type, item_id, quantity, price, status) VALUES (?, 'food', ?, ?, ?, 'draft')");
            
            foreach ($details as $d) {
                if ($d['item_type'] == 'food') {
                    $fp = $db->prepare("SELECT price FROM foods WHERE id = ?");
                    $fp->execute([$d['menu_id']]);
                    $price = $fp->fetchColumn();
                    
                    $final_price = $price;
                    if ($d['toppings_info']) {
                        $t_ids = explode(',', $d['toppings_info']);
                        foreach ($t_ids as $tid) {
                            $tp = $db->prepare("SELECT price FROM toppings WHERE id = ?");
                            $tp->execute([$tid]);
                            $final_price += (float)$tp->fetchColumn();
                        }
                    }
                    $ins_item->execute([$pos_order_id, $d['menu_id'], $d['quantity'], $final_price]);
                }
            }
            
            $upd = $db->prepare("UPDATE pos_orders SET total_amount = (SELECT SUM(price * quantity) FROM pos_order_items WHERE pos_order_id = ?) WHERE id = ?");
            $upd->execute([$pos_order_id, $pos_order_id]);

            $db->commit();
            echo json_encode(['success' => true]);
            break;

        case 'get_order':
            $table_id = $_GET['table_id'] ?? 0;
            if (!$table_id) throw new Exception('Thiếu table_id');

            $stmt = $db->prepare("SELECT * FROM pos_orders WHERE table_id = ? AND status = 'open' LIMIT 1");
            $stmt->execute([$table_id]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                echo json_encode(['success' => true, 'data' => null]);
                break;
            }

            // Lấy danh sách món trong order
            $stmt = $db->prepare("
                SELECT i.*, 
                       CASE 
                           WHEN i.item_type = 'food' THEN f.name 
                           WHEN i.item_type = 'combo' THEN c.name 
                       END as name,
                       CASE 
                           WHEN i.item_type = 'food' THEN f.image 
                           WHEN i.item_type = 'combo' THEN c.image 
                       END as image
                FROM pos_order_items i
                LEFT JOIN foods f ON i.item_id = f.id AND i.item_type = 'food'
                LEFT JOIN combos c ON i.item_id = c.id AND i.item_type = 'combo'
                WHERE i.pos_order_id = ?
                ORDER BY i.id ASC
            ");
            $stmt->execute([$order['id']]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'data' => ['order' => $order, 'items' => $items]]);
            break;

        case 'add_item':
            $table_id = $_POST['table_id'] ?? 0;
            $item_type = $_POST['item_type'] ?? '';
            $item_id = $_POST['item_id'] ?? 0;
            $price = $_POST['price'] ?? 0;
            $quantity = $_POST['quantity'] ?? 1;
            $notes = $_POST['notes'] ?? '';

            if (!$table_id || !$item_type || !$item_id) {
                throw new Exception('Thiếu thông tin');
            }

            $db->beginTransaction();

            // Tìm order hiện tại, nếu chưa có thì tạo mới
            $stmt = $db->prepare("SELECT id FROM pos_orders WHERE table_id = ? AND status = 'open' LIMIT 1");
            $stmt->execute([$table_id]);
            $order_id = $stmt->fetchColumn();

            if (!$order_id) {
                $db->prepare("INSERT INTO pos_orders (table_id, status) VALUES (?, 'open')")->execute([$table_id]);
                $order_id = $db->lastInsertId();
                // Update table status
                $db->prepare("UPDATE restaurant_tables SET status = 'occupied' WHERE id = ?")->execute([$table_id]);
            }

            // Kiểm tra món đã có trong order chưa (cùng status draft và cùng notes thì cộng dồn)
            $stmt = $db->prepare("SELECT id, quantity FROM pos_order_items WHERE pos_order_id = ? AND item_type = ? AND item_id = ? AND IFNULL(notes, '') = ? AND status = 'draft' LIMIT 1");
            $stmt->execute([$order_id, $item_type, $item_id, $notes]);
            $existing_item = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing_item) {
                $db->prepare("UPDATE pos_order_items SET quantity = quantity + ? WHERE id = ?")->execute([$quantity, $existing_item['id']]);
            } else {
                $db->prepare("INSERT INTO pos_order_items (pos_order_id, item_type, item_id, quantity, price, notes, status) VALUES (?, ?, ?, ?, ?, ?, 'draft')")
                   ->execute([$order_id, $item_type, $item_id, $quantity, $price, $notes]);
            }

            // Update total
            updateOrderTotal($db, $order_id);

            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Thêm món thành công']);
            break;

        case 'update_qty':
            $item_id = $_POST['item_id'] ?? 0;
            $qty = $_POST['quantity'] ?? 0;
            
            $stmt = $db->prepare("SELECT pos_order_id FROM pos_order_items WHERE id = ?");
            $stmt->execute([$item_id]);
            $order_id = $stmt->fetchColumn();

            if ($order_id) {
                if ($qty > 0) {
                    $db->prepare("UPDATE pos_order_items SET quantity = ? WHERE id = ?")->execute([$qty, $item_id]);
                } else {
                    $db->prepare("DELETE FROM pos_order_items WHERE id = ?")->execute([$item_id]);
                }
                updateOrderTotal($db, $order_id);
            }
            echo json_encode(['success' => true]);
            break;

        case 'send_to_kitchen':
            $order_id = $_POST['order_id'] ?? 0;
            if ($order_id) {
                $db->query("UPDATE pos_order_items SET status = 'pending' WHERE pos_order_id = $order_id AND status = 'draft'");
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
            }
            break;

        case 'checkout':
            $order_id = $_POST['order_id'] ?? 0;
            $payment_method = $_POST['payment_method'] ?? 'cash';
            
            if (!$order_id) throw new Exception('Thiếu order_id');

            $db->beginTransaction();

            $stmt = $db->prepare("SELECT * FROM pos_orders WHERE id = ?");
            $stmt->execute([$order_id]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$order) throw new Exception('Không tìm thấy order');

            $db->prepare("UPDATE pos_orders SET status = 'paid', payment_method = ? WHERE id = ?")->execute([$payment_method, $order_id]);
            
            if ($order['table_id']) {
                $db->prepare("UPDATE restaurant_tables SET status = 'available', is_available = 1 WHERE id = ?")->execute([$order['table_id']]);
            }

            if ($order['booking_id']) {
                $db->prepare("UPDATE service_bookings SET status = 'Completed' WHERE id = ?")->execute([$order['booking_id']]);
            }

            // Lấy thông tin chi tiết order để trả về cho màn hình In hóa đơn
            $items = $db->query("
                SELECT i.*, 
                       CASE WHEN i.item_type = 'food' THEN f.name ELSE c.name END as name
                FROM pos_order_items i
                LEFT JOIN foods f ON i.item_id = f.id AND i.item_type = 'food'
                LEFT JOIN combos c ON i.item_id = c.id AND i.item_type = 'combo'
                WHERE i.pos_order_id = $order_id
            ")->fetchAll(PDO::FETCH_ASSOC);

            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Thanh toán thành công', 'data' => ['order' => $order, 'items' => $items]]);
            break;

        case 'cancel_order':
            $order_id = $_POST['order_id'] ?? 0;
            if ($order_id) {
                $db->beginTransaction();
                $stmt = $db->prepare("SELECT table_id FROM pos_orders WHERE id = ?");
                $stmt->execute([$order_id]);
                $table_id = $stmt->fetchColumn();

                $db->query("DELETE FROM pos_order_items WHERE pos_order_id = $order_id");
                $db->query("UPDATE pos_orders SET status = 'cancelled', total_amount = 0 WHERE id = $order_id");
                
                if ($table_id) {
                    $db->prepare("UPDATE restaurant_tables SET status = 'available' WHERE id = ?")->execute([$table_id]);
                }
                
                $db->commit();
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
            }
            break;

        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function updateOrderTotal($db, $order_id) {
    $stmt = $db->prepare("SELECT SUM(quantity * price) FROM pos_order_items WHERE pos_order_id = ?");
    $stmt->execute([$order_id]);
    $total = $stmt->fetchColumn() ?: 0;
    $db->prepare("UPDATE pos_orders SET total_amount = ? WHERE id = ?")->execute([$total, $order_id]);
}
