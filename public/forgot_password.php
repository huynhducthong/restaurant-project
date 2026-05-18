<?php 
session_start(); 
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Quên mật khẩu - Restaurantly</title>
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

    .card-box {
      background: var(--card);
      border-radius: 20px;
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

    .card-header-area {
      text-align: center;
      margin-bottom: 28px;
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
      margin-bottom: 10px;
    }

    .card-desc {
      font-size: 13.5px;
      color: var(--muted);
      line-height: 1.6;
    }

    .alert-custom {
      border-radius: 10px;
      padding: 10px 14px;
      font-size: 13px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .alert-danger-custom {
      background: #fff3f2;
      border: 1px solid #f5c0bb;
      color: var(--danger);
    }

    .alert-success-custom {
      background: #f0faf5;
      border: 1px solid #a8dcc5;
      color: var(--success);
    }

    .input-group-custom {
      position: relative;
      margin-bottom: 16px;
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

    .input-group-custom:focus-within .input-icon { color: var(--accent); }

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

    .form-input::placeholder { color: var(--muted); font-size: 13.5px; }

    .form-input:focus {
      border-color: var(--accent);
      background: #fff;
      box-shadow: 0 0 0 3px rgba(201,169,110,0.12);
    }

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

    .btn-submit:hover { background: #2d2a24; transform: translateY(-1px); }
    .btn-submit:active { transform: translateY(0); }
    .btn-submit:disabled { background: #b0aca6; cursor: not-allowed; transform: none; }

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

    .input-group-custom { animation: fadeUp 0.5s ease 0.38s both; }
    .btn-submit { animation: fadeUp 0.5s ease 0.46s both; }

    @media (max-width: 480px) {
      .card-box { padding: 36px 24px; }
    }
  </style>
</head>
<body>
  <div class="page-wrapper">
    <div class="card-box">
      <div class="card-header-area">
        <p class="card-eyebrow">Quên mật khẩu</p>
        <h1 class="card-title">Khôi phục tài khoản</h1>
        <p class="card-desc">Nhập email đã đăng ký, chúng tôi sẽ gửi mã OTP 6 chữ số để bạn đặt lại mật khẩu.</p>
      </div>

      <?php if(isset($_SESSION['error'])): ?>
        <div class="alert-custom alert-danger-custom">
          <i class="bi bi-exclamation-circle"></i>
          <?= $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
      <?php endif; ?>

      <?php if(isset($_SESSION['success'])): ?>
        <div class="alert-custom alert-success-custom">
          <i class="bi bi-check-circle"></i>
          <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
      <?php endif; ?>

      <form action="../config/forgot_action.php" method="POST" id="forgotForm">
        <div class="input-group-custom">
          <input type="email" name="email" id="emailInput" class="form-input" placeholder="Địa chỉ email của bạn" required autocomplete="email">
          <i class="bi bi-envelope input-icon"></i>
        </div>
        <button type="submit" id="submitBtn" class="btn-submit">
          Gửi mã xác nhận
        </button>
      </form>

      <div class="card-footer-link">
        <a href="login.php">← Quay lại đăng nhập</a>
      </div>
    </div>
  </div>

  <script>
    document.getElementById('forgotForm').addEventListener('submit', function() {
      const btn = document.getElementById('submitBtn');
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Đang gửi mã...';
      btn.disabled = true;
    });
  </script>
</body>
</html>