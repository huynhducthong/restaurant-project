<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: public/login.php');
    exit;
}
$db  = (new Database())->getConnection();
$type = $_GET['type'] ?? 'table';
$svc  = [
    'table'    => ['title' => 'Đặt Bàn', 'sub' => 'Trải nghiệm ẩm thực đỉnh cao', 'icon' => 'table'],
    'birthday' => ['title' => 'Kỷ Niệm', 'sub' => 'Không gian riêng tư, đặc biệt', 'icon' => 'birthday'],
    'chef'     => ['title' => 'Đầu Bếp Riêng', 'sub' => 'Phục vụ tận bàn theo yêu cầu', 'icon' => 'chef'],
];
$cfg   = $svc[$type] ?? $svc['table'];
$combos = $db->query(
    "SELECT c.*, GROUP_CONCAT(f.name SEPARATOR '|') as list_foods
     FROM combos c LEFT JOIN combo_items ci ON c.id=ci.combo_id
     LEFT JOIN foods f ON ci.food_id=f.id WHERE c.status=1 GROUP BY c.id"
)->fetchAll(PDO::FETCH_ASSOC);
$t_open = $db->query("SELECT * FROM restaurant_tables WHERE category='open' ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$t_room = $db->query("SELECT * FROM restaurant_tables WHERE category='room'  ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$foods  = $db->query("SELECT * FROM foods WHERE status=1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

include 'views/client/layouts/header.php';

$is_success = isset($_GET['success']) && isset($_GET['id']);
$booking = null;
if ($is_success) {
    $stmt = $db->prepare("SELECT s.*, t.table_code FROM service_bookings s LEFT JOIN restaurant_tables t ON s.table_id = t.id WHERE s.id = ?");
    $stmt->execute([$_GET['id']]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;1,400&family=Be+Vietnam+Pro:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
    /* === TOKENS === */
    :root {
        --g0: #0a211e;
        --g1: #143B36;
        --g2: #1c4f49;
        --g3: #245e57;
        --gold: #cda45e;
        --gold2: #e8c98a;
        --gold3: #f5e4bc;
        --cream: #f8f4ec;
        --warm: #f0e9d8;
        --ink: #0d1f1c;
        --txt-muted: rgba(13, 31, 28, .5);
        --shadow: 0 4px 24px rgba(13, 31, 28, .06);
        --shadow-lg: 0 16px 48px rgba(13, 31, 28, .1);
        --r: 16px;
        --ease: cubic-bezier(.4, 0, .2, 1);
    }

    /* === PAGE === */
    .bk-page {
        background: var(--cream);
        min-height: 100vh;
        font-family: 'Be Vietnam Pro', sans-serif;
        padding-bottom: 80px
    }

    /* === HERO === */
    .bk-hero {
        position: relative;
        overflow: hidden;
        background: linear-gradient(135deg, var(--g0) 0%, var(--g1) 60%, var(--g2) 100%);
        padding: 110px 0 80px;
        text-align: center;
        color: #fff;
    }

    .bk-hero::before {
        content: '';
        position: absolute;
        inset: 0;
        background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }

    .bk-hero-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(205, 164, 94, .15);
        border: 1px solid rgba(205, 164, 94, .3);
        border-radius: 50px;
        padding: 6px 18px;
        margin-bottom: 22px;
        font-size: 11px;
        letter-spacing: .18em;
        text-transform: uppercase;
        color: var(--gold2);
    }

    .bk-hero h1 {
        font-family: 'Playfair Display', serif;
        font-size: clamp(2.2rem, 5vw, 3.5rem);
        font-weight: 400;
        color: #fff;
        margin: 0 0 10px;
        line-height: 1.15;
    }

    .bk-hero h1 em {
        font-style: italic;
        color: var(--gold);
    }

    .bk-hero-sub {
        color: rgba(255, 255, 255, .55);
        font-size: 15px;
        margin: 0 0 36px;
        font-weight: 300;
    }

    /* service type switcher */
    .svc-tabs {
        display: flex;
        justify-content: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .svc-tab {
        padding: 9px 22px;
        border-radius: 50px;
        border: 1.5px solid rgba(205, 164, 94, .3);
        color: rgba(255, 255, 255, .5);
        text-decoration: none;
        font-size: 13px;
        transition: all .2s var(--ease);
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .svc-tab:hover {
        border-color: var(--gold);
        color: var(--gold2);
    }

    .svc-tab.active {
        background: var(--gold);
        border-color: var(--gold);
        color: var(--g0);
        font-weight: 600;
    }

    /* === MAIN GRID === */
    .bk-wrap {
        max-width: 1160px;
        margin: -48px auto 0;
        padding: 0 20px;
        display: grid;
        grid-template-columns: 1fr 360px;
        gap: 24px;
        position: relative;
        z-index: 1;
    }

    @media(max-width:920px) {
        .bk-wrap {
            grid-template-columns: 1fr;
            margin-top: 0;
            padding-top: 28px;
        }
    }

    /* === SEAMLESS FORM BOX (THAY THẾ CARD2) === */
    .seamless-box {
        background: #fff;
        border-radius: var(--r);
        box-shadow: var(--shadow);
        padding: 45px;
        border: 1px solid rgba(20, 59, 54, .05);
    }

    @media(max-width:600px) {
        .seamless-box {
            padding: 25px;
        }
    }

    .section-heading {
        font-family: 'Playfair Display', serif;
        font-size: 1.45rem;
        color: var(--g1);
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 600;
    }

    .section-heading::before {
        content: '';
        width: 4px;
        height: 22px;
        background: var(--gold);
        border-radius: 4px;
    }

    .section-divider {
        border: none;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(20, 59, 54, .1) 15%, rgba(20, 59, 54, .1) 85%, transparent);
        margin: 40px 0;
    }

    .opt-badge {
        font-family: 'Be Vietnam Pro', sans-serif;
        font-size: 10px;
        padding: 3px 10px;
        border-radius: 50px;
        background: rgba(205, 164, 94, .1);
        color: var(--gold);
        font-weight: 500;
        margin-left: auto;
    }

    /* === FORM FIELDS === */
    .fg {
        margin-bottom: 18px;
    }

    .fg:last-child {
        margin-bottom: 0;
    }

    .fl {
        display: block;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: .1em;
        text-transform: uppercase;
        color: var(--g2);
        margin-bottom: 7px;
    }

    .fl .req {
        color: #e53e3e;
    }

    .fi {
        width: 100%;
        padding: 13px 16px;
        border: 1.5px solid #e5dfd5;
        border-radius: 10px;
        font-family: 'Be Vietnam Pro', sans-serif;
        font-size: 14px;
        color: var(--ink);
        background: #faf9f6;
        outline: none;
        transition: border-color .2s, box-shadow .2s, background .2s;
    }

    .fi:focus {
        background: #fff;
        border-color: var(--g2);
        box-shadow: 0 0 0 3px rgba(20, 59, 54, .08);
    }

    .fi::placeholder {
        color: rgba(13, 31, 28, .3);
    }

    .fi option {
        background: #fff;
        color: var(--ink);
    }

    .frow {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    @media(max-width:540px) {
        .frow {
            grid-template-columns: 1fr;
        }
    }

    /* guest counter */
    .gctr {
        display: flex;
        align-items: center;
        border: 1.5px solid #e5dfd5;
        border-radius: 10px;
        overflow: hidden;
        height: 50px;
        background: #faf9f6;
    }

    .gctr-btn {
        width: 50px;
        height: 100%;
        border: none;
        background: rgba(205, 164, 94, .15);
        color: var(--g1);
        font-size: 20px;
        cursor: pointer;
        flex-shrink: 0;
        transition: background .15s;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .gctr-btn:hover {
        background: rgba(205, 164, 94, .3);
    }

    .gctr-val {
        flex: 1;
        text-align: center;
        border: none;
        background: transparent;
        font-size: 18px;
        font-weight: 600;
        font-family: 'Playfair Display', serif;
        color: var(--ink);
        outline: none;
        min-width: 0;
    }

    /* === SEAT MAP BUTTON === */
    .map-open-btn {
        width: 100%;
        padding: 14px;
        border-radius: 10px;
        border: 1.5px dashed rgba(20, 59, 54, .3);
        background: rgba(20, 59, 54, .02);
        color: var(--g2);
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all .2s;
        margin-bottom: 16px;
        font-family: 'Be Vietnam Pro', sans-serif;
    }

    .map-open-btn:hover {
        border-color: var(--g1);
        background: rgba(20, 59, 54, .06);
    }

    .map-open-btn svg {
        width: 18px;
        height: 18px;
        fill: none;
        stroke: currentColor;
        stroke-width: 2;
    }

    /* selected seat pill */
    .seat-pill {
        display: none;
        align-items: center;
        gap: 10px;
        background: rgba(20, 59, 54, .05);
        border: 1px solid rgba(20, 59, 54, .1);
        border-radius: 10px;
        padding: 12px 16px;
        margin-bottom: 16px;
    }

    .seat-pill.show {
        display: flex;
    }

    .seat-pill-badge {
        background: var(--g1);
        color: #fff;
        padding: 4px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 700;
    }

    .seat-pill-price {
        font-size: 13px;
        color: var(--gold);
        font-weight: 600;
    }

    .seat-pill-clear {
        margin-left: auto;
        background: none;
        border: none;
        color: rgba(13, 31, 28, .35);
        cursor: pointer;
        font-size: 18px;
        line-height: 1;
    }

    .seat-pill-clear:hover {
        color: #e53e3e;
    }

    /* === COMBO GRID === */
    .combo-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 14px;
    }

    .cc {
        border: 1.5px solid #e5dfd5;
        border-radius: 12px;
        padding: 18px;
        cursor: pointer;
        transition: all .22s var(--ease);
        position: relative;
        overflow: hidden;
        background: #faf9f6;
    }

    .cc::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(20, 59, 54, .05), transparent);
        opacity: 0;
        transition: opacity .2s;
    }

    .cc:hover {
        border-color: rgba(20, 59, 54, .2);
        background: #fff;
    }

    .cc:hover::before {
        opacity: 1;
    }

    .cc.active {
        border-color: var(--g1);
        background: rgba(20, 59, 54, .04);
    }

    .cc.active::before {
        opacity: 1;
    }

    .cc-check {
        position: absolute;
        top: 12px;
        right: 12px;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: var(--g1);
        color: #fff;
        font-size: 11px;
        font-weight: 700;
        display: none;
        align-items: center;
        justify-content: center;
    }

    .cc.active .cc-check {
        display: flex;
    }

    .cc-name {
        font-size: 14px;
        font-weight: 600;
        color: var(--ink);
        margin: 0 0 6px;
        line-height: 1.3;
    }

    .cc-price {
        font-size: 16px;
        font-weight: 700;
        color: var(--g1);
    }

    .cc-foods {
        font-size: 12px;
        color: var(--txt-muted);
        margin-top: 8px;
        line-height: 1.5;
    }

    /* === MENU LIST === */
    .menu-scroll {
        max-height: 280px;
        overflow-y: auto;
        padding-right: 6px;
    }

    .menu-scroll::-webkit-scrollbar {
        width: 4px;
    }

    .menu-scroll::-webkit-scrollbar-thumb {
        background: rgba(20, 59, 54, .15);
        border-radius: 4px;
    }

    .mrow {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 10px;
        border-bottom: 1px dashed rgba(13, 31, 28, .08);
        transition: background .2s;
        border-radius: 8px;
    }

    .mrow:hover {
        background: rgba(20, 59, 54, .02);
    }

    .mrow:last-child {
        border: none;
    }

    .mrow-cb {
        width: 18px;
        height: 18px;
        flex-shrink: 0;
        accent-color: var(--g1);
        cursor: pointer;
    }

    .mrow-name {
        flex: 1;
        font-size: 14px;
        color: var(--ink);
    }

    .mrow-price {
        font-size: 13px;
        color: var(--g1);
        font-weight: 600;
        white-space: nowrap;
    }

    .mrow-qty {
        width: 56px;
        height: 36px;
        text-align: center;
        border: 1.5px solid #e5dfd5;
        border-radius: 8px;
        font-size: 13px;
        color: var(--ink);
        padding: 0;
        outline: none;
        display: none;
        background: #fff;
        font-family: 'Be Vietnam Pro', sans-serif;
    }

    .mrow-qty:focus {
        border-color: var(--gold);
    }

    .mrow.checked .mrow-qty {
        display: block;
    }

    /* === SIDEBAR === */
    .sidebar-wrap {
        position: sticky;
        top: 24px;
    }

    .sb-card {
        background: var(--g0);
        border-radius: var(--r);
        border: 1px solid rgba(205, 164, 94, .2);
        overflow: hidden;
        box-shadow: var(--shadow-lg);
    }

    .sb-head {
        background: var(--g1);
        padding: 24px;
        border-bottom: 1px solid rgba(205, 164, 94, .15);
    }

    .sb-head h5 {
        font-family: 'Playfair Display', serif;
        font-size: 1.15rem;
        font-weight: 400;
        color: #fff;
        margin: 0;
        letter-spacing: .03em;
    }

    .sb-head p {
        font-size: 13px;
        color: rgba(255, 255, 255, .5);
        margin: 4px 0 0;
    }

    .sb-body {
        padding: 26px 24px;
    }

    .sb-row {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 15px;
        font-size: 14px;
    }

    .sb-row-l {
        color: rgba(255, 255, 255, .5);
        flex: 1;
        line-height: 1.4;
    }

    .sb-row-r {
        color: rgba(255, 255, 255, .9);
        font-weight: 500;
        white-space: nowrap;
        text-align: right;
    }

    .sb-row-r.hi {
        color: var(--gold);
    }

    .sb-sep {
        height: 1px;
        background: rgba(255, 255, 255, .08);
        margin: 20px 0;
    }

    .deposit-block {
        background: rgba(205, 164, 94, .08);
        border: 1px solid rgba(205, 164, 94, .18);
        border-radius: 12px;
        padding: 20px;
        text-align: center;
    }

    .deposit-label {
        font-size: 11px;
        letter-spacing: .15em;
        text-transform: uppercase;
        color: rgba(205, 164, 94, .7);
    }

    .deposit-num {
        font-family: 'Playfair Display', serif;
        font-size: 2.4rem;
        font-weight: 400;
        color: var(--gold);
        line-height: 1;
        margin: 8px 0 4px;
    }

    .deposit-note {
        font-size: 12px;
        color: rgba(255, 255, 255, .3);
    }

    .btn-go {
        width: 100%;
        margin-top: 20px;
        padding: 16px;
        border-radius: 12px;
        background: linear-gradient(135deg, var(--gold), #b8923e);
        border: none;
        color: var(--g0);
        font-family: 'Be Vietnam Pro', sans-serif;
        font-size: 14px;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        cursor: pointer;
        transition: all .25s var(--ease);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-go:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 28px rgba(205, 164, 94, .35);
    }

    .btn-go:active {
        transform: none;
    }

    .btn-go-note {
        font-size: 12px;
        color: rgba(255, 255, 255, .3);
        text-align: center;
        margin-top: 12px;
    }

    /* === MAP MODAL === */
    .modal-content {
        background: var(--g0) !important;
        border: 1px solid rgba(205, 164, 94, .25) !important;
        color: #fff !important;
        border-radius: 18px !important;
        overflow: hidden;
    }

    .modal-header {
        border-bottom: 1px solid rgba(205, 164, 94, .2) !important;
        padding: 18px 24px !important;
    }

    .modal-title {
        font-family: 'Playfair Display', serif;
        font-size: 1.1rem;
        font-weight: 400;
    }

    .btn-close {
        filter: brightness(0) invert(.5) !important;
    }

    .map-legend {
        display: flex;
        gap: 18px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .legend-item {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        color: rgba(255, 255, 255, .55);
    }

    .leg-dot {
        width: 11px;
        height: 11px;
        border-radius: 3px;
    }

    .map-zone-label {
        font-size: 10px;
        letter-spacing: .15em;
        text-transform: uppercase;
        color: rgba(205, 164, 94, .7);
        margin-bottom: 12px;
    }

    .seats-g {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 8px;
    }

    .seats-g.vip {
        grid-template-columns: repeat(2, 1fr);
    }

    .mseat {
        border-radius: 9px;
        padding: 10px 6px;
        text-align: center;
        cursor: pointer;
        transition: all .18s;
        border: 1.5px solid transparent;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 3px;
    }

    .mseat.available {
        background: rgba(16, 185, 129, .12);
        border-color: rgba(16, 185, 129, .35);
        color: #34d399;
    }

    .mseat.available:hover {
        background: rgba(16, 185, 129, .22);
    }

    .mseat.booked {
        background: rgba(239, 68, 68, .08);
        border-color: rgba(239, 68, 68, .2);
        color: rgba(239, 68, 68, .5);
        cursor: not-allowed;
    }

    .mseat.selected {
        background: rgba(205, 164, 94, .2);
        border-color: var(--gold);
        color: var(--gold);
        box-shadow: 0 0 0 3px rgba(205, 164, 94, .12);
    }

    .mseat-code {
        font-size: 12px;
        font-weight: 700;
    }

    .mseat-sub {
        font-size: 9px;
        opacity: .6;
    }

    .mseat-price {
        font-size: 9px;
        margin-top: 2px;
    }

    .map-confirm-btn {
        background: var(--gold) !important;
        color: var(--g0) !important;
        font-weight: 700 !important;
        border: none !important;
        border-radius: 8px !important;
        padding: 10px 28px !important;
    }

    .modal-footer {
        border-top: 1px solid rgba(205, 164, 94, .2) !important;
        padding: 14px 24px !important;
        gap: 10px !important;
    }

    .modal-hint {
        font-size: 13px;
        color: rgba(255, 255, 255, .35);
        flex: 1;
    }
</style>

<div class="bk-page" style="background: #fdfbf7;">
    <?php if ($is_success && $booking): ?>
    <section class="bk-hero" style="padding: 140px 0 100px; background: #fdfbf7;">
        <div class="container" style="position:relative;z-index:1">
            <div class="success-icon-wrap" style="margin-bottom: 30px; animation: fadeInUp 0.8s var(--ease);">
                <div style="width: 80px; height: 80px; background: rgba(205, 164, 94, 0.1); border: 2px solid var(--gold); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto; color: var(--gold); font-size: 32px; box-shadow: 0 0 30px rgba(205, 164, 94, 0.2);">
                    <i class="fas fa-check"></i>
                </div>
            </div>
            <div class="bk-hero-eyebrow" style="animation: fadeInUp 0.8s var(--ease) 0.1s both; color: var(--gold); border-color: rgba(205,164,94,0.3); background: transparent;">
                Đặt chỗ thành công
            </div>
            <h1 style="animation: fadeInUp 0.8s var(--ease) 0.2s both; color: #111; font-weight: 600;">
                Cảm ơn <em><?= htmlspecialchars($booking['customer_name']) ?></em>!<br>
                Yêu cầu của bạn đã được ghi nhận
            </h1>
            <p class="bk-hero-sub" style="max-width: 600px; margin: 0 auto 40px; animation: fadeInUp 0.8s var(--ease) 0.3s both; color: #555;">
                Mã số đặt chỗ: <span style="color: var(--gold); font-weight: 700;">#SVR-<?= htmlspecialchars($booking['id']) ?></span>. 
                Đội ngũ chúng tôi sẽ gọi điện xác nhận cho bạn qua số <span style="color: #111; font-weight: 600;"><?= htmlspecialchars($booking['customer_phone']) ?></span> sớm nhất.
            </p>
            <div class="svc-tabs" style="gap: 15px; justify-content: center; animation: fadeInUp 0.8s var(--ease) 0.4s both;">
                <a href="admin/export_pdf.php?id=<?= $booking['id'] ?>" class="btn-go" style="width: auto; padding: 14px 40px; margin-top: 0; background: #111; color: var(--gold); border: 1px solid var(--gold); border-radius: 0; font-family:'Cormorant Garamond',serif; font-size:16px;">
                    <i class="fas fa-file-pdf me-2"></i> Tải Phiếu Xác Nhận
                </a>
                <a href="index.php" class="svc-tab" style="padding: 14px 40px; border-radius: 0; border: 1px solid #ddd; color: #111; background: transparent; font-family:'Cormorant Garamond',serif; font-size:16px;">
                    Về trang chủ
                </a>
            </div>
        </div>
    </section>

    <style>
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>

    <div class="bk-wrap" style="margin-top: -60px; grid-template-columns: 1fr; max-width: 850px; animation: fadeInUp 1s var(--ease) 0.5s both;">
        <div class="seamless-box" style="padding: 50px; border: 1px solid rgba(212, 176, 106, 0.2); background: #fff url('https://www.transparenttextures.com/patterns/cubes.png');">
            <div style="text-align: center; margin-bottom: 40px;">
                <h3 class="section-heading" style="justify-content: center; font-size: 1.8rem; margin-bottom: 10px;">Chi tiết đặt dịch vụ</h3>
                <div style="width: 50px; height: 2px; background: var(--gold); margin: 0 auto;"></div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 40px; margin-bottom: 40px;">
                <div style="padding: 20px; background: #faf9f6; border-radius: 12px; border-left: 3px solid var(--gold);">
                    <label class="fl" style="color: var(--txt-muted); font-size: 10px; letter-spacing: 2px;">DỊCH VỤ</label>
                    <div style="font-size: 1.1rem; font-weight: 600; color: var(--g1);"><?= htmlspecialchars(strtoupper($booking['service_type'])) ?></div>
                </div>
                <div style="padding: 20px; background: #faf9f6; border-radius: 12px; border-left: 3px solid var(--gold);">
                    <label class="fl" style="color: var(--txt-muted); font-size: 10px; letter-spacing: 2px;">THỜI GIAN</label>
                    <div style="font-size: 1.1rem; font-weight: 600; color: var(--g1);"><?= date('H:i · d/m/Y', strtotime($booking['booking_date'])) ?></div>
                </div>
                <div style="padding: 20px; background: #faf9f6; border-radius: 12px; border-left: 3px solid var(--gold);">
                    <label class="fl" style="color: var(--txt-muted); font-size: 10px; letter-spacing: 2px;">SỐ KHÁCH</label>
                    <div style="font-size: 1.1rem; font-weight: 600; color: var(--g1);"><?= htmlspecialchars($booking['guests']) ?> NGƯỜI</div>
                </div>
                <?php if ($booking['table_code']): ?>
                <div style="padding: 20px; background: #faf9f6; border-radius: 12px; border-left: 3px solid var(--gold);">
                    <label class="fl" style="color: var(--txt-muted); font-size: 10px; letter-spacing: 2px;">VỊ TRÍ</label>
                    <div style="font-size: 1.1rem; font-weight: 600; color: var(--gold);"><?= htmlspecialchars($booking['table_code']) ?></div>
                </div>
                <?php endif; ?>
            </div>

            <?php 
                $has_bespoke = !empty($booking['has_candle']) || !empty($booking['has_handwritten_card']) || !empty($booking['has_flower']) || !empty($booking['event_type']) || !empty($booking['music_playlist']);
                if ($has_bespoke): 
            ?>
            <div style="margin-bottom: 40px;">
                <h4 style="font-family: 'Playfair Display', serif; font-size: 1.3rem; color: var(--g1); margin-bottom: 15px;"><i class="fas fa-magic me-2" style="color:var(--gold);"></i> Trải Nghiệm Cá Nhân Hóa (Bespoke)</h4>
                <div style="background: rgba(205, 164, 94, 0.05); border: 1px solid rgba(205, 164, 94, 0.2); border-radius: 12px; padding: 25px;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                        <?php if (!empty($booking['event_type'])): ?>
                            <div>
                                <span style="font-size: 11px; color: var(--txt-muted); text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 5px;">Dịp Đặc Biệt</span>
                                <span style="font-size: 14px; font-weight: 600; color: var(--g1);"><?= htmlspecialchars($booking['event_type']) ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($booking['has_candle'])): ?>
                            <div>
                                <span style="font-size: 11px; color: var(--txt-muted); text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 5px;">Trang Trí</span>
                                <span style="font-size: 14px; font-weight: 600; color: var(--g1);">🕯 Nến thơm</span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($booking['has_handwritten_card'])): ?>
                            <div>
                                <span style="font-size: 11px; color: var(--txt-muted); text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 5px;">Thiệp Viết Tay</span>
                                <span style="font-size: 14px; font-weight: 600; color: var(--g1);">✉️ <?= htmlspecialchars($booking['card_message'] ?: 'Chúc mừng') ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($booking['has_flower'])): ?>
                            <div>
                                <span style="font-size: 11px; color: var(--txt-muted); text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 5px;">Hoa Tươi Thiết Kế</span>
                                <span style="font-size: 14px; font-weight: 600; color: var(--g1);">💐 <?= htmlspecialchars($booking['flower_preference'] ?: 'Hoa theo mùa') ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($booking['music_playlist']) || !empty($booking['light_tone'])): ?>
                            <div>
                                <span style="font-size: 11px; color: var(--txt-muted); text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 5px;">Cấu Hình Phòng VIP</span>
                                <span style="font-size: 14px; font-weight: 600; color: var(--g1);">
                                    <?= htmlspecialchars($booking['music_playlist'] ?: 'Không nhạc') ?> 
                                    <?= !empty($booking['light_tone']) ? ' · ' . htmlspecialchars($booking['light_tone']) : '' ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div style="background: var(--g0); color: #fff; padding: 25px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: rgba(255,255,255,0.5);">Tiền tạm tính</div>
                    <div style="font-size: 1.4rem; font-weight: 600; color: var(--gold);"><?= number_format($booking['total_amount']) ?>đ</div>
                </div>
                <div style="text-align: right;">
                    <?php if ($booking['status'] === 'Confirmed'): ?>
                        <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #34d399;"><i class="fas fa-check-circle me-1"></i>Đã thanh toán cọc</div>
                        <div style="font-size: 1.4rem; font-weight: 600; color: #34d399;"><?= number_format($booking['deposit_amount']) ?>đ</div>
                    <?php else: ?>
                        <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: rgba(255,255,255,0.5);">Tiền cọc cần thanh toán</div>
                        <div style="font-size: 1.4rem; font-weight: 600; color: #e53e3e;"><?= number_format($booking['deposit_amount']) ?>đ</div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div style="margin-top: 40px; padding-top: 30px; border-top: 1px dashed #e5dfd5; text-align: center;">
                <?php if ($booking['status'] === 'Confirmed'): ?>
                    <p style="font-size: 14px; color: var(--g1); font-weight: 600; line-height: 1.6;">
                        <i class="fas fa-glass-cheers me-2" style="color:var(--gold);"></i> Đơn đặt bàn đã được xác nhận thanh toán thành công.<br>
                        Hẹn gặp lại quý khách tại <strong>Restaurantly</strong>.
                    </p>
                <?php else: ?>
                    <p style="font-size: 13px; color: var(--txt-muted); line-height: 1.6;">
                        Quý khách vui lòng kiểm tra email hoặc điện thoại để nhận thông tin thanh toán.<br>
                        Cảm ơn quý khách đã tin tưởng lựa chọn <strong>Restaurantly</strong>.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php else: ?>
    <section class="bk-hero">
        <div class="container" style="position:relative;z-index:1">
            <div class="bk-hero-eyebrow">
                <?php
                $icons = ['table' => '🍽', 'birthday' => '🎂', 'chef' => '👨‍🍳'];
                echo $icons[$type] ?? '🍽';
                ?> Đặt dịch vụ online
            </div>
            <h1>
                <em><?= htmlspecialchars($cfg['title']) ?></em><br>
                tại Restaurantly
            </h1>
            <p class="bk-hero-sub"><?= htmlspecialchars($cfg['sub']) ?></p>
            <div class="svc-tabs">
                <a href="?type=table" class="svc-tab <?= $type === 'table' ? 'active' : '' ?>">🍽 Đặt Bàn</a>
                <a href="?type=birthday" class="svc-tab <?= $type === 'birthday' ? 'active' : '' ?>">🎂 Kỷ Niệm</a>
                <a href="?type=chef" class="svc-tab <?= $type === 'chef' ? 'active' : '' ?>">👨‍🍳 Đầu Bếp</a>
            </div>
        </div>
    </section>

    <div class="bk-wrap" id="main">
        <div>
            <form method="POST" action="config/process_service_booking.php" id="bk-form">
                <input type="hidden" name="service_type" value="<?= htmlspecialchars($type) ?>">
                <input type="hidden" name="selected_combo_id" id="sid" value="0">
                <input type="hidden" name="table_id" id="tid" value="">

                <div class="seamless-box">

                    <div>
                        <h3 class="section-heading">Thông tin liên hệ</h3>
                        <div class="frow">
                            <div class="fg">
                                <label class="fl">Họ và tên <span class="req">*</span></label>
                                <input type="text" name="customer_name" class="fi"
                                    value="<?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>"
                                    required placeholder="Nguyễn Văn A" oninput="us()">
                            </div>
                            <div class="fg">
                                <label class="fl">Số điện thoại <span class="req">*</span></label>
                                <input type="tel" name="customer_phone" class="fi" required
                                    placeholder="09xx xxx xxx" oninput="us()">
                            </div>
                        </div>
                        <div class="frow">
                            <div class="fg">
                                <label class="fl">Ngày & Giờ đến <span class="req">*</span></label>
                                <input type="datetime-local" name="booking_date" id="bd" class="fi" required onchange="us()">
                            </div>
                            <div class="fg">
                                <label class="fl">Số lượng khách</label>
                                <div class="gctr">
                                    <button type="button" class="gctr-btn" onclick="cg(-1)">−</button>
                                    <input type="number" name="guests" id="gi" class="gctr-val"
                                        value="2" min="1" max="50" onchange="us()">
                                    <button type="button" class="gctr-btn" onclick="cg(1)">+</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="section-divider">

                    <div>
                        <h3 class="section-heading">Vị trí & Chỗ ngồi</h3>
                        <button type="button" class="map-open-btn" data-bs-toggle="modal" data-bs-target="#mapModal">
                            <svg viewBox="0 0 24 24">
                                <path d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                            </svg>
                            Xem sơ đồ nhà hàng & chọn bàn
                        </button>

                        <div class="seat-pill" id="sp">
                            <span class="seat-pill-badge" id="sp-code"></span>
                            <span class="seat-pill-price" id="sp-price"></span>
                            <span style="font-size:12px;color:var(--txt-muted)">đã chọn</span>
                            <button type="button" class="seat-pill-clear" onclick="clrSeat()">✕</button>
                        </div>

                        <select id="tsel" class="fi" onchange="fromDrop(this)">
                            <option value="" data-price="0">— hoặc chọn nhanh từ danh sách —</option>
                            <optgroup label="Sảnh chính">
                                <?php foreach ($t_open as $t): ?>
                                    <option value="<?= $t['id'] ?>" data-price="<?= $t['price'] ?>" data-code="Bàn <?= htmlspecialchars($t['table_code']) ?>" <?= !$t['is_available'] ? 'disabled' : '' ?>>
                                        Bàn <?= htmlspecialchars($t['table_code']) ?> — <?= number_format($t['price']) ?>đ <?= !$t['is_available'] ? '(Đã đặt)' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="Phòng VIP">
                                <?php foreach ($t_room as $r): ?>
                                    <option value="<?= $r['id'] ?>" data-price="<?= $r['price'] ?>" data-code="Phòng <?= htmlspecialchars($r['table_code']) ?>" <?= !$r['is_available'] ? 'disabled' : '' ?>>
                                        Phòng <?= htmlspecialchars($r['table_code']) ?> — <?= number_format($r['price']) ?>đ <?= !$r['is_available'] ? '(Đã đặt)' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        </select>
                    </div>

                    <hr class="section-divider">

                    <div>
                        <h3 class="section-heading">Gói Combo ưu đãi <span class="opt-badge">Tùy chọn</span></h3>
                        <div class="combo-grid">
                            <div class="cc active" data-price="0" onclick="selCombo(0,this)">
                                <div class="cc-check">✓</div>
                                <div class="cc-name">Không dùng combo</div>
                                <div class="cc-price" style="color:var(--txt-muted);font-size:13px;font-weight:400">Gọi món tự do</div>
                            </div>
                            <?php foreach ($combos as $cb): ?>
                                <div class="cc" data-price="<?= (float)$cb['price'] ?>" onclick="selCombo(<?= $cb['id'] ?>,this)">
                                    <div class="cc-check">✓</div>
                                    <div class="cc-name"><?= htmlspecialchars($cb['name']) ?></div>
                                    <div class="cc-price"><?= number_format($cb['price']) ?>đ</div>
                                    <?php if (!empty($cb['list_foods'])): ?>
                                        <div class="cc-foods"><?= htmlspecialchars(str_replace('|', ' · ', $cb['list_foods'])) ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php if (!empty($foods)): ?>
                        <hr class="section-divider">
                        <div>
                            <h3 class="section-heading">Thực đơn chọn trước <span class="opt-badge">Tùy chọn</span></h3>
                            <div class="menu-scroll">
                                <?php foreach ($foods as $fd): ?>
                                    <div class="mrow" id="mr<?= $fd['id'] ?>">
                                        <input type="checkbox" class="mrow-cb"
                                            name="menu_items[]" value="<?= $fd['id'] ?>"
                                            onchange="togMrow(this,<?= $fd['id'] ?>,<?= (float)$fd['price'] ?>)">
                                        <span class="mrow-name"><?= htmlspecialchars($fd['name']) ?></span>
                                        <span class="mrow-price"><?= number_format($fd['price']) ?>đ</span>
                                        <input type="number" class="mrow-qty fi" style="width:56px;margin-bottom:0;padding:6px 8px;"
                                            name="quantity[<?= $fd['id'] ?>]" id="q<?= $fd['id'] ?>"
                                            value="1" min="1" onchange="us()">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <hr class="section-divider">

                    <div>
                        <h3 class="section-heading">Yêu cầu bổ sung <span class="opt-badge">Tùy chọn</span></h3>
                        <textarea name="message" class="fi" rows="3" style="resize:vertical;line-height:1.6"
                            placeholder="Dị ứng thực phẩm, trang trí sinh nhật, yêu cầu đặc biệt..."></textarea>
                    </div>

                </div>
            </form>
        </div>

        <div>
            <div class="sidebar-wrap">
                <div class="sb-card">
                    <div class="sb-head">
                        <h5>Tóm tắt đặt chỗ</h5>
                        <p><?= htmlspecialchars($cfg['title'] . ' · ' . $cfg['sub']) ?></p>
                    </div>
                    <div class="sb-body">
                        <div class="sb-row"><span class="sb-row-l">Khách hàng</span><span class="sb-row-r" id="sn">—</span></div>
                        <div class="sb-row"><span class="sb-row-l">Ngày & Giờ</span><span class="sb-row-r" id="sd">—</span></div>
                        <div class="sb-row"><span class="sb-row-l">Số khách</span><span class="sb-row-r" id="sg">2 người</span></div>
                        <div class="sb-row"><span class="sb-row-l">Vị trí</span><span class="sb-row-r hi" id="ss">Chưa chọn</span></div>
                        <div class="sb-sep"></div>
                        <div class="sb-row"><span class="sb-row-l">Phí vị trí</span><span class="sb-row-r" id="sp2">0đ</span></div>
                        <div class="sb-row"><span class="sb-row-l">Combo / Món</span><span class="sb-row-r" id="sm">0đ</span></div>
                        <div class="sb-sep"></div>
                        <div class="deposit-block">
                            <div class="deposit-label">Tiền cọc trước (30%)</div>
                            <div class="deposit-num" id="sdep">0đ</div>
                            <div class="deposit-note">Thanh toán phần còn lại tại nhà hàng</div>
                        </div>
                        <button type="submit" form="bk-form" class="btn-go" id="btn-go">
                            <span id="btn-txt">Gửi yêu cầu đặt chỗ</span>
                            <span id="btn-spin" style="display:none"><span class="spinner-border spinner-border-sm"></span></span>
                        </button>
                        <p class="btn-go-note">Chúng tôi xác nhận trong vòng 30 phút</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="mapModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Sơ đồ vị trí nhà hàng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:24px">
                <div class="map-legend">
                    <div class="legend-item">
                        <div class="leg-dot" style="background:rgba(16,185,129,.4)"></div>Còn trống
                    </div>
                    <div class="legend-item">
                        <div class="leg-dot" style="background:rgba(239,68,68,.3)"></div>Đã đặt
                    </div>
                    <div class="legend-item">
                        <div class="leg-dot" style="background:rgba(205,164,94,.4)"></div>Đang chọn
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1.5fr 1px 1fr;gap:0;align-items:start">
                    <div>
                        <div class="map-zone-label">Sảnh chính — tối đa 6 khách/bàn</div>
                        <div class="seats-g" id="sg-open">
                            <?php foreach ($t_open as $t):
                                $st = $t['is_available'] ? 'available' : 'booked'; ?>
                                <div class="mseat <?= $st ?>"
                                    data-id="<?= $t['id'] ?>" data-price="<?= $t['price'] ?>"
                                    data-code="Bàn <?= htmlspecialchars($t['table_code']) ?>">
                                    <span class="mseat-code"><?= htmlspecialchars($t['table_code']) ?></span>
                                    <span class="mseat-sub">≤6 khách</span>
                                    <span class="mseat-price"><?= number_format($t['price']) ?>đ</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div style="background:rgba(205,164,94,.15);min-height:200px;margin:0 20px"></div>
                    <div>
                        <div class="map-zone-label">Phòng VIP — tối đa 16 khách</div>
                        <div class="seats-g vip" id="sg-vip">
                            <?php foreach ($t_room as $r):
                                $st = $r['is_available'] ? 'available' : 'booked'; ?>
                                <div class="mseat <?= $st ?>"
                                    style="min-height:80px"
                                    data-id="<?= $r['id'] ?>" data-price="<?= $r['price'] ?>"
                                    data-code="Phòng <?= htmlspecialchars($r['table_code']) ?>">
                                    <span class="mseat-code"><?= htmlspecialchars($r['table_code']) ?></span>
                                    <span class="mseat-sub">≤16 khách</span>
                                    <span class="mseat-price"><?= number_format($r['price']) ?>đ</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <span class="modal-hint" id="mhint">Chưa chọn vị trí nào</span>
                <button type="button" class="btn btn-secondary"
                    style="background:rgba(255,255,255,.08);border-color:rgba(255,255,255,.12);color:#fff;border-radius:8px"
                    data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="map-confirm-btn btn" id="mapConfirm" data-bs-dismiss="modal">
                    Xác nhận vị trí
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    /* STATE */
    var selId = 0,
        selCode = '',
        selPrice = 0,
        selComboPr = 0,
        menuPr = {};

    /* GUEST COUNTER */
    function cg(d) {
        var i = document.getElementById('gi');
        i.value = Math.max(1, Math.min(50, parseInt(i.value || 2) + d));
        us();
    }

    /* COMBO SELECT */
    function selCombo(id, el) {
        document.querySelectorAll('.cc').forEach(function(c) {
            c.classList.remove('active');
        });
        el.classList.add('active');
        document.getElementById('sid').value = id;
        selComboPr = parseFloat(el.dataset.price || 0);
        us();
    }

    /* MENU TOGGLE */
    function togMrow(cb, id, pr) {
        var row = document.getElementById('mr' + id);
        if (cb.checked) {
            row.classList.add('checked');
            menuPr[id] = pr;
        } else {
            row.classList.remove('checked');
            delete menuPr[id];
        }
        us();
    }

    /* SEAT FROM MAP */
    document.querySelectorAll('.mseat.available').forEach(function(s) {
        s.addEventListener('click', function() {
            document.querySelectorAll('.mseat').forEach(function(x) {
                x.classList.remove('selected');
            });
            s.classList.add('selected');
            selId = s.dataset.id;
            selCode = s.dataset.code;
            selPrice = parseFloat(s.dataset.price || 0);
            var h = document.getElementById('mhint');
            if (h) h.textContent = '✓ Đã chọn: ' + selCode + ' · ' + selPrice.toLocaleString('vi-VN') + 'đ';
        });
    });

    /* CONFIRM FROM MAP MODAL */
    document.getElementById('mapConfirm').addEventListener('click', function() {
        if (!selId) {
            return;
        }
        applyseat();
    });

    /* SEAT FROM DROPDOWN */
    function fromDrop(sel) {
        var opt = sel.options[sel.selectedIndex];
        selId = sel.value;
        selPrice = parseFloat(opt.dataset.price || 0);
        selCode = opt.dataset.code || opt.text.split('—')[0].trim();
        document.getElementById('tid').value = selId;
        document.querySelectorAll('.mseat').forEach(function(x) {
            x.classList.remove('selected');
        });
        var m = document.querySelector('.mseat[data-id="' + selId + '"]');
        if (m) m.classList.add('selected');
        showPill();
        us();
    }

    function applyseat() {
        document.getElementById('tid').value = selId;
        document.getElementById('tsel').value = selId;
        showPill();
        us();
    }

    function showPill() {
        if (!selId) {
            return;
        }
        var p = document.getElementById('sp');
        p.classList.add('show');
        document.getElementById('sp-code').textContent = selCode;
        document.getElementById('sp-price').textContent = selPrice.toLocaleString('vi-VN') + 'đ';
    }

    function clrSeat() {
        selId = '';
        selCode = '';
        selPrice = 0;
        document.getElementById('tid').value = '';
        document.getElementById('tsel').value = '';
        document.getElementById('sp').classList.remove('show');
        document.querySelectorAll('.mseat').forEach(function(x) {
            x.classList.remove('selected');
        });
        us();
    }

    /* UPDATE SUMMARY */
    function us() {
        var n = document.querySelector('[name="customer_name"]');
        document.getElementById('sn').textContent = n && n.value ? n.value : '—';

        var d = document.getElementById('bd');
        if (d && d.value) {
            var dt = new Date(d.value);
            document.getElementById('sd').textContent =
                dt.toLocaleDateString('vi-VN', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric'
                }) +
                ' · ' + dt.toLocaleTimeString('vi-VN', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
        } else document.getElementById('sd').textContent = '—';

        document.getElementById('sg').textContent = document.getElementById('gi').value + ' người';
        document.getElementById('ss').textContent = selCode || 'Chưa chọn';
        document.getElementById('sp2').textContent = selPrice.toLocaleString('vi-VN') + 'đ';

        var mt = 0;
        for (var id in menuPr) {
            var qe = document.getElementById('q' + id);
            mt += menuPr[id] * (qe ? parseInt(qe.value) || 1 : 1);
        }
        var food = selComboPr > 0 ? selComboPr : mt;
        document.getElementById('sm').textContent = food.toLocaleString('vi-VN') + 'đ';
        document.getElementById('sdep').textContent =
            Math.ceil((selPrice + food) * .3).toLocaleString('vi-VN') + 'đ';
    }

    /* SUBMIT */
    document.getElementById('bk-form').addEventListener('submit', function() {
        var b = document.getElementById('btn-go');
        b.disabled = true;
        document.getElementById('btn-txt').style.display = 'none';
        document.getElementById('btn-spin').style.display = 'block';
    });

    /* INIT: set datetime min = now+2h */
    (function() {
        var inp = document.getElementById('bd');
        var now = new Date();
        now.setHours(now.getHours() + 2);
        if (inp) inp.min = now.toISOString().slice(0, 16);
        us();
    })();
</script>
    <?php endif; ?>
</div>

<?php include 'views/client/layouts/footer.php'; ?>