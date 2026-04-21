<?php
session_start();
require_once '../config/google_setup.php';
$login_url = $client->createAuthUrl(); 
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title>Đăng nhập - Restaurantly</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=Playfair+Display:wght@500;600&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --bg: #f7f5f2;
      --card: #ffffff;
      --text: #1a1814;
      --muted: #8a857d;
      --border: #e8e4de;
      --accent: #c9a96e;
      --accent-dark: #b08a50;
      --danger: #d94f3d;
      --success: #2d7a5b;
      --input-bg: #faf9f7;
      --shadow: 0 2px 40px rgba(0,0,0,0.08);
    }

    body {
      background: var(--bg);
      color: var(--text);
      font-family: 'DM Sans', sans-serif;
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

    .brand-mark {
      position: fixed;
      top: 28px;
      left: 32px;
      font-family: 'Playfair Display', serif;
      font-size: 18px;
      color: var(--accent);
      letter-spacing: 0.02em;
      opacity: 0;
      animation: fadeUp 0.6s ease 0.1s forwards;
    }

    .card-box {
      background: var(--card);
      border-radius: 20px;
      box-shadow: var(--shadow);
      padding: 48px 44px;
      width: 100%;
      max-width: 420px;
      border: 1px solid var(--border);
      opacity: 0;
      transform: translateY(24px);
      animation: fadeUp 0.7s cubic-bezier(.22,.68,0,1.2) 0.2s forwards;
    }

    @keyframes fadeUp {
      to { opacity: 1; transform: translateY(0); }
    }

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
      font-family: 'Playfair Display', serif;
      font-size: 28px;
      font-weight: 600;
      color: var(--text);
      line-height: 1.2;
    }

    /* Alerts */
    .alert-custom {
      border-radius: 10px;
      padding: 10px 14px;
      font-size: 13px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 8px;
      animation: fadeUp 0.4s ease forwards;
    }

    .alert-success-custom {
      background: #f0faf5;
      border: 1px solid #a8dcc5;
      color: var(--success);
    }

    .alert-danger-custom {
      background: #fff3f2;
      border: 1px solid #f5c0bb;
      color: var(--danger);
    }

    /* Inputs */
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
      border: 1.5px solid var(--border);
      border-radius: 10px;
      padding: 13px 14px 13px 40px;
      font-family: 'DM Sans', sans-serif;
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
      background: #fff;
      box-shadow: 0 0 0 3px rgba(201,169,110,0.12);
    }

    .input-group-custom:focus-within .input-icon { color: var(--accent); }

    /* Forgot password */
    .forgot-link {
      display: block;
      text-align: right;
      margin-top: -6px;
      margin-bottom: 18px;
      font-size: 12.5px;
      color: var(--muted);
      text-decoration: none;
      transition: color 0.2s;
    }

    .forgot-link:hover { color: var(--accent); }

    /* Submit */
    .btn-submit {
      width: 100%;
      background: var(--text);
      color: #fff;
      border: none;
      border-radius: 10px;
      padding: 14px;
      font-family: 'DM Sans', sans-serif;
      font-size: 14px;
      font-weight: 600;
      letter-spacing: 0.04em;
      cursor: pointer;
      position: relative;
      overflow: hidden;
      transition: background 0.2s, transform 0.15s;
    }

    .btn-submit::after {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
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
      background: #fff;
      color: var(--text);
      border: 1.5px solid var(--border);
      border-radius: 10px;
      padding: 12px 14px;
      font-family: 'DM Sans', sans-serif;
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

    /* Footer */
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

    /* Stagger */
    .input-group-custom:nth-child(1) { animation: fadeUp 0.5s ease 0.35s both; }
    .input-group-custom:nth-child(2) { animation: fadeUp 0.5s ease 0.42s both; }
    .forgot-link { animation: fadeUp 0.5s ease 0.49s both; }
    .btn-submit { animation: fadeUp 0.5s ease 0.54s both; }

    @media (max-width: 480px) {
      .card-box { padding: 36px 24px; }
    }
  </style>
</head>
<body>
  <div class="brand-mark">Restaurantly</div>

  <div class="page-wrapper">
    <div class="card-box">
      <div class="card-header-area">
        <p class="card-eyebrow">Chào mừng trở lại</p>
        <h1 class="card-title">Đăng nhập</h1>
      </div>

      <?php if(isset($_SESSION['success'])): ?>
        <div class="alert-custom alert-success-custom">
          <i class="bi bi-check-circle"></i>
          <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
      <?php endif; ?>

      <?php if(isset($_GET['error'])): ?>
        <div class="alert-custom alert-danger-custom">
          <i class="bi bi-exclamation-circle"></i>
          <?php 
            $err = $_GET['error'];
            if($err == 'wrong_password') echo "Mật khẩu không chính xác!";
            elseif($err == 'user_not_found') echo "Email này chưa được đăng ký!";
            elseif($err == 'empty') echo "Vui lòng nhập đầy đủ thông tin!";
            else echo "Lỗi đăng nhập, vui lòng thử lại!";
          ?>
        </div>
      <?php endif; ?>

      <form action="../config/login_action.php" method="POST">

        <div class="input-group-custom">
          <input type="email" name="email" class="form-input" placeholder="Địa chỉ email" required autocomplete="email">
          <i class="bi bi-envelope input-icon"></i>
        </div>

        <div class="input-group-custom">
          <input type="password" id="pw" name="password" class="form-input has-toggle" placeholder="Mật khẩu" required autocomplete="current-password">
          <i class="bi bi-lock input-icon"></i>
          <button type="button" class="toggle-pw" onclick="togglePw('pw', this)" tabindex="-1" aria-label="Hiện/ẩn mật khẩu">
            <i class="bi bi-eye-slash"></i>
          </button>
        </div>

        <a href="forgot_password.php" class="forgot-link">Quên mật khẩu?</a>

        <button type="submit" class="btn-submit">Đăng nhập</button>
      </form>

      <div class="divider">hoặc tiếp tục với</div>

      <a href="<?= htmlspecialchars($login_url) ?>" class="btn-google">
        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
          <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
          <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
          <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
        </svg>
        Đăng nhập bằng Google
      </a>

      <div class="card-footer-link">
        Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a>
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
  </script>
</body>
</html>