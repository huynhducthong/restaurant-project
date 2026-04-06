<?php
// 1. Import các cấu hình cần thiết
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/models/Booking.php';

// 2. Khởi tạo kết nối
$database = new Database();
$db = $database->getConnection();

// 3. Xử lý logic đặt bàn
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

// 4. Lấy dữ liệu Video (Hỗ trợ cả YouTube và Local File)
try {
    $query = "SELECT * FROM videos WHERE is_active = 1 LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $video = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $video = null;
}

$video_type = $video ? $video['video_type'] : 'youtube';
$video_url = $video ? $video['video_url'] : 'dQw4w9WgXcQ';
$file_path = $video ? $video['file_path'] : '';

// --- MỚI: TRUY VẤN DỮ LIỆU COMBO ---
$sql_combos = "SELECT c.*, GROUP_CONCAT(f.name SEPARATOR ', ') as list_foods 
               FROM combos c
               LEFT JOIN combo_items ci ON c.id = ci.combo_id
               LEFT JOIN foods f ON ci.food_id = f.id
               WHERE c.status = 1 
               GROUP BY c.id 
               ORDER BY c.id DESC";
$stmt_combos = $db->prepare($sql_combos);
$stmt_combos->execute();

// 5. BẮT ĐẦU HIỂN THỊ GIAO DIỆN
include __DIR__ . '/views/client/layouts/header.php'; 
?>

<section id="hero" class="p-0">
  <div id="heroCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="3000">
    <div class="carousel-indicators">
      <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active"></button>
      <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1"></button>
      <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2"></button>
    </div>
    <div class="carousel-inner">
      <div class="carousel-item active" style="background-image: url('public/assets/img/hero-bg.jpg');">
        <div class="carousel-container">
          <div class="carousel-content container text-center">
            <h2>Chào mừng đến với <span>Restaurantly</span></h2>
            <p>Trải nghiệm ẩm thực tinh tế trong không gian sang trọng.</p>
          </div>
        </div>
      </div>
      <div class="carousel-item" style="background-image: url('public/assets/img/hero-bg-2.jpg');">
        <div class="carousel-container">
          <div class="carousel-content container text-center">
            <h2>Hương vị khó quên</h2>
            <p>Nguyên liệu tươi sạch được chế biến bởi những đầu bếp hàng đầu.</p>
          </div>
        </div>
      </div>
      <div class="carousel-item" style="background-image: url('public/assets/img/hero-bg-3.jpg');">
        <div class="carousel-container">
          <div class="carousel-content container text-center">
            <h2>Không gian ấm cúng</h2>
            <p>Nơi lý tưởng cho những buổi tiệc gia đình.</p>
          </div>
        </div>
      </div>
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
      <span class="carousel-control-prev-icon"></span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
      <span class="carousel-control-next-icon"></span>
    </button>
  </div>
</section>

<section id="about" class="about-section" style="background: #0c0b09; color: #fff; padding: 100px 0; overflow: hidden;">
  <div class="container-fluid px-0"> 
    <div class="row g-0 align-items-center">
      <div class="col-lg-7" style="padding-left: 5%; padding-right: 30px;"> 
        <div class="video-wrapper" style="position: relative; width: 100%; border-radius: 5px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.5); background: #000;">
          <?php if ($video_type == 'youtube'): ?>
            <iframe width="100%" height="500" src="https://www.youtube.com/embed/<?php echo $video_url; ?>" frameborder="0" allowfullscreen style="display: block; border: none;"></iframe>
          <?php else: ?>
            <video controls style="width: 100%; height: 500px; display: block; object-fit: cover;">
                <source src="/restaurant-project/<?php echo $file_path; ?>" type="video/mp4">
            </video>
          <?php endif; ?>
        </div>
      </div>
      <div class="col-lg-5 p-4 p-md-5">
        <div class="about-content" style="padding-right: 5%;"> 
          <h2 style="font-family: 'Playfair Display', serif; color: #d9ba85; font-size: 3rem; margin-bottom: 25px; font-weight: 700; line-height: 1.2;">Câu Chuyện <br> Về Restaurantly</h2>
          <p style="font-family: 'Poppins', sans-serif; font-weight: 300; line-height: 2; color: #ced4da; font-size: 1.15rem; margin-bottom: 25px;">
            Nằm giữa lòng Biên Hòa, chúng tôi mang đến một không gian ẩm thực tinh tế, nơi mỗi nguyên liệu đều được kể thành một câu chuyện riêng biệt. 
          </p>
          <div class="mt-5">
            <a href="#menu" style="font-family: 'Poppins', sans-serif; font-size: 0.9rem; letter-spacing: 3px; color: #d9ba85; text-decoration: none; text-transform: uppercase; border-bottom: 2px solid #d9ba85; padding-bottom: 8px; transition: 0.3s; font-weight: 600;">Khám phá thực đơn →</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section id="menu" class="menu section-bg" style="background: #0c0b09; padding: 80px 0;">
  <div class="container">
    <div class="section-title text-center mb-5">
      <h2 style="color: #cda45e; font-family: 'Playfair Display', serif;">Thực Đơn</h2>
      <p style="color: white; font-size: 24px;">Khám phá hương vị Restaurantly</p>
    </div>

    <?php 
    $categories = $db->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);
    $chunks = array_chunk($categories, 3);
    foreach ($chunks as $chunk): 
    ?>
    <div class="row mb-5">
      <?php foreach ($chunk as $cat): 
        $cat_id = $cat['id'];
        $foods = $db->query("SELECT * FROM foods WHERE category_id = $cat_id LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
      ?>
      <div class="col-lg-4 col-md-6 mb-4">
        <div class="category-column-box">
          <h3 class="category-title text-center" style="color: #cda45e; margin-bottom: 30px;"><?php echo mb_convert_case($cat['name'], MB_CASE_UPPER, "UTF-8"); ?></h3>
          <div class="menu-list">
            <?php foreach ($foods as $f): ?>
            <div class="menu-item-horizontal d-flex align-items-center mb-4">
              <div class="item-img" style="width: 70px; height: 70px; margin-right: 15px;">
                <img src="public/assets/img/menu/<?php echo $f['image']; ?>" alt="<?php echo $f['name']; ?>" style="width: 100%; border-radius: 50%; border: 3px solid #37332a;">
              </div>
              <div class="item-details flex-grow-1">
                <div class="d-flex justify-content-between align-items-baseline">
                  <h5 class="food-name" style="color: #fff; font-size: 18px; margin: 0;"><?php echo $f['name']; ?></h5>
                  <span class="food-price" style="color: #cda45e; font-weight: 600;"><?php echo number_format($f['price'], 0, ',', '.'); ?>đ</span>
                </div>
                <p class="food-desc" style="color: #aaaaaa; font-size: 14px; font-style: italic; margin: 0;"><?php echo $f['description']; ?></p>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="text-center mt-4">
    <a href="menu.php" class="btn-view-all" style="color: #cda45e; text-decoration: none; border: 2px solid #cda45e; padding: 10px 30px; border-radius: 50px; transition: 0.3s;">Xem toàn bộ thực đơn</a>
  </div>
</section>

<section id="combos" style="background: #1a1814; padding: 80px 0; border-top: 1px solid #37332a; border-bottom: 1px solid #37332a;">
  <div class="container">
    <div class="section-title text-center mb-5">
      <h2 style="color: #cda45e; font-family: 'Playfair Display', serif; font-size: 36px; font-weight: 700;">Combo Đặc Biệt</h2>
      <p style="color: #fff; font-size: 18px; font-style: italic;">Lựa chọn hoàn hảo để chia sẻ niềm vui</p>
    </div>

    <div class="row g-4 justify-content-center">
      <?php while ($row = $stmt_combos->fetch(PDO::FETCH_ASSOC)): ?>
      <div class="col-lg-4 col-md-6">
        <div class="combo-card" style="background: #0c0b09; border: 1px solid #37332a; padding: 25px; height: 100%; display: flex; flex-direction: column; transition: 0.4s; border-radius: 10px;">
          <div class="combo-img mb-3" style="height: 220px; overflow: hidden; border-radius: 5px;">
            <img src="public/assets/img/combos/<?= $row['image'] ?: 'default-combo.jpg' ?>" style="width: 100%; height: 100%; object-fit: cover; transition: 0.5s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
          </div>
          
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h4 style="color: #fff; margin: 0; font-size: 22px; font-family: 'Poppins', sans-serif;"><?= htmlspecialchars($row['name']) ?></h4>
            <span style="color: #cda45e; font-weight: 700; font-size: 18px;"><?= number_format($row['price'], 0, ',', '.') ?>đ</span>
          </div>
          
          <p style="color: #aaaaaa; font-size: 14px; font-style: italic; flex-grow: 1;"><?= htmlspecialchars($row['description']) ?></p>
          
          <div style="border-top: 1px dashed #37332a; padding-top: 15px; margin-bottom: 20px;">
              <small style="color: #cda45e; text-transform: uppercase; letter-spacing: 1px; font-size: 11px; font-weight: 600;">Gồm các món:</small>
              <p style="color: #ced4da; font-size: 13px; margin: 5px 0 0 0;"><?= htmlspecialchars($row['list_foods']) ?></p>
          </div>
          
          <button onclick="addToCart('combo', <?= $row['id'] ?>)" style="background: transparent; color: #fff; border: 2px solid #cda45e; width: 100%; padding: 12px; border-radius: 50px; font-weight: 600; transition: 0.3s; text-transform: uppercase; letter-spacing: 1px;">Đặt ngay</button>
        </div>
      </div>
      <?php endwhile; ?>
    </div>
  </div>
</section>
<section id="chefs" class="chefs" style="background: #0c0b09; padding: 80px 0;">
  <div class="container">
    <div class="section-title text-center" style="margin-bottom: 40px;">
      <h2 style="font-size: 14px; font-weight: 500; letter-spacing: 2px; text-transform: uppercase; color: #aaaaaa; font-family: 'Poppins', sans-serif;">Đội ngũ đầu bếp</h2>
      <p style="margin: 15px 0 0 0; font-size: 36px; font-weight: 700; font-family: 'Playfair Display', serif; color: #cda45e;">Những nghệ nhân ẩm thực hàng đầu</p>
    </div>
    <div class="row justify-content-center">
      <div class="col-lg-4 col-md-6">
        <div class="member" style="background: #1a1814; padding: 30px; border: 1px solid #37332a; text-align: center; transition: 0.3s; border-radius: 5px;">
          <img src="public/assets/img/chefs/chefs-1.jpg" class="img-fluid" alt="Chef 1" style="border-radius: 5px; margin-bottom: 15px;">
          <div class="member-info">
            <h4 style="font-weight: 700; margin-bottom: 5px; font-size: 18px; color: #fff;">Walter White</h4>
            <span style="display: block; font-size: 15px; font-style: italic; color: #cda45e;">Bếp trưởng</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/views/client/layouts/footer.php'; ?>

<style>
  .combo-card:hover { transform: translateY(-10px); border-color: #cda45e !important; box-shadow: 0 5px 20px rgba(205, 164, 94, 0.2); }
  .combo-card button:hover { background: #cda45e !important; color: #000 !important; }
</style>