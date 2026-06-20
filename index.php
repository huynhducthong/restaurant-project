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

          <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(to bottom, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0.2) 25%, rgba(0,0,0,0.2) 75%, #F9F9F9 100%);"></div>

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
                    font-family: <?= $row['desc_font_family'] ?? "'Open Sans', sans-serif" ?>; 
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
                    font-family:'Open Sans', sans-serif;
                    letter-spacing:1px;
                    background-color: <?= htmlspecialchars($row['button_color'] ?? '#A88746') ?>;
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

</section>

<main id="main">
  <section id="about" class="about-section" style="background: #F9F9F9; color: #222222; padding: 140px 0; overflow: hidden;">
    <div class="container-fluid px-0">
      <div class="row g-0 align-items-center">
        <div class="col-lg-7" style="padding-left: 5%; padding-right: 30px;">
          <div class="video-wrapper" style="position: relative; width: 100%; border-radius: 0; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border: 1px solid #A88746; background: #FFFFFF;">

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
            <h2 style="font-family: 'Cormorant Garamond', serif; color: #A88746; font-size: 3rem; margin-bottom: 25px; font-weight: 700; line-height: 1.2;">
              <?= $v_title ?>
            </h2>
            <p style="font-family: 'Open Sans', sans-serif; font-weight: 300; line-height: 2; color: #222222; font-size: 1.15rem; margin-bottom: 25px;">
              <?= nl2br(htmlspecialchars($v_desc)) ?>
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section id="menu" class="menu section-bg" style="background: #FFFFFF; padding: 100px 0; overflow: hidden;">

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
      <p style="color:#A88746; font-family:'Open Sans',sans-serif; font-size:13px; letter-spacing:4px; text-transform:uppercase; margin-bottom:12px;">Hương Vị Tinh Tế</p>
      <h2 style="color:#A88746; font-family:'Cormorant Garamond', serif; font-size:clamp(2rem,5vw,3.2rem); font-weight:700; margin-bottom:16px;">Một Trải Nghiệm Độc Đáo</h2>
      <p style="color:#666666; font-family:'Open Sans',sans-serif; font-size:15px; max-width:900px; margin:0 auto; line-height:1.8;">
        Khám phá bản giao hưởng hương vị nơi truyền thống cổ xưa hòa quyện với nghệ thuật hiện đại. Mỗi món ăn là một kiệt tác được tuyển chọn kỹ lưỡng, không chỉ để thưởng thức mà còn để cảm nhận.
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
      background: linear-gradient(to top, rgba(168, 135, 70,0.9) 0%, transparent 80%);
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
      font-family: 'Open Sans', sans-serif;
      font-size: 15px;
      font-weight: 600;
      display: block;
      text-shadow: 0 2px 4px rgba(0,0,0,0.5);
    }

    .menu-gallery-price {
      color: #ffffff;
      font-family: 'Open Sans', sans-serif;
      font-size: 14px;
      font-weight: 700;
      text-shadow: 0 1px 3px rgba(0,0,0,0.6);
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
      display: inline-flex;
      justify-content: center;
      align-items: center;
      padding: 14px 44px;
      background: #E65C00;
      border: 1.5px solid #E65C00;
      color: #ffffff;
      font-family: 'Open Sans', sans-serif;
      font-size: 12px;
      font-weight: 700;
      letter-spacing: 4px;
      text-transform: uppercase;
      text-decoration: none;
      backdrop-filter: blur(6px);
      transform: translateY(-50%);
      transition: background 0.3s, color 0.3s, box-shadow 0.3s, border-color 0.3s;
      border-radius: 0;
      white-space: nowrap;
    }

    .menu-explore-btn:hover {
      background: #FF7A00;
      color: #ffffff;
      box-shadow: 0 0 20px rgba(255, 122, 0, 0.6);
      border-color: #FF7A00;
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
      color: #E65C00 !important;
      text-shadow: 0 0 8px rgba(230, 92, 0, 0.3);
      transition: all 0.3s ease;
    }
    .menu-list-item:hover {
      background: rgba(230, 92, 0, 0.04);
    }
  </style>

  <section id="combos" style="background: #F9F9F9; padding: 40px 0 0 0; border-top: 1px solid rgba(168, 135, 70,0.1); position: relative;">
    <!-- Radial subtle glow behind the whole section -->
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 60vw; height: 60vw; background: radial-gradient(circle, rgba(168, 135, 70,0.05) 0%, transparent 70%); pointer-events: none; z-index: 0;"></div>
    
    <div class="container" style="position: relative; z-index: 1;">
      <div class="section-title text-center mb-5" style="margin-bottom: 30px !important;">
        <p style="color: #A88746; font-family: 'Open Sans', sans-serif; font-size: 11px; font-weight: 500; letter-spacing: 4px; text-transform: uppercase; margin-bottom: 15px;">Tuyển Chọn Thượng Hạng</p>
        <h2 style="color: #A88746; font-family: 'Cormorant Garamond', serif; font-size: 42px; font-weight: 400; letter-spacing: 1px;">Bộ Sưu Tập Hương Vị</h2>
      </div>

      
        
        <div style="width: 100vw; position: relative; left: 50%; right: 50%; margin-left: -50vw; margin-right: -50vw; overflow: hidden;">
        
        <style>
          .cine-reveal { opacity: 0; transform: translateY(40px); transition: opacity 1s cubic-bezier(0.25, 1, 0.5, 1), transform 1s cubic-bezier(0.25, 1, 0.5, 1); }
          .cine-reveal.visible { opacity: 1; transform: translateY(0); }
          .cine-glass-card {
             background: linear-gradient(135deg, rgba(0,0,0,0.5) 0%, rgba(0,0,0,0.1) 100%); 
             backdrop-filter: blur(2px); 
             -webkit-backdrop-filter: blur(2px); 
             border: 1px solid rgba(255,255,255,0.05); 
             border-top: 1px solid rgba(0,0,0,0.15);
             border-left: 1px solid rgba(0,0,0,0.1);
             border-radius: 12px; 
             padding: 50px 40px; 
             box-shadow: 0 20px 40px rgba(0,0,0,0.3); 
             height: 100%;
             transition: transform 0.5s cubic-bezier(0.25, 1, 0.5, 1), background 0.5s, box-shadow 0.5s, border-color 0.5s;
             transform: translateZ(0); /* Hardware acceleration fix */
          }
          .cine-glass-card:hover {
             transform: translateY(-10px) translateZ(0) !important;
             background: linear-gradient(135deg, rgba(0,0,0,0.6) 0%, rgba(0,0,0,0.2) 100%);
             box-shadow: 0 30px 60px rgba(0,0,0,0.5);
             border-top: 1px solid rgba(255,255,255,0.25);
             border-left: 1px solid rgba(255,255,255,0.2);
          }
          .cine-card-left { transform: translateY(-40px); }
          .cine-card-right { transform: translateY(40px); }
          @media (max-width: 991px) {
            .cine-card-left, .cine-card-right { transform: translateY(0); }
          }
          .cine-title {
             color: #C9A66B; 
             font-family: 'Cormorant Garamond', serif; 
             font-size: clamp(3.5rem, 8vw, 6.5rem); 
             margin-bottom: 10px; 
             font-weight: 300; 
             letter-spacing: 8px; 
             text-transform: uppercase; 
             text-shadow: 0 10px 40px rgba(0,0,0,0.9);
          }
          .cine-subtitle {
             color: #E2E0D9; 
             font-style: italic; 
             font-size: 1.35rem; 
             max-width: 800px; 
             margin: 0 auto; 
             line-height: 1.8; 
             text-shadow: 0 4px 15px rgba(0,0,0,0.9); 
             letter-spacing: 2px;
             font-family: 'Cormorant Garamond', serif;
          }
        </style>

        <?php if (!empty($active_themes)): ?>
          <?php foreach ($active_themes as $t): ?>
            <?php if(empty($t['combos']) && empty($t['foods'])) continue; ?>
            
            <div class="cine-section" style="position: relative; min-height: 100vh; padding: 180px 0; margin-bottom: 0px; background: url('<?= htmlspecialchars($t['image']) ?>') center/cover fixed no-repeat; display: flex; align-items: center;">
              
              <!-- Layer 1: Dark Gradient Overlay (Top & Bottom dark, center transparent) -->
              <div style="position: absolute; inset: 0; background: linear-gradient(180deg, rgba(15,15,15,0.9) 0%, rgba(15,15,15,0.1) 25%, rgba(15,15,15,0.1) 75%, rgba(15,15,15,0.9) 100%); z-index: 1;"></div>
              
              <!-- Layer 2: Radial Glow behind title -->
              <div style="position: absolute; top: 20%; left: 50%; transform: translateX(-50%); width: 70vw; height: 50vh; background: radial-gradient(ellipse, rgba(201, 166, 107, 0.15) 0%, transparent 60%); pointer-events: none; z-index: 1;"></div>
              
              <div class="container" style="position: relative; z-index: 2;">
                <!-- Theme Header -->
                <div class="text-center mb-5 pb-5 cine-reveal" style="transition-delay: 0.1s;">
                  <h3 class="cine-title"><?= htmlspecialchars($t['name']) ?></h3>
                  <p class="cine-subtitle">Curated Collection — <?= htmlspecialchars($t['description']) ?></p>
                </div>

                <!-- Glassmorphism Menus -->
                <div class="row g-5 justify-content-center mt-4">
                  
                  <?php if(!empty($t['combos'])): ?>
                  <div class="col-lg-6 col-md-12 cine-reveal" style="transition-delay: 0.3s;">
                    <div class="cine-glass-card cine-card-left">
                      <h4 style="text-align: center; color: #C9A66B; font-family: 'Cormorant Garamond', serif; margin-bottom: 40px; font-size: 24px; font-weight: 500; letter-spacing: 6px; border-bottom: 1px solid rgba(201, 166, 107, 0.2); padding-bottom: 20px; text-transform: uppercase;">SET MENU THƯỢNG HẠNG</h4>
                      <div class="d-flex flex-column gap-4">
                        <?php foreach($t['combos'] as $row): ?>
                          <div class="menu-list-item" style="cursor: pointer; transition: transform 0.3s ease;" onmouseover="this.style.transform='translateX(10px)'" onmouseout="this.style.transform='translateX(0)'" onclick="window.location.href='combo_detail.php?id=<?= $row['id'] ?>'">
                            <div class="d-flex justify-content-between align-items-baseline mb-2">
                              <h5 class="menu-hover-trigger" data-img="public/assets/img/combos/<?= htmlspecialchars($row['image'] ?: 'default-combo.jpg') ?>" style="color: #ffffff; font-family: 'Cormorant Garamond', serif; font-size: 1.3rem; font-weight: 300; margin: 0;">
                                <?= htmlspecialchars($row['name']) ?>
                              </h5>
                              <div style="color: #C9A66B; font-weight: 400; font-family: 'Cormorant Garamond', serif; font-size: 1.2rem; margin-left: 20px; white-space: nowrap;">
                                <?= number_format($row['price'], 0, ',', '.') ?>đ
                              </div>
                            </div>
                            <p style="color: #ffffff; font-size: 13px; margin: 0 0 8px 0; line-height: 1.6; max-width: 90%; text-shadow: 0 2px 4px rgba(0,0,0,0.8);"><?= htmlspecialchars($row['description']) ?></p>
                            <div style="font-size: 11px; color: #e6e6e6; font-style: italic; text-shadow: 0 2px 4px rgba(0,0,0,0.8);"><i class="bi bi-star-fill me-1" style="color:#C9A66B; font-size:9px;"></i><?= htmlspecialchars(str_replace(',', ' • ', $row['list_foods'])) ?></div>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  </div>
                  <?php endif; ?>

                  <?php if(!empty($t['foods'])): ?>
                  <div class="col-lg-6 col-md-12 cine-reveal" style="transition-delay: 0.5s;">
                    <div class="cine-glass-card cine-card-right">
                      <h4 style="text-align: center; color: #C9A66B; font-family: 'Cormorant Garamond', serif; margin-bottom: 40px; font-size: 24px; font-weight: 500; letter-spacing: 6px; border-bottom: 1px solid rgba(201, 166, 107, 0.2); padding-bottom: 20px; text-transform: uppercase;"><?= __('our_menu') ?></h4>
                      <div class="d-flex flex-column gap-4">
                        <?php foreach($t['foods'] as $f): ?>
                          <div class="menu-list-item" style="transition: transform 0.3s ease;" onmouseover="this.style.transform='translateX(10px)'" onmouseout="this.style.transform='translateX(0)'">
                            <div class="d-flex justify-content-between align-items-baseline mb-2">
                              <h5 class="menu-hover-trigger" data-img="public/assets/img/menu/<?= htmlspecialchars($f['image'] ?: 'default-food.jpg') ?>" style="color: #ffffff; font-family: 'Cormorant Garamond', serif; font-size: 1.3rem; font-weight: 300; margin: 0; cursor: default;">
                                <?= htmlspecialchars($f['name']) ?>
                              </h5>
                              <div style="color: #C9A66B; font-weight: 400; font-family: 'Cormorant Garamond', serif; font-size: 1.2rem; margin-left: 20px; white-space: nowrap;">
                                <?= number_format($f['price'], 0, ',', '.') ?>đ
                              </div>
                            </div>
                            <p style="color: #ffffff; font-size: 13px; margin: 0; line-height: 1.6; max-width: 90%; text-shadow: 0 2px 4px rgba(0,0,0,0.8);"><?= htmlspecialchars($f['description']) ?></p>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  </div>
                  <?php endif; ?>

                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
        </div>

        <script>
        document.addEventListener("DOMContentLoaded", function() {
            const observerOptions = {
                root: null,
                rootMargin: "0px",
                threshold: 0.15
            };
            const observer = new IntersectionObserver((entries, obs) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add("visible");
                        obs.unobserve(entry.target);
                    }
                });
            }, observerOptions);
            
            document.querySelectorAll(".cine-reveal").forEach(el => {
                observer.observe(el);
            });
        });
        </script>
      </div> <!-- Closes container from line 529 -->
    </section> <!-- Closes section combos from line 525 -->
        
<style>
/* Chef's Table Styles */
.netflix-chef-section {
    background: #181818;
    color: #F6F2E9;
    padding: 120px 0 100px 0;
    overflow: hidden;
}
.netflix-hero-chef {
    position: relative;
    width: 100%;
    margin-bottom: 80px;
    display: flex;
    justify-content: center;
}
.netflix-hero-image-wrapper {
    position: relative;
    width: 65%;
    max-width: 1000px;
    aspect-ratio: 16/9;
    overflow: hidden;
    border-radius: 4px;
    box-shadow: 0 40px 80px rgba(0,0,0,0.6);
}
.netflix-hero-image-wrapper img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: top center;
    transition: transform 5s ease-out;
}
.netflix-hero-image-wrapper:hover img {
    transform: scale(1.05);
}
.netflix-hero-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(24,24,24,1) 0%, rgba(24,24,24,0.4) 40%, transparent 100%);
    pointer-events: none;
}
.netflix-hero-glow {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 100%;
    height: 100%;
    background: radial-gradient(circle, rgba(201,166,107,0.1) 0%, transparent 60%);
    pointer-events: none;
    z-index: 0;
}
.netflix-hero-content {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    padding: 50px 40px;
    text-align: center;
    z-index: 2;
}
.netflix-hero-name {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(3rem, 6vw, 5rem);
    color: #F6F2E9;
    margin: 0 0 10px 0;
    font-weight: 300;
    letter-spacing: 4px;
    text-transform: uppercase;
    text-shadow: 0 10px 30px rgba(0,0,0,0.9);
}
.netflix-hero-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 14px;
    color: #C9A66B;
    letter-spacing: 3px;
    text-transform: uppercase;
    margin-bottom: 5px;
    font-weight: 500;
}
.netflix-hero-exp {
    font-family: 'Cormorant Garamond', serif;
    font-size: 11px;
    color: #aaa;
    letter-spacing: 2px;
    margin-bottom: 25px;
    text-transform: uppercase;
}
.netflix-hero-quote {
    font-family: 'Cormorant Garamond', serif;
    font-size: 26px;
    font-style: italic;
    color: #E2E0D9;
    margin-bottom: 35px;
    font-weight: 300;
}
.netflix-btn {
    display: inline-block;
    padding: 12px 35px;
    border: 1px solid rgba(201,166,107,0.5);
    background: transparent;
    color: #C9A66B;
    font-family: 'Cormorant Garamond', serif;
    font-size: 12px;
    letter-spacing: 3px;
    text-transform: uppercase;
    text-decoration: none;
    transition: all 0.3s ease;
}
.netflix-btn:hover {
    background: #C9A66B;
    color: #181818;
    border-color: #C9A66B;
}

/* Sub Chefs */
.netflix-sub-chefs {
    display: flex;
    justify-content: center;
    gap: 30px;
    flex-wrap: wrap;
    max-width: 1200px;
    margin: 0 auto;
}
.netflix-sub-card {
    position: relative;
    width: calc(33.333% - 20px);
    min-width: 280px;
    aspect-ratio: 3/4;
    overflow: hidden;
    border-radius: 4px;
    cursor: pointer;
    background: #111;
}
.netflix-sub-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 1.5s cubic-bezier(0.25, 1, 0.5, 1);
    opacity: 0.8;
}
.netflix-sub-card:hover .netflix-sub-img {
    transform: scale(1.08);
    opacity: 1;
}
.netflix-sub-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(24,24,24,0.95) 0%, rgba(24,24,24,0.4) 50%, transparent 100%);
    transition: all 0.4s ease;
}
.netflix-sub-card:hover .netflix-sub-overlay {
    background: rgba(24,24,24,0.8);
}
.netflix-sub-info {
    position: absolute;
    bottom: 30px;
    left: 30px;
    right: 30px;
    transition: all 0.4s ease;
}
.netflix-sub-card:hover .netflix-sub-info {
    bottom: 50px;
}
.netflix-sub-name {
    font-family: 'Cormorant Garamond', serif;
    font-size: 32px;
    color: #F6F2E9;
    margin-bottom: 5px;
    font-weight: 300;
    letter-spacing: 2px;
}
.netflix-sub-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 11px;
    color: #C9A66B;
    letter-spacing: 2px;
    text-transform: uppercase;
}
.netflix-sub-quote {
    font-family: 'Cormorant Garamond', serif;
    font-size: 16px;
    font-style: italic;
    color: #ccc;
    opacity: 0;
    margin-top: 15px;
    transform: translateY(10px);
    transition: all 0.4s ease;
    transition-delay: 0.1s;
}
.netflix-sub-card:hover .netflix-sub-quote {
    opacity: 1;
    transform: translateY(0);
}
.netflix-sub-btn {
    display: inline-block;
    margin-top: 25px;
    color: #F6F2E9;
    font-size: 11px;
    font-family: 'Cormorant Garamond', serif;
    letter-spacing: 2px;
    text-transform: uppercase;
    text-decoration: none;
    border-bottom: 1px solid rgba(255,255,255,0.3);
    padding-bottom: 3px;
    opacity: 0;
    transform: translateY(10px);
    transition: all 0.4s ease;
    transition-delay: 0.2s;
}
.netflix-sub-card:hover .netflix-sub-btn {
    opacity: 1;
    transform: translateY(0);
}
.netflix-sub-btn:hover {
    color: #C9A66B;
    border-bottom-color: #C9A66B;
}

@media (max-width: 991px) {
    .netflix-hero-image-wrapper { width: 90%; aspect-ratio: 4/5; }
    .netflix-sub-card { width: calc(50% - 15px); }
}
@media (max-width: 767px) {
    .netflix-sub-card { width: 100%; }
}
</style>


<style>
@import url('https://fonts.googleapis.com/css2?family=Great+Vibes&display=swap');

.editorial-chef-section {
    background: #FBF7F0; /* Ivory/Beige background as requested */
    padding: 60px 0 120px 0;
    position: relative;
}
.editorial-grid {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-top: 60px;
}
.editorial-card {
    position: relative;
    width: calc(33.333% - 10px);
    min-width: 300px;
    aspect-ratio: 4/5;
    overflow: hidden;
    cursor: pointer;
    background: #000;
}
.editorial-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center top;
    transition: all 0.8s cubic-bezier(0.25, 1, 0.5, 1);
}
.editorial-card:hover .editorial-img {
    transform: scale(1.05);
}
.editorial-overlay {
    position: absolute;
    inset: 0;
    background: transparent;
    pointer-events: none;
    transition: all 0.5s ease;
}
.editorial-card:hover .editorial-overlay {
    background: transparent;
}
.editorial-signature {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -40%) rotate(-10deg) scale(0.9);
    font-family: 'Great Vibes', cursive;
    font-size: clamp(3rem, 5vw, 4.5rem);
    color: #C9A66B;
    opacity: 0;
    transition: all 0.6s cubic-bezier(0.25, 1, 0.5, 1);
    white-space: nowrap;
    text-shadow: 0 5px 15px rgba(0,0,0,0.8);
    pointer-events: none;
    z-index: 2;
}
.editorial-card:hover .editorial-signature {
    opacity: 1;
    transform: translate(-50%, -50%) rotate(-10deg) scale(1);
}
.editorial-info {
    position: absolute;
    bottom: 30px;
    left: 0;
    width: 100%;
    text-align: center;
    z-index: 3;
    padding: 0 20px;
    transition: all 0.5s ease;
}
.editorial-card:hover .editorial-info {
    bottom: 40px;
}
.editorial-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 10px;
    color: #C9A66B;
    letter-spacing: 4px;
    text-transform: uppercase;
    margin-bottom: 8px;
    opacity: 0.8;
}
.editorial-card:hover .editorial-title {
    opacity: 1;
}
.editorial-name {
    font-family: 'Cormorant Garamond', serif;
    font-size: 32px;
    color: #F6F2E9;
    margin: 0;
    font-weight: 400;
    letter-spacing: 2px;
    text-transform: uppercase;
}
.editorial-btn {
    display: inline-block;
    margin-top: 60px;
    padding: 14px 40px;
    border: 1px solid #E65C00;
    background: #E65C00;
    color: #ffffff;
    font-family: 'Cormorant Garamond', serif;
    font-size: 12px;
    letter-spacing: 3px;
    text-transform: uppercase;
    text-decoration: none;
    transition: all 0.3s ease;
}
.editorial-btn:hover {
    background: #FF7A00;
    color: #ffffff;
    border-color: #FF7A00;
    box-shadow: 0 0 20px rgba(255, 122, 0, 0.6);
}

@media (max-width: 991px) {
    .editorial-card { width: calc(50% - 15px); }
}
@media (max-width: 767px) {
    .editorial-card { width: 100%; max-width: 400px; margin: 0 auto; }
}
</style>

<section id="chefs" class="editorial-chef-section">
    <div class="px-2 position-relative z-1" style="width: 100%; max-width: 100%; margin: 0 auto;">
      <div class="section-title text-start px-md-2" style="margin-bottom: 50px !important;">
        <div class="d-flex align-items-center" style="margin-bottom: 15px;">
            <p style="color: #A88746; font-family: 'Cormorant Garamond', serif; font-size: 12px; font-weight: 500; letter-spacing: 3px; text-transform: uppercase; margin: 0; padding-right: 15px;">ĐỘI NGŨ ĐẦU BẾP</p>
            <div style="flex: 0 0 60px; height: 1px; background-color: #A88746;"></div>
        </div>
        <h2 style="color: #A88746; font-family: 'Cormorant Garamond', serif; font-size: 42px; font-weight: 400; letter-spacing: 1px;">Những Nghệ Nhân Ẩm Thực Hàng Đầu</h2>
      </div>
      
      <div class="editorial-grid">
        <?php if (!empty($home_chefs)): ?>
          <?php 
          $delay = 0.1;
          foreach ($home_chefs as $chef): 
          ?>
            <div class="editorial-card cine-reveal" style="transition-delay: <?= $delay ?>s;" onclick="window.location.href='chefs.php'">
              <img class="editorial-img" src="public/assets/img/chefs/<?= htmlspecialchars($chef['image']) ?>" alt="<?= htmlspecialchars($chef['name']) ?>" onerror="this.src='public/assets/img/chefs/default-chef.jpg'">
              <div class="editorial-overlay"></div>
              <div class="editorial-signature"><?= htmlspecialchars($chef['name']) ?></div>
              <div class="editorial-info">
                  <div class="editorial-title"><?= htmlspecialchars($chef['position']) ?></div>
                  <h4 class="editorial-name"><?= htmlspecialchars($chef['name']) ?></h4>
              </div>
            </div>
          <?php 
          $delay += 0.2;
          endforeach; 
          ?>
        <?php else: ?>
          <p class="text-center" style="color:#666666; font-style: italic;">Chưa có thông tin đầu bếp.</p>
        <?php endif; ?>
      </div>
      
      <div class="text-center">
        <a href="chefs.php" class="editorial-btn">TẤT CẢ ĐẦU BẾP</a>
      </div>
    </div>
</section>
  <!-- ATMOSPHERE & GALLERY SECTION -->
  <section id="atmosphere" style="background: #F9F9F9; padding: 140px 0; position: relative;">
    <div class="container" style="position: relative; z-index: 1;">
      <div class="section-title text-center mb-5" style="margin-bottom: 30px !important;">
        <p style="color: #A88746; font-family: 'Open Sans', sans-serif; font-size: 11px; font-weight: 500; letter-spacing: 4px; text-transform: uppercase; margin-bottom: 15px;">Không Gian & Trải Nghiệm</p>
        <h2 style="color: #A88746; font-family: 'Cormorant Garamond', serif; font-size: 42px; font-weight: 400; letter-spacing: 1px;">Kiệt Tác Kiến Trúc Tĩnh Lặng</h2>
      </div>

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

    <div class="container text-center mt-5 pt-4">
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
    border: 2px solid #A88746;
    text-decoration: none;
  }

  .btn-book-custom {
    background: #A88746;
  }

  .btn-book-custom:hover,
  .btn-menu-custom:hover,
  .btn-view-all-custom:hover {
    background: #A88746;
    color: #fff;
  }

  .explore-menu-link {
    font-family: 'Open Sans', sans-serif;
    font-size: 0.9rem;
    letter-spacing: 3px;
    color: #A88746;
    text-decoration: none;
    text-transform: uppercase;
    border-bottom: 2px solid #d9ba85;
    padding-bottom: 8px;
    transition: 0.3s;
    font-weight: 600;
  }

  /* --- MICHELIN COMBO CARDS --- */
  .combo-card-custom {
    background: #FFFFFF;
    border: 1px solid rgba(168, 135, 70,0.2);
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
    border-color: #A88746;
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
    background: #A88746;
    backdrop-filter: blur(8px);
    border: 1px solid #A88746;
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
    font-family: 'Cormorant Garamond', serif;
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
    color: #A88746;
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
    color: #A88746;
    font-weight: 500;
    font-size: 15px;
    letter-spacing: 1px;
  }

  .btn-combo-order {
    background: #E65C00;
    color: #ffffff;
    text-decoration: none !important;
    border: 1px solid #E65C00;
    padding: 10px 24px;
    font-weight: 500;
    font-size: 11px;
    letter-spacing: 3px;
    transition: all 0.4s ease;
    text-transform: uppercase;
  }

  .btn-combo-order:hover {
    background: #FF7A00;
    color: #ffffff;
    border-color: #FF7A00;
    box-shadow: 0 0 20px rgba(255, 122, 0, 0.6);
  }

  /* --- MICHELIN CHEFS SECTION --- */
  .chefs-subtitle {
    font-size: 11px;
    font-weight: 500;
    letter-spacing: 4px;
    text-transform: uppercase;
    color: #A88746;
    font-family: 'Open Sans', sans-serif;
  }

  .chefs-title {
    margin: 15px 0 0 0;
    font-size: 42px;
    font-weight: 400;
    font-family: 'Cormorant Garamond', serif;
    color: #222222;
    letter-spacing: 1px;
  }

  .luxury-chef-card {
    border-radius: 0;
    overflow: hidden;
    position: relative;
    background: #FFFFFF;
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
    background: #333336;
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
    background: linear-gradient(to top, rgba(168, 135, 70, 0.95) 0%, rgba(168, 135, 70, 0.7) 40%, transparent 100%);
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    text-align: center;
    transition: all 0.6s ease;
  }

  .chef-quote {
    color: rgba(246, 241, 231, 0.8);
    font-family: 'Cormorant Garamond', serif;
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
    font-family: 'Cormorant Garamond', serif;
    margin-bottom: 5px;
    letter-spacing: 1px;
  }

  .chef-title {
    color: #A88746;
    font-size: 11px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 3px;
    font-family: 'Open Sans', sans-serif;
  }

  /* --- ATMOSPHERE & GALLERY --- */
  .atmosphere-grid {
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    grid-auto-rows: 250px;
    gap: 0px;
    width: 100vw;
    position: relative;
    left: 50%;
    right: 50%;
    margin-left: -50vw;
    margin-right: -50vw;
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
    border: 1px solid rgba(168, 135, 70, 0);
    transition: all 0.6s ease;
    pointer-events: none;
  }

  .atmo-item:hover img {
    filter: grayscale(0) brightness(1.05);
    transform: scale(1.05);
  }

  .atmo-item:hover::after {
    border-color: rgba(168, 135, 70, 0.4);
    inset: 15px; /* Creates an inner border effect */
  }

  /* Asymmetric Placement */
  .item-1 {
    grid-column: span 6;
    grid-row: span 2;
  }

  .item-2 {
    grid-column: span 2;
    grid-row: span 1;
  }

  .item-3 {
    grid-column: span 2;
    grid-row: span 1;
  }

  .item-4 {
    grid-column: span 2;
    grid-row: span 1;
  }

  .item-5 {
    grid-column: span 2;
    grid-row: span 1;
  }

  .item-6 {
    grid-column: span 2;
    grid-row: span 1;
  }

  .item-7 {
    grid-column: span 2;
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
