<?php
// 1. Khởi tạo Session và Import các cấu hình
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/models/Booking.php';

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
} catch (Exception $e) {}

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
    // 5.1 Lấy Banner - Ưu tiên lấy từ Database
    $stmt_banners = $db->prepare("SELECT * FROM banners ORDER BY display_order ASC");
    $stmt_banners->execute();
    $banners_db = $stmt_banners->fetchAll(PDO::FETCH_ASSOC);

    // 5.2 Lấy Video - Kiểm tra kỹ is_active
    $stmt_video = $db->prepare("SELECT * FROM videos WHERE id = 1 LIMIT 1");
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

} catch (Exception $e) {}

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
              </div>
            </div>
          </div>
        </div>
      <?php $first = false; endforeach; ?>
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
<section id="about" class="about-section" style="background: #0c0b09; color: #fff; padding: 100px 0; overflow: hidden;">
  <div class="container-fluid px-0"> 
    <div class="row g-0 align-items-center">
      <div class="col-lg-7" style="padding-left: 5%; padding-right: 30px;"> 
        <div class="video-wrapper" style="position: relative; width: 100%; border-radius: 5px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.5); background: #000;">
          
          <?php if ($video_type == 'youtube' && !empty($video_url)): ?>
            <iframe width="100%" height="500" 
                    src="https://www.youtube.com/embed/<?php echo htmlspecialchars($video_url); ?>" 
                    frameborder="0" allowfullscreen style="display: block; border: none;">
            </iframe>
          <?php elseif ($video_type == 'local' && !empty($file_path)): ?>
            <video controls style="width: 100%; height: 500px; display: block; object-fit: cover;">
                <source src="admin/<?php echo htmlspecialchars($file_path); ?>" type="video/mp4">
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

  <section id="menu" class="menu section-bg" style="background: #0c0b09; padding: 80px 0;">
    <div class="container">
      <div class="section-title text-center mb-5">
        <h2 style="color: #cda45e; font-family: 'Playfair Display', serif;">Thực Đơn</h2>
        <p style="color: white; font-size: 24px;">Khám phá hương vị <?= htmlspecialchars($settings['restaurant_name'] ?? 'Restaurantly') ?></p>
      </div>

      <?php
      // Fix N+1: 1 query JOIN duy nhất thay vì query trong foreach
      // Đồng bộ is_active với manage_foods.php
      $all_foods_stmt = $db->prepare(
          "SELECT f.*, c.id as cat_id
           FROM foods f
           JOIN categories c ON f.category_id = c.id
           WHERE f.is_active = 1
           ORDER BY c.name ASC, f.id ASC"
      );
      $all_foods_stmt->execute();
      $foods_by_cat = [];
      foreach ($all_foods_stmt->fetchAll(PDO::FETCH_ASSOC) as $frow) {
          $foods_by_cat[$frow['cat_id']][] = $frow;
      }

      $categories = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
      foreach (array_chunk($categories, 3) as $chunk):
      ?>
      <div class="row mb-5">
        <?php foreach ($chunk as $cat):
          $cat_foods = array_slice($foods_by_cat[$cat['id']] ?? [], 0, 3);
        ?>
        <div class="col-lg-4 col-md-6 mb-4">
          <div class="category-column-box">
            <h3 class="category-title text-center" style="color: #cda45e; margin-bottom: 30px;">
              <?= htmlspecialchars(mb_convert_case($cat['name'], MB_CASE_UPPER, 'UTF-8')) ?>
            </h3>
            <div class="menu-list">
              <?php if (empty($cat_foods)): ?>
                <p class="text-muted text-center small fst-italic">Chưa có món</p>
              <?php else: ?>
              <?php foreach ($cat_foods as $f): ?>
              <div class="menu-item-horizontal d-flex align-items-center mb-4">
                <div class="item-img" style="width:70px;height:70px;margin-right:15px;flex-shrink:0;">
                  <img src="public/assets/img/menu/<?= htmlspecialchars($f['image']) ?>"
                       alt="<?= htmlspecialchars($f['name']) ?>"
                       onerror="this.src='public/assets/img/menu/default.jpg'"
                       style="width:100%;height:100%;object-fit:cover;border-radius:50%;border:3px solid #37332a;">
                </div>
                <div class="item-details flex-grow-1">
                  <div class="d-flex justify-content-between align-items-baseline">
                    <h5 class="food-name" style="color:#fff;font-size:18px;margin:0;">
                      <?= htmlspecialchars($f['name']) ?>
                    </h5>
                    <span class="food-price" style="color:#cda45e;font-weight:600;">
                      <?= number_format($f['price'], 0, ',', '.') ?>đ
                    </span>
                  </div>
                  <p class="food-desc" style="color:#aaaaaa;font-size:14px;font-style:italic;margin:0;">
                    <?= htmlspecialchars($f['description']) ?>
                  </p>
                </div>
              </div>
              <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endforeach; ?>
      <div class="text-center mt-4">
        <a href="menu.php" class="btn-view-all-custom">Xem toàn bộ thực đơn</a>
      </div>
    </div>
  </section>

  <section id="combos" style="background: #1a1814; padding: 80px 0; border-top: 1px solid #37332a; border-bottom: 1px solid #37332a;">
    <div class="container">
      <div class="section-title text-center mb-5">
        <h2 style="color: #cda45e; font-family: 'Playfair Display', serif; font-size: 36px; font-weight: 700;">Combo Đặc Biệt</h2>
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
  </section>

  <section id="chefs" class="chefs" style="background: #0c0b09; padding: 80px 0;">
    <div class="container">
      <div class="section-title text-center mb-5">
        <h2 class="chefs-subtitle">Đội ngũ đầu bếp</h2>
        <p class="chefs-title">Những nghệ nhân ẩm thực hàng đầu</p>
      </div>
      <div class="row justify-content-center">
        <div class="col-lg-4 col-md-6">
          <div class="chef-member-card">
            <img src="public/assets/img/chefs/chefs-1.jpg" class="img-fluid" alt="Chef 1">
            <div class="member-info">
              <h4>Walter White</h4>
              <span>Bếp trưởng</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</main>

<?php include __DIR__ . '/views/client/layouts/footer.php'; ?>

<style>
    .btn-menu-custom, .btn-book-custom, .btn-view-all-custom {
        font-weight: 600; font-size: 13px; letter-spacing: 0.5px; text-transform: uppercase; 
        display: inline-block; padding: 12px 30px; border-radius: 50px; transition: 0.3s; 
        color: white; border: 2px solid #cda45e; text-decoration: none;
    }
    .btn-book-custom { background: #cda45e; }
    .btn-book-custom:hover, .btn-menu-custom:hover, .btn-view-all-custom:hover { background: #cda45e; color: #fff; }

    .explore-menu-link {
        font-family: 'Poppins', sans-serif; font-size: 0.9rem; letter-spacing: 3px; 
        color: #d9ba85; text-decoration: none; text-transform: uppercase; 
        border-bottom: 2px solid #d9ba85; padding-bottom: 8px; transition: 0.3s; font-weight: 600;
    }

    .combo-card-custom {
        background: #0c0b09; border: 1px solid #37332a; padding: 25px; height: 100%; 
        display: flex; flex-direction: column; transition: 0.4s; border-radius: 10px;
    }
    .combo-card-custom:hover { transform: translateY(-10px); border-color: #cda45e; box-shadow: 0 5px 20px rgba(205, 164, 94, 0.2); }
    
    .combo-img img { width: 100%; height: 220px; object-fit: cover; border-radius: 5px; transition: 0.5s; }
    .combo-img:hover img { transform: scale(1.1); }
    
    .combo-name-custom { color: #fff; margin: 0; font-size: 22px; font-family: 'Poppins', sans-serif; }
    .combo-price-custom { color: #cda45e; font-weight: 700; font-size: 18px; }
    .combo-desc-custom { color: #aaaaaa; font-size: 14px; font-style: italic; flex-grow: 1; margin: 10px 0; }
    
    .combo-items-list { border-top: 1px dashed #37332a; padding-top: 15px; margin-bottom: 20px; }
    .combo-items-list small { color: #cda45e; text-transform: uppercase; letter-spacing: 1px; font-size: 11px; font-weight: 600; }
    .combo-items-list p { color: #ced4da; font-size: 13px; margin: 5px 0 0 0; }

    .btn-combo-order { background: transparent; color: #fff; border: 2px solid #cda45e; width: 100%; padding: 12px; border-radius: 50px; font-weight: 600; transition: 0.3s; text-transform: uppercase; }
    .btn-combo-order:hover { background: #cda45e !important; color: #000 !important; }

    .chefs-subtitle { font-size: 14px; font-weight: 500; letter-spacing: 2px; text-transform: uppercase; color: #aaaaaa; font-family: 'Poppins', sans-serif; }
    .chefs-title { margin: 15px 0 0 0; font-size: 36px; font-weight: 700; font-family: 'Playfair Display', serif; color: #cda45e; }
    
    .chef-member-card { background: #1a1814; padding: 30px; border: 1px solid #37332a; text-align: center; transition: 0.3s; border-radius: 5px; }
    .chef-member-card img { border-radius: 5px; margin-bottom: 15px; }
    .chef-member-card h4 { font-weight: 700; margin-bottom: 5px; font-size: 18px; color: #fff; }
    .chef-member-card span { display: block; font-size: 15px; font-style: italic; color: #cda45e; }
</style>