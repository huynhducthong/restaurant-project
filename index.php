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
      $combos = $t_combos->fetchAll(PDO::FETCH_ASSOC);
      foreach ($combos as &$combo) {
          $stmt_items = $db->prepare("SELECT f.* FROM foods f JOIN combo_items ci ON f.id = ci.food_id WHERE ci.combo_id = ? AND f.is_active = 1");
          $stmt_items->execute([$combo['id']]);
          $combo['items'] = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
      }
      $t['combos'] = $combos;
      
      
      $t_foods = $db->prepare("SELECT f.*, c.name as cat_name FROM foods f LEFT JOIN categories c ON f.category_id = c.id WHERE f.theme_id = ? AND f.is_active = 1");
      $t_foods->execute([$t['id']]);
      $t['foods'] = $t_foods->fetchAll(PDO::FETCH_ASSOC);
  }
  unset($t);

  // 5.4 Lấy danh sách đầu bếp nổi bật cho trang chủ
    $stmt_home_chefs = $db->prepare("SELECT * FROM chefs WHERE is_active = 1 AND is_featured = 1 ORDER BY sort_order ASC, id ASC");
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

<section id="hero-video" class="d-flex align-items-center justify-content-center" style="position: relative; width: 100vw; height: 100vh; overflow: hidden; background: #000; margin: 0; padding: 0;">
  <!-- Video Background -->
  <?php if ($video_type == 'youtube' && !empty($video_url)): ?>
    <iframe style="position: absolute; top: 50%; left: 50%; width: 100vw; height: 100vh; transform: translate(-50%, -50%); pointer-events: none;" 
      src="https://www.youtube.com/embed/<?php echo htmlspecialchars($video_url); ?>?autoplay=1&mute=1&controls=0&loop=1&playlist=<?php echo htmlspecialchars($video_url); ?>&rel=0&showinfo=0&modestbranding=1" 
      frameborder="0" allow="autoplay; fullscreen">
    </iframe>
  <?php elseif ($video_type == 'vimeo' && !empty($video_url)): ?>
    <iframe style="position: absolute; top: 50%; left: 50%; width: 100vw; height: 100vh; transform: translate(-50%, -50%); pointer-events: none;"
      src="https://player.vimeo.com/video/<?php echo htmlspecialchars($video_url); ?>?background=1&autoplay=1&loop=1&byline=0&title=0&muted=1"
      frameborder="0" allow="autoplay; fullscreen">
    </iframe>
  <?php elseif ($video_type == 'muse' && !empty($video_url)): ?>
    <iframe style="position: absolute; top: 50%; left: 50%; width: 100vw; height: 100vh; transform: translate(-50%, -50%); pointer-events: none;"
      src="https://muse.ai/embed/<?php echo htmlspecialchars($video_url); ?>?autoplay=1&loop=1&muted=1&search=0&links=0"
      frameborder="0" allow="autoplay; fullscreen">
    </iframe>
  <?php elseif ($video_type == 'local' && !empty($file_path)): ?>
    <?php
    $ext_v = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    $mime_map = ['mp4' => 'video/mp4', 'webm' => 'video/webm', 'mov' => 'video/quicktime'];
    $mime_v = $mime_map[$ext_v] ?? 'video/mp4';
    ?>
    <video autoplay loop muted playsinline style="position: absolute; top: 50%; left: 50%; min-width: 100%; min-height: 100%; width: auto; height: auto; transform: translate(-50%, -50%); object-fit: cover; z-index: 0; pointer-events: none;">
      <source src="<?= htmlspecialchars($file_path) ?>" type="<?= $mime_v ?>">
    </video>
  <?php else: ?>
    <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-image: url('public/assets/img/about.jpg'); background-size: cover; background-position: center; z-index: 0;"></div>
  <?php endif; ?>

  <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(to bottom, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0.3) 50%, rgba(0,0,0,0.7) 100%); z-index: 1;"></div>

    <div class="container position-relative text-center" style="z-index: 2;">
    <?php
      $v_title = $video_data['title'] ?? '';
      $v_desc  = $video_data['description'] ?? '';
      
      if (empty($v_title)) {
          $v_title = "Câu Chuyện Về " . htmlspecialchars($settings['restaurant_name'] ?? 'Restaurantly');
      }
      if (empty($v_desc)) {
          $v_desc = "Nằm giữa lòng " . htmlspecialchars($settings['address'] ?? 'Biên Hòa') . ", chúng tôi mang đến một không gian ẩm thực tinh tế, ẩm thực văn hóa cao cấp.";
      }
    ?>
    <h1 style="color: #fff; font-family: 'Cormorant Garamond', serif; font-size: clamp(3rem, 7vw, 6rem); font-weight: 700; text-shadow: 2px 2px 10px rgba(0,0,0,0.8); margin-bottom: 20px;">
      <?= $v_title ?>
    </h1>
    <p style="color: #eee; font-family: 'Source Sans 3', sans-serif; font-size: clamp(1.2rem, 3vw, 1.8rem); font-weight: 300; text-shadow: 1px 1px 5px rgba(0,0,0,0.8); letter-spacing: 1px;">
      <?= nl2br(htmlspecialchars($v_desc)) ?>
    </p>
  </div>
</section>

<main id="main">

<!-- PROMOTIONS (BANNER SLIDER) -->
<section id="promotions" style="padding: 0; margin: 0; overflow: hidden; cursor: grab; background: #111;">
    <div id="bannerCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-touch="true" data-bs-interval="3000" style="width: 100vw;">
    <div class="carousel-inner" id="bannerCarouselInner">
      <?php
      $first = true;
      foreach ($banners_db as $row):
        $title_weight = ($row['font_style'] == 'bold') ? 'bold' : 'normal';
        $title_style  = ($row['font_style'] == 'italic') ? 'italic' : 'normal';
        $desc_weight  = (($row['desc_font_style'] ?? 'normal') == 'bold') ? 'bold' : 'normal';
        $desc_style   = (($row['desc_font_style'] ?? 'normal') == 'italic') ? 'italic' : 'normal';
      ?>
                                        <div class="carousel-item <?= $first ? 'active' : '' ?>" style="background: #050505; height: clamp(500px, 75vh, 800px); overflow: hidden;">
          <div class="row g-0 h-100">
                        <!-- Phần chữ (Bên trái) -->
            <div class="col-lg-5 d-flex flex-column justify-content-center px-4 px-lg-5 py-5" style="z-index: 2; padding-left: clamp(2rem, 8vw, 6rem) !important; min-height: clamp(400px, 60vh, 600px);">
              
              <!-- Subtitle nhỏ có đường gạch ngang -->
              <div class="d-flex align-items-center mb-4">
                <div style="width: 40px; height: 1px; background-color: rgba(255,255,255,0.5); margin-right: 15px;"></div>
                <span style="color: rgba(255,255,255,0.7); font-size: 11px; letter-spacing: 2px; text-transform: uppercase; font-family: 'Source Sans 3', sans-serif; font-weight: 600;">
                  The Best Experience
                </span>
              </div>

              <!-- Tiêu đề chính -->
              <h2 style="
                  color: <?= $row['text_color'] ?? '#ffffff' ?>; 
                  font-family: <?= $row['font_family'] ?? "'Oswald', 'Source Sans 3', sans-serif" ?>; 
                  font-size: clamp(3rem, 6vw, 5.5rem); 
                  font-weight: 800; 
                  font-style: <?= $title_style ?>;
                  text-transform: uppercase;
                  line-height: 1.1;
                  margin-bottom: 30px;
                  letter-spacing: 1px;">
                <?= nl2br(htmlspecialchars($row['title'])) ?>
              </h2>

              <!-- Mô tả -->
              <p style="
                  color: <?= $row['desc_color'] ?? '#cccccc' ?>; 
                  font-family: 'Cormorant Garamond', serif; 
                  font-size: clamp(1.1rem, 2vw, 1.4rem); 
                  font-weight: 400; 
                  font-style: italic;
                  line-height: 1.8;
                  max-width: 85%;
                  margin-bottom: 40px;">
                <?= htmlspecialchars($row['description']) ?>
              </p>

              <!-- Nút bấm viền (Outline Button) -->
              <?php if (!empty($row['button_text'])): ?>
                <div>
                  <a href="<?= htmlspecialchars($row['button_link'] ?? '#') ?>" class="animate__animated animate__fadeInUp" style="
                    display:inline-block;
                    padding:14px 40px;
                    border-radius:0px;
                    text-transform:uppercase;
                    text-decoration:none;
                    font-weight:600;
                    font-size:12px;
                    font-family:'Source Sans 3', sans-serif;
                    letter-spacing:2px;
                    background-color: transparent;
                    color: #fff;
                    border: 1px solid rgba(255,255,255,0.5);
                    transition: all 0.3s ease;
                " onmouseover="this.style.backgroundColor='#fff'; this.style.color='#000';" onmouseout="this.style.backgroundColor='transparent'; this.style.color='#fff';">
                    <?= htmlspecialchars($row['button_text']) ?>
                  </a>
                </div>
              <?php endif; ?>
            </div>

                                    <!-- Phần ảnh (Bên phải) -->
            <div class="col-lg-7 h-100 position-relative">
              <img src="public/assets/img/hero/<?= $row['image_url'] ?>" alt="<?= htmlspecialchars($row['title']) ?>" style="width: 100%; height: 100%; object-fit: cover; pointer-events: none; filter: brightness(0.9);">
              <!-- Gradient mờ từ trái sang để hòa trộn 2 khối -->
              <div class="d-none d-lg-block" style="position: absolute; top: 0; left: 0; bottom: 0; width: 250px; background: linear-gradient(to right, #050505 0%, rgba(5,5,5,0.7) 30%, transparent 100%); pointer-events: none;"></div>
              <!-- Gradient mờ từ dưới lên cho Mobile -->
              <div class="d-block d-lg-none" style="position: absolute; top: -1px; left: 0; right: 0; height: 150px; background: linear-gradient(to bottom, #050505 0%, transparent 100%); pointer-events: none;"></div>
            </div>
          </div>
        </div>
      <?php $first = false; endforeach; ?>
    </div>
  </div>
  
  <script>
    document.addEventListener('DOMContentLoaded', function() {
        const carouselSection = document.getElementById('promotions');
        const myCarousel = document.getElementById('bannerCarousel');
        let isDown = false;
        let startX;
        
        carouselSection.addEventListener('mousedown', (e) => {
            isDown = true;
            carouselSection.style.cursor = 'grabbing';
            startX = e.pageX;
            e.preventDefault(); 
        });

        carouselSection.addEventListener('mouseleave', () => {
            isDown = false;
            carouselSection.style.cursor = 'grab';
        });

        carouselSection.addEventListener('mouseup', (e) => {
            if(!isDown) return;
            isDown = false;
            carouselSection.style.cursor = 'grab';
            
            const endX = e.pageX;
            const diff = startX - endX;
            
            if(Math.abs(diff) > 50) { 
                const bsCarousel = bootstrap.Carousel.getInstance(myCarousel) || new bootstrap.Carousel(myCarousel);
                if(diff > 0) {
                    bsCarousel.next();
                } else {
                    bsCarousel.prev();
                }
            }
        });
    });
  </script>
</section>

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

<main id="main">


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
      <p style="color:#A88746; font-family:'Source Sans 3',sans-serif; font-size:13px; letter-spacing:4px; text-transform:uppercase; margin-bottom:12px;">Hương Vị Tinh Tế</p>
      <h2 style="color:#A88746; font-family:'Cormorant Garamond', serif; font-size:clamp(2rem,5vw,3.2rem); font-weight:700; margin-bottom:16px;">Một Trải Nghiệm Độc Đáo</h2>
      <p style="color:#666666; font-family:'Source Sans 3',sans-serif; font-size:15px; max-width:900px; margin:0 auto; line-height:1.8;">
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

  

    

  <section id="combos" class="minimal-menu-wrapper">
      <div class="container">
          <div class="section-title text-center mb-5 gsap-fade-up">
              <p style="color: #A88746; font-family: 'Source Sans 3', sans-serif; font-size: 11px; font-weight: 500; letter-spacing: 4px; text-transform: uppercase; margin-bottom: 15px;">Tuyển Chọn Thượng Hạng</p>
              <h2 style="color: #2A201A; font-family: 'Cormorant Garamond', serif; font-size: 42px; font-weight: 400; letter-spacing: 1px;">Bộ Sưu Tập Hương Vị</h2>
          </div>
          
          <?php if (!empty($active_themes)): ?>
              <?php foreach ($active_themes as $t): ?>
                  <?php if(empty($t['combos']) && empty($t['foods'])) continue; ?>
                  
                  <div class="minimal-menu-theme">
                      <div class="text-center mb-4 gsap-fade-up">
                          <h3 style="color: #C9A66B; font-family: 'Cormorant Garamond', serif; font-size: 32px; font-weight: 400; text-transform: uppercase; letter-spacing: 3px; margin-bottom: 10px;"><?= htmlspecialchars($t['name']) ?></h3>
                          <p style="color: #666; font-family: 'Source Sans 3', sans-serif; font-size: 14px; max-width: 600px; margin: 0 auto; font-style: italic;"><?= htmlspecialchars($t['description']) ?></p>
                      </div>
                      
                      <div class="row g-0 shadow-lg gsap-fade-up" style="border-radius: 8px; overflow: hidden; background: #FFFFFF;">
                          <!-- LEFT COLUMN: COMBOS (BLACK) -->
                          <div class="col-lg-6 p-5 minimal-menu-box" style="background: #FFFFFF;">
                              <h4 style="color: #2A201A; font-family: 'Cormorant Garamond', serif; font-size: 24px; margin-bottom: 30px; text-transform: uppercase; letter-spacing: 2px; text-align: center; border-bottom: 1px solid rgba(0,0,0,0.1); padding-bottom: 15px;">Set Menu Thượng Hạng</h4>
                              <div class="d-flex flex-column gap-4">
                                  <?php if(!empty($t['combos'])): ?>
                                      <?php foreach($t['combos'] as $row): ?>
                                          <div class="minimal-menu-item gsap-fade-up">
                                              <div style="cursor: pointer;" onclick="const el = document.getElementById('combo-items-<?= $row['id'] ?>'); el.style.display = (el.style.display === 'none') ? 'block' : 'none';">
                                                  <div class="d-flex align-items-center">
                                                      <img src="public/assets/img/combos/<?= htmlspecialchars($row['image'] ?: 'default-combo.jpg') ?>" class="minimal-menu-img me-3" style="border: 1px solid rgba(0,0,0,0.1);" alt="">
                                                      <div style="flex: 1;">
                                                          <div class="d-flex justify-content-between align-items-baseline">
                                                              <h5 style="color: #2A201A; font-family: 'Cormorant Garamond', serif; font-size: 18px; margin: 0; font-weight: 400; text-transform: uppercase;"><?= htmlspecialchars($row['name']) ?> <i class="bi bi-chevron-down ms-1" style="font-size: 12px; color: #C9A66B;"></i></h5>
                                                              <div style="flex-grow: 1; border-bottom: 1px dashed rgba(255,255,255,0.2); margin: 0 10px; position: relative; top: -4px;"></div>
                                                              <div style="color: #C9A66B; font-family: 'Source Sans 3', sans-serif; font-size: 16px; font-weight: 600;"><?= number_format($row['price'], 0, ',', '.') ?>đ</div>
                                                          </div>
                                                          <p style="color: #666; font-size: 12px; margin: 5px 0 0 0; line-height: 1.5;"><?= htmlspecialchars($row['description']) ?></p>
                                                          <div style="font-size: 10px; color: #666; font-style: italic; margin-top: 4px;"><i class="bi bi-star-fill me-1" style="color:#C9A66B; font-size:8px;"></i><?= htmlspecialchars(str_replace(',', ' • ', $row['list_foods'])) ?></div>
                                                      </div>
                                                  </div>
                                              </div>
                                              <!-- Combo items dropdown -->
                                              <div id="combo-items-<?= $row['id'] ?>" class="minimal-combo-items">
                                                  <?php if (!empty($row['items'])): ?>
                                                      <div class="row g-2">
                                                      <?php foreach ($row['items'] as $item): ?>
                                                          <div class="col-12 d-flex align-items-center mb-2 px-3">
                                                              <img src="public/assets/img/menu/<?= htmlspecialchars($item['image'] ?: 'default-food.jpg') ?>" style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%; border: 1px solid rgba(255,255,255,0.1); margin-right: 15px;" alt="">
                                                              <div>
                                                                  <h6 style="color: #2A201A; font-family: 'Cormorant Garamond', serif; font-size: 14px; margin: 0; font-weight: 300;"><?= htmlspecialchars($item['name']) ?></h6>
                                                                  <div style="font-size: 10px; color: #777; max-width: 100%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($item['description']) ?></div>
                                                              </div>
                                                          </div>
                                                      <?php endforeach; ?>
                                                      </div>
                                                      <div class="mt-3 text-center">
                                                          <a href="combo_detail.php?id=<?= $row['id'] ?>" class="btn btn-sm" style="border: 1px solid #C9A66B; color: #C9A66B; border-radius: 0; text-transform: uppercase; font-size: 10px; letter-spacing: 1px; padding: 6px 15px; text-decoration: none;">Chi Tiết & Đặt Bàn</a>
                                                      </div>
                                                  <?php endif; ?>
                                              </div>
                                          </div>
                                      <?php endforeach; ?>
                                  <?php endif; ?>
                              </div>
                          </div>
                          
                          <!-- RIGHT COLUMN: FOODS (BEIGE) -->
                          <div class="col-lg-6 p-5 minimal-menu-box" style="background: #F4F1EA;">
                              <h4 style="color: #2A201A; font-family: 'Cormorant Garamond', serif; font-size: 24px; margin-bottom: 30px; text-transform: uppercase; letter-spacing: 2px; text-align: center; border-bottom: 1px solid rgba(0,0,0,0.1); padding-bottom: 15px;">Món tự chọn</h4>
                              <div class="d-flex flex-column gap-4">
                                  <?php if(!empty($t['foods'])): ?>
                                      <?php foreach($t['foods'] as $f): ?>
                                          <div class="minimal-menu-item gsap-fade-up">
                                              <div class="d-flex align-items-center">
                                                  <div style="flex: 1;">
                                                      <div class="d-flex justify-content-between align-items-baseline">
                                                          <h5 style="color: #2A201A; font-family: 'Cormorant Garamond', serif; font-size: 18px; margin: 0; font-weight: 600; text-transform: uppercase;"><?= htmlspecialchars($f['name']) ?></h5>
                                                          <div style="flex-grow: 1; border-bottom: 1px dashed rgba(0,0,0,0.2); margin: 0 10px; position: relative; top: -4px;"></div>
                                                          <div style="color: #E65C00; font-family: 'Source Sans 3', sans-serif; font-size: 16px; font-weight: 700;"><?= number_format($f['price'], 0, ',', '.') ?>đ</div>
                                                      </div>
                                                      <p style="color: #666; font-size: 12px; margin: 5px 0 0 0; line-height: 1.5;"><?= htmlspecialchars($f['description']) ?></p>
                                                  </div>
                                                  <img src="public/assets/img/menu/<?= htmlspecialchars($f['image'] ?: 'default-food.jpg') ?>" class="minimal-menu-img ms-3" style="border: 1px solid rgba(0,0,0,0.1);" alt="">
                                              </div>
                                          </div>
                                      <?php endforeach; ?>
                                  <?php endif; ?>
                              </div>
                          </div>
                      </div>
                  </div>
              <?php endforeach; ?>
          <?php endif; ?>
      </div>
  </section> <!-- Closes section combos from line 525 -->
        






<section id="chefs" class="awesome-team-section">
    <div class="awesome-team-title">ĐỘI NGŨ ĐẦU BẾP</div>
    <div class="awesome-team-subtitle">
        Khám phá những nghệ nhân ẩm thực hàng đầu của chúng tôi, những người luôn dành trọn đam mê để mang đến trải nghiệm hương vị tuyệt hảo nhất.
    </div>
    <a href="chefs.php" class="awesome-team-link">Xem tất cả</a>
    
    <div class="awesome-team-grid">
      <?php if (!empty($home_chefs)): ?>
        <?php foreach ($home_chefs as $chef): ?>
          <div class="awesome-card gsap-fade-up" onclick="window.location.href='chefs.php'" style="cursor: pointer;">
            <div class="awesome-img-wrapper">
                <img src="public/assets/img/chefs/<?= htmlspecialchars($chef['image']) ?>" alt="<?= htmlspecialchars($chef['name']) ?>" onerror="this.src='public/assets/img/chefs/default-chef.jpg'">
            </div>
            <div class="awesome-name"><?= htmlspecialchars($chef['name']) ?></div>
            <div class="awesome-role"><?= htmlspecialchars($chef['position']) ?></div>
            <div class="awesome-desc">
                <?php
                   $desc = $chef['signature_dishes'] ?? 'Đam mê ẩm thực và sáng tạo không ngừng nghỉ để mang đến những món ăn tuyệt hảo nhất.';
                   echo htmlspecialchars($desc);
                ?>
            </div>
            <div class="awesome-socials">
                <?php if (!empty($chef['facebook'])): ?>
                    <a href="<?= htmlspecialchars($chef['facebook']) ?>" target="_blank" style="color: inherit; text-decoration: none;"><i class="bi bi-facebook"></i></a>
                <?php else: ?>
                    <i class="bi bi-facebook"></i>
                <?php endif; ?>
                
                <?php if (!empty($chef['instagram'])): ?>
                    <a href="<?= htmlspecialchars($chef['instagram']) ?>" target="_blank" style="color: inherit; text-decoration: none;"><i class="bi bi-instagram"></i></a>
                <?php else: ?>
                    <i class="bi bi-instagram"></i>
                <?php endif; ?>
                
                <?php if (!empty($chef['email'])): ?>
                    <a href="mailto:<?= htmlspecialchars($chef['email']) ?>" style="color: inherit; text-decoration: none;"><i class="bi bi-envelope"></i></a>
                <?php else: ?>
                    <i class="bi bi-envelope"></i>
                <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p class="text-center w-100" style="color:#666666; font-style: italic;">Chưa có thông tin đầu bếp.</p>
      <?php endif; ?>
    </div>
</section>
  <!-- ATMOSPHERE & GALLERY SECTION -->
  <section id="atmosphere" style="background: #F9F9F9; padding: 140px 0; position: relative;">
    <div class="container" style="position: relative; z-index: 1;">
      <div class="section-title text-center mb-5" style="margin-bottom: 30px !important;">
        <p style="color: #A88746; font-family: 'Source Sans 3', sans-serif; font-size: 11px; font-weight: 500; letter-spacing: 4px; text-transform: uppercase; margin-bottom: 15px;">Không Gian & Trải Nghiệm</p>
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
            <div class="atmo-item item-<?= $item_class ?> gsap-zoom-in">
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

<?php if (($settings['promo_popup_enabled'] ?? '0') == '1' && !empty($settings['promo_popup_file'])): ?>
<!-- Welcome Promo Popup -->
<div class="modal fade" id="welcomePromoModal" tabindex="-1" aria-labelledby="welcomePromoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 550px;">
    <div class="modal-content" style="background: transparent; border: none; align-items: center;">
      <div style="position: relative; display: block; background: #fff; border-radius: 8px; box-shadow: 0 10px 40px rgba(0,0,0,0.5); overflow: hidden; width: 100%;">
        
        <!-- Nút tắt (Nằm bên trong góc trên phải của thẻ trắng, màu trắng dễ nhìn) -->
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="position: absolute; right: 15px; top: 15px; z-index: 1060; background-color: #ffffff; opacity: 1; padding: 10px; border-radius: 50%; box-shadow: 0 4px 12px rgba(0,0,0,0.4);"></button>

        <?php if (($settings['promo_popup_type'] ?? 'image') === 'pdf'): ?>
            <iframe src="public/<?= htmlspecialchars($settings['promo_popup_file']) ?>" style="width: 100%; height: 70vh; border:none; display: block;"></iframe>
        <?php else: ?>
            <img src="public/<?= htmlspecialchars($settings['promo_popup_file']) ?>" class="img-fluid" style="max-height: 75vh; width: 100%; object-fit: contain; display: block; margin: 0 auto; background: #fff;" alt="Welcome Promo">
        <?php endif; ?>
        
        <?php if (!empty($settings['promo_popup_content'])): ?>
        <div class="p-4 text-dark text-center" style="border-top: 1px solid #eee; background: #fdfdfd;">
            <p class="mb-0" style="white-space: pre-wrap; font-size: 1.05rem; line-height: 1.5;"><?= htmlspecialchars($settings['promo_popup_content']) ?></p>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log("Welcome Popup script initialized.");
    
    // Kiểm tra xem có phải là tải lại trang (F5) hoặc truy cập từ bên ngoài (nhập URL, từ Google...)
    let isReload = false;
    let isExternalEntry = false;
    
    if (window.performance) {
        if (performance.getEntriesByType) {
            let navEntries = performance.getEntriesByType("navigation");
            if (navEntries.length > 0 && navEntries[0].type === "reload") {
                isReload = true;
            }
        } else if (performance.navigation && performance.navigation.type === 1) {
            isReload = true;
        }
    }
    
    if (document.referrer === "" || document.referrer.indexOf(location.hostname) === -1) {
        isExternalEntry = true;
    }
    
    // Chỉ hiện Popup nếu là F5 hoặc mới vào web lần đầu
    if (isReload || isExternalEntry) {
        setTimeout(function() {
            var promoModalEl = document.getElementById('welcomePromoModal');
            console.log("Modal element found:", promoModalEl);
            if (promoModalEl) {
                try {
                    var promoModal = new bootstrap.Modal(promoModalEl, {
                        backdrop: 'static', 
                        keyboard: true
                    });
                    promoModal.show();
                    console.log("Modal shown successfully.");
                } catch (e) {
                    console.error("Error showing modal:", e);
                }
            }
        }, 1000);
    }
});
</script>
<?php endif; ?>

<?php include __DIR__ . '/views/client/layouts/footer.php'; ?>


