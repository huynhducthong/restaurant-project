<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';

$db = (new Database())->getConnection();

// --- Lấy cấu hình chung (Settings) ---
$stmt = $db->query("SELECT * FROM settings");
$settings = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['key_name']] = $row['key_value'];
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

<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500&family=Source+Sans+3:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">

<style>
    :root {
        --bg-color: #F9F9F9;       /* Cream */
        --card-bg: #ffffff;        /* White */
        --text-main: #222222;
        --text-muted: #777777;
        --accent-burgundy: #A88746;
        --accent-burgundy: #A88746;
        --border-light: rgba(168, 135, 70, 0.15);
        --ease: cubic-bezier(0.25, 1, 0.5, 1);
    }

    .contact-page {
        background: var(--bg-color);
        color: var(--text-main);
        font-family: 'Source Sans 3', sans-serif;
    }

    .contact-hero {
        padding: 180px 0 100px;
        text-align: center;
        position: relative;
        background: url('public/assets/img/hero/1776687242_hero-bg.jpg') center center / cover no-repeat fixed;
        border-bottom: 1px solid var(--border-light);
    }
    
    .contact-hero::before {
        content: '';
        position: absolute; inset: 0;
        background: linear-gradient(0deg, rgba(26, 26, 29, 0.8) 0%, rgba(26, 26, 29, 0.95) 100%);
    }

    .contact-hero .container {
        position: relative;
        z-index: 2;
    }

    .contact-hero h2 {
        font-family: 'Cormorant Garamond', serif;
        font-size: clamp(3rem, 5vw, 4.5rem);
        color: var(--accent-burgundy);
        margin-bottom: 20px;
        font-weight: 700;
        line-height: 1.1;
    }

    .contact-hero p {
        color: var(--text-muted);
        font-style: italic;
        font-size: 1.2rem;
        max-width: 600px;
        margin: 0 auto;
        font-family: 'Cormorant Garamond', serif;
    }

    .contact-section {
        padding: 100px 0;
        position: relative;
        z-index: 10;
        margin-top: -60px;
    }

    .info-item {
        background: var(--card-bg);
        border: 1px solid var(--border-light);
        border-radius: 0;
        padding: 30px;
        margin-bottom: 20px;
        transition: all 0.3s var(--ease);
    }
    
    .info-item:hover {
        border-color: var(--accent-burgundy);
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.03);
    }

    .info-item i {
        font-size: 1.8rem;
        color: var(--accent-burgundy);
        margin-right: 20px;
        float: left;
    }

    .info-item h5 {
        color: var(--text-main);
        margin-bottom: 8px;
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.4rem;
        font-weight: 700;
        letter-spacing: 1px;
    }

    .info-item p {
        margin: 0;
        color: var(--text-muted);
        font-size: 0.95rem;
    }

    .map-mini {
        border-radius: 0;
        overflow: hidden;
        border: 1px solid var(--border-light);
        height: 300px;
        margin-top: 30px;
    }

    .map-mini iframe {
        width: 100%;
        height: 100%;
        border: none;
    }

    .form-wrapper {
        background: var(--card-bg);
        border: 1px solid var(--border-light);
        border-radius: 0;
        padding: 50px 40px;
        height: 100%;
    }

    .form-wrapper .form-label {
        color: var(--accent-burgundy);
        font-weight: 600;
        font-size: 11px;
        letter-spacing: 2px;
        text-transform: uppercase;
        margin-bottom: 10px;
    }

    .form-control {
        background: transparent;
        border: none;
        border-bottom: 1px solid var(--border-light);
        color: var(--text-main);
        padding: 12px 0;
        border-radius: 0;
        transition: all 0.3s ease;
        font-size: 14px;
        box-shadow: none !important;
    }

    .form-control:focus {
        border-bottom-color: var(--accent-burgundy);
        background: transparent;
        color: var(--text-main);
    }
    
    .form-control::placeholder {
        color: #999;
    }

    .btn-gold {
        background: var(--accent-burgundy);
        color: #ffffff;
        font-weight: 600;
        padding: 16px 30px;
        border-radius: 0;
        border: 1px solid var(--accent-burgundy);
        transition: all 0.3s var(--ease);
        text-transform: uppercase;
        letter-spacing: 2px;
        width: 100%;
        font-size: 13px;
        margin-top: 25px;
    }

    .btn-gold:hover {
        background: transparent;
        color: var(--accent-burgundy);
    }

    .btn-gold:disabled {
        background: #e2e8f0;
        border-color: #e2e8f0;
        color: #94a3b8;
        cursor: not-allowed;
    }
    
    @media (max-width: 768px) {
        .contact-hero { padding: 120px 0 60px; }
        .contact-hero h2 { font-size: 2.5rem; }
        .contact-section { padding: 60px 0; margin-top: -30px; }
        .form-wrapper { padding: 30px 20px; }
        .info-item { padding: 20px; }
        .map-mini { height: 250px; }
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
                        <p><?= htmlspecialchars($settings['address'] ?? '123 Đường ABC, Quận 1, TP. HCM') ?></p>
                    </div>
                    <div class="info-item">
                        <i class="bi bi-envelope-fill"></i>
                        <h5>Email</h5>
                        <p>contact@restaurantly.com</p>
                    </div>
                    <div class="info-item">
                        <i class="bi bi-telephone-fill"></i>
                        <h5>Điện thoại</h5>
                        <p><?= htmlspecialchars($settings['hotline'] ?? '0901 234 567') ?></p>
                    </div>
                    <div class="info-item">
                        <i class="bi bi-clock-fill"></i>
                        <h5>Giờ mở cửa</h5>
                        <p><?= htmlspecialchars($settings['open_time'] ?? '09:00 AM - 11:00 PM') ?></p>
                    </div>

                    <?php if (!empty($settings['google_map_iframe'])): ?>
                        <div class="map-mini">
                            <?= $settings['google_map_iframe'] ?>
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