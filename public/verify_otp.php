<?php 
session_start(); 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_otp = $_POST['otp'] ?? '';
    $system_otp = $_SESSION['reset_otp'] ?? '';
    $otp_time = $_SESSION['otp_time'] ?? 0;

    if (time() - $otp_time > 300) {
        $error = "Mã xác nhận đã hết hạn. Vui lòng lấy mã mới.";
    } elseif ($user_otp == $system_otp && !empty($system_otp)) {
        header("Location: reset_password.php");
        exit();
    } else {
        $error = "Mã xác nhận không chính xác. Vui lòng kiểm tra lại.";
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title>Xác thực OTP - Restaurantly</title>
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

    /* OTP input */
    .otp-wrapper {
      margin-bottom: 20px;
      animation: fadeUp 0.5s ease 0.38s both;
    }

    .otp-input {
      width: 100%;
      background: var(--input-bg);
      border: 1.5px solid var(--border);
      border-radius: 10px;
      padding: 16px 14px;
      font-family: 'DM Sans', sans-serif;
      font-size: 26px;
      font-weight: 600;
      color: var(--text);
      text-align: center;
      letter-spacing: 0.3em;
      outline: none;
      transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
      -webkit-appearance: none;
    }

    .otp-input::placeholder {
      color: var(--border);
      letter-spacing: 0.2em;
      font-weight: 400;
    }

    .otp-input:focus {
      border-color: var(--accent);
      background: #fff;
      box-shadow: 0 0 0 3px rgba(201,169,110,0.12);
    }

    /* Timer */
    .otp-timer {
      text-align: center;
      font-size: 12px;
      color: var(--muted);
      margin-top: 8px;
    }

    .otp-timer span { color: var(--accent); font-weight: 600; }

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
      transition: background 0.2s, transform 0.15s;
      animation: fadeUp 0.5s ease 0.46s both;
    }

    .btn-submit:hover { background: #2d2a24; transform: translateY(-1px); }
    .btn-submit:active { transform: translateY(0); }

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

    @media (max-width: 480px) {
      .card-box { padding: 36px 24px; }
      .otp-input { font-size: 22px; }
    }
  </style>
</head>
<body>
  <div class="page-wrapper">
    <div class="card-box">
      <div class="card-header-area">
        <p class="card-eyebrow">Xác thực OTP</p>
        <h1 class="card-title">Nhập mã xác nhận</h1>
        <p class="card-desc">
          Mã 6 số đã được gửi đến<br>
          <strong><?= htmlspecialchars($_SESSION['reset_email'] ?? 'email của bạn') ?></strong>
        </p>
      </div>

      <?php if(isset($error)): ?>
        <div class="alert-custom alert-danger-custom">
          <i class="bi bi-exclamation-circle"></i>
          <?= $error ?>
        </div>
      <?php endif; ?>

      <form action="" method="POST">
        <div class="otp-wrapper">
          <input type="text" name="otp" class="otp-input" placeholder="000000" maxlength="6" pattern="\d{6}" required autocomplete="one-time-code" inputmode="numeric">
          <p class="otp-timer">Mã hết hạn sau <span id="countdown">05:00</span></p>
        </div>
        <button type="submit" class="btn-submit">Xác nhận mã</button>
      </form>

      <div class="card-footer-link">
        Không nhận được mã? <a href="forgot_password.php">Gửi lại</a>
      </div>
    </div>
  </div>

  <script>
    // Countdown 5 phút
    let total = 300;
    const el = document.getElementById('countdown');
    const tick = setInterval(() => {
      total--;
      const m = String(Math.floor(total / 60)).padStart(2, '0');
      const s = String(total % 60).padStart(2, '0');
      el.textContent = m + ':' + s;
      if (total <= 0) {
        clearInterval(tick);
        el.textContent = '00:00';
        el.style.color = '#d94f3d';
      }
    }, 1000);

    // Chỉ cho nhập số
    document.querySelector('.otp-input').addEventListener('input', function() {
      this.value = this.value.replace(/\D/g, '').slice(0, 6);
    });
  </script>
</body>
</html>