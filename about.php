<?php
require_once __DIR__ . '/config/database.php';
$path_prefix = '';
if (session_status() === PHP_SESSION_NONE) session_start();
$database = new Database(); 
$db = $database->getConnection();

// Self-healing / auto-migration for comments like, dislike and reports
try {
    $db->exec("ALTER TABLE about_comments ADD COLUMN IF NOT EXISTS likes INT DEFAULT 0");
    $db->exec("ALTER TABLE about_comments ADD COLUMN IF NOT EXISTS dislikes INT DEFAULT 0");
    $db->exec("CREATE TABLE IF NOT EXISTS about_comment_reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        comment_id INT NOT NULL,
        user_id INT DEFAULT NULL,
        reason TEXT NOT NULL,
        user_ip VARCHAR(50) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
} catch (Exception $e) {}

$user_ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$current_user = null;

if (isset($_SESSION['user_id'])) {
    $u_stmt = $db->prepare("SELECT id, avatar, avatar_blob, full_name, username FROM users WHERE id = ?");
    $u_stmt->execute([$_SESSION['user_id']]);
    $current_user = $u_stmt->fetch(PDO::FETCH_ASSOC);
    if ($current_user) {
        $_SESSION['user_name'] = $current_user['full_name'] ?: $current_user['username'];
    }
}

function safe_html_render($html) {
    if (empty($html)) return '';
    $decoded = htmlspecialchars_decode($html);
    $allowed = '<h1><h2><h3><h4><h5><h6><p><br><b><i><strong><em><u><ul><ol><li><a><img><blockquote><span><div><table><thead><tbody><tr><th><td>';
    $cleaned = strip_tags($decoded, $allowed);
    $cleaned = preg_replace('/on[a-zA-Z]+\s*=\s*["\'][^"\']*["\']/i', '', $cleaned);
    return preg_replace('/href\s*=\s*["\']javascript:[^"\']*["\']/i', 'href="#"', $cleaned);
}

function get_category_icon($slug) {
    $slug = strtolower($slug);
    if (strpos($slug, 'wine') !== false || strpos($slug, 'ruou') !== false) return '🍷';
    if (strpos($slug, 'chef') !== false || strpos($slug, 'dau-bep') !== false) return '👨‍🍳';
    if (strpos($slug, 'fine') !== false || strpos($slug, 'am-thuc') !== false) return '🍽️';
    if (strpos($slug, 'art') !== false || strpos($slug, 'nghe-thuat') !== false) return '🎨';
    if (strpos($slug, 'menu') !== false || strpos($slug, 'mon-an') !== false) return '🍂';
    if (strpos($slug, 'event') !== false || strpos($slug, 'su-kien') !== false) return '✨';
    return '🍽️';
}

// Check if we are viewing a specific article
$article_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$article = null;

if ($article_id > 0) {
    // 1. Fetch details of this article
    $stmt = $db->prepare("SELECT a.*, c.name as cat_name FROM about_content a JOIN about_categories c ON a.category_id=c.id WHERE a.id=? AND a.status=1");
    $stmt->execute([$article_id]);
    $article = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($article) {
        // Track the view count by inserting a view action
        try {
            $db->prepare("INSERT INTO about_shares (content_id, platform, user_ip) VALUES (?, 'view', ?)")->execute([$article_id, $user_ip]);
        } catch (Exception $e) {}

        // Fetch likes, saves, views, and comments details
        $pid = $article['id'];
        $s=$db->prepare("SELECT COUNT(*) FROM about_likes WHERE content_id=?"); $s->execute([$pid]); $article['like_count']=(int)$s->fetchColumn();
        $s=$db->prepare("SELECT COUNT(*) FROM about_likes WHERE content_id=? AND user_ip=?"); $s->execute([$pid,$user_ip]); $article['user_liked']=(int)$s->fetchColumn()>0;
        $s=$db->prepare("SELECT COUNT(*) FROM about_shares WHERE content_id=? AND platform!='view'"); $s->execute([$pid]); $article['share_count']=(int)$s->fetchColumn();
        $s=$db->prepare("SELECT COUNT(*) FROM about_shares WHERE content_id=? AND platform='view'"); $s->execute([$pid]); $article['view_count']=(int)$s->fetchColumn();
        $s=$db->prepare("SELECT COUNT(*) FROM about_comments WHERE content_id=? AND status='approved'"); $s->execute([$pid]); $article['comment_count']=(int)$s->fetchColumn();
        
        $uid = $_SESSION['user_id'] ?? 0;
        $article['user_saved'] = false;
        if ($uid) {
            $save_check = $db->prepare("SELECT id FROM about_saved_posts WHERE user_id = ? AND post_id = ?");
            $save_check->execute([$uid, $pid]);
            $article['user_saved'] = !!$save_check->fetch();
        }
        
        // Fetch comments
        $s=$db->prepare("SELECT c.*, u.avatar as user_avatar, u.full_name as current_full_name 
                         FROM about_comments c 
                         LEFT JOIN users u ON c.user_id = u.id 
                         WHERE c.content_id=? AND c.status='approved' 
                         ORDER BY c.created_at DESC LIMIT 100"); 
        $s->execute([$pid]); 
        $article_comments = $s->fetchAll(PDO::FETCH_ASSOC);

        // Fetch related articles (same category, different id)
        $stmt = $db->prepare("SELECT a.*, c.name as cat_name FROM about_content a JOIN about_categories c ON a.category_id=c.id WHERE a.category_id=? AND a.id != ? AND a.status=1 ORDER BY a.created_at DESC LIMIT 3");
        $stmt->execute([$article['category_id'], $article_id]);
        $related_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // If not enough related posts, fill with others
        if (count($related_posts) < 3) {
            $needed = 3 - count($related_posts);
            $exclude_ids = array_merge([$article_id], array_column($related_posts, 'id'));
            $placeholders = implode(',', array_fill(0, count($exclude_ids), '?'));
            
            $stmt = $db->prepare("SELECT a.*, c.name as cat_name FROM about_content a JOIN about_categories c ON a.category_id=c.id WHERE a.status=1 AND a.id NOT IN ($placeholders) ORDER BY a.created_at DESC LIMIT $needed");
            $stmt->execute($exclude_ids);
            $related_posts = array_merge($related_posts, $stmt->fetchAll(PDO::FETCH_ASSOC));
        }
    }
}

// Fetch general items for the News List page (or for sidebar widgets on the Reading page)
$cat_id = isset($_GET['cat_id']) ? (int)$_GET['cat_id'] : 0;

if ($cat_id > 0) {
    $stmt = $db->prepare("SELECT a.*, c.name as cat_name FROM about_content a JOIN about_categories c ON a.category_id=c.id WHERE a.status=1 AND a.category_id=? ORDER BY a.is_pinned DESC, a.display_order ASC, a.id DESC");
    $stmt->execute([$cat_id]);
} else {
    $stmt = $db->prepare("SELECT a.*, c.name as cat_name FROM about_content a JOIN about_categories c ON a.category_id=c.id WHERE a.status=1 ORDER BY a.is_pinned DESC, a.display_order ASC, a.id DESC");
    $stmt->execute();
}
$all_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Populate stats for list posts
foreach ($all_posts as &$post) {
    $pid = $post['id'];
    $s=$db->prepare("SELECT COUNT(*) FROM about_likes WHERE content_id=?"); $s->execute([$pid]); $post['like_count']=(int)$s->fetchColumn();
    $s=$db->prepare("SELECT COUNT(*) FROM about_comments WHERE content_id=? AND status='approved'"); $s->execute([$pid]); $post['comment_count']=(int)$s->fetchColumn();
    $s=$db->prepare("SELECT COUNT(*) FROM about_shares WHERE content_id=? AND platform='view'"); $s->execute([$pid]); $post['view_count']=(int)$s->fetchColumn();
}
unset($post);

// Partition posts for editorial magazine layout
$hero_post = !empty($all_posts) ? $all_posts[0] : null;
$secondary_posts = [];
$standard_posts = [];
if (count($all_posts) > 1) {
    $secondary_posts = array_slice($all_posts, 1, 2);
}
if (count($all_posts) > 3) {
    $standard_posts = array_slice($all_posts, 3);
}

// Popular Articles for Sidebar (computed from actual views in about_shares)
$popular_stmt = $db->prepare("
    SELECT a.id, a.title, a.thumbnail, a.created_at, COUNT(s.id) as view_count, c.name as cat_name
    FROM about_content a
    JOIN about_categories c ON a.category_id=c.id
    LEFT JOIN about_shares s ON a.id = s.content_id AND s.platform = 'view'
    WHERE a.status = 1
    GROUP BY a.id
    ORDER BY view_count DESC, a.created_at DESC
    LIMIT 5
");
$popular_stmt->execute();
$popular_posts = $popular_stmt->fetchAll(PDO::FETCH_ASSOC);

// If no views recorded yet, fallback to recent posts
if (empty($popular_posts) || $popular_posts[0]['view_count'] == 0) {
    $popular_stmt = $db->prepare("
        SELECT a.id, a.title, a.thumbnail, a.created_at, 0 as view_count, c.name as cat_name
        FROM about_content a
        JOIN about_categories c ON a.category_id=c.id
        WHERE a.status = 1
        ORDER BY a.created_at DESC
        LIMIT 5
    ");
    $popular_stmt->execute();
    $popular_posts = $popular_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch active categories
$cat_stmt = $db->prepare("
    SELECT c.id, c.name, c.slug, COUNT(a.id) as post_count 
    FROM about_categories c 
    LEFT JOIN about_content a ON c.id = a.category_id AND a.status = 1 
    GROUP BY c.id 
    ORDER BY c.id ASC
");
$cat_stmt->execute();
$categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

$recent_posts = array_slice($all_posts, 0, 5);

include __DIR__ . '/views/client/layouts/header.php';
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Inter:wght@300;400;500;600;700&display=swap');

:root {
    --news-bg: #FFFFFF;              /* White background */
    --news-card-bg: #ffffff;          /* Pure White cards */
    --news-dark-bg: #fcfbfa;          /* Elegant background for inputs */
    --news-gold: #C9A66B;             /* Luxury Gold accent */
    --news-gold-hover: #b39158;       /* Hover Gold */
    --news-gold-muted: rgba(201, 166, 107, 0.08);
    --news-olive: #4F5B3A;            /* Brand Olive Green */
    --news-text: #222222;             /* Dark text for headers & copy */
    --news-text-dark: #222222;        /* High contrast text */
    --news-text-muted: #555555;       /* Muted subtext */
    --news-text-muted-dark: #666666;  /* High contrast subtext inside white cards */
    --news-border: rgba(168, 135, 70, 0.3); /* Theme Gold-Bronze outline */
    --news-border-light: rgba(201, 166, 107, 0.45); /* Theme Light Gold outline */
    --accent-burgundy: #C9A66B;       /* Map old burgundy class to gold */
}

/* Base Reset and Layout */
.news-page-wrap {
    position: relative;
    background: url('<?= $path_prefix ?>public/assets/img/about_bg_premium.jpg') center/cover no-repeat fixed !important;
    color: var(--news-text);
    min-height: 85vh;
    padding: 180px 0 60px 0; /* Clear header space */
    font-family: 'Source Sans 3', sans-serif;
    z-index: 1;
}

.news-page-wrap::before {
    content: '';
    position: absolute;
    inset: 0;
    /* Lớp phủ trung hòa làm mờ nhẹ và dịu nền, giúp thông tin rõ nét */
    background: rgba(12, 11, 9, 0.75) !important; 
    z-index: -1;
}

.news-page-wrap > .container {
    position: relative;
    z-index: 2;
}

/* Typography elements */
.font-playfair {
    font-family: 'Cormorant Garamond', serif;
}

/* Breadcrumbs */
.news-breadcrumbs {
    font-size: 13px;
    color: var(--news-text-muted);
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
}
.news-breadcrumbs a {
    color: var(--news-gold);
    text-decoration: none;
    transition: color 0.2s;
}
.news-breadcrumbs a:hover {
    color: #fff;
    text-decoration: underline;
}
.news-breadcrumbs span {
    color: var(--news-border);
}

/* Sidebar Widgets */
.sidebar-widget {
    background: var(--news-card-bg);
    border-radius: 0;
    border: 1px solid var(--news-border-light);
    border-top: 2px solid var(--news-gold);
    padding: 25px;
    margin-bottom: 25px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.02);
}
.widget-title {
    font-family: 'Source Sans 3', sans-serif;
    font-size: 16px;
    font-weight: 700;
    color: var(--news-text-dark);
    text-transform: uppercase;
    border-left: 3px solid var(--news-gold);
    padding-left: 10px;
    margin-bottom: 20px;
    letter-spacing: 0.5px;
}

/* Sidebar: Popular Numbered Widget (VnExpress Style) */
.popular-sidebar-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}
.popular-sidebar-item {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    text-decoration: none;
    border-bottom: 1px solid var(--news-border-light);
    padding-bottom: 15px;
    transition: border-color 0.2s;
}
.popular-sidebar-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}
.popular-number {
    font-family: 'Source Sans 3', sans-serif;
    font-size: 38px;
    font-weight: 700;
    color: var(--news-gold);
    line-height: 0.8;
    opacity: 0.7;
    transition: opacity 0.2s, transform 0.2s;
    width: 25px;
    text-align: center;
}
.popular-sidebar-item:hover .popular-number {
    opacity: 1;
    transform: scale(1.1);
}
.popular-text {
    flex: 1;
}
.popular-title {
    font-size: 14px;
    color: var(--news-text-dark);
    font-weight: 500;
    line-height: 1.4;
    margin: 0 0 4px 0;
    transition: color 0.2s;
}
.popular-sidebar-item:hover .popular-title {
    color: var(--news-gold);
}
.popular-meta {
    font-size: 11px;
    color: var(--news-text-muted-dark);
}

/* Sidebar Categories list */
.cat-sidebar-list {
    display: flex;
    flex-direction: column;
    gap: 6px;
    list-style: none;
    padding: 0;
    margin: 0;
}
.cat-sidebar-item a {
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: var(--news-text-muted-dark);
    text-decoration: none;
    font-size: 13px;
    padding: 8px 12px;
    border-radius: 0;
    border-bottom: 1px solid var(--news-border-light);
    transition: background 0.2s, color 0.2s;
}
.cat-sidebar-item a:hover, .cat-sidebar-item.active a {
    background: var(--news-gold-muted);
    color: var(--news-gold);
}

/* Newsletter Widget */
.newsletter-sidebar {
    background: var(--news-card-bg);
    border: 1px solid var(--news-border-light);
    border-top: 2px solid var(--news-gold);
    padding: 25px;
    border-radius: 0;
    margin-bottom: 25px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.02);
}
.newsletter-sidebar-title {
    font-family: 'Source Sans 3', sans-serif;
    font-size: 18px;
    font-weight: 700;
    color: var(--news-gold);
    margin-bottom: 10px;
}
.newsletter-sidebar-desc {
    font-size: 12px;
    color: var(--news-text-muted-dark);
    line-height: 1.5;
    margin-bottom: 15px;
}
.newsletter-sidebar .form-control {
    background: var(--news-dark-bg);
    border: 1px solid var(--news-border-light);
    border-bottom: 1px solid var(--news-gold);
    color: var(--news-text-dark);
    font-size: 13px;
    padding: 12px;
    border-radius: 0;
    box-shadow: none;
}
.newsletter-sidebar .form-control:focus {
    border-color: var(--news-gold);
}
.newsletter-sidebar .btn-subscribe {
    background: var(--accent-burgundy);
    color: #ffffff;
    border: none;
    width: 100%;
    font-size: 13px;
    font-weight: 600;
    padding: 12px;
    border-radius: 0;
    transition: all 0.4s ease;
    text-transform: uppercase;
    letter-spacing: 2px;
    box-shadow: none;
}
.newsletter-sidebar .btn-subscribe:hover {
    background: transparent;
    color: var(--accent-burgundy);
    border: 1px solid var(--accent-burgundy);
    box-shadow: none;
    transform: none;
}

/* LIST VIEW STYLES */
.news-list-left-col {
    max-width: 100%;
    width: 100%;
}
.news-horizontal-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}
.news-row-card {
    background: var(--news-card-bg);
    border-radius: 0;
    overflow: hidden;
    border: 1px solid var(--news-border-light);
    border-left: 3px solid var(--news-gold);
    cursor: pointer;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    display: flex;
    flex-direction: row;
    height: 180px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.02);
}
.news-row-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.06);
    opacity: 1;
}
.news-row-img {
    width: 260px;
    height: 100%;
    overflow: hidden;
    position: relative;
    background: var(--news-dark-bg);
    flex-shrink: 0;
}
.news-row-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s;
}
.news-row-card:hover .news-row-img img {
    transform: scale(1.05);
}
.news-row-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    background: var(--accent-burgundy);
    color: #ffffff;
    font-size: 9px;
    font-weight: 600;
    padding: 3px 8px;
    border-radius: 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: none;
}
.news-row-body {
    padding: 20px;
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.news-row-title {
    font-family: 'Source Sans 3', sans-serif;
    font-size: 18px;
    font-weight: 700;
    color: var(--news-text-dark);
    line-height: 1.4;
    margin: 0 0 8px 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    transition: color 0.2s;
}
.news-row-card:hover .news-row-title {
    color: var(--news-gold);
}
.news-row-excerpt {
    color: var(--news-text-muted-dark);
    font-size: 13px;
    line-height: 1.6;
    margin-bottom: 8px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.news-row-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 12px;
    color: var(--news-text-muted-dark);
    border-top: 1px solid var(--news-border-light);
    padding-top: 8px;
    margin-top: auto;
}
@media (max-width: 768px) {
    .news-row-card {
        flex-direction: column;
        height: auto;
    }
    .news-row-img {
        width: 100%;
        height: 200px;
    }
}

/* READ VIEW STYLES */
.article-read-card {
    background: var(--news-card-bg);
    border-radius: 0;
    border: 1px solid var(--news-border-light);
    border-top: 2px solid var(--news-gold);
    padding: 40px;
    margin-bottom: 25px;
    color: var(--news-text-dark);
    box-shadow: 0 10px 40px rgba(0,0,0,0.02);
}
.article-category-label {
    display: inline-block;
    color: var(--news-gold);
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
    margin-bottom: 10px;
    letter-spacing: 1px;
}
.article-headline {
    font-family: 'Source Sans 3', sans-serif;
    font-size: 34px;
    font-weight: 700;
    line-height: 1.35;
    color: var(--news-text-dark);
    margin-bottom: 12px;
}
.article-meta-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 12px;
    color: #666;
    border-bottom: 1px solid var(--news-border-light);
    padding-bottom: 12px;
    margin-bottom: 20px;
}
.article-meta-left {
    display: flex;
    align-items: center;
    gap: 15px;
}
.article-action-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 1px solid var(--news-border-light);
    border-bottom: 1px solid var(--news-border-light);
    padding: 10px 0;
    margin-bottom: 25px;
}
.article-actions-left {
    display: flex;
    gap: 12px;
}
.article-action-btn {
    background: transparent;
    border: 1px solid var(--news-border-light);
    color: #555555;
    border-radius: 0;
    padding: 6px 14px;
    font-size: 13px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    transition: all 0.2s;
}
.article-action-btn:hover {
    background: rgba(20, 59, 54, 0.05);
    color: var(--news-gold);
    border-color: var(--news-gold);
}
.article-action-btn.liked {
    color: #f33e58;
    border-color: #f33e5844;
    background: rgba(243, 62, 88, 0.05);
}
.article-action-btn.liked:hover {
    background: rgba(243, 62, 88, 0.1);
    color: #f33e58;
    border-color: #f33e58;
}
.article-action-btn.saved {
    color: var(--news-gold);
    border-color: var(--news-gold-muted);
    background: rgba(20, 59, 54, 0.05);
}
.article-action-btn.saved:hover {
    background: rgba(20, 59, 54, 0.1);
    color: var(--news-gold);
    border-color: var(--news-gold);
}
.article-comment-anchor {
    font-size: 13px;
    color: #555555;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 6px;
}
.article-comment-anchor:hover {
    color: var(--news-gold);
}
.article-sapo {
    font-size: 17px;
    font-weight: 600;
    line-height: 1.6;
    color: #333333;
    margin-bottom: 25px;
    font-style: italic;
    border-left: 3px solid var(--news-gold);
    padding-left: 15px;
}
.article-featured-img {
    width: 100%;
    border-radius: 0;
    overflow: hidden;
    margin-bottom: 8px;
    border: 1px solid var(--news-border-light);
}
.article-featured-img img {
    width: 100%;
    height: auto;
    display: block;
}
.article-caption {
    font-size: 12px;
    color: #666;
    text-align: center;
    margin-bottom: 30px;
    font-style: italic;
}
.article-body-content {
    font-size: 17px;
    line-height: 1.8;
    color: #ffffff;
}
.article-body-content p {
    margin-bottom: 20px;
}
.article-body-content blockquote {
    background: #f8fafc;
    border-left: 4px solid var(--news-gold);
    padding: 15px 20px;
    margin: 25px 0;
    font-style: italic;
    color: #4a5568;
    font-size: 16px;
}
.article-body-content figure {
    margin: 25px 0;
    text-align: center;
}
.article-body-content img {
    max-width: 100%;
    height: auto;
    border-radius: 0;
    border: 1px solid var(--news-border-light);
}
.article-author-tag {
    text-align: right;
    font-weight: 600;
    color: var(--news-gold);
    margin-top: 30px;
    font-size: 14px;
    font-style: italic;
}

/* Related articles section */
.related-articles-section {
    margin-top: 40px;
    border-top: 1px solid var(--news-border-light);
    padding-top: 30px;
}
.related-title {
    font-family: 'Source Sans 3', sans-serif;
    font-size: 18px;
    font-weight: 700;
    color: var(--news-text-dark);
    margin-bottom: 20px;
    text-transform: uppercase;
}
.related-card {
    background: var(--news-card-bg);
    border-radius: 0;
    overflow: hidden;
    border: 1px solid var(--news-border-light);
    text-decoration: none;
    transition: transform 0.2s, box-shadow 0.2s;
    height: 100%;
    display: block;
}
.related-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.05);
    opacity: 1;
}
.related-img {
    height: 130px;
    overflow: hidden;
}
.related-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.related-body {
    padding: 12px;
}
.related-card-title {
    font-family: 'Source Sans 3', sans-serif;
    font-size: 14px;
    color: var(--news-text-dark);
    font-weight: 600;
    line-height: 1.4;
    margin: 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.related-card:hover .related-card-title {
    color: var(--news-gold);
}

/* VnExpress Comment System */
.vne-comments-section {
    margin-top: 45px;
    border-top: 1px solid var(--news-border-light);
    padding-top: 30px;
}
.vne-comments-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 2px solid var(--news-border-light);
    padding-bottom: 12px;
    margin-bottom: 20px;
}
.vne-comments-title {
    font-family: 'Source Sans 3', sans-serif;
    font-size: 20px;
    font-weight: 700;
    color: var(--news-text-dark);
    margin: 0;
}
.vne-comments-tabs {
    display: flex;
    gap: 15px;
}
.vne-comments-tab {
    font-size: 13px;
    color: #888888;
    text-decoration: none;
    font-weight: 600;
    padding-bottom: 12px;
    position: relative;
    transition: color 0.2s;
}
.vne-comments-tab.active, .vne-comments-tab:hover {
    color: var(--news-gold);
}
.vne-comments-tab.active::after {
    content: '';
    position: absolute;
    bottom: -14px;
    left: 0;
    right: 0;
    height: 2px;
    background: var(--news-gold);
}
.vne-comment-form {
    background: var(--news-dark-bg);
    border: 1px solid var(--news-border-light);
    border-radius: 0;
    padding: 20px;
    margin-bottom: 25px;
}
.vne-comment-textarea {
    width: 100%;
    background: transparent;
    border: none;
    color: var(--news-text-dark);
    font-size: 14px;
    resize: none;
    outline: none;
    height: 70px;
    margin-bottom: 10px;
}
.vne-comment-form-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 1px solid var(--news-border-light);
    padding-top: 10px;
}
.vne-comment-user-box {
    display: flex;
    align-items: center;
    gap: 12px;
}
.vne-comment-user-box label {
    font-size: 12px;
    color: #555555;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 4px;
}
.vne-comment-inp-name {
    background: #262629;
    border: 1px solid var(--news-border-light);
    border-radius: 4px;
    padding: 4px 8px;
    color: var(--news-text-dark);
    font-size: 12px;
    width: 130px;
    outline: none;
}
.vne-comment-btn {
    background: transparent;
    color: var(--accent-burgundy);
    border: 1px solid var(--accent-burgundy);
    border-radius: 0;
    padding: 6px 18px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: none;
}
.vne-comment-btn:hover {
    background: var(--accent-burgundy);
    color: #fff;
    box-shadow: none;
    transform: none;
}
.vne-comments-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}
.vne-comment-item {
    display: flex;
    gap: 12px;
}
.vne-comment-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    color: var(--news-gold);
    font-size: 13px;
    flex-shrink: 0;
}
.vne-comment-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}
.vne-comment-bubble {
    flex: 1;
}
.vne-comment-meta-top {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 4px;
}
.vne-comment-author {
    font-weight: 600;
    color: var(--news-gold);
    font-size: 13px;
}
.vne-comment-text {
    font-size: 14px;
    color: #333333;
    line-height: 1.5;
    word-break: break-word;
    margin-bottom: 6px;
}
.vne-comment-actions {
    display: flex;
    align-items: center;
    gap: 15px;
    font-size: 12px;
    color: #666;
}
.vne-comment-action-link {
    color: inherit;
    text-decoration: none;
    cursor: pointer;
    font-weight: 600;
    transition: color 0.2s;
}
.vne-comment-action-link:hover {
    color: var(--news-gold);
}
.vne-replies-list {
    margin-left: 48px;
    margin-top: 12px;
    border-left: 2px solid var(--news-border-light);
    padding-left: 12px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

/* Toast alert styling */
#news-toast {
    position: fixed;
    bottom: 25px;
    left: 50%;
    transform: translateX(-50%);
    background: #A88746;
    color: #0c0b09;
    padding: 10px 20px;
    border-radius: 50px;
    font-weight: 700;
    font-size: 13px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    z-index: 10000;
    display: none;
    animation: fadeInUp 0.3s;
}
@keyframes fadeInUp {
    from { opacity: 0; transform: translate(-50%, 15px); }
    to { opacity: 1; transform: translate(-50%, 0); }
}

/* Modal for Likers list */
.news-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.7);
    z-index: 10000;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.news-overlay.active {
    display: flex;
}.news-modal {
    background: var(--news-bg);
    border-radius: 0;
    width: 100%;
    max-width: 400px;
    max-height: 450px;
    display: flex;
    flex-direction: column;
    border: 1px solid var(--news-border-light);
    box-shadow: none;
    overflow: hidden;
}
.news-modal-head {
    padding: 15px;
    border-bottom: 1px solid var(--news-border-light);
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.news-modal-head h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
    color: var(--news-text-dark);
    font-family: 'Source Sans 3', sans-serif;
}
.news-modal-close {
    background: none;
    border: none;
    color: #888;
    font-size: 18px;
    cursor: pointer;
}
.news-modal-close:hover {
    color: var(--news-text-dark);
}
.news-modal-body {
    flex: 1;
    overflow-y: auto;
    padding: 10px 0;
}
.liker-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 20px;
    transition: background 0.2s;
}
.liker-row:hover {
    background: rgba(0, 0, 0, 0.03);
}
.liker-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--news-gold);
    color: var(--news-dark-bg);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 12px;
}
.liker-name {
    color: var(--news-text-dark);
    font-size: 13px;
    font-weight: 500;
}

/* Inline Reply Input styling */
.vne-inline-reply-box {
    margin-top: 8px;
    background: var(--news-dark-bg);
    border: 1px solid var(--news-border-light);
    padding: 10px;
    border-radius: 6px;
}
.vne-inline-reply-textarea {
    width: 100%;
    background: transparent;
    border: none;
    color: var(--news-text-dark);
    font-size: 13px;
    resize: none;
    outline: none;
    height: 45px;
    margin-bottom: 8px;
}
.vne-inline-reply-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 1px solid var(--news-border-light);
    padding-top: 6px;
}
.vne-comment-action-sep {
    color: var(--news-border-light);
    font-size: 10px;
    user-select: none;
}
/* Report Modal Styles */
.vne-report-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(5px);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 1100;
    transition: all 0.3s;
}
.vne-report-modal-overlay.active {
    display: flex;
}
.vne-report-modal-box {
    background: var(--news-bg);
    border: 1px solid var(--news-border-light);
    border-radius: 0;
    width: 90%;
    max-width: 480px;
    overflow: hidden;
    box-shadow: none;
}
.vne-report-modal-header {
    background: var(--news-dark-bg);
    border-bottom: 1px solid var(--news-border-light);
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.vne-report-modal-close-btn {
    background: transparent;
    border: none;
    color: #888;
    font-size: 24px;
    cursor: pointer;
    line-height: 1;
    transition: color 0.2s;
}
.vne-report-modal-close-btn:hover {
    color: var(--news-text-dark);
}
.vne-report-modal-body {
    padding: 20px;
}
.vne-report-textarea {
    width: 100%;
    height: 110px;
    background: #262629;
    border: 1px solid var(--news-border-light);
    border-radius: 6px;
    color: var(--news-text-dark);
    padding: 12px;
    font-size: 13px;
    resize: none;
    outline: none;
    transition: border-color 0.2s;
}
.vne-report-textarea:focus {
    border-color: var(--news-gold);
}
.vne-report-modal-footer {
    background: var(--news-dark-bg);
    border-top: 1px solid var(--news-border-light);
    padding: 15px 20px;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

/* =======================================================
   LUXURY MAGAZINE STYLING ADDITIONS
   ======================================================= */

/* Section gaps */
.news-page-wrap .container > * {
    margin-bottom: 60px;
}

/* Luxury Hero Article */
.luxury-hero {
    position: relative;
    width: 100%;
    height: 480px;
    overflow: hidden;
    cursor: pointer;
    border: 1px solid var(--news-border-light);
    box-shadow: var(--news-gold-muted) 0px 8px 24px;
    margin-bottom: 50px !important;
}
.luxury-hero-img-wrap {
    width: 100%;
    height: 100%;
    position: relative;
}
.luxury-hero-img-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}
.hero-no-img {
    width: 100%;
    height: 100%;
    background: #2a2824;
}
.luxury-hero-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to bottom, rgba(34, 34, 34, 0.2) 0%, rgba(34, 34, 34, 0.8) 100%);
    transition: opacity 0.5s;
}
.luxury-hero:hover .luxury-hero-img-wrap img {
    transform: scale(1.04);
}
.luxury-hero-content {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 50px;
    color: #ffffff;
    z-index: 10;
}
.luxury-hero-badge {
    display: inline-block;
    background: var(--news-olive);
    color: #ffffff;
    font-size: 11px;
    letter-spacing: 2px;
    text-transform: uppercase;
    padding: 5px 12px;
    font-weight: 600;
    margin-bottom: 15px;
}
.luxury-hero-title {
    font-size: 40px;
    font-weight: 700;
    line-height: 1.25;
    margin-bottom: 15px;
    color: #ffffff;
    max-width: 900px;
}
.luxury-hero-desc {
    font-size: 15px;
    line-height: 1.6;
    color: rgba(255, 255, 255, 0.9);
    margin-bottom: 20px;
    max-width: 750px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.luxury-hero-meta {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.7);
    margin-bottom: 25px;
}
.luxury-hero-btn {
    display: inline-flex;
    align-items: center;
    color: var(--news-gold);
    text-decoration: none;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    transition: color 0.3s, transform 0.3s;
}
.luxury-hero-btn:hover {
    color: #ffffff;
    transform: translateX(5px);
}

/* Explore Topics Bar */
.explore-topics-bar {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 30px 20px;
    background: var(--news-card-bg);
    border: 1px solid var(--news-border-light);
    margin-bottom: 50px !important;
}
.explore-title {
    font-size: 18px;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: var(--news-olive);
    margin-bottom: 20px;
}
.luxury-tags {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 12px;
}
.luxury-tag {
    display: inline-block;
    padding: 8px 22px;
    background: var(--news-bg);
    color: var(--news-text);
    border: 1px solid var(--news-border-light);
    border-radius: 4px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.4s ease;
}
.luxury-tag:hover, .luxury-tag.active {
    background: var(--news-gold);
    color: #222222;
    border-color: var(--news-gold);
    box-shadow: 0 4px 10px rgba(201, 166, 107, 0.2);
}

/* Secondary Featured Articles Grid */
.secondary-grid {
    margin-bottom: 50px !important;
}
.secondary-card {
    background: var(--news-card-bg);
    border: 1px solid var(--news-border-light);
    overflow: hidden;
    cursor: pointer;
    transition: transform 0.4s ease, box-shadow 0.4s ease;
    height: 100%;
    display: flex;
    flex-direction: column;
}
.secondary-card-img-wrap {
    width: 100%;
    aspect-ratio: 16/10;
    overflow: hidden;
    position: relative;
}
.secondary-card-img-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.6s ease;
}
.secondary-card:hover .secondary-card-img-wrap img {
    transform: scale(1.05);
}
.secondary-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(79, 91, 58, 0.08);
}
.secondary-card-badge {
    position: absolute;
    top: 15px;
    left: 15px;
    background: var(--news-olive);
    color: #ffffff;
    font-size: 10px;
    letter-spacing: 1px;
    text-transform: uppercase;
    padding: 4px 10px;
    font-weight: 600;
}
.secondary-card-body {
    padding: 25px;
    display: flex;
    flex-direction: column;
    flex-grow: 1;
}
.secondary-card-title {
    font-size: 22px;
    font-weight: 700;
    line-height: 1.35;
    color: var(--news-text-dark);
    margin-bottom: 12px;
    transition: color 0.3s;
}
.secondary-card:hover .secondary-card-title {
    color: var(--news-gold);
}
.secondary-card-excerpt {
    font-size: 13.5px;
    line-height: 1.6;
    color: var(--news-text-muted-dark);
    margin-bottom: 15px;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.secondary-card-meta {
    margin-top: auto;
    display: flex;
    justify-content: space-between;
    font-size: 11px;
    color: var(--news-text-muted-dark);
    border-top: 1px solid var(--news-border-light);
    padding-top: 12px;
}

/* Redesigned Popular Sidebar list items */
.popular-thumb-wrap {
    width: 60px;
    height: 60px;
    border-radius: 4px;
    overflow: hidden;
    flex-shrink: 0;
    border: 1px solid var(--news-border-light);
}
.popular-thumb-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}
.popular-sidebar-item:hover .popular-thumb-wrap img {
    transform: scale(1.08);
}
.popular-thumb-placeholder {
    width: 100%;
    height: 100%;
    background: var(--news-bg);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--news-gold);
}
.pop-cat {
    color: var(--news-gold) !important;
    font-weight: 600;
}

/* Category Sidebar improvements */
.cat-name-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
}
.cat-icon {
    font-size: 15px;
}
.cat-label {
    font-size: 13.5px;
    font-weight: 500;
}
.cat-count {
    font-size: 11px;
    color: var(--news-text-muted-dark);
}

/* Newsletter improvements */
.newsletter-input {
    border-radius: 4px !important;
    border: 1px solid var(--news-border-light) !important;
    background: var(--news-bg) !important;
}
.newsletter-input:focus {
    border-color: var(--news-gold) !important;
    box-shadow: 0 0 0 0.2rem rgba(201, 166, 107, 0.15) !important;
}
.btn-subscribe-gold {
    background: var(--news-gold);
    color: #ffffff;
    border: none;
    width: 100%;
    font-size: 13px;
    font-weight: 600;
    padding: 12px;
    border-radius: 4px;
    transition: all 0.4s ease;
    text-transform: uppercase;
    letter-spacing: 2px;
}
.btn-subscribe-gold:hover {
    background: var(--news-olive);
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(79, 91, 58, 0.15);
}

/* Scroll reveal class values */
.reveal-fade {
    opacity: 0;
    transform: translateY(20px);
    transition: opacity 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94), transform 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}
.reveal-visible {
    opacity: 1;
    transform: translateY(0);
}

/* Detail page typography */
.article-headline {
    font-size: 42px !important;
    line-height: 1.25 !important;
    color: var(--news-text-dark) !important;
    margin-bottom: 20px !important;
}
.article-read-card {
    padding: 50px !important;
    border-color: var(--news-border-light) !important;
    box-shadow: 0 15px 35px rgba(79, 91, 58, 0.04) !important;
}
.article-sapo {
    font-family: 'Cormorant Garamond', serif;
    font-size: 20px !important;
    line-height: 1.6 !important;
    color: var(--news-olive) !important;
    border-left: 3px solid var(--news-gold) !important;
    padding-left: 20px !important;
    margin-bottom: 35px !important;
}
.article-body-content {
    font-size: 17.5px !important;
    line-height: 1.85 !important;
    color: #ffffff !important;
}
.article-body-content p {
    margin-bottom: 25px !important;
}
.article-body-content blockquote {
    background: var(--news-bg) !important;
    border-left: 4px solid var(--news-gold) !important;
    padding: 20px 30px !important;
    margin: 35px 0 !important;
    font-style: italic !important;
    color: var(--news-text-dark) !important;
    font-size: 18px !important;
    font-family: 'Cormorant Garamond', serif;
}
.article-featured-img {
    border-color: var(--news-border-light) !important;
    box-shadow: 0 8px 30px rgba(0,0,0,0.03);
}

/* Comments section improvements */
.vne-comments-section {
    border-color: var(--news-border-light) !important;
}
.vne-comments-header {
    border-color: var(--news-border-light) !important;
}
.vne-comments-tab.active::after {
    background: var(--news-gold) !important;
}
.vne-comment-form {
    border-color: var(--news-border-light) !important;
    background: var(--news-bg) !important;
    border-radius: 4px !important;
}
.vne-comment-inp-name, .vne-comment-textarea {
    border: 1px solid var(--news-border-light) !important;
    background: #ffffff !important;
    color: var(--news-text-dark) !important;
    padding: 8px 12px !important;
    border-radius: 4px !important;
}
.vne-comment-inp-name:focus, .vne-comment-textarea:focus {
    border-color: var(--news-gold) !important;
    outline: none;
}
.vne-comment-btn {
    border: 1px solid var(--news-gold) !important;
    background: var(--news-gold) !important;
    color: #ffffff !important;
    border-radius: 4px !important;
    padding: 8px 22px !important;
    font-size: 13px !important;
    font-weight: 600 !important;
    letter-spacing: 1px;
}
.vne-comment-btn:hover {
    background: var(--news-olive) !important;
    border-color: var(--news-olive) !important;
    color: #ffffff !important;
}
.vne-comment-bubble {
    background: #ffffff;
    border: 1px solid var(--news-border-light);
    padding: 15px 20px;
    border-radius: 4px;
}
.vne-replies-list {
    border-left: 2px solid var(--news-border-light) !important;
}

/* Horizontal card enhancement */
.news-row-card {
    border: 1px solid var(--news-border-light) !important;
    border-left: 3px solid var(--news-gold) !important;
    box-shadow: 0 4px 15px rgba(79, 91, 58, 0.02) !important;
    transition: transform 0.4s ease, box-shadow 0.4s ease !important;
}
.news-row-card:hover {
    transform: translateY(-4px) !important;
    box-shadow: 0 12px 30px rgba(79, 91, 58, 0.08) !important;
}
.news-row-title {
    font-size: 21px !important;
    color: var(--news-text-dark) !important;
    transition: color 0.3s;
}
.news-row-card:hover .news-row-title {
    color: var(--news-gold) !important;
}
.text-gold-btn {
    color: var(--news-gold);
    font-weight: 600;
    transition: color 0.3s;
}
.news-row-card:hover .text-gold-btn {
    color: var(--news-olive);
}

/* Adaptive Responsiveness breakpoints */
@media (max-width: 991px) {
    .luxury-hero {
        height: 380px;
        margin-bottom: 35px !important;
    }
    .luxury-hero-content {
        padding: 30px;
    }
    .luxury-hero-title {
        font-size: 30px;
    }
    .explore-topics-bar {
        margin-bottom: 35px !important;
    }
}
@media (max-width: 767px) {
    .luxury-hero {
        height: 300px;
    }
    .luxury-hero-title {
        font-size: 24px;
    }
    .luxury-hero-desc {
        font-size: 13.5px;
        -webkit-line-clamp: 2;
    }
    .explore-title {
        font-size: 16px;
    }
    .luxury-tag {
        padding: 6px 14px;
        font-size: 12px;
    }
    .secondary-grid {
        margin-bottom: 35px !important;
    }
}
@media (max-width: 768px) {
    .news-page-wrap { padding: 120px 0 40px 0 !important; }
    .article-read-card { padding: 20px !important; }
    .article-headline { font-size: 24px !important; line-height: 1.4 !important; }
    .article-meta-bar { flex-direction: column; align-items: flex-start !important; gap: 10px; }
    .article-action-bar { flex-direction: column; gap: 15px; align-items: flex-start !important; }
    .gia-timeline-list::after { left: 31px !important; }
    .gia-timeline-list .timeline-item { width: 100% !important; padding-left: 70px !important; padding-right: 20px !important; }
    .gia-timeline-list .timeline-item::after { left: 23px !important; }
    .gia-timeline-list .timeline-item::before { width: 30px !important; left: 31px !important; right: auto !important; }
    .gia-timeline-list .right-item, .gia-timeline-list .left-item { left: 0% !important; padding-left: 70px !important; padding-right: 20px !important; }
    .gia-timeline-list .timeline-content { padding: 15px 20px !important; }
    .gia-timeline-list .timeline-year { font-size: 22px !important; }
    .gia-timeline-list .timeline-content h4 { font-size: 1.1rem !important; }
    .vne-comment-bubble { padding: 10px 15px !important; }
    .vne-comment-actions { flex-wrap: wrap; gap: 8px; }
}
</style>

<div class="news-page-wrap">
    <div class="container">
        <?php if ($article): 
            $publish_time = $article['created_at'] ? date('H:i, d/m/Y', strtotime($article['created_at'])) : '';
        ?>
            <style>
                .article-headline {
                    color: #ffffff !important;
                }
                .article-meta-bar, .article-meta-bar span {
                    color: #cccccc !important;
                }
                .article-body-content, .article-body-content p, .article-body-content span, .article-body-content div {
                    color: #e5e5e5 !important;
                }
                .article-author-tag {
                    color: #cda45e !important;
                }
            </style>
            <!-- ==========================================
                 ARTICLE READING VIEW (DETAILS)
                 ========================================== -->
            <div class="news-breadcrumbs" style="max-width: 900px; margin: 0 auto 25px;">
                <a href="../index.php">Trang chủ</a>
                <span>&gt;</span>
                <a href="about.php">Về chúng tôi</a>
                <span>&gt;</span>
                <a href="about.php?cat_id=<?= $article['category_id'] ?>"><?= htmlspecialchars($article['cat_name']) ?></a>
                <span>&gt;</span>
                <span style="color: #bbb; font-size: 12px; font-weight: 400;"><?= htmlspecialchars($article['title']) ?></span>
            </div>

            <div class="row justify-content-center">
                <!-- Main Reading Column -->
                <div class="col-12 col-xl-11">
                    <div class="article-read-card reveal-fade" style="background: #1a1814; border: 1px solid rgba(205, 164, 94, 0.25); padding: 40px; border-radius: 8px; margin-bottom: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
                        <span class="article-category-label" style="background: #cda45e; color: #000; padding: 4px 10px; font-size: 11px; text-transform: uppercase; font-weight: bold; letter-spacing: 1px; border-radius: 2px; display: inline-block; margin-bottom: 15px;"><?= htmlspecialchars($article['cat_name']) ?></span>
                        <h1 class="article-headline font-playfair" style="color: #fff; font-size: 32px; font-weight: 700; margin-bottom: 15px; line-height: 1.3;"><?= htmlspecialchars($article['title']) ?></h1>
                    
                        <div class="article-meta-bar" style="display: flex; gap: 15px; color: #aaa; font-size: 13px; margin-bottom: 25px; border-bottom: 1px solid rgba(255, 255, 255, 0.08); padding-bottom: 15px;">
                            <span>📅 <?= $publish_time ?></span>
                        </div>

                        <!-- Featured Thumbnail -->
                        <?php if ($article['thumbnail']): ?>
                            <div class="article-featured-img" style="margin-bottom: 25px; border-radius: 6px; overflow: hidden; border: 1px solid rgba(205, 164, 94, 0.2);">
                                <img src="public/assets/img/about/<?= htmlspecialchars($article['thumbnail']) ?>" alt="<?= htmlspecialchars($article['title']) ?>" style="width: 100%; height: auto; display: block; object-fit: cover;">
                            </div>
                            <div class="article-caption" style="font-size: 13px; color: #888; text-align: center; margin-top: -15px; margin-bottom: 25px; font-style: italic;">Hình ảnh bài viết: <?= htmlspecialchars($article['title']) ?></div>
                        <?php endif; ?>

                        <!-- Body Content -->
                        <div class="article-body-content" style="color: #e5e5e5; font-size: 16px; line-height: 1.8; font-family: 'Poppins', sans-serif;">
                            <?= safe_html_render($article['content']) ?>
                        </div>

                        <div class="article-author-tag" style="text-align: right; font-style: italic; color: #cda45e; margin-top: 30px; font-weight: 500; font-family: 'Cormorant Garamond', serif; font-size: 18px;">— Restaurantly Editor</div>

                        <!-- Return Button -->
                        <div class="article-action-bar" style="margin-top: 40px; display: flex; justify-content: flex-end; border-top: 1px solid rgba(255, 255, 255, 0.08); padding-top: 20px;">
                            <a href="about.php" class="article-action-btn" style="text-decoration: none; background: transparent; border: 1px solid #cda45e; color: #cda45e; padding: 8px 24px; border-radius: 30px; font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; transition: all 0.3s;" onmouseover="this.style.background='#cda45e'; this.style.color='#000';" onmouseout="this.style.background='transparent'; this.style.color='#cda45e';">
                                <i class="bi bi-arrow-left me-1"></i> Trở về danh sách
                            </a>
                        </div>
                    </div>

                    <!-- Pinned Related Articles -->
                    <div class="related-articles-section reveal-fade" style="margin-top: 50px;">
                        <h3 class="related-title font-playfair" style="color: #cda45e; font-size: 22px; margin-bottom: 25px; border-bottom: 1px solid rgba(205,164,94,0.2); padding-bottom: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">Bài viết liên quan</h3>
                        <div class="row g-3">
                            <?php foreach ($related_posts as $rp): ?>
                                <div class="col-sm-4">
                                    <a href="about.php?id=<?= $rp['id'] ?>" class="related-card" style="display: block; text-decoration: none; border: 1px solid rgba(205, 164, 94, 0.15); border-radius: 8px; overflow: hidden; background: #1a1814; transition: all 0.3s;" onmouseover="this.style.borderColor='rgba(205, 164, 94, 0.6)'; this.style.transform='translateY(-3px)'; this.style.boxShadow='0 10px 20px rgba(0,0,0,0.3)';" onmouseout="this.style.borderColor='rgba(205, 164, 94, 0.15)'; this.style.transform='none'; this.style.boxShadow='none';">
                                        <div class="related-img" style="height: 140px; overflow: hidden; background: #121109; position: relative;">
                                            <?php if ($rp['thumbnail']): ?>
                                                <img src="public/assets/img/about/<?= htmlspecialchars($rp['thumbnail']) ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                            <?php else: ?>
                                                <div style="display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; color: rgba(255,255,255,0.15);"><i class="bi bi-journal-text" style="font-size: 28px;"></i></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="related-body" style="padding: 15px;">
                                            <h4 class="related-card-title" style="font-size: 14px; margin: 0; color: #fff; line-height: 1.5; font-weight: 500; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; height: 42px; transition: color 0.2s;" onmouseover="this.style.color='#cda45e'" onmouseout="this.style.color='#fff'"><?= htmlspecialchars($rp['title']) ?></h4>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- Milestone Timeline View -->
            <div class="row justify-content-center">
                <div class="col-12 col-xl-11">
                    <?php if (empty($all_posts)): ?>
                        <div class="no-posts-card text-center reveal-fade">
                            <i class="bi bi-journal-x" style="font-size: 3rem; color: var(--news-gold);"></i>
                            <h3 class="font-cormorant mt-3 text-light">Chưa có bài viết nào</h3>
                            <p class="mb-0 text-muted">Vui lòng quay lại sau để cập nhật thông tin mới nhất.</p>
                        </div>
                    <?php else: ?>
                        <style>
                        .about { 
                            background: url('public/assets/img/bg_timeline.png') no-repeat center center fixed; 
                            background-size: cover; 
                            position: relative; 
                        } 
                        .about::before { 
                            content: ''; 
                            position: absolute; 
                            inset: 0; 
                            background: rgba(12, 11, 9, 0.85); 
                            z-index: 0; 
                        } 
                        .about > .container { 
                            position: relative; 
                            z-index: 1; 
                        } 
                        /* Timeline Styles cho danh sách */
                        .gia-timeline-list { position: relative; max-width: 1200px; margin: 40px auto; padding: 20px 0; overflow: hidden; }
                        .gia-timeline-list::after { content: ''; position: absolute; width: 2px; background-color: #cda45e; top: 0; bottom: 0; left: 50%; margin-left: -1px; }
                        .gia-timeline-list .timeline-item { padding: 10px 0; position: relative; background-color: inherit; width: 50%; perspective: 1000px; }
                        .gia-timeline-list .timeline-item::after { content: ''; position: absolute; width: 16px; height: 16px; right: -8px; background-color: #0c0b09; border: 3px solid #cda45e; top: 24px; border-radius: 50%; z-index: 1; }
                        .gia-timeline-list .timeline-item::before { content: ''; position: absolute; width: 80px; height: 2px; background-color: #cda45e; top: 31px; z-index: 0; }
                        .gia-timeline-list .left-item { left: 0; padding-right: 80px; padding-left: 20px; }
                        .gia-timeline-list .left-item::before { right: 0; }
                        .gia-timeline-list .right-item { left: 50%; padding-left: 80px; padding-right: 20px; }
                        .gia-timeline-list .right-item::after { left: -8px; }
                        .gia-timeline-list .right-item::before { left: 0; }
                        
                        /* Layout content & Animation scroll */
                        .gia-timeline-list .timeline-content { padding: 25px 30px; position: relative; border-radius: 8px; border: 1px solid rgba(205, 164, 94, 0.3); box-shadow: 0 4px 15px rgba(0,0,0,0.25); transform-style: preserve-3d; will-change: transform, opacity; transition: border-color 0.3s; z-index: 2; background: inherit; }
                        .gia-timeline-list .timeline-content:hover { border-color: rgba(205, 164, 94, 0.5); }
                        .gia-timeline-list .timeline-year { color: #cda45e; font-family: 'Cormorant Garamond', serif; font-size: 28px; margin-bottom: 12px; font-weight: bold; text-align: center; }
                        .gia-timeline-list .timeline-text { color: #ccc; font-size: 15px; line-height: 1.6; text-align: center; }
                        .gia-timeline-list .timeline-content img { width: 100%; height: auto; object-fit: cover; border-radius: 6px; margin-bottom: 20px; display: block; }
                        .gia-timeline-list .timeline-content h4 { text-align: center; }
                        .gia-timeline-list .timeline-content a.btn { display: block; margin: 0 auto; width: max-content; }
                        
                        /* Hiệu ứng Nửa Sáng Nửa Tối (Half Light / Half Dark UI) */
                        .gia-timeline-list .left-item .timeline-content { background: #ffffff !important; color: #1a1814 !important; border: 1px solid rgba(201, 166, 107, 0.4) !important; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05) !important; }
                        .gia-timeline-list .left-item .timeline-content h4 { color: #1a1814 !important; }
                        .gia-timeline-list .right-item .timeline-content h4 { color: #ffffff !important; }
                        .gia-timeline-list .left-item .timeline-text { color: #333333 !important; }
                        .gia-timeline-list .left-item .timeline-year { color: #9C742D !important; }
                        .gia-timeline-list .right-item .timeline-content { background: #1a1814 !important; color: #E5E5E5 !important; border: 1px solid rgba(205, 164, 94, 0.4) !important; }
                        .gia-timeline-list .right-item .timeline-text { color: #cccccc !important; }
                        .gia-timeline-list .right-item .timeline-year { color: #cda45e !important; }
                        
                        @media screen and (max-width: 768px) {
                            .gia-timeline-list::after { left: 31px; }
                            .gia-timeline-list .timeline-item { width: 100%; padding-left: 70px; padding-right: 25px; }
                            .gia-timeline-list .timeline-item::after { left: 23px; }
                            .gia-timeline-list .timeline-item::before { width: 30px; left: 31px; right: auto; }
                            .gia-timeline-list .right-item { left: 0%; }
                            .gia-timeline-list .left-item { padding-left: 70px; padding-right: 25px; }
                        }
                        </style>
                        <div class="gia-timeline-list">
                            <?php 
                                $isLeft = true;
                                foreach ($all_posts as $post): 
                                    $excerpt = strip_tags(html_entity_decode($post['content'] ?? '', ENT_QUOTES, 'UTF-8'));
                                    $year = htmlspecialchars($post['milestone_text'] ?? date('Y', strtotime($post['created_at'])));
                                    $itemClass = $isLeft ? 'left-item' : 'right-item';
                                    $isLeft = !$isLeft;
                            ?>
                                <div class="timeline-item <?= $itemClass ?>">
                                    <div class="timeline-content">
                                        <div class="timeline-year"><?= $year ?></div>
                                        <?php if ($post['thumbnail']): ?>
                                            <img src="public/assets/img/about/<?= htmlspecialchars($post['thumbnail']) ?>" alt="">
                                        <?php endif; ?>
                                        <h4 class="font-cormorant mb-3" style="font-size: 1.3rem;"><?= htmlspecialchars($post['title']) ?></h4>
                                        <a href="about.php?id=<?= $post['id'] ?>" class="btn btn-sm rounded-pill" style="background: transparent; border: 1px solid #cda45e; color: #cda45e; padding: 8px 20px; font-size: 12px; font-weight: 600; text-transform: uppercase; transition: all 0.3s;" onmouseover="this.style.background='#cda45e'; this.style.color='#000';" onmouseout="this.style.background='transparent'; this.style.color='#cda45e';">Xem chi tiết</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div> <!-- End row -->
        <?php endif; ?>
    </div> <!-- End container -->
</div> <!-- End page-wrap -->

<div id="news-toast"></div>

<!-- Likers Modal -->
<div class="news-overlay" id="likers-overlay" onclick="closeLikersOutside(event)">
  <div class="news-modal" onclick="event.stopPropagation()">
    <div class="news-modal-head">
      <h3>Người đã thích</h3>
      <button class="news-modal-close" onclick="closeLikers()">✕</button>
    </div>
    <div class="news-modal-body" id="likers-body">
      <!-- Loaded dynamically via AJAX -->
    </div>
  </div>
</div>

<script>
const BASE = '<?= rtrim((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on'?'https':'http').'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']),'/') ?>';

// Toggle Anon state for comments
function toggleAnonInput(formPrefix) {
    const isAnon = document.getElementById(formPrefix + '-anon').checked;
    const authorInp = document.getElementById(formPrefix + '-author');
    if (authorInp) {
        if (isAnon) {
            authorInp.value = 'Người dùng ẩn danh';
            authorInp.disabled = true;
        } else {
            authorInp.value = '<?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>';
            authorInp.disabled = false;
        }
    }
}

// Post a comment or reply
function submitComment(parentId = 0) {
    let text = '';
    let name = '';
    let isAnon = 0;
    
    if (parentId === 0) {
        text = document.getElementById('main-comment-text').value.trim();
        isAnon = document.getElementById('main-comment-anon').checked ? 1 : 0;
        name = document.getElementById('main-comment-author').value.trim();
    } else {
        text = document.getElementById('inline-reply-text-' + parentId).value.trim();
        isAnon = document.getElementById('inline-reply-anon-' + parentId).checked ? 1 : 0;
        name = document.getElementById('inline-reply-author-' + parentId).value.trim();
    }

    if (!text) {
        showToast('Vui lòng nhập nội dung ý kiến!', '#f33e58');
        return;
    }
    if (!name) {
        name = 'Ẩn danh';
    }

    const body = new URLSearchParams({
        content_id: <?= $article_id ?>,
        author_name: name,
        comment: text,
        is_anonymous: isAnon,
        parent_id: parentId
    });

    fetch(BASE + '/../ajax/ajax_about_comment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString()
    })
    .then(r => r.json())
    .then(d => {
        if (d.status === 'success') {
            showToast('✅ Đã gửi ý kiến thành công!');
            
            // Build new comment HTML dynamically
            const newC = d.comment;
            const initial = (newC.author_name || 'A').charAt(0).toUpperCase();
            const avatarUrl = newC.user_avatar ? ('/restaurant-project/../ajax/get_avatar.php?user_id=' + newC.user_id) : '';
            
            let avatarHtml = `<div class="vne-comment-avatar">${initial}</div>`;
            if (newC.is_anonymous == 1) {
                avatarHtml = `<div class="vne-comment-avatar" style="background:#2a2824; color:#777;"><i class="bi bi-person-fill"></i></div>`;
            } else if (newC.user_avatar) {
                avatarHtml = `<div class="vne-comment-avatar"><img src="${avatarUrl}" alt=""></div>`;
            }
            
            const newCommentHtml = `
                <div class="vne-comment-item">
                    <div class="vne-comment-avatar" ${parentId > 0 ? 'style="width: 28px; height: 28px; font-size: 11px;"' : ''}>
                        ${avatarHtml}
                    </div>
                    <div class="vne-comment-bubble">
                        <div class="vne-comment-meta-top">
                            <span class="vne-comment-author">${newC.author_name}</span>
                            <span style="font-size:11px; color:#555;">⏰ Vừa xong</span>
                        </div>
                        <div class="vne-comment-text">${escapeHtml(newC.comment)}</div>
                        <div class="vne-comment-actions" ${parentId > 0 ? 'style="margin-top: 4px;"' : ''}>
                            <span class="vne-comment-action-link" onclick="showReplyForm(${parentId > 0 ? parentId : newC.id}, '${newC.author_name}', '${parentId > 0 ? newC.author_name : ''}')">
                                <i class="bi bi-reply"></i> Trả lời
                            </span>
                            <span class="vne-comment-action-sep">•</span>
                            <span class="vne-comment-action-link" onclick="commentReact(${newC.id}, 'like')">
                                <i class="bi bi-hand-thumbs-up"></i> Thích (<span id="cmt-likes-${newC.id}">0</span>)
                            </span>
                            <span class="vne-comment-action-sep">•</span>
                            <span class="vne-comment-action-link" onclick="commentReact(${newC.id}, 'dislike')">
                                <i class="bi bi-hand-thumbs-down"></i> Không thích (<span id="cmt-dislikes-${newC.id}">0</span>)
                            </span>
                            <span class="vne-comment-action-sep">•</span>
                            <span class="vne-comment-action-link text-danger" onclick="openReportModal(${newC.id})">
                                <i class="bi bi-flag"></i> Báo cáo
                            </span>
                        </div>
                        ${parentId === 0 ? `
                        <div class="vne-replies-list" id="replies-list-${newC.id}"></div>
                        ` : ''}
                    </div>
                </div>
            `;
            
            if (parentId === 0) {
                const list = document.getElementById('comments-container');
                const placeholder = document.getElementById('no-comments-placeholder');
                if (placeholder) placeholder.style.display = 'none';
                
                const blockHtml = `
                    <div class="vne-comment-block" id="comment-block-${newC.id}">
                        ${newCommentHtml}
                    </div>
                `;
                list.insertAdjacentHTML('afterbegin', blockHtml);
                
                document.getElementById('main-comment-text').value = '';
            } else {
                const replyList = document.getElementById('replies-list-' + parentId);
                replyList.insertAdjacentHTML('beforeend', newCommentHtml);
                
                // Remove the inline reply box after sending
                const replyBox = document.getElementById('inline-reply-box-' + parentId);
                if (replyBox) replyBox.remove();
            }
            
            // Update comments counters
            const currentCount = parseInt(document.getElementById('vne-cmt-count').textContent) + 1;
            document.getElementById('vne-cmt-count').textContent = currentCount;
            document.getElementById('vne-header-cmt-count').textContent = currentCount;
        } else {
            showToast('⚠️ ' + (d.message || 'Lỗi không xác định'), '#f33e58');
        }
    })
    .catch(err => {
        console.error(err);
        showToast('⚠️ Có lỗi xảy ra trong quá trình gửi bình luận.', '#f33e58');
    });
}

// Show inline reply form (VnExpress style)
function showReplyForm(commentId, authorName, replyToName = '') {
    // If reply form already exists, just focus it
    const existing = document.getElementById('inline-reply-box-' + commentId);
    if (existing) {
        const textEl = document.getElementById('inline-reply-text-' + commentId);
        if (replyToName && !textEl.value.includes('@' + replyToName)) {
            textEl.value = '@' + replyToName + ' ' + textEl.value;
        }
        textEl.focus();
        return;
    }
    
    // Close other open reply forms first
    document.querySelectorAll('.vne-inline-reply-box').forEach(el => el.remove());
    
    const block = document.getElementById('comment-block-' + commentId);
    const bubble = block.querySelector('.vne-comment-bubble');
    
    const replyFormHtml = `
        <div class="vne-inline-reply-box animate__animated animate__fadeIn" id="inline-reply-box-${commentId}">
            <textarea id="inline-reply-text-${commentId}" class="vne-inline-reply-textarea" placeholder="Phản hồi ý kiến của ${authorName}..."></textarea>
            <div class="vne-inline-reply-footer">
                <div class="vne-comment-user-box">
                    <label>
                        <input type="checkbox" id="inline-reply-anon-${commentId}" onchange="toggleAnonInput('inline-reply-${commentId}')">
                        Ẩn danh
                    </label>
                    <input type="text" id="inline-reply-author-${commentId}" class="vne-comment-inp-name" style="width: 105px;" placeholder="Tên" value="<?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>">
                </div>
                <div>
                    <button class="vne-comment-btn" style="padding: 4px 12px; font-size: 11px;" onclick="submitComment(${commentId})">Gửi</button>
                    <button class="article-action-btn" style="display:inline-block; padding: 4px 10px; font-size: 11px;" onclick="document.getElementById('inline-reply-box-${commentId}').remove()">Hủy</button>
                </div>
            </div>
        </div>
    `;
    
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = replyFormHtml;
    bubble.insertBefore(tempDiv.firstElementChild, bubble.querySelector('.vne-replies-list'));
    
    const textarea = document.getElementById('inline-reply-text-' + commentId);
    if (replyToName) {
        textarea.value = '@' + replyToName + ' ';
    }
    textarea.focus();
}

// Like article action
function articleLike(id) {
    fetch(BASE + '/../ajax/ajax_about_like.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'content_id=' + id
    })
    .then(r => r.json())
    .then(d => {
        if (d.status === 'success') {
            const isLiked = (d.action === 'liked');
            
            // Toggle like status class & text on all like buttons
            const likeBtns = [document.getElementById('vne-like-btn'), document.getElementById('vne-like-btn-bottom')];
            likeBtns.forEach(btn => {
                if (!btn) return;
                btn.classList.toggle('liked', isLiked);
                
                const icon = btn.querySelector('i');
                if (icon) {
                    icon.className = isLiked ? 'bi bi-heart-fill' : 'bi bi-heart';
                }
            });
            
            const likeTxt = document.getElementById('vne-like-text');
            if (likeTxt) likeTxt.textContent = isLiked ? 'Đã thích' : 'Thích';
            
            const likeCountEl = document.getElementById('vne-like-count');
            if (likeCountEl) likeCountEl.textContent = d.count;
            
            showToast(isLiked ? '❤️ Đã thích bài viết' : '💔 Đã bỏ thích bài viết');
        }
    });
}

// Save/Bookmark article action
function articleSave(id) {
    fetch(BASE + '/../ajax/ajax_about_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'content_id=' + id + '&action=save'
    })
    .then(r => r.json())
    .then(d => {
        if (d.status === 'success') {
            const isSaved = d.saved;
            const btn = document.getElementById('vne-save-btn');
            if (btn) {
                btn.classList.toggle('saved', isSaved);
                const icon = btn.querySelector('i');
                if (icon) {
                    icon.className = isSaved ? 'bi bi-bookmark-fill' : 'bi bi-bookmark';
                }
                const saveTxt = document.getElementById('vne-save-text');
                if (saveTxt) saveTxt.textContent = isSaved ? 'Đã lưu' : 'Lưu';
            }
            showToast(isSaved ? '🔖 Đã lưu bài viết thành công' : 'Removed from bookmarks');
        }
    });
}

// Share article (copies URL to clipboard and registers view counts)
function articleShare(id) {
    const url = window.location.href.split('?')[0] + '?id=' + id;
    navigator.clipboard.writeText(url)
        .then(() => {
            showToast('🔗 Đã sao chép liên kết vào khay nhớ tạm!');
            
            // Record a share action
            fetch(BASE + '/../ajax/ajax_about_share.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'content_id=' + id + '&platform=link'
            });
        })
        .catch(err => {
            console.error(err);
            showToast('⚠️ Không thể sao chép liên kết.', '#f33e58');
        });
}

// Newsletter subscription in widget
function submitSidebarNewsletter(e) {
    e.preventDefault();
    const email = document.getElementById('widget-email-inp').value.trim();
    if (!email) return;
    
    fetch(BASE + '/newsletter_subscribe.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'email=' + encodeURIComponent(email)
    })
    .then(r => r.text())
    .then(txt => {
        if (txt.includes('success') || txt.includes('đăng ký thành công') || txt.includes('thành công') || txt.includes('success')) {
            showToast('✅ Đăng ký nhận bản tin thành công!');
            document.getElementById('widget-email-inp').value = '';
        } else {
            showToast('✅ Đăng ký nhận tin thành công! Cảm ơn bạn.');
            document.getElementById('widget-email-inp').value = '';
        }
    })
    .catch(() => {
        showToast('✅ Đăng ký thành công!');
        document.getElementById('widget-email-inp').value = '';
    });
}

// Show modal of users who liked the article
function showLikers(id) {
    const overlay = document.getElementById('likers-overlay');
    const body = document.getElementById('likers-body');
    if (!overlay || !body) return;
    
    body.innerHTML = '<div style="padding:30px; text-align:center; color:#666; font-size:13px;">Đang tải danh sách...</div>';
    overlay.classList.add('active');
    
    fetch(BASE + '/../ajax/ajax_about_like.php?get_users=1&content_id=' + id)
    .then(r => r.json())
    .then(users => {
        if (!users || users.length === 0) {
            body.innerHTML = '<div style="padding:30px; text-align:center; color:#666; font-size:13px;">Chưa có ai thích bài viết này.</div>';
            return;
        }
        body.innerHTML = users.map(u => `
            <div class="liker-row">
                <div class="liker-avatar">${(u.full_name || 'U').charAt(0).toUpperCase()}</div>
                <div class="liker-name">${escapeHtml(u.full_name)}</div>
            </div>
        `).join('');
    })
    .catch(() => {
        body.innerHTML = '<div style="padding:30px; text-align:center; color:#f33e58; font-size:13px;">Lỗi khi tải dữ liệu.</div>';
    });
}

    function closeLikers() {
      document.getElementById('likers-overlay').style.display='none';
    }
    function closeLikersOutside(e) {
      if(e.target.id === 'likers-overlay') closeLikers();
    }
    
    // Thêm Animation xoay 360 độ đồng bộ chính xác với con lăn chuột
    document.addEventListener("DOMContentLoaded", function() {
        const timelineItems = document.querySelectorAll('.timeline-item');
        
        function updateTimelineAnimation() {
            const viewportHeight = window.innerHeight;
            
            timelineItems.forEach(item => {
                const content = item.querySelector('.timeline-content');
                if (!content) return;
                
                const rect = item.getBoundingClientRect();
                const itemCenter = rect.top + rect.height / 2;
                
                // Vùng hiệu ứng: bắt đầu từ dưới cùng màn hình (viewportHeight + 150) tới vị trí 55% màn hình
                const startAnim = viewportHeight + 150; 
                const endAnim = viewportHeight * 0.55;  
                
                // Tính tiến độ từ 0 đến 1
                let progress = (startAnim - itemCenter) / (startAnim - endAnim);
                progress = Math.max(0, Math.min(1, progress));
                
                // Khi thẻ ở dưới cùng (progress = 0) -> xoay -180 độ (lật ngược mặt sau)
                // Khi thẻ lướt vào tầm nhìn (progress = 1) -> xoay 0 độ (trạng thái đọc bình thường)
                // 180 độ chính là một lần lật thẻ (1 flip), tốc độ sẽ chậm và sang trọng hơn nhiều so với 360!
                const rotationY = (1 - progress) * -180;
                
                // Hiệu ứng mờ dần
                const opacity = Math.max(0.1, progress);
                
                content.style.transform = `rotateY(${rotationY}deg)`;
                content.style.opacity = opacity;
            });
        }
        
        window.addEventListener('scroll', () => {
            requestAnimationFrame(updateTimelineAnimation);
        });
        
        // Khởi tạo lần đầu
        updateTimelineAnimation();
    });

// Helper utilities
function showToast(msg, bg = '#A88746') {
    const t = document.getElementById('news-toast');
    if (!t) return;
    t.textContent = msg;
    t.style.background = bg;
    t.style.color = bg === '#A88746' ? '#0c0b09' : '#ffffff';
    t.style.display = 'block';
    setTimeout(() => { t.style.display = 'none'; }, 2800);
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}

// Comment reactions (likes/dislikes)
function commentReact(commentId, type) {
    fetch(BASE + '/../ajax/ajax_about_comment_reaction.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'comment_id=' + commentId + '&type=' + type
    })
    .then(r => r.json())
    .then(d => {
        if (d.status === 'success') {
            const likesEl = document.getElementById('cmt-likes-' + commentId);
            const dislikesEl = document.getElementById('cmt-dislikes-' + commentId);
            if (likesEl) likesEl.textContent = d.likes;
            if (dislikesEl) dislikesEl.textContent = d.dislikes;
            
            let emoji = '✨';
            if (d.message.includes('Đã thích bình luận')) emoji = '👍';
            else if (d.message.includes('Đã không thích bình luận')) emoji = '👎';
            else if (d.message.includes('hủy')) emoji = '↩️';
            else if (d.message.includes('đổi thành thích')) emoji = '🔄 👍';
            else if (d.message.includes('đổi thành không thích')) emoji = '🔄 👎';
            
            showToast(emoji + ' ' + (d.message || 'Thao tác thành công!'));
        } else {
            showToast('⚠️ ' + (d.message || 'Có lỗi xảy ra'), '#f33e58');
        }
    })
    .catch(() => {
        showToast('⚠️ Lỗi kết nối máy chủ.', '#f33e58');
    });
}

// Comment reporting
function openReportModal(commentId) {
    const overlay = document.getElementById('report-modal-overlay');
    const input = document.getElementById('report-comment-id');
    const textarea = document.getElementById('report-reason-text');
    if (!overlay || !input || !textarea) return;
    
    input.value = commentId;
    textarea.value = '';
    overlay.classList.add('active');
    setTimeout(() => { textarea.focus(); }, 150);
}

function closeReportModal() {
    const overlay = document.getElementById('report-modal-overlay');
    if (overlay) overlay.classList.remove('active');
}

function closeReportModalOutside(e) {
    if (e.target === document.getElementById('report-modal-overlay')) closeReportModal();
}

function submitReport() {
    const commentId = document.getElementById('report-comment-id').value;
    const reason = document.getElementById('report-reason-text').value.trim();
    
    if (reason.length < 5) {
        showToast('⚠️ Lý do quá ngắn (tối thiểu 5 ký tự).', '#f33e58');
        return;
    }
    
    fetch(BASE + '/../ajax/ajax_about_comment_report.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'comment_id=' + commentId + '&reason=' + encodeURIComponent(reason)
    })
    .then(r => r.json())
    .then(d => {
        if (d.status === 'success') {
            showToast('✅ Báo cáo bình luận thành công!');
            closeReportModal();
        } else {
            showToast('⚠️ ' + (d.message || 'Lỗi gửi báo cáo'), '#f33e58');
        }
    })
    .catch(() => {
        showToast('⚠️ Lỗi kết nối máy chủ khi báo cáo.', '#f33e58');
    });
}

// Intersection Observer for scroll reveal animations
document.addEventListener('DOMContentLoaded', () => {
    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.1
    };

    const revealObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('reveal-visible');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    document.querySelectorAll('.reveal-fade').forEach(el => {
        revealObserver.observe(el);
    });
});

// Close modal on Escape key press
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        const overlay = document.getElementById('likers-overlay');
        if (overlay && overlay.classList.contains('active')) {
            closeLikers();
        }
        const reportOverlay = document.getElementById('report-modal-overlay');
        if (reportOverlay && reportOverlay.classList.contains('active')) {
            closeReportModal();
        }
    }
});
</script>

<?php include __DIR__ . '/views/client/layouts/footer.php'; ?>