<?php 
session_start(); 

if (!isset($_SESSION['reset_otp_verified']) && !isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title>Đặt lại mật khẩu - Restaurantly</title>
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

    .card-desc strong { color: var(--text); font-weight: 600; }

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

    .alert-warn {
      background: #fff8ee;
      border: 1px solid #f5dfa0;
      color: #8a6a1e;
    }

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

    .input-group-custom:focus-within .input-icon { color: var(--accent); }

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
      padding: 13px 42px 13px 40px;
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
      margin-top: 6px;
      transition: background 0.2s, transform 0.15s;
    }

    .btn-submit:hover { background: #2d2a24; transform: translateY(-1px); }
    .btn-submit:active { transform: translateY(0); }

    .input-group-custom:nth-child(1) { animation: fadeUp 0.5s ease 0.38s both; }
    .input-group-custom:nth-child(2) { animation: fadeUp 0.5s ease 0.45s both; }
    .btn-submit { animation: fadeUp 0.5s ease 0.52s both; }

    @media (max-width: 480px) {
      .card-box { padding: 36px 24px; }
    }
  </style>
</head>
<body>
  <div class="page-wrapper">
    <div class="card-box">
      <div class="card-header-area">
        <p class="card-eyebrow">Bước cuối cùng</p>
        <h1 class="card-title">Mật khẩu mới</h1>
        <p class="card-desc">
          Đang thiết lập lại mật khẩu cho<br>
          <strong><?= htmlspecialchars($_SESSION['reset_email']) ?></strong>
        </p>
      </div>

      <?php if(isset($_SESSION['error'])): ?>
        <div class="alert-custom alert-danger-custom">
          <i class="bi bi-exclamation-circle"></i>
          <?= $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
      <?php endif; ?>

      <div id="js-error" class="alert-custom alert-warn" style="display:none;">
        <i class="bi bi-x-circle"></i>
        <span id="js-error-text"></span>
      </div>

      <form action="../config/reset_action.php" method="POST" id="resetForm">
        <div class="input-group-custom">
          <input type="password" name="password" id="p1" class="form-input" placeholder="Mật khẩu mới (ít nhất 6 ký tự)" required minlength="6" autocomplete="new-password">
          <i class="bi bi-lock input-icon"></i>
          <button type="button" class="toggle-pw" onclick="togglePw('p1', this)" tabindex="-1" aria-label="Hiện/ẩn mật khẩu">
            <i class="bi bi-eye-slash"></i>
          </button>
        </div>

        <div class="input-group-custom">
          <input type="password" name="re-password" id="p2" class="form-input" placeholder="Xác nhận mật khẩu mới" required minlength="6" autocomplete="new-password">
          <i class="bi bi-lock input-icon"></i>
          <button type="button" class="toggle-pw" onclick="togglePw('p2', this)" tabindex="-1" aria-label="Hiện/ẩn mật khẩu">
            <i class="bi bi-eye-slash"></i>
          </button>
        </div>

        <button type="submit" class="btn-submit">Lưu mật khẩu mới</button>
      </form>
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

    document.getElementById('resetForm').onsubmit = function(e) {
      const p1 = document.getElementById('p1').value;
      const p2 = document.getElementById('p2').value;
      const err = document.getElementById('js-error');
      const txt = document.getElementById('js-error-text');
      if (p1 !== p2) {
        e.preventDefault();
        txt.innerText = 'Mật khẩu xác nhận không khớp!';
        err.style.display = 'flex';
      } else {
        err.style.display = 'none';
      }
    };
  </script>
</body>
</html>