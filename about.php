<?php
require_once __DIR__ . '/config/database.php';
$path_prefix = '';
if (session_status() === PHP_SESSION_NONE) session_start();
$database = new Database(); 
$db = $database->getConnection();

// Self-healing / auto-migration for comments like, dislike and reports
try {
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
$related_posts = [];

if ($article_id > 0) {
    // 1. Fetch details of this article
    $stmt = $db->prepare("SELECT a.*, c.name as cat_name FROM about_content a JOIN about_categories c ON a.category_id=c.id WHERE a.id=? AND a.status=1");
    $stmt->execute([$article_id]);
    $article = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($article) {
        

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
    $stmt = $db->prepare("SELECT a.*, c.name as cat_name FROM about_content a JOIN about_categories c ON a.category_id=c.id WHERE a.status=1 AND a.category_id=? ORDER BY a.display_order ASC, a.id DESC");
    $stmt->execute([$cat_id]);
} else {
    $stmt = $db->prepare("SELECT a.*, c.name as cat_name FROM about_content a JOIN about_categories c ON a.category_id=c.id WHERE a.status=1 ORDER BY a.display_order ASC, a.id DESC");
    $stmt->execute();
}
$all_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);



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

// Popular Articles for Sidebar (Now just latest articles)
$popular_stmt = $db->prepare("
    SELECT a.id, a.title, a.thumbnail, a.created_at, c.name as cat_name
    FROM about_content a
    JOIN about_categories c ON a.category_id=c.id
    WHERE a.status = 1
    ORDER BY a.created_at DESC
    LIMIT 5
");
$popular_stmt->execute();
$popular_posts = $popular_stmt->fetchAll(PDO::FETCH_ASSOC);

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

<link href="<?= $path_prefix ?>public/assets/client/css/home.css" rel="stylesheet">

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
                <a href="index.php">Trang chủ</a>
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
const BASE = '<?= rtrim((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on'?'https':'http').'://'.$_SERVER['HTTP_HOST'].str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])),'/') ?>';

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
        
    }
});
</script>

<?php include __DIR__ . '/views/client/layouts/footer.php'; ?>