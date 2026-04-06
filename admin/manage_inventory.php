<?php
require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();

if (isset($_POST['action']) && $_POST['action'] == 'add_inventory') {
    header('Content-Type: application/json');
    $item_name  = trim($_POST['item_name'] ?? '');
    $category   = $_POST['category'] ?? '';
    $unit_name  = $_POST['unit_name'] ?? '';
    $cost_price = isset($_POST['cost_price']) ? (float)$_POST['cost_price'] : 0;
    if (!empty($item_name)) {
        $stmt = $db->prepare("INSERT INTO inventory (item_name, category, unit_name, cost_price, stock_quantity) VALUES (?, ?, ?, ?, 0)");
        echo json_encode(['status' => $stmt->execute([$item_name, $category, $unit_name, $cost_price]) ? 'success' : 'error']);
    }
    exit;
}
if (isset($_POST['add_category'])) {
    $v = trim($_POST['new_category_name']);
    if ($v) { $db->prepare("INSERT IGNORE INTO inventory_categories (name) VALUES (?)")->execute([$v]); }
    header("Location: manage_inventory.php"); exit;
}
if (isset($_POST['edit_category'])) {
    $old = $_POST['old_category_name']; $new = trim($_POST['new_category_name']);
    if ($new && $old !== $new) {
        $db->beginTransaction();
        try { $db->prepare("UPDATE inventory_categories SET name=? WHERE name=?")->execute([$new,$old]); $db->prepare("UPDATE inventory SET category=? WHERE category=?")->execute([$new,$old]); $db->commit(); } catch(Exception $e){ $db->rollBack(); }
    }
    header("Location: manage_inventory.php"); exit;
}
if (isset($_GET['delete_cat'])) {
    $n = $_GET['delete_cat'];
    $c = $db->prepare("SELECT COUNT(*) FROM inventory WHERE category=?"); $c->execute([$n]);
    if ($c->fetchColumn() > 0) echo "<script>alert('Danh mục đang có nguyên liệu, không thể xóa.');location='manage_inventory.php';</script>";
    else { $db->prepare("DELETE FROM inventory_categories WHERE name=?")->execute([$n]); header("Location: manage_inventory.php"); }
    exit;
}
if (isset($_POST['add_unit'])) {
    $v = trim($_POST['new_unit_name']);
    if ($v) { $db->prepare("INSERT IGNORE INTO inventory_units (name) VALUES (?)")->execute([$v]); }
    header("Location: manage_inventory.php"); exit;
}
if (isset($_POST['edit_unit'])) {
    $old = $_POST['old_unit_name']; $new = trim($_POST['new_unit_name']);
    if ($new && $old !== $new) {
        $db->beginTransaction();
        try { $db->prepare("UPDATE inventory_units SET name=? WHERE name=?")->execute([$new,$old]); $db->prepare("UPDATE inventory SET unit_name=? WHERE unit_name=?")->execute([$new,$old]); $db->commit(); } catch(Exception $e){ $db->rollBack(); }
    }
    header("Location: manage_inventory.php"); exit;
}
if (isset($_GET['delete_unit'])) {
    $n = $_GET['delete_unit'];
    $c = $db->prepare("SELECT COUNT(*) FROM inventory WHERE unit_name=?"); $c->execute([$n]);
    if ($c->fetchColumn() > 0) echo "<script>alert('Đơn vị đang được sử dụng, không thể xóa.');location='manage_inventory.php';</script>";
    else { $db->prepare("DELETE FROM inventory_units WHERE name=?")->execute([$n]); header("Location: manage_inventory.php"); }
    exit;
}
if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    $cs = $db->prepare("SELECT f.name FROM food_recipes r JOIN foods f ON r.food_id=f.id WHERE r.ingredient_id=?");
    $cs->execute([$id]); $foods = $cs->fetchAll(PDO::FETCH_COLUMN);
    if ($foods && !isset($_GET['confirm_force_delete'])) {
        $fl = implode('\n- ', $foods);
        echo "<script>if(confirm('CẢNH BÁO:\\n- $fl\\n\\nXóa sẽ mất định mức. Tiếp tục?')){location='manage_inventory.php?delete_id=$id&confirm_force_delete=1';}else{location='manage_inventory.php';}</script>"; exit;
    }
    try { $db->beginTransaction(); $db->prepare("DELETE FROM food_recipes WHERE ingredient_id=?")->execute([$id]); $db->prepare("DELETE FROM inventory_history WHERE ingredient_id=?")->execute([$id]); $db->prepare("DELETE FROM inventory WHERE id=?")->execute([$id]); $db->commit(); } catch(Exception $e){ $db->rollBack(); }
    header("Location: manage_inventory.php"); exit;
}

$ft  = $_GET['filter_type'] ?? 'month';
$fv  = $_GET['filter_val']  ?? ($ft=='day' ? date('Y-m-d') : ($ft=='year' ? date('Y') : date('Y-m')));
$wh  = $ft=='day' ? "DATE(created_at)='$fv'" : ($ft=='year' ? "YEAR(created_at)='$fv'" : "MONTH(created_at)='".explode('-',$fv)[1]."' AND YEAR(created_at)='".explode('-',$fv)[0]."'");

$report   = $db->query("SELECT SUM(CASE WHEN type='import' THEN quantity ELSE 0 END) ti, SUM(CASE WHEN type='export' THEN quantity ELSE 0 END) te, SUM(CASE WHEN type='loss' THEN quantity ELSE 0 END) tl FROM inventory_history WHERE $wh")->fetch(PDO::FETCH_ASSOC);
$tcost    = $db->query("SELECT SUM(revenue) FROM inventory")->fetchColumn() ?: 0;
$top      = $db->query("SELECT i.item_name, SUM(h.quantity) tot, i.unit_name FROM inventory_history h JOIN inventory i ON h.ingredient_id=i.id WHERE h.type='export' AND $wh GROUP BY h.ingredient_id ORDER BY tot DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
$cats     = $db->query("SELECT name FROM inventory_categories ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
$units    = $db->query("SELECT name FROM inventory_units ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
$inv      = $db->query("SELECT * FROM inventory ORDER BY category, item_name")->fetchAll(PDO::FETCH_ASSOC);

$ex   = (float)($report['te'] ?? 0);
$loss = (float)($report['tl'] ?? 0);
$rate = $ex > 0 ? round($loss/$ex*100,1) : 0;

include '../public/admin_layout_header.php';
?>

<style>
:root {
  --g: #16a34a; --g2: #15803d;
  --r: #dc2626;
  --tx: #111827; --sub: #6b7280; --bd: #e5e7eb;
  --bg: #f9fafb; --card: #ffffff;
  --rad: 10px; --rad-sm: 6px;
}
.pg { padding: 24px 28px; background: var(--bg); min-height: 100vh; }

/* HEADER */
.hdr { display:flex; align-items:center; justify-content:space-between; margin-bottom:22px; gap:12px; flex-wrap:wrap; }
.hdr-left h2 { font-size:1.15rem; font-weight:700; color:var(--tx); margin:0; }
.hdr-left p  { font-size:.78rem; color:var(--sub); margin:2px 0 0; }

/* FILTER */
.flt { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.flt select, .flt input { height:34px; border:1px solid var(--bd); border-radius:var(--rad-sm); padding:0 12px; font-size:.82rem; color:var(--tx); background:#fff; outline:none; }
.flt select:focus, .flt input:focus { border-color:var(--g); }
.flt button { height:34px; padding:0 16px; background:var(--g); color:#fff; border:none; border-radius:var(--rad-sm); font-size:.82rem; font-weight:600; cursor:pointer; }
.flt button:hover { background:var(--g2); }

/* STATS */
.stats { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:20px; }
@media(max-width:860px){ .stats{grid-template-columns:1fr 1fr;} }
.st { background:var(--card); border:1px solid var(--bd); border-radius:var(--rad); padding:14px 16px; }
.st-lbl { font-size:.7rem; color:var(--sub); font-weight:600; margin-bottom:8px; }
.st-val { font-size:1.4rem; font-weight:700; color:var(--tx); line-height:1; }
.st-val.up   { color:var(--g); }
.st-val.down { color:var(--r); }
.st-val.warn { color:#d97706; }
.st-sub { font-size:.72rem; color:var(--sub); margin-top:4px; }
.prog { height:3px; border-radius:99px; background:var(--bd); margin-top:8px; }
.prog-b { height:100%; border-radius:99px; background:#d97706; }

/* LAYOUT */
.layout { display:grid; grid-template-columns:200px 1fr; gap:16px; align-items:start; }
@media(max-width:840px){ .layout{grid-template-columns:1fr;} }

/* CARD */
.card { background:var(--card); border:1px solid var(--bd); border-radius:var(--rad); }
.card-title { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:var(--sub); padding:14px 16px 0; margin-bottom:12px; }

/* SIDEBAR */
.side { display:flex; flex-direction:column; gap:14px; }
.panel-bd { padding:0 14px 14px; }
.add-row { display:flex; gap:6px; margin-bottom:10px; }
.add-row input { flex:1; height:32px; border:1px solid var(--bd); border-radius:var(--rad-sm); padding:0 9px; font-size:.8rem; color:var(--tx); outline:none; background:#fff; }
.add-row input:focus { border-color:var(--g); }
.add-btn { height:32px; width:32px; border:none; border-radius:var(--rad-sm); background:var(--g); color:#fff; font-size:.78rem; cursor:pointer; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.add-btn:hover { background:var(--g2); }
.tag-list { max-height:160px; overflow-y:auto; }
.tag-list::-webkit-scrollbar { width:2px; } .tag-list::-webkit-scrollbar-thumb { background:var(--bd); }
.tag-row { display:flex; align-items:center; gap:3px; padding:3px 4px; border-radius:var(--rad-sm); }
.tag-row:hover { background:var(--bg); }
.tag-row input[type=text] { flex:1; border:none; background:transparent; font-size:.8rem; color:var(--tx); font-weight:500; outline:none; padding:2px; min-width:0; }
.te { border:none; background:none; color:#d97706; font-size:.7rem; cursor:pointer; padding:2px 3px; opacity:.6; } .te:hover{opacity:1;}
.td { border:none; background:none; color:var(--r); font-size:.68rem; cursor:pointer; padding:2px 3px; opacity:.5; text-decoration:none; display:inline-flex; align-items:center; } .td:hover{opacity:1;}

/* RIGHT */
.right { display:flex; flex-direction:column; gap:14px; }

/* FORM */
.form-bd { padding:0 16px 16px; }
.fg { display:grid; grid-template-columns:1fr 130px 110px; gap:10px; align-items:end; }
.fg2 { display:grid; grid-template-columns:1fr 140px; gap:10px; align-items:end; margin-top:10px; }
@media(max-width:680px){ .fg,.fg2{grid-template-columns:1fr;} }
.ff label { font-size:.7rem; font-weight:600; color:var(--sub); display:block; margin-bottom:4px; }
.ff input, .ff select { width:100%; height:36px; border:1px solid var(--bd); border-radius:var(--rad-sm); padding:0 10px; font-size:.85rem; color:var(--tx); background:#fff; outline:none; box-sizing:border-box; }
.ff input:focus, .ff select:focus { border-color:var(--g); }
.btn-add-main { height:36px; padding:0 18px; background:var(--g); color:#fff; border:none; border-radius:var(--rad-sm); font-size:.85rem; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:6px; white-space:nowrap; }
.btn-add-main:hover { background:var(--g2); }

/* TABLE */
.tbl-top { display:flex; align-items:center; justify-content:space-between; padding:14px 16px 12px; border-bottom:1px solid var(--bd); flex-wrap:wrap; gap:8px; }
.tbl-top h6 { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:var(--sub); margin:0; }
.tbl-top .cnt { font-size:.7rem; font-weight:600; color:var(--sub); background:var(--bg); border:1px solid var(--bd); border-radius:99px; padding:1px 8px; margin-left:6px; }
.srch { height:32px; border:1px solid var(--bd); border-radius:var(--rad-sm); padding:0 10px 0 28px; font-size:.8rem; width:170px; outline:none; color:var(--tx); background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2.5'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cpath d='m21 21-4.35-4.35'/%3E%3C/svg%3E") no-repeat 9px center; }
.srch:focus { border-color:var(--g); }
.tbl { width:100%; border-collapse:collapse; }
.tbl thead th { font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--sub); padding:8px 14px; background:var(--bg); border-bottom:1px solid var(--bd); white-space:nowrap; }
.tbl thead th:first-child { padding-left:16px; }
.tbl thead th:last-child { text-align:right; padding-right:16px; }
.tbl tbody tr { border-bottom:1px solid #f3f4f6; transition:background .1s; }
.tbl tbody tr:last-child { border:none; }
.tbl tbody tr:hover { background:#fafafa; }
.tbl td { padding:10px 14px; font-size:.85rem; color:var(--tx); vertical-align:middle; }
.tbl td:first-child { padding-left:16px; }
.tbl td:last-child { text-align:right; padding-right:16px; }
.i-name { font-weight:600; }
.cbadge { display:inline-block; padding:2px 8px; border-radius:4px; font-size:.7rem; font-weight:600; background:var(--bg); color:var(--sub); border:1px solid var(--bd); }
.stk-ok  { font-weight:600; }
.stk-low { color:var(--r); font-weight:700; }
.u-sm { font-size:.75rem; color:var(--sub); margin-left:2px; font-weight:400; }
.c-td { color:var(--sub); font-size:.8rem; }
.acts { display:flex; justify-content:flex-end; gap:5px; }
.bi { width:28px; height:28px; border-radius:var(--rad-sm); display:flex; align-items:center; justify-content:center; font-size:.75rem; cursor:pointer; border:1px solid; transition:all .12s; text-decoration:none; background:#fff; }
.bi.im { border-color:#bbf7d0; color:var(--g); } .bi.im:hover { background:var(--g); color:#fff; border-color:var(--g); }
.bi.de { border-color:#fecaca; color:var(--r); } .bi.de:hover { background:var(--r); color:#fff; border-color:var(--r); }
.empty-td { text-align:center; padding:40px; color:var(--sub); font-size:.85rem; }

/* MODAL */
.modal-content { border:none; border-radius:var(--rad); overflow:hidden; box-shadow:0 10px 40px rgba(0,0,0,.15); }
.m-hd { background:var(--g); padding:16px 20px; display:flex; align-items:center; justify-content:space-between; }
.m-hd h5 { color:#fff; font-size:.95rem; font-weight:700; margin:0; }
.m-close { background:none; border:none; color:rgba(255,255,255,.8); font-size:.9rem; cursor:pointer; padding:2px 6px; }
.m-close:hover { color:#fff; }
.m-bd { padding:20px; }
.m-bd label { font-size:.7rem; font-weight:600; color:var(--sub); display:block; margin-bottom:5px; }
.qty-row { display:flex; border:1px solid var(--bd); border-radius:var(--rad-sm); overflow:hidden; }
.qty-row:focus-within { border-color:var(--g); }
.qty-row input { flex:1; border:none; padding:10px 12px; font-size:1rem; font-weight:600; outline:none; color:var(--tx); }
.qty-row .u-tag { padding:0 14px; background:var(--bg); color:var(--sub); font-size:.82rem; font-weight:600; display:flex; align-items:center; border-left:1px solid var(--bd); }
.m-ft { padding:12px 20px; background:var(--bg); border-top:1px solid var(--bd); display:flex; justify-content:flex-end; gap:8px; }
.btn-c { height:36px; padding:0 16px; border:1px solid var(--bd); border-radius:var(--rad-sm); background:#fff; color:var(--tx); font-size:.85rem; font-weight:500; cursor:pointer; }
.btn-ok { height:36px; padding:0 20px; background:var(--g); color:#fff; border:none; border-radius:var(--rad-sm); font-size:.85rem; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:6px; }
.btn-ok:hover { background:var(--g2); }

/* TOAST */
#toast { position:fixed; bottom:24px; right:24px; z-index:9999; background:#111827; color:#fff; padding:10px 18px; border-radius:var(--rad-sm); font-size:.85rem; font-weight:500; opacity:0; transform:translateY(6px); transition:opacity .2s,transform .2s; pointer-events:none; }
#toast.on { opacity:1; transform:none; }
#toast.err { background:var(--r); }
</style>

<div class="pg">

  <!-- HEADER -->
  <div class="hdr">
    <div class="hdr-left">
      <h2>Quản Lý Kho Nguyên Liệu</h2>
      <p>Cập nhật lúc <?= date('H:i, d/m/Y') ?></p>
    </div>
    <form method="GET" class="flt">
      <select name="filter_type" id="filter_type" onchange="updFlt(this.value)">
        <option value="day"   <?= $ft=='day'  ?'selected':'' ?>>Theo Ngày</option>
        <option value="month" <?= $ft=='month'?'selected':'' ?>>Theo Tháng</option>
        <option value="year"  <?= $ft=='year' ?'selected':'' ?>>Theo Năm</option>
      </select>
      <input type="<?= $ft=='day'?'date':($ft=='year'?'number':'month') ?>"
             name="filter_val" id="filter_val" value="<?= $fv ?>" style="min-width:140px;">
      <button type="submit">Xem</button>
    </form>
  </div>

  <!-- STATS -->
  <div class="stats">
    <div class="st">
      <div class="st-lbl">Tổng Nhập</div>
      <div class="st-val up">+<?= number_format($report['ti']??0,1) ?></div>
      <div class="st-sub">đơn vị nhập vào</div>
    </div>
    <div class="st">
      <div class="st-lbl">Tổng Xuất</div>
      <div class="st-val down">−<?= number_format($report['te']??0,1) ?></div>
      <div class="st-sub">đơn vị đã xuất</div>
    </div>
    <div class="st">
      <div class="st-lbl">Hao Hụt</div>
      <div class="st-val warn"><?= $rate ?>%</div>
      <div class="prog"><div class="prog-b" style="width:<?= min($rate,100) ?>%"></div></div>
    </div>
    <div class="st">
      <div class="st-lbl">Chi Phí Tiêu Hao</div>
      <div class="st-val"><?= number_format($tcost) ?>đ</div>
      <div class="st-sub">Theo định mức</div>
    </div>
  </div>

  <!-- LAYOUT -->
  <div class="layout">

    <!-- SIDEBAR -->
    <div class="side">
      <div class="card">
        <div class="card-title">Danh Mục</div>
        <div class="panel-bd">
          <form method="POST" class="add-row">
            <input type="text" name="new_category_name" placeholder="Thêm mới…" required>
            <button type="submit" name="add_category" class="add-btn"><i class="fa fa-plus"></i></button>
          </form>
          <div class="tag-list">
            <?php foreach($cats as $c): ?>
            <div class="tag-row">
              <form method="POST" style="display:flex;align-items:center;flex:1;gap:2px;min-width:0;">
                <input type="hidden" name="old_category_name" value="<?= htmlspecialchars($c) ?>">
                <input type="text" name="new_category_name" value="<?= htmlspecialchars($c) ?>">
                <button type="submit" name="edit_category" class="te" title="Lưu"><i class="fa fa-check"></i></button>
              </form>
              <a href="?delete_cat=<?= urlencode($c) ?>" class="td" onclick="return confirm('Xóa «<?= htmlspecialchars($c) ?>»?')"><i class="fa fa-times"></i></a>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-title">Đơn Vị</div>
        <div class="panel-bd">
          <form method="POST" class="add-row">
            <input type="text" name="new_unit_name" placeholder="kg, lít, chai…" required>
            <button type="submit" name="add_unit" class="add-btn"><i class="fa fa-plus"></i></button>
          </form>
          <div class="tag-list">
            <?php foreach($units as $u): ?>
            <div class="tag-row">
              <form method="POST" style="display:flex;align-items:center;flex:1;gap:2px;min-width:0;">
                <input type="hidden" name="old_unit_name" value="<?= htmlspecialchars($u) ?>">
                <input type="text" name="new_unit_name" value="<?= htmlspecialchars($u) ?>">
                <button type="submit" name="edit_unit" class="te" title="Lưu"><i class="fa fa-check"></i></button>
              </form>
              <a href="?delete_unit=<?= urlencode($u) ?>" class="td" onclick="return confirm('Xóa «<?= htmlspecialchars($u) ?>»?')"><i class="fa fa-times"></i></a>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <?php if(!empty($top)): ?>
      <div class="card">
        <div class="card-title">Dùng Nhiều Nhất</div>
        <div class="panel-bd" style="padding-top:0;">
          <?php foreach($top as $i=>$t): ?>
          <div style="display:flex;align-items:center;justify-content:space-between;padding:7px 0;border-bottom:1px solid #f3f4f6;font-size:.8rem;">
            <span style="color:var(--sub);font-weight:700;margin-right:6px;"><?= $i+1 ?>.</span>
            <span style="flex:1;font-weight:600;color:var(--tx);"><?= htmlspecialchars($t['item_name']) ?></span>
            <span style="font-weight:700;color:var(--g);"><?= number_format($t['tot'],1) ?> <?= $t['unit_name'] ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- RIGHT -->
    <div class="right">

      <!-- FORM THÊM MỚI -->
      <div class="card">
        <div class="card-title">Thêm Nguyên Liệu</div>
        <div class="form-bd">
          <form id="form-add">
            <div class="fg">
              <div class="ff"><label>Tên nguyên liệu *</label><input type="text" name="item_name" placeholder="VD: Thịt bò, Hành…" required></div>
              <div class="ff"><label>Danh mục</label>
                <select name="category"><?php foreach($cats as $c): ?><option><?= htmlspecialchars($c) ?></option><?php endforeach; ?></select>
              </div>
              <div class="ff"><label>Đơn vị</label>
                <select name="unit_name"><?php foreach($units as $u): ?><option><?= htmlspecialchars($u) ?></option><?php endforeach; ?></select>
              </div>
            </div>
            <div class="fg2">
              <div class="ff"><label>Giá vốn (đ)</label><input type="number" name="cost_price" value="0" min="0"></div>
              <button type="submit" class="btn-add-main"><i class="fa fa-plus"></i> Thêm vào kho</button>
            </div>
          </form>
        </div>
      </div>

      <!-- BẢNG -->
      <div class="card">
        <div class="tbl-top">
          <h6>Tồn Kho<span class="cnt"><?= count($inv) ?></span></h6>
          <input class="srch" type="text" id="srch" placeholder="Tìm kiếm…" oninput="doSearch()">
        </div>
        <div style="overflow-x:auto;">
          <table class="tbl" id="invTbl">
            <thead>
              <tr>
                <th>Nguyên Liệu</th>
                <th>Danh Mục</th>
                <th>Tồn Kho</th>
                <th>Giá Vốn</th>
                <th>Thao Tác</th>
              </tr>
            </thead>
            <tbody>
              <?php if(empty($inv)): ?>
                <tr><td class="empty-td" colspan="5">Chưa có nguyên liệu nào</td></tr>
              <?php else: ?>
                <?php foreach($inv as $i): ?>
                <tr>
                  <td><span class="i-name"><?= htmlspecialchars($i['item_name']) ?></span></td>
                  <td><span class="cbadge"><?= htmlspecialchars($i['category']) ?></span></td>
                  <td>
                    <span class="<?= $i['stock_quantity']<5?'stk-low':'stk-ok' ?>">
                      <?= number_format($i['stock_quantity'],2) ?><span class="u-sm"><?= $i['unit_name'] ?></span>
                    </span>
                  </td>
                  <td class="c-td"><?= number_format($i['cost_price']) ?>đ</td>
                  <td>
                    <div class="acts">
                      <button class="bi im btn-import" data-id="<?= $i['id'] ?>" data-name="<?= htmlspecialchars($i['item_name']) ?>" data-unit="<?= $i['unit_name'] ?>" title="Nhập kho"><i class="fa fa-plus"></i></button>
                      <a href="?delete_id=<?= $i['id'] ?>" class="bi de" onclick="return confirm('Xóa «<?= htmlspecialchars($i['item_name']) ?>»?')" title="Xóa"><i class="fa fa-trash"></i></a>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- MODAL -->
<div class="modal fade" id="modalImport" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <div class="m-hd">
        <h5>Nhập kho: <span id="m-name"></span></h5>
        <button class="m-close" data-bs-dismiss="modal"><i class="fa fa-times"></i></button>
      </div>
      <form id="form-import">
        <div class="m-bd">
          <input type="hidden" name="item_id" id="m-id">
          <label>Số lượng nhập thêm</label>
          <div class="qty-row">
            <input type="number" name="quantity" id="qty-in" step="0.01" min="0.01" required placeholder="0.00">
            <div class="u-tag" id="m-unit"></div>
          </div>
        </div>
        <div class="m-ft">
          <button type="button" class="btn-c" data-bs-dismiss="modal">Hủy</button>
          <button type="submit" class="btn-ok"><i class="fa fa-check"></i> Xác nhận</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div id="toast"></div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function updFlt(t){
  const el=document.getElementById('filter_val');
  el.type=t==='day'?'date':(t==='year'?'number':'month');
  if(t==='year'){el.placeholder='YYYY';}
}
function doSearch(){
  const q=document.getElementById('srch').value.toLowerCase();
  document.querySelectorAll('#invTbl tbody tr').forEach(r=>{
    r.style.display=r.textContent.toLowerCase().includes(q)?'':'none';
  });
}
function toast(msg,err=false){
  const t=document.getElementById('toast');
  t.textContent=msg; t.className='on'+(err?' err':'');
  setTimeout(()=>{t.className='';},2800);
}
$(function(){
  updFlt($('#filter_type').val());

  $('#form-add').on('submit',function(e){
    e.preventDefault();
    const btn=$(this).find('.btn-add-main'),orig=btn.html();
    btn.text('Đang lưu…').prop('disabled',true);
    $.post('manage_inventory.php',$(this).serialize()+'&action=add_inventory',function(r){
      if(r.status==='success'){toast('Đã thêm thành công');setTimeout(()=>location.reload(),800);}
      else{toast('Không thể lưu',true);btn.html(orig).prop('disabled',false);}
    },'json').fail(()=>{toast('Lỗi kết nối',true);btn.html(orig).prop('disabled',false);});
  });

  $(document).on('click','.btn-import',function(){
    $('#m-id').val($(this).data('id'));
    $('#m-name').text($(this).data('name'));
    $('#m-unit').text($(this).data('unit'));
    $('#qty-in').val('');
    new bootstrap.Modal(document.getElementById('modalImport')).show();
    setTimeout(()=>$('#qty-in').focus(),380);
  });

  $('#form-import').on('submit',function(e){
    e.preventDefault();
    const btn=$(this).find('.btn-ok');
    btn.text('Đang xử lý…').prop('disabled',true);
    $.post('ajax_update_stock.php',$(this).serialize(),function(){
      toast('Đã cập nhật kho');setTimeout(()=>location.reload(),800);
    }).fail(()=>{toast('Lỗi cập nhật',true);btn.html('<i class="fa fa-check"></i> Xác nhận').prop('disabled',false);});
  });
});
</script>