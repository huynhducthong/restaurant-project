<?php
session_start();
require_once __DIR__ . '/config/database.php';

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
        echo "success";
    } catch(Exception $e) {
        echo "error";
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

// Truy vấn các đơn đặt trước (Upcoming)
$upcoming_query = "
    SELECT 
        sb.id, sb.customer_name, sb.guests, sb.booking_date, sb.service_type, 
        c.name as combo_name
    FROM service_bookings sb
    LEFT JOIN combos c ON sb.combo_id = c.id
    WHERE sb.status = 'Confirmed' AND DATE(sb.booking_date) > CURDATE()
    ORDER BY sb.booking_date ASC
    LIMIT 15
";
$stmt_up = $db->query($upcoming_query);
$upcoming_orders = $stmt_up->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>KDS — Kitchen Command | Bespoke</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Syne:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
/* ═══════════════════════════════════════
   TOKENS — Light / Warm Kitchen Theme
═══════════════════════════════════════ */
:root {
  --bg:        #f0ece4;
  --surface:   #ffffff;
  --surface2:  #f7f4ef;
  --border:    rgba(20,59,54,.08);
  --border-md: rgba(20,59,54,.14);

  --forest:    #143B36;
  --forest-lt: #1c5049;
  --forest-dim:rgba(20,59,54,.07);

  --gold:      #9a6f2e;
  --gold-bg:   #fdf5e6;
  --gold-border:rgba(154,111,46,.25);

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

  --txt:       #1a2e2b;
  --txt-muted: #6b7f7c;
  --txt-dim:   #a8b8b5;

  --shadow-sm: 0 1px 4px rgba(20,59,54,.06), 0 4px 12px rgba(20,59,54,.04);
  --shadow-md: 0 4px 16px rgba(20,59,54,.08), 0 12px 32px rgba(20,59,54,.05);
  --shadow-lg: 0 8px 32px rgba(20,59,54,.12), 0 24px 56px rgba(20,59,54,.07);

  --mono: 'Space Mono', monospace;
  --sans: 'Syne', sans-serif;

  --r:    10px;
  --r-sm: 6px;
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
  background: var(--gold-bg);
  border-color: var(--gold-border);
  color: var(--gold);
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
.block-label.gold  { color: var(--gold); }
.block-label.muted { color: var(--txt-muted); }
.block-body {
  font-size: 13px;
  line-height: 1.55;
  color: #922b21;
  font-weight: 500;
}

/* DNA block */
.dna-block {
  background: var(--gold-bg);
  border: 1px solid var(--gold-border);
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
  background: #fff;
  border: 1px solid var(--border-md);
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
.kds-toast.success { border-color: var(--green-border); }
.kds-toast.success i { color: var(--green); }
.kds-toast.error { border-color: var(--red-border); }
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
  border-left: 3px solid var(--gold);
}
.up-date {
  font-size: 11px;
  color: var(--gold);
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
$totalOrders  = count($orders);
$urgentOrders = 0;
foreach ($orders as $o) {
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
    <div class="refresh-bar">
      <span class="refresh-label">Auto&nbsp;15s</span>
      <div class="refresh-ring">
        <svg viewBox="0 0 28 28" width="28" height="28">
          <circle cx="14" cy="14" r="11"/>
          <circle class="progress" cx="14" cy="14" r="11" id="refreshProgress"/>
        </svg>
      </div>
    </div>

    <div>
      <div class="kds-clock" id="liveClock">00:00:00</div>
      <div class="kds-date" id="liveDate"></div>
    </div>

    <a href="admin/admin_dashboard.php" class="btn-exit">
      <i class="fas fa-arrow-right-from-bracket" style="font-size:11px"></i>
      Thoát
    </a>
  </div>
</header>

<!-- ═══ MAIN ═══ -->
<main class="kds-main">

  <?php if ($urgentOrders > 0): ?>
  <div class="kds-section-label" style="color:var(--red);opacity:.7">
    <i class="fas fa-circle-exclamation" style="font-size:9px"></i>
    Đơn khẩn — dưới 30 phút
  </div>
  <?php endif; ?>

  <div class="ticket-grid">

    <?php if (empty($orders)): ?>
    <div class="kds-empty">
      <div class="kds-empty-ring">✓</div>
      <h3>Bếp đang rảnh rỗi</h3>
      <p>// Không có đơn nào chờ chế biến</p>
    </div>

    <?php else: ?>
    <?php foreach ($orders as $idx => $order):
      $diff      = strtotime($order['booking_date']) - time();
      $is_urgent = ($diff > 0 && $diff < 1800);
      $dt        = new DateTime($order['booking_date']);
    ?>
    <div class="ticket <?= $is_urgent ? 'urgent' : '' ?>" id="ticket-<?= $order['id'] ?>">

      <!-- Strip -->
      <div class="ticket-strip"></div>

      <!-- Head -->
      <div class="ticket-head">
        <div class="ticket-head-left">
          <div class="ticket-order-num"># <?= str_pad($order['id'], 4, '0', STR_PAD_LEFT) ?></div>
          <div class="ticket-customer"><?= htmlspecialchars($order['customer_name']) ?></div>
        </div>
        <div class="ticket-time">
          <div class="time-main"><?= $dt->format('H:i') ?></div>
          <div class="time-date"><?= $dt->format('d/m') ?></div>
          <div class="urgent-badge">
            <i class="fas fa-bolt" style="font-size:8px"></i> KHẨN
          </div>
        </div>
      </div>

      <!-- Body -->
      <div class="ticket-body">

        <!-- Meta chips -->
        <div class="meta-row">
          <span class="meta-chip guests">
            <i class="fas fa-users"></i>
            <?= $order['guests'] ?> khách
          </span>
          <span class="meta-chip svc">
            <i class="fas fa-concierge-bell"></i>
            <?= $order['service_type'] === 'chef' ? 'Đầu bếp riêng' : ucfirst($order['service_type']) ?>
          </span>
        </div>

        <!-- Course / Combo -->
        <div class="course-block">
          <?php if ($order['service_type'] === 'chef' || $order['combo_id'] == -1): ?>
            <div class="course-icon special">⭐</div>
            <div>
              <div class="course-label">Loại thực đơn</div>
              <div class="course-name">Thực Đơn Thiết Kế Riêng</div>
            </div>
          <?php else: ?>
            <div class="course-icon regular">
              <i class="fas fa-utensils" style="color:var(--blue);font-size:13px"></i>
            </div>
            <div>
              <div class="course-label">Combo / Thực đơn</div>
              <div class="course-name"><?= htmlspecialchars($order['combo_name'] ?? 'A la Carte — Gọi Món') ?></div>
            </div>
          <?php endif; ?>
        </div>

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

      <!-- Footer -->
      <div class="ticket-foot">
        <button class="btn-done" onclick="completeOrder(<?= $order['id'] ?>, this)">
          <i class="fas fa-check-circle"></i>
          <span>Chế Biến Xong</span>
        </button>
      </div>

    </div>
    <?php endforeach; ?>
    <?php endif; ?>

  </div>

  <!-- Upcoming Orders Section -->
  <?php if (!empty($upcoming_orders)): ?>
  <div class="upcoming-section">
    <div class="upcoming-title">
      <i class="fas fa-calendar-alt"></i> Đơn đặt trước cho ngày mai / sắp tới (<?= count($upcoming_orders) ?>)
    </div>
    <div class="upcoming-list">
      <?php foreach ($upcoming_orders as $up): 
          $up_date = date('d/m/Y - H:i', strtotime($up['booking_date']));
      ?>
      <div class="upcoming-card">
        <div class="up-date"><?= $up_date ?></div>
        <div class="up-name">
          Khách: <?= htmlspecialchars($up['customer_name']) ?> (<?= $up['guests'] ?> người)
        </div>
        <div class="up-detail">
          <?php 
            if ($up['combo_name']) echo "<strong>Combo:</strong> " . htmlspecialchars($up['combo_name']) . "<br>";
            if ($up['service_type'] !== 'table') echo "<strong>Dịch vụ:</strong> " . htmlspecialchars($up['service_type']) . "<br>";
          ?>
          <a href="admin/booking_service.php" style="color:var(--forest);font-size:11px;text-decoration:none;margin-top:5px;display:inline-block">Xem chi tiết <i class="fas fa-arrow-right"></i></a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

</main>

<!-- Toast container -->
<div class="kds-toast-wrap" id="toastWrap"></div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
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

setInterval(function() {
  elapsed++;
  var ratio    = elapsed / REFRESH_SEC;
  var offset   = circumference * (1 - ratio);
  if (ring) ring.style.strokeDashoffset = offset;
  if (elapsed >= REFRESH_SEC) window.location.reload();
}, 1000);

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
</script>
</body>
</html>