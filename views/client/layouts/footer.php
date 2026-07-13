<?php
require_once __DIR__ . "/../../../config/database.php";
$db = (new Database())->getConnection();

$stmt = $db->query("SELECT * FROM settings");
$settings = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings["$row[key_name]"] = $row["key_value"];
}

$path_prefix = $path_prefix ?? "";
if (!function_exists("safe_url")) {
    function safe_url($url, $prefix) {
        if (empty($url)) return "";
        if (preg_match("/^(https?:|\/\/|\/)/i", $url)) return $url;
        return $prefix . $url;
    }
}

$showSocial = true;
$showMap = !empty($settings["google_map_iframe"]);

$f_img_1 = !empty($settings["footer_img_1"]) ? safe_url("public/assets/img/" . $settings["footer_img_1"], $path_prefix) : safe_url("public/assets/img/default_footer_1.jpg", $path_prefix);
$f_img_2 = !empty($settings["footer_img_2"]) ? safe_url("public/assets/img/" . $settings["footer_img_2"], $path_prefix) : safe_url("public/assets/img/default_footer_2.jpg", $path_prefix);
$f_img_3 = !empty($settings["footer_img_3"]) ? safe_url("public/assets/img/" . $settings["footer_img_3"], $path_prefix) : safe_url("public/assets/img/default_footer_3.jpg", $path_prefix);
?>
<style>
    .new-footer {
        background-color: #0c0c0c;
        color: #fff;
        font-family: "Source Sans 3", sans-serif;
    }
    .footer-top-grid {
        display: flex;
        flex-wrap: wrap;
        background: #111;
    }
    .footer-top-left {
        flex: 1 1 400px;
        display: flex;
        align-items: center;
        padding: 60px;
    }
    .footer-top-left h2 {
        font-family: "Cormorant Garamond", serif;
        font-size: 3.5rem;
        color: #fff;
        margin: 0;
        line-height: 1.2;
    }
    .map-wrapper iframe {
        width: 100% !important;
        height: 100% !important;
        border: none;
        filter: grayscale(100%) invert(92%) contrast(83%) hue-rotate(180deg);
    }
    .footer-top-right {
        flex: 1 1 600px;
        display: grid;
        grid-template-columns: repeat(3, 1fr);
    }
    .footer-top-right img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        aspect-ratio: 1/1;
        transition: transform 0.5s ease;
    }
    .footer-ig-box {
        overflow: hidden;
        position: relative;
    }
    .footer-ig-box:hover img {
        transform: scale(1.05);
    }
    .footer-main {
        padding: 80px 20px 40px;
        max-width: 1200px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 30px;
        text-align: center;
    }
    .footer-col-left { text-align: center; }
    .footer-col-left ul {
        list-style: none;
        padding: 0;
        margin: 0 0 20px 0;
    }
    .footer-col-left li { margin-bottom: 12px; }
    .footer-col-left a, .footer-col-right a {
        color: #ccc;
        text-decoration: none;
        font-size: 14px;
        transition: color 0.3s;
    }
    .footer-col-left a:hover, .footer-col-right a:hover {
        color: #C19A5B;
    }
    .footer-michelin {
        display: flex;
        justify-content: center;
        gap: 15px;
    }
    .footer-michelin img { width: 35px; opacity: 0.8; }
    
    .footer-col-center .footer-logo-text {
        font-family: "Cormorant Garamond", serif;
        font-size: 4rem;
        color: #C19A5B;
        font-style: italic;
        line-height: 1;
        margin-bottom: 20px;
        display: inline-block;
    }
    .footer-socials {
        display: flex;
        justify-content: center;
        gap: 20px;
    }
    .footer-socials a {
        color: #fff;
        font-size: 1.2rem;
        transition: color 0.3s;
    }
    .footer-socials a:hover {
        color: #C19A5B;
    }
    
    .footer-col-right { text-align: center; }
    .footer-col-right h4 {
        font-family: "Cormorant Garamond", serif;
        color: #fff;
        font-size: 1.5rem;
        margin-bottom: 20px;
    }
    .footer-col-right p {
        color: #ccc;
        font-size: 14px;
        margin-bottom: 8px;
        line-height: 1.6;
    }
    
    .footer-bottom {
        border-top: 1px solid rgba(255,255,255,0.1);
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 12px;
        color: #777;
        max-width: 1200px;
        margin: 0 auto;
    }
    .footer-bottom-links a {
        color: #777;
        text-decoration: none;
        margin-left: 15px;
        transition: color 0.3s;
    }
    .footer-bottom-links a:hover {
        color: #C19A5B;
    }

    @media (max-width: 900px) {
        .footer-main {
            grid-template-columns: 1fr;
            gap: 50px;
        }
        .footer-bottom {
            flex-direction: column;
            gap: 15px;
            text-align: center;
        }
        .footer-top-left { padding: 40px 20px; text-align: center; justify-content: center; }
        .footer-top-left h2 { font-size: 2.5rem; }
        .footer-top-left, .footer-top-right {
            flex: 1 1 100% !important;
            max-width: 100% !important;
        }
    }
</style>

<footer class="new-footer">
    <div class="footer-top-grid">
        <div class="footer-top-left" style="position: relative; overflow: hidden;">
            <?php if (!empty($settings["google_map_iframe"])): ?>
                <div class="map-wrapper" style="position: absolute; inset: 0; width: 100%; height: 100%; z-index: 1;">
                    <?= $settings["google_map_iframe"] ?>
                </div>
                <div style="position: absolute; inset: 0; background: linear-gradient(to right, rgba(17,17,17,0.95) 0%, rgba(17,17,17,0.4) 100%); z-index: 2; pointer-events: none;"></div>
            <?php endif; ?>
            <h2 style="position: relative; z-index: 3;">Điểm Đến</h2>
        </div>
        <div class="footer-top-right">
            <div class="footer-ig-box"><img src="<?= $f_img_1 ?>" alt="Insta 1"></div>
            <div class="footer-ig-box"><img src="<?= $f_img_2 ?>" alt="Insta 2"></div>
            <div class="footer-ig-box"><img src="<?= $f_img_3 ?>" alt="Insta 3"></div>
        </div>
    </div>
    
    <div class="footer-main">
        <div class="footer-col-left">
            <ul>
                <li><a href="<?= safe_url("index.php", $path_prefix) ?>">Home</a></li>
                <li><a href="<?= safe_url("about.php", $path_prefix) ?>">About</a></li>
                <li><a href="<?= safe_url("booking_service.php", $path_prefix) ?>">Stay</a></li>
                <li><a href="<?= safe_url("menu.php", $path_prefix) ?>">Menu</a></li>
                <li><a href="<?= safe_url("contact.php", $path_prefix) ?>">Contact</a></li>
            </ul>
            <div class="footer-michelin">
                <i class="fas fa-certificate" style="color: #fff; font-size: 24px;"></i>
                <i class="fas fa-award" style="color: #fff; font-size: 24px;"></i>
            </div>
        </div>
        
        <div class="footer-col-center">
            <div class="footer-logo-text notranslate">
                <?= htmlspecialchars($settings["restaurant_name"] ?? "NHÃ") ?>.
            </div>
            <div class="footer-socials">
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-youtube"></i></a>
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fas fa-envelope"></i></a>
            </div>
        </div>
        
        <div class="footer-col-right">
            <h4>Contact</h4>
            <p>Restaurant <span class="notranslate"><?= htmlspecialchars($settings["restaurant_name"] ?? "NHÃ") ?></span>.<br>
            <?= htmlspecialchars($settings["address"] ?? "Kasteellaan 1\n5421 CB Gemert\nThe Netherlands") ?></p>
            <p style="margin-top: 15px;">
                <?= htmlspecialchars($settings["hotline"] ?? "+31 40 200 5955") ?><br>
                <?= htmlspecialchars($settings["email"] ?? "info@restaurantgem.com") ?>
            </p>
        </div>
    </div>
    
    <div class="footer-bottom">
        <div>&copy; 2026 Restaurant <span class="notranslate"><?= htmlspecialchars($settings["restaurant_name"] ?? "NHÃ") ?></span> - All rights reserved - Website by Unique Design</div>
        <div class="footer-bottom-links">
            <a href="#">Cookies</a>
            <a href="#">Privacy</a>
            <a href="#">Terms of service</a>
            <a href="#">Sitemap</a>
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

<!-- GSAP & ScrollTrigger -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
<!-- Lenis Smooth Scroll -->
<script src="https://unpkg.com/lenis@1.1.13/dist/lenis.min.js"></script>

<style>
/* Recommended Lenis CSS */
html.lenis, html.lenis body {
  height: auto;
}
.lenis.lenis-smooth {
  scroll-behavior: auto !important;
}
.lenis.lenis-smooth [data-lenis-prevent] {
  overscroll-behavior: contain;
}
.lenis.lenis-stopped {
  overflow: hidden;
}
.lenis.lenis-smooth iframe {
  pointer-events: none;
}
</style>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // 1. Initialize Lenis
    const lenis = new Lenis({
        lerp: 0.08, // Adjust for smoothness (lower is smoother but slower)
        smoothWheel: true,
    });

    // 2. Sync GSAP ScrollTrigger with Lenis
    lenis.on('scroll', ScrollTrigger.update);
    gsap.ticker.add((time) => {
        lenis.raf(time * 1000);
    });
    gsap.ticker.lagSmoothing(0);

    // 3. Register GSAP Plugins
    gsap.registerPlugin(ScrollTrigger);

    // 4. Generic Animations Setup
    // Fade Up
    gsap.utils.toArray('.gsap-fade-up').forEach(element => {
        gsap.fromTo(element, 
            { autoAlpha: 0, y: 50 },
            {
                autoAlpha: 1, y: 0, duration: 1, ease: "power3.out",
                scrollTrigger: { trigger: element, start: "top 85%", toggleActions: "play none none none" }
            }
        );
    });

    // Fade In
    gsap.utils.toArray('.gsap-fade-in').forEach(element => {
        gsap.fromTo(element, 
            { autoAlpha: 0 },
            {
                autoAlpha: 1, duration: 1.2, ease: "power2.inOut",
                scrollTrigger: { trigger: element, start: "top 80%", toggleActions: "play none none none" }
            }
        );
    });

    // Zoom In
    gsap.utils.toArray('.gsap-zoom-in').forEach(element => {
        gsap.fromTo(element, 
            { autoAlpha: 0, scale: 0.95 },
            {
                autoAlpha: 1, scale: 1, duration: 1.2, ease: "power3.out",
                scrollTrigger: { trigger: element, start: "top 85%", toggleActions: "play none none none" }
            }
        );
    });
    
    // Parallax background
    gsap.utils.toArray('.gsap-parallax-bg').forEach(bg => {
        gsap.to(bg, {
            yPercent: 20,
            ease: "none",
            scrollTrigger: {
                trigger: bg.parentElement,
                start: "top bottom",
                end: "bottom top",
                scrub: true
            }
        });
    });
});
</script>

    </div><!-- /.main-wrapper -->
</body>
</html>
