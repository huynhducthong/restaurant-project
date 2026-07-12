<?php 
session_start(); 
require_once '../config/google_setup.php';
$login_url = $client->createAuthUrl(); 
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Đăng ký - Restaurantly</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500&family=Source+Sans+3:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --bg: #F9F9F9;
      --card: #ffffff;
      --text: #222222;
      --muted: #666666;
      --border: rgba(168, 135, 70, 0.2);
      --accent: #A88746;
      --accent-dark: #b89555;
      --danger: #d94f3d;
      --success: #2d7a5b;
      --input-bg: #ffffff;
      --shadow: 0 10px 40px rgba(0,0,0,0.05);
      --accent-burgundy: #A88746;
    }

    body {
      background: linear-gradient(rgba(26,26,29,0.85), rgba(26,26,29,0.85)), url('assets/img/about-bg.jpg') center/cover no-repeat fixed;
      color: var(--text);
      font-family: 'Source Sans 3', sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .page-wrapper {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 100%;
    }

    .card-box {
      background: var(--card);
      border-radius: 0;
      box-shadow: var(--shadow);
      padding: 48px 44px;
      width: 100%;
      max-width: 440px;
      border: 1px solid var(--border);
      opacity: 0;
      transform: translateY(24px);
      animation: fadeUp 0.7s cubic-bezier(.22,.68,0,1.2) 0.2s forwards;
    }

    @keyframes fadeUp {
      to { opacity: 1; transform: translateY(0); }
    }

    /* Header */
    .card-header-area {
      text-align: center;
      margin-bottom: 32px;
    }

    .card-eyebrow {
      font-size: 11px;
      font-weight: 600;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: var(--accent);
      margin-bottom: 8px;
    }

    .card-title {
      font-family: 'Cormorant Garamond', serif;
      font-size: 28px;
      font-weight: 600;
      color: var(--text);
      line-height: 1.2;
    }

    /* Alerts */
    .alert-custom {
      border-radius: 0;
      padding: 10px 14px;
      font-size: 13px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 8px;
      animation: fadeUp 0.4s ease forwards;
    }

    .alert-warn {
      background: #fff8ee;
      border: 1px solid #f5dfa0;
      color: #8a6a1e;
    }

    .alert-danger-custom {
      background: #fff3f2;
      border: 1px solid #f5c0bb;
      color: var(--danger);
    }

    /* Form inputs */
    .input-group-custom {
      position: relative;
      margin-bottom: 14px;
    }

    .input-group-custom .input-icon {
      position: absolute;
      left: 14px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--muted);
      font-size: 15px;
      pointer-events: none;
      transition: color 0.2s;
    }

    .input-group-custom .toggle-pw {
      position: absolute;
      right: 14px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--muted);
      font-size: 15px;
      cursor: pointer;
      background: none;
      border: none;
      padding: 0;
      display: flex;
      align-items: center;
      transition: color 0.2s;
    }

    .input-group-custom .toggle-pw:hover { color: var(--accent); }

    .form-input {
      width: 100%;
      background: var(--input-bg);
      border: 1px solid var(--border);
      border-radius: 0;
      padding: 13px 14px 13px 40px;
      font-family: 'Source Sans 3', sans-serif;
      font-size: 14px;
      color: var(--text);
      outline: none;
      transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
      -webkit-appearance: none;
    }

    .form-input.has-toggle { padding-right: 42px; }

    .form-input::placeholder { color: var(--muted); font-size: 13.5px; }

    .form-input:focus {
      border-color: var(--accent);
      background: #FFFFFF;
      box-shadow: 0 0 0 3px rgba(201,169,110,0.12);
    }

    .form-input:focus + .input-icon,
    .input-group-custom:focus-within .input-icon { color: var(--accent); }

    /* Submit */
    .btn-submit {
      width: 100%;
      background: var(--accent-burgundy);
      color: #fff;
      border: none;
      border-radius: 0;
      padding: 14px;
      font-family: 'Source Sans 3', sans-serif;
      font-size: 14px;
      font-weight: 600;
      letter-spacing: 0.04em;
      cursor: pointer;
      margin-top: 6px;
      position: relative;
      overflow: hidden;
      transition: background 0.2s, transform 0.15s;
    }

    .btn-submit::after {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(90deg, transparent, rgba(0,0,0,0.1), transparent);
      transform: translateX(-100%);
      transition: transform 0.5s ease;
    }

    .btn-submit:hover { background: #2d2a24; transform: translateY(-1px); }
    .btn-submit:hover::after { transform: translateX(100%); }
    .btn-submit:active { transform: translateY(0); }

    /* Divider */
    .divider {
      display: flex;
      align-items: center;
      gap: 12px;
      margin: 22px 0;
      color: var(--muted);
      font-size: 12px;
    }

    .divider::before, .divider::after {
      content: '';
      flex: 1;
      height: 1px;
      background: var(--border);
    }

    /* Google button */
    .btn-google {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      width: 100%;
      background: #FFFFFF;
      color: var(--text);
      border: 1px solid var(--border);
      border-radius: 0;
      padding: 12px 14px;
      font-family: 'Source Sans 3', sans-serif;
      font-size: 14px;
      font-weight: 500;
      cursor: pointer;
      text-decoration: none;
      transition: border-color 0.2s, box-shadow 0.2s, transform 0.15s;
    }

    .btn-google:hover {
      border-color: #c5bdb3;
      box-shadow: 0 2px 12px rgba(0,0,0,0.06);
      transform: translateY(-1px);
      color: var(--text);
    }

    .btn-google svg { width: 18px; height: 18px; flex-shrink: 0; }

    /* Footer link */
    .card-footer-link {
      text-align: center;
      margin-top: 24px;
      padding-top: 20px;
      border-top: 1px solid var(--border);
      font-size: 13px;
      color: var(--muted);
    }

    .card-footer-link a {
      color: var(--accent);
      font-weight: 500;
      text-decoration: none;
      transition: color 0.2s;
    }

    .card-footer-link a:hover { color: var(--accent-dark); }

    /* Staggered field animation */
    .input-group-custom:nth-child(1) { animation: fadeUp 0.5s ease 0.35s both; }
    .input-group-custom:nth-child(2) { animation: fadeUp 0.5s ease 0.42s both; }
    .input-group-custom:nth-child(3) { animation: fadeUp 0.5s ease 0.49s both; }
    .input-group-custom:nth-child(4) { animation: fadeUp 0.5s ease 0.56s both; }
    .btn-submit { animation: fadeUp 0.5s ease 0.63s both; }

    @media (max-width: 576px) {
      body { padding: 15px; }
      .card-box { padding: 30px 20px; border-radius: 8px; }
      .card-title { font-size: 24px; }
      .brand-mark { display: none; }
      .form-input { font-size: 16px; padding: 12px 14px 12px 40px; }
    }
  </style>
</head>
<body>
  <div class="page-wrapper">
    <div class="card-box">
      <div class="card-header-area">
        <p class="card-eyebrow">Tham gia cùng chúng tôi</p>
        <h1 class="card-title">Tạo tài khoản</h1>
      </div>

      <?php if(isset($_SESSION['error'])): ?>
        <div class="alert-custom alert-warn">
          <i class="bi bi-info-circle"></i>
          <?= $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
      <?php endif; ?>

      <div id="js-msg" class="alert-custom alert-danger-custom" style="display:none;">
        <i class="bi bi-x-circle"></i>
        <span id="js-msg-text"></span>
      </div>

      <form action="../config/register_action.php" method="POST" id="formReg">

        <div class="input-group-custom">
          <input type="text" name="fullname" class="form-input" placeholder="Họ và tên" required autocomplete="name">
          <i class="bi bi-person input-icon"></i>
        </div>

        <div class="input-group-custom">
          <input type="email" name="email" class="form-input" placeholder="Địa chỉ email" required autocomplete="email">
          <i class="bi bi-envelope input-icon"></i>
        </div>

        <div class="input-group-custom">
          <input type="password" id="p1" name="password" class="form-input has-toggle" placeholder="Mật khẩu (ít nhất 6 ký tự)" required minlength="6" autocomplete="new-password">
          <i class="bi bi-lock input-icon"></i>
          <button type="button" class="toggle-pw" onclick="togglePw('p1', this)" tabindex="-1" aria-label="Hiện/ẩn mật khẩu">
            <i class="bi bi-eye-slash"></i>
          </button>
        </div>

        <div class="input-group-custom">
          <input type="password" id="p2" name="re-password" class="form-input has-toggle" placeholder="Xác nhận mật khẩu" required minlength="6" autocomplete="new-password">
          <i class="bi bi-lock input-icon"></i>
          <button type="button" class="toggle-pw" onclick="togglePw('p2', this)" tabindex="-1" aria-label="Hiện/ẩn mật khẩu">
            <i class="bi bi-eye-slash"></i>
          </button>
        </div>

        <button type="submit" class="btn-submit">Tạo tài khoản</button>
      </form>

      <div class="divider">hoặc tiếp tục với</div>

      <a href="<?= htmlspecialchars($login_url) ?>" class="btn-google">
        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
          <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
          <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
          <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
        </svg>
        Đăng ký bằng Google
      </a>

      <div class="card-footer-link">
        Đã có tài khoản? <a href="login.php">Đăng nhập ngay</a>
      </div>
    </div>
  </div>

  <script>
    function togglePw(id, btn) {
      const input = document.getElementById(id);
      const icon = btn.querySelector('i');
      if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye';
      } else {
        input.type = 'password';
        icon.className = 'bi bi-eye-slash';
      }
    }

    document.getElementById('formReg').onsubmit = function(e) {
      const p1 = document.getElementById('p1').value;
      const p2 = document.getElementById('p2').value;
      const msg = document.getElementById('js-msg');
      const txt = document.getElementById('js-msg-text');

      if (p1 !== p2) {
        e.preventDefault();
        txt.innerText = 'Mật khẩu xác nhận không khớp!';
        msg.style.display = 'flex';
        msg.style.animation = 'none';
        msg.offsetHeight; // reflow
        msg.style.animation = '';
      } else {
        msg.style.display = 'none';
      }
    };
  </script>
</body>
</html>