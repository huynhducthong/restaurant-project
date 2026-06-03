<?php
// 1. Khởi tạo Session và Import các cấu hình
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
require_once __DIR__ . '/config/database.php';


// 2. Khởi tạo kết nối
$database = new Database();
$db = $database->getConnection();

/** * 3. LẤY CẤU HÌNH HỆ THỐNG (Đồng bộ với Settings Admin)
 * Bổ sung để hiển thị Tên nhà hàng và Căn lề Banner linh hoạt
 */
$settings = [];
try {
  $stmt_settings = $db->prepare("SELECT * FROM settings");
  $stmt_settings->execute();
  while ($row = $stmt_settings->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['key_name']] = $row['key_value'];
  }
} catch (Exception $e) {
}

// Thiết lập căn lề chữ dựa trên settings (left, center, right)
$pos = $settings['name_position'] ?? 'center';
$align_class = ($pos == 'left') ? 'text-start' : (($pos == 'right') ? 'text-end' : 'text-center');

// 4. Xử lý logic đặt bàn
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET['action']) && $_GET['action'] == 'book') {
  $name = $_POST['name'] ?? '';
  $email = $_POST['email'] ?? '';
  $phone = $_POST['phone'] ?? '';
  $date = $_POST['date'] ?? '';
  $time = $_POST['time'] ?? '';
  $people = $_POST['people'] ?? 1;

  $booking = new Booking($db);
  if ($booking->create($name, $email, $phone, $date, $time, $people)) {
    echo "<script>alert('Đặt bàn thành công! Chúng tôi sẽ sớm liên hệ.'); window.location.href='index.php';</script>";
  } else {
    echo "<script>alert('Có lỗi xảy ra, vui lòng thử lại.');</script>";
  }
}

// 5. Lấy dữ liệu Banner, Video và Combo
$banners_db = [];
$video_data = null;
$video_type = 'youtube';
$video_url  = '';
$file_path  = '';
$stmt_combos = null;

try {
  // 5.1 Lấy Banner - Ưu tiên lấy từ Database (Lọc Banner bật và Hẹn giờ)
  $stmt_banners = $db->prepare("SELECT * FROM banners WHERE is_active = 1 AND (start_date IS NULL OR start_date <= NOW()) AND (end_date IS NULL OR end_date >= NOW()) ORDER BY display_order ASC");
  $stmt_banners->execute();
  $banners_db = $stmt_banners->fetchAll(PDO::FETCH_ASSOC);

  // 5.2 Lấy Video — đồng bộ với VideoController.php (không hardcode id=1)
  $stmt_video = $db->prepare("SELECT * FROM videos ORDER BY id ASC LIMIT 1");
  $stmt_video->execute();
  $video_data = $stmt_video->fetch(PDO::FETCH_ASSOC);

  // Nếu tìm thấy video, gán giá trị vào các biến
  if ($video_data) {
    $video_type = $video_data['video_type'] ?? 'youtube';
    $video_url  = $video_data['video_url'] ?? '';
    $file_path  = $video_data['file_path'] ?? '';
  }

  // 5.3 Truy vấn Combo — đồng bộ với list_combos.php (dùng is_active)
  $sql_combos = "SELECT c.*, GROUP_CONCAT(f.name ORDER BY f.name SEPARATOR ', ') as list_foods
                   FROM combos c
                   LEFT JOIN combo_items ci ON c.id = ci.combo_id
                   LEFT JOIN foods f        ON ci.food_id = f.id
                   WHERE c.is_active = 1
                   GROUP BY c.id
                   ORDER BY c.id DESC";
  $stmt_combos = $db->prepare($sql_combos);
  $stmt_combos->execute();

  // 5.4 Lấy danh sách đầu bếp nổi bật cho trang chủ
  $stmt_home_chefs = $db->prepare("SELECT * FROM chefs WHERE is_active = 1 ORDER BY is_featured DESC, sort_order ASC, id ASC LIMIT 3");
  $stmt_home_chefs->execute();
  $home_chefs = $stmt_home_chefs->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  $home_chefs = [];
}

// 6. Nhúng Header
include __DIR__ . '/views/client/layouts/header.php';
?>

<section id="hero" class="d-flex align-items-center">
  <div id="heroCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" style="width: 100%; height: 100vh;">

    <div class="carousel-inner">
      <?php
      // Dùng lại $banners_db đã fetch ở trên — tránh query trùng
      $first = true;
      foreach ($banners_db as $row):
        $title_weight = ($row['font_style'] == 'bold') ? 'bold' : 'normal';
        $title_style  = ($row['font_style'] == 'italic') ? 'italic' : 'normal';
        $desc_weight  = (($row['desc_font_style'] ?? 'normal') == 'bold') ? 'bold' : 'normal';
        $desc_style   = (($row['desc_font_style'] ?? 'normal') == 'italic') ? 'italic' : 'normal';
      ?>
        <div class="carousel-item <?= $first ? 'active' : '' ?>"
          style="background-image: url('public/assets/img/hero/<?= $row['image_url'] ?>'); background-size: cover; height: 100vh; background-position: center; position: relative;">

          <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5);"></div>

          <div class="container position-relative d-flex flex-column justify-content-center h-100"
            style="text-align: <?= $row['text_align'] ?>; z-index: 2;">

            <div class="row">
              <div class="col-lg-12">
                <h1 style="
                    color: <?= $row['text_color'] ?>; 
                    font-family: <?= $row['font_family'] ?>; 
                    font-size: <?= $row['title_font_size'] ?? 48 ?>px; 
                    font-weight: <?= $title_weight ?>; 
                    font-style: <?= $title_style ?>;
                    text-shadow: 2px 2px 5px rgba(0,0,0,0.7);
                    margin-bottom: 15px;">
                  <?= htmlspecialchars($row['title']) ?>
                </h1>

                <p style="
                    color: <?= $row['desc_color'] ?? '#eeeeee' ?>; 
                    font-family: <?= $row['desc_font_family'] ?? "'Poppins', sans-serif" ?>; 
                    font-size: <?= $row['desc_font_size'] ?? 24 ?>px; 
                    font-weight: <?= $desc_weight ?>; 
                    font-style: <?= $desc_style ?>;
                    text-shadow: 1px 1px 4px rgba(0,0,0,0.7);">
                  <?= htmlspecialchars($row['description']) ?>
                </p>
                <?php if (!empty($row['button_text'])): ?>
                  <a href="<?= htmlspecialchars($row['button_link'] ?? '#') ?>" class="animate__animated animate__fadeInUp" style="
                    display:inline-block;
                    padding:12px 36px;
                    border-radius:50px;
                    text-transform:uppercase;
                    text-decoration:none;
                    font-weight:600;
                    font-size:14px;
                    font-family:'Poppins', sans-serif;
                    letter-spacing:1px;
                    background-color: <?= htmlspecialchars($row['button_color'] ?? '#cda45e') ?>;
                    color: #fff;
                    border: none;
                    transition:0.3s;
                    margin-top:20px;
                " onmouseover="this.style.opacity='0.8';" onmouseout="this.style.opacity='1';">
                    <?= htmlspecialchars($row['button_text']) ?>
                  </a>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      <?php $first = false;
      endforeach; ?>
    </div>

    <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
      <span class="carousel-control-prev-icon"></span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
      <span class="carousel-control-next-icon"></span>
    </button>
  </div>
</section>

<main id="main">
  <section id="about" class="about-section" style="background: #0A1C1A; color: #fff; padding: 140px 0; overflow: hidden;">
    <div class="container-fluid px-0">
      <div class="row g-0 align-items-center">
        <div class="col-lg-7" style="padding-left: 5%; padding-right: 30px;">
          <div class="video-wrapper" style="position: relative; width: 100%; border-radius: 5px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.5); background: #000;">

            <?php if ($video_type == 'youtube' && !empty($video_url)): ?>
              <iframe width="100%" height="500"
                src="https://www.youtube.com/embed/<?php echo htmlspecialchars($video_url); ?>"
                frameborder="0" allowfullscreen style="display: block; border: none;">
              </iframe>
            <?php elseif ($video_type == 'vimeo' && !empty($video_url)): ?>
              <iframe width="100%" height="500"
                src="https://player.vimeo.com/video/<?php echo htmlspecialchars($video_url); ?>"
                frameborder="0" allowfullscreen style="display: block; border: none;">
              </iframe>
            <?php elseif ($video_type == 'muse' && !empty($video_url)): ?>
              <iframe width="100%" height="500"
                src="https://muse.ai/embed/<?php echo htmlspecialchars($video_url); ?>?search=0&links=0"
                frameborder="0" allowfullscreen style="display: block; border: none;">
              </iframe>
            <?php elseif ($video_type == 'local' && !empty($file_path)): ?>
              <?php
              $ext_v = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
              $mime_map = ['mp4' => 'video/mp4', 'webm' => 'video/webm', 'mov' => 'video/quicktime'];
              $mime_v = $mime_map[$ext_v] ?? 'video/mp4';
              ?>
              <video controls style="width: 100%; height: 500px; display: block; object-fit: cover;">
                <source src="<?= htmlspecialchars($file_path) ?>" type="<?= $mime_v ?>">
                Trình duyệt của bạn không hỗ trợ xem video.
              </video>
            <?php else: ?>
              <img src="public/assets/img/about.jpg" class="img-fluid" alt="About Us" style="width: 100%; height: 500px; object-fit: cover;">
            <?php endif; ?>

          </div>
        </div>

        <div class="col-lg-5 p-4 p-md-5">
          <div class="about-content" style="padding-right: 5%;">
            <h2 style="font-family: 'Playfair Display', serif; color: #d9ba85; font-size: 3rem; margin-bottom: 25px; font-weight: 700; line-height: 1.2;">
              Câu Chuyện <br> Về <?= htmlspecialchars($settings['restaurant_name'] ?? 'Restaurantly') ?>
            </h2>
            <p style="font-family: 'Poppins', sans-serif; font-weight: 300; line-height: 2; color: #ced4da; font-size: 1.15rem; margin-bottom: 25px;">
              Nằm giữa lòng <?= htmlspecialchars($settings['address'] ?? 'Biên Hòa') ?>, chúng tôi mang đến một không gian ẩm thực tinh tế.
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section id="menu" class="menu section-bg" style="background: #0d0d0d; padding: 100px 0; overflow: hidden;">

    <?php
    // Lấy tất cả món ăn active để hiển thị gallery
    $all_foods_stmt = $db->prepare(
      "SELECT f.*, c.id as cat_id, c.name as cat_name
         FROM foods f
         JOIN categories c ON f.category_id = c.id
         WHERE f.is_active = 1
         ORDER BY f.id ASC"
    );
    $all_foods_stmt->execute();
    $all_foods = $all_foods_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Chia thành 2 hàng
    $total = count($all_foods);
    $half  = (int)ceil($total / 2);
    $row1  = array_slice($all_foods, 0, $half);
    $row2  = array_slice($all_foods, $half);

    // Nếu ít hơn 6 món, nhân đôi để đủ ảnh chạy đẹp
    while (count($row1) < 6) $row1 = array_merge($row1, $row1);
    while (count($row2) < 6) $row2 = array_merge($row2, $row2);
    ?>

    <!-- Tiêu đề -->
    <div class="text-center mb-5" style="position:relative; z-index:2;">
      <p style="color:#cda45e; font-family:'Poppins',sans-serif; font-size:13px; letter-spacing:4px; text-transform:uppercase; margin-bottom:12px;">Hương Vị Tinh Tế</p>
      <h2 style="color:#fff; font-family:'Playfair Display',serif; font-size:clamp(2rem,5vw,3.2rem); font-weight:700; margin-bottom:16px;">Một Trải Nghiệm Độc Đáo</h2>
      <div style="width:50px; height:2px; background:#cda45e; margin:0 auto 20px;"></div>
      <p style="color:#aaaaaa; font-family:'Poppins',sans-serif; font-size:15px; max-width:560px; margin:0 auto; line-height:1.8;">
        Khám phá bản giao hưởng hương vị nơi truyền thống cổ xưa hòa quyện với nghệ thuật hiện đại.<br>
        Mỗi món ăn là một kiệt tác được tuyển chọn kỹ lưỡng, không chỉ để thưởng thức mà còn để cảm nhận.
      </p>
    </div>

    <!-- GALLERY WRAPPER (2 hàng cuộn liên tục) -->
    <div class="menu-gallery-wrapper" style="position:relative;">

      <!-- Hàng 1 — cuộn sang trái -->
      <div class="menu-marquee-track" style="margin-bottom:8px;">
        <div class="menu-marquee-inner marquee-left">
          <?php
          // Nhân đôi để loop liền mạch
          $display_row1 = array_merge($row1, $row1);
          foreach ($display_row1 as $f): ?>
            <div class="menu-gallery-item">
              <img src="public/assets/img/menu/<?= htmlspecialchars($f['image']) ?>"
                   alt="<?= htmlspecialchars($f['name']) ?>"
                   onerror="this.src='public/assets/img/menu/default.jpg'">
              <div class="menu-gallery-overlay">
                <span class="menu-gallery-name"><?= htmlspecialchars($f['name']) ?></span>
                <span class="menu-gallery-price"><?= number_format($f['price'], 0, ',', '.') ?>đ</span>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- NÚT Ở GIỮA (đè lên giữa 2 hàng) -->
      <div class="menu-center-btn">
        <a href="menu.php" class="menu-explore-btn">MENU</a>
      </div>

      <!-- Hàng 2 — cuộn sang phải (ngược chiều) -->
      <div class="menu-marquee-track" style="margin-top:8px;">
        <div class="menu-marquee-inner marquee-right">
          <?php
          $display_row2 = array_merge($row2, $row2);
          foreach ($display_row2 as $f): ?>
            <div class="menu-gallery-item">
              <img src="public/assets/img/menu/<?= htmlspecialchars($f['image']) ?>"
                   alt="<?= htmlspecialchars($f['name']) ?>"
                   onerror="this.src='public/assets/img/menu/default.jpg'">
              <div class="menu-gallery-overlay">
                <span class="menu-gallery-name"><?= htmlspecialchars($f['name']) ?></span>
                <span class="menu-gallery-price"><?= number_format($f['price'], 0, ',', '.') ?>đ</span>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

    </div><!-- /gallery-wrapper -->
  </section>

  <style>
    /* ========== MENU GALLERY INFINITE SCROLL ========== */
    .menu-gallery-wrapper {
      width: 100%;
      overflow: hidden;
    }

    .menu-marquee-track {
      overflow: hidden;
      width: 100%;
      position: relative;
    }

    .menu-marquee-inner {
      display: flex;
      gap: 8px;
      width: max-content;
      will-change: transform;
    }

    /* Hàng 1: chạy sang trái */
    .marquee-left {
      animation: scrollLeft 40s linear infinite;
    }
    /* Hàng 2: chạy sang phải */
    .marquee-right {
      animation: scrollRight 40s linear infinite;
    }

    @keyframes scrollLeft {
      0%   { transform: translateX(0); }
      100% { transform: translateX(-50%); }
    }
    @keyframes scrollRight {
      0%   { transform: translateX(-50%); }
      100% { transform: translateX(0); }
    }

    /* Dừng khi hover */
    .menu-marquee-track:hover .menu-marquee-inner {
      animation-play-state: paused;
    }

    /* Từng ô ảnh */
    .menu-gallery-item {
      position: relative;
      width: 240px;
      height: 200px;
      flex-shrink: 0;
      overflow: hidden;
      border-radius: 4px;
      cursor: pointer;
    }

    .menu-gallery-item img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      transition: transform 0.6s ease;
    }

    .menu-gallery-item:hover img {
      transform: scale(1.08);
    }

    /* Overlay tên + giá hiện khi hover */
    .menu-gallery-overlay {
      position: absolute;
      inset: 0;
      background: linear-gradient(to top, rgba(0,0,0,0.75) 0%, transparent 60%);
      display: flex;
      flex-direction: column;
      justify-content: flex-end;
      padding: 12px;
      opacity: 0;
      transition: opacity 0.4s;
    }

    .menu-gallery-item:hover .menu-gallery-overlay {
      opacity: 1;
    }

    .menu-gallery-name {
      color: #fff;
      font-family: 'Poppins', sans-serif;
      font-size: 13px;
      font-weight: 600;
      display: block;
    }

    .menu-gallery-price {
      color: #cda45e;
      font-family: 'Poppins', sans-serif;
      font-size: 12px;
      font-weight: 700;
    }

    /* ===== NÚT EXPLORE MENU Ở GIỮA ===== */
    .menu-center-btn {
      position: relative;
      z-index: 10;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 0;           /* không chiếm chiều cao — đè lên khoảng cách giữa 2 hàng */
      margin: 0;
    }

    .menu-explore-btn {
      display: inline-block;
      padding: 14px 44px;
      background: rgba(13,13,13,0.85);
      border: 1.5px solid #cda45e;
      color: #cda45e;
      font-family: 'Poppins', sans-serif;
      font-size: 12px;
      font-weight: 700;
      letter-spacing: 4px;
      text-transform: uppercase;
      text-decoration: none;
      backdrop-filter: blur(6px);
      transform: translateY(-50%);   /* Căn giữa đúng theo chiều dọc */
      transition: background 0.3s, color 0.3s, box-shadow 0.3s;
      border-radius: 2px;
      white-space: nowrap;
    }

    .menu-explore-btn:hover {
      background: #cda45e;
      color: #000;
      box-shadow: 0 0 30px rgba(205,164,94,0.4);
    }
  </style>

  <section id="combos" style="background: #0A1C1A; padding: 140px 0; border-top: 1px solid rgba(212,176,106,0.15); border-bottom: 1px solid rgba(212,176,106,0.15);">
    <div class="container">
      <div class="section-title text-center mb-5">
        <h2 style="color: #cda45e; font-family: 'Playfair Display', serif; font-size: 36px; font-weight: 700;">Bộ Sưu Tập Hương Vị</h2>
        <p style="color: #fff; font-size: 18px; font-style: italic;">Lựa chọn hoàn hảo để chia sẻ niềm vui</p>
      </div>

      <div class="row g-4 justify-content-center">
        <?php if ($stmt_combos): ?>
          <?php while ($row = $stmt_combos->fetch(PDO::FETCH_ASSOC)): ?>
            <div class="col-lg-4 col-md-6">
              <div class="combo-card-custom">
                <div class="combo-img mb-3">
                  <img src="public/assets/img/combos/<?= htmlspecialchars($row['image'] ?: 'default-combo.jpg') ?>"
                    alt="<?= htmlspecialchars($row['name']) ?>"
                    onerror="this.src='public/assets/img/combos/default-combo.jpg'">
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <h4 class="combo-name-custom"><?= htmlspecialchars($row['name']) ?></h4>
                  <span class="combo-price-custom"><?= number_format($row['price'], 0, ',', '.') ?>đ</span>
                </div>
                <p class="combo-desc-custom"><?= htmlspecialchars($row['description']) ?></p>
                <div class="combo-items-list">
                  <small>Gồm các món:</small>
                  <p><?= htmlspecialchars($row['list_foods']) ?></p>
                </div>
                <button onclick="addToCart('combo', <?= $row['id'] ?>)" class="btn-combo-order">Đặt ngay</button>
              </div>
            </div>
          <?php endwhile; ?>
        <?php endif; ?>
      </div>
    </div>
  <section id="chefs" class="chefs" style="background: #143B36; padding: 140px 0;">
    <div class="container">
      <div class="section-title text-center mb-5">
        <h2 class="chefs-subtitle">Đội ngũ đầu bếp</h2>
        <p class="chefs-title">Những nghệ nhân ẩm thực hàng đầu</p>
      </div>
      <div class="row justify-content-center">
        <?php if (!empty($home_chefs)): ?>
          <?php foreach ($home_chefs as $hchef): ?>
            <div class="col-lg-4 col-md-6 mb-4">
              <div class="chef-member-card">
                <?php if (!empty($hchef['image'])): ?>
                  <img src="public/assets/img/chefs/<?= htmlspecialchars($hchef['image']) ?>"
                    class="img-fluid" alt="<?= htmlspecialchars($hchef['name']) ?>"
                    onerror="this.style.display='none'"
                    style="width:100%;height:220px;object-fit:cover;border-radius:5px;margin-bottom:15px;">
                <?php else: ?>
                  <div style="width:100%;height:220px;background:linear-gradient(135deg,#2c3e50,#1a252f);display:flex;align-items:center;justify-content:center;border-radius:5px;margin-bottom:15px;">
                    <i class="bi bi-person" style="font-size:4rem;color:#cda45e;opacity:.5;"></i>
                  </div>
                <?php endif; ?>
                <div class="member-info">
                  <h4><?= htmlspecialchars($hchef['name']) ?></h4>
                  <span><?= htmlspecialchars($hchef['position']) ?></span>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="text-center" style="color:#666;">Chưa có thông tin đầu bếp.</p>
        <?php endif; ?>
      </div>
      <div class="text-center mt-4">
        <a href="chefs.php" class="btn-view-all-custom">Xem tất cả đầu bếp</a>
      </div>
    </div>
  </section>

</main>

<?php include __DIR__ . '/views/client/layouts/footer.php'; ?>

<style>
  /* --- FINE DINING LUXURY ANIMATIONS --- */
  @keyframes kenBurnsLux {
    0% { transform: scale(1); }
    100% { transform: scale(1.08); }
  }
  .carousel-item.active {
    animation: kenBurnsLux 20s ease-out forwards;
  }
  .carousel-item .container {
    animation: reverseKenBurnsLux 20s ease-out forwards;
  }
  @keyframes reverseKenBurnsLux {
    0% { transform: scale(1); }
    100% { transform: scale(0.925); }
  }
  
  .section-title h2, .section-title p {
    animation: fadeInUpSlow 2s ease-out forwards;
  }
  @keyframes fadeInUpSlow {
    0% { opacity: 0; transform: translateY(30px); }
    100% { opacity: 1; transform: translateY(0); }
  }
  /* ------------------------------------- */
  .btn-menu-custom,
  .btn-book-custom,
  .btn-view-all-custom {
    font-weight: 600;
    font-size: 13px;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    display: inline-block;
    padding: 12px 30px;
    border-radius: 50px;
    transition: 0.3s;
    color: white;
    border: 2px solid #cda45e;
    text-decoration: none;
  }

  .btn-book-custom {
    background: #cda45e;
  }

  .btn-book-custom:hover,
  .btn-menu-custom:hover,
  .btn-view-all-custom:hover {
    background: #cda45e;
    color: #fff;
  }

  .explore-menu-link {
    font-family: 'Poppins', sans-serif;
    font-size: 0.9rem;
    letter-spacing: 3px;
    color: #d9ba85;
    text-decoration: none;
    text-transform: uppercase;
    border-bottom: 2px solid #d9ba85;
    padding-bottom: 8px;
    transition: 0.3s;
    font-weight: 600;
  }

  .combo-card-custom {
    background: #143B36;
    border: 1px solid rgba(212,176,106,0.2);
    padding: 25px;
    height: 100%;
    display: flex;
    flex-direction: column;
    transition: 0.8s cubic-bezier(0.2, 0.8, 0.2, 1);
    border-radius: 10px;
  }

  .combo-card-custom:hover {
    transform: translateY(-5px);
    border-color: rgba(205, 164, 94, 0.6);
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
  }

  .combo-img img {
    width: 100%;
    height: 220px;
    object-fit: cover;
    border-radius: 5px;
    transition: 1s cubic-bezier(0.2, 0.8, 0.2, 1);
  }

  .combo-img:hover img {
    transform: scale(1.05);
  }

  .combo-name-custom {
    color: #fff;
    margin: 0;
    font-size: 22px;
    font-family: 'Poppins', sans-serif;
  }

  .combo-price-custom {
    color: #cda45e;
    font-weight: 700;
    font-size: 18px;
  }

  .combo-desc-custom {
    color: #aaaaaa;
    font-size: 14px;
    font-style: italic;
    flex-grow: 1;
    margin: 10px 0;
  }

  .combo-items-list {
    border-top: 1px dashed rgba(212,176,106,0.2);
    padding-top: 15px;
    margin-bottom: 20px;
  }

  .combo-items-list small {
    color: #cda45e;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-size: 11px;
    font-weight: 600;
  }

  .combo-items-list p {
    color: #ced4da;
    font-size: 13px;
    margin: 5px 0 0 0;
  }

  .btn-combo-order {
    background: transparent;
    color: #fff;
    border: 2px solid #cda45e;
    width: 100%;
    padding: 12px;
    border-radius: 50px;
    font-weight: 600;
    transition: 0.3s;
    text-transform: uppercase;
  }

  .btn-combo-order:hover {
    background: #cda45e !important;
    color: #000 !important;
  }

  .chefs-subtitle {
    font-size: 14px;
    font-weight: 500;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: #aaaaaa;
    font-family: 'Poppins', sans-serif;
  }

  .chefs-title {
    margin: 15px 0 0 0;
    font-size: 36px;
    font-weight: 700;
    font-family: 'Playfair Display', serif;
    color: #cda45e;
  }

  .chef-member-card {
    background: #0A1C1A;
    padding: 40px;
    border: 1px solid rgba(212,176,106,0.2);
    text-align: center;
    transition: 0.8s cubic-bezier(0.2, 0.8, 0.2, 1);
    border-radius: 5px;
  }

  .chef-member-card img {
    border-radius: 5px;
    margin-bottom: 15px;
  }

  .chef-member-card h4 {
    font-weight: 700;
    margin-bottom: 5px;
    font-size: 18px;
    color: #fff;
  }

  .chef-member-card span {
    display: block;
    font-size: 15px;
    font-style: italic;
    color: #cda45e;
  }
</style>