<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/inventory_helper.php';
require_once __DIR__ . '/config/pusher.php';

function triggerUpdate() {
    global $pusher;
    try {
        $pusher->trigger('restaurant-channel', 'update_data', ['time' => time()]);
    } catch (Exception $e) { }
}

$role = $_SESSION['role'] ?? '';
if (!in_array($role, ['admin', 'chef', 1, 2])) {
    echo "<h1>403 Forbidden - Màn hình dành riêng cho Bếp trưởng!</h1>";
    exit;
}

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'complete_order') {
    $booking_id = intval($_POST['booking_id']);
    try {
        $stmt = $db->prepare("UPDATE service_bookings SET status = 'Completed' WHERE id = ?");
        $stmt->execute([$booking_id]);
        
        // Giải phóng bàn
        $stmt_table = $db->prepare("SELECT table_id FROM service_bookings WHERE id = ?");
        $stmt_table->execute([$booking_id]);
        $table_id = $stmt_table->fetchColumn();
        if ($table_id) {
            $db->prepare("UPDATE restaurant_tables SET is_available = 1 WHERE id = ?")->execute([$table_id]);
        }
        
        triggerUpdate();
        echo "success";
    } catch(Exception $e) {
        echo "error";
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'complete_pos_item') {
    $item_id = intval($_POST['item_id']);
    $item_type_req = $_POST['item_type'] ?? 'pos';
    try {
        if ($item_type_req === 'booking') {
            // For bookings tested before check-in, just update status without complex inventory deduction
            $db->prepare("UPDATE booking_details SET status = 'ready' WHERE id = ?")->execute([$item_id]);
            triggerUpdate();
            echo "success";
            exit;
        }

        $db->beginTransaction();

        $stmt_check = $db->prepare("SELECT item_type, item_id as food_id, quantity, status FROM pos_order_items WHERE id = ? FOR UPDATE");
        $stmt_check->execute([$item_id]);
        $item = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if (!$item) throw new Exception("Không tìm thấy món ăn này.");
        if ($item['status'] === 'ready' || $item['status'] === 'served') {
            $db->rollBack();
            triggerUpdate();
            echo "success";
            exit;
        }

        $food_ids = [];
        if ($item['item_type'] === 'food') {
            $food_ids[] = $item['food_id'];
        } elseif ($item['item_type'] === 'combo') {
            $c_foods = $db->query("SELECT food_id FROM combo_items WHERE combo_id = " . (int)$item['food_id'])->fetchAll(PDO::FETCH_COLUMN);
            $food_ids = array_merge($food_ids, $c_foods);
        }

        if (!empty($food_ids)) {
            $placeholders = implode(',', array_fill(0, count($food_ids), '?'));
            $query_recipe = $db->prepare("
                SELECT r.food_id, r.ingredient_id, r.quantity_required, r.unit as r_unit, i.unit_name as i_unit, i.category
                FROM food_recipes r
                JOIN inventory i ON r.ingredient_id = i.id
                WHERE r.food_id IN ($placeholders)
            ");
            $query_recipe->execute($food_ids);
            
            $recipes_by_food = [];
            while ($row = $query_recipe->fetch(PDO::FETCH_ASSOC)) {
                $recipes_by_food[$row['food_id']][] = $row;
            }

            $required_by_stock = [];
            $item_qty = (float)$item['quantity'];

            foreach ($food_ids as $food_id) {
                $recipes = $recipes_by_food[$food_id] ?? [];
                foreach ($recipes as $r) {
                    $ing_id = $r['ingredient_id'];
                    $qty_req = (float)$r['quantity_required'];
                    $category = $r['category'];

                    $target_warehouse_id = ($category === 'Đồ uống') ? 3 : 2;
                    $qty_in_stock_unit = convert_to_base_unit($qty_req, $r['r_unit'], $r['i_unit']);
                    $total_reduction = $qty_in_stock_unit * $item_qty;

                    $key = $ing_id . ':' . $target_warehouse_id;
                    if (!isset($required_by_stock[$key])) {
                        $required_by_stock[$key] = ['ingredient_id' => $ing_id, 'warehouse_id' => $target_warehouse_id, 'quantity' => 0];
                    }
                    $required_by_stock[$key]['quantity'] += $total_reduction;
                }
            }

            $query_check_stock = $db->prepare("SELECT quantity FROM inventory_stocks WHERE ingredient_id = ? AND warehouse_id = ? FOR UPDATE");
            $query_update_inventory = $db->prepare("UPDATE inventory_stocks SET quantity = quantity - ? WHERE ingredient_id = ? AND warehouse_id = ? AND quantity >= ?");
            $query_history = $db->prepare("INSERT INTO inventory_history (ingredient_id, warehouse_id, type, quantity, performed_by) VALUES (?, ?, 'export', ?, 'Hệ thống KDS (Báo xong món POS)')");

            foreach ($required_by_stock as $row) {
                $ing_id = (int)$row['ingredient_id'];
                $warehouse_id = (int)$row['warehouse_id'];
                $total_reduction = (float)$row['quantity'];

                if ($total_reduction > 0) {
                    $query_check_stock->execute([$ing_id, $warehouse_id]);
                    $current_stock = (float)($query_check_stock->fetchColumn() ?: 0);
                    if ($current_stock < $total_reduction) {
                        throw new Exception("Kho không đủ nguyên liệu (ID: $ing_id). Cần $total_reduction, còn $current_stock.");
                    }

                    $query_update_inventory->execute([$total_reduction, $ing_id, $warehouse_id, $total_reduction]);
                    if ($query_update_inventory->rowCount() === 0) {
                        throw new Exception("Lỗi khi trừ kho nguyên liệu ID $ing_id.");
                    }

                    $query_history->execute([$ing_id, $warehouse_id, $total_reduction]);
                }
            }
        }

        $db->prepare("UPDATE pos_order_items SET status = 'ready' WHERE id = ?")->execute([$item_id]);
        $db->commit();
        triggerUpdate();
        echo "success";
    } catch(Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        echo "error|" . $e->getMessage();
    }
    exit;
}

// New handler for progressive status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_pos_item_status') {
    $item_id = (int)$_POST['item_id'];
    $new_status = $_POST['new_status'];
    
    // Only allow valid transitions
    $valid_statuses = ['pending', 'preparing', 'cooking', 'ready', 'served'];
    if (in_array($new_status, $valid_statuses)) {
        // If transitioning to 'ready', we should also deduct inventory like the old complete_pos_item did.
        // But for simplicity in this workflow, if they use the new progressive buttons, we will call the old action for 'ready' from frontend.
        // So this handler is just for 'preparing' and 'cooking'.
        $item_type = $_POST['item_type'] ?? 'pos';
        if ($item_type === 'booking') {
            $db->prepare("UPDATE booking_details SET status = ? WHERE id = ?")->execute([$new_status, $item_id]);
        } else {
            $db->prepare("UPDATE pos_order_items SET status = ? WHERE id = ?")->execute([$new_status, $item_id]);
        }
        triggerUpdate();
        echo "success";
    } else {
        echo "error|Invalid status";
    }
    exit;
}

$query = "
    SELECT 
        sb.id, sb.customer_name, sb.guests, sb.booking_date, sb.service_type, 
        sb.chef_requirements, sb.message, sb.combo_id,
        u.allergies, u.doneness, u.flavor_profile,
        c.name as combo_name
    FROM service_bookings sb
    LEFT JOIN users u ON sb.user_id = u.id
    LEFT JOIN combos c ON sb.combo_id = c.id
    WHERE sb.status = 'Confirmed' AND DATE(sb.booking_date) = CURDATE()
    ORDER BY sb.booking_date ASC
";
$stmt = $db->query($query);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách các món ăn lẻ (A la Carte) cho từng đơn
foreach ($orders as &$order) {
    $stmt_details = $db->prepare("
        SELECT bd.id as order_item_id, bd.quantity, bd.notes as special_notes, f.name as food_name, bd.toppings_info, bd.status
        FROM booking_details bd
        JOIN foods f ON bd.menu_id = f.id
        WHERE bd.booking_id = ?
    ");
    $stmt_details->execute([$order['id']]);
    $foods_list = $stmt_details->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($foods_list as &$f) {
        $f['toppings'] = [];
        if (!empty($f['toppings_info'])) {
            $t_ids = explode(',', $f['toppings_info']);
            $t_ids_str = implode(',', array_map('intval', $t_ids));
            if (!empty($t_ids_str)) {
                $toppings_query = $db->query("SELECT name FROM toppings WHERE id IN ($t_ids_str)")->fetchAll(PDO::FETCH_COLUMN);
                $f['toppings'] = $toppings_query;
            }
        }
    }
    unset($f);
    
    $order['foods'] = $foods_list;
}
unset($order);

// --- Fetch POS Orders (Món ăn gọi tại bàn) ---
$pos_query = "
    SELECT 
        o.id as order_id, 
        t.table_code as table_name,
        o.created_at,
        oi.id as order_item_id,
        oi.item_type,
        oi.item_id as food_id,
        oi.quantity,
        oi.status,
        oi.notes,
        CASE WHEN oi.item_type = 'food' THEN f.name ELSE c.name END as food_name
    FROM pos_orders o
    JOIN restaurant_tables t ON o.table_id = t.id
    JOIN pos_order_items oi ON o.id = oi.pos_order_id
    LEFT JOIN foods f ON oi.item_id = f.id AND oi.item_type = 'food'
    LEFT JOIN combos c ON oi.item_id = c.id AND oi.item_type = 'combo'
    WHERE oi.status IN ('pending', 'preparing', 'cooking', 'ready') AND o.status != 'completed'
    ORDER BY o.created_at ASC
";
$pos_items = $db->query($pos_query)->fetchAll(PDO::FETCH_ASSOC);

$pos_orders = [];
foreach ($pos_items as $item) {
    $oid = $item['order_id'];
    if (!isset($pos_orders[$oid])) {
        $pos_orders[$oid] = [
            'id' => 'POS-' . $oid,
            'real_order_id' => $oid,
            'customer_name' => 'Bàn: ' . $item['table_name'],
            'booking_date' => $item['created_at'],
            'is_pos' => true,
            'foods' => []
        ];
    }
    $pos_orders[$oid]['foods'][] = [
        'order_item_id' => $item['order_item_id'],
        'food_name' => $item['food_name'],
        'quantity' => $item['quantity'],
        'status' => $item['status'],
        'toppings' => !empty($item['notes']) ? [str_replace('Topping: ', '', $item['notes'])] : [],
        'note' => ''
    ];
}

// Merge POS orders and Bookings
$all_orders = array_merge($orders, array_values($pos_orders));
usort($all_orders, function($a, $b) {
    return strtotime($a['booking_date']) - strtotime($b['booking_date']);
});

// ── KANBAN CATEGORIZATION ──
$kanban_todo = [];
$kanban_inprogress = [];
$kanban_ready = [];

foreach ($all_orders as $order) {
    $pending_cnt = 0;
    $progress_cnt = 0;
    $ready_cnt = 0;
    $total_cnt = 0;
    
    if (!empty($order['foods'])) {
        foreach ($order['foods'] as $food) {
            if (!isset($food['status'])) continue;
            $total_cnt++;
            if ($food['status'] === 'pending') $pending_cnt++;
            if ($food['status'] === 'preparing' || $food['status'] === 'cooking') $progress_cnt++;
            if ($food['status'] === 'ready' || $food['status'] === 'served') $ready_cnt++;
        }
    }
    
    if ($progress_cnt > 0) {
        $kanban_inprogress[] = $order;
    } elseif ($total_cnt > 0 && $ready_cnt === $total_cnt) {
        $kanban_ready[] = $order;
    } else {
        $kanban_todo[] = $order;
    }
}

// Truy vấn các đơn đặt trước (Upcoming)
$upcoming_query = "
    SELECT 
        sb.id, sb.customer_name, sb.guests, sb.booking_date, sb.service_type, sb.chef_requirements, sb.message,
        c.name as combo_name,
        u.allergies, u.doneness, u.flavor_profile
    FROM service_bookings sb
    LEFT JOIN combos c ON sb.combo_id = c.id
    LEFT JOIN users u ON sb.user_id = u.id
    WHERE sb.status = 'Confirmed' AND DATE(sb.booking_date) > CURDATE()
    ORDER BY sb.booking_date ASC
    LIMIT 15
";
$stmt_up = $db->query($upcoming_query);
$upcoming_orders = $stmt_up->fetchAll(PDO::FETCH_ASSOC);

foreach ($upcoming_orders as &$up_order) {
    $stmt_details = $db->prepare("
        SELECT bd.quantity, bd.notes as special_notes, f.name as food_name, bd.toppings_info
        FROM booking_details bd
        JOIN foods f ON bd.menu_id = f.id
        WHERE bd.booking_id = ?
    ");
    $stmt_details->execute([$up_order['id']]);
    $foods_list = $stmt_details->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($foods_list as &$f) {
        $f['toppings'] = [];
        if (!empty($f['toppings_info'])) {
            $t_ids = explode(',', $f['toppings_info']);
            $t_ids_str = implode(',', array_map('intval', $t_ids));
            if (!empty($t_ids_str)) {
                $toppings_query = $db->query("SELECT name FROM toppings WHERE id IN ($t_ids_str)")->fetchAll(PDO::FETCH_COLUMN);
                $f['toppings'] = $toppings_query;
            }
        }
    }
    unset($f);
    $up_order['foods'] = $foods_list;
}
unset($up_order);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>KDS — Kitchen Command | Bespoke</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500&family=Source+Sans+3:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
/* ═══════════════════════════════════════
   TOKENS — Light / Warm Kitchen Theme
═══════════════════════════════════════ */
:root {
  --bg:        #F8F9FA;
  --surface:   #FFFFFF;
  --surface2:  #F1F3F5;
  --border:    #DEE2E6;
  --border-md: #CED4DA;

  --forest:    #A88746;
  --forest-lt: #5f6e45;
  --forest-dim:rgba(168, 135, 70,.1);

  --accent-burgundy:      #A88746;
  --accent-burgundy-bg:   rgba(168, 135, 70,.1);
  --accent-burgundy-border:rgba(168, 135, 70,.3);

  --red:       #c0392b;
  --red-bg:    #fff5f5;
  --red-border:rgba(192,57,43,.2);

  --green:     #1a7a4a;
  --green-bg:  #f0fdf6;
  --green-border:rgba(26,122,74,.2);

  --blue:      #1e5fa3;
  --blue-bg:   #eff6ff;
  --blue-border:rgba(30,95,163,.18);

  --amber:     #92580a;
  --amber-bg:  #fffbeb;
  --amber-border:rgba(146,88,10,.2);

  --txt:       #212529;
  --txt-muted: #6C757D;
  --txt-dim:   #ADB5BD;

  --shadow-sm: 0 1px 4px rgba(209, 209, 209,.06), 0 4px 12px rgba(209, 209, 209,.04);
  --shadow-md: 0 4px 16px rgba(209, 209, 209,.08), 0 12px 32px rgba(209, 209, 209,.05);
  --shadow-lg: 0 8px 32px rgba(209, 209, 209,.12), 0 24px 56px rgba(209, 209, 209,.07);

  --mono: 'Space Mono', monospace;
  --sans: 'Syne', sans-serif;

  --r:    0px;
  --r-sm: 0px;
  --ease: cubic-bezier(.4,0,.2,1);
}

/* ═══════════════════════════════════════
   BASE
═══════════════════════════════════════ */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

html { height: 100%; }
body {
  background: var(--bg);
  color: var(--txt);
  font-family: var(--sans);
  min-height: 100vh;
  padding: 0;
  overflow-x: hidden;
}

/* Subtle paper grain texture */
body::before {
  content: '';
  position: fixed; inset: 0;
  background-image:
    url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='200' height='200'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='200' height='200' filter='url(%23n)' opacity='0.03'/%3E%3C/svg%3E");
  pointer-events: none;
  z-index: 0;
  opacity: .5;
}

/* ═══════════════════════════════════════
   TOPBAR
═══════════════════════════════════════ */
.kds-topbar {
  position: sticky; top: 0; z-index: 100;
  background: rgba(255,255,255,.92);
  backdrop-filter: blur(16px);
  border-bottom: 1px solid var(--border);
  padding: 0 28px;
  height: 64px;
  display: flex; align-items: center; justify-content: space-between;
  gap: 24px;
  box-shadow: 0 1px 0 var(--border), 0 2px 12px rgba(20,59,54,.04);
}

/* Left: brand + title */
.topbar-left {
  display: flex; align-items: center; gap: 16px;
}
.topbar-logo {
  width: 36px; height: 36px;
  background: var(--forest);
  border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  font-size: 16px;
  flex-shrink: 0;
  box-shadow: 0 2px 8px rgba(20,59,54,.25);
}
.topbar-title {
  font-size: 15px;
  font-weight: 700;
  color: var(--forest);
  letter-spacing: .04em;
  line-height: 1.2;
}
.topbar-subtitle {
  font-size: 10px;
  color: var(--txt-muted);
  letter-spacing: .12em;
  text-transform: uppercase;
  font-family: var(--mono);
}

/* Center: live stats pills */
.topbar-stats {
  display: flex; align-items: center; gap: 8px;
}
.stat-pill {
  display: flex; align-items: center; gap: 7px;
  padding: 5px 14px;
  border-radius: 50px;
  font-family: var(--mono);
  font-size: 12px;
  font-weight: 700;
  border: 1px solid;
}
.stat-pill.total {
  background: var(--blue-bg);
  border-color: var(--blue-border);
  color: var(--blue);
}
.stat-pill.urgent {
  background: var(--red-bg);
  border-color: var(--red-border);
  color: var(--red);
}
.stat-pill.normal {
  background: var(--green-bg);
  border-color: var(--green-border);
  color: var(--green);
}
.stat-pill-dot {
  width: 6px; height: 6px; border-radius: 50%;
  background: currentColor;
}
.stat-pill.urgent .stat-pill-dot {
  animation: dotPulse 1.2s ease-in-out infinite;
}
@keyframes dotPulse {
  0%,100% { opacity: .4; transform: scale(.8); }
  50%      { opacity: 1;  transform: scale(1.3); }
}

/* Right: clock + actions */
.topbar-right {
  display: flex; align-items: center; gap: 12px;
  flex-shrink: 0;
}
.kds-clock {
  font-family: var(--mono);
  font-size: 20px;
  font-weight: 700;
  color: var(--forest);
  letter-spacing: .08em;
  background: var(--forest-dim);
  border: 1px solid rgba(20,59,54,.12);
  border-radius: var(--r-sm);
  padding: 6px 16px;
  line-height: 1;
}
.kds-date {
  font-family: var(--mono);
  font-size: 10px;
  color: var(--txt-muted);
  text-align: center;
  margin-top: 2px;
  letter-spacing: .04em;
}

.refresh-bar {
  display: flex; align-items: center; gap: 8px;
}
.refresh-label {
  font-size: 10px;
  font-family: var(--mono);
  color: var(--txt-muted);
  letter-spacing: .06em;
}
.refresh-ring {
  width: 28px; height: 28px; position: relative;
}
.refresh-ring svg { transform: rotate(-90deg); }
.refresh-ring circle {
  fill: none;
  stroke: rgba(20,59,54,.1);
  stroke-width: 3;
}
.refresh-ring .progress {
  stroke: var(--forest);
  stroke-width: 3;
  stroke-linecap: round;
  stroke-dasharray: 69.1;
  stroke-dashoffset: 0;
  transition: stroke-dashoffset .5s linear;
}

.btn-exit {
  display: flex; align-items: center; gap: 7px;
  padding: 7px 16px;
  border: 1px solid var(--border-md);
  border-radius: var(--r-sm);
  background: transparent;
  color: var(--txt-muted);
  font-family: var(--sans);
  font-size: 12px;
  font-weight: 500;
  letter-spacing: .06em;
  text-decoration: none;
  cursor: pointer;
  transition: all .2s var(--ease);
}
.btn-exit:hover { border-color: var(--red); color: var(--red); background: var(--red-bg); }

/* ═══════════════════════════════════════
   MAIN GRID
═══════════════════════════════════════ */
.kds-main {
  position: relative; z-index: 1;
  padding: 28px;
}

/* Section label */
.kds-section-label {
  font-family: var(--mono);
  font-size: 10px;
  letter-spacing: .18em;
  text-transform: uppercase;
  color: var(--txt-muted);
  margin-bottom: 20px;
  display: flex; align-items: center; gap: 10px;
}
.kds-section-label::after {
  content: '';
  flex: 1; height: 1px;
  background: var(--border);
}

.ticket-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 18px;
}

/* ═══════════════════════════════════════
   TICKET
═══════════════════════════════════════ */
.ticket {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--r);
  display: flex; flex-direction: column;
  position: relative; overflow: hidden;
  transition: transform .25s var(--ease), box-shadow .25s var(--ease), border-color .25s;
  animation: ticketIn .4s var(--ease) both;
  box-shadow: var(--shadow-sm);
}

@keyframes ticketIn {
  from { opacity: 0; transform: translateY(16px) scale(.98); }
  to   { opacity: 1; transform: none; }
}

.ticket:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-lg);
  border-color: var(--border-md);
}

/* Top accent strip */
.ticket-strip {
  height: 3px;
  background: var(--border);
  position: relative; overflow: hidden;
}
.ticket-strip::after {
  content: '';
  position: absolute; inset: 0;
  background: linear-gradient(90deg, transparent, var(--forest), transparent);
  opacity: 0;
  transition: opacity .3s;
}
.ticket:hover .ticket-strip::after { opacity: 1; }

/* URGENT state */
.ticket.urgent {
  border-color: rgba(192,57,43,.22);
  box-shadow: 0 0 0 1px rgba(192,57,43,.1), var(--shadow-md);
}
.ticket.urgent .ticket-strip {
  background: var(--red);
  animation: urgentStrip 1.5s ease-in-out infinite;
}
.ticket.urgent .ticket-strip::after { display: none; }
@keyframes urgentStrip {
  0%,100% { opacity: .7; }
  50%      { opacity: 1; }
}
.ticket.urgent:hover {
  box-shadow: 0 0 0 1px rgba(192,57,43,.3), var(--shadow-lg);
}

/* ── TICKET HEADER ── */
.ticket-head {
  padding: 18px 18px 14px;
  display: flex; align-items: flex-start; justify-content: space-between;
  gap: 10px;
  border-bottom: 1px solid var(--border);
  background: var(--surface2);
}
.ticket-head-left { flex: 1; min-width: 0; }

.ticket-order-num {
  font-family: var(--mono);
  font-size: 10px;
  letter-spacing: .14em;
  color: var(--txt-muted);
  margin-bottom: 5px;
  text-transform: uppercase;
}
.ticket-customer {
  font-size: 17px;
  font-weight: 700;
  color: var(--txt);
  line-height: 1.2;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Time badge */
.ticket-time {
  flex-shrink: 0;
  text-align: right;
}
.time-main {
  font-family: var(--mono);
  font-size: 15px;
  font-weight: 700;
  color: var(--forest);
  line-height: 1.1;
}
.time-date {
  font-family: var(--mono);
  font-size: 10px;
  color: var(--txt-muted);
  margin-top: 2px;
}
.ticket.urgent .time-main { color: var(--red); }
.urgent-badge {
  display: none;
  font-family: var(--mono);
  font-size: 9px;
  font-weight: 700;
  letter-spacing: .1em;
  text-transform: uppercase;
  color: var(--red);
  background: var(--red-bg);
  border: 1px solid var(--red-border);
  border-radius: 4px;
  padding: 2px 8px;
  margin-top: 4px;
}
.ticket.urgent .urgent-badge { display: inline-block; }

/* ── TICKET BODY ── */
.ticket-body {
  padding: 16px 18px;
  flex: 1;
  display: flex; flex-direction: column; gap: 12px;
}

/* Meta row */
.meta-row {
  display: flex; gap: 8px; flex-wrap: wrap;
}
.meta-chip {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 4px 12px;
  border-radius: 50px;
  font-size: 12px;
  font-weight: 500;
  border: 1px solid;
}
.meta-chip.guests {
  background: var(--blue-bg);
  border-color: var(--blue-border);
  color: var(--blue);
}
.meta-chip.svc {
  background: var(--accent-burgundy-bg);
  border-color: var(--accent-burgundy-border);
  color: var(--accent-burgundy);
}
.meta-chip i { font-size: 10px; }

/* Course / combo */
.course-block {
  background: var(--surface2);
  border: 1px solid var(--border);
  border-radius: var(--r-sm);
  padding: 12px 14px;
  display: flex; align-items: center; gap: 10px;
}
.course-icon {
  width: 32px; height: 32px;
  border-radius: 6px;
  display: flex; align-items: center; justify-content: center;
  font-size: 14px; flex-shrink: 0;
}
.course-icon.special {
  background: var(--amber-bg);
  border: 1px solid var(--amber-border);
}
.course-icon.regular {
  background: var(--blue-bg);
  border: 1px solid var(--blue-border);
}
.course-label {
  font-size: 10px;
  font-family: var(--mono);
  color: var(--txt-muted);
  letter-spacing: .08em;
  text-transform: uppercase;
  margin-bottom: 3px;
}
.course-name {
  font-size: 13px;
  font-weight: 600;
  color: var(--txt);
  line-height: 1.3;
}
.course-icon.special + div .course-name { color: var(--amber); }
.course-icon.regular + div .course-name { color: var(--blue); }

/* ALLERGY WARNING */
.allergy-block {
  background: var(--red-bg);
  border: 1px solid var(--red-border);
  border-radius: var(--r-sm);
  padding: 12px 14px;
  position: relative; overflow: hidden;
}
.allergy-block::before {
  content: '!';
  position: absolute;
  right: -4px; top: -8px;
  font-size: 4rem;
  font-weight: 900;
  color: rgba(192,57,43,.05);
  line-height: 1;
  pointer-events: none;
}
.block-label {
  display: flex; align-items: center; gap: 6px;
  font-family: var(--mono);
  font-size: 9px;
  font-weight: 700;
  letter-spacing: .16em;
  text-transform: uppercase;
  margin-bottom: 6px;
}
.block-label.red   { color: var(--red); }
.block-label.gold  { color: var(--accent-burgundy); }
.block-label.muted { color: var(--txt-muted); }
.block-body {
  font-size: 13px;
  line-height: 1.55;
  color: #922b21;
  font-weight: 500;
}

/* DNA block */
.dna-block {
  background: var(--accent-burgundy-bg);
  border: 1px solid var(--accent-burgundy-border);
  border-radius: var(--r-sm);
  padding: 12px 14px;
}
.dna-body { font-size: 13px; color: var(--txt); line-height: 1.7; }
.dna-body span { color: var(--txt-muted); margin-right: 4px; }

/* Note block */
.note-block {
  background: var(--surface2);
  border-left: 2px solid var(--border-md);
  border-radius: 0 var(--r-sm) var(--r-sm) 0;
  padding: 10px 14px;
}
.note-text {
  font-size: 12px;
  font-style: italic;
  color: var(--txt-muted);
  line-height: 1.6;
}

/* Food List */
.food-list {
  display: flex; flex-direction: column; gap: 8px;
  margin-top: 10px;
}
.food-item {
  display: flex; justify-content: space-between; align-items: flex-start;
  font-size: 13px; font-weight: 600; color: var(--txt);
  padding-bottom: 6px; border-bottom: 1px dashed var(--border);
}
.food-item:last-child { border-bottom: none; padding-bottom: 0; }
.food-name { flex: 1; }
.food-qty { font-family: var(--mono); font-weight: 700; color: var(--forest); margin-left: 10px; font-size: 14px; }
.food-note { display: block; font-size: 11px; font-style: italic; color: #c0392b; background: rgba(192,57,43,0.08); padding: 2px 6px; border-radius: 4px; margin-top: 4px; display: inline-block; font-weight: 700; letter-spacing: 0.02em; }

/* ── TICKET FOOTER ── */
.ticket-foot {
  padding: 14px 18px;
  border-top: 1px solid var(--border);
  background: var(--surface2);
}

.btn-done {
  width: 100%;
  padding: 12px 16px;
  border: 1px solid var(--green-border);
  border-radius: var(--r-sm);
  background: var(--green-bg);
  color: var(--green);
  font-family: var(--sans);
  font-size: 13px;
  font-weight: 700;
  letter-spacing: .08em;
  text-transform: uppercase;
  cursor: pointer;
  display: flex; align-items: center; justify-content: center; gap: 8px;
  transition: all .22s var(--ease);
  position: relative; overflow: hidden;
}
.btn-done::before {
  content: '';
  position: absolute; inset: 0;
  background: var(--forest);
  opacity: 0;
  transition: opacity .22s;
}
.btn-done:hover {
  border-color: var(--forest);
  box-shadow: 0 4px 16px rgba(20,59,54,.18);
  color: #fff;
}
.btn-done:hover::before { opacity: 1; }
.btn-done:active { transform: scale(.97); }
.btn-done span, .btn-done i { position: relative; z-index: 1; }

/* Completing state */
.btn-done.completing {
  pointer-events: none;
  opacity: .7;
}

/* ═══════════════════════════════════════
   EMPTY STATE
═══════════════════════════════════════ */
.kds-empty {
  grid-column: 1 / -1;
  text-align: center;
  padding: 100px 24px;
}
.kds-empty-ring {
  width: 96px; height: 96px;
  border-radius: 50%;
  background: var(--green-bg);
  border: 2px solid var(--green-border);
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 28px;
  font-size: 40px;
  animation: breathe 3s ease-in-out infinite;
}
@keyframes breathe {
  0%,100% { box-shadow: 0 0 0 0 rgba(26,122,74,.08); }
  50%      { box-shadow: 0 0 0 20px rgba(26,122,74,0); }
}
.kds-empty h3 {
  font-size: 1.4rem; font-weight: 600;
  color: var(--txt-muted);
  margin-bottom: 8px;
}
.kds-empty p {
  font-size: 13px;
  font-family: var(--mono);
  color: var(--txt-dim);
  letter-spacing: .04em;
}

/* ═══════════════════════════════════════
   TOAST
═══════════════════════════════════════ */
.kds-toast-wrap {
  position: fixed;
  bottom: 28px; right: 28px;
  z-index: 9999;
  display: flex; flex-direction: column; gap: 10px;
  pointer-events: none;
}
.kds-toast {
  background: var(--surface);
  border: 1px solid var(--border-md);
  border-left: 4px solid var(--border-md);
  border-radius: var(--r-sm);
  padding: 14px 18px;
  font-size: 13px;
  color: var(--txt);
  display: flex; align-items: center; gap: 10px;
  animation: toastIn .3s var(--ease) forwards;
  max-width: 300px;
  pointer-events: all;
  box-shadow: var(--shadow-md);
}
.kds-toast.success { border-left-color: var(--green); background: #f0fdf4; }
.kds-toast.success i { color: var(--green); }
.kds-toast.error { border-left-color: var(--red); background: #fef2f2; }
.kds-toast.error i { color: var(--red); }
@keyframes toastIn {
  from { opacity: 0; transform: translateY(12px) scale(.96); }
  to   { opacity: 1; transform: none; }
}
@keyframes toastOut {
  to { opacity: 0; transform: translateY(8px) scale(.96); }
}

/* ═══════════════════════════════════════
   SCROLLBAR
═══════════════════════════════════════ */
::-webkit-scrollbar { width: 6px; background: var(--bg); }
::-webkit-scrollbar-thumb { background: rgba(20,59,54,.15); border-radius: 3px; }
::-webkit-scrollbar-thumb:hover { background: rgba(20,59,54,.25); }

/* ═══════════════════════════════════════
   STAGGER ANIMATION
═══════════════════════════════════════ */
.ticket:nth-child(1)  { animation-delay: .04s; }
.ticket:nth-child(2)  { animation-delay: .08s; }
.ticket:nth-child(3)  { animation-delay: .12s; }
.ticket:nth-child(4)  { animation-delay: .16s; }
.ticket:nth-child(5)  { animation-delay: .20s; }
.ticket:nth-child(6)  { animation-delay: .24s; }
.ticket:nth-child(7)  { animation-delay: .28s; }
.ticket:nth-child(8)  { animation-delay: .32s; }
.ticket:nth-child(n+9){ animation-delay: .36s; }

@media (max-width: 640px) {
  .kds-topbar { padding: 0 16px; gap: 12px; }
  .topbar-stats { display: none; }
  .kds-main { padding: 16px; }
  .ticket-grid { grid-template-columns: 1fr; gap: 14px; }
}

/* Upcoming Orders Widget */
.upcoming-section {
  margin-top: 40px;
  background: var(--surface);
  border: 1px solid var(--border-md);
  border-radius: var(--r);
  padding: 20px;
}
.upcoming-title {
  font-size: 14px;
  font-weight: 700;
  color: var(--forest);
  margin-bottom: 15px;
  text-transform: uppercase;
  letter-spacing: .05em;
  display: flex;
  align-items: center;
  gap: 8px;
}
.upcoming-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 12px;
}
.upcoming-card {
  background: var(--surface2);
  padding: 12px 16px;
  border-radius: var(--r-sm);
  border-left: 3px solid var(--accent-burgundy);
}
.up-date {
  font-size: 11px;
  color: var(--accent-burgundy);
  font-family: var(--mono);
  font-weight: 700;
  margin-bottom: 4px;
}
.up-name {
  font-size: 13px;
  font-weight: 600;
  color: var(--txt);
  margin-bottom: 2px;
}
.up-detail {
  font-size: 12px;
  color: var(--txt-muted);
}
</style>
</head>
<body>

<?php
$totalOrders  = count($all_orders);
$urgentOrders = 0;
foreach ($all_orders as $o) {
    $diff = strtotime($o['booking_date']) - time();
    if ($diff > 0 && $diff < 1800) $urgentOrders++;
}
$normalOrders = $totalOrders - $urgentOrders;
?>

<!-- ═══ TOPBAR ═══ -->
<header class="kds-topbar">
  <div class="topbar-left">
    <div class="topbar-logo">🍳</div>
    <div>
      <div class="topbar-title">Kitchen Display System</div>
      <div class="topbar-subtitle">Bespoke · Command Center</div>
    </div>
  </div>

  <div class="topbar-stats">
    <div class="stat-pill total">
      <div class="stat-pill-dot"></div>
      <span><?= $totalOrders ?> Đơn</span>
    </div>
    <?php if ($urgentOrders > 0): ?>
    <div class="stat-pill urgent">
      <div class="stat-pill-dot"></div>
      <span><?= $urgentOrders ?> Khẩn</span>
    </div>
    <?php endif; ?>
    <div class="stat-pill normal">
      <div class="stat-pill-dot"></div>
      <span><?= $normalOrders ?> Bình thường</span>
    </div>
  </div>

  <div class="topbar-right">
    <?php if (!empty($upcoming_orders)): ?>
    <button class="btn-exit" style="cursor:pointer; padding: 7px 12px; background:var(--forest-dim); border:1px solid rgba(168, 135, 70, 0.3); color:var(--forest);" onclick="document.getElementById('upcomingModal').style.display='flex'">
      <i class="fas fa-calendar-alt"></i> Sắp tới (<?= count($upcoming_orders) ?>)
    </button>
    <?php endif; ?>

    <div>
      <div class="kds-clock" id="liveClock">00:00:00</div>
      <div class="kds-date" id="liveDate"></div>
    </div>

    <button id="btnSound" class="btn-exit" style="cursor:pointer; padding: 7px 12px;" onclick="toggleSound()" title="Bật/Tắt âm thanh báo đơn mới">
      <i class="fas fa-volume-up"></i>
    </button>

    <a href="admin/admin_dashboard.php" class="btn-exit">
      <i class="fas fa-arrow-right-from-bracket" style="font-size:11px"></i>
      Thoát
    </a>
  </div>
</header>

<!-- ═══ MAIN ═══ -->
<main class="kds-main">

  <div class="kanban-board" style="display: flex; gap: 20px; padding: 0 28px; height: calc(100vh - 120px); overflow-x: auto; overflow-y: hidden; align-items: stretch;">

    <?php
    if (!function_exists('renderTicket')) {
      function renderTicket($order) {
          $diff      = strtotime($order['booking_date']) - time();
          $is_urgent = ($diff > 0 && $diff < 1800);
          $dt        = new DateTime($order['booking_date']);
          ?>
          <?php if (isset($order['is_pos'])): ?>
          <!-- ═══ POS TICKET ═══ -->
          <div class="ticket <?= $is_urgent ? 'urgent' : '' ?>" id="ticket-pos-<?= $order['real_order_id'] ?>">
            <div class="ticket-strip"></div>
            <div class="ticket-head">
              <div class="ticket-head-left">
                <div class="ticket-order-num">POS ORDER # <?= str_pad($order['real_order_id'], 4, '0', STR_PAD_LEFT) ?></div>
                <div class="ticket-customer" style="color: var(--blue);"><?= htmlspecialchars($order['customer_name']) ?></div>
              </div>
              <div class="ticket-time">
                <div class="time-main"><?= $dt->format('H:i') ?></div>
                <div class="time-date"><?= $dt->format('d/m') ?></div>
                <div class="urgent-badge"><i class="fas fa-bolt" style="font-size:8px"></i> KHẨN</div>
              </div>
            </div>
            <div class="ticket-body">
              
              <!-- Meta chips -->
              <div class="meta-row">
                <span class="meta-chip guests" style="background: var(--blue-bg); border-color: var(--blue-border); color: var(--blue);">
                  <i class="fas fa-chair"></i> Gọi tại bàn
                </span>
                <span class="meta-chip svc" style="background: var(--green-bg); border-color: var(--green-border); color: var(--green);">
                  <i class="fas fa-concierge-bell"></i> Lên món liền
                </span>
              </div>

              <div class="course-block" style="background: var(--surface2); border-color: var(--border);">
                <div class="course-icon regular"><i class="fas fa-utensils" style="color:var(--blue);font-size:13px"></i></div>
                <div>
                  <div class="course-label">Thu Ngân Gọi Món</div>
                  <div class="course-name">Phục vụ tại bàn</div>
                </div>
              </div>
              <div class="food-list">
                <?php foreach ($order['foods'] as $f): ?>
                  <div class="food-item" style="align-items: center;">
                    <div class="food-name">
                      <span style="font-weight: 700;"><?= htmlspecialchars($f['food_name']) ?></span>
                      <?php if ($f['status'] === 'pending'): ?>
                        <span class="badge bg-secondary ms-2" style="font-size: 0.7rem; padding: 2px 6px; border-radius: 4px;">Đợi bếp</span>
                      <?php elseif ($f['status'] === 'preparing'): ?>
                        <span class="badge bg-info text-dark ms-2" style="font-size: 0.7rem; padding: 2px 6px; border-radius: 4px;">Đang chuẩn bị</span>
                      <?php elseif ($f['status'] === 'cooking'): ?>
                        <span class="badge bg-warning text-dark ms-2" style="font-size: 0.7rem; padding: 2px 6px; border-radius: 4px;">Đang nấu</span>
                      <?php elseif ($f['status'] === 'ready'): ?>
                        <span class="badge bg-success ms-2" style="font-size: 0.7rem; padding: 2px 6px; border-radius: 4px;">Đã xong</span>
                      <?php endif; ?>
                    </div>
                    <div class="food-qty" style="margin-right: 15px;">x<?= $f['quantity'] ?></div>
                    
                    <?php if ($f['status'] === 'pending'): ?>
                      <button type="button" style="padding:8px 16px; font-size:13px; font-weight:600; border:1px solid var(--border-md); background:var(--surface); border-radius:6px; cursor:pointer;" onclick="updatePosStatus(<?= $f['order_item_id'] ?>, 'preparing', this, 'pos')">
                        <i class="fas fa-play" style="color:var(--txt-muted); margin-right:6px;"></i>Chuẩn bị
                      </button>
                    <?php elseif ($f['status'] === 'preparing'): ?>
                      <button type="button" style="padding:8px 16px; font-size:13px; font-weight:600; border:1px solid var(--border-md); background:var(--surface); border-radius:6px; cursor:pointer; color:#d35400;" onclick="updatePosStatus(<?= $f['order_item_id'] ?>, 'cooking', this, 'pos')">
                        <i class="fas fa-fire" style="margin-right:6px;"></i>Nấu
                      </button>
                    <?php elseif ($f['status'] === 'cooking'): ?>
                      <button type="button" style="padding:8px 16px; font-size:13px; font-weight:600; border:1px solid var(--green-border); background:var(--green-bg); color:var(--green); border-radius:6px; cursor:pointer;" onclick="completePosItem(<?= $f['order_item_id'] ?>, this, 'pos')">
                        <i class="fas fa-check" style="margin-right:6px;"></i>Xong
                      </button>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
            
            <!-- Footer -->
            <div class="ticket-foot" style="display: flex; justify-content: center; align-items: center; padding: 12px; background: var(--surface2); border-top: 1px solid var(--border); color: var(--txt-muted); font-size: 11px; font-weight: 500; font-family: var(--mono);">
              <i class="fas fa-info-circle" style="margin-right: 6px;"></i> Bấm "XONG" từng món khi hoàn tất
            </div>
          </div>

          <?php else: ?>
          <!-- ═══ BOOKING TICKET ═══ -->
          <div class="ticket <?= $is_urgent ? 'urgent' : '' ?>" id="ticket-<?= $order['id'] ?>">
            <div class="ticket-strip"></div>
            <div class="ticket-head">
              <div class="ticket-head-left">
                <div class="ticket-order-num">BOOKING # <?= str_pad($order['id'], 4, '0', STR_PAD_LEFT) ?></div>
                <div class="ticket-customer"><?= htmlspecialchars($order['customer_name']) ?></div>
              </div>
              <div class="ticket-time">
                <div class="time-main"><?= $dt->format('H:i') ?></div>
                <div class="time-date"><?= $dt->format('d/m') ?></div>
                <div class="urgent-badge"><i class="fas fa-bolt" style="font-size:8px"></i> KHẨN</div>
              </div>
            </div>
            <div class="ticket-body">
              <div class="meta-row">
                <span class="meta-chip guests">
                  <i class="fas fa-user-friends"></i> <?= $order['guests'] ?> Khách
                </span>
                <span class="meta-chip svc">
                  <i class="fas fa-concierge-bell"></i> <?= htmlspecialchars($order['service_type']) ?>
                </span>
              </div>

              <?php if (!empty($order['combo_name'])): ?>
              <div class="course-block">
                <div class="course-icon special"><i class="fas fa-star" style="color:var(--amber);font-size:13px"></i></div>
                <div>
                  <div class="course-label">Tasting Menu</div>
                  <div class="course-name"><?= htmlspecialchars($order['combo_name']) ?></div>
                </div>
              </div>
              <?php endif; ?>

              <?php if (!empty($order['foods'])): ?>
              <div class="food-list">
                    <?php 
                    $itemType = (isset($order['is_pos']) && $order['is_pos']) ? 'pos' : 'booking';
                    foreach ($order['foods'] as $f): 
                    ?>
                  <div class="food-item" style="display:flex; flex-direction:column; gap:8px;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                      <div class="food-name">
                        <span style="font-weight: 700;"><?= htmlspecialchars($f['food_name']) ?></span>
                        <?php if (!empty($f['toppings'])): ?>
                          <br>
                          <span class="food-note" style="background: rgba(168, 135, 70, 0.1); color: var(--accent-burgundy); border: 1px solid rgba(168, 135, 70, 0.2); font-weight: bold; font-style: normal; margin-top: 2px;">
                            <i class="fas fa-plus-circle" style="font-size:9px; margin-right:3px;"></i>Topping: <?= htmlspecialchars(implode(', ', $f['toppings'])) ?>
                          </span>
                        <?php endif; ?>
                        <?php if (!empty($f['special_notes'])): ?>
                          <br>
                          <span class="food-note" style="background: rgba(192, 57, 43, 0.08); color: #c0392b; border: 1px solid rgba(192, 57, 43, 0.15); font-weight: bold; font-style: italic; margin-top: 2px;">
                            <i class="fas fa-exclamation-circle" style="font-size:9px; margin-right:3px;"></i><?= htmlspecialchars($f['special_notes']) ?>
                          </span>
                        <?php endif; ?>
                      </div>
                      <div class="food-qty">x<?= $f['quantity'] ?></div>
                    </div>
                    <!-- Nút thao tác món -->
                    <?php if (isset($f['status'])): ?>
                    <div class="food-actions" style="display:flex; gap:8px; margin-top:4px;">
                      <?php if ($f['status'] == 'pending'): ?>
                        <button type="button" style="padding:8px 16px; font-size:13px; font-weight:600; border:1px solid var(--border-md); background:var(--surface); border-radius:6px; cursor:pointer;" onclick="updatePosStatus(<?= $f['order_item_id'] ?>, 'preparing', this, '<?= $itemType ?>')">
                          <i class="fas fa-play" style="color:var(--txt-muted); margin-right:6px;"></i>Chuẩn bị
                        </button>
                      <?php elseif ($f['status'] == 'preparing'): ?>
                        <button type="button" style="padding:8px 16px; font-size:13px; font-weight:600; border:1px solid var(--border-md); background:var(--surface); border-radius:6px; cursor:pointer; color:#d35400;" onclick="updatePosStatus(<?= $f['order_item_id'] ?>, 'cooking', this, '<?= $itemType ?>')">
                          <i class="fas fa-fire" style="margin-right:6px;"></i>Nấu
                        </button>
                      <?php elseif ($f['status'] == 'cooking'): ?>
                        <button type="button" style="padding:4px 8px; font-size:11px; border:1px solid var(--green-border); background:var(--green-bg); color:var(--green); border-radius:4px; cursor:pointer; font-weight:600;" onclick="completePosItem(<?= $f['order_item_id'] ?>, this, '<?= $itemType ?>')">
                          <i class="fas fa-check" style="font-size:9px; margin-right:4px;"></i>Xong
                        </button>
                      <?php elseif ($f['status'] == 'ready'): ?>
                        <span style="font-size:11px; font-weight:600; color:var(--green);"><i class="fas fa-check-circle" style="margin-right:4px;"></i>Đã xong</span>
                      <?php endif; ?>
                    </div>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              </div>
              <?php endif; ?>

              <!-- Allergy warning -->
              <?php if (!empty($order['allergies'])): ?>
              <div class="allergy-block">
                <div class="block-label red">
                  <i class="fas fa-shield-virus" style="font-size:9px"></i>
                  Cảnh Báo Dị Ứng Y Tế
                </div>
                <div class="block-body"><?= htmlspecialchars($order['allergies']) ?></div>
              </div>
              <?php endif; ?>

              <!-- DNA -->
              <?php if (!empty($order['doneness']) || !empty($order['flavor_profile'])): ?>
              <div class="dna-block">
                <div class="block-label gold">
                  <i class="fas fa-dna" style="font-size:9px"></i>
                  DNA Ẩm Thực Khách Hàng
                </div>
                <div class="dna-body">
                  <?php if (!empty($order['doneness'])): ?>
                    <div><span>Độ chín bò:</span><?= htmlspecialchars($order['doneness']) ?></div>
                  <?php endif; ?>
                  <?php if (!empty($order['flavor_profile'])): ?>
                    <div><span>Khẩu vị:</span><?= htmlspecialchars($order['flavor_profile']) ?></div>
                  <?php endif; ?>
                </div>
              </div>
              <?php endif; ?>

              <!-- Chef requirements / Note -->
              <?php if (!empty($order['chef_requirements'])): ?>
              <div class="note-block">
                <div class="block-label muted">
                  <i class="fas fa-quote-left" style="font-size:8px"></i>
                  Yêu cầu bếp trưởng
                </div>
                <div class="note-text"><?= htmlspecialchars($order['chef_requirements']) ?></div>
              </div>
              <?php endif; ?>

              <?php if (!empty($order['message'])): ?>
              <div class="note-block">
                <div class="block-label muted">
                  <i class="fas fa-comment-dots" style="font-size:8px"></i>
                  Lời nhắn khách hàng
                </div>
                <div class="note-text"><?= htmlspecialchars($order['message']) ?></div>
              </div>
              <?php endif; ?>

            </div>

            <?php
            $all_ready = true;
            if (!empty($order['foods'])) {
                foreach ($order['foods'] as $f) {
                    if (!isset($f['status']) || ($f['status'] !== 'ready' && $f['status'] !== 'served')) {
                        $all_ready = false;
                        break;
                    }
                }
            }
            // Nếu đơn không có món nào (lỗi) hoặc chưa xong hết món, thì không hiển thị nút Xong
            if ($all_ready && !empty($order['foods'])):
            ?>
            <!-- Footer -->
            <div class="ticket-foot">
              <button class="btn-done" onclick="completeOrder(<?= $order['id'] ?>, this)">
                <i class="fas fa-check-circle"></i>
                <span>Chế Biến Xong</span>
              </button>
            </div>
            <?php endif; ?>

          </div>
          <?php endif; ?>
          <?php
      }
    }
    ?>

    <!-- Cột 1: MỚI NHẬN -->
    <div class="kanban-col" style="flex: 1; min-width: 360px; display: flex; flex-direction: column; background: var(--bg); border: 1px solid var(--border); border-radius: var(--r); margin-bottom: 20px;">
      <div style="padding: 18px 24px; font-weight: 700; color: var(--forest); border-bottom: 2px solid var(--border-md); background: var(--surface); border-radius: var(--r) var(--r) 0 0; display: flex; justify-content: space-between; align-items: center;">
        <div><i class="fas fa-inbox me-2"></i> MỚI NHẬN</div>
        <span style="background: var(--surface2); padding: 4px 10px; border-radius: 20px; font-size: 12px; color: var(--txt); border: 1px solid var(--border);"><?= count($kanban_todo) ?></span>
      </div>
      <div style="flex: 1; overflow-y: auto; padding: 20px 24px; display: flex; flex-direction: column; gap: 16px;">
        <?php 
        if (empty($kanban_todo)) { echo '<div style="text-align:center; padding: 40px; color: var(--txt-muted); font-size: 13px;">Không có đơn</div>'; }
        foreach ($kanban_todo as $order) { renderTicket($order); } 
        ?>
      </div>
    </div>

    <!-- Cột 2: ĐANG CHẾ BIẾN -->
    <div class="kanban-col" style="flex: 1; min-width: 360px; display: flex; flex-direction: column; background: var(--bg); border: 1px solid var(--border); border-radius: var(--r); margin-bottom: 20px;">
      <div style="padding: 18px 24px; font-weight: 700; color: #d35400; border-bottom: 2px solid var(--border-md); background: var(--surface); border-radius: var(--r) var(--r) 0 0; display: flex; justify-content: space-between; align-items: center;">
        <div><i class="fas fa-fire me-2"></i> ĐANG CHẾ BIẾN</div>
        <span style="background: var(--surface2); padding: 4px 10px; border-radius: 20px; font-size: 12px; color: var(--txt); border: 1px solid var(--border);"><?= count($kanban_inprogress) ?></span>
      </div>
      <div style="flex: 1; overflow-y: auto; padding: 20px 24px; display: flex; flex-direction: column; gap: 16px;">
        <?php 
        if (empty($kanban_inprogress)) { echo '<div style="text-align:center; padding: 40px; color: var(--txt-muted); font-size: 13px;">Không có đơn</div>'; }
        foreach ($kanban_inprogress as $order) { renderTicket($order); } 
        ?>
      </div>
    </div>

    <!-- Cột 3: SẴN SÀNG -->
    <div class="kanban-col" style="flex: 1; min-width: 360px; display: flex; flex-direction: column; background: var(--bg); border: 1px solid var(--green-border); border-radius: var(--r); margin-bottom: 20px; box-shadow: 0 4px 20px rgba(26,122,74,0.05);">
      <div style="padding: 18px 24px; font-weight: 700; color: var(--green); border-bottom: 2px solid var(--green-border); background: var(--green-bg); border-radius: var(--r) var(--r) 0 0; display: flex; justify-content: space-between; align-items: center;">
        <div><i class="fas fa-bell me-2"></i> SẴN SÀNG</div>
        <span style="background: #fff; padding: 4px 10px; border-radius: 20px; font-size: 12px; color: var(--green); border: 1px solid var(--green-border);"><?= count($kanban_ready) ?></span>
      </div>
      <div style="flex: 1; overflow-y: auto; padding: 20px 24px; display: flex; flex-direction: column; gap: 16px; background: #fafdfb;">
        <?php 
        if (empty($kanban_ready)) { echo '<div style="text-align:center; padding: 40px; color: var(--txt-muted); font-size: 13px;">Không có đơn</div>'; }
        foreach ($kanban_ready as $order) { renderTicket($order); } 
        ?>
      </div>
    </div>

  </div>

  <!-- Upcoming Orders Modal -->
  <?php if (!empty($upcoming_orders)): ?>
  <div id="upcomingModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; justify-content:center; align-items:center;">
    <div style="background:var(--bg); width: 600px; max-width: 90%; max-height: 80vh; border-radius: 12px; display:flex; flex-direction:column; overflow:hidden; box-shadow: var(--shadow-lg);">
      <div style="padding: 16px 24px; border-bottom: 1px solid var(--border); display:flex; justify-content:space-between; align-items:center; background: #fff;">
        <h3 style="margin:0; font-size: 16px; color: var(--forest);"><i class="fas fa-calendar-alt me-2"></i> Đơn đặt trước cho ngày mai / sắp tới (<?= count($upcoming_orders) ?>)</h3>
        <button onclick="document.getElementById('upcomingModal').style.display='none'" style="background:none; border:none; font-size:24px; cursor:pointer; color:var(--txt-muted); line-height:1;">&times;</button>
      </div>
      <div style="padding: 24px; overflow-y:auto; flex:1;">
        <div class="upcoming-list">
          <?php foreach ($upcoming_orders as $up): 
              $up_date = date('d/m/Y - H:i', strtotime($up['booking_date']));
          ?>
          <div class="upcoming-card" style="margin-bottom:12px; padding:16px; border:1px solid var(--border); border-radius:8px; background:#fff; box-shadow: var(--shadow-sm);">
            <div class="up-date" style="color:var(--forest); font-weight:700; font-size:14px; margin-bottom:4px;"><?= $up_date ?></div>
            <div class="up-name" style="font-weight:600; font-size:15px; margin-bottom:4px; color:var(--txt);">
              Khách: <?= htmlspecialchars($up['customer_name']) ?> (<?= $up['guests'] ?> người)
            </div>
            <div class="up-detail" style="font-size:13px; color:var(--txt-muted);">
              <?php 
                if ($up['combo_name']) echo "<div style='margin-bottom:8px;'><strong style='color:var(--txt)'>Combo Tasting:</strong> " . htmlspecialchars($up['combo_name']) . "</div>";
                if ($up['service_type'] !== 'table') echo "<div style='margin-bottom:8px;'><strong style='color:var(--txt)'>Dịch vụ:</strong> " . htmlspecialchars($up['service_type']) . "</div>";
              ?>
              
              <?php if (!empty($up['foods'])): ?>
              <div style="background:var(--surface2); padding:10px 14px; border-radius:6px; margin-bottom:10px;">
                <strong style="color:var(--txt); display:block; margin-bottom:6px; font-size:12px; text-transform:uppercase;">Danh sách món ăn:</strong>
                <?php foreach ($up['foods'] as $f): ?>
                  <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                    <span style="font-weight:600; color:var(--txt);"><?= htmlspecialchars($f['food_name']) ?></span>
                    <span style="font-weight:700;">x<?= $f['quantity'] ?></span>
                  </div>
                  <?php if (!empty($f['toppings'])): ?>
                    <div style="font-size:11px; color:var(--forest); margin-left:8px; margin-bottom:4px;">+ <?= htmlspecialchars(implode(', ', $f['toppings'])) ?></div>
                  <?php endif; ?>
                <?php endforeach; ?>
              </div>
              <?php endif; ?>

              <?php if (!empty($up['allergies'])): ?>
              <div style="background:var(--red-bg); padding:8px 12px; border-radius:6px; margin-bottom:10px; border:1px solid var(--red-border);">
                <strong style="color:var(--red); font-size:12px;"><i class="fas fa-shield-virus"></i> Cảnh báo dị ứng:</strong>
                <div style="color:var(--red); font-weight:600; margin-top:2px;"><?= htmlspecialchars($up['allergies']) ?></div>
              </div>
              <?php endif; ?>

              <?php if (!empty($up['chef_requirements'])): ?>
              <div style="background:#fffbeb; padding:8px 12px; border-radius:6px; border:1px solid rgba(146,88,10,0.2);">
                <strong style="color:#92580a; font-size:12px;"><i class="fas fa-quote-left"></i> Yêu cầu cho bếp:</strong>
                <div style="color:#92580a; margin-top:2px; font-style:italic;"><?= htmlspecialchars($up['chef_requirements']) ?></div>
              </div>
              <?php endif; ?>
              
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

</main>

<!-- Toast container -->
<div class="kds-toast-wrap" id="toastWrap"></div>

<!-- Audio Elements -->
<audio id="audioNewOrder" src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" preload="auto"></audio>
<audio id="audioUrgent" src="https://assets.mixkit.co/active_storage/sfx/995/995-preview.mp3" preload="auto"></audio>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
/* ── Live Clock ── */
function updateClock() {
  var now  = new Date();
  var time = now.toLocaleTimeString('vi-VN', { hour12: false });
  var date = now.toLocaleDateString('vi-VN', { weekday: 'short', day: '2-digit', month: '2-digit' });
  document.getElementById('liveClock').textContent = time;
  document.getElementById('liveDate').textContent  = date;
}
setInterval(updateClock, 1000);
updateClock();

/* ── Refresh ring countdown ── */
var REFRESH_SEC = 15;
var circumference = 2 * Math.PI * 11; // r=11 → 69.115
var ring = document.getElementById('refreshProgress');
var elapsed = 0;

function completeOrder(id, btn) {
  $(btn).addClass('completing').find('span').text('Đang xử lý...');
  
  $.post('kds.php', { action: 'complete_order', booking_id: id }, function(res) {
    if (res.trim() === 'success') {
      $('#ticket-'+id).fadeOut(400, function(){ 
        $(this).remove(); 
        checkEmpty();
      });
      showToast('Đã báo cáo hoàn thành đơn', 'success');
    } else {
      showToast('Có lỗi xảy ra', 'error');
      $(btn).removeClass('completing').find('span').text('Chế Biến Xong');
    }
  });
}

function updatePosStatus(item_id, new_status, btn, type = 'pos') {
  let originalHtml = $(btn).html();
  $(btn).html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
  
  $.post('kds.php', { action: 'update_pos_item_status', item_id: item_id, new_status: new_status, item_type: type }, function(res) {
    if (res.trim() === 'success') {
      showToast('Đã cập nhật trạng thái!', 'success');
      // Reload page to reflect new status buttons
      setTimeout(() => window.location.reload(), 500);
    } else {
      let errorMsg = 'Có lỗi xảy ra';
      if (res.trim().startsWith('error|')) {
          errorMsg = res.trim().split('|')[1];
      }
      showToast(errorMsg, 'error');
      $(btn).html(originalHtml).prop('disabled', false);
    }
  });
}

function completePosItem(item_id, btn, type = 'pos') {
  $(btn).html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
  
  $.post('kds.php', { action: 'complete_pos_item', item_id: item_id, item_type: type }, function(res) {
    if (res.trim() === 'success') {
      showToast('Đã báo Xong cho Thu Ngân!', 'success');
      setTimeout(() => window.location.reload(), 400);
    } else {
      let errorMsg = 'Có lỗi xảy ra';
      if (res.trim().startsWith('error|')) {
          errorMsg = res.trim().split('|')[1];
      }
      showToast(errorMsg, 'error');
      $(btn).html('<i class="fas fa-check"></i> XONG').prop('disabled', false);
    }
  });
}

/* ── Check Empty ── */



// Real-time updates with Pusher
var pusher = new Pusher('cfbc6305339f352b0089', {
  cluster: 'ap1'
});

var channel = pusher.subscribe('restaurant-channel');
channel.bind('update_data', function(data) {
    // Play a tiny notification beep
    var audio = new Audio('../public/assets/sounds/bell.mp3');
    audio.play().catch(function(){});
    
    // Reload to fetch new KDS tickets
    setTimeout(() => {
        window.location.reload();
    }, 500);
});

// Auto-trigger Telegram Reminder every 1 minute
setInterval(() => {
  fetch('admin/cron/cron_telegram_reminder.php', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(res => res.json())
    .then(data => {
      if (data.status === 'success' && data.reminded > 0) {
        showToast(`Đã gửi ${data.reminded} thông báo nhắc khách đến qua Telegram!`, 'success');
      }
    })
    .catch(err => console.error('Telegram cron error:', err));
  fetch('admin/cron/cron_auto_noshow.php', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .catch(err => console.error('No-show cron error:', err));
}, 60000);

/* ── Toast helper ── */
function showToast(msg, type) {
  var wrap  = document.getElementById('toastWrap');
  var toast = document.createElement('div');
  toast.className = 'kds-toast ' + (type || '');
  toast.innerHTML = '<i class="fas ' + (type === 'success' ? 'fa-check-circle' : 'fa-circle-xmark') + '"></i>' + msg;
  wrap.appendChild(toast);
  setTimeout(function() {
    toast.style.animation = 'toastOut .3s var(--ease) forwards';
    setTimeout(function() { toast.remove(); }, 300);
  }, 3000);
}

/* ── Complete order ── */
function completeOrder(id, btn) {
  if (!confirm('Xác nhận món ăn đã nấu xong và giao cho nhân viên phục vụ?')) return;

  btn.classList.add('completing');
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Đang xử lý...</span>';

  $.post('kds.php', { action: 'complete_order', booking_id: id }, function(res) {
    if (res === 'success') {
      showToast('Đơn #' + String(id).padStart(4,'0') + ' — Hoàn thành!', 'success');
      var ticket = document.getElementById('ticket-' + id);
      if (ticket) {
        ticket.style.transition = 'all .4s var(--ease)';
        ticket.style.opacity    = '0';
        ticket.style.transform  = 'scale(.95) translateY(-10px)';
        setTimeout(function() {
          ticket.remove();
          // Update stats
          var total = document.querySelectorAll('.ticket').length;
          if (total === 0) window.location.reload();
        }, 420);
      }
    } else {
      showToast('Có lỗi xảy ra! Thử lại.', 'error');
      btn.classList.remove('completing');
      btn.innerHTML = '<i class="fas fa-check-circle"></i><span>Chế Biến Xong</span>';
    }
  }).fail(function() {
    showToast('Không kết nối được máy chủ!', 'error');
    btn.classList.remove('completing');
    btn.innerHTML = '<i class="fas fa-check-circle"></i><span>Chế Biến Xong</span>';
  });
}

/* ── Sound Alerts ── */
var soundEnabled = localStorage.getItem('kds_sound') !== '0'; // Mặc định bật
function toggleSound() {
    soundEnabled = !soundEnabled;
    localStorage.setItem('kds_sound', soundEnabled ? '1' : '0');
    updateSoundIcon();
    if(soundEnabled) {
        document.getElementById('audioNewOrder').play().catch(e=>{});
    }
}

function updateSoundIcon() {
    var btn = document.getElementById('btnSound');
    if(btn) {
        btn.innerHTML = soundEnabled ? '<i class="fas fa-volume-up"></i>' : '<i class="fas fa-volume-mute" style="color:var(--red)"></i>';
        btn.style.borderColor = soundEnabled ? 'var(--border-md)' : 'var(--red-border)';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    updateSoundIcon();
    
    // Play sound logic
    var currentOrders = [<?= implode(',', array_column($orders, 'id')) ?>];
    var prevOrdersStr = localStorage.getItem('kds_orders');
    var prevOrders = prevOrdersStr ? prevOrdersStr.split(',').map(Number) : [];

    var currentUrgent = <?= $urgentOrders ?>;
    var prevUrgent = parseInt(localStorage.getItem('kds_urgent') || 0);

    if (soundEnabled) {
        var hasNewOrder = currentOrders.some(id => !prevOrders.includes(id));
        if (hasNewOrder && prevOrdersStr !== null) {
            // Có đơn mới
            document.getElementById('audioNewOrder').play().catch(e=>{});
            showToast('CÓ ĐƠN ĐẶT BÀN MỚI!', 'success');
        } else if (currentUrgent > prevUrgent) {
            // Có đơn chuyển sang khẩn cấp
            document.getElementById('audioUrgent').play().catch(e=>{});
            showToast('Chú ý: Có đơn vừa chuyển sang trạng thái KHẨN!', 'error');
        }
    }

    localStorage.setItem('kds_orders', currentOrders.join(','));
    localStorage.setItem('kds_urgent', currentUrgent);
});
</script>
</body>
</html>