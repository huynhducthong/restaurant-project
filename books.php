<?php
require_once __DIR__ . '/config/database.php';
$db = (new Database())->getConnection();

// Lấy danh sách sách đang bán
$books = $db->query(
    "SELECT * FROM books WHERE is_active = 1 ORDER BY id DESC"
)->fetchAll(PDO::FETCH_ASSOC);

// Nhóm theo danh mục
$by_cat = [];
foreach ($books as $b) {
    $by_cat[$b['category']][] = $b;
}

// ── Lấy thông tin thanh toán từ settings ──
$settings = [];
$s = $db->query("SELECT key_name, key_value FROM settings")->fetchAll(PDO::FETCH_ASSOC);
foreach ($s as $row) $settings[$row['key_name']] = $row['key_value'];

// ── Xử lý đặt hàng ──
$order_success = false;
$order_code    = '';
$order_error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_order'])) {
    $name    = trim($_POST['customer_name'] ?? '');
    $phone   = trim($_POST['phone']         ?? '');
    $address = trim($_POST['address']       ?? '');
    $method  = ($_POST['delivery_method'] ?? '') === 'ship' ? 'ship' : 'pickup';
    $note    = trim($_POST['note']          ?? '');
    $items   = $_POST['items']              ?? [];

    // Validate
    if ($name === '' || $phone === '') {
        $order_error = 'Vui lòng nhập họ tên và số điện thoại.';
    } elseif ($method === 'ship' && $address === '') {
        $order_error = 'Vui lòng nhập địa chỉ giao hàng.';
    } elseif (empty($items)) {
        $order_error = 'Vui lòng chọn ít nhất 1 cuốn sách.';
    } else {
        // Tính tổng tiền + kiểm tra tồn kho
        $total  = 0;
        $valid_items = [];
        foreach ($items as $book_id => $qty) {
            $qty = (int)$qty;
            if ($qty <= 0) continue;
            $bs = $db->prepare("SELECT * FROM books WHERE id = ? AND is_active = 1");
            $bs->execute([(int)$book_id]);
            $book = $bs->fetch(PDO::FETCH_ASSOC);
            if (!$book) continue;
            if ($book['stock'] < $qty) {
                $order_error = "Sách \"" . htmlspecialchars($book['title']) . "\" chỉ còn {$book['stock']} cuốn.";
                break;
            }
            $total += $book['price'] * $qty;
            $valid_items[] = ['book' => $book, 'qty' => $qty];
        }

        if ($order_error === '' && !empty($valid_items)) {
            // Tạo mã đơn hàng
            $order_code = 'BK' . date('ymd') . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));

            $db->beginTransaction();
            try {
                $db->prepare(
                    "INSERT INTO book_orders
                     (order_code, customer_name, phone, address, delivery_method, note, total_amount)
                     VALUES (?, ?, ?, ?, ?, ?, ?)"
                )->execute([$order_code, $name, $phone, $address, $method, $note, $total]);

                $order_id = (int)$db->lastInsertId();
                $ins = $db->prepare(
                    "INSERT INTO book_order_items (order_id, book_id, book_title, quantity, price)
                     VALUES (?, ?, ?, ?, ?)"
                );
                $upd = $db->prepare("UPDATE books SET stock = stock - ? WHERE id = ?");

                foreach ($valid_items as $item) {
                    $ins->execute([
                        $order_id,
                        $item['book']['id'],
                        $item['book']['title'],
                        $item['qty'],
                        $item['book']['price']
                    ]);
                    $upd->execute([$item['qty'], $item['book']['id']]);
                }

                $db->commit();
                $order_success = true;
                $order_total   = $total;
            } catch (Exception $e) {
                $db->rollBack();
                $order_error = 'Lỗi hệ thống. Vui lòng thử lại.';
            }
        }
    }
}

include __DIR__ . '/views/client/layouts/header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">

<style>
    :root {
        --gold: #cda45e;
        --ink: #1a1814;
    }

    /* Hero */
    .books-hero {
        background: linear-gradient(160deg, #1a1814 0%, #2a221a 100%);
        padding: 120px 0 60px;
        text-align: center;
    }

    .books-hero h1 {
        font-family: 'Playfair Display', serif;
        font-size: clamp(2rem, 5vw, 3.2rem);
        color: #fff;
        margin: 0 0 14px;
    }

    .books-hero h1 em {
        color: var(--gold);
        font-style: italic;
    }

    .books-hero p {
        color: rgba(255, 255, 255, .6);
        font-family: 'DM Sans', sans-serif;
        font-size: 1.05rem;
    }

    /* Book card */
    .book-card {
        background: #fff;
        border-radius: 14px;
        overflow: hidden;
        border: 1px solid #f0ece4;
        box-shadow: 0 2px 14px rgba(0, 0, 0, .06);
        transition: transform .25s, box-shadow .25s;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .book-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, .12);
    }

    .book-img-wrap {
        position: relative;
        overflow: hidden;
        padding-top: 140%;
        background: #f8f4ee;
    }

    .book-img-wrap img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform .4s;
    }

    .book-card:hover .book-img-wrap img {
        transform: scale(1.05);
    }

    .book-body {
        padding: 16px;
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .book-title {
        font-family: 'Playfair Display', serif;
        font-size: 1rem;
        font-weight: 700;
        color: var(--ink);
        margin: 0 0 4px;
    }

    .book-author {
        font-size: 12px;
        color: #888;
        margin: 0 0 10px;
    }

    .book-price {
        font-size: 1.15rem;
        font-weight: 700;
        color: var(--gold);
        margin-top: auto;
    }

    .book-stock {
        font-size: 11px;
        color: #999;
    }

    .out-of-stock {
        opacity: .55;
    }

    .badge-cat {
        position: absolute;
        top: 10px;
        left: 10px;
        z-index: 2;
        background: var(--gold);
        color: #000;
        font-size: 9px;
        font-weight: 700;
        letter-spacing: .1em;
        text-transform: uppercase;
        padding: 3px 8px;
        border-radius: 4px;
    }

    /* Qty selector */
    .qty-wrap {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 10px;
    }

    .qty-btn {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        border: 1.5px solid #ddd;
        background: #f8f8f8;
        font-size: 16px;
        line-height: 1;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: .15s;
    }

    .qty-btn:hover {
        border-color: var(--gold);
        background: #fef9f0;
    }

    .qty-input {
        width: 40px;
        text-align: center;
        border: 1.5px solid #ddd;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        padding: 3px 0;
    }

    .btn-add-cart {
        width: 100%;
        margin-top: 10px;
        padding: 8px;
        background: var(--gold);
        border: none;
        border-radius: 8px;
        color: #000;
        font-weight: 600;
        font-size: 13px;
        cursor: pointer;
        transition: .2s;
    }

    .btn-add-cart:hover {
        background: #b8923e;
    }

    .btn-add-cart:disabled {
        background: #ddd;
        color: #999;
        cursor: default;
    }

    /* Cart sidebar */
    .cart-sidebar {
        position: fixed;
        right: 20px;
        bottom: 20px;
        z-index: 500;
    }

    .cart-btn {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: var(--gold);
        border: none;
        color: #000;
        font-size: 22px;
        cursor: pointer;
        position: relative;
        box-shadow: 0 4px 16px rgba(205, 164, 94, .5);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform .2s;
    }

    .cart-btn:hover {
        transform: scale(1.1);
    }

    .cart-count {
        position: absolute;
        top: -4px;
        right: -4px;
        background: #dc3545;
        color: #fff;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        font-size: 11px;
        font-weight: 700;
        display: none;
        align-items: center;
        justify-content: center;
    }

    /* Order form */
    .order-section {
        background: #f9f6f0;
        padding: 60px 0;
    }

    .order-card {
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 4px 24px rgba(0, 0, 0, .08);
        overflow: hidden;
    }

    .order-header {
        background: linear-gradient(135deg, var(--ink), #2a221a);
        padding: 28px 32px;
        color: #fff;
    }

    .order-header h3 {
        font-family: 'Playfair Display', serif;
        margin: 0;
        font-size: 1.5rem;
    }

    /* Delivery toggle */
    .delivery-opt {
        border: 2px solid #e8e2d9;
        border-radius: 10px;
        padding: 14px 16px;
        cursor: pointer;
        transition: .2s;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .delivery-opt.active {
        border-color: var(--gold);
        background: #fef9f0;
    }

    .delivery-opt input {
        display: none;
    }

    /* Success state */
    .success-box {
        text-align: center;
        padding: 40px 20px;
        background: #f0fdf4;
        border-radius: 14px;
        border: 1px solid #bbf7d0;
    }

    /* Section title */
    .section-sep {
        display: flex;
        align-items: center;
        gap: 16px;
        margin: 40px 0 24px;
    }

    .section-sep-line {
        flex: 1;
        height: 1px;
        background: #e8e2d9;
    }

    .section-sep-text {
        font-family: 'DM Sans', sans-serif;
        font-size: 10px;
        font-weight: 600;
        letter-spacing: .2em;
        text-transform: uppercase;
        color: var(--gold);
    }

    /* Offcanvas */
    .offcanvas-cart { width: 400px !important; }
    .cart-item { display: flex; gap: 12px; padding: 16px; border-bottom: 1px solid #f0ece4; }
    .cart-item-img { width: 60px; height: 80px; object-fit: cover; border-radius: 6px; }
    .cart-item-info { flex: 1; display: flex; flex-direction: column; justify-content: space-between; }
    .cart-item-title { font-family: 'Playfair Display', serif; font-weight: 700; font-size: 15px; margin-bottom: 4px; line-height: 1.2; }
    .cart-item-price { color: var(--gold); font-weight: 700; font-size: 14px; }
    .cart-item-qty { display: flex; align-items: center; gap: 8px; margin-top: auto; }
    .cart-item-qty button { width: 24px; height: 24px; border: 1px solid #ddd; background: #fff; border-radius: 4px; font-size: 14px; cursor: pointer; display: flex; align-items: center; justify-content: center; }
    .cart-item-remove { color: #dc3545; background: none; border: none; font-size: 12px; padding: 0; cursor: pointer; }
    .checkout-summary { background: #faf9f6; padding: 24px; border-radius: 12px; border: 1px solid #e8e2d9; }
</style>

<!-- HERO -->
<section class="books-hero">
    <div class="container">
        <p style="font-family:'DM Sans',sans-serif;font-size:12px;letter-spacing:.2em;text-transform:uppercase;color:var(--gold);margin-bottom:12px">
            ✦ Cửa hàng sách
        </p>
        <h1>Sách <em>Ẩm Thực</em> & Nấu Ăn</h1>
        <p>Bí quyết từ bếp nhà hàng — nay trong tay bạn</p>
    </div>
</section>

<!-- SÁCH -->
<section style="background:#faf7f2;padding:60px 0">
    <div class="container">
        <?php if (empty($books)): ?>
            <div class="text-center py-5 text-muted">
                <div style="font-size:48px;margin-bottom:16px">📚</div>
                <p>Chưa có sách nào. Vui lòng quay lại sau!</p>
            </div>
        <?php else: ?>

            <?php foreach ($by_cat as $cat => $cat_books): ?>
                <div class="section-sep">
                    <span class="section-sep-line"></span>
                    <span class="section-sep-text"><?= htmlspecialchars($cat) ?></span>
                    <span class="section-sep-line"></span>
                </div>

                <div class="row g-4 mb-4">
                    <?php foreach ($cat_books as $b): $out = $b['stock'] <= 0; ?>
                        <div class="col-6 col-md-4 col-lg-3">
                            <div class="book-card <?= $out ? 'out-of-stock' : '' ?>"
                                data-id="<?= $b['id'] ?>"
                                data-title="<?= htmlspecialchars($b['title']) ?>"
                                data-price="<?= (float)$b['price'] ?>"
                                data-stock="<?= (int)$b['stock'] ?>">

                                <div class="book-img-wrap">
                                    <span class="badge-cat"><?= htmlspecialchars($b['category']) ?></span>
                                    <img src="<?= !empty($b['image']) ? 'public/assets/img/books/' . htmlspecialchars($b['image']) : 'public/assets/img/books/default-book.jpg' ?>"
                                        alt="<?= htmlspecialchars($b['title']) ?>"
                                        onerror="this.src='public/assets/img/books/default-book.jpg'">
                                </div>

                                <div class="book-body">
                                    <div class="book-title"><?= htmlspecialchars($b['title']) ?></div>
                                    <?php if ($b['author']): ?>
                                        <div class="book-author">✍ <?= htmlspecialchars($b['author']) ?></div>
                                    <?php endif; ?>
                                    <?php if ($b['description']): ?>
                                        <p style="font-size:12px;color:#666;margin:0 0 8px;flex:1;
                                  display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden">
                                            <?= htmlspecialchars($b['description']) ?>
                                        </p>
                                    <?php endif; ?>
                                    <div class="d-flex justify-content-between align-items-center mt-auto">
                                        <div class="book-price"><?= number_format($b['price'], 0, ',', '.') ?>đ</div>
                                        <div class="book-stock"><?= $out ? 'Hết hàng' : "Còn {$b['stock']}" ?></div>
                                    </div>
                                    <?php if (!$out): ?>
                                        <div class="qty-wrap">
                                            <button class="qty-btn" onclick="changeQty(<?= $b['id'] ?>,-1)">−</button>
                                            <input class="qty-input" type="number" id="qty-<?= $b['id'] ?>" value="1" min="1" max="<?= $b['stock'] ?>">
                                            <button class="qty-btn" onclick="changeQty(<?= $b['id'] ?>,1)">+</button>
                                        </div>
                                        <button class="btn-add-cart" onclick="addToCart(<?= $b['id'] ?>)">
                                            🛒 Thêm vào giỏ
                                        </button>
                                    <?php else: ?>
                                        <button class="btn-add-cart" disabled>Hết hàng</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<!-- GIỎ HÀNG FLOATING -->
<div class="cart-sidebar">
    <button class="cart-btn" data-bs-toggle="offcanvas" data-bs-target="#cartOffcanvas" id="cart-btn" title="Xem giỏ hàng">
        🛒
        <span class="cart-count" id="cart-count">0</span>
    </button>
</div>

<!-- OFFCANVAS CART -->
<div class="offcanvas offcanvas-end offcanvas-cart" tabindex="-1" id="cartOffcanvas" aria-labelledby="cartOffcanvasLabel">
    <div class="offcanvas-header" style="background: linear-gradient(135deg, var(--ink), #2a221a); color: #fff;">
        <h5 class="offcanvas-title" id="cartOffcanvasLabel" style="font-family:'Playfair Display',serif;"><i class="fas fa-shopping-cart me-2" style="color:var(--gold)"></i> Giỏ hàng của bạn</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column p-0">
        <div id="offcanvas-items" class="flex-grow-1" style="overflow-y: auto;">
            <!-- Cart items render here -->
        </div>
        <div class="p-4 bg-light border-top mt-auto shadow-sm" style="z-index:10;">
            <div class="d-flex justify-content-between mb-3 align-items-center">
                <span class="fw-bold text-muted">Tổng cộng:</span>
                <span style="color:var(--gold); font-size: 1.5rem; font-weight: 700;" id="offcanvas-total">0đ</span>
            </div>
            <button class="btn w-100 fw-bold py-3 rounded-pill" style="background:var(--gold); color:#000;" onclick="showCheckout()" id="btn-proceed-checkout" disabled>
                TIẾN HÀNH THANH TOÁN
            </button>
        </div>
    </div>
</div>

<!-- FORM ĐẶT HÀNG (CHECKOUT) -->
<section class="order-section" id="order-section" <?= !$order_success && !$order_error ? 'style="display:none;"' : '' ?>>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">

                <?php if ($order_success): ?>
                    <div class="success-box mx-auto" style="max-width:600px;">
                        <div style="font-size:56px;margin-bottom:16px">🎉</div>
                        <h3 style="font-family:'Playfair Display',serif;color:#166534">Đặt hàng thành công!</h3>
                        <p class="text-muted">Mã đơn hàng của bạn: <strong style="font-size:1.2rem;color:var(--gold)"><?= htmlspecialchars($order_code) ?></strong></p>
                        <p class="text-muted">Chúng tôi sẽ liên hệ xác nhận qua số điện thoại bạn đã cung cấp.</p>

                        <?php if (!empty($settings['bank_number'])): ?>
                            <div style="background:#fff;border-radius:12px;padding:20px;margin:20px auto;max-width:360px;border:1px solid #bbf7d0; text-align:left;">
                                <div style="font-size:13px;color:#555;margin-bottom:8px">Thông tin chuyển khoản:</div>
                                <div style="font-weight:700;font-size:1.1rem"><?= htmlspecialchars($settings['bank_name'] ?? '') ?></div>
                                <div style="font-size:1.3rem;font-weight:700;color:var(--gold);letter-spacing:.05em">
                                    <?= htmlspecialchars($settings['bank_number']) ?>
                                </div>
                                <div><?= htmlspecialchars($settings['bank_holder'] ?? '') ?></div>
                                <div style="color:#888;font-size:12px;margin-top:8px">
                                    Nội dung: <strong><?= htmlspecialchars($order_code) ?></strong>
                                </div>
                                <div style="font-size:1.2rem;font-weight:700;color:#dc3545;margin-top:12px">
                                    Tổng: <?= number_format($order_total ?? 0, 0, ',', '.') ?>đ
                                </div>
                            </div>
                        <?php endif; ?>

                        <a href="books.php" class="btn btn-outline-success mt-2 rounded-pill px-4">Tiếp tục mua sắm</a>
                    </div>

                <?php else: ?>
                    <div class="order-card p-0">
                        <div class="order-header">
                            <h3>📦 Hoàn tất đơn hàng</h3>
                            <p style="margin:6px 0 0;opacity:.7;font-size:13px">
                                Vui lòng kiểm tra lại thông tin và xác nhận đặt hàng
                            </p>
                        </div>
                        
                        <form method="POST" id="order-form">
                            <input type="hidden" name="submit_order" value="1">
                            <div class="row g-0">
                                
                                <!-- CỘT TRÁI: THÔNG TIN KHÁCH HÀNG -->
                                <div class="col-lg-7 p-4 p-md-5 border-end">
                                    <h5 class="fw-bold mb-4" style="color:var(--ink)">Thông tin giao hàng</h5>
                                    
                                    <?php if ($order_error): ?>
                                        <div class="alert alert-danger border-0 mb-4 shadow-sm">
                                            <i class="fas fa-exclamation-circle me-2"></i>
                                            <?= htmlspecialchars($order_error) ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="row g-3 mb-4">
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold small text-muted">Họ và tên <span class="text-danger">*</span></label>
                                            <input type="text" name="customer_name" class="form-control bg-light border-0 py-2" placeholder="VD: Nguyễn Văn A" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold small text-muted">Số điện thoại <span class="text-danger">*</span></label>
                                            <input type="tel" name="phone" class="form-control bg-light border-0 py-2" placeholder="VD: 0912 345 678" required>
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label fw-bold small text-muted">Hình thức nhận hàng <span class="text-danger">*</span></label>
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <label class="delivery-opt active h-100" id="opt-pickup" onclick="setDelivery('pickup')">
                                                    <input type="radio" name="delivery_method" value="pickup" checked>
                                                    <div>
                                                        <div class="fw-bold small">🏠 Đến lấy tại quán</div>
                                                        <div style="font-size:11px;color:#888"><?= htmlspecialchars($settings['address'] ?? 'Địa chỉ nhà hàng') ?></div>
                                                    </div>
                                                </label>
                                            </div>
                                            <div class="col-6">
                                                <label class="delivery-opt h-100" id="opt-ship" onclick="setDelivery('ship')">
                                                    <input type="radio" name="delivery_method" value="ship">
                                                    <div>
                                                        <div class="fw-bold small">🚚 Giao tận nơi</div>
                                                        <div style="font-size:11px;color:#888">Phí ship thỏa thuận</div>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-4" id="address-wrap" style="display:none">
                                        <label class="form-label fw-bold small text-muted">Địa chỉ chi tiết <span class="text-danger">*</span></label>
                                        <input type="text" name="address" id="address-input" class="form-control bg-light border-0 py-2" placeholder="Số nhà, đường, phường, quận, thành phố">
                                    </div>

                                    <div class="mb-0">
                                        <label class="form-label fw-bold small text-muted">Ghi chú thêm (Không bắt buộc)</label>
                                        <textarea name="note" class="form-control bg-light border-0" rows="3" placeholder="Yêu cầu đặc biệt, thời gian giao hàng mong muốn..."></textarea>
                                    </div>
                                </div>
                                
                                <!-- CỘT PHẢI: ORDER SUMMARY -->
                                <div class="col-lg-5 p-4 p-md-5 bg-white">
                                    <h5 class="fw-bold mb-4" style="color:var(--ink)">Tóm tắt đơn hàng</h5>
                                    <div class="checkout-summary">
                                        <div id="checkout-items" class="mb-4 border-bottom pb-4">
                                            <!-- Checkout items render here -->
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-4">
                                            <span class="fw-bold text-muted">TỔNG CỘNG</span>
                                            <span style="font-size:1.6rem; font-weight:700; color:var(--gold)" id="checkout-total">0đ</span>
                                        </div>
                                        
                                        <button type="submit" class="btn w-100 py-3 fw-bold rounded-pill shadow-sm" style="background:var(--gold);color:#000;border:none;font-size:1rem" id="btn-order">
                                            ✓ XÁC NHẬN ĐẶT HÀNG
                                        </button>
                                        <p class="text-center text-muted small mt-3 mb-0" style="line-height:1.6">
                                            Sau khi đặt, chúng tôi sẽ gọi xác nhận trong vòng 30 phút.
                                            <br>Hotline hỗ trợ: <strong><?= htmlspecialchars($settings['hotline']) ?></strong>
                                        </p>
                                    </div>
                                </div>
                                
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</section>

<!-- Hidden items container -->
<div id="cart-items-hidden" style="display:none"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    var cart = {};

    // Lấy thông tin sách từ card
    function getBookInfo(id) {
        var card = document.querySelector('.book-card[data-id="' + id + '"]');
        if (!card) return null;
        return {
            id: id,
            title: card.dataset.title,
            price: parseFloat(card.dataset.price),
            stock: parseInt(card.dataset.stock)
        };
    }

    function changeQty(id, delta) {
        var input = document.getElementById('qty-' + id);
        if (!input) return;
        var val = Math.max(1, Math.min(parseInt(input.max), parseInt(input.value) + delta));
        input.value = val;
    }

    function addToCart(id) {
        var qty = parseInt(document.getElementById('qty-' + id).value);
        var book = getBookInfo(id);
        if (!book) return;
        if ((cart[id] || 0) + qty > book.stock) {
            alert('Không đủ số lượng trong kho!');
            return;
        }
        cart[id] = (cart[id] || 0) + qty;
        renderCart();

        // Mở offcanvas giỏ hàng
        var offcanvasEl = document.getElementById('cartOffcanvas');
        var offcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl) || new bootstrap.Offcanvas(offcanvasEl);
        offcanvas.show();
    }

    function updateCartItem(id, delta) {
        var book = getBookInfo(id);
        if (!book) return;
        var newQty = (cart[id] || 0) + delta;
        if (newQty <= 0) {
            delete cart[id];
        } else if (newQty > book.stock) {
            alert('Không đủ số lượng trong kho!');
            return;
        } else {
            cart[id] = newQty;
        }
        renderCart();
    }

    function removeCartItem(id) {
        delete cart[id];
        renderCart();
    }

    function renderCart() {
        var total = 0, count = 0;
        var offcanvasHtml = '';
        var checkoutHtml = '';
        var hiddenHtml = '';

        for (var id in cart) {
            var book = getBookInfo(parseInt(id));
            if (!book || cart[id] <= 0) continue;
            var sub = book.price * cart[id];
            total += sub;
            count += cart[id];

            var imgElement = document.querySelector('.book-card[data-id="' + id + '"] img');
            var imgPath = imgElement ? imgElement.src : 'public/assets/img/books/default-book.jpg';

            // Offcanvas UI
            offcanvasHtml += `
            <div class="cart-item">
                <img src="${imgPath}" class="cart-item-img" alt="${escHtml(book.title)}">
                <div class="cart-item-info">
                    <div class="cart-item-title">${escHtml(book.title)}</div>
                    <div class="cart-item-price">${book.price.toLocaleString('vi-VN')}đ</div>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <div class="cart-item-qty">
                            <button onclick="updateCartItem(${id}, -1)">−</button>
                            <span style="font-size:14px;font-weight:600;width:24px;text-align:center">${cart[id]}</span>
                            <button onclick="updateCartItem(${id}, 1)">+</button>
                        </div>
                        <button class="cart-item-remove" onclick="removeCartItem(${id})"><i class="fas fa-trash"></i> Xóa</button>
                    </div>
                </div>
            </div>`;

            // Checkout Summary UI
            checkoutHtml += `
            <div class="d-flex justify-content-between mb-3" style="font-size:14px">
                <div><span class="text-muted">${cart[id]}x</span> <strong style="color:var(--ink)">${escHtml(book.title)}</strong></div>
                <div class="fw-bold">${sub.toLocaleString('vi-VN')}đ</div>
            </div>`;

            // Hidden inputs for form
            hiddenHtml += '<input type="hidden" name="items[' + id + ']" value="' + cart[id] + '">';
        }

        var countEl = document.getElementById('cart-count');
        var offcanvasItems = document.getElementById('offcanvas-items');
        var offcanvasTotal = document.getElementById('offcanvas-total');
        var btnProceed = document.getElementById('btn-proceed-checkout');
        
        var checkoutItems = document.getElementById('checkout-items');
        var checkoutTotal = document.getElementById('checkout-total');
        var hiddenEl = document.getElementById('cart-items-hidden');

        countEl.style.display = count > 0 ? 'flex' : 'none';
        countEl.textContent = count;

        if (offcanvasItems) {
            offcanvasItems.innerHTML = count > 0 ? offcanvasHtml : '<div class="text-center text-muted p-5 mt-5"><div><i class="fas fa-shopping-cart fa-3x mb-3 opacity-25"></i></div>Giỏ hàng trống</div>';
        }
        if (offcanvasTotal) offcanvasTotal.textContent = total.toLocaleString('vi-VN') + 'đ';
        if (btnProceed) btnProceed.disabled = (count === 0);

        if (checkoutItems) checkoutItems.innerHTML = checkoutHtml;
        if (checkoutTotal) checkoutTotal.textContent = total.toLocaleString('vi-VN') + 'đ';
        if (hiddenEl) hiddenEl.innerHTML = hiddenHtml;

        var existingInputs = document.querySelectorAll('#order-form input[name^="items["]');
        existingInputs.forEach(function(el) { el.remove(); });
        var form = document.getElementById('order-form');
        if (form) form.insertAdjacentHTML('beforeend', hiddenHtml);

        // Ẩn checkout section nếu xóa hết giỏ hàng (ngoại trừ đang hiện thông báo thành công/lỗi)
        if (count === 0 && document.getElementById('order-section').style.display !== 'none' && !'<?= $order_success ? "1" : "" ?>' && !'<?= $order_error ? "1" : "" ?>') {
            document.getElementById('order-section').style.display = 'none';
        }
    }

    function escHtml(str) {
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function setDelivery(type) {
        var wrap = document.getElementById('address-wrap');
        var input = document.getElementById('address-input');
        var optP = document.getElementById('opt-pickup');
        var optS = document.getElementById('opt-ship');
        optP.classList.toggle('active', type === 'pickup');
        optS.classList.toggle('active', type === 'ship');
        wrap.style.display = type === 'ship' ? 'block' : 'none';
        if (input) input.required = (type === 'ship');
    }

    function showCheckout() {
        var offcanvasEl = document.getElementById('cartOffcanvas');
        var offcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);
        if (offcanvas) offcanvas.hide();

        var orderSec = document.getElementById('order-section');
        orderSec.style.display = 'block';
        
        // Đợi offcanvas đóng một chút rồi scroll mượt xuống form
        setTimeout(function() {
            orderSec.scrollIntoView({ behavior: 'smooth' });
        }, 350);
    }
</script>

<?php include __DIR__ . '/views/client/layouts/footer.php'; ?>