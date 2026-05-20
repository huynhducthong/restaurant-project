<?php
require_once __DIR__ . '/config/database.php';
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
    $stmt = $db->prepare("SELECT a.*, c.name as cat_name FROM about_content a JOIN about_categories c ON a.category_id=c.id WHERE a.status=1 AND a.category_id=? ORDER BY a.is_pinned DESC, a.display_order ASC, a.created_at DESC");
    $stmt->execute([$cat_id]);
} else {
    $stmt = $db->prepare("SELECT a.*, c.name as cat_name FROM about_content a JOIN about_categories c ON a.category_id=c.id WHERE a.status=1 ORDER BY a.is_pinned DESC, a.display_order ASC, a.created_at DESC");
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
$cat_stmt = $db->prepare("SELECT id, name, slug FROM about_categories ORDER BY id ASC");
$cat_stmt->execute();
$categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

$recent_posts = array_slice($all_posts, 0, 5);

include __DIR__ . '/views/client/layouts/header.php';
?>

<style>
:root {
    --news-bg: #0d2b27;         /* Matches var(--F2) from menu.php */
    --news-card-bg: #ffffff;    /* WHITE background for frames/cards/widgets */
    --news-dark-bg: #f8fafc;    /* Light slate background for inputs/inner boxes */
    --news-gold: #e5a93b;       /* More vibrant premium gold highlights */
    --news-gold-hover: #c88e27; /* Saturated hover state for gold links */
    --news-gold-muted: rgba(229, 169, 59, 0.1);
    --news-text: #ffffff;       /* Bright pure white text for outside cards */
    --news-text-dark: #111827;  /* High contrast dark text for inside white cards */
    --news-text-muted: #e2e8f0; /* Bright light gray text for outside cards */
    --news-text-muted-dark: #4b5563; /* High contrast medium dark gray for tóm tắt/meta inside white cards */
    --news-border: rgba(229, 169, 59, 0.15);
    --news-border-light: #e2e8f0; /* Light gray border for white cards */
}

/* Base Reset and Layout */
.news-page-wrap {
    background: var(--news-bg);
    color: var(--news-text);
    min-height: 85vh;
    padding: 180px 0 60px 0; /* Clear header space */
    font-family: "Poppins", sans-serif;
}

/* Typography elements */
.font-playfair {
    font-family: 'Playfair Display', serif;
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
    border-radius: 12px;
    border: 1px solid var(--news-border-light);
    border-top: 4px solid var(--news-gold);
    padding: 22px;
    margin-bottom: 25px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.03);
}
.widget-title {
    font-family: 'Playfair Display', serif;
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
    border-bottom: 1px dashed var(--news-border-light);
    padding-bottom: 15px;
    transition: border-color 0.2s;
}
.popular-sidebar-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}
.popular-number {
    font-family: 'Playfair Display', serif;
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
    border-radius: 6px;
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
    border-top: 4px solid var(--news-gold);
    padding: 22px;
    border-radius: 12px;
    margin-bottom: 25px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.03);
}
.newsletter-sidebar-title {
    font-family: 'Playfair Display', serif;
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
    color: var(--news-text-dark);
    font-size: 13px;
    padding: 10px;
    border-radius: 6px;
    box-shadow: none;
}
.newsletter-sidebar .form-control:focus {
    border-color: var(--news-gold);
}
.newsletter-sidebar .btn-subscribe {
    background: linear-gradient(135deg, var(--news-gold), #b89248);
    color: #ffffff;
    border: none;
    width: 100%;
    font-size: 13px;
    font-weight: 700;
    padding: 10px;
    border-radius: 6px;
    transition: all 0.2s;
    box-shadow: 0 4px 15px rgba(212, 176, 106, 0.2);
}
.newsletter-sidebar .btn-subscribe:hover {
    background: linear-gradient(135deg, #edd9a3, var(--news-gold));
    box-shadow: 0 6px 20px rgba(212, 176, 106, 0.35);
    transform: translateY(-1px);
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
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid var(--news-border-light);
    border-top: 4px solid var(--news-gold);
    cursor: pointer;
    transition: transform 0.25s, box-shadow 0.25s, border-color 0.25s;
    display: flex;
    flex-direction: row;
    height: 180px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.03);
}
.news-row-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 30px rgba(224, 179, 84, 0.2);
    border-color: var(--news-gold);
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
    background: linear-gradient(135deg, var(--news-gold), #b89248);
    color: #ffffff;
    font-size: 9px;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 3px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}
.news-row-body {
    padding: 20px;
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.news-row-title {
    font-family: 'Playfair Display', serif;
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
    border-radius: 12px;
    border: 1px solid var(--news-border-light);
    border-top: 5px solid var(--news-gold);
    padding: 35px;
    margin-bottom: 25px;
    color: var(--news-text-dark);
    box-shadow: 0 8px 30px rgba(0,0,0,0.03);
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
    font-family: 'Playfair Display', serif;
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
    background: #f8fafc;
    border: 1px solid var(--news-border-light);
    color: #555555;
    border-radius: 4px;
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
    border-radius: 8px;
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
    color: var(--news-text-dark);
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
    border-radius: 6px;
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
    font-family: 'Playfair Display', serif;
    font-size: 18px;
    font-weight: 700;
    color: var(--news-text-dark);
    margin-bottom: 20px;
    text-transform: uppercase;
}
.related-card {
    background: #ffffff;
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid var(--news-border-light);
    text-decoration: none;
    transition: transform 0.2s;
    height: 100%;
    display: block;
}
.related-card:hover {
    transform: translateY(-2px);
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
    font-family: 'Playfair Display', serif;
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
    font-family: 'Playfair Display', serif;
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
    background: #f8fafc;
    border: 1px solid var(--news-border-light);
    border-radius: 8px;
    padding: 15px;
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
    background: #ffffff;
    border: 1px solid var(--news-border-light);
    border-radius: 4px;
    padding: 4px 8px;
    color: var(--news-text-dark);
    font-size: 12px;
    width: 130px;
    outline: none;
}
.vne-comment-btn {
    background: linear-gradient(135deg, var(--news-gold), #b89248);
    color: #ffffff;
    border: none;
    border-radius: 4px;
    padding: 6px 18px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 4px 15px rgba(212, 176, 106, 0.2);
}
.vne-comment-btn:hover {
    background: linear-gradient(135deg, #edd9a3, var(--news-gold));
    box-shadow: 0 6px 20px rgba(212, 176, 106, 0.35);
    transform: translateY(-1px);
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
    background: #cda45e;
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
    background: var(--news-card-bg);
    border-radius: 12px;
    width: 100%;
    max-width: 400px;
    max-height: 450px;
    display: flex;
    flex-direction: column;
    border: 1px solid var(--news-border-light);
    box-shadow: 0 15px 40px rgba(0,0,0,0.15);
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
    font-family: 'Playfair Display', serif;
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
    background: var(--news-card-bg);
    border: 1px solid var(--news-border-light);
    border-radius: 12px;
    width: 90%;
    max-width: 480px;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
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
    background: #ffffff;
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
</style>

<div class="news-page-wrap">
    <div class="container">
        
        <?php if ($article): 
            $publish_time = $article['created_at'] ? date('H:i, d/m/Y', strtotime($article['created_at'])) : '';
        ?>
            <!-- ==========================================
                 ARTICLE READING VIEW (DETAILS)
                 ========================================== -->
            <div class="news-breadcrumbs">
                <a href="index.php">Trang chủ</a>
                <span>&gt;</span>
                <a href="Aboutus.php">Tin tức</a>
                <span>&gt;</span>
                <a href="Aboutus.php?cat_id=<?= $article['category_id'] ?>"><?= htmlspecialchars($article['cat_name']) ?></a>
                <span>&gt;</span>
                <span style="color: #666; font-size: 12px; font-weight: 400;"><?= htmlspecialchars($article['title']) ?></span>
            </div>

            <div class="row g-4">
                <!-- Main Reading Column -->
                <div class="col-lg-8">
                    <div class="article-read-card">
                        <span class="article-category-label"><?= htmlspecialchars($article['cat_name']) ?></span>
                    <h1 class="article-headline font-playfair"><?= htmlspecialchars($article['title']) ?></h1>
                    
                    <div class="article-meta-bar">
                        <div class="article-meta-left">
                            <span>📅 <?= $publish_time ?></span>
                            <span>👁️ <?= $article['view_count'] ?> lượt xem</span>
                        </div>
                    </div>

                    <!-- Social Toolbar (Top) -->
                    <div class="article-action-bar">
                        <div class="article-actions-left">
                            <button class="article-action-btn <?= $article['user_liked'] ? 'liked' : '' ?>" id="vne-like-btn" onclick="articleLike(<?= $article['id'] ?>)">
                                <i class="bi <?= $article['user_liked'] ? 'bi-heart-fill' : 'bi-heart' ?>"></i> 
                                <span id="vne-like-text"><?= $article['user_liked'] ? 'Đã thích' : 'Thích' ?></span> 
                                (<span id="vne-like-count" onclick="event.stopPropagation(); showLikers(<?= $article['id'] ?>)"><?= $article['like_count'] ?></span>)
                            </button>
                            
                            <button class="article-action-btn <?= $article['user_saved'] ? 'saved' : '' ?>" id="vne-save-btn" onclick="articleSave(<?= $article['id'] ?>)">
                                <i class="bi <?= $article['user_saved'] ? 'bi-bookmark-fill' : 'bi-bookmark' ?>"></i> 
                                <span id="vne-save-text"><?= $article['user_saved'] ? 'Đã lưu' : 'Lưu' ?></span>
                            </button>
                            
                            <button class="article-action-btn" onclick="articleShare(<?= $article['id'] ?>)">
                                <i class="bi bi-share"></i> Chia sẻ
                            </button>
                        </div>
                        
                        <a href="#vne-cmt-anchor" class="article-comment-anchor">
                            <i class="bi bi-chat-left-text-fill"></i> <strong>Ý kiến (<span id="vne-header-cmt-count"><?= $article['comment_count'] ?></span>)</strong>
                        </a>
                    </div>



                    <!-- Featured Thumbnail -->
                    <?php if ($article['thumbnail']): ?>
                        <div class="article-featured-img">
                            <img src="public/assets/img/about/<?= htmlspecialchars($article['thumbnail']) ?>" alt="<?= htmlspecialchars($article['title']) ?>">
                        </div>
                        <div class="article-caption">Hình ảnh bài viết: <?= htmlspecialchars($article['title']) ?></div>
                    <?php endif; ?>

                    <!-- Body Content -->
                    <div class="article-body-content">
                        <?= safe_html_render($article['content']) ?>
                    </div>

                    <div class="article-author-tag">Restaurantly Editor</div>

                    <!-- Social Toolbar (Bottom) -->
                    <div class="article-action-bar" style="margin-top: 30px;">
                        <div class="article-actions-left">
                            <button class="article-action-btn <?= $article['user_liked'] ? 'liked' : '' ?>" id="vne-like-btn-bottom" onclick="articleLike(<?= $article['id'] ?>)">
                                <i class="bi <?= $article['user_liked'] ? 'bi-heart-fill' : 'bi-heart' ?>"></i> Thích
                            </button>
                            <button class="article-action-btn" onclick="articleShare(<?= $article['id'] ?>)">
                                <i class="bi bi-share"></i> Chia sẻ
                            </button>
                        </div>
                        
                        <a href="Aboutus.php" class="article-action-btn" style="text-decoration: none;">
                            <i class="bi bi-arrow-left"></i> Trở về danh sách
                        </a>
                    </div>

                    <!-- Pinned Related Articles -->
                    <div class="related-articles-section">
                        <h3 class="related-title font-playfair">Bài viết liên quan</h3>
                        <div class="row g-3">
                            <?php foreach ($related_posts as $rp): ?>
                                <div class="col-sm-4">
                                    <a href="Aboutus.php?id=<?= $rp['id'] ?>" class="related-card">
                                        <div class="related-img">
                                            <?php if ($rp['thumbnail']): ?>
                                                <img src="public/assets/img/about/<?= htmlspecialchars($rp['thumbnail']) ?>" alt="">
                                            <?php else: ?>
                                                <div style="background: #2a2824; width:100%; height:100%;"></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="related-body">
                                            <h4 class="related-card-title"><?= htmlspecialchars($rp['title']) ?></h4>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- VnExpress Comment System -->
                    <div class="vne-comments-section" id="vne-cmt-anchor">
                        <div class="vne-comments-header">
                            <h3 class="vne-comments-title font-playfair">Ý kiến (<span id="vne-cmt-count"><?= $article['comment_count'] ?></span>)</h3>
                            <div class="vne-comments-tabs">
                                <a href="javascript:void(0)" class="vne-comments-tab active">Mới nhất</a>
                            </div>
                        </div>

                        <!-- Top Level Comment Form -->
                        <div class="vne-comment-form">
                            <textarea id="main-comment-text" class="vne-comment-textarea" placeholder="Chia sẻ ý kiến của bạn..."></textarea>
                            <div class="vne-comment-form-footer">
                                <div class="vne-comment-user-box">
                                    <label>
                                        <input type="checkbox" id="main-comment-anon" onchange="toggleAnonInput('main-comment')">
                                        Ẩn danh
                                    </label>
                                    <input type="text" id="main-comment-author" class="vne-comment-inp-name" placeholder="Tên hiển thị" value="<?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>">
                                </div>
                                <button class="vne-comment-btn" onclick="submitComment(0)">Gửi ý kiến</button>
                            </div>
                        </div>

                        <!-- Comments List -->
                        <div class="vne-comments-list" id="comments-container">
                            <?php if (empty($article_comments)): ?>
                                <div id="no-comments-placeholder" style="text-align:center; color:#666; padding:30px; font-size:14px;">Chưa có ý kiến nào. Hãy là người đầu tiên chia sẻ!</div>
                            <?php else: 
                                // Parse comments into hierarchical structure
                                $roots = array_filter($article_comments, fn($c) => !$c['parent_id'] || $c['parent_id'] == 0);
                                $replies = array_filter($article_comments, fn($c) => $c['parent_id'] > 0);
                                
                                foreach ($roots as $c):
                                    $initial = mb_strtoupper(mb_substr($c['author_name'] ?: 'A', 0, 1));
                                    $c_time = $c['created_at'] ? date('d/m/Y H:i', strtotime($c['created_at'])) : '';
                                    $avatar_url = '';
                                    if ($c['user_id'] && !$c['is_anonymous']) {
                                        $avatar_url = '/restaurant-project/ajax/get_avatar.php?user_id=' . $c['user_id'];
                                    }
                            ?>
                                <div class="vne-comment-block" id="comment-block-<?= $c['id'] ?>">
                                    <div class="vne-comment-item">
                                        <div class="vne-comment-avatar">
                                            <?php if ($avatar_url): ?>
                                                <img src="<?= $avatar_url ?>" alt="">
                                            <?php else: ?>
                                                <?= $initial ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="vne-comment-bubble">
                                            <div class="vne-comment-meta-top">
                                                <span class="vne-comment-author"><?= htmlspecialchars($c['author_name'] ?: 'Ẩn danh') ?></span>
                                                <span style="font-size:11px; color:#555;">⏰ <?= $c_time ?></span>
                                            </div>
                                            <div class="vne-comment-text">
                                                 <?php if ($c['comment'] === 'Bình luận này đã vi phạm và bị quản trị viên xóa'): ?>
                                                     <span class="text-danger" style="font-style: italic; opacity: 0.8;"><i class="bi bi-exclamation-triangle-fill me-1"></i> Bình luận này đã vi phạm và bị quản trị viên xóa.</span>
                                                 <?php else: ?>
                                                     <?= htmlspecialchars($c['comment']) ?>
                                                 <?php endif; ?>
                                             </div>
                                            
                                            <?php if ($c['comment'] !== 'Bình luận này đã vi phạm và bị quản trị viên xóa'): ?><div class="vne-comment-actions">
                                                <span class="vne-comment-action-link" onclick="showReplyForm(<?= $c['id'] ?>, '<?= htmlspecialchars($c['author_name'] ?: 'Ẩn danh') ?>')">
                                                    <i class="bi bi-reply"></i> Trả lời
                                                </span>
                                                <span class="vne-comment-action-sep">•</span>
                                                <span class="vne-comment-action-link" onclick="commentReact(<?= $c['id'] ?>, 'like')">
                                                    <i class="bi bi-hand-thumbs-up"></i> Thích (<span id="cmt-likes-<?= $c['id'] ?>"><?= (int)$c['likes'] ?></span>)
                                                </span>
                                                <span class="vne-comment-action-sep">•</span>
                                                <span class="vne-comment-action-link" onclick="commentReact(<?= $c['id'] ?>, 'dislike')">
                                                    <i class="bi bi-hand-thumbs-down"></i> Không thích (<span id="cmt-dislikes-<?= $c['id'] ?>"><?= (int)$c['dislikes'] ?></span>)
                                                </span>
                                                <span class="vne-comment-action-sep">•</span>
                                                <span class="vne-comment-action-link text-danger" onclick="openReportModal(<?= $c['id'] ?>)">
                                                    <i class="bi bi-flag"></i> Báo cáo
                                                </span>
                                            </div><?php endif; ?>

                                            <!-- Nested replies will be loaded inside this div -->
                                            <div class="vne-replies-list" id="replies-list-<?= $c['id'] ?>">
                                                <?php 
                                                    $child_cmts = array_filter($replies, fn($r) => $r['parent_id'] == $c['id']);
                                                    foreach ($child_cmts as $cc):
                                                        $cc_initial = mb_strtoupper(mb_substr($cc['author_name'] ?: 'A', 0, 1));
                                                        $cc_time = $cc['created_at'] ? date('d/m/Y H:i', strtotime($cc['created_at'])) : '';
                                                        $cc_avatar = '';
                                                        if ($cc['user_id'] && !$cc['is_anonymous']) {
                                                            $cc_avatar = '/restaurant-project/ajax/get_avatar.php?user_id=' . $cc['user_id'];
                                                        }
                                                ?>
                                                    <div class="vne-comment-item">
                                                        <div class="vne-comment-avatar" style="width: 28px; height: 28px; font-size: 11px;">
                                                            <?php if ($cc_avatar): ?>
                                                                <img src="<?= $cc_avatar ?>" alt="">
                                                            <?php else: ?>
                                                                <?= $cc_initial ?>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="vne-comment-bubble">
                                                            <div class="vne-comment-meta-top">
                                                                <span class="vne-comment-author"><?= htmlspecialchars($cc['author_name'] ?: 'Ẩn danh') ?></span>
                                                                <span style="font-size:11px; color:#555;">⏰ <?= $cc_time ?></span>
                                                            </div>
                                                            <div class="vne-comment-text">
                                                                 <?php if ($cc['comment'] === 'Bình luận này đã vi phạm và bị quản trị viên xóa'): ?>
                                                                     <span class="text-danger" style="font-style: italic; opacity: 0.8;"><i class="bi bi-exclamation-triangle-fill me-1"></i> Bình luận này đã vi phạm và bị quản trị viên xóa.</span>
                                                                 <?php else: ?>
                                                                     <?= htmlspecialchars($cc['comment']) ?>
                                                                 <?php endif; ?>
                                                             </div>
                                                            <?php if ($cc['comment'] !== 'Bình luận này đã vi phạm và bị quản trị viên xóa'): ?><div class="vne-comment-actions" style="margin-top: 4px;">
                                                                <span class="vne-comment-action-link" onclick="showReplyForm(<?= $c['id'] ?>, '<?= htmlspecialchars($cc['author_name'] ?: 'Ẩn danh') ?>', '<?= htmlspecialchars($cc['author_name'] ?: 'Ẩn danh') ?>')">
                                                                    <i class="bi bi-reply"></i> Trả lời
                                                                </span>
                                                                <span class="vne-comment-action-sep">•</span>
                                                                <span class="vne-comment-action-link" onclick="commentReact(<?= $cc['id'] ?>, 'like')">
                                                                    <i class="bi bi-hand-thumbs-up"></i> Thích (<span id="cmt-likes-<?= $cc['id'] ?>"><?= (int)$cc['likes'] ?></span>)
                                                                </span>
                                                                <span class="vne-comment-action-sep">•</span>
                                                                <span class="vne-comment-action-link" onclick="commentReact(<?= $cc['id'] ?>, 'dislike')">
                                                                    <i class="bi bi-hand-thumbs-down"></i> Không thích (<span id="cmt-dislikes-<?= $cc['id'] ?>"><?= (int)$cc['dislikes'] ?></span>)
                                                                </span>
                                                                <span class="vne-comment-action-sep">•</span>
                                                                <span class="vne-comment-action-link text-danger" onclick="openReportModal(<?= $cc['id'] ?>)">
                                                                    <i class="bi bi-flag"></i> Báo cáo
                                                                </span>
                                                            </div><?php endif; ?>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </div>
                    </div>
                    </div>
                </div>

                <!-- Report Comment Modal (VnExpress styled) -->
                <div class="vne-report-modal-overlay" id="report-modal-overlay" onclick="closeReportModalOutside(event)">
                    <div class="vne-report-modal-box animate__animated animate__zoomIn">
                        <div class="vne-report-modal-header">
                            <h4 class="font-playfair text-dark mb-0" style="font-size: 16px;"><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i> Báo cáo bình luận vi phạm</h4>
                            <button class="vne-report-modal-close-btn" onclick="closeReportModal()">&times;</button>
                        </div>
                        <div class="vne-report-modal-body">
                            <p style="font-size: 12px; color: #888; line-height: 1.5; margin-bottom: 12px;">
                                Vui lòng điền lý do chi tiết (ví dụ: ngôn từ thô tục, quảng cáo trái phép, spam, xúc phạm người khác) để ban quản trị tiến hành xem xét và xử lý.
                            </p>
                            <input type="hidden" id="report-comment-id" value="0">
                            <textarea id="report-reason-text" class="vne-report-textarea" placeholder="Nhập lý do báo cáo của bạn tại đây... (tối thiểu 5 ký tự)"></textarea>
                        </div>
                        <div class="vne-report-modal-footer">
                            <button class="vne-comment-btn" style="padding: 6px 20px; font-size: 13px;" onclick="submitReport()">Gửi báo cáo</button>
                            <button class="article-action-btn" style="padding: 6px 15px; font-size: 13px; display: inline-block;" onclick="closeReportModal()">Hủy bỏ</button>
                        </div>
                    </div>
                </div>

        <?php else: ?>
            <!-- ==========================================
                 NEWS LIST VIEW (MAIN PORTAL)
                 ========================================== -->
            <div class="news-breadcrumbs">
                <a href="index.php">Trang chủ</a>
                <span>&gt;</span>
                <span style="color: #666;">Tin tức &amp; Sự kiện</span>
                <?php if ($cat_id > 0): ?>
                    <span>&gt;</span>
                    <?php 
                        $selected_cat = '';
                        foreach ($categories as $cat) {
                            if ($cat['id'] == $cat_id) {
                                $selected_cat = $cat['name'];
                                break;
                            }
                        }
                    ?>
                    <span style="color: #cda45e;"><?= htmlspecialchars($selected_cat) ?></span>
                <?php endif; ?>
            </div>

            <div class="row g-4">
                <!-- Main News List Column -->
                <div class="col-lg-8">
                    <div class="news-list-left-col">
                        <?php if (empty($all_posts)): ?>
                        <div style="background: var(--news-card-bg); border-radius: 12px; border: 1px solid var(--news-border-light); padding: 40px; text-align: center; color: var(--news-text-muted-dark);">
                            <i class="bi bi-journal-x" style="font-size: 3rem; color: var(--news-gold);"></i>
                            <h3 class="font-playfair mt-3 text-dark">Chưa có bài viết nào</h3>
                            <p class="mb-0">Vui lòng quay lại sau để cập nhật thông tin mới nhất.</p>
                        </div>
                    <?php else: ?>
                        <!-- Horizontal News Row List (VnExpress styled) -->
                        <div class="news-horizontal-list">
                            <?php foreach ($all_posts as $post): 
                                $excerpt = strip_tags(html_entity_decode($post['content'] ?? '', ENT_QUOTES, 'UTF-8'));
                                $post_time = $post['created_at'] ? date('d/m/Y', strtotime($post['created_at'])) : '';
                            ?>
                                <div class="news-row-card" onclick="window.location.href='Aboutus.php?id=<?= $post['id'] ?>'">
                                    <div class="news-row-img">
                                        <?php if ($post['thumbnail']): ?>
                                            <img src="public/assets/img/about/<?= htmlspecialchars($post['thumbnail']) ?>" alt="<?= htmlspecialchars($post['title']) ?>">
                                        <?php else: ?>
                                            <div class="no-img-placeholder"><i class="bi bi-image" style="font-size: 1.5rem;"></i></div>
                                        <?php endif; ?>
                                        <div class="news-row-badge"><?= htmlspecialchars($post['cat_name']) ?></div>
                                    </div>
                                    <div class="news-row-body">
                                        <div>
                                            <h3 class="news-row-title font-playfair"><?= htmlspecialchars($post['title']) ?></h3>
                                            <p class="news-row-excerpt"><?= mb_strimwidth($excerpt, 0, 180, "...") ?></p>
                                        </div>
                                        <div class="news-row-meta">
                                            <span>📅 <?= $post_time ?></span>
                                            <span>❤️ <?= $post['like_count'] ?> • 💬 <?= $post['comment_count'] ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    </div>
                </div>
        <?php endif; ?>

                <!-- ==========================================
                     SIDEBAR COLUMN (SHARED FOR BOTH VIEWS)
                     ========================================== -->
                <div class="col-lg-4">
                    
                    <!-- Popular / Most Viewed widget (VnExpress Style Numbered List) -->
                    <div class="sidebar-widget">
                        <h4 class="widget-title">Đọc nhiều nhất</h4>
                        <div class="popular-sidebar-list">
                            <?php 
                                $num = 1;
                                foreach ($popular_posts as $pop): 
                                    $pop_time = $pop['created_at'] ? date('d/m/Y', strtotime($pop['created_at'])) : '';
                            ?>
                                <a href="Aboutus.php?id=<?= $pop['id'] ?>" class="popular-sidebar-item">
                                    <div class="popular-number"><?= $num++ ?></div>
                                    <div class="popular-text">
                                        <h5 class="popular-title font-playfair"><?= htmlspecialchars($pop['title']) ?></h5>
                                        <div class="popular-meta">
                                            <span style="color:#cda45e"><?= htmlspecialchars($pop['cat_name']) ?></span> • <?= $pop_time ?>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Category Widget -->
                    <div class="sidebar-widget">
                        <h4 class="widget-title">Chuyên mục</h4>
                        <ul class="cat-sidebar-list">
                            <li class="cat-sidebar-item <?= ($cat_id == 0) ? 'active' : '' ?>">
                                <a href="Aboutus.php">
                                    <span>Tất cả tin tức</span>
                                    <span class="badge bg-dark">★</span>
                                </a>
                            </li>
                            <?php foreach ($categories as $cat): ?>
                                <li class="cat-sidebar-item <?= ($cat_id == $cat['id']) ? 'active' : '' ?>">
                                    <a href="Aboutus.php?cat_id=<?= $cat['id'] ?>">
                                        <span><?= htmlspecialchars($cat['name']) ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- Newsletter subscription widget -->
                    <div class="newsletter-sidebar">
                        <h4 class="newsletter-sidebar-title font-playfair">Đăng ký bản tin</h4>
                        <p class="newsletter-sidebar-desc">Nhận tin tức mới nhất, ưu đãi độc quyền và sự kiện đặc biệt về ẩm thực từ chúng tôi.</p>
                        <form id="newsletter-widget-form" onsubmit="submitSidebarNewsletter(event)">
                            <div class="mb-3">
                                <input type="email" id="widget-email-inp" class="form-control" placeholder="Địa chỉ email của bạn..." required>
                            </div>
                            <button type="submit" class="btn-subscribe">ĐĂNG KÝ NGAY</button>
                        </form>
                    </div>

                </div>
            </div> <!-- End row -->
    </div> <!-- End container -->
</div> <!-- End page-wrap -->

<!-- Toast Element -->
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

    fetch(BASE + '/ajax/ajax_about_comment.php', {
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
            const avatarUrl = newC.user_avatar ? ('/restaurant-project/ajax/get_avatar.php?user_id=' + newC.user_id) : '';
            
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
    fetch(BASE + '/ajax/ajax_about_like.php', {
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
    fetch(BASE + '/ajax/ajax_about_action.php', {
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
            fetch(BASE + '/ajax/ajax_about_share.php', {
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
    
    fetch(BASE + '/ajax/ajax_about_like.php?get_users=1&content_id=' + id)
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
    document.getElementById('likers-overlay').classList.remove('active');
}
function closeLikersOutside(e) {
    if (e.target === document.getElementById('likers-overlay')) closeLikers();
}

// Helper utilities
function showToast(msg, bg = '#cda45e') {
    const t = document.getElementById('news-toast');
    if (!t) return;
    t.textContent = msg;
    t.style.background = bg;
    t.style.color = bg === '#cda45e' ? '#0c0b09' : '#ffffff';
    t.style.display = 'block';
    setTimeout(() => { t.style.display = 'none'; }, 2800);
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}

// Comment reactions (likes/dislikes)
function commentReact(commentId, type) {
    fetch(BASE + '/ajax/ajax_about_comment_reaction.php', {
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
    
    fetch(BASE + '/ajax/ajax_about_comment_report.php', {
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