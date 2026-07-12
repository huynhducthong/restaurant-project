<?php
require_once 'config/database.php';
$db = (new Database())->getConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $db->prepare("SELECT * FROM chefs WHERE id = ? AND is_active = 1");
$stmt->execute([$id]);
$chef = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$chef) {
    header("Location: chefs.php");
    exit;
}

$img = !empty($chef['image']) ? 'public/assets/img/chefs/' . htmlspecialchars($chef['image']) : 'public/assets/img/chefs/default-chef.jpg';

// Fetch Signature Dishes
$sig_dishes = [];
if (!empty($chef['signature_dishes'])) {
    $dish_ids = explode(',', $chef['signature_dishes']);
    $in = str_repeat('?,', count($dish_ids) - 1) . '?';
    $stmt_d = $db->prepare("SELECT * FROM foods WHERE id IN ($in) AND status = 1 LIMIT 4");
    $stmt_d->execute($dish_ids);
    $sig_dishes = $stmt_d->fetchAll(PDO::FETCH_ASSOC);
}

// Parse Awards
$cert_stmt = $db->prepare("SELECT * FROM chef_certificates WHERE chef_id = ? ORDER BY issue_date DESC");
$cert_stmt->execute([$id]);
$awards = $cert_stmt->fetchAll(PDO::FETCH_ASSOC);
$gallery_stmt = $db->prepare("SELECT * FROM chef_gallery WHERE chef_id = ? ORDER BY sort_order ASC, id ASC LIMIT 4");
$gallery_stmt->execute([$id]);
$gallery_images = $gallery_stmt->fetchAll(PDO::FETCH_ASSOC);



$page_title = htmlspecialchars($chef['name']) . " - Đội bếp";
include __DIR__ . '/views/client/layouts/header.php';
?>


<!-- HERO SECTION -->
<div class="cd-hero" style="--cd-bg-img: url('<?= $img ?>');">
    <div class="cd-hero-bg" style="background-image: var(--cd-bg-img);"></div>
    <div class="cd-hero-overlay"></div>
    <div class="cd-hero-content">
        <span class="cd-position"><?= htmlspecialchars($chef['position'] ?? 'Đầu Bếp') ?></span>
        <h1 class="cd-name"><?= htmlspecialchars($chef['name']) ?></h1>
        <?php if (!empty($chef['quote'])): ?>
            <p class="cd-quote"><?= htmlspecialchars($chef['quote']) ?></p>
        <?php endif; ?>
    </div>
</div>

<!-- BIOGRAPHY -->
<?php if (!empty($chef['description'])): ?>
<div class="cd-section">
    <div class="container">
        <div class="cd-bio">
            <?= nl2br(htmlspecialchars($chef['description'])) ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- SIGNATURE TECHNIQUE -->
<?php if (!empty($chef['signature_technique']) || !empty($chef['signature_technique_process'])): ?>
<div class="cd-section st-story-section">
    <div class="container">
        
        <!-- Title & Quote -->
        <div class="st-header text-center mb-5">
            <h2 class="st-title">TUYỆT KỸ CHẾ BIẾN</h2>
            <div class="st-divider"></div>
        </div>

        <div class="st-story-body">




            <!-- Timeline -->
            <?php if (!empty($chef['signature_technique_process'])): ?>
            <div class="st-timeline-wrap mb-5">
                <h4 class="st-timeline-title text-center mb-4">Quy trình thực hiện</h4>
                <div class="st-timeline">
                    <?php
                    // Auto parse numbered lists into steps
                    $process_text = $chef['signature_technique_process'];
                    // Split by numbers like 1., 2., 3. or 1), 2)
                    $steps = preg_split('/^\s*\d+[\.\)]\s*/m', $process_text, -1, PREG_SPLIT_NO_EMPTY);
                    if (empty($steps) || count($steps) == 1) {
                        // If parsing fails (no numbers), just split by newlines
                        $steps = explode("\n", $process_text);
                    }
                    
                    foreach ($steps as $index => $step) {
                        $step = trim($step);
                        if ($step) {
                            $side = ($index % 2 == 0) ? 'left' : 'right';
                            echo '
                            <div class="st-timeline-item '.$side.'">
                                <div class="st-timeline-dot"></div>
                                <div class="st-timeline-content">
                                    <span class="st-step-num">BƯỚC '.($index + 1).'</span>
                                    <p>' . nl2br(htmlspecialchars($step)) . '</p>
                                </div>
                            </div>';
                        }
                    }
                    ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Final Result -->
            <?php if (!empty($chef['signature_technique_final_result'])): ?>
            <div class="st-final-result text-center">
                <div class="st-final-icon mb-3"><i class="bi bi-gem"></i></div>
                <h4 class="st-final-title">Thành Quả</h4>
                <p class="st-final-text"><?= nl2br(htmlspecialchars($chef['signature_technique_final_result'])) ?></p>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>
<?php endif; ?>

<!-- AWARDS -->
<?php if (!empty($awards)): ?>
<div class="cd-section">
    <div class="container">
        <div class="text-center mb-5">
            <h2 style="font-family: 'Cormorant Garamond', serif; color: #111; font-size: 2.5rem;">BẢNG VÀNG DANH DỰ</h2>
            <div style="width: 50px; height: 2px; background: #cda45e; margin: 20px auto;"></div>
        </div>
        <div class="cd-hall-grid">
            <?php foreach ($awards as $award): ?>
            <div class="cd-hall-plaque">
                <div class="plaque-bg-icon">
                    <i class="bi bi-patch-check"></i>
                </div>
                <div class="plaque-content">
                    <?php if (!empty($award['issue_date'])): ?>
                        <div class="plaque-year"><?= htmlspecialchars(date('Y', strtotime($award['issue_date']))) ?></div>
                    <?php endif; ?>
                    <h3 class="plaque-title"><?= htmlspecialchars($award['certificate_name']) ?></h3>
                    <div class="plaque-divider"></div>
                    <p class="plaque-desc"><?= htmlspecialchars($award['issuer']) ?></p>
                    <?php if (!empty($award['certificate_image'])): ?>
                        <button type="button" onclick="openLightbox('public/assets/img/chefs/certificates/<?= htmlspecialchars($award['certificate_image']) ?>')" class="plaque-btn" style="background: transparent; border: 1px solid #cda45e; cursor: pointer; padding: 8px 15px; margin-top: 10px;">
                            <i class="bi bi-award"></i> Xem Chứng Nhận
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- SIGNATURE DISHES -->
<?php if (!empty($sig_dishes)): ?>
<div class="cd-section" style="background: #fdfbf7;">
    <div class="container">
        <div class="text-center mb-5">
            <h2 style="font-family: 'Cormorant Garamond', serif; color: #111; font-size: 2.5rem;">Kiệt Tác Ẩm Thực</h2>
            <div style="width: 50px; height: 2px; background: #cda45e; margin: 20px auto;"></div>
        </div>
        <div class="cd-dishes-list">
            <?php foreach ($sig_dishes as $index => $dish): ?>
            <div class="cd-dish-row <?= $index % 2 != 0 ? 'reverse' : '' ?>">
                <div class="cd-dish-img-wrap">
                    <img src="public/assets/img/menu/<?= htmlspecialchars($dish['image']) ?>" alt="<?= htmlspecialchars($dish['name']) ?>">
                </div>
                <div class="cd-dish-info-wrap">
                    <h4 class="cd-dish-name"><?= htmlspecialchars($dish['name']) ?></h4>
                    <p class="cd-dish-desc"><?= mb_strimwidth(strip_tags($dish['description'] ?? ''), 0, 150, '...') ?></p>
                    <a href="dish.php?id=<?= $dish['id'] ?>" class="cd-dish-btn">Khám phá hương vị</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>





<!-- CHEF GALLERY -->
<?php if (!empty($gallery_images)): ?>
<div class="cd-section st-gallery-section" style="background-color: #fff; padding: 80px 0 0 0;">
    <div class="st-gallery-header text-center mb-5">
        <h2 style="font-family: 'Cormorant Garamond', serif; color: #111; font-size: 2.5rem; letter-spacing: 2px;">Thư Viện Ảnh Hoạt Động</h2>
        <div style="width: 50px; height: 2px; background: #cda45e; margin: 20px auto;"></div>
    </div>
    
    <div class="container-fluid px-0">
        <div class="st-gallery-grid">
            <?php foreach ($gallery_images as $g_img): ?>
                <div class="st-gallery-item" onclick="openLightbox('/restaurant-project/public/assets/img/chefs/gallery/<?= htmlspecialchars($g_img['image']) ?>')">
                    <img src="/restaurant-project/public/assets/img/chefs/gallery/<?= htmlspecialchars($g_img['image']) ?>" alt="Gallery Activity Image" loading="lazy">
                    <div class="st-gallery-hover">
                        <i class="bi bi-zoom-in"></i>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>


<?php endif; ?>

<div class="cd-section" style="background: #fcfaf5;">
    <div class="container" style="max-width: 1000px; margin: 0 auto;">
        <div class="text-center mb-5">
            <h2 style="font-family: 'Cormorant Garamond', serif; color: #111; font-size: 2.5rem;">Trải Nghiệm Cùng Bếp Trưởng</h2>
            <p style="color: #666; font-size: 1rem; margin-top: 10px;">Những chia sẻ chân thực từ các thực khách đã trực tiếp trải nghiệm ẩm thực do Bếp trưởng thực hiện.</p>
            <div style="width: 50px; height: 2px; background: #cda45e; margin: 20px auto;"></div>
        </div>
        
        <!-- Prestige Stats -->
        <div class="prestige-stats">
            <div class="prestige-main">
                <div class="rating-big" id="modalAvgRating">0.0<span style="font-size:1.2rem;">/5</span></div>
                <div class="stars-wrap mb-2" id="modalAvgStars"></div>
                <div class="reviews-count" id="modalReviewsCount">Được đánh giá từ 0 trải nghiệm</div>
                <div class="recommend-text">98% khách hàng sẵn sàng giới thiệu Bếp trưởng cho bạn bè.</div>
            </div>
            <div class="prestige-cards">
                <div class="p-card"><i class="bi bi-award-fill"></i><span><?= htmlspecialchars($chef['experience'] ?? '15') ?> năm kinh nghiệm</span></div>
                <div class="p-card"><i class="bi bi-people-fill"></i><span>850+ thực khách đã phục vụ</span></div>
                <div class="p-card"><i class="bi bi-arrow-repeat"></i><span>96% khách quay lại</span></div>
                <div class="p-card"><i class="bi bi-journal-check"></i><span>35 thực đơn Bespoke</span></div>
            </div>
        </div>

        <div class="chef-modal-section" style="background: transparent; border: none; padding: 0;">
            <!-- Reviews list -->
            <div id="modalReviewsList" class="luxury-reviews-list">
              <!-- Rendered dynamically -->
            </div>
            
            <div id="reviewsShowMoreContainer" class="text-center mt-4" style="display: none;">
                <button type="button" id="btnShowMoreReviews" class="btn-show-more-luxury">Xem thêm trải nghiệm</button>
            </div>

            <!-- Submit Review Form -->
            <div class="write-review-luxury mt-5">
              <h5 style="font-family:'Cormorant Garamond', serif; font-size: 1.5rem; color: #111; margin-bottom: 20px; text-align: center;">Chia Sẻ Trải Nghiệm Của Bạn</h5>
              <form id="submitReviewForm" onsubmit="submitChefReview(event)">
                <input type="hidden" name="chef_id" id="reviewChefId" value="<?= $chef['id'] ?>">
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" style="font-size: 0.9rem; color: #555; font-weight: 600;">Loại trải nghiệm</label>
                        <select name="experience_type" class="form-select luxury-input" required>
                            <option value="Fine Dining">Fine Dining</option>
                            <option value="Chef's Table">Chef's Table</option>
                            <option value="Private Dining">Private Dining</option>
                            <option value="Bespoke Menu">Bespoke Menu</option>
                            <option value="Anniversary Dinner">Anniversary Dinner</option>
                            <option value="Corporate Event">Corporate Event</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" style="font-size: 0.9rem; color: #555; font-weight: 600;">Đánh giá sao</label>
                        <div class="star-rating-picker">
                          <div class="rating-stars-picker">
                            <i class="bi bi-star-fill star-pick active" data-val="1"></i>
                            <i class="bi bi-star-fill star-pick active" data-val="2"></i>
                            <i class="bi bi-star-fill star-pick active" data-val="3"></i>
                            <i class="bi bi-star-fill star-pick active" data-val="4"></i>
                            <i class="bi bi-star-fill star-pick active" data-val="5"></i>
                          </div>
                          <input type="hidden" name="rating" id="selectedRatingVal" value="5">
                        </div>
                    </div>
                </div>

                <?php if (!isset($_SESSION['user_id'])): ?>
                <div class="mb-3">
                  <input type="text" name="author_name" id="reviewAuthorName" class="luxury-input" placeholder="Tên của bạn (Tùy chọn)">
                </div>
                <?php endif; ?>

                <div class="mb-4">
                  <textarea name="comment" id="reviewComment" class="luxury-input" rows="4" placeholder="Kể về trải nghiệm ẩm thực đáng nhớ của bạn..." required></textarea>
                </div>

                <div class="text-center">
                    <button type="submit" class="btn-submit-luxury">Gửi Cảm Nhận</button>
                </div>
              </form>
            </div>
        </div>
    </div>
</div>
</div>
<script>
// Fetch Reviews & Ratings
let allReviews = [];
let currentlyDisplayedReviews = 0;

function renderReviewsChunk(reviewsToRender, append = false) {
    let reviewsList = document.getElementById('modalReviewsList');
    if (!append) reviewsList.innerHTML = '';
    
    reviewsToRender.forEach(rev => {
        let div = document.createElement('div');
        div.className = 'review-card';
        
        let avatarSrc = rev.user_avatar ? 'public/assets/img/avatars/' + rev.user_avatar : 'https://placehold.co/50x50/F6F2E9/4F5B3A?text=' + rev.author_name.charAt(0);
        
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
}

function fetchChefReviews(chefId) {
    fetch('ajax/get_chef_reviews.php?chef_id=' + chefId)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                document.getElementById('modalAvgRating').textContent = data.avg_rating > 0 ? data.avg_rating.toFixed(1) : '0.0';
                document.getElementById('modalReviewsCount').textContent = data.review_count + ' đánh giá';
                
                let avgStarsContainer = document.getElementById('modalAvgStars');
                avgStarsContainer.innerHTML = '';
                let ratingVal = Math.round(data.avg_rating);
                for (let i = 1; i <= 5; i++) {
                    let star = document.createElement('i');
                    star.className = i <= ratingVal ? 'bi bi-star-fill text-warning' : 'bi bi-star';
                    avgStarsContainer.appendChild(star);
                }
                
                if (data.reviews && data.reviews.length > 0) {
                    allReviews = data.reviews;
                    currentlyDisplayedReviews = Math.min(2, allReviews.length);
                    renderReviewsChunk(allReviews.slice(0, currentlyDisplayedReviews));
                    
                    if (allReviews.length > 2) {
                        document.getElementById('reviewsShowMoreContainer').style.display = 'block';
                    } else {
                        document.getElementById('reviewsShowMoreContainer').style.display = 'none';
                    }
                } else {
                    document.getElementById('modalReviewsList').innerHTML = '<p class="text-center text-muted py-3">Chưa có đánh giá nào cho đầu bếp này. Hãy là người đầu tiên chia sẻ cảm nhận!</p>';
                    document.getElementById('reviewsShowMoreContainer').style.display = 'none';
                }
            }
        });
}

document.addEventListener("DOMContentLoaded", function() {
    let btnShowMore = document.getElementById('btnShowMoreReviews');
    if(btnShowMore) {
        btnShowMore.addEventListener('click', function() {
            let nextBatch = allReviews.slice(currentlyDisplayedReviews, currentlyDisplayedReviews + 5);
            renderReviewsChunk(nextBatch, true);
            currentlyDisplayedReviews += nextBatch.length;
            
            if (currentlyDisplayedReviews >= allReviews.length) {
                document.getElementById('reviewsShowMoreContainer').style.display = 'none';
            }
        });
    }
});

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


document.addEventListener("DOMContentLoaded", function() {
    let chefId = <?= $id ?>;
    document.getElementById("reviewChefId").value = chefId;
    fetchChefReviews(chefId);
});
</script>




<script>
// OVERRIDE OLD JS FUNCTIONS
function renderReviewsChunk(reviewsToRender, append = false) {
    let reviewsList = document.getElementById('modalReviewsList');
    if (!append) reviewsList.innerHTML = '';
    
    reviewsToRender.forEach((rev, index) => {
        let div = document.createElement('div');
        div.className = 'luxury-review-card';
        div.style.animationDelay = (index * 0.1) + 's';
        
        let avatarSrc = rev.user_avatar ? 'public/assets/img/avatars/' + rev.user_avatar : 'https://placehold.co/100x100/F6F2E9/A88746?text=' + rev.author_name.charAt(0);
        let chefAvatar = '<?= $img ?>';
        
        let starsHtml = '';
        for (let j = 1; j <= 5; j++) {
            starsHtml += j <= rev.rating ? '<i class="bi bi-star-fill"></i>' : '<i class="bi bi-star"></i>';
        }
        
        let expType = rev.experience_type || 'Fine Dining';
        
        let chefResponseHtml = '';
        if (rev.chef_response) {
            chefResponseHtml = `
            <div class="chef-response-box">
                <img src="${chefAvatar}" class="chef-response-avatar" alt="Chef">
                <div class="chef-response-content">
                    <h5>Executive Chef Response</h5>
                    <p>${escapeHtml(rev.chef_response)}</p>
                </div>
            </div>`;
        }
        
        div.innerHTML = `
            <div class="luxury-review-header">
                <div class="l-author-info">
                    <img class="l-avatar" src="${avatarSrc}" onerror="this.src='https://placehold.co/100x100/1a1814/A88746?text=${rev.author_name.charAt(0)}'" />
                    <div class="l-meta">
                        <h4>${escapeHtml(rev.author_name)}</h4>
                        <div class="l-date">${rev.created_at}</div>
                    </div>
                </div>
                <div class="l-right">
                    <div class="l-stars">${starsHtml}</div>
                    <div class="l-exp-badge">${escapeHtml(expType)}</div>
                </div>
            </div>
            <div class="luxury-review-body">
                ${escapeHtml(rev.comment)}
            </div>
            ${chefResponseHtml}
        `;
        reviewsList.appendChild(div);
    });
}
// Automatically trigger the fetch for the initial load since the HTML ID is the same 
// and the old script is still present further down (we just override the render function)
setTimeout(() => {
    let chefId = document.getElementById('reviewChefId').value;
    if(chefId) fetchChefReviews(chefId);
}, 500);
</script>



<!-- LIGHTBOX MODAL -->
<div id="chefLightbox" class="chef-lightbox" onclick="closeLightbox(event)">
    <span class="lightbox-close" onclick="closeLightbox(event)">&times;</span>
    <img class="lightbox-content" id="lightboxImg" src="" alt="Zoom Image">
</div>



<script>
function openLightbox(src) {
    const lightbox = document.getElementById('chefLightbox');
    const img = document.getElementById('lightboxImg');
    img.src = src;
    lightbox.style.display = 'flex';
    setTimeout(() => {
        lightbox.classList.add('active');
    }, 10);
    document.body.style.overflow = 'hidden'; // prevent scroll
}
function closeLightbox(event) {
    if (event.target.id === 'chefLightbox' || event.target.classList.contains('lightbox-close')) {
        const lightbox = document.getElementById('chefLightbox');
        lightbox.classList.remove('active');
        setTimeout(() => {
            lightbox.style.display = 'none';
        }, 300);
        document.body.style.overflow = ''; // restore scroll
    }
}
</script>

<?php include __DIR__ . '/views/client/layouts/footer.php'; ?>
