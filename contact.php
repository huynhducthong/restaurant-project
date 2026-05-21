<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';

$db = (new Database())->getConnection();

// --- Lấy cấu hình Footer (sử dụng lại cho phần liên hệ) ---
$stmt = $db->query("SELECT * FROM footer_settings");
$ft = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $ft[$row['setting_key']] = $row['setting_value'];
}

// --- Xử lý gửi form ---
$messageSent = false;
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Kiểm tra Honey Pot (Spam prevention)
    if (!empty($_POST['website'])) {
        // Nếu bot điền vào field này, im lặng dừng lại hoặc trả về lỗi
        if (isset($_POST['is_ajax'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Spam detected.']);
            exit;
        }
        $errorMsg = 'Yêu cầu không hợp lệ.';
    } 
    // 2. Kiểm tra CSRF token
    elseif (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        $errorMsg = 'Yêu cầu không hợp lệ. Vui lòng thử lại.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if ($name === '' || $email === '' || $subject === '' || $message === '') {
            $errorMsg = 'Vui lòng điền đầy đủ tất cả các trường.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMsg = 'Địa chỉ email không hợp lệ.';
        } else {
            try {
                // Lưu vào CSDL Contacts
                $stmt = $db->prepare("INSERT INTO contacts (name, email, subject, message, status) VALUES (?, ?, ?, ?, 'new')");
                $stmt->execute([$name, $email, $subject, $message]);

                $messageSent = true;
            } catch (Exception $e) {
                $errorMsg = 'Lỗi hệ thống: ' . $e->getMessage();
            }
        }
    }

    // Trả về JSON nếu là AJAX
    if (isset($_POST['is_ajax'])) {
        header('Content-Type: application/json');
        if ($messageSent) {
            echo json_encode(['success' => true, 'message' => 'Cảm ơn bạn đã liên hệ! Chúng tôi sẽ phản hồi sớm nhất.']);
        } else {
            echo json_encode(['success' => false, 'message' => $errorMsg]);
        }
        exit;
    }
}

// Tạo CSRF token mới
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

include 'views/client/layouts/header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
    :root {
        --bg-dark: #0a1715;
        --forest: #143B36;
        --forest-light: #1d5750;
        --forest-glow: rgba(20, 59, 54, 0.5);
        --gold: #cda45e;
        --gold-glow: rgba(205, 164, 94, 0.3);
        --glass-bg: rgba(20, 59, 54, 0.25);
        --glass-border: rgba(205, 164, 94, 0.15);
        --ease: cubic-bezier(0.25, 1, 0.5, 1);
    }

    .contact-page {
        background: var(--bg-dark);
        color: #fff;
        font-family: 'Inter', sans-serif;
    }

    .contact-hero {
        padding: 180px 0 100px;
        text-align: center;
        position: relative;
        background: url('public/assets/img/hero-bg.jpg') center center / cover no-repeat fixed;
        border-bottom: 1px solid var(--glass-border);
    }
    
    .contact-hero::before {
        content: '';
        position: absolute; inset: 0;
        background: linear-gradient(0deg, var(--bg-dark) 0%, rgba(10,23,21,0.6) 100%);
    }

    .contact-hero .container {
        position: relative;
        z-index: 2;
    }

    .contact-hero h2 {
        font-family: 'Playfair Display', serif;
        font-size: clamp(3rem, 5vw, 4.5rem);
        color: var(--gold);
        margin-bottom: 20px;
        font-weight: 700;
        letter-spacing: 2px;
        text-shadow: 0 5px 15px rgba(0,0,0,0.5);
    }

    .contact-hero p {
        color: rgba(255,255,255,0.8);
        font-style: italic;
        font-size: 1.1rem;
        max-width: 600px;
        margin: 0 auto;
    }

    .contact-section {
        padding: 100px 0;
        position: relative;
        z-index: 10;
        margin-top: -60px;
    }

    .info-item {
        background: var(--glass-bg);
        border: 1px solid var(--glass-border);
        backdrop-filter: blur(10px);
        border-radius: 16px;
        padding: 25px;
        margin-bottom: 20px;
        transition: all 0.4s var(--ease);
        position: relative;
        overflow: hidden;
    }
    
    .info-item::before {
        content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.05), transparent);
        transition: 0.5s;
    }

    .info-item:hover {
        border-color: var(--gold);
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.4), 0 0 15px var(--forest-glow);
        background: rgba(20, 59, 54, 0.4);
    }
    
    .info-item:hover::before {
        left: 200%;
    }

    .info-item i {
        font-size: 2rem;
        color: var(--gold);
        margin-right: 20px;
        float: left;
        transition: 0.3s;
    }

    .info-item:hover i {
        transform: scale(1.1);
    }

    .info-item h5 {
        color: #fff;
        margin-bottom: 8px;
        font-family: 'Playfair Display', serif;
        font-size: 1.25rem;
        letter-spacing: 1px;
    }

    .info-item p {
        margin: 0;
        color: rgba(255,255,255,0.6);
        font-size: 0.95rem;
    }

    .map-mini {
        border-radius: 16px;
        overflow: hidden;
        border: 1px solid var(--glass-border);
        height: 250px;
        margin-top: 30px;
        transition: 0.4s;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    }
    
    .map-mini:hover {
        border-color: var(--gold);
        box-shadow: 0 15px 40px rgba(0,0,0,0.5);
    }

    .map-mini iframe {
        width: 100%;
        height: 100%;
        border: none;
        filter: invert(90%) hue-rotate(180deg) brightness(80%) contrast(80%);
        transition: 0.5s;
    }
    
    .map-mini:hover iframe {
        filter: invert(90%) hue-rotate(180deg) brightness(95%) contrast(90%);
    }

    .form-wrapper {
        background: var(--forest);
        border: 1px solid var(--glass-border);
        border-radius: 20px;
        padding: 50px 40px;
        height: 100%;
        box-shadow: 0 20px 50px rgba(0,0,0,0.5);
        position: relative;
    }

    .form-wrapper .form-label {
        color: var(--gold);
        font-weight: 500;
        font-size: 12px;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        margin-bottom: 10px;
    }

    .form-control {
        background: rgba(0,0,0,0.25);
        border: 1px solid rgba(255,255,255,0.1);
        border-bottom: 1px solid rgba(255,255,255,0.2);
        color: #fff;
        padding: 15px 20px;
        border-radius: 8px;
        transition: all 0.3s ease;
        font-size: 14px;
    }

    .form-control:focus {
        border-color: rgba(255,255,255,0.1);
        border-bottom-color: var(--gold);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        background: rgba(212, 176, 106, 0.05);
        color: #fff;
    }
    
    .form-control::placeholder {
        color: rgba(255,255,255,0.3);
    }

    .btn-gold {
        background: linear-gradient(135deg, #E6C887 0%, #D4B06A 50%, #A5803A 100%);
        color: var(--bg-dark);
        font-weight: 600;
        padding: 16px 30px;
        border-radius: 50px;
        border: none;
        transition: all 0.4s var(--ease);
        text-transform: uppercase;
        letter-spacing: 1.5px;
        width: 100%;
        font-size: 14px;
        margin-top: 15px;
    }

    .btn-gold:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px var(--gold-glow);
        background: linear-gradient(135deg, #f0d59e 0%, #e8c47b 50%, #b8943f 100%);
    }

    .alert {
        border-radius: 12px;
        border: none;
        padding: 15px 20px;
        font-size: 14px;
    }

    /* Animations & Polish */
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .info-item, .form-wrapper, .map-mini {
        opacity: 0;
        animation: fadeInUp 0.8s var(--ease) forwards;
    }

    .info-item:nth-child(1) { animation-delay: 0.1s; }
    .info-item:nth-child(2) { animation-delay: 0.2s; }
    .info-item:nth-child(3) { animation-delay: 0.3s; }
    .info-item:nth-child(4) { animation-delay: 0.4s; }
    .map-mini { animation-delay: 0.5s; }
    .form-wrapper { animation-delay: 0.3s; }

    .btn-gold:disabled {
        background: rgba(255,255,255,0.1);
        color: rgba(255,255,255,0.3);
        cursor: not-allowed;
        box-shadow: none;
        transform: none;
    }
</style>

<main class="contact-page">
    <!-- Hero -->
    <section class="contact-hero">
        <div class="container">
            <h2>Liên Hệ</h2>
            <p>Kết nối với chúng tôi để nhận được sự phục vụ tốt nhất</p>
        </div>
    </section>

    <!-- Nội dung chính -->
    <section class="contact-section">
        <div class="container">
            <div class="row g-4">
                <!-- Cột trái: Thông tin liên hệ + Map -->
                <div class="col-lg-5">
                    <div class="info-item">
                        <i class="bi bi-geo-alt-fill"></i>
                        <h5>Địa chỉ</h5>
                        <p><?= htmlspecialchars($ft['address'] ?? 'Đang cập nhật...') ?></p>
                    </div>
                    <div class="info-item">
                        <i class="bi bi-envelope-fill"></i>
                        <h5>Email</h5>
                        <p><?= htmlspecialchars($ft['email'] ?? 'Đang cập nhật...') ?></p>
                    </div>
                    <div class="info-item">
                        <i class="bi bi-telephone-fill"></i>
                        <h5>Điện thoại</h5>
                        <p><?= htmlspecialchars($ft['phone'] ?? 'Đang cập nhật...') ?></p>
                    </div>
                    <div class="info-item">
                        <i class="bi bi-clock-fill"></i>
                        <h5>Giờ mở cửa</h5>
                        <p><?= htmlspecialchars($ft['opening_hours'] ?? 'Đang cập nhật...') ?></p>
                    </div>

                    <?php if (($ft['show_map'] ?? '0') == '1' && !empty($ft['google_map_iframe'])): ?>
                        <div class="map-mini">
                            <?= $ft['google_map_iframe'] ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Cột phải: Form liên hệ -->
                <div class="col-lg-7">
                    <div class="form-wrapper">
                        <?php if ($messageSent): ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                Cảm ơn bạn đã liên hệ! Chúng tôi sẽ phản hồi trong thời gian sớm nhất.
                            </div>
                        <?php elseif ($errorMsg !== ''): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <?= $errorMsg ?>
                            </div>
                        <?php endif; ?>

                        <form id="contactForm" action="contact.php" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="is_ajax" value="1">
                            
                            <!-- Honey Pot (Anti-spam) -->
                            <div style="display:none !important;">
                                <input type="text" name="website" tabindex="-1" autocomplete="off">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Họ và tên</label>
                                <input type="text" name="name" class="form-control" placeholder="Nhập họ tên của bạn"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" placeholder="Nhập email" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tiêu đề</label>
                                <input type="text" name="subject" class="form-control" placeholder="Tiêu đề lời nhắn"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nội dung</label>
                                <textarea name="message" rows="5" class="form-control"
                                    placeholder="Viết nội dung bạn muốn gửi..." required></textarea>
                            </div>
                            <button type="submit" id="btnSubmit" class="btn btn-gold d-flex align-items-center justify-content-center gap-2">
                                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                <span class="btn-text">Gửi lời nhắn</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Toast Notifications -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1100;">
        <div id="contactToast" class="toast align-items-center text-white border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>
</main>

<script>
document.getElementById('contactForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    var form = this;
    var btn = document.getElementById('btnSubmit');
    var spinner = btn.querySelector('.spinner-border');
    var btnText = btn.querySelector('.btn-text');
    var toastEl = document.getElementById('contactToast');
    var toast = new bootstrap.Toast(toastEl);
    
    // Disable form & show loading
    btn.disabled = true;
    spinner.classList.remove('d-none');
    btnText.innerText = 'Đang gửi...';
    
    var formData = new FormData(form);
    
    fetch('contact.php', {
        method: 'POST',
        body: formData
    })
    .then(function(response) {
        return response.json();
    })
    .then(function(data) {
        // Reset button
        btn.disabled = false;
        spinner.classList.add('d-none');
        btnText.innerText = 'Gửi lời nhắn';
        
        // Configure toast
        toastEl.classList.remove('bg-success', 'bg-danger');
        toastEl.classList.add(data.success ? 'bg-success' : 'bg-danger');
        toastEl.querySelector('.toast-body').innerText = data.message;
        
        toast.show();
        
        if (data.success) {
            form.reset();
        }
    })
    .catch(function(error) {
        btn.disabled = false;
        spinner.classList.add('d-none');
        btnText.innerText = 'Gửi lời nhắn';
        
        toastEl.classList.remove('bg-success');
        toastEl.classList.add('bg-danger');
        toastEl.querySelector('.toast-body').innerText = 'Đã có lỗi xảy ra. Vui lòng thử lại sau.';
        toast.show();
    });
});
</script>

<?php include 'views/client/layouts/footer.php'; ?>