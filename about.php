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



<div class="news-page-wrap">
    <div class="container">
        <?php if ($article): 
            $publish_time = $article['created_at'] ? date('H:i, d/m/Y', strtotime($article['created_at'])) : '';
        ?>
            
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