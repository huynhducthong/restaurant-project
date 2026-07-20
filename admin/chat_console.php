<?php
require_once __DIR__ . '/../public/admin_layout_header.php';
require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();

// Lấy danh sách session
$stmt = $db->query("SELECT * FROM chat_sessions ORDER BY FIELD(status, 'waiting_agent', 'agent_handling', 'bot_handling', 'closed'), created_at DESC");
$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$active_session = $_GET['session_id'] ?? ($sessions[0]['session_id'] ?? null);
$customer = null;
$bookings = [];

if ($active_session) {
    // Nếu chưa nhận, chuyển trạng thái sang agent_handling nếu đang waiting
    try {
        $stmt_update = $db->prepare("UPDATE chat_sessions SET status = 'agent_handling', first_response_at = IFNULL(first_response_at, CURRENT_TIMESTAMP) WHERE session_id = ? AND status = 'waiting_agent'");
        $stmt_update->execute([$active_session]);
    } catch(PDOException $e) {
        $stmt_update = $db->prepare("UPDATE chat_sessions SET status = 'agent_handling' WHERE session_id = ? AND status = 'waiting_agent'");
        $stmt_update->execute([$active_session]);
    }
    
    // Lấy thông tin khách
    $stmt_c = $db->prepare("SELECT * FROM chat_sessions WHERE session_id = ?");
    $stmt_c->execute([$active_session]);
    $customer = $stmt_c->fetch(PDO::FETCH_ASSOC);

    // Lấy lịch sử đặt bàn (Dựa vào số điện thoại)
    if ($customer) {
        $stmt_b = $db->prepare("SELECT * FROM service_bookings WHERE customer_phone = ? ORDER BY created_at DESC LIMIT 5");
        $stmt_b->execute([$customer['customer_phone']]);
        $bookings = $stmt_b->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<style>
.chat-console {
    display: flex; height: calc(100vh - 120px); gap: 15px; margin-top: 15px;
}
.col-sessions, .col-chat, .col-info { background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; flex-direction: column; overflow: hidden; }
.col-sessions { width: 300px; flex-shrink: 0; }
.col-chat { flex-grow: 1; }
.col-info { width: 300px; flex-shrink: 0; }
.header-panel { padding: 15px; background: #f8f9fa; border-bottom: 1px solid #eee; font-weight: bold; }

/* Session List */
.session-list { flex-grow: 1; overflow-y: auto; }
.session-item { padding: 15px; border-bottom: 1px solid #eee; cursor: pointer; transition: background 0.2s; text-decoration: none; display: block; color: inherit; }
.session-item:hover, .session-item.active { background: #eef2f5; }
.session-item.waiting { border-left: 4px solid #dc3545; background: #fff5f5; }
.session-name { font-weight: bold; font-size: 14px; margin-bottom: 5px; }
.session-status { font-size: 11px; padding: 2px 6px; border-radius: 12px; }
.st-waiting { background: #dc3545; color: #fff; }
.st-agent { background: #113f36; color: #fff; }
.st-bot { background: #6c757d; color: #fff; }
.st-closed { background: #e2e3e5; color: #333; }

/* Chat Area */
.chat-messages { flex-grow: 1; overflow-y: auto; padding: 15px; display: flex; flex-direction: column; gap: 10px; background: #fdfdfd; }
.msg-bubble { max-width: 75%; padding: 10px 14px; border-radius: 18px; font-size: 14px; line-height: 1.4; }
.msg-bubble img { max-width: 100%; border-radius: 8px; }
.msg-customer { background: #f1f3f5; color: #333; align-self: flex-start; border-bottom-left-radius: 4px; }
.msg-admin, .msg-bot { background: #113f36; color: #fff; align-self: flex-end; border-bottom-right-radius: 4px; }
.msg-bot { background: #C19A5B; }
.sender-name { font-size: 11px; margin-bottom: 2px; color: #888; }
.chat-input-area { padding: 15px; border-top: 1px solid #eee; background: #fff; display: flex; gap: 10px; align-items: center; }

/* Info Area */
.info-body { padding: 15px; overflow-y: auto; flex-grow: 1; }
.info-block { margin-bottom: 20px; }
</style>

<div class="content-area">
    <div class="chat-console">
        <!-- Cột 1: Danh sách Sessions -->
        <div class="col-sessions">
            <div class="header-panel">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>Danh sách khách hàng</span>
                    <span class="badge bg-danger" id="waitingCount">0 chờ</span>
                </div>
                <input type="text" id="searchChat" class="form-control form-control-sm mb-2" placeholder="Tìm tên hoặc SĐT..." onkeyup="renderSessions()">
                <select id="filterChat" class="form-select form-select-sm" onchange="renderSessions()">
                    <option value="all">Tất cả trạng thái</option>
                    <option value="waiting_agent">Đang chờ</option>
                    <option value="agent_handling">Đang chat</option>
                    <option value="bot_handling">Bot xử lý</option>
                    <option value="closed">Đã đóng</option>
                </select>
            </div>
            <div class="session-list" id="sessionList">
                <?php foreach ($sessions as $s): 
                    $is_active = $s['session_id'] === $active_session ? 'active' : '';
                    $is_waiting = $s['status'] === 'waiting_agent' ? 'waiting' : '';
                    $st_lbl = [
                        'waiting_agent' => ['st-waiting', 'Đang chờ'],
                        'agent_handling' => ['st-agent', 'Đang chat'],
                        'bot_handling' => ['st-bot', 'Bot đang xử lý'],
                        'closed' => ['st-closed', 'Đã đóng']
                    ][$s['status']];
                ?>
                <a href="?session_id=<?= $s['session_id'] ?>" class="session-item <?= $is_active ?> <?= $is_waiting ?>">
                    <div class="d-flex justify-content-between">
                        <div class="session-name"><?= htmlspecialchars($s['customer_name']) ?></div>
                        <span class="session-status <?= $st_lbl[0] ?>"><?= $st_lbl[1] ?></span>
                    </div>
                    <div class="text-muted" style="font-size:12px;"><?= htmlspecialchars($s['customer_phone']) ?></div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Cột 2: Chat -->
        <div class="col-chat">
            <?php if ($customer): ?>
            <div class="header-panel d-flex justify-content-between align-items-center">
                <span>Trò chuyện với: <span class="text-primary"><?= htmlspecialchars($customer['customer_name']) ?></span></span>
                <?php if ($customer['status'] !== 'closed'): ?>
                    <button class="btn btn-sm btn-outline-danger" onclick="closeSession('<?= $customer['session_id'] ?>')">Kết thúc Chat</button>
                <?php endif; ?>
            </div>
            <div class="chat-messages" id="adminChatMessages">
                <!-- Messages JS loaded -->
            </div>
            <?php if ($customer['status'] !== 'closed'): ?>
            <div class="quick-replies px-3 pt-2 bg-light border-top">
                <button class="btn btn-sm btn-outline-secondary me-1 mb-1" onclick="sendQuickReply('Cảm ơn bạn đã liên hệ với Restaurantly!')">Cảm ơn</button>
                <button class="btn btn-sm btn-outline-secondary me-1 mb-1" onclick="sendQuickReply('Bạn vui lòng cung cấp thông tin để nhà hàng hỗ trợ nhé.')">Xin thông tin</button>
                <button class="btn btn-sm btn-outline-secondary me-1 mb-1" onclick="sendQuickReply('Dạ, bạn có thể tham khảo thực đơn tại Website của nhà hàng ạ.')">Link Menu</button>
            </div>
            <div class="chat-input-area border-top-0 pt-2">
                <input type="file" id="adminChatImage" accept="image/jpeg, image/png" style="display:none;" onchange="sendAdminImage(this)">
                <button class="btn btn-light" onclick="document.getElementById('adminChatImage').click()"><i class="fas fa-paperclip"></i></button>
                <input type="text" id="adminChatInput" class="form-control" placeholder="Nhập tin nhắn..." onkeypress="if(event.key==='Enter') sendAdminMessage()">
                <button class="btn btn-primary" onclick="sendAdminMessage()"><i class="fas fa-paper-plane"></i></button>
            </div>
            <?php else: ?>
            <div class="p-3 text-center text-muted bg-light border-top">
                Phiên trò chuyện đã kết thúc.<br>
                <button class="btn btn-sm btn-outline-success mt-2" onclick="reopenSession('<?= $customer['session_id'] ?>')">Mở lại Chat</button>
            </div>
            <?php endif; ?>
            <?php else: ?>
            <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                Vui lòng chọn một phiên trò chuyện bên trái
            </div>
            <?php endif; ?>
        </div>

        <!-- Cột 3: Thông tin -->
        <div class="col-info">
            <div class="header-panel">Thông tin khách hàng</div>
            <div class="info-body">
                <?php if ($customer): ?>
                <div class="info-block">
                    <h6 class="fw-bold">Hồ sơ</h6>
                    <p class="mb-1"><i class="fas fa-user me-2 text-muted"></i> <?= htmlspecialchars($customer['customer_name']) ?></p>
                    <p class="mb-1"><i class="fas fa-phone me-2 text-muted"></i> <?= htmlspecialchars($customer['customer_phone']) ?></p>
                    <p class="mb-1"><i class="fas fa-clock me-2 text-muted"></i> <?= date('d/m/Y H:i', strtotime($customer['created_at'])) ?></p>
                </div>
                
                <div class="info-block mt-4">
                    <h6 class="fw-bold">Lịch sử đặt bàn (5 gần nhất)</h6>
                    <?php if (count($bookings) > 0): ?>
                        <ul class="list-group list-group-flush" style="font-size:13px;">
                        <?php foreach($bookings as $b): ?>
                            <li class="list-group-item px-0">
                                <strong><?= date('d/m/Y', strtotime($b['booking_date'])) ?></strong> - <?= htmlspecialchars($b['time_slot'] ?? date('H:i', strtotime($b['booking_date']))) ?>
                                <br><span class="badge bg-secondary"><?= $b['status'] ?></span> <?= number_format($b['total_amount'] ?? 0) ?>đ
                            </li>
                        <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted" style="font-size:13px;">Chưa có lịch sử đặt bàn.</p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
const activeSession = '<?= $active_session ?? '' ?>';
let adminLastMessageId = 0;

function appendAdminMessage(msg) {
    if (document.getElementById('admin_msg_' + msg.id)) return;
    
    const div = document.createElement('div');
    div.id = 'admin_msg_' + msg.id;
    div.className = 'msg-bubble ' + (msg.sender_type === 'customer' ? 'msg-customer' : (msg.sender_type === 'bot' ? 'msg-bot' : 'msg-admin'));
    
    if (msg.is_hidden == 1) {
        div.style.opacity = '0.6';
    }
    
    let html = '';
    if (msg.sender_type !== 'customer') {
        html += `<div class="sender-name text-end d-flex justify-content-end align-items-center" style="gap:6px;">`;
        if (msg.is_hidden != 1) {
            html += `<i class="fas fa-eye-slash text-muted" style="cursor:pointer; font-size:12px;" onclick="hideAdminMessage(${msg.id})" title="Ẩn tin nhắn này với khách"></i>`;
        } else {
            html += `<i class="fas fa-eye text-primary" style="cursor:pointer; font-size:12px;" onclick="unhideAdminMessage(${msg.id})" title="Bỏ ẩn tin nhắn này"></i>`;
            html += `<span style="font-size:10px; color:#dc3545;">Đã ẩn</span>`;
        }
        html += `<span>${msg.sender_type === 'bot' ? 'Bot' : 'Nhân viên'}</span></div>`;
    }
    
    if (msg.message_type === 'image') {
        html += `<img src="/${msg.content}">`;
    } else {
        // Escape HTML
        const textNode = document.createTextNode(msg.content);
        const p = document.createElement('p');
        p.appendChild(textNode);
        html += p.innerHTML;
    }
    div.innerHTML = html;
    
    const container = document.getElementById('adminChatMessages');
    container.appendChild(div);
    container.scrollTop = container.scrollHeight;
}

function hideAdminMessage(id) {
    if (!confirm('Bạn muốn ẩn tin nhắn này khỏi màn hình của khách?')) return;
    fetch('api/chat_admin_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=hide_message&msg_id=${id}`
    }).then(res => res.json()).then(data => {
        if (data.status === 'success') {
            const div = document.getElementById('admin_msg_' + id);
            if (div) {
                div.style.opacity = '0.6';
                const nameDiv = div.querySelector('.sender-name');
                if (nameDiv) {
                    nameDiv.innerHTML = `<i class="fas fa-eye text-primary" style="cursor:pointer; font-size:12px;" onclick="unhideAdminMessage(${id})" title="Bỏ ẩn tin nhắn này"></i><span style="font-size:10px; color:#dc3545;">Đã ẩn</span><span>${div.classList.contains('msg-bot') ? 'Bot' : 'Nhân viên'}</span>`;
                }
            }
        }
    });
}

function unhideAdminMessage(id) {
    if (!confirm('Bạn muốn hiển thị lại tin nhắn này cho khách hàng?')) return;
    fetch('api/chat_admin_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=unhide_message&msg_id=${id}`
    }).then(res => res.json()).then(data => {
        if (data.status === 'success') {
            const div = document.getElementById('admin_msg_' + id);
            if (div) {
                div.style.opacity = '1';
                const nameDiv = div.querySelector('.sender-name');
                if (nameDiv) {
                    nameDiv.innerHTML = `<i class="fas fa-eye-slash text-muted" style="cursor:pointer; font-size:12px;" onclick="hideAdminMessage(${id})" title="Ẩn tin nhắn này với khách"></i><span>${div.classList.contains('msg-bot') ? 'Bot' : 'Nhân viên'}</span>`;
                }
            }
        }
    });
}

function loadAdminMessages() {
    if (!activeSession) return;
    fetch(`api/chat_admin_api.php?action=get_messages&session_id=${activeSession}&last_id=${adminLastMessageId}`)
    .then(res => { if(res.status===304) return null; return res.json(); })
    .then(data => {
        if (!data) return;
        if (data.status === 'success' && data.messages.length > 0) {
            data.messages.forEach(m => {
                appendAdminMessage(m);
                adminLastMessageId = Math.max(adminLastMessageId, m.id);
            });
            // Play ting.mp3 if new message from customer and not first load
            if (adminLastMessageId > 0 && data.messages.some(m => m.sender_type === 'customer')) {
                // Audio would play here if user interacted
            }
        }
    });
}

function sendAdminMessage() {
    const input = document.getElementById('adminChatInput');
    const msg = input.value.trim();
    if (!msg) return;
    
    input.value = '';
    
    fetch('api/chat_admin_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=send_message&session_id=${activeSession}&content=${encodeURIComponent(msg)}`
    }).then(() => loadAdminMessages());
}

function sendQuickReply(text) {
    const input = document.getElementById('adminChatInput');
    input.value = text;
    sendAdminMessage();
}

function sendAdminImage(input) {
    if (!input.files || input.files.length === 0) return;
    const file = input.files[0];
    const formData = new FormData();
    formData.append('action', 'upload_image');
    formData.append('session_id', activeSession);
    formData.append('image', file);
    
    fetch('api/chat_admin_api.php', {
        method: 'POST', body: formData
    }).then(res=>res.json()).then(data=>{
        input.value='';
        if(data.status==='success') {
            loadAdminMessages();
        }
    });
}

function closeSession(id) {
    if(!confirm('Bạn có chắc chắn muốn kết thúc phiên chat này?')) return;
    fetch('api/chat_admin_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=close_session&session_id=${id}`
    }).then(res=>res.json()).then(data=>{
        if(data.status==='success') location.reload();
    });
}

function reopenSession(id) {
    if(!confirm('Bạn muốn mở lại phiên chat này để tiếp tục nhắn tin?')) return;
    fetch('api/chat_admin_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=reopen_session&session_id=${id}`
    }).then(res=>res.json()).then(data=>{
        if(data.status==='success') location.reload();
    });
}

if (activeSession) {
    loadAdminMessages(); // initial load
}

// Pusher Real-time integration
var pusher = new Pusher('cfbc6305339f352b0089', { cluster: 'ap1' });
var channel = pusher.subscribe('chat-channel');
channel.bind('chat_updated', function(data) {
    pollSessions();
    if (activeSession && (!data.session_id || data.session_id === activeSession)) {
        loadAdminMessages();
    }
});

let allSessions = [];

function renderSessions() {
    const listContainer = document.getElementById('sessionList');
    const searchVal = document.getElementById('searchChat') ? document.getElementById('searchChat').value.toLowerCase() : '';
    const filterVal = document.getElementById('filterChat') ? document.getElementById('filterChat').value : 'all';
    
    let html = '';
    allSessions.forEach(s => {
        // Filter logic
        if (filterVal !== 'all' && s.status !== filterVal) return;
        if (searchVal) {
            if (!s.customer_name.toLowerCase().includes(searchVal) && !s.customer_phone.includes(searchVal)) {
                return;
            }
        }

        const isActive = s.session_id === activeSession ? 'active' : '';
        const isWaiting = s.status === 'waiting_agent' ? 'waiting' : '';
        let stClass = ''; let stText = '';
        if(s.status === 'waiting_agent') { stClass = 'st-waiting'; stText = 'Đang chờ'; }
        else if(s.status === 'agent_handling') { stClass = 'st-agent'; stText = 'Đang chat'; }
        else if(s.status === 'bot_handling') { stClass = 'st-bot'; stText = 'Bot xử lý'; }
        else if(s.status === 'closed') { stClass = 'st-closed'; stText = 'Đã đóng'; }
        
        html += `<a href="?session_id=${s.session_id}" class="session-item ${isActive} ${isWaiting}">
            <div class="d-flex justify-content-between">
                <div class="session-name">${s.customer_name}</div>
                <span class="session-status ${stClass}">${stText}</span>
            </div>
            <div class="text-muted" style="font-size:12px;">${s.customer_phone}</div>
        </a>`;
    });
    
    if (html === '') {
        html = '<div class="p-3 text-center text-muted">Không tìm thấy phiên chat phù hợp</div>';
    }
    listContainer.innerHTML = html;
}

// Global Polling cho Header (waiting_agent)
function pollSessions() {
    fetch('api/chat_admin_api.php?action=check_alerts')
    .then(res => res.json())
    .then(data => {
        const badges = document.querySelectorAll('.chat-waiting-badge');
        badges.forEach(b => {
            b.innerText = data.waiting_count;
            b.style.display = data.waiting_count > 0 ? 'inline-block' : 'none';
        });
        
        const countLbl = document.getElementById('waitingCount');
        if (countLbl) {
            countLbl.innerText = data.waiting_count + ' chờ';
            if(data.waiting_count > 0 && !window.lastTingPlayed) {
                let audio = new Audio('/public/assets/audio/ting.mp3');
                audio.play().catch(e => {});
                window.lastTingPlayed = true; // prevent spam
            } else if (data.waiting_count === 0) {
                window.lastTingPlayed = false;
            }
        }
    });

    fetch('api/chat_admin_api.php?action=get_sessions')
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            allSessions = data.sessions;
            renderSessions();
        }
    });
}

pollSessions();
// pollSessions interval removed, handled by Pusher
</script>

<?php include __DIR__ . '/../public/admin_layout_footer.php'; ?>
