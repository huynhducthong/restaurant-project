<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'config/database.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: public/login.php'); exit;
}
$db  = (new Database())->getConnection();
$type = $_GET['type'] ?? 'table';
$svc  = [
    'table'    => ['title'=>'Đặt Bàn','sub'=>'Trải nghiệm ẩm thực đỉnh cao','icon'=>'table'],
    'birthday' => ['title'=>'Kỷ Niệm','sub'=>'Không gian riêng tư, đặc biệt','icon'=>'birthday'],
    'chef'     => ['title'=>'Đầu Bếp Riêng','sub'=>'Phục vụ tận bàn theo yêu cầu','icon'=>'chef'],
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
?>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;1,400&family=Be+Vietnam+Pro:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
/* === TOKENS === */
:root{
  --g0:#0a211e;--g1:#143B36;--g2:#1c4f49;--g3:#245e57;
  --gold:#cda45e;--gold2:#e8c98a;--gold3:#f5e4bc;
  --cream:#f8f4ec;--warm:#f0e9d8;--ink:#0d1f1c;
  --txt-muted:rgba(13,31,28,.5);
  --shadow:0 4px 24px rgba(13,31,28,.1);
  --shadow-lg:0 16px 48px rgba(13,31,28,.14);
  --r:14px;--ease:cubic-bezier(.4,0,.2,1);
}

/* === PAGE === */
.bk-page{background:var(--cream);min-height:100vh;font-family:'Be Vietnam Pro',sans-serif;padding-bottom:80px}

/* === HERO === */
.bk-hero{
  position:relative;overflow:hidden;
  background:linear-gradient(135deg,var(--g0) 0%,var(--g1) 60%,var(--g2) 100%);
  padding:110px 0 80px;text-align:center;color:#fff;
}
.bk-hero::before{
  content:'';position:absolute;inset:0;
  background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}
.bk-hero-eyebrow{
  display:inline-flex;align-items:center;gap:8px;
  background:rgba(205,164,94,.15);border:1px solid rgba(205,164,94,.3);
  border-radius:50px;padding:6px 18px;margin-bottom:22px;
  font-size:11px;letter-spacing:.18em;text-transform:uppercase;color:var(--gold2);
}
.bk-hero h1{
  font-family:'Playfair Display',serif;
  font-size:clamp(2.2rem,5vw,3.5rem);font-weight:400;
  color:#fff;margin:0 0 10px;line-height:1.15;
}
.bk-hero h1 em{font-style:italic;color:var(--gold);}
.bk-hero-sub{color:rgba(255,255,255,.55);font-size:15px;margin:0 0 36px;font-weight:300;}

/* service type switcher */
.svc-tabs{display:flex;justify-content:center;gap:8px;flex-wrap:wrap;}
.svc-tab{
  padding:9px 22px;border-radius:50px;border:1.5px solid rgba(205,164,94,.3);
  color:rgba(255,255,255,.5);text-decoration:none;font-size:13px;
  transition:all .2s var(--ease);display:flex;align-items:center;gap:6px;
}
.svc-tab:hover{border-color:var(--gold);color:var(--gold2);}
.svc-tab.active{background:var(--gold);border-color:var(--gold);color:var(--g0);font-weight:600;}

/* === MAIN GRID === */
.bk-wrap{
  max-width:1160px;margin:-48px auto 0;padding:0 20px;
  display:grid;grid-template-columns:1fr 360px;gap:24px;
  position:relative;z-index:1;
}
@media(max-width:920px){.bk-wrap{grid-template-columns:1fr;margin-top:0;padding-top:28px;}}

/* === STEP BADGE === */
.step-indicator{
  display:flex;align-items:center;gap:12px;margin-bottom:28px;
}
.step-pip{
  display:flex;align-items:center;gap:4px;
  font-size:11px;letter-spacing:.08em;text-transform:uppercase;
  color:var(--txt-muted);font-weight:500;
}
.step-pip-dot{
  width:7px;height:7px;border-radius:50%;
  background:var(--gold);opacity:.35;
  transition:opacity .3s;
}
.step-pip-dot.on{opacity:1;}
.step-pip-label{margin-left:4px;}

/* === CARD === */
.card2{
  background:#fff;border-radius:var(--r);
  box-shadow:var(--shadow);margin-bottom:20px;overflow:hidden;
  border:1px solid rgba(20,59,54,.06);
  transition:box-shadow .25s;
}
.card2:hover{box-shadow:var(--shadow-lg);}
.card2-head{
  display:flex;align-items:center;gap:14px;
  padding:18px 26px;background:var(--g1);color:#fff;
  position:relative;
}
.card2-head::after{
  content:'';position:absolute;bottom:-1px;left:26px;right:26px;
  height:1px;background:rgba(205,164,94,.2);
}
.card2-num{
  width:30px;height:30px;border-radius:8px;
  background:rgba(205,164,94,.2);border:1px solid rgba(205,164,94,.35);
  display:flex;align-items:center;justify-content:center;
  font-size:13px;font-weight:700;color:var(--gold);flex-shrink:0;
}
.card2-title{font-size:13px;font-weight:600;letter-spacing:.04em;color:rgba(255,255,255,.92);}
.card2-badge{
  margin-left:auto;font-size:10px;padding:3px 10px;border-radius:50px;
  background:rgba(205,164,94,.15);color:var(--gold);border:1px solid rgba(205,164,94,.25);
}
.card2-body{padding:26px;}

/* === FORM FIELDS === */
.fg{margin-bottom:18px;}
.fg:last-child{margin-bottom:0;}
.fl{
  display:block;font-size:11px;font-weight:600;
  letter-spacing:.1em;text-transform:uppercase;
  color:var(--g2);margin-bottom:7px;
}
.fl .req{color:#e53e3e;}
.fi{
  width:100%;padding:12px 14px;
  border:1.5px solid #e0d8cc;border-radius:10px;
  font-family:'Be Vietnam Pro',sans-serif;font-size:14px;color:var(--ink);
  background:#fff;outline:none;
  transition:border-color .2s,box-shadow .2s;
}
.fi:focus{border-color:var(--g2);box-shadow:0 0 0 3px rgba(20,59,54,.1);}
.fi::placeholder{color:rgba(13,31,28,.3);}
.fi option{background:#fff;color:var(--ink);}
.frow{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
@media(max-width:540px){.frow{grid-template-columns:1fr;}}

/* guest counter */
.gctr{
  display:flex;align-items:center;
  border:1.5px solid #e0d8cc;border-radius:10px;overflow:hidden;
  height:48px;
}
.gctr-btn{
  width:48px;height:100%;border:none;background:var(--warm);
  color:var(--g1);font-size:20px;cursor:pointer;flex-shrink:0;
  transition:background .15s;display:flex;align-items:center;justify-content:center;
}
.gctr-btn:hover{background:var(--gold3);}
.gctr-val{
  flex:1;text-align:center;border:none;background:transparent;
  font-size:18px;font-weight:600;font-family:'Playfair Display',serif;
  color:var(--ink);outline:none;min-width:0;
}

/* === SEAT MAP BUTTON === */
.map-open-btn{
  width:100%;padding:13px;border-radius:10px;
  border:1.5px dashed rgba(20,59,54,.25);
  background:rgba(20,59,54,.03);color:var(--g2);
  font-size:13px;font-weight:600;cursor:pointer;
  display:flex;align-items:center;justify-content:center;gap:8px;
  transition:all .2s;margin-bottom:14px;
  font-family:'Be Vietnam Pro',sans-serif;
}
.map-open-btn:hover{border-color:var(--g1);background:rgba(20,59,54,.07);}
.map-open-btn svg{width:18px;height:18px;fill:none;stroke:currentColor;stroke-width:2;}

/* selected seat pill */
.seat-pill{
  display:none;align-items:center;gap:10px;
  background:rgba(20,59,54,.06);border:1px solid rgba(20,59,54,.14);
  border-radius:10px;padding:11px 14px;margin-bottom:14px;
}
.seat-pill.show{display:flex;}
.seat-pill-badge{
  background:var(--g1);color:#fff;
  padding:3px 10px;border-radius:6px;font-size:12px;font-weight:700;
}
.seat-pill-price{font-size:12px;color:var(--gold);font-weight:600;}
.seat-pill-clear{
  margin-left:auto;background:none;border:none;
  color:rgba(13,31,28,.35);cursor:pointer;font-size:16px;line-height:1;
}
.seat-pill-clear:hover{color:#e53e3e;}

/* === COMBO GRID === */
.combo-grid{
  display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;
}
.cc{
  border:1.5px solid #e0d8cc;border-radius:12px;padding:16px;
  cursor:pointer;transition:all .22s var(--ease);position:relative;overflow:hidden;
  background:#fff;
}
.cc::before{
  content:'';position:absolute;inset:0;
  background:linear-gradient(135deg,rgba(20,59,54,.05),transparent);
  opacity:0;transition:opacity .2s;
}
.cc:hover{border-color:rgba(20,59,54,.3);}
.cc:hover::before{opacity:1;}
.cc.active{border-color:var(--g1);background:rgba(20,59,54,.04);}
.cc.active::before{opacity:1;}
.cc-check{
  position:absolute;top:10px;right:10px;
  width:20px;height:20px;border-radius:50%;
  background:var(--g1);color:#fff;font-size:10px;font-weight:700;
  display:none;align-items:center;justify-content:center;
}
.cc.active .cc-check{display:flex;}
.cc-name{font-size:13px;font-weight:600;color:var(--ink);margin:0 0 5px;line-height:1.3;}
.cc-price{font-size:15px;font-weight:700;color:var(--g1);}
.cc-foods{font-size:11px;color:var(--txt-muted);margin-top:6px;line-height:1.5;}

/* === MENU LIST === */
.menu-scroll{max-height:260px;overflow-y:auto;padding-right:4px;}
.menu-scroll::-webkit-scrollbar{width:3px;}
.menu-scroll::-webkit-scrollbar-thumb{background:rgba(20,59,54,.15);border-radius:4px;}
.mrow{
  display:flex;align-items:center;gap:10px;
  padding:10px 0;border-bottom:1px solid rgba(13,31,28,.06);
}
.mrow:last-child{border:none;}
.mrow-cb{width:17px;height:17px;flex-shrink:0;accent-color:var(--g1);cursor:pointer;}
.mrow-name{flex:1;font-size:13px;color:var(--ink);}
.mrow-price{font-size:12px;color:var(--g1);font-weight:600;white-space:nowrap;}
.mrow-qty{
  width:52px;height:34px;text-align:center;
  border:1.5px solid #e0d8cc;border-radius:7px;
  font-size:13px;color:var(--ink);padding:0;
  outline:none;display:none;
  font-family:'Be Vietnam Pro',sans-serif;
}
.mrow.checked .mrow-qty{display:block;}

/* === SIDEBAR === */
.sidebar-wrap{position:sticky;top:24px;}
.sb-card{
  background:var(--g0);border-radius:var(--r);
  border:1px solid rgba(205,164,94,.2);overflow:hidden;
}
.sb-head{
  background:var(--g1);padding:20px 24px;
  border-bottom:1px solid rgba(205,164,94,.15);
}
.sb-head h5{
  font-family:'Playfair Display',serif;font-size:1.05rem;
  font-weight:400;color:#fff;margin:0;letter-spacing:.03em;
}
.sb-head p{font-size:12px;color:rgba(255,255,255,.4);margin:3px 0 0;}
.sb-body{padding:22px 24px;}
.sb-row{
  display:flex;align-items:flex-start;justify-content:space-between;
  gap:10px;margin-bottom:13px;font-size:13px;
}
.sb-row-l{color:rgba(255,255,255,.45);flex:1;line-height:1.4;}
.sb-row-r{color:rgba(255,255,255,.85);font-weight:500;white-space:nowrap;text-align:right;}
.sb-row-r.hi{color:var(--gold);}
.sb-sep{height:1px;background:rgba(255,255,255,.08);margin:16px 0;}
.deposit-block{
  background:rgba(205,164,94,.08);border:1px solid rgba(205,164,94,.18);
  border-radius:10px;padding:16px;text-align:center;
}
.deposit-label{font-size:10px;letter-spacing:.15em;text-transform:uppercase;color:rgba(205,164,94,.6);}
.deposit-num{
  font-family:'Playfair Display',serif;font-size:2.2rem;font-weight:400;
  color:var(--gold);line-height:1;margin:6px 0 2px;
}
.deposit-note{font-size:11px;color:rgba(255,255,255,.25);}
.btn-go{
  width:100%;margin-top:18px;padding:15px;border-radius:10px;
  background:linear-gradient(135deg,var(--gold),#b8923e);
  border:none;color:var(--g0);
  font-family:'Be Vietnam Pro',sans-serif;font-size:13px;font-weight:700;
  letter-spacing:.08em;text-transform:uppercase;cursor:pointer;
  transition:all .25s var(--ease);
  display:flex;align-items:center;justify-content:center;gap:8px;
}
.btn-go:hover{transform:translateY(-2px);box-shadow:0 10px 28px rgba(205,164,94,.35);}
.btn-go:active{transform:none;}
.btn-go-note{font-size:11px;color:rgba(255,255,255,.25);text-align:center;margin-top:10px;}

/* === MAP MODAL === */
.modal-content{
  background:var(--g0) !important;
  border:1px solid rgba(205,164,94,.25) !important;
  color:#fff !important;border-radius:18px !important;overflow:hidden;
}
.modal-header{border-bottom:1px solid rgba(205,164,94,.2) !important;padding:18px 24px !important;}
.modal-title{font-family:'Playfair Display',serif;font-size:1.1rem;font-weight:400;}
.btn-close{filter:brightness(0) invert(.5) !important;}
.map-legend{display:flex;gap:18px;margin-bottom:20px;flex-wrap:wrap;}
.legend-item{display:flex;align-items:center;gap:6px;font-size:12px;color:rgba(255,255,255,.55);}
.leg-dot{width:11px;height:11px;border-radius:3px;}
.map-zone-label{
  font-size:10px;letter-spacing:.15em;text-transform:uppercase;
  color:rgba(205,164,94,.7);margin-bottom:12px;
}
.seats-g{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;}
.seats-g.vip{grid-template-columns:repeat(2,1fr);}
.mseat{
  border-radius:9px;padding:10px 6px;
  text-align:center;cursor:pointer;transition:all .18s;
  border:1.5px solid transparent;
  display:flex;flex-direction:column;align-items:center;gap:3px;
}
.mseat.available{
  background:rgba(16,185,129,.12);border-color:rgba(16,185,129,.35);
  color:#34d399;
}
.mseat.available:hover{background:rgba(16,185,129,.22);}
.mseat.booked{
  background:rgba(239,68,68,.08);border-color:rgba(239,68,68,.2);
  color:rgba(239,68,68,.5);cursor:not-allowed;
}
.mseat.selected{
  background:rgba(205,164,94,.2);border-color:var(--gold);
  color:var(--gold);box-shadow:0 0 0 3px rgba(205,164,94,.12);
}
.mseat-code{font-size:12px;font-weight:700;}
.mseat-sub{font-size:9px;opacity:.6;}
.mseat-price{font-size:9px;margin-top:2px;}
.map-confirm-btn{
  background:var(--gold) !important;color:var(--g0) !important;
  font-weight:700 !important;border:none !important;border-radius:8px !important;padding:10px 28px !important;
}
.modal-footer{border-top:1px solid rgba(205,164,94,.2) !important;padding:14px 24px !important;gap:10px !important;}
.modal-hint{font-size:13px;color:rgba(255,255,255,.35);flex:1;}
</style>

<section class="bk-hero">
  <div class="container" style="position:relative;z-index:1">
    <div class="bk-hero-eyebrow">
      <?php
      $icons=['table'=>'🍽','birthday'=>'🎂','chef'=>'👨‍🍳'];
      echo $icons[$type]??'🍽';
      ?> Đặt dịch vụ online
    </div>
    <h1>
      <em><?= htmlspecialchars($cfg['title']) ?></em><br>
      tại Restaurantly
    </h1>
    <p class="bk-hero-sub"><?= htmlspecialchars($cfg['sub']) ?></p>
    <div class="svc-tabs">
      <a href="?type=table"    class="svc-tab <?= $type==='table'?'active':'' ?>">🍽 Đặt Bàn</a>
      <a href="?type=birthday" class="svc-tab <?= $type==='birthday'?'active':'' ?>">🎂 Kỷ Niệm</a>
      <a href="?type=chef"     class="svc-tab <?= $type==='chef'?'active':'' ?>">👨‍🍳 Đầu Bếp</a>
    </div>
  </div>
</section>

<div class="bk-wrap" id="main">
<!-- ========== LEFT FORM ========== -->
<div>
<form method="POST" action="config/process_service_booking.php" id="bk-form">
  <input type="hidden" name="service_type"      value="<?= htmlspecialchars($type) ?>">
  <input type="hidden" name="selected_combo_id" id="sid" value="0">
  <input type="hidden" name="table_id"          id="tid" value="">

  <!-- 1. Thông tin -->
  <div class="card2">
    <div class="card2-head">
      <div class="card2-num">1</div>
      <span class="card2-title">Thông tin liên hệ</span>
    </div>
    <div class="card2-body">
      <div class="frow">
        <div class="fg">
          <label class="fl">Họ và tên <span class="req">*</span></label>
          <input type="text" name="customer_name" class="fi"
                 value="<?= htmlspecialchars($_SESSION['user_name']??'') ?>"
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
  </div>

  <!-- 2. Vị trí -->
  <div class="card2">
    <div class="card2-head">
      <div class="card2-num">2</div>
      <span class="card2-title">Chọn vị trí bàn</span>
    </div>
    <div class="card2-body">
      <button type="button" class="map-open-btn" data-bs-toggle="modal" data-bs-target="#mapModal">
        <svg viewBox="0 0 24 24"><path d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
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
          <?php foreach($t_open as $t): ?>
          <option value="<?= $t['id'] ?>" data-price="<?= $t['price'] ?>" data-code="Bàn <?= htmlspecialchars($t['table_code']) ?>" <?= !$t['is_available']?'disabled':'' ?>>
            Bàn <?= htmlspecialchars($t['table_code']) ?> — <?= number_format($t['price']) ?>đ <?= !$t['is_available']?'(Đã đặt)':'' ?>
          </option>
          <?php endforeach; ?>
        </optgroup>
        <optgroup label="Phòng VIP">
          <?php foreach($t_room as $r): ?>
          <option value="<?= $r['id'] ?>" data-price="<?= $r['price'] ?>" data-code="Phòng <?= htmlspecialchars($r['table_code']) ?>" <?= !$r['is_available']?'disabled':'' ?>>
            Phòng <?= htmlspecialchars($r['table_code']) ?> — <?= number_format($r['price']) ?>đ <?= !$r['is_available']?'(Đã đặt)':'' ?>
          </option>
          <?php endforeach; ?>
        </optgroup>
      </select>
    </div>
  </div>

  <!-- 3. Combo -->
  <div class="card2">
    <div class="card2-head">
      <div class="card2-num">3</div>
      <span class="card2-title">Gói Combo ưu đãi</span>
      <span class="card2-badge">Tùy chọn</span>
    </div>
    <div class="card2-body">
      <div class="combo-grid">
        <div class="cc active" data-price="0" onclick="selCombo(0,this)">
          <div class="cc-check">✓</div>
          <div class="cc-name">Không dùng combo</div>
          <div class="cc-price" style="color:var(--txt-muted);font-size:12px;font-weight:400">Gọi món tự do</div>
        </div>
        <?php foreach($combos as $cb): ?>
        <div class="cc" data-price="<?= (float)$cb['price'] ?>" onclick="selCombo(<?= $cb['id'] ?>,this)">
          <div class="cc-check">✓</div>
          <div class="cc-name"><?= htmlspecialchars($cb['name']) ?></div>
          <div class="cc-price"><?= number_format($cb['price']) ?>đ</div>
          <?php if(!empty($cb['list_foods'])): ?>
          <div class="cc-foods"><?= htmlspecialchars(str_replace('|', ' · ', $cb['list_foods'])) ?></div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- 4. Thực đơn -->
  <?php if(!empty($foods)): ?>
  <div class="card2">
    <div class="card2-head">
      <div class="card2-num">4</div>
      <span class="card2-title">Thực đơn chọn trước</span>
      <span class="card2-badge">Tùy chọn</span>
    </div>
    <div class="card2-body">
      <div class="menu-scroll">
        <?php foreach($foods as $fd): ?>
        <div class="mrow" id="mr<?= $fd['id'] ?>">
          <input type="checkbox" class="mrow-cb"
                 name="menu_items[]" value="<?= $fd['id'] ?>"
                 onchange="togMrow(this,<?= $fd['id'] ?>,<?= (float)$fd['price'] ?>)">
          <span class="mrow-name"><?= htmlspecialchars($fd['name']) ?></span>
          <span class="mrow-price"><?= number_format($fd['price']) ?>đ</span>
          <input type="number" class="mrow-qty fi" style="width:52px;margin-bottom:0;padding:4px 8px;"
                 name="quantity[<?= $fd['id'] ?>]" id="q<?= $fd['id'] ?>"
                 value="1" min="1" onchange="us()">
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- 5. Ghi chú -->
  <div class="card2">
    <div class="card2-head">
      <div class="card2-num">5</div>
      <span class="card2-title">Yêu cầu bổ sung</span>
      <span class="card2-badge">Tùy chọn</span>
    </div>
    <div class="card2-body">
      <textarea name="message" class="fi" rows="3" style="resize:vertical;line-height:1.6"
                placeholder="Dị ứng thực phẩm, trang trí sinh nhật, yêu cầu đặc biệt..."></textarea>
    </div>
  </div>
</form>
</div>

<!-- ========== RIGHT SIDEBAR ========== -->
<div>
<div class="sidebar-wrap">
<div class="sb-card">
  <div class="sb-head">
    <h5>Tóm tắt đặt chỗ</h5>
    <p><?= htmlspecialchars($cfg['title'].' · '.$cfg['sub']) ?></p>
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
</div><!-- .bk-wrap -->

<!-- ========== MAP MODAL ========== -->
<div class="modal fade" id="mapModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Sơ đồ vị trí nhà hàng</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:24px">
        <div class="map-legend">
          <div class="legend-item"><div class="leg-dot" style="background:rgba(16,185,129,.4)"></div>Còn trống</div>
          <div class="legend-item"><div class="leg-dot" style="background:rgba(239,68,68,.3)"></div>Đã đặt</div>
          <div class="legend-item"><div class="leg-dot" style="background:rgba(205,164,94,.4)"></div>Đang chọn</div>
        </div>
        <div style="display:grid;grid-template-columns:1.5fr 1px 1fr;gap:0;align-items:start">
          <div>
            <div class="map-zone-label">Sảnh chính — tối đa 6 khách/bàn</div>
            <div class="seats-g" id="sg-open">
              <?php foreach($t_open as $t):
                $st=$t['is_available']?'available':'booked'; ?>
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
              <?php foreach($t_room as $r):
                $st=$r['is_available']?'available':'booked'; ?>
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
var selId=0,selCode='',selPrice=0,selComboPr=0,menuPr={};

/* GUEST COUNTER */
function cg(d){var i=document.getElementById('gi');i.value=Math.max(1,Math.min(50,parseInt(i.value||2)+d));us();}

/* COMBO SELECT */
function selCombo(id,el){
  document.querySelectorAll('.cc').forEach(function(c){c.classList.remove('active');});
  el.classList.add('active');
  document.getElementById('sid').value=id;
  selComboPr=parseFloat(el.dataset.price||0);
  us();
}

/* MENU TOGGLE */
function togMrow(cb,id,pr){
  var row=document.getElementById('mr'+id);
  if(cb.checked){row.classList.add('checked');menuPr[id]=pr;}
  else{row.classList.remove('checked');delete menuPr[id];}
  us();
}

/* SEAT FROM MAP */
document.querySelectorAll('.mseat.available').forEach(function(s){
  s.addEventListener('click',function(){
    document.querySelectorAll('.mseat').forEach(function(x){x.classList.remove('selected');});
    s.classList.add('selected');
    selId=s.dataset.id; selCode=s.dataset.code; selPrice=parseFloat(s.dataset.price||0);
    var h=document.getElementById('mhint');
    if(h) h.textContent='✓ Đã chọn: '+selCode+' · '+selPrice.toLocaleString('vi-VN')+'đ';
  });
});

/* CONFIRM FROM MAP MODAL */
document.getElementById('mapConfirm').addEventListener('click',function(){
  if(!selId){return;}
  applyseat();
});

/* SEAT FROM DROPDOWN */
function fromDrop(sel){
  var opt=sel.options[sel.selectedIndex];
  selId=sel.value;
  selPrice=parseFloat(opt.dataset.price||0);
  selCode=opt.dataset.code||opt.text.split('—')[0].trim();
  document.getElementById('tid').value=selId;
  document.querySelectorAll('.mseat').forEach(function(x){x.classList.remove('selected');});
  var m=document.querySelector('.mseat[data-id="'+selId+'"]');
  if(m) m.classList.add('selected');
  showPill();us();
}

function applyseat(){
  document.getElementById('tid').value=selId;
  document.getElementById('tsel').value=selId;
  showPill(); us();
}

function showPill(){
  if(!selId){return;}
  var p=document.getElementById('sp');
  p.classList.add('show');
  document.getElementById('sp-code').textContent=selCode;
  document.getElementById('sp-price').textContent=selPrice.toLocaleString('vi-VN')+'đ';
}

function clrSeat(){
  selId='';selCode='';selPrice=0;
  document.getElementById('tid').value='';
  document.getElementById('tsel').value='';
  document.getElementById('sp').classList.remove('show');
  document.querySelectorAll('.mseat').forEach(function(x){x.classList.remove('selected');});
  us();
}

/* UPDATE SUMMARY */
function us(){
  var n=document.querySelector('[name="customer_name"]');
  document.getElementById('sn').textContent=n&&n.value?n.value:'—';

  var d=document.getElementById('bd');
  if(d&&d.value){
    var dt=new Date(d.value);
    document.getElementById('sd').textContent=
      dt.toLocaleDateString('vi-VN',{day:'2-digit',month:'2-digit',year:'numeric'})+
      ' · '+dt.toLocaleTimeString('vi-VN',{hour:'2-digit',minute:'2-digit'});
  } else document.getElementById('sd').textContent='—';

  document.getElementById('sg').textContent=document.getElementById('gi').value+' người';
  document.getElementById('ss').textContent=selCode||'Chưa chọn';
  document.getElementById('sp2').textContent=selPrice.toLocaleString('vi-VN')+'đ';

  var mt=0;
  for(var id in menuPr){
    var qe=document.getElementById('q'+id);
    mt+=menuPr[id]*(qe?parseInt(qe.value)||1:1);
  }
  var food=selComboPr>0?selComboPr:mt;
  document.getElementById('sm').textContent=food.toLocaleString('vi-VN')+'đ';
  document.getElementById('sdep').textContent=
    Math.ceil((selPrice+food)*.3).toLocaleString('vi-VN')+'đ';
}

/* SUBMIT */
document.getElementById('bk-form').addEventListener('submit',function(){
  var b=document.getElementById('btn-go');
  b.disabled=true;
  document.getElementById('btn-txt').style.display='none';
  document.getElementById('btn-spin').style.display='block';
});

/* INIT: set datetime min = now+2h */
(function(){
  var inp=document.getElementById('bd');
  var now=new Date(); now.setHours(now.getHours()+2);
  if(inp) inp.min=now.toISOString().slice(0,16);
  us();
})();
</script>

<?php include 'views/client/layouts/footer.php'; ?>