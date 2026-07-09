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
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500&family=Source Sans 3:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Source+Sans+3:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
<style>
:root {
  --bg-color: #FFFFFF;
  --card-bg: #F9F9F9;
  --text-main: #1A1A1D;
  --text-muted: #666666;
  --accent-burgundy: #A88746;
  --border-light: rgba(0, 0, 0, 0.1);
  --ease: cubic-bezier(.4,0,.2,1);
  --gold: #A88746;
  --olive: #8B6D36;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body { background: var(--bg-color); color: var(--text-main); font-family: 'Source Sans 3', sans-serif; }

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
@media(max-width: 768px){
  .ch-strip { gap: 30px; padding: 30px 20px; }
  .strip-num { font-size: 2rem; }
  .ch-hero { padding: 120px 0 60px; }
  .ch-wrap { padding: 60px 20px; }
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
  font-family: 'Source Sans 3', sans-serif;
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
  background: var(--bg-color);
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
  background: #f8f9fa;
  border: 1px solid #ddd;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  cursor: pointer;
  z-index: 10;
  transition: 0.3s;
  color: #333;
}
.chef-modal-close:hover {
  background: #A67B27;
  color: #fff;
  border-color: #A67B27;
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
  background: #ffffff;
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
  background: #f1f1f1;
}
.chef-modal-scroll-content::-webkit-scrollbar-thumb {
  background: #A67B27;
  border-radius: 3px;
}
.chef-modal-pos {
  font-size: 11px;
  letter-spacing: 3px;
  text-transform: uppercase;
  color: #A67B27;
  font-weight: 600;
  display: block;
  margin-bottom: 8px;
}
.chef-modal-name {
  font-family: 'Cormorant Garamond', serif;
  font-size: 2.2rem;
  font-weight: 700;
  color: #A67B27;
  margin-bottom: 20px;
  line-height: 1.2;
}
.chef-modal-quote-wrap {
  position: relative;
  border-left: 3px solid #A67B27;
  padding-left: 20px;
  margin-bottom: 25px;
}
.chef-modal-quote {
  font-family: 'Cormorant Garamond', serif;
  font-style: italic;
  font-size: 1.15rem;
  color: #333333;
  line-height: 1.6;
}
.chef-modal-desc {
  font-size: 14px;
  color: #555555;
  line-height: 1.8;
  margin-bottom: 30px;
}
.chef-modal-section {
  margin-bottom: 30px;
  border-top: 1px dashed var(--border-light);
  padding-top: 25px;
}
.chef-modal-section .section-title {
  font-family: 'Cormorant Garamond', serif;
  font-size: 1.25rem;
  color: #222222;
  margin-bottom: 15px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 10px;
}
.chef-modal-section .section-title i {
  color: #A67B27;
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
  background: #f8f9fa;
  padding: 12px 15px;
  border: 1px solid #e0e0e0;
}
.award-icon {
  font-size: 22px;
  color: #A67B27;
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
  color: #222222;
}
.award-subtitle {
  font-size: 11px;
  color: #666666;
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
  border: 1px solid #e0e0e0;
  background: #f8f9fa;
  padding: 10px;
  align-items: center;
  transition: border-color 0.2s;
}
.dish-item:hover {
  border-color: #A67B27;
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
  color: #222222;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.dish-desc {
  font-size: 11px;
  color: #666666;
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
  font-family: 'Cormorant Garamond', serif;
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



.ch-hero h1 { color: #fff !important; text-shadow: 2px 2px 10px rgba(0,0,0,0.5); }
.ch-hero h1 em { color: var(--gold) !important; }
.ch-hero-sub { color: #eee !important; }
.ch-hero > * { z-index: 2; position: relative; }
.ch-hero > * {
  position: relative;
  z-index: 1;
}
.ch-hero::before {
  content: '';
  position: absolute;
  top: 0; left: 0; width: 100%; height: 100%;
  background: url('public/assets/img/about-bg.jpg') center/cover no-repeat;
  opacity: 0.8;
  z-index: 0;
}
.ch-hero::after {
  content: '';
  position: absolute;
  top: 0; left: 0; width: 100%; height: 100%;
  background: rgba(0, 0, 0, 0.65); /* Dark overlay to make white text pop */
  z-index: 1;
}
.ch-hero h1 { color: #fff !important; text-shadow: 2px 2px 10px rgba(0,0,0,0.5); }
.ch-hero h1 em { color: var(--gold) !important; }
.ch-hero-sub { color: #eee !important; }
.ch-hero > * { z-index: 2; position: relative; }
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
    <div class="ch-card" style="cursor: pointer;" onclick="window.location.href='chef_detail.php?id=<?= $chef['id'] ?>'">
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

<?php include __DIR__ . '/views/client/layouts/footer.php'; ?>