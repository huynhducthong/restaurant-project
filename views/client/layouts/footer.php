<?php
require_once __DIR__ . '/../../../config/database.php';
$db = (new Database())->getConnection();

$stmt = $db->query("SELECT * FROM settings");
$settings = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['key_name']] = $row['key_value'];
}

// Các liên kết footer được giữ cố định
$links = [];

// Đảm bảo $path_prefix và safe_url tồn tại
$path_prefix = $path_prefix ?? '';
if (!function_exists('safe_url')) {
    function safe_url($url, $prefix)
    {
        if (empty($url))
            return '';
        // Nếu URL là tuyệt đối (bắt đầu bằng http, https, // hoặc /)
        if (preg_match('/^(https?:|\/\/|\/)/i', $url)) {
            return $url;
        }
        return $prefix . $url;
    }
}

$bgImg = !empty($settings['footer_bg_image']) ? safe_url('public/assets/img/' . $settings['footer_bg_image'], $path_prefix) : '';
$logo = !empty($settings['footer_logo']) ? safe_url('public/assets/img/' . $settings['footer_logo'], $path_prefix) : '';
$showSocial = true;
$showMap = !empty($settings['google_map_iframe']);
$showNews = true;
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500&family=Source+Sans+3:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap');

    <?php $text_color = '#1a1814'; ?>

    .footer {
        position: relative;
        padding: 80px 0 40px;
        background-color: #E6E5E3;
        color:
            <?= $text_color ?>
        ;
        border-top: 1px solid rgba(200, 155, 92, 0.25);
        <?php if ($bgImg): ?>
            background: url('<?= $bgImg ?>') center/cover no-repeat fixed;
        <?php endif; ?>
    }

    <?php if ($bgImg): ?>
        .footer::before {
            content: '';
            position: absolute;
            inset: 0;
            /* Gradient nửa sáng nửa tối từ trên xuống (trung hòa ở trên, tối dần ở dưới) */
            background: linear-gradient(to bottom, 
                rgba(230, 229, 227, 0.35) 0%, 
                rgba(230, 229, 227, 0.65) 50%, 
                rgba(12, 11, 9, 0.92) 100%
            );
            z-index: 1;
        }

        /* Đảm bảo độ tương phản tốt cho phần bottom-bar khi nền tối dần */
        .footer .bottom-bar {
            border-top: 1px solid rgba(255, 255, 255, 0.15) !important;
            color: rgba(255, 255, 255, 0.85) !important;
        }
        .footer .bottom-bar a {
            color: rgba(255, 255, 255, 0.85) !important;
        }
        .footer .bottom-bar a:hover {
            color: #C89B5C !important;
        }
    <?php endif; ?>
    .footer .container {
        position: relative;
        z-index: 2;
    }

    .footer h4 {
        font-size: 14px;
        letter-spacing: 2px;
        text-transform: uppercase;
        color: inherit;
        opacity: 0.8;
        margin-bottom: 25px;
        font-weight: 700;
    }

    .footer p,
    .footer a,
    .footer li {
        font-size: 17px;
        font-weight: 600;
        color: inherit;
        line-height: 1.8;
        text-decoration: none;
        transition: opacity 0.3s;
        margin-bottom: 5px;
    }

    .footer a:hover {
        color: #C89B5C !important;
        opacity: 1;
    }

    .footer-logo {
        max-height: 80px;
        margin-bottom: 25px;
    }

    .social-icons {
        margin-top: 25px;
    }

    .social-icons a {
        display: inline-block;
        margin-right: 15px;
        font-size: 18px;
        color: inherit;
    }

    .explore-links a {
        display: block;
        margin-bottom: 12px;
    }

    .contact-info {
        margin-bottom: 25px;
    }

    /* Map Overlay */
    .map-wrapper {
        position: relative;
        width: 100%;
        height: 150px;
        border-radius: 4px;
        overflow: hidden;
        margin-top: 10px;
    }

    .map-wrapper iframe {
        width: 100%;
        height: 100%;
        border: none;
    }

    .map-overlay {
        position: absolute;
        inset: 0;
        background: rgba(17, 63, 54, 0.7);
        backdrop-filter: blur(2px);
        z-index: 2;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: opacity 0.3s ease;
    }

    .map-overlay-message {
        background: #FFFFFF;
        color: #113f36;
        padding: 10px 20px;
        border-radius: 4px;
        font-size: 12px;
        font-family: 'Source Sans 3', sans-serif;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        pointer-events: none;
    }

    .map-wrapper.active .map-overlay {
        opacity: 0;
        pointer-events: none;
    }

    .newsletter-form {
        margin-top: 10px;
    }

    .newsletter-form input {
        width: 100%;
        padding: 12px 0;
        border: none;
        border-bottom: 1px solid currentColor;
        background: transparent;
        color: inherit;
        outline: none;
        margin-bottom: 15px;
        font-family: 'Source Sans 3', sans-serif;
        font-size: 17px;
        font-weight: 600;
        opacity: 0.8;
    }

    .newsletter-form input::placeholder {
        color: inherit;
        opacity: 0.6;
    }

    .newsletter-form button {
        background:
            <?= $text_color ?>
        ;
        color: #113f36;
        width: 100%;
        border: none;
        padding: 14px 20px;
        border-radius: 4px;
        font-weight: 600;
        cursor: pointer;
        font-family: 'Source Sans 3', sans-serif;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        font-size: 12px;
        transition: opacity 0.3s;
    }

    .newsletter-form button:hover {
        opacity: 0.8;
    }

    .bottom-bar {
        border-top: 1px solid currentColor;
        opacity: 0.8;
        margin-top: 60px;
        padding-top: 25px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-family: 'Source Sans 3', sans-serif;
        font-size: 14px;
        color: inherit;
    }

    @media (max-width: 768px) {
        .bottom-bar {
            flex-direction: column;
            gap: 15px;
            text-align: center;
        }
    }
</style>

<footer class="footer">
    <div class="container">
        <div class="row gy-5">
            <!-- Cột 1: Thông tin liên hệ -->
            <div class="col-lg-3 col-md-6">
                <?php if ($logo): ?>
                    <img src="<?= htmlspecialchars($logo) ?>" alt="Logo" class="footer-logo">
                <?php else: ?>
                    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 25px;">
                        <h4
                            style="margin: 0; font-family: 'Source Sans 3', sans-serif; font-size: 24px; text-transform: none; letter-spacing: normal; color: inherit; line-height: 1.2;">
                            <?= htmlspecialchars($settings['restaurant_name'] ?? 'Restaurantly') ?>
                        </h4>
                    </div>
                <?php endif; ?>

                <div class="contact-info" style="margin-bottom: 25px;">
                    <?php if (!empty($settings['address'])): ?>
                        <p style=""><?= htmlspecialchars($settings['address']) ?></p><?php endif; ?>
                    <?php if (!empty($settings['hotline'])): ?>
                        <p style=" margin-top: 15px;"><?= htmlspecialchars($settings['hotline']) ?></p><?php endif; ?>
                    <p style=""><a href="mailto:contact@restaurantly.com">contact@restaurantly.com</a></p>
                </div>

                <?php if ($showSocial): ?>
                    <div class="social-icons" style="margin-top:0">
                        <a href="https://facebook.com" target="_blank"><i class="fab fa-facebook-f"></i></a>
                        <a href="https://instagram.com" target="_blank"><i class="fab fa-instagram"></i></a>
                        <a href="https://tiktok.com" target="_blank"><i class="fab fa-tiktok"></i></a>
                        <a href="https://zalo.me" target="_blank"><i class="fab fa-zalo"></i></a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Cột 2: Quick Links & Thực Đơn -->
            <div class="col-lg-3 col-md-6">
                <h4>LIÊN KẾT NHANH</h4>
                <div class="explore-links" style="margin-bottom: 30px;">
                    <?php foreach ($links as $l): ?>
                        <a href="<?= htmlspecialchars(safe_url($l['url'], $path_prefix)) ?>"
                            style=""><?= htmlspecialchars($l['title']) ?></a>
                    <?php endforeach; ?>
                    <?php if (empty($links)): ?>
                        <a href="<?= safe_url('index.php', $path_prefix) ?>" style="">Trang chủ</a>
                        <a href="<?= safe_url('about/index.php', $path_prefix) ?>" style="">Về chúng tôi</a>
                        <a href="<?= safe_url('contact.php', $path_prefix) ?>" style="">Liên hệ</a>
                    <?php endif; ?>
                </div>

                <h4>THỰC ĐƠN</h4>
                <div class="explore-links">
                    <a href="<?= safe_url('menu.php', $path_prefix) ?>#starters" style="">Món khai vị (Appetizers)</a>
                    <a href="<?= safe_url('menu.php', $path_prefix) ?>#main" style="">Món chính (Main Courses)</a>
                    <a href="<?= safe_url('menu.php', $path_prefix) ?>#seafood" style="">Hải sản tươi sống (Seafood)</a>
                    <a href="<?= safe_url('menu.php', $path_prefix) ?>#bbq" style="">Đồ nướng (BBQ & Grill)</a>
                    <a href="<?= safe_url('menu.php', $path_prefix) ?>#vegetarian" style="">Món chay (Vegetarian)</a>
                    <a href="<?= safe_url('menu.php', $path_prefix) ?>#desserts" style="">Tráng miệng (Desserts)</a>
                    <a href="<?= safe_url('menu.php', $path_prefix) ?>#drinks" style="">Đồ uống & Rượu vang (Drinks)</a>
                </div>
            </div>

            <!-- Cột 3: Dịch vụ & Mô tả -->
            <div class="col-lg-3 col-md-6">
                <h4>DỊCH VỤ</h4>
                <div class="explore-links" style="margin-bottom: 30px;">
                    <a href="<?= safe_url('services.php', $path_prefix) ?>#private" style="">Đặt tiệc cá nhân (Private
                        Dining)</a>
                    <a href="<?= safe_url('services.php', $path_prefix) ?>#events" style="">Tiệc cưới & Sự kiện
                        (Events)</a>
                    <a href="<?= safe_url('services.php', $path_prefix) ?>#catering" style="">Phục vụ tận nơi
                        (Catering)</a>
                    <a href="<?= safe_url('services.php', $path_prefix) ?>#delivery" style="">Giao hàng (Delivery)</a>
                </div>

                <h4>MÔ TẢ</h4>
                <p style=" line-height: 1.8; color: inherit;">Trải nghiệm ẩm thực tuyệt vời với không gian sang trọng và
                    các món ăn được chế biến từ nguyên liệu tươi ngon nhất.</p>
            </div>

            <!-- Cột 4: Giờ mở cửa & Đặt bàn -->
            <div class="col-lg-3 col-md-6">
                <h4>GIỜ MỞ CỬA</h4>
                <div class="contact-info" style=" line-height: 2;">
                    <?php if (!empty($settings['open_time']) && !empty($settings['open_days'])): ?>
                        <div style="display: flex; justify-content: space-between;">
                            <span><?= htmlspecialchars($settings['open_days']) ?></span>
                            <span><?= htmlspecialchars($settings['open_time']) ?></span></div>
                    <?php else: ?>
                        <div style="display: flex; justify-content: space-between;"><span>Thứ 2</span> <span>Nghỉ định
                                kỳ</span></div>
                        <div style="display: flex; justify-content: space-between;"><span>Thứ 3 - Thứ 6</span> <span>10:00
                                AM - 10:00 PM</span></div>
                        <div style="display: flex; justify-content: space-between;"><span>Thứ 7 - CN</span> <span>09:00 AM -
                                11:00 PM</span></div>
                        <div style="display: flex; justify-content: space-between;"><span>Ngày Lễ</span> <span>Theo lịch đặt
                                trước</span></div>
                    <?php endif; ?>
                </div>

                <a href="<?= safe_url('booking_service.php?type=table', $path_prefix ?? '') ?>"
                    style="display: block; text-align: center; background: #FFFFFF; color: #113f36; padding: 12px; border-radius: 6px; font-family: 'Source Sans 3', sans-serif; font-weight: 600; margin-top: 30px; text-decoration: none; transition: background 0.3s;"
                    onmouseover="this.style.background='#e0e0e0'" onmouseout="this.style.background='#fff'">
                    Đặt Bàn Ngay (Book a Table)
                </a>

                <?php if ($showMap && !empty($settings['google_map_iframe'])): ?>
                    <div class="map-wrapper mt-4" id="footerMapWrapper">
                        <?= $settings['google_map_iframe'] ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="bottom-bar">
            <div>© 2026 Restaurantly. All Rights Reserved.</div>
            <div>
                <a href="#">Điều khoản sử dụng</a>
                <span style="margin: 0 10px;">|</span>
                <a href="#">Chính sách bảo mật</a>
            </div>
        </div>
    </div>
</footer>

<!-- FLOATING GLOBAL CHAT WIDGET -->
<div class="global-chat-widget">
    <div class="chat-widget-content">
        <span class="chat-eyebrow">RESTAURANTLY</span>
        <h3 class="chat-heading">Trải nghiệm fine dining hôm nay</h3>
        <button onclick="openChatModal()" class="btn-chat-gold">TRÒ CHUYỆN</button>
    </div>
</div>

<?php
$is_logged_in_chat = isset($_SESSION['user_id']);
$chat_user_name = '';
$chat_user_phone = '';
if ($is_logged_in_chat) {
    try {
        $stmt_c = $db->prepare("SELECT full_name, phone FROM users WHERE id = ?");
        $stmt_c->execute([$_SESSION['user_id']]);
        $u_chat = $stmt_c->fetch(PDO::FETCH_ASSOC);
        if ($u_chat) {
            $chat_user_name = $u_chat['full_name'];
            $chat_user_phone = $u_chat['phone'] ?? '0000000000';
        }
    } catch (PDOException $e) {
    }
}
?>

<!-- CHAT MODAL -->
<div class="chat-modal-overlay" id="chatModalOverlay" style="display: none;">
    <div class="chat-modal-container">
        <div class="chat-header">
            <div>
                <h5 class="m-0" style="font-family: 'Source Sans 3', sans-serif; color: #C19A5B;">Restaurantly Support
                </h5>
                <small id="chatStatusText" style="color: #bbb;">Trợ lý ảo thông minh</small>
            </div>
            <div style="display: flex; gap: 12px; align-items: center;">
                <button class="chat-close-btn" onclick="clearChatHistory()" title="Xóa lịch sử trò chuyện"
                    style="font-size: 14px; opacity: 0.8; color: #ccc;"><i class="fas fa-trash-alt"></i></button>
                <button class="chat-close-btn" onclick="closeChatModal()"><i class="fas fa-times"></i></button>
            </div>
        </div>

        <div class="chat-body" id="chatBody">
            <div id="chatMessages" class="chat-messages-container">
                <!-- Messages will be injected here -->
            </div>

            <div id="chatInitForm" class="chat-init-form">
                <?php if (!$is_logged_in_chat): ?>
                    <div style="padding: 40px 20px; text-align: center;">
                        <i class="fas fa-user-lock fa-3x" style="color: #ccc; margin-bottom: 20px;"></i>
                        <p class="mb-4" style="font-size: 17px; font-weight: 600; color: #555;">Vui lòng đăng nhập để sử
                            dụng tính năng trò chuyện trực tuyến với nhà hàng.</p>
                        <a href="<?= safe_url('public/login.php', $path_prefix ?? '') ?>" class="btn btn-chat-gold w-100"
                            style="text-decoration: none; padding: 12px; display: block; border-radius: 6px;">ĐĂNG NHẬP
                            NGAY</a>
                    </div>
                <?php else: ?>
                    <div class="text-center" style="padding: 50px 20px;">
                        <i class="fas fa-spinner fa-spin fa-2x" style="color: #C19A5B;"></i>
                        <p class="mt-3" style="color: #666;">Đang kết nối...</p>
                        <input type="hidden" id="chatCustomerName"
                            value="<?= htmlspecialchars($chat_user_name, ENT_QUOTES) ?>">
                        <input type="hidden" id="chatCustomerPhone"
                            value="<?= htmlspecialchars($chat_user_phone, ENT_QUOTES) ?>">
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="chat-footer" id="chatFooter" style="display: none;">
            <div class="typing-indicator" id="typingIndicator" style="display: none;">
                <span></span><span></span><span></span> <small class="ms-2 text-muted" id="typingText">Bot đang soạn câu
                    trả lời...</small>
            </div>

            <div class="customer-quick-replies pb-2 px-3 pt-2"
                style="background: #f8f9fa; border-top: 1px solid #eee; overflow-x: auto; white-space: nowrap;">
                <button type="button" class="btn btn-sm btn-outline-secondary me-1"
                    style="border-radius: 20px; font-size: 12px; padding: 4px 10px;"
                    onclick="sendCustomerQuickReply('Tôi muốn đặt bàn')">Tôi muốn đặt bàn</button>
                <button type="button" class="btn btn-sm btn-outline-secondary me-1"
                    style="border-radius: 20px; font-size: 12px; padding: 4px 10px;"
                    onclick="sendCustomerQuickReply('Thực đơn nhà hàng có gì?')">Xem thực đơn</button>
                <button type="button" class="btn btn-sm btn-outline-secondary me-1"
                    style="border-radius: 20px; font-size: 12px; padding: 4px 10px;"
                    onclick="sendCustomerQuickReply('Gặp nhân viên hỗ trợ')">Gặp nhân viên</button>
            </div>
            <div class="d-flex align-items-center gap-2">
                <input type="file" id="chatImageInput" accept="image/jpeg, image/png" style="display: none;"
                    onchange="sendChatImage(this)">
                <button class="chat-attach-btn" onclick="document.getElementById('chatImageInput').click()"><i
                        class="fas fa-paperclip"></i></button>
                <input type="text" id="chatInputMessage" class="form-control chat-input" placeholder="Nhập tin nhắn..."
                    onkeypress="if(event.key === 'Enter') sendChatMessage()">
                <button class="chat-send-btn" onclick="sendChatMessage()"><i class="fas fa-paper-plane"></i></button>
            </div>
        </div>
    </div>
</div>

<style>
    /* === CHAT MODAL === */
    .chat-modal-overlay {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 10000;
        display: flex;
        align-items: flex-end;
        justify-content: flex-end;
    }

    .chat-modal-container {
        background: #fff;
        width: 360px;
        height: 550px;
        border-radius: 12px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        animation: slideUp 0.3s ease;
    }

    @keyframes slideUp {
        from {
            transform: translateY(50px);
            opacity: 0;
        }

        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .chat-header {
        background: #0d1a16;
        padding: 15px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .chat-close-btn {
        background: none;
        border: none;
        color: #fff;
        font-size: 18px;
        cursor: pointer;
    }

    .chat-body {
        flex-grow: 1;
        overflow-y: auto;
        background: #f8f9fa;
        position: relative;
    }

    .chat-init-form {
        padding: 30px 20px;
    }

    .chat-messages-container {
        padding: 15px;
        display: none;
        flex-direction: column;
        gap: 10px;
    }

    .chat-msg {
        position: relative;
        max-width: 80%;
        padding: 10px 14px;
        border-radius: 18px;
        font-size: 14px;
        line-height: 1.4;
        word-wrap: break-word;
        margin-bottom: 5px;
    }

    .chat-msg.customer {
        background: #C19A5B;
        color: #fff;
        align-self: flex-end;
        border-bottom-right-radius: 4px;
    }

    .chat-msg.bot {
        background: #e9ecef;
        color: #333;
        align-self: flex-start;
        border-bottom-left-radius: 4px;
    }

    .chat-msg.admin {
        background: #113f36;
        color: #fff;
        align-self: flex-start;
        border-bottom-left-radius: 4px;
    }

    .chat-msg img {
        max-width: 100%;
        border-radius: 8px;
        margin-top: 5px;
    }

    .chat-msg .msg-delete-icon {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        font-size: 14px;
        color: #dc3545;
        cursor: pointer;
        opacity: 0;
        transition: opacity 0.2s;
        background: #fff;
        border-radius: 50%;
        padding: 5px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        z-index: 10;
    }

    .chat-msg.customer .msg-delete-icon {
        left: -32px;
    }

    .chat-msg.bot .msg-delete-icon,
    .chat-msg.admin .msg-delete-icon {
        right: -32px;
    }

    .chat-msg:hover .msg-delete-icon {
        opacity: 1;
    }

    .chat-footer {
        padding: 12px 15px;
        background: #fff;
        border-top: 1px solid #eee;
        position: relative;
    }

    .chat-input {
        border-radius: 20px;
        background: #f1f3f5;
        border: none;
        padding-left: 15px;
    }

    .chat-input:focus {
        box-shadow: none;
        border: 1px solid #C19A5B;
    }

    .chat-send-btn,
    .chat-attach-btn {
        background: none;
        border: none;
        color: #113f36;
        font-size: 18px;
        cursor: pointer;
        transition: color 0.2s;
    }

    .chat-send-btn:hover {
        color: #C19A5B;
    }

    .typing-indicator {
        position: absolute;
        top: -25px;
        left: 15px;
        display: flex;
        align-items: center;
    }

    .typing-indicator span {
        display: inline-block;
        width: 6px;
        height: 6px;
        background: #C19A5B;
        border-radius: 50%;
        margin: 0 2px;
        animation: bounce 1.4s infinite ease-in-out both;
    }

    .typing-indicator span:nth-child(1) {
        animation-delay: -0.32s;
    }

    .typing-indicator span:nth-child(2) {
        animation-delay: -0.16s;
    }

    @keyframes bounce {

        0%,
        80%,
        100% {
            transform: scale(0);
        }

        40% {
            transform: scale(1);
        }
    }

    @media (max-width: 500px) {
        .chat-modal-container {
            width: 100%;
            height: 100%;
            border-radius: 0;
        }
    }
</style>

<style>
    /* === FLOATING CHAT WIDGET === */
    .global-chat-widget {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 9999;
        background-color: #F9F9F9;
        /* Obsidian */
        width: 180px;
        /* Scaled down */
        padding: 15px;
        /* Scaled down */
        border: 1px solid rgba(168, 135, 70, 0.4);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
    }

    .chat-widget-content {
        border: 1px solid rgba(168, 135, 70, 0.2);
        padding: 0;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
    }

    .chat-eyebrow {
        font-family: 'Source Sans 3', sans-serif;
        font-size: 8px;
        /* Scaled down */
        letter-spacing: 1px;
        color: #A88746;
        /* Gold */
        text-transform: uppercase;
        margin-bottom: 5px;
    }

    .chat-heading {
        font-family: 'Source Sans 3', sans-serif;
        font-size: 1rem;
        /* Scaled down */
        color: #222222;
        /* Soft Ash */
        font-weight: 400;
        line-height: 1.2;
        margin-bottom: 12px;
    }

    .btn-chat-gold {
        display: inline-block;
        background-color: #A88746;
        color: #F9F9F9;
        font-family: 'Source Sans 3', sans-serif;
        font-size: 10px;
        /* Scaled down */
        font-weight: 600;
        letter-spacing: 1px;
        text-transform: uppercase;
        padding: 8px 12px;
        /* Scaled down */
        text-decoration: none;
        transition: all 0.3s ease;
        width: 100%;
        text-align: center;
        border: none;
    }

    .btn-chat-gold:hover {
        background-color: #d6af70;
        color: #000;
    }

    @media (max-width: 768px) {
        .global-chat-widget {
            width: 150px;
            padding: 12px;
            bottom: 15px;
            right: 15px;
        }

        .chat-heading {
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .btn-chat-gold {
            font-size: 9px;
            padding: 6px 10px;
        }
    }
</style>

<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
    function activateFooterMap() {
        document.getElementById('footerMapWrapper').classList.add('active');
    }

    /* === CHAT LOGIC === */
    let chatSessionId = localStorage.getItem('restaurantly_chat_session_id') || '';
    let chatPollingInterval = null;
    let lastMessageId = 0;

    function openChatModal() {
        document.getElementById('chatModalOverlay').style.display = 'flex';
        if (chatSessionId) {
            document.getElementById('chatInitForm').style.display = 'none';
            document.getElementById('chatMessages').style.display = 'flex';
            document.getElementById('chatFooter').style.display = 'block';
            loadChatMessages();
            startChatPolling();
        } else {
            <?php if ($is_logged_in_chat): ?>
                // Tự động bắt đầu chat nếu đã đăng nhập
                initChatSession();
            <?php endif; ?>
        }
    }

    function closeChatModal() {
        document.getElementById('chatModalOverlay').style.display = 'none';
        if (chatPollingInterval) clearInterval(chatPollingInterval);
    }

    function clearChatHistory() {
        if (confirm('Bạn có chắc chắn muốn xóa lịch sử trò chuyện trên thiết bị này không?')) {
            localStorage.removeItem('restaurantly_chat_session_id');
            chatSessionId = '';
            document.getElementById('chatMessages').innerHTML = '';
            lastMessageId = 0;
            if (chatPollingInterval) clearInterval(chatPollingInterval);

            <?php if ($is_logged_in_chat): ?>
                // Tạo phiên mới ngay lập tức
                initChatSession();
            <?php else: ?>
                // Quay về form đăng nhập
                document.getElementById('chatMessages').style.display = 'none';
                document.getElementById('chatFooter').style.display = 'none';
                document.getElementById('chatInitForm').style.display = 'block';
            <?php endif; ?>
        }
    }

    function deleteSingleMessage(msgId) {
        if (!msgId) return;
        let deletedMsgs = JSON.parse(localStorage.getItem('deleted_msgs_' + chatSessionId) || '[]');
        if (!deletedMsgs.includes(msgId)) {
            deletedMsgs.push(msgId);
            localStorage.setItem('deleted_msgs_' + chatSessionId, JSON.stringify(deletedMsgs));
        }
        const msgDiv = document.getElementById('chat_msg_' + msgId);
        if (msgDiv) {
            msgDiv.remove();
        }
    }

    function initChatSession() {
        const nameInput = document.getElementById('chatCustomerName');
        const phoneInput = document.getElementById('chatCustomerPhone');

        if (!nameInput || !phoneInput) return;

        const name = nameInput.value.trim() || 'Khách hàng';
        const phone = phoneInput.value.trim() || '0000000000';

        // Tạo session tạm
        chatSessionId = 'sess_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
        localStorage.setItem('restaurantly_chat_session_id', chatSessionId);

        document.getElementById('chatInitForm').style.display = 'none';
        document.getElementById('chatMessages').style.display = 'flex';
        document.getElementById('chatFooter').style.display = 'block';

        // Gửi yêu cầu khởi tạo session lên API
        fetch('api/chat/client_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=init_session&session_id=${chatSessionId}&name=${encodeURIComponent(name)}&phone=${encodeURIComponent(phone)}`
        }).then(res => res.json()).then(data => {
            loadChatMessages();
            startChatPolling();
        }).catch(err => console.error(err));
    }

    function showTyping(text = 'Bot đang soạn câu trả lời...') {
        document.getElementById('typingText').innerText = text;
        document.getElementById('typingIndicator').style.display = 'flex';
    }

    function hideTyping() {
        document.getElementById('typingIndicator').style.display = 'none';
    }

    function appendMessage(sender, type, content, msgId) {
        let deletedMsgs = JSON.parse(localStorage.getItem('deleted_msgs_' + chatSessionId) || '[]');
        if (msgId && deletedMsgs.includes(msgId)) return; // Không hiển thị tin nhắn đã xóa
        if (msgId && document.getElementById('chat_msg_' + msgId)) return; // Tránh trùng lặp tin nhắn

        const msgDiv = document.createElement('div');
        msgDiv.className = `chat-msg ${sender}`;
        if (msgId) msgDiv.id = 'chat_msg_' + msgId;

        let innerHtml = '';
        if (type === 'image') {
            innerHtml = `<img src="${content}" alt="Image">`;
        } else {
            // Render raw string or escape it if you prefer, currently just assigning it later or using innerText
            innerHtml = `<span></span>`;
        }

        if (msgId) {
            innerHtml += `<i class="fas fa-trash-alt msg-delete-icon" onclick="deleteSingleMessage(${msgId})" title="Xóa tin nhắn này"></i>`;
        }

        msgDiv.innerHTML = innerHtml;

        // Set actual text safely
        if (type !== 'image') {
            msgDiv.querySelector('span').innerText = content;
        }

        document.getElementById('chatMessages').appendChild(msgDiv);
        const body = document.getElementById('chatBody');
        body.scrollTop = body.scrollHeight;
    }

    function sendChatMessage() {
        const input = document.getElementById('chatInputMessage');
        const msg = input.value.trim();
        if (!msg || !chatSessionId) return;

        input.value = '';
        // Xóa dòng appendMessage tạm thời để tránh bị nhân đôi tin nhắn khi loadChatMessages gọi về
        showTyping('Đang gửi...');

        fetch('api/chat/client_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=send_message&session_id=${chatSessionId}&content=${encodeURIComponent(msg)}`
        }).then(() => loadChatMessages());
    }

    function sendCustomerQuickReply(text) {
        const input = document.getElementById('chatInputMessage');
        input.value = text;
        sendChatMessage();
    }

    function sendChatImage(input) {
        if (!input.files || input.files.length === 0) return;
        const file = input.files[0];
        if (file.size > 2 * 1024 * 1024) {
            alert('Vui lòng chọn ảnh < 2MB');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'upload_image');
        formData.append('session_id', chatSessionId);
        formData.append('image', file);

        showTyping('Đang tải ảnh lên...');

        fetch('api/chat/client_api.php', {
            method: 'POST',
            body: formData
        }).then(res => res.json()).then(data => {
            input.value = '';
            loadChatMessages();
        }).catch(err => console.error(err));
    }

    function loadChatMessages() {
        fetch(`api/chat/client_api.php?action=get_messages&session_id=${chatSessionId}&last_id=${lastMessageId}`)
            .then(res => {
                if (res.status === 304) return null; // Not modified
                return res.json();
            })
            .then(data => {
                if (!data) return;
                if (data.status === 'invalid_session') {
                    localStorage.removeItem('restaurantly_chat_session_id');
                    location.reload();
                    return;
                }
                if (data.status === 'success' && data.messages.length > 0) {
                    hideTyping();
                    data.messages.forEach(m => {
                        appendMessage(m.sender_type, m.message_type, m.content, m.id);
                        lastMessageId = Math.max(lastMessageId, m.id);
                    });
                }
                if (data.session_status === 'waiting_agent') {
                    document.getElementById('chatStatusText').innerText = 'Đang chờ nhân viên kết nối...';
                } else if (data.session_status === 'agent_handling') {
                    document.getElementById('chatStatusText').innerText = 'Nhân viên đang hỗ trợ';
                } else if (data.session_status === 'bot_handling') {
                    document.getElementById('chatStatusText').innerText = 'Trợ lý ảo thông minh';
                } else if (data.session_status === 'closed') {
                    document.getElementById('chatStatusText').innerText = 'Phiên chat đã kết thúc';
                }

                if (data.is_admin_typing) {
                    showTyping('Nhân viên đang nhập...');
                }
            }).catch(err => console.error(err));
    }

    function startChatPolling() {
        if (!window.chatPusherInitialized) {
            var pusher = new Pusher('cfbc6305339f352b0089', { cluster: 'ap1' });
            var channel = pusher.subscribe('chat-channel');
            channel.bind('chat_updated', function (data) {
                if ((!data.session_id || data.session_id === chatSessionId) && document.getElementById('chatModalOverlay').style.display !== 'none') {
                    loadChatMessages();
                }
            });
            channel.bind('message_hidden', function (data) {
                if (data.session_id === chatSessionId) {
                    let msgDiv = document.getElementById('chat_msg_' + data.message_id);
                    if (msgDiv) msgDiv.style.display = 'none';
                }
            });
            channel.bind('message_unhidden', function (data) {
                if (data.session_id === chatSessionId) {
                    document.getElementById('chatMessages').innerHTML = '';
                    lastMessageId = 0;
                    loadChatMessages();
                }
            });
            window.chatPusherInitialized = true;
        }
    }
</script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">