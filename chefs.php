<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/config/database.php';
$database = new Database();
$db = $database->getConnection();

// Automated DB upgrades
try {
    $db->exec("ALTER TABLE chefs ADD COLUMN IF NOT EXISTS awards TEXT DEFAULT NULL");
    $db->exec("ALTER TABLE chefs ADD COLUMN IF NOT EXISTS signature_dishes VARCHAR(255) DEFAULT NULL");
    
    // Create chef_reviews table if it does not exist
    $db->exec("CREATE TABLE IF NOT EXISTS chef_reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        chef_id INT NOT NULL,
        user_id INT NULL,
        author_name VARCHAR(100) DEFAULT 'Khách ẩn danh',
        rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
        comment TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    $db->exec("ALTER TABLE chef_reviews ADD COLUMN IF NOT EXISTS author_name VARCHAR(100) DEFAULT 'Khách ẩn danh'");
} catch (Exception $e) {
    // Ignore database upgrade errors
}

$stmt = $db->prepare("SELECT * FROM chefs WHERE is_active = 1 AND position IN ('Bếp trưởng', 'Bếp phó', 'Bếp chính') ORDER BY sort_order ASC, id DESC");
$stmt->execute();
$all_chefs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Map signature dishes for each chef
foreach ($all_chefs as &$chef) {
    $chef['signature_dishes_list'] = [];
    if (!empty($chef['signature_dishes'])) {
        $ids = array_map('intval', explode(',', $chef['signature_dishes']));
        if (!empty($ids)) {
            $in_clause = implode(',', $ids);
            $food_stmt = $db->query("SELECT id, name, price, image, description, allergens, chef_note FROM foods WHERE id IN ($in_clause) AND status = 1");
            $chef['signature_dishes_list'] = $food_stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
}
unset($chef);

// Pagination logic
$limit = 8;
$total_chefs = count($all_chefs);
$total_pages = ceil($total_chefs / $limit);
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
}
$offset = ($page - 1) * $limit;
$chefs = array_slice($all_chefs, $offset, $limit);

include __DIR__ . '/views/client/layouts/header.php';
?>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500&family=Montserrat:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Open+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
<style>
:root {
  --bg-color: #1A1A1D;
  --card-bg: #262629;
  --text-main: #EAEAEA;
  --text-muted: #A0A0A5;
  --accent-burgundy: #A88746;
  --border-light: rgba(168, 135, 70, 0.25);
  --ease: cubic-bezier(.4,0,.2,1);
  --gold: #A88746;
  --olive: #8B6D36;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body { background: var(--bg-color); color: var(--text-main); font-family: 'Open Sans', sans-serif; }

/* HERO */
.ch-hero {
  position: relative; overflow: hidden;
  padding: 160px 0 80px; text-align: center;
  background: var(--bg-color);
}
.ch-hero-eyebrow {
  display: inline-flex; align-items: center; gap: 12px;
  font-size: 11px; letter-spacing: 3px; text-transform: uppercase; color: var(--accent-burgundy); font-weight: 600;
  margin-bottom: 20px;
}
.ch-hero-eyebrow::before, .ch-hero-eyebrow::after {
  content: ''; display: block; width: 30px; height: 1px; background: var(--accent-burgundy); opacity: .5;
}
.ch-hero h1 {
  font-family: 'Cormorant Garamond', serif; font-weight: 700;
  font-size: clamp(2.6rem, 6vw, 4.5rem); color: var(--accent-burgundy); line-height: 1.2; margin-bottom: 20px;
}
.ch-hero h1 em { font-style: italic; color: var(--accent-burgundy); font-weight: 600; }
.ch-hero-sub {
  font-size: 15px; color: var(--text-muted); font-weight: 400;
  max-width: 550px; margin: 0 auto; line-height: 1.8;
}

/* STATS STRIP */
.ch-strip {
  background: var(--card-bg);
  border-top: 1px solid var(--border-light);
  border-bottom: 1px solid var(--border-light);
  padding: 40px 32px;
  display: flex; align-items: center; justify-content: center;
  gap: 80px; flex-wrap: wrap;
}
.strip-item { text-align: center; }
.strip-num { font-family: 'Cormorant Garamond', serif; font-weight: 700; font-size: 2.5rem; color: var(--accent-burgundy); line-height: 1; margin-bottom: 10px; }
.strip-lbl { font-size: 10px; letter-spacing: 2px; text-transform: uppercase; color: var(--text-muted); font-weight: 600; }

/* WRAP + GRID */
.ch-wrap { max-width: 1200px; margin: 0 auto; padding: 100px 20px; }
.ch-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 30px;
}
@media(max-width: 991px){
  .ch-grid { grid-template-columns: repeat(2, 1fr); gap: 30px; }
}
@media(max-width: 576px){
  .ch-grid { grid-template-columns: 1fr; gap: 40px; }
}

/* PAGINATION */
.ch-pagination-wrap {
  display: flex;
  justify-content: center;
  margin-top: 50px;
}
.ch-pagination {
  display: flex;
  list-style: none;
  padding: 0;
  margin: 0;
  gap: 10px;
  align-items: center;
}
.ch-pagination li a {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  border: 1px solid var(--border-light);
  color: var(--text-muted);
  text-decoration: none;
  font-family: 'Montserrat', sans-serif;
  font-weight: 600;
  font-size: 14px;
  transition: all 0.3s var(--ease);
}
.ch-pagination li.active a {
  background: var(--accent-burgundy);
  color: #fff;
  border-color: var(--accent-burgundy);
  box-shadow: 0 0 15px rgba(168, 135, 70, 0.4);
}
.ch-pagination li a:hover {
  background: rgba(168, 135, 70, 0.1);
  color: var(--accent-burgundy);
  border-color: var(--accent-burgundy);
}
.ch-pagination li.disabled a {
  opacity: 0.3;
  pointer-events: none;
}

/* CARD */
.ch-card {
  background: var(--card-bg);
  border: 1px solid var(--border-light);
  border-top: 3px solid var(--accent-burgundy);
  display: flex; flex-direction: column;
  transition: transform 0.3s var(--ease), box-shadow 0.3s var(--ease);
}
.ch-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 15px 40px rgba(0,0,0,0.15);
}

/* Ảnh */
.ch-img { width: 100%; aspect-ratio: 3/4; overflow: hidden; position: relative; border-bottom: 1px solid var(--border-light); }
.ch-img img {
  width: 100%; height: 100%; object-fit: cover;
  filter: grayscale(0.2) contrast(1.05);
  transition: transform 0.65s var(--ease), filter 0.5s var(--ease);
}
.ch-card:hover .ch-img img {
  transform: scale(1.05);
  filter: grayscale(0) contrast(1.1);
}

/* THÔNG TIN PUBLIC BÊN DƯỚI */
.ch-info {
  padding: 35px 30px;
  display: flex; flex-direction: column; flex: 1;
}
.ch-position {
  font-size: 10px; letter-spacing: 2px; text-transform: uppercase;
  color: var(--accent-burgundy); margin-bottom: 10px; font-weight: 600;
}
.ch-name {
  font-family: 'Cormorant Garamond', serif; font-weight: 700;
  font-size: 2rem; color: var(--text-main); line-height: 1.1; margin-bottom: 20px;
}
.ch-exp-spec {
  display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px;
  font-size: 12px; color: var(--text-muted);
}
.ch-exp-spec i { color: var(--accent-burgundy); margin-right: 6px; font-size: 14px; }

.ch-desc {
  font-size: 13px; color: var(--text-muted); line-height: 1.8;
  margin-bottom: 25px; flex: 1;
}

.ch-quote {
  font-family: 'Cormorant Garamond', serif; font-style: italic;
  font-size: 1.1rem; color: var(--accent-burgundy);
  border-left: 2px solid var(--accent-burgundy);
  padding-left: 15px; margin-bottom: 25px;
  line-height: 1.6;
}

.ch-social { display: flex; gap: 12px; border-top: 1px solid var(--border-light); padding-top: 20px; }
.ch-social a {
  width: 36px; height: 36px; border: 1px solid var(--border-light);
  display: flex; align-items: center; justify-content: center;
  color: var(--text-muted); font-size: 14px; text-decoration: none;
  transition: 0.3s;
}
.ch-social a:hover { background: var(--accent-burgundy); color: #fff; border-color: var(--accent-burgundy); }

/* EMPTY */
.ch-empty { text-align: center; padding: 100px 20px; color: var(--text-muted); }
.ch-empty i { font-size: 52px; opacity: 0.2; display: block; margin-bottom: 16px; }

/* CHEF DETAIL MEGA MODAL */
.chef-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.75);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.4s var(--ease);
}
.chef-modal-overlay.active {
  opacity: 1;
  pointer-events: auto;
}
.chef-modal-container {
  width: 90%;
  max-width: 1000px;
  height: 85vh;
  max-height: 750px;
  background: #1E1E22;
  border: 1px solid var(--border-light);
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
  position: relative;
  overflow: hidden;
  transform: translateY(30px);
  transition: transform 0.4s var(--ease);
  display: flex;
}
.chef-modal-overlay.active .chef-modal-container {
  transform: translateY(0);
}
.chef-modal-close {
  position: absolute;
  top: 20px;
  right: 20px;
  background: rgba(0,0,0,0.5);
  border: 1px solid var(--border-light);
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  cursor: pointer;
  z-index: 10;
  transition: 0.3s;
  color: #fff;
}
.chef-modal-close:hover {
  background: var(--gold);
  color: #fff;
  border-color: var(--gold);
}
.chef-modal-body {
  display: flex;
  width: 100%;
  height: 100%;
}
.chef-modal-left {
  width: 40%;
  height: 100%;
  background: var(--bg-color);
  overflow: hidden;
  border-right: 1px solid var(--border-light);
}
.chef-modal-left img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
.chef-modal-right {
  width: 60%;
  height: 100%;
  padding: 40px;
  display: flex;
  flex-direction: column;
  position: relative;
  background: #1E1E22;
}
.chef-modal-scroll-content {
  flex: 1;
  overflow-y: auto;
  padding-right: 15px;
}
/* Custom Scrollbar */
.chef-modal-scroll-content::-webkit-scrollbar {
  width: 6px;
}
.chef-modal-scroll-content::-webkit-scrollbar-track {
  background: rgba(255,255,255,0.02);
}
.chef-modal-scroll-content::-webkit-scrollbar-thumb {
  background: var(--gold);
  border-radius: 3px;
}
.chef-modal-pos {
  font-size: 11px;
  letter-spacing: 3px;
  text-transform: uppercase;
  color: var(--gold);
  font-weight: 600;
  display: block;
  margin-bottom: 8px;
}
.chef-modal-name {
  font-family: 'Playfair Display', serif;
  font-size: 2.2rem;
  font-weight: 700;
  color: var(--gold);
  margin-bottom: 20px;
  line-height: 1.2;
}
.chef-modal-quote-wrap {
  position: relative;
  border-left: 3px solid var(--gold);
  padding-left: 20px;
  margin-bottom: 25px;
}
.chef-modal-quote {
  font-family: 'Playfair Display', serif;
  font-style: italic;
  font-size: 1.15rem;
  color: var(--text-main);
  line-height: 1.6;
}
.chef-modal-desc {
  font-size: 14px;
  color: var(--text-muted);
  line-height: 1.8;
  margin-bottom: 30px;
}
.chef-modal-section {
  margin-bottom: 30px;
  border-top: 1px dashed var(--border-light);
  padding-top: 25px;
}
.chef-modal-section .section-title {
  font-family: 'Playfair Display', serif;
  font-size: 1.25rem;
  color: #fff;
  margin-bottom: 15px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 10px;
}
.chef-modal-section .section-title i {
  color: var(--gold);
}

/* Awards grid */
.awards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 15px;
}
.award-item {
  display: flex;
  align-items: center;
  gap: 12px;
  background: var(--bg-color);
  padding: 12px 15px;
  border: 1px solid var(--border-light);
}
.award-icon {
  font-size: 22px;
  color: var(--gold);
  display: flex;
  align-items: center;
}
.award-info {
  display: flex;
  flex-direction: column;
}
.award-title {
  font-size: 13px;
  font-weight: 600;
  color: var(--text-main);
}
.award-subtitle {
  font-size: 11px;
  color: var(--text-muted);
}

/* Signature dishes grid */
.dishes-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
}
.dish-item {
  display: flex;
  gap: 12px;
  border: 1px solid var(--border-light);
  background: var(--bg-color);
  padding: 10px;
  align-items: center;
  transition: border-color 0.2s;
}
.dish-item:hover {
  border-color: var(--gold);
}
.dish-img {
  width: 70px;
  height: 70px;
  flex-shrink: 0;
  overflow: hidden;
  background: var(--card-bg);
}
.dish-img img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.dish-info {
  display: flex;
  flex-direction: column;
  justify-content: center;
  overflow: hidden;
}
.dish-name {
  font-size: 13px;
  font-weight: 600;
  color: var(--text-main);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.dish-desc {
  font-size: 11px;
  color: var(--text-muted);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-top: 2px;
}
.dish-price {
  font-size: 12px;
  color: var(--gold);
  font-weight: 600;
  margin-top: 4px;
}

/* Modal Footer & Button */
.chef-modal-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 30px;
  padding-top: 25px;
  border-top: 1px solid var(--border-light);
  flex-wrap: wrap;
  gap: 20px;
}
.chef-modal-social {
  display: flex;
  gap: 10px;
}
.chef-modal-social a {
  width: 38px;
  height: 38px;
  border: 1px solid var(--border-light);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-muted);
  font-size: 15px;
  text-decoration: none;
  transition: 0.3s;
}
.chef-modal-social a:hover {
  background: var(--gold);
  color: #fff;
  border-color: var(--gold);
}
.chef-book-btn {
  background: var(--gold);
  color: #ffffff;
  padding: 12px 24px;
  text-decoration: none;
  font-size: 13px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 1px;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: 0.3s;
  border: 1px solid var(--gold);
}
.chef-book-btn:hover {
  background: transparent;
  color: var(--gold);
  border-color: var(--gold);
}

/* Star Rating Picker */
.star-rating-picker {
  display: flex;
  align-items: center;
  gap: 12px;
}
.rating-picker-label {
  font-size: 13px;
  color: var(--text-muted);
}
.rating-stars-picker {
  display: flex;
  gap: 6px;
  cursor: pointer;
}
.rating-stars-picker .star-pick {
  font-size: 20px;
  color: #4A4A4F;
  transition: color 0.2s;
}
.rating-stars-picker .star-pick.active,
.rating-stars-picker .star-pick:hover {
  color: #FFC107 !important;
}

/* Reviews Summary */
.reviews-summary {
  display: flex;
  align-items: center;
  gap: 20px;
  background: var(--bg-color);
  padding: 15px 20px;
  border: 1px solid var(--border-light);
  margin-bottom: 25px;
}
.rating-big {
  font-size: 2.5rem;
  font-weight: 700;
  color: var(--gold);
  line-height: 1;
}
.rating-details {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.stars-wrap {
  display: flex;
  gap: 3px;
}
.reviews-count {
  font-size: 12px;
  color: var(--text-muted);
}

/* Reviews List */
.reviews-list {
  display: flex;
  flex-direction: column;
  gap: 15px;
  max-height: 250px;
  overflow-y: auto;
  padding-right: 8px;
  margin-bottom: 25px;
}
.reviews-list::-webkit-scrollbar {
  width: 4px;
}
.reviews-list::-webkit-scrollbar-thumb {
  background: var(--gold);
  border-radius: 2px;
}
.review-card {
  background: var(--bg-color);
  border: 1px solid var(--border-light);
  padding: 15px;
}
.review-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 10px;
}
.review-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  object-fit: cover;
  border: 1px solid var(--border-light);
}
.review-meta {
  display: flex;
  flex-direction: column;
  flex: 1;
}
.review-author {
  font-size: 13px;
  font-weight: 600;
  color: var(--text-main);
}
.review-date {
  font-size: 10px;
  color: var(--text-muted);
}
.review-stars {
  display: flex;
  gap: 2px;
  font-size: 11px;
}
.review-comment {
  font-size: 13px;
  color: var(--text-muted);
  line-height: 1.6;
  white-space: pre-line;
}

/* Review form inputs */
.review-input {
  width: 100%;
  background: var(--bg-color);
  border: 1px solid var(--border-light);
  color: var(--text-main);
  padding: 10px 14px;
  font-size: 13px;
  outline: none;
  font-family: inherit;
  transition: border-color 0.2s;
}
.review-input:focus {
  border-color: var(--gold);
}
.btn-submit-review {
  background: var(--gold);
  border: 1px solid var(--gold);
  color: #fff;
  padding: 8px 20px;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 1px;
  font-weight: 600;
  cursor: pointer;
  transition: 0.3s;
}
.btn-submit-review:hover {
  background: transparent;
  color: var(--gold);
}

/* DISH DETAIL SUB-MODAL */
.dish-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.7);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  z-index: 10000;
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.3s ease;
}
.dish-modal-overlay.active {
  opacity: 1;
  pointer-events: auto;
}
.dish-modal-container {
  width: 90%;
  max-width: 500px;
  background: #1E1E22;
  border: 1px solid var(--border-light);
  position: relative;
  overflow: hidden;
  transform: scale(0.9);
  transition: transform 0.3s var(--ease);
}
.dish-modal-overlay.active .dish-modal-container {
  transform: scale(1);
}
.dish-modal-close {
  position: absolute;
  top: 15px;
  right: 15px;
  background: rgba(0,0,0,0.5);
  border: 1px solid var(--border-light);
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  cursor: pointer;
  z-index: 10;
  transition: 0.3s;
  color: #fff;
}
.dish-modal-close:hover {
  background: var(--gold);
  border-color: var(--gold);
}
.dish-modal-content {
  display: flex;
  flex-direction: column;
}
.dish-modal-img-wrap {
  width: 100%;
  height: 250px;
  overflow: hidden;
  border-bottom: 1px solid var(--border-light);
}
.dish-modal-img-wrap img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.dish-modal-info {
  padding: 25px;
  max-height: 300px;
  overflow-y: auto;
}
.dish-modal-info::-webkit-scrollbar {
  width: 4px;
}
.dish-modal-info::-webkit-scrollbar-thumb {
  background: var(--gold);
  border-radius: 2px;
}
.dish-modal-title {
  font-family: 'Playfair Display', serif;
  font-size: 1.6rem;
  color: var(--gold);
  margin-bottom: 8px;
  font-weight: 700;
}
.dish-modal-price {
  font-size: 14px;
  font-weight: 700;
  color: #fff;
  display: block;
  margin-bottom: 15px;
}
.dish-modal-desc {
  font-size: 13px;
  color: var(--text-muted);
  line-height: 1.6;
  margin-bottom: 20px;
}
.dish-info-item {
  margin-top: 15px;
  border-top: 1px dashed rgba(255,255,255,0.1);
  padding-top: 12px;
}
.dish-info-item strong {
  font-size: 12px;
  color: var(--gold);
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 4px;
  font-weight: 600;
}
.dish-info-item p {
  font-size: 12px;
  color: var(--text-muted);
  margin: 0;
  line-height: 1.5;
}

/* RESPONSIVE FOR CHEF MODAL */
@media(max-width: 991px) {
  .chef-modal-container {
    height: 90vh;
    max-height: 90vh;
  }
}
@media(max-width: 768px) {
  .chef-modal-container {
    flex-direction: column;
    height: 95vh;
    max-height: 95vh;
    width: 95%;
  }
  .chef-modal-body {
    flex-direction: column;
    overflow-y: auto;
  }
  .chef-modal-left {
    width: 100%;
    height: 250px;
    border-right: none;
    border-bottom: 1px solid var(--border-light);
  }
  .chef-modal-right {
    width: 100%;
    height: auto;
    padding: 25px;
  }
  .chef-modal-scroll-content {
    overflow-y: visible;
    padding-right: 0;
  }
  .chef-modal-name {
    font-size: 1.8rem;
  }
  .dishes-grid {
    grid-template-columns: 1fr;
  }
  .chef-modal-footer {
    flex-direction: column;
    align-items: stretch;
    text-align: center;
  }
  .chef-modal-social {
    justify-content: center;
  }
  .chef-book-btn {
    justify-content: center;
  }
}
</style>

<div class="page-space"></div>

<!-- HERO -->
<section class="ch-hero">
  <div class="container">
    <div class="ch-hero-eyebrow">Restaurantly</div>
    <h1>Những <em>nghệ nhân</em><br>đứng sau mỗi món ăn</h1>
    <p class="ch-hero-sub">Đội ngũ đầu bếp của chúng tôi mang trong mình đam mê, kỹ năng và câu chuyện riêng — để biến từng bữa ăn thành một trải nghiệm đáng nhớ.</p>
  </div>
</section>

<!-- STATS STRIP -->
<?php if(!empty($all_chefs)): ?>
<div class="ch-strip">
  <div class="strip-item">
    <div class="strip-num"><?= count($all_chefs) ?>+</div>
    <div class="strip-lbl">Đầu bếp chuyên nghiệp</div>
  </div>
  <div class="strip-item">
    <div class="strip-num"><?php
      $exp = array_sum(array_filter(array_column($all_chefs,'experience')));
      echo ($exp ?: 50).'+';
    ?></div>
    <div class="strip-lbl">Năm kinh nghiệm</div>
  </div>
  <div class="strip-item">
    <div class="strip-num">10+</div>
    <div class="strip-lbl">Năm phục vụ</div>
  </div>
</div>
<?php endif; ?>

<!-- GRID -->
<div class="ch-wrap">
  <?php if(!empty($chefs)): ?>
  <div class="ch-grid">
    <?php foreach($chefs as $index => $chef):
      $img = !empty($chef['image'])
        ? 'public/assets/img/chefs/'.htmlspecialchars($chef['image'])
        : 'public/assets/img/chefs/default-chef.jpg';
      $hasSocial = !empty($chef['facebook']) || !empty($chef['instagram']) || !empty($chef['email']);
      $chef_json = htmlspecialchars(json_encode($chef, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
    ?>
    <div class="ch-card" style="cursor: pointer;" onclick='openChefModal(<?= $chef_json ?>)'>
      <div class="ch-img">
        <img src="<?= $img ?>"
             alt="<?= htmlspecialchars($chef['name']) ?>"
             onerror="this.onerror=null; this.src='public/assets/img/chefs/default-chef.jpg'">
      </div>

      <div class="ch-info">
        <div class="ch-position"><?= htmlspecialchars($chef['position'] ?? 'Đầu Bếp') ?></div>
        <div class="ch-name"><?= htmlspecialchars($chef['name']) ?></div>

        <div class="ch-exp-spec" style="margin-bottom: 0;">
            <?php if(!empty($chef['experience'])): ?>
            <div><i class="bi bi-clock-history"></i> <?= (int)$chef['experience'] ?> năm kinh nghiệm</div>
            <?php endif; ?>
        </div>
      </div>

    </div>
    <?php endforeach; ?>
  </div>

  <?php if ($total_pages > 1): ?>
  <div class="ch-pagination-wrap">
    <ul class="ch-pagination">
      <li class="<?= $page <= 1 ? 'disabled' : '' ?>">
        <a href="?page=<?= $page - 1 ?>"><i class="bi bi-chevron-left"></i></a>
      </li>
      <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <li class="<?= $i == $page ? 'active' : '' ?>">
          <a href="?page=<?= $i ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
      <li class="<?= $page >= $total_pages ? 'disabled' : '' ?>">
        <a href="?page=<?= $page + 1 ?>"><i class="bi bi-chevron-right"></i></a>
      </li>
    </ul>
  </div>
  <?php endif; ?>

  <?php else: ?>
  <div class="ch-empty">
    <i class="bi bi-people"></i>
    <p style="font-family:'Cormorant Garamond', serif;font-style:italic;font-size:1.2rem;">
      Thông tin đội ngũ đang được cập nhật...
    </p>
  </div>
  <?php endif; ?>
</div>

<!-- CHEF DETAIL MEGA MODAL -->
<div id="chefDetailModal" class="chef-modal-overlay">
  <div class="chef-modal-container">
    <button class="chef-modal-close" onclick="closeChefModal()">✕</button>
    <div class="chef-modal-body">
      <!-- Cột Trái: Ảnh chân dung nguyên người/cận cảnh sắc nét -->
      <div class="chef-modal-left">
        <img id="modalChefImg" src="" alt="Chef Portrait">
      </div>
      <!-- Cột Phải: Dành cho Chữ và Tính năng -->
      <div class="chef-modal-right">
        <div class="chef-modal-scroll-content">
          <span id="modalChefPosition" class="chef-modal-pos"></span>
          <h2 id="modalChefName" class="chef-modal-name"></h2>
          
          <div id="modalChefQuoteWrap" class="chef-modal-quote-wrap">
            <p id="modalChefQuote" class="chef-modal-quote"></p>
          </div>
          
          <p id="modalChefDesc" class="chef-modal-desc"></p>
          
          <!-- Awards & Expertise -->
          <div class="chef-modal-section" id="modalChefAwardsSection">
            <h4 class="section-title"><i class="bi bi-award-fill"></i> Giải Thưởng & Chuyên Môn</h4>
            <div id="modalChefAwards" class="awards-grid">
              <!-- Rendered via JS -->
            </div>
          </div>
          
          <!-- Signature Dishes -->
          <div class="chef-modal-section" id="modalChefDishesSection">
            <h4 class="section-title"><i class="bi bi-stars"></i> Món Ăn Đặc Trưng</h4>
            <div id="modalChefDishes" class="dishes-grid">
              <!-- Rendered via JS -->
            </div>
          </div>

          <!-- Reviews & Ratings Section -->
          <div class="chef-modal-section">
            <h4 class="section-title"><i class="bi bi-chat-left-text-fill"></i> Đánh Giá Từ Thực Khách</h4>
            
            <!-- Summary Stats -->
            <div class="reviews-summary">
              <div class="rating-big" id="modalAvgRating">0.0</div>
              <div class="rating-details">
                <div class="stars-wrap" id="modalAvgStars">
                  <!-- Rendered dynamically -->
                </div>
                <span class="reviews-count" id="modalReviewsCount">0 đánh giá</span>
              </div>
            </div>

            <!-- Reviews list -->
            <div id="modalReviewsList" class="reviews-list">
              <!-- Rendered dynamically -->
            </div>

            <!-- Submit Review Form -->
            <div class="write-review-form">
              <h5 style="font-family:'Playfair Display',serif; font-size: 1.1rem; color: #fff; margin-bottom: 12px;">Gửi đánh giá của bạn</h5>
              <form id="submitReviewForm" onsubmit="submitChefReview(event)">
                <input type="hidden" name="chef_id" id="reviewChefId">
                
                <div class="star-rating-picker mb-3">
                  <span class="rating-picker-label">Đánh giá:</span>
                  <div class="rating-stars-picker">
                    <i class="bi bi-star-fill star-pick active" data-val="1"></i>
                    <i class="bi bi-star-fill star-pick active" data-val="2"></i>
                    <i class="bi bi-star-fill star-pick active" data-val="3"></i>
                    <i class="bi bi-star-fill star-pick active" data-val="4"></i>
                    <i class="bi bi-star-fill star-pick active" data-val="5"></i>
                  </div>
                  <input type="hidden" name="rating" id="selectedRatingVal" value="5">
                </div>

                <?php if (!isset($_SESSION['user_id'])): ?>
                <div style="margin-bottom: 12px;">
                  <input type="text" name="author_name" id="reviewAuthorName" class="review-input" placeholder="Tên của bạn (Tùy chọn)">
                </div>
                <?php endif; ?>

                <div style="margin-bottom: 15px;">
                  <textarea name="comment" id="reviewComment" class="review-input" rows="3" placeholder="Viết nhận xét của bạn tại đây..." required></textarea>
                </div>

                <button type="submit" class="btn-submit-review">Gửi đánh giá</button>
              </form>
            </div>
          </div>
          
          <!-- Social & Action -->
          <div class="chef-modal-footer">
            <div id="modalChefSocial" class="chef-modal-social">
              <!-- Rendered via JS -->
            </div>
            <a id="modalBookBtn" href="#" class="chef-book-btn">
              <i class="bi bi-calendar-event"></i> Yêu cầu Đầu bếp phục vụ riêng
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- DISH DETAIL SUB-MODAL -->
<div id="dishDetailModal" class="dish-modal-overlay">
  <div class="dish-modal-container">
    <button class="dish-modal-close" onclick="closeDishModal()">✕</button>
    <div class="dish-modal-content">
      <div class="dish-modal-img-wrap">
        <img id="dishModalImg" src="" alt="Dish Image">
      </div>
      <div class="dish-modal-info text-dark">
        <h3 id="dishModalName" class="dish-modal-title"></h3>
        <span id="dishModalPrice" class="dish-modal-price text-muted"></span>
        <p id="dishModalDesc" class="dish-modal-desc text-muted"></p>
        
        <div id="dishModalAllergensSection" class="dish-info-item" style="display: none;">
          <strong><i class="bi bi-exclamation-triangle-fill"></i> Dị ứng / Thành phần lưu ý:</strong>
          <p id="dishModalAllergens"></p>
        </div>
        
        <div id="dishModalNoteSection" class="dish-info-item" style="display: none;">
          <strong><i class="bi bi-info-circle-fill"></i> Ghi chú từ Bếp trưởng:</strong>
          <p id="dishModalNote"></p>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
let currentChefDishes = [];

function openChefModal(chef) {
    // Save signature dishes for sub-modal trigger
    currentChefDishes = chef.signature_dishes_list || [];

    // 1. Ảnh chân dung
    let chefImg = chef.image ? 'public/assets/img/chefs/' + chef.image : 'public/assets/img/chefs/default-chef.jpg';
    document.getElementById('modalChefImg').src = chefImg;
    document.getElementById('modalChefImg').alt = chef.name;
    
    // 2. Chức vụ & Tên
    document.getElementById('modalChefPosition').textContent = chef.position || 'Đầu Bếp';
    document.getElementById('modalChefName').textContent = chef.name;
    
    // 3. Trích dẫn (Quote)
    let quoteWrap = document.getElementById('modalChefQuoteWrap');
    if (chef.quote && chef.quote.trim() !== '') {
        document.getElementById('modalChefQuote').textContent = `"${chef.quote}"`;
        quoteWrap.style.display = 'block';
    } else {
        quoteWrap.style.display = 'none';
    }
    
    // 4. Mô tả chi tiết
    document.getElementById('modalChefDesc').textContent = chef.description || 'Thông tin chi tiết về đầu bếp đang được cập nhật.';
    
    // 5. Giải thưởng & Chuyên môn (Awards)
    let awardsContainer = document.getElementById('modalChefAwards');
    awardsContainer.innerHTML = '';
    
    if (chef.awards && chef.awards.trim() !== '') {
        let lines = chef.awards.split('\n');
        let hasAwards = false;
        lines.forEach(line => {
            if (line.trim() === '') return;
            let parts = line.split('|');
            let title = parts[0] ? parts[0].trim() : '';
            let subtitle = parts[1] ? parts[1].trim() : '';
            let iconClass = parts[2] ? parts[2].trim() : 'award-fill';
            
            if (title !== '') {
                hasAwards = true;
                let awardItem = document.createElement('div');
                awardItem.className = 'award-item';
                awardItem.innerHTML = `
                    <div class="award-icon"><i class="bi bi-${iconClass}"></i></div>
                    <div class="award-info">
                        <span class="award-title">${title}</span>
                        ${subtitle ? `<span class="award-subtitle">${subtitle}</span>` : ''}
                    </div>
                `;
                awardsContainer.appendChild(awardItem);
            }
        });
        if (hasAwards) {
            document.getElementById('modalChefAwardsSection').style.display = 'block';
        } else {
            document.getElementById('modalChefAwardsSection').style.display = 'none';
        }
    } else {
        document.getElementById('modalChefAwardsSection').style.display = 'none';
    }
    
    // 6. Món ăn đặc trưng (Signature Dishes)
    let dishesContainer = document.getElementById('modalChefDishes');
    dishesContainer.innerHTML = '';
    let dishesSection = document.getElementById('modalChefDishesSection');
    
    if (currentChefDishes.length > 0) {
        currentChefDishes.forEach((dish, idx) => {
            let dishImg = dish.image ? 'public/assets/img/foods/' + dish.image : 'https://placehold.co/400x530/F6F2E9/4F5B3A?text=Dish';
            let formattedPrice = parseFloat(dish.price).toLocaleString('vi-VN') + ' đ';
            let dishItem = document.createElement('div');
            dishItem.className = 'dish-item';
            dishItem.style.cursor = 'pointer';
            dishItem.onclick = function() { openDishModal(idx); };
            dishItem.innerHTML = `
                <div class="dish-img">
                    <img src="${dishImg}" alt="${dish.name}" onerror="this.onerror=null; this.src='https://placehold.co/100x100/F6F2E9/4F5B3A?text=Dish'">
                </div>
                <div class="dish-info">
                    <span class="dish-name" title="${dish.name}">${dish.name}</span>
                    <span class="dish-desc" title="${dish.description || ''}">${dish.description || 'Món ăn đặc trưng tinh tế.'}</span>
                    <span class="dish-price">${formattedPrice}</span>
                </div>
            `;
            dishesContainer.appendChild(dishItem);
        });
        dishesSection.style.display = 'block';
    } else {
        dishesSection.style.display = 'none';
    }
    
    // 7. Mạng xã hội
    let socialContainer = document.getElementById('modalChefSocial');
    socialContainer.innerHTML = '';
    let hasSocial = false;
    
    if (chef.facebook && chef.facebook.trim() !== '') {
        hasSocial = true;
        socialContainer.innerHTML += `<a href="${chef.facebook}" target="_blank" rel="noopener"><i class="bi bi-facebook"></i></a>`;
    }
    if (chef.instagram && chef.instagram.trim() !== '') {
        hasSocial = true;
        socialContainer.innerHTML += `<a href="${chef.instagram}" target="_blank" rel="noopener"><i class="bi bi-instagram"></i></a>`;
    }
    if (chef.email && chef.email.trim() !== '') {
        hasSocial = true;
        socialContainer.innerHTML += `<a href="mailto:${chef.email}"><i class="bi bi-envelope"></i></a>`;
    }
    socialContainer.style.display = hasSocial ? 'flex' : 'none';
    
    // 8. Đặt liên kết
    document.getElementById('modalBookBtn').href = `booking_service.php?type=chef&chef_id=${chef.id}`;
    
    // Set Chef ID for reviews form
    document.getElementById('reviewChefId').value = chef.id;
    
    // Fetch comments and ratings
    fetchChefReviews(chef.id);
    
    // Hiển thị Overlay
    let overlay = document.getElementById('chefDetailModal');
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeChefModal() {
    let overlay = document.getElementById('chefDetailModal');
    overlay.classList.remove('active');
    document.body.style.overflow = '';
}

// Sub-modal for signature dish details
function openDishModal(idx) {
    let dish = currentChefDishes[idx];
    if (!dish) return;
    
    let dishImg = dish.image ? 'public/assets/img/foods/' + dish.image : 'https://placehold.co/400x530/F6F2E9/4F5B3A?text=Dish';
    document.getElementById('dishModalImg').src = dishImg;
    document.getElementById('dishModalName').textContent = dish.name;
    document.getElementById('dishModalPrice').textContent = parseFloat(dish.price).toLocaleString('vi-VN') + ' đ';
    document.getElementById('dishModalDesc').textContent = dish.description || 'Món ăn mang đậm phong cách ẩm thực đỉnh cao của bếp trưởng.';
    
    // Allergens
    let allergensSec = document.getElementById('dishModalAllergensSection');
    if (dish.allergens && dish.allergens.trim() !== '') {
        document.getElementById('dishModalAllergens').textContent = dish.allergens;
        allergensSec.style.display = 'block';
    } else {
        allergensSec.style.display = 'none';
    }
    
    // Chef Notes
    let noteSec = document.getElementById('dishModalNoteSection');
    if (dish.chef_note && dish.chef_note.trim() !== '') {
        document.getElementById('dishModalNote').textContent = dish.chef_note;
        noteSec.style.display = 'block';
    } else {
        noteSec.style.display = 'none';
    }
    
    let modal = document.getElementById('dishDetailModal');
    modal.classList.add('active');
}

function closeDishModal() {
    let modal = document.getElementById('dishDetailModal');
    modal.classList.remove('active');
}

// Fetch Reviews & Ratings
function fetchChefReviews(chefId) {
    fetch('ajax/get_chef_reviews.php?chef_id=' + chefId)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Update average rating
                document.getElementById('modalAvgRating').textContent = data.avg_rating > 0 ? data.avg_rating.toFixed(1) : '0.0';
                document.getElementById('modalReviewsCount').textContent = data.review_count + ' đánh giá';
                
                // Render stars for average rating
                let avgStarsContainer = document.getElementById('modalAvgStars');
                avgStarsContainer.innerHTML = '';
                let ratingVal = Math.round(data.avg_rating);
                for (let i = 1; i <= 5; i++) {
                    let star = document.createElement('i');
                    star.className = i <= ratingVal ? 'bi bi-star-fill text-warning' : 'bi bi-star';
                    avgStarsContainer.appendChild(star);
                }
                
                // Render reviews list
                let reviewsList = document.getElementById('modalReviewsList');
                reviewsList.innerHTML = '';
                
                if (data.reviews && data.reviews.length > 0) {
                    data.reviews.forEach(rev => {
                        let div = document.createElement('div');
                        div.className = 'review-card';
                        
                        // Avatar path: fallback to placeholder if none
                        let avatarSrc = rev.user_avatar ? 'public/assets/img/avatars/' + rev.user_avatar : 'https://placehold.co/50x50/F6F2E9/4F5B3A?text=' + rev.author_name.charAt(0);
                        
                        // Render rating stars
                        let starsHtml = '';
                        for (let j = 1; j <= 5; j++) {
                            starsHtml += j <= rev.rating ? '<i class="bi bi-star-fill text-warning"></i>' : '<i class="bi bi-star text-muted"></i>';
                        }
                        
                        div.innerHTML = `
                            <div class="review-header">
                                <img class="review-avatar" src="${avatarSrc}" onerror="this.onerror=null; this.src='https://placehold.co/50x50/262629/A88746?text=${rev.author_name.charAt(0)}'" />
                                <div class="review-meta">
                                    <span class="review-author">${escapeHtml(rev.author_name)}</span>
                                    <span class="review-date">${rev.created_at}</span>
                                </div>
                                <div class="review-stars">${starsHtml}</div>
                            </div>
                            <div class="review-comment">${escapeHtml(rev.comment)}</div>
                        `;
                        reviewsList.appendChild(div);
                    });
                } else {
                    reviewsList.innerHTML = '<p class="text-center text-muted py-3">Chưa có đánh giá nào cho đầu bếp này. Hãy là người đầu tiên chia sẻ cảm nhận!</p>';
                }
            }
        });
}

function escapeHtml(text) {
    if (!text) return '';
    return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function submitChefReview(event) {
    event.preventDefault();
    let form = event.target;
    let formData = new FormData(form);
    
    fetch('ajax/submit_chef_review.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert(data.message);
            form.reset();
            resetStarPicker();
            let chefId = document.getElementById('reviewChefId').value;
            fetchChefReviews(chefId);
        } else {
            alert(data.message || 'Có lỗi xảy ra.');
        }
    })
    .catch(error => {
        console.error('Error submitting review:', error);
        alert('Có lỗi xảy ra khi gửi đánh giá.');
    });
}

// Star rating picker interactivity
document.addEventListener('DOMContentLoaded', function() {
    let starPicks = document.querySelectorAll('.star-pick');
    starPicks.forEach(star => {
        star.addEventListener('click', function() {
            let val = parseInt(this.getAttribute('data-val'));
            document.getElementById('selectedRatingVal').value = val;
            updateStarPicker(val);
        });
        star.addEventListener('mouseover', function() {
            let val = parseInt(this.getAttribute('data-val'));
            updateStarPicker(val);
        });
    });
    
    let starsContainer = document.querySelector('.rating-stars-picker');
    if (starsContainer) {
        starsContainer.addEventListener('mouseleave', function() {
            let val = parseInt(document.getElementById('selectedRatingVal').value);
            updateStarPicker(val);
        });
    }
});

function updateStarPicker(val) {
    let starPicks = document.querySelectorAll('.star-pick');
    starPicks.forEach(star => {
        let starVal = parseInt(star.getAttribute('data-val'));
        if (starVal <= val) {
            star.classList.add('active');
        } else {
            star.classList.remove('active');
        }
    });
}

function resetStarPicker() {
    document.getElementById('selectedRatingVal').value = '5';
    updateStarPicker(5);
}

// Đóng modal khi bấm ra ngoài container
window.addEventListener('click', function(e) {
    let overlay = document.getElementById('chefDetailModal');
    if (e.target === overlay) {
        closeChefModal();
    }
    
    let dishOverlay = document.getElementById('dishDetailModal');
    if (e.target === dishOverlay) {
        closeDishModal();
    }
});
</script>

<?php include __DIR__ . '/views/client/layouts/footer.php'; ?>