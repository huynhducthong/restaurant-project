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

  // 5.3 Truy vấn Chủ đề
  $active_themes = $db->query("SELECT * FROM themes WHERE is_active = 1 AND (start_date IS NULL OR start_date <= NOW()) AND (end_date IS NULL OR end_date >= NOW()) ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
  foreach ($active_themes as &$t) {
      $t_combos = $db->prepare("SELECT c.*, GROUP_CONCAT(f.name ORDER BY f.name SEPARATOR ', ') as list_foods
                                FROM combos c
                                LEFT JOIN combo_items ci ON c.id = ci.combo_id
                                LEFT JOIN foods f ON ci.food_id = f.id
                                WHERE c.theme_id = ? AND c.is_active = 1
                                GROUP BY c.id ORDER BY c.id DESC");
      $t_combos->execute([$t['id']]);
      $t['combos'] = $t_combos->fetchAll(PDO::FETCH_ASSOC);
      
      $t_foods = $db->prepare("SELECT f.*, c.name as cat_name FROM foods f LEFT JOIN categories c ON f.category_id = c.id WHERE f.theme_id = ? AND f.is_active = 1");
      $t_foods->execute([$t['id']]);
      $t['foods'] = $t_foods->fetchAll(PDO::FETCH_ASSOC);
  }
  unset($t);

  // 5.4 Lấy danh sách đầu bếp nổi bật cho trang chủ
  $stmt_home_chefs = $db->prepare("SELECT * FROM chefs WHERE is_active = 1 ORDER BY is_featured DESC, sort_order ASC, id ASC LIMIT 3");
  $stmt_home_chefs->execute();
  $home_chefs = $stmt_home_chefs->fetchAll(PDO::FETCH_ASSOC);
  // 5.5 Lấy danh sách ảnh Gallery cho trang chủ (7 ảnh)
  $stmt_galleries = $db->prepare("SELECT * FROM galleries WHERE is_active = 1 ORDER BY sort_order ASC, id DESC LIMIT 7");
  $stmt_galleries->execute();
  $home_galleries = $stmt_galleries->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
  $home_chefs = [];
  $home_galleries = [];
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

          <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(to bottom, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0.2) 25%, rgba(0,0,0,0.2) 75%, #F6F2E9 100%);"></div>

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
                    border-radius:0px;
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
  <img id="hoverImageTooltip" class="menu-hover-tooltip" src="" alt="">
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tooltip = document.getElementById('hoverImageTooltip');
    const triggers = document.querySelectorAll('.menu-hover-trigger');
    
    triggers.forEach(trigger => {
        trigger.addEventListener('mousemove', function(e) {
            tooltip.src = this.getAttribute('data-img');
            tooltip.style.left = e.pageX + 'px';
            tooltip.style.top = e.pageY + 'px';
            tooltip.style.opacity = '1';
            tooltip.style.transform = 'translate(15px, -50%) scale(1)';
        });
        trigger.addEventListener('mouseleave', function() {
            tooltip.style.opacity = '0';
            tooltip.style.transform = 'translate(15px, -50%) scale(0.95)';
        });
    });
});
</script>

</body>
</html></section>

<main id="main">
  <section id="about" class="about-section" style="background: #F6F2E9; color: #222222; padding: 140px 0; overflow: hidden;">
    <div class="container-fluid px-0">
      <div class="row g-0 align-items-center">
        <div class="col-lg-7" style="padding-left: 5%; padding-right: 30px;">
          <div class="video-wrapper" style="position: relative; width: 100%; border-radius: 0; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border: 1px solid #C9A66B; background: #fff;">

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
            <?php
              $v_title = $video_data['title'] ?? '';
              $v_desc  = $video_data['description'] ?? '';
              
              if (empty($v_title)) {
                  $v_title = "Câu Chuyện <br> Về " . htmlspecialchars($settings['restaurant_name'] ?? 'Restaurantly');
              }
              if (empty($v_desc)) {
                  $v_desc = "Nằm giữa lòng " . htmlspecialchars($settings['address'] ?? 'Biên Hòa') . ", chúng tôi mang đến một không gian ẩm thực tinh tế.";
              }
            ?>
            <h2 style="font-family: 'Playfair Display', serif; color: #4F5B3A; font-size: 3rem; margin-bottom: 25px; font-weight: 700; line-height: 1.2;">
              <?= $v_title ?>
            </h2>
            <p style="font-family: 'Poppins', sans-serif; font-weight: 300; line-height: 2; color: #222222; font-size: 1.15rem; margin-bottom: 25px;">
              <?= nl2br(htmlspecialchars($v_desc)) ?>
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section id="menu" class="menu section-bg" style="background: #ffffff; padding: 100px 0; overflow: hidden;">

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

    // Nếu ít hơn 6 món và có dữ liệu, nhân đôi để đủ ảnh chạy đẹp (Tránh lỗi lặp vô hạn nếu mảng rỗng)
    if (count($row1) > 0) {
        while (count($row1) < 6) $row1 = array_merge($row1, $row1);
    }
    if (count($row2) > 0) {
        while (count($row2) < 6) $row2 = array_merge($row2, $row2);
    }
    ?>

    <!-- Tiêu đề -->
    <div class="text-center mb-5" style="position:relative; z-index:2;">
      <p style="color:#C9A66B; font-family:'Poppins',sans-serif; font-size:13px; letter-spacing:4px; text-transform:uppercase; margin-bottom:12px;">Hương Vị Tinh Tế</p>
      <h2 style="color:#4F5B3A; font-family:'Playfair Display',serif; font-size:clamp(2rem,5vw,3.2rem); font-weight:700; margin-bottom:16px;">Một Trải Nghiệm Độc Đáo</h2>
      <div style="width:50px; height:2px; background:#C9A66B; margin:0 auto 20px;"></div>
      <p style="color:#666666; font-family:'Poppins',sans-serif; font-size:15px; max-width:560px; margin:0 auto; line-height:1.8;">
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
      border-radius: 0;
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
      background: linear-gradient(to top, rgba(79,91,58,0.9) 0%, transparent 80%);
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
      color: #C9A66B;
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
      background: #4F5B3A;
      border: 1.5px solid #4F5B3A;
      color: #C9A66B;
      font-family: 'Poppins', sans-serif;
      font-size: 12px;
      font-weight: 700;
      letter-spacing: 4px;
      text-transform: uppercase;
      text-decoration: none;
      backdrop-filter: blur(6px);
      transform: translateY(-50%);   /* Căn giữa đúng theo chiều dọc */
      transition: background 0.3s, color 0.3s, box-shadow 0.3s;
      border-radius: 0;
      white-space: nowrap;
    }

    .menu-explore-btn:hover {
      background: #cda45e;
      color: #000;
      box-shadow: none; border-color: #C9A66B;
    }
    
    .menu-hover-tooltip {
      position: absolute;
      width: 200px;
      height: 200px;
      border-radius: 50%;
      object-fit: cover;
      z-index: 9999;
      pointer-events: none;
      opacity: 0;
      transition: opacity 0.2s ease, transform 0.2s ease;
      box-shadow: 0 15px 35px rgba(0,0,0,0.2);
      border: 4px solid #fff;
      transform: translate(15px, -50%) scale(0.95);
    }
    .menu-hover-trigger:hover {
      color: #C9A66B !important;
      transition: color 0.3s ease;
    }
    .menu-list-item:hover {
      background: rgba(201, 166, 107, 0.03);
    }
  </style>

  <section id="combos" style="background: #F6F2E9; padding: 160px 0; border-top: 1px solid rgba(79,91,58,0.1); border-bottom: 1px solid rgba(79,91,58,0.1); position: relative;">
    <!-- Radial subtle glow behind the whole section -->
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 60vw; height: 60vw; background: radial-gradient(circle, rgba(79,91,58,0.05) 0%, transparent 70%); pointer-events: none; z-index: 0;"></div>
    
    <div class="container" style="position: relative; z-index: 1;">
      <div class="section-title text-center mb-5" style="margin-bottom: 80px !important;">
        <p style="color: #C9A66B; font-family: 'Poppins', sans-serif; font-size: 11px; font-weight: 500; letter-spacing: 4px; text-transform: uppercase; margin-bottom: 15px;">Tuyển Chọn Thượng Hạng</p>
        <h2 style="color: #4F5B3A; font-family: 'Playfair Display', serif; font-size: 42px; font-weight: 400; letter-spacing: 1px;">Bộ Sưu Tập Hương Vị</h2>
        <div style="width: 1px; height: 40px; background: #C9A66B; margin: 20px auto;"></div>
      </div>

      <div class="row g-5 justify-content-center">
        <?php if (!empty($active_themes)): ?>
          <?php foreach ($active_themes as $t): ?>
            <?php if(empty($t['combos']) && empty($t['foods'])) continue; ?>
            <div class="col-12 text-center mt-5 mb-3">
              <?php if($t['image']): ?>
                <div style="max-width: 800px; height: 300px; margin: 0 auto 30px; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
                  <img src="<?= htmlspecialchars($t['image']) ?>" alt="<?= htmlspecialchars($t['name']) ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                </div>
              <?php endif; ?>
              <h3 style="color: #4F5B3A; font-family: 'Playfair Display', serif; font-size: 32px; border-bottom: 1px solid #C9A66B; display: inline-block; padding-bottom: 10px;"><?= htmlspecialchars($t['name']) ?></h3>
              <p style="color: #666; font-style: italic; max-width: 600px; margin: 15px auto 0;"><?= htmlspecialchars($t['description']) ?></p>
            </div>
            
            <?php if(!empty($t['combos'])): ?>
              <div class="col-12 mt-5">
                <h4 style="text-align: center; color: #4F5B3A; font-family: 'Playfair Display', serif; margin-bottom: 30px; font-size: 24px; letter-spacing: 2px;">SET MENU (TASTING MENU)</h4>
                <div class="row g-5 justify-content-center">
                  <?php foreach($t['combos'] as $row): ?>
                    <div class="col-lg-6 col-md-12">
                      <div class="menu-list-item" style="display: flex; align-items: center; border-bottom: 1px dashed rgba(79,91,58,0.2); padding: 15px 10px; cursor: pointer; transition: background 0.3s ease;" onclick="window.location.href='combo_detail.php?id=<?= $row['id'] ?>'">
                        <div style="flex-grow: 1;">
                          <h5 class="menu-hover-trigger" data-img="public/assets/img/combos/<?= htmlspecialchars($row['image'] ?: 'default-combo.jpg') ?>" style="color: #222; font-family: 'Playfair Display', serif; font-size: 1.3rem; margin-bottom: 5px; display: inline-block;">
                            <?= htmlspecialchars($row['name']) ?>
                          </h5>
                          <p style="color: #666; font-size: 13px; margin: 0; line-height: 1.6; max-width: 90%;"><?= htmlspecialchars($row['description']) ?></p>
                          <div style="font-size: 11px; color: #999; margin-top: 8px;"><i class="bi bi-star-fill me-1" style="color:#C9A66B; font-size:9px;"></i><?= htmlspecialchars(str_replace(',', ' • ', $row['list_foods'])) ?></div>
                        </div>
                        <div style="color: #C9A66B; font-weight: 500; font-family: 'Playfair Display', serif; font-size: 1.3rem; margin-left: 20px; white-space: nowrap;">
                          <?= number_format($row['price'], 0, ',', '.') ?>đ
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>

            <?php if(!empty($t['foods'])): ?>
              <div class="col-12 mt-5 mb-4">
                <h4 style="text-align: center; color: #4F5B3A; font-family: 'Playfair Display', serif; margin-bottom: 30px; font-size: 24px; letter-spacing: 2px;">MÓN LẺ (A LA CARTE)</h4>
                <div class="row g-5 justify-content-center">
                  <?php foreach($t['foods'] as $f): ?>
                    <div class="col-lg-6 col-md-12">
                      <div class="menu-list-item" style="display: flex; align-items: center; border-bottom: 1px dashed rgba(79,91,58,0.2); padding: 15px 10px; transition: background 0.3s ease;">
                        <div style="flex-grow: 1;">
                          <h5 class="menu-hover-trigger" data-img="public/assets/img/menu/<?= htmlspecialchars($f['image'] ?: 'default-food.jpg') ?>" style="color: #222; font-family: 'Playfair Display', serif; font-size: 1.3rem; margin-bottom: 5px; display: inline-block; cursor: default;">
                            <?= htmlspecialchars($f['name']) ?>
                          </h5>
                          <p style="color: #666; font-size: 13px; margin: 0; line-height: 1.6; max-width: 90%;"><?= htmlspecialchars($f['description']) ?></p>
                        </div>
                        <div style="color: #C9A66B; font-weight: 500; font-family: 'Playfair Display', serif; font-size: 1.3rem; margin-left: 20px; white-space: nowrap;">
                          <?= number_format($f['price'], 0, ',', '.') ?>đ
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>

          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  <section id="chefs" class="chefs" style="background: #ffffff; padding: 140px 0; position: relative;">
    <div class="container" style="position: relative; z-index: 1;">
      <div class="section-title text-center mb-5" style="margin-bottom: 70px !important;">
        <p style="color: #C9A66B; font-family: 'Poppins', sans-serif; font-size: 11px; font-weight: 500; letter-spacing: 4px; text-transform: uppercase; margin-bottom: 15px;">Đội Ngũ Đầu Bếp</p>
        <h2 style="color: #4F5B3A; font-family: 'Playfair Display', serif; font-size: 42px; font-weight: 400; letter-spacing: 1px;">Những Nghệ Nhân Ẩm Thực Hàng Đầu</h2>
        <div style="width: 1px; height: 40px; background: #C9A66B; margin: 20px auto;"></div>
      </div>
      
      <div class="row justify-content-center g-4">
        <?php if (!empty($home_chefs)): ?>
          <?php 
          $quotes = [
            "Ẩm thực không chỉ là hương vị, nó là sự tinh tế của ký ức.",
            "Từng nguyên liệu đều có tiếng nói riêng của nó.",
            "Nấu ăn là nghệ thuật kể chuyện không dùng lời."
          ];
          $idx = 0;
          foreach ($home_chefs as $hchef): 
            $quote = $quotes[$idx % count($quotes)];
            $idx++;
          ?>
            <div class="col-lg-4 col-md-6 mb-4">
              <div class="luxury-chef-card">
                <div class="chef-img-wrapper">
                  <?php if (!empty($hchef['image'])): ?>
                    <img src="public/assets/img/chefs/<?= htmlspecialchars($hchef['image']) ?>"
                      alt="<?= htmlspecialchars($hchef['name']) ?>"
                      onerror="this.src='public/assets/img/chefs/default-chef.jpg'">
                  <?php else: ?>
                    <div class="chef-placeholder-img">
                      <i class="bi bi-person"></i>
                    </div>
                  <?php endif; ?>
                  
                  <div class="chef-overlay">
                    <p class="chef-quote">"<?= $quote ?>"</p>
                    <div class="chef-info">
                      <h4 class="chef-name"><?= htmlspecialchars($hchef['name']) ?></h4>
                      <span class="chef-title"><?= htmlspecialchars($hchef['position']) ?></span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="text-center" style="color:#666666; font-style: italic;">Chưa có thông tin đầu bếp.</p>
        <?php endif; ?>
      </div>
      
      <div class="text-center mt-5">
        <a href="chefs.php" class="btn-combo-order" style="display:inline-block; width: auto; padding: 12px 36px;">TẤT CẢ ĐẦU BẾP</a>
      </div>
    </div>
  </section>

  <!-- ATMOSPHERE & GALLERY SECTION -->
  <section id="atmosphere" style="background: #F6F2E9; padding: 140px 0; position: relative;">
    <div class="container" style="position: relative; z-index: 1;">
      <div class="section-title text-center mb-5" style="margin-bottom: 70px !important;">
        <p style="color: #C9A66B; font-family: 'Poppins', sans-serif; font-size: 11px; font-weight: 500; letter-spacing: 4px; text-transform: uppercase; margin-bottom: 15px;">Không Gian & Trải Nghiệm</p>
        <h2 style="color: #4F5B3A; font-family: 'Playfair Display', serif; font-size: 42px; font-weight: 400; letter-spacing: 1px;">Kiệt Tác Kiến Trúc Tĩnh Lặng</h2>
        <div style="width: 1px; height: 40px; background: #C9A66B; margin: 20px auto;"></div>
      </div>

      <!-- Asymmetric Grid Gallery -->
      <div class="atmosphere-grid">
        <?php if (!empty($home_galleries)): ?>
          <?php 
          $item_class = 1;
          foreach ($home_galleries as $gallery): 
          ?>
            <div class="atmo-item item-<?= $item_class ?>">
              <img src="public/assets/img/gallery/<?= htmlspecialchars($gallery['image_url']) ?>" alt="<?= htmlspecialchars($gallery['title'] ?? 'Atmosphere') ?>">
            </div>
          <?php 
            $item_class++;
            // Nếu có hơn 7 ảnh, từ ảnh thứ 8 trở đi sẽ bị ẩn
            if ($item_class > 8) $item_class = 8;
          endforeach; 
          ?>
        <?php else: ?>
          <!-- Dummy content nếu DB rỗng -->
          <div class="atmo-item item-1">
            <img src="public/assets/img/hero/1776687242_hero-bg.jpg" alt="Atmosphere" onerror="this.src='public/assets/img/about-bg.jpg'">
          </div>
          <div class="atmo-item item-2">
            <img src="public/assets/img/hero/1776687610_hero-bg-2.jpg" alt="Atmosphere" onerror="this.src='public/assets/img/about-bg.jpg'">
          </div>
          <div class="atmo-item item-3">
            <img src="public/assets/img/about-bg.jpg" alt="Atmosphere" onerror="this.src='public/assets/img/about.jpg'">
          </div>
          <div class="atmo-item item-4">
            <img src="public/assets/img/hero/1776687242_hero-bg.jpg" alt="Atmosphere" onerror="this.src='public/assets/img/about-bg.jpg'">
          </div>
        <?php endif; ?>
      </div>

      <div class="text-center mt-5 pt-4">
        <a href="#" class="btn-combo-order" style="display:inline-block; width: auto; padding: 12px 36px;">KHÁM PHÁ KHÔNG GIAN TẠI ĐÂY</a>
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
    color: #4F5B3A;
    text-decoration: none;
    text-transform: uppercase;
    border-bottom: 2px solid #d9ba85;
    padding-bottom: 8px;
    transition: 0.3s;
    font-weight: 600;
  }

  /* --- MICHELIN COMBO CARDS --- */
  .combo-card-custom {
    background: #fff;
    border: 1px solid rgba(79,91,58,0.2);
    height: 100%;
    display: flex;
    flex-direction: column;
    transition: all 0.6s cubic-bezier(0.2, 0.8, 0.2, 1);
    overflow: hidden;
    position: relative;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); border-radius: 0;
  }

  .combo-card-custom:hover {
    transform: translateY(-8px);
    border-color: #4F5B3A;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
  }

  .combo-img-wrap {
    position: relative;
    overflow: hidden;
  }

  /* Gradient overlay to smoothly blend image into background */
  .combo-img-wrap::after {
    content: '';
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 60px;
    background: linear-gradient(to top, #fff, transparent);
    pointer-events: none;
  }

  .combo-img-wrap img {
    width: 100%;
    height: 280px;
    object-fit: cover;
    transition: transform 1.5s cubic-bezier(0.2, 0.8, 0.2, 1);
  }

  .combo-card-custom:hover .combo-img-wrap img {
    transform: scale(1.05);
  }

  .combo-badge {
    position: absolute;
    top: 20px;
    left: 20px;
    background: #C9A66B;
    backdrop-filter: blur(8px);
    border: 1px solid #C9A66B;
    color: #222222;
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: 2px;
    padding: 6px 14px;
    z-index: 2;
    border-radius: 0;
    font-weight: 500;
  }

  .combo-content {
    padding: 0 35px 35px 35px;
    display: flex;
    flex-direction: column;
    flex-grow: 1;
    position: relative;
    z-index: 1;
    margin-top: -15px; /* Pull content up slightly */
  }

  .combo-name-custom {
    color: #222222;
    margin: 0 0 8px 0;
    font-size: 26px;
    font-weight: 400;
    font-family: 'Playfair Display', serif;
    letter-spacing: 1px;
    line-height: 1.3;
  }

  .combo-desc-custom {
    color: #666666;
    font-size: 13px;
    font-weight: 300;
    line-height: 1.6;
    margin-bottom: 20px;
    font-style: italic;
  }

  .combo-divider {
    width: 40px;
    height: 1px;
    background: rgba(198, 167, 106, 0.3);
    margin: 0 0 20px 0;
  }

  .combo-items-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 35px;
    flex-grow: 1;
  }

  .combo-items-list span {
    color: #222222;
    font-size: 13.5px;
    font-weight: 300;
    letter-spacing: 0.5px;
    display: flex;
    align-items: flex-start;
  }

  .combo-items-list span::before {
    content: '•';
    color: #C9A66B;
    margin-right: 12px;
    font-size: 14px;
  }

  .combo-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 1px solid rgba(255, 255, 255, 0.05);
    padding-top: 20px;
  }

  .combo-price-custom {
    color: #C9A66B;
    font-weight: 500;
    font-size: 15px;
    letter-spacing: 1px;
  }

  .btn-combo-order {
    background: transparent;
    color: #C9A66B;
    border: 1px solid #C9A66B;
    padding: 10px 24px;
    font-weight: 500;
    font-size: 11px;
    letter-spacing: 3px;
    transition: all 0.4s ease;
    text-transform: uppercase;
  }

  .btn-combo-order:hover {
    background: rgba(198, 167, 106, 0.1);
    color: #222222;
    border-color: #C9A66B;
  }

  /* --- MICHELIN CHEFS SECTION --- */
  .chefs-subtitle {
    font-size: 11px;
    font-weight: 500;
    letter-spacing: 4px;
    text-transform: uppercase;
    color: #C9A66B;
    font-family: 'Poppins', sans-serif;
  }

  .chefs-title {
    margin: 15px 0 0 0;
    font-size: 42px;
    font-weight: 400;
    font-family: 'Playfair Display', serif;
    color: #222222;
    letter-spacing: 1px;
  }

  .luxury-chef-card {
    border-radius: 0;
    overflow: hidden;
    position: relative;
    background: #fff;
    box-shadow: 0 5px 25px rgba(0,0,0,0.05);
  }

  .chef-img-wrapper {
    position: relative;
    overflow: hidden;
    height: 480px;
  }

  .chef-img-wrapper img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 1.5s cubic-bezier(0.2, 0.8, 0.2, 1);
  }

  .chef-placeholder-img {
    width: 100%;
    height: 100%;
    background: #E0DDD5;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  
  .chef-placeholder-img i {
    font-size: 5rem;
    color: rgba(198, 167, 106, 0.3);
  }

  .luxury-chef-card:hover .chef-img-wrapper img {
    transform: scale(1.06);
  }

  .chef-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 60px 30px 30px 30px;
    background: linear-gradient(to top, rgba(79,91,58, 0.95) 0%, rgba(79,91,58, 0.7) 40%, transparent 100%);
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    text-align: center;
    transition: all 0.6s ease;
  }

  .chef-quote {
    color: rgba(246, 241, 231, 0.8);
    font-family: 'Playfair Display', serif;
    font-size: 16px;
    font-style: italic;
    line-height: 1.6;
    margin-bottom: 25px;
    opacity: 0;
    transform: translateY(20px);
    transition: all 0.6s cubic-bezier(0.2, 0.8, 0.2, 1);
  }

  .luxury-chef-card:hover .chef-quote {
    opacity: 1;
    transform: translateY(0);
  }

  .chef-info {
    position: relative;
    padding-top: 20px;
  }

  .chef-info::before {
    content: '';
    position: absolute;
    top: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 30px;
    height: 1px;
    background: #C6A76A;
  }

  .chef-name {
    color: #222222;
    font-size: 24px;
    font-weight: 400;
    font-family: 'Playfair Display', serif;
    margin-bottom: 5px;
    letter-spacing: 1px;
  }

  .chef-title {
    color: #C9A66B;
    font-size: 11px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 3px;
    font-family: 'Poppins', sans-serif;
  }

  /* --- ATMOSPHERE & GALLERY --- */
  .atmosphere-grid {
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    grid-auto-rows: 250px;
    gap: 20px;
  }

  .atmo-item {
    position: relative;
    overflow: hidden;
    border-radius: 0;
    box-shadow: 0 10px 30px rgba(0,0,0,0.05);
  }

  .atmo-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    filter: grayscale(0.1) brightness(0.95);
    transition: all 1s cubic-bezier(0.2, 0.8, 0.2, 1);
  }

  .atmo-item::after {
    content: '';
    position: absolute;
    inset: 0;
    border: 1px solid rgba(79, 91, 58, 0);
    transition: all 0.6s ease;
    pointer-events: none;
  }

  .atmo-item:hover img {
    filter: grayscale(0) brightness(1.05);
    transform: scale(1.05);
  }

  .atmo-item:hover::after {
    border-color: rgba(79, 91, 58, 0.4);
    inset: 15px; /* Creates an inner border effect */
  }

  /* Asymmetric Placement */
  .item-1 {
    grid-column: span 6;
    grid-row: span 2;
  }

  .item-2 {
    grid-column: span 6;
    grid-row: span 1;
  }

  .item-3 {
    grid-column: span 3;
    grid-row: span 1;
  }

  .item-4 {
    grid-column: span 3;
    grid-row: span 1;
  }

  .item-5 {
    grid-column: span 4;
    grid-row: span 1;
  }

  .item-6 {
    grid-column: span 4;
    grid-row: span 1;
  }

  .item-7 {
    grid-column: span 4;
    grid-row: span 1;
  }

  .item-8 {
    display: none; 
  }

  /* Responsive Adjustments */
  @media (max-width: 991px) {
    .atmosphere-grid {
      display: flex;
      flex-direction: column;
    }
    .atmo-item {
      height: 300px;
    }
  }
</style>
