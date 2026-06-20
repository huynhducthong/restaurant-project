<?php
require_once __DIR__ . '/../../../config/database.php';
$db = (new Database())->getConnection();

$stmt = $db->query("SELECT * FROM footer_settings");
$ft = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { $ft[$row['setting_key']] = $row['setting_value']; }

$links      = $db->query("SELECT * FROM footer_links ORDER BY priority ASC")->fetchAll(PDO::FETCH_ASSOC) ?? [];

// Đảm bảo $path_prefix và safe_url tồn tại
$path_prefix = $path_prefix ?? '';
if (!function_exists('safe_url')) {
    function safe_url($url, $prefix) {
        if (empty($url)) return '';
        // Nếu URL là tuyệt đối (bắt đầu bằng http, https, // hoặc /)
        if (preg_match('/^(https?:|\/\/|\/)/i', $url)) {
            return $url;
        }
        return $prefix . $url;
    }
}

$bgImg      = !empty($ft['footer_bg_image']) ? safe_url('public/assets/img/' . $ft['footer_bg_image'], $path_prefix) : '';
$logo       = !empty($ft['footer_logo']) ? safe_url('public/assets/img/' . $ft['footer_logo'], $path_prefix) : '';
$showSocial = ($ft['show_social'] ?? '0') == '1';
$showMap    = ($ft['show_map'] ?? '0') == '1';
$showNews   = ($ft['show_newsletter'] ?? '0') == '1';
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500&family=Open+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap');

.footer {
    font-family: 'Cormorant Garamond', serif;
    position: relative;
    padding: 80px 0 40px;
    background-color: <?= !empty($ft['footer_bg_color']) && $ft['footer_bg_color'] !== '#F9F9F9' ? $ft['footer_bg_color'] : '#113f36' ?>;
    color: <?= $ft['footer_text_color'] ?? '#ffffff' ?>;
    <?php if ($bgImg): ?>
    background: url('<?= $bgImg ?>') center/cover no-repeat fixed;
    <?php endif; ?>
}
<?php if ($bgImg): ?>
.footer::before {
    content: '';
    position: absolute;
    inset: 0;
    background: rgba(17, 63, 54, 0.9);
    z-index: 1;
}
<?php endif; ?>
.footer .container { position: relative; z-index: 2; }
.footer h4 { 
    font-family: 'Open Sans', sans-serif; 
    font-size: 11px; 
    letter-spacing: 2px; 
    text-transform: uppercase; 
    color: rgba(0,0,0,0.6); 
    margin-bottom: 25px; 
    font-weight: 500;
}
.footer p, .footer a, .footer li { 
    font-family: 'Cormorant Garamond', serif; 
    font-size: 15px; 
    color: #ffffff; 
    line-height: 1.8; 
    text-decoration: none; 
    transition: opacity 0.3s;
    margin-bottom: 5px;
}
.footer a:hover { opacity: 0.7; color: #fff; }
.footer-logo { max-height: 80px; margin-bottom: 25px; }

.social-icons { margin-top: 25px; }
.social-icons a {
    display: inline-block;
    margin-right: 15px;
    font-size: 18px;
}

.explore-links a {
    display: block; 
    margin-bottom: 12px;
}

.contact-info { margin-bottom: 25px; }

/* Map Overlay */
.map-wrapper {
    position: relative; width: 100%; height: 150px;
    border-radius: 4px; overflow: hidden;
    margin-top: 10px;
}
.map-wrapper iframe { width: 100%; height: 100%; border: none; }
.map-overlay {
    position: absolute; inset: 0; background: rgba(17, 63, 54, 0.7);
    backdrop-filter: blur(2px); z-index: 2; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: opacity 0.3s ease;
}
.map-overlay-message {
    background: #FFFFFF; color: #113f36; padding: 10px 20px;
    border-radius: 4px; font-size: 12px; font-family: 'Open Sans', sans-serif;
    font-weight: 600; text-transform: uppercase; letter-spacing: 1px;
    pointer-events: none;
}
.map-wrapper.active .map-overlay { opacity: 0; pointer-events: none; }

.newsletter-form { margin-top: 10px; }
.newsletter-form input {
    width: 100%; padding: 12px 0; border: none; border-bottom: 1px solid rgba(255,255,255,0.3);
    background: transparent; color: #fff; outline: none; margin-bottom: 15px;
    font-family: 'Cormorant Garamond', serif; font-size: 15px;
}
.newsletter-form input::placeholder { color: rgba(0,0,0,0.5); }
.newsletter-form button {
    background: #FFFFFF; color: #113f36; width: 100%;
    border: none; padding: 14px 20px; border-radius: 4px; 
    font-weight: 600; cursor: pointer; font-family: 'Open Sans', sans-serif;
    text-transform: uppercase; letter-spacing: 1.5px; font-size: 12px;
    transition: background 0.3s;
}
.newsletter-form button:hover { background: #e0e0e0; }

.bottom-bar {
    border-top: 1px solid rgba(255,255,255,0.2);
    margin-top: 60px;
    padding-top: 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-family: 'Cormorant Garamond', serif;
    font-size: 14px;
    color: rgba(255,255,255,0.8);
}
@media (max-width: 768px) {
    .bottom-bar { flex-direction: column; gap: 15px; text-align: center; }
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
                        <h4 style="margin: 0; font-family: 'Cormorant Garamond', serif; font-size: 24px; text-transform: none; letter-spacing: normal; color: #fff; line-height: 1.2;">
                            <?= htmlspecialchars($ft['restaurant_name'] ?? 'Restaurantly') ?>
                        </h4>
                    </div>
                <?php endif; ?>
                
                <div class="contact-info" style="margin-bottom: 25px;">
                    <?php if (!empty($ft['address'])): ?><p style="font-size: 13px;"><?= htmlspecialchars($ft['address']) ?></p><?php endif; ?>
                    <?php if (!empty($ft['phone'])): ?><p style="font-size: 13px; margin-top: 15px;"><?= htmlspecialchars($ft['phone']) ?></p><?php endif; ?>
                    <?php if (!empty($ft['email'])): ?><p style="font-size: 13px;"><a href="mailto:<?= htmlspecialchars($ft['email']) ?>"><?= htmlspecialchars($ft['email']) ?></a></p><?php endif; ?>
                </div>
                
                <?php if ($showSocial): ?>
                <div class="social-icons" style="margin-top:0">
                    <?php if (!empty($ft['facebook_url'])): ?><a href="<?= htmlspecialchars($ft['facebook_url']) ?>" target="_blank"><i class="fab fa-facebook-f"></i></a><?php endif; ?>
                    <?php if (!empty($ft['instagram_url'])): ?><a href="<?= htmlspecialchars($ft['instagram_url']) ?>" target="_blank"><i class="fab fa-instagram"></i></a><?php endif; ?>
                    <?php if (!empty($ft['tiktok_url'])): ?><a href="<?= htmlspecialchars($ft['tiktok_url']) ?>" target="_blank"><i class="fab fa-tiktok"></i></a><?php endif; ?>
                    <?php if (!empty($ft['zalo_url'])): ?><a href="<?= htmlspecialchars($ft['zalo_url']) ?>" target="_blank"><i class="fab fa-zalo"></i></a><?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Cột 2: Quick Links & Thực Đơn -->
            <div class="col-lg-3 col-md-6">
                <h4>LIÊN KẾT NHANH</h4>
                <div class="explore-links" style="margin-bottom: 30px;">
                    <?php foreach ($links as $l): ?>
                        <a href="<?= htmlspecialchars(safe_url($l['url'], $path_prefix)) ?>" style="font-size: 13px;"><?= htmlspecialchars($l['title']) ?></a>
                    <?php endforeach; ?>
                    <?php if (empty($links)): ?>
                        <a href="<?= safe_url('index.php', $path_prefix) ?>" style="font-size: 13px;">Trang chủ</a>
                        <a href="<?= safe_url('about.php', $path_prefix) ?>" style="font-size: 13px;">Về chúng tôi</a>
                        <a href="<?= safe_url('contact.php', $path_prefix) ?>" style="font-size: 13px;">Liên hệ</a>
                    <?php endif; ?>
                </div>
                
                <h4>THỰC ĐƠN</h4>
                <div class="explore-links">
                    <a href="<?= safe_url('menu.php', $path_prefix) ?>#starters" style="font-size: 13px;">Món khai vị (Appetizers)</a>
                    <a href="<?= safe_url('menu.php', $path_prefix) ?>#main" style="font-size: 13px;">Món chính (Main Courses)</a>
                    <a href="<?= safe_url('menu.php', $path_prefix) ?>#seafood" style="font-size: 13px;">Hải sản tươi sống (Seafood)</a>
                    <a href="<?= safe_url('menu.php', $path_prefix) ?>#bbq" style="font-size: 13px;">Đồ nướng (BBQ & Grill)</a>
                    <a href="<?= safe_url('menu.php', $path_prefix) ?>#vegetarian" style="font-size: 13px;">Món chay (Vegetarian)</a>
                    <a href="<?= safe_url('menu.php', $path_prefix) ?>#desserts" style="font-size: 13px;">Tráng miệng (Desserts)</a>
                    <a href="<?= safe_url('menu.php', $path_prefix) ?>#drinks" style="font-size: 13px;">Đồ uống & Rượu vang (Drinks)</a>
                </div>
            </div>

            <!-- Cột 3: Dịch vụ & Mô tả -->
            <div class="col-lg-3 col-md-6">
                <h4>DỊCH VỤ</h4>
                <div class="explore-links" style="margin-bottom: 30px;">
                    <a href="<?= safe_url('services.php', $path_prefix) ?>#private" style="font-size: 13px;">Đặt tiệc cá nhân (Private Dining)</a>
                    <a href="<?= safe_url('services.php', $path_prefix) ?>#events" style="font-size: 13px;">Tiệc cưới & Sự kiện (Events)</a>
                    <a href="<?= safe_url('services.php', $path_prefix) ?>#catering" style="font-size: 13px;">Phục vụ tận nơi (Catering)</a>
                    <a href="<?= safe_url('services.php', $path_prefix) ?>#delivery" style="font-size: 13px;">Giao hàng (Delivery)</a>
                </div>
                
                <h4>MÔ TẢ</h4>
                <p style="font-size: 13px; line-height: 1.8; color: #fff;"><?= nl2br(htmlspecialchars($ft['footer_description'] ?? 'Trải nghiệm ẩm thực tuyệt vời với không gian sang trọng và các món ăn được chế biến từ nguyên liệu tươi ngon nhất.')) ?></p>
            </div>

            <!-- Cột 4: Giờ mở cửa & Đặt bàn -->
            <div class="col-lg-3 col-md-6">
                <h4>GIỜ MỞ CỬA</h4>
                <div class="contact-info" style="font-size: 13px; line-height: 2;">
                    <?php if (!empty($ft['opening_hours'])): ?>
                        <div style="white-space: pre-line;"><?= htmlspecialchars($ft['opening_hours']) ?></div>
                    <?php else: ?>
                        <div style="display: flex; justify-content: space-between;"><span>Thứ 2</span> <span>Nghỉ định kỳ</span></div>
                        <div style="display: flex; justify-content: space-between;"><span>Thứ 3 - Thứ 6</span> <span>10:00 AM - 10:00 PM</span></div>
                        <div style="display: flex; justify-content: space-between;"><span>Thứ 7 - CN</span> <span>09:00 AM - 11:00 PM</span></div>
                        <div style="display: flex; justify-content: space-between;"><span>Ngày Lễ</span> <span>Theo lịch đặt trước</span></div>
                    <?php endif; ?>
                </div>
                
                <a href="<?= safe_url('booking_service.php?type=table', $path_prefix) ?>" style="display: block; text-align: center; background: #FFFFFF; color: #113f36; padding: 12px; border-radius: 6px; font-family: 'Open Sans', sans-serif; font-weight: 600; margin-top: 30px; text-decoration: none; transition: background 0.3s;" onmouseover="this.style.background='#e0e0e0'" onmouseout="this.style.background='#fff'">
                    Đặt Bàn Ngay (Book a Table)
                </a>

                <?php if ($showMap && !empty($ft['google_map_iframe'])): ?>
                    <div class="map-wrapper mt-4" id="footerMapWrapper">
                        <?= $ft['google_map_iframe'] ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="bottom-bar">
            <div><?= htmlspecialchars($ft['copyright_text'] ?? '© 2026 Restaurantly. All Rights Reserved.') ?></div>
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

<!-- CHAT MODAL -->
<div class="chat-modal-overlay" id="chatModalOverlay" style="display: none;">
    <div class="chat-modal-container">
        <div class="chat-header">
            <div>
                <h5 class="m-0" style="font-family: 'Playfair Display', serif; color: #C19A5B;">Restaurantly Support</h5>
                <small id="chatStatusText" style="color: #bbb;">Trợ lý ảo thông minh</small>
            </div>
            <button class="chat-close-btn" onclick="closeChatModal()"><i class="fas fa-times"></i></button>
        </div>
        
        <div class="chat-body" id="chatBody">
            <div id="chatMessages" class="chat-messages-container">
                <!-- Messages will be injected here -->
            </div>
            
            <div id="chatInitForm" class="chat-init-form">
                <p class="text-center mb-3" style="font-size: 14px;">Vui lòng cho chúng tôi biết thông tin của bạn trước khi bắt đầu trò chuyện.</p>
                <div class="mb-3">
                    <input type="text" id="chatCustomerName" class="form-control" placeholder="Họ và Tên *" required>
                </div>
                <div class="mb-3">
                    <input type="text" id="chatCustomerPhone" class="form-control" placeholder="Số điện thoại *" required>
                </div>
                <button type="button" class="btn btn-chat-gold w-100" onclick="initChatSession()">BẮT ĐẦU TRÒ CHUYỆN</button>
            </div>
        </div>

        <div class="chat-footer" id="chatFooter" style="display: none;">
            <div class="typing-indicator" id="typingIndicator" style="display: none;">
                <span></span><span></span><span></span> <small class="ms-2 text-muted" id="typingText">Bot đang soạn câu trả lời...</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <input type="file" id="chatImageInput" accept="image/jpeg, image/png" style="display: none;" onchange="sendChatImage(this)">
                <button class="chat-attach-btn" onclick="document.getElementById('chatImageInput').click()"><i class="fas fa-paperclip"></i></button>
                <input type="text" id="chatInputMessage" class="form-control chat-input" placeholder="Nhập tin nhắn..." onkeypress="if(event.key === 'Enter') sendChatMessage()">
                <button class="chat-send-btn" onclick="sendChatMessage()"><i class="fas fa-paper-plane"></i></button>
            </div>
        </div>
    </div>
</div>

<style>
/* === CHAT MODAL === */
.chat-modal-overlay {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.5); z-index: 10000;
    display: flex; align-items: center; justify-content: center;
}
.chat-modal-container {
    background: #fff; width: 380px; height: 550px;
    border-radius: 12px; overflow: hidden;
    display: flex; flex-direction: column;
    box-shadow: 0 15px 30px rgba(0,0,0,0.2);
    animation: slideUp 0.3s ease;
}
@keyframes slideUp {
    from { transform: translateY(50px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
.chat-header {
    background: #0d1a16; padding: 15px 20px;
    display: flex; justify-content: space-between; align-items: center;
}
.chat-close-btn { background: none; border: none; color: #fff; font-size: 18px; cursor: pointer; }
.chat-body {
    flex-grow: 1; overflow-y: auto; background: #f8f9fa;
    position: relative;
}
.chat-init-form { padding: 30px 20px; }
.chat-messages-container { padding: 15px; display: none; flex-direction: column; gap: 10px; }
.chat-msg { max-width: 80%; padding: 10px 14px; border-radius: 18px; font-size: 14px; line-height: 1.4; word-wrap: break-word; }
.chat-msg.customer { background: #C19A5B; color: #fff; align-self: flex-end; border-bottom-right-radius: 4px; }
.chat-msg.bot { background: #e9ecef; color: #333; align-self: flex-start; border-bottom-left-radius: 4px; }
.chat-msg.admin { background: #113f36; color: #fff; align-self: flex-start; border-bottom-left-radius: 4px; }
.chat-msg img { max-width: 100%; border-radius: 8px; margin-top: 5px; }
.chat-footer { padding: 12px 15px; background: #fff; border-top: 1px solid #eee; position: relative; }
.chat-input { border-radius: 20px; background: #f1f3f5; border: none; padding-left: 15px; }
.chat-input:focus { box-shadow: none; border: 1px solid #C19A5B; }
.chat-send-btn, .chat-attach-btn { background: none; border: none; color: #113f36; font-size: 18px; cursor: pointer; transition: color 0.2s; }
.chat-send-btn:hover { color: #C19A5B; }
.typing-indicator { position: absolute; top: -25px; left: 15px; display: flex; align-items: center; }
.typing-indicator span { display: inline-block; width: 6px; height: 6px; background: #C19A5B; border-radius: 50%; margin: 0 2px; animation: bounce 1.4s infinite ease-in-out both; }
.typing-indicator span:nth-child(1) { animation-delay: -0.32s; }
.typing-indicator span:nth-child(2) { animation-delay: -0.16s; }
@keyframes bounce { 0%, 80%, 100% { transform: scale(0); } 40% { transform: scale(1); } }
@media (max-width: 500px) {
    .chat-modal-container { width: 100%; height: 100%; border-radius: 0; }
}
</style>

<style>
/* === FLOATING CHAT WIDGET === */
.global-chat-widget {
  position: fixed;
  bottom: 20px;
  right: 20px;
  z-index: 9999;
  background-color: #F9F9F9; /* Obsidian */
  width: 180px; /* Scaled down */
  padding: 15px; /* Scaled down */
  border: 1px solid rgba(168, 135, 70, 0.4);
  box-shadow: 0 10px 25px rgba(0,0,0,0.5);
}
.chat-widget-content {
  border: 1px solid rgba(168, 135, 70, 0.2);
  padding: 0;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}
.chat-eyebrow {
  font-family: 'Open Sans', sans-serif;
  font-size: 8px; /* Scaled down */
  letter-spacing: 1px;
  color: #A88746; /* Gold */
  text-transform: uppercase;
  margin-bottom: 5px;
}
.chat-heading {
  font-family: 'Cormorant Garamond', serif;
  font-size: 1rem; /* Scaled down */
  color: #222222; /* Soft Ash */
  font-weight: 400;
  line-height: 1.2;
  margin-bottom: 12px;
}
.btn-chat-gold {
  display: inline-block;
  background-color: #A88746;
  color: #F9F9F9;
  font-family: 'Open Sans', sans-serif;
  font-size: 10px; /* Scaled down */
  font-weight: 600;
  letter-spacing: 1px;
  text-transform: uppercase;
  padding: 8px 12px; /* Scaled down */
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
    }
}

function closeChatModal() {
    document.getElementById('chatModalOverlay').style.display = 'none';
    if (chatPollingInterval) clearInterval(chatPollingInterval);
}

function initChatSession() {
    const name = document.getElementById('chatCustomerName').value.trim();
    const phone = document.getElementById('chatCustomerPhone').value.trim();
    
    if (!name || !phone) {
        alert('Vui lòng nhập đầy đủ Tên và Số điện thoại!');
        return;
    }
    
    // Tạo session tạm
    chatSessionId = 'sess_' + Date.now() + '_' + Math.floor(Math.random()*1000);
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

function appendMessage(sender, type, content) {
    const msgDiv = document.createElement('div');
    msgDiv.className = `chat-msg ${sender}`;
    if (type === 'image') {
        msgDiv.innerHTML = `<img src="${content}" alt="Image">`;
    } else {
        msgDiv.innerText = content;
    }
    document.getElementById('chatMessages').appendChild(msgDiv);
    const body = document.getElementById('chatBody');
    body.scrollTop = body.scrollHeight;
}

function sendChatMessage() {
    const input = document.getElementById('chatInputMessage');
    const msg = input.value.trim();
    if (!msg) return;
    
    input.value = '';
    showTyping('Bot đang soạn câu trả lời...');
    
    fetch('api/chat/client_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=send_message&session_id=${chatSessionId}&content=${encodeURIComponent(msg)}`
    }).then(res => res.json()).then(data => {
        loadChatMessages();
    }).catch(err => console.error(err));
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
        if (data.status === 'success' && data.messages.length > 0) {
            hideTyping();
            data.messages.forEach(m => {
                appendMessage(m.sender_type, m.message_type, m.content);
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
    if (chatPollingInterval) clearInterval(chatPollingInterval);
    chatPollingInterval = setInterval(() => {
        if (document.getElementById('chatModalOverlay').style.display !== 'none') {
            loadChatMessages();
        }
    }, 3000);
}
</script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">