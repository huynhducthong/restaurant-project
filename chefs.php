<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/config/database.php';
$database = new Database();
$db = $database->getConnection();
$stmt = $db->prepare("SELECT * FROM chefs WHERE is_active = 1 ORDER BY sort_order ASC, id DESC");
$stmt->execute();
$chefs = $stmt->fetchAll(PDO::FETCH_ASSOC);
include __DIR__ . '/views/client/layouts/header.php';
?>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=Be+Vietnam+Pro:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
:root{
  --F:#143B36;--F1:#1a4d46;--F2:#0d2b27;
  --gold:#cda45e;--gold2:#e8d5a8;
  --ch:#f0ece3;--mu:rgba(240,236,227,.45);
  --ease:cubic-bezier(.4,0,.2,1);
}
*{box-sizing:border-box;margin:0;padding:0;}
body{background:var(--F2);color:var(--ch);font-family:'Be Vietnam Pro',sans-serif;}

/* HERO */
.ch-hero{
  position:relative;overflow:hidden;
  padding:140px 0 80px;text-align:center;
  background:linear-gradient(180deg,#080e0d 0%,var(--F2) 100%);
}
.ch-hero::before{
  content:'';position:absolute;inset:0;
  background:radial-gradient(ellipse 70% 60% at 50% 0%,rgba(20,59,54,.6),transparent 70%);
}
.ch-hero-eyebrow{
  display:inline-flex;align-items:center;gap:12px;
  font-size:10px;letter-spacing:.28em;text-transform:uppercase;color:var(--gold);
  margin-bottom:20px;
}
.ch-hero-eyebrow::before,.ch-hero-eyebrow::after{
  content:'';display:block;width:28px;height:1px;background:var(--gold);opacity:.4;
}
.ch-hero h1{
  font-family:'Cormorant Garamond',serif;font-weight:300;
  font-size:clamp(2.6rem,6vw,4.5rem);color:#fff;line-height:1.1;margin-bottom:14px;
}
.ch-hero h1 em{font-style:italic;color:var(--gold);}
.ch-hero-sub{
  font-size:14px;color:var(--mu);font-weight:300;
  max-width:480px;margin:0 auto;line-height:1.8;
}

/* STATS STRIP */
.ch-strip{
  background:var(--F);
  border-top:1px solid rgba(205,164,94,.12);
  border-bottom:1px solid rgba(205,164,94,.12);
  padding:28px 32px;
  display:flex;align-items:center;justify-content:center;
  gap:48px;flex-wrap:wrap;
}
.strip-item{text-align:center;}
.strip-num{font-family:'Cormorant Garamond',serif;font-weight:300;font-size:2.2rem;color:var(--gold);line-height:1;}
.strip-lbl{font-size:9px;letter-spacing:.18em;text-transform:uppercase;color:var(--mu);margin-top:4px;}

/* WRAP + GRID */
.ch-wrap{max-width:1200px;margin:0 auto;padding:72px 32px 80px;}
.ch-grid{
  display:grid;
  grid-template-columns:repeat(auto-fill,minmax(320px,1fr));
  gap:2px;
}
@media(max-width:600px){.ch-grid{grid-template-columns:1fr;}}

/* CARD — chỉ hiện tên + vị trí mặc định */
.ch-card{
  position:relative;overflow:hidden;
  background:var(--F);
}

/* Ảnh */
.ch-img{width:100%;aspect-ratio:3/4;overflow:hidden;position:relative;}
.ch-img img{
  width:100%;height:100%;object-fit:cover;
  filter:brightness(.55) saturate(.8);
  transition:transform .65s var(--ease),filter .5s var(--ease);
}
.ch-card:hover .ch-img img{
  transform:scale(1.07);
  filter:brightness(.25) saturate(.6);
}

/* Gradient overlay */
.ch-img-overlay{
  position:absolute;inset:0;
  background:linear-gradient(to top,var(--F) 0%,rgba(20,59,54,.15) 55%,transparent 100%);
  transition:opacity .4s;
}
.ch-card:hover .ch-img-overlay{
  opacity:1;
  background:linear-gradient(to top,rgba(9,30,27,.98) 0%,rgba(9,30,27,.7) 45%,transparent 100%);
}

/* ── Mặc định: chỉ tên + vị trí ── */
.ch-default{
  position:absolute;bottom:0;left:0;right:0;
  padding:28px 28px 26px;
  transition:opacity .3s var(--ease),transform .35s var(--ease);
}
.ch-card:hover .ch-default{
  opacity:0;
  transform:translateY(8px);
  pointer-events:none;
}
.ch-position-d{
  font-size:9px;letter-spacing:.22em;text-transform:uppercase;
  color:var(--gold);margin-bottom:7px;
}
.ch-name-d{
  font-family:'Cormorant Garamond',serif;font-weight:400;
  font-size:1.6rem;color:#fff;line-height:1.1;
}

/* ── Hover: thông tin đầy đủ ── */
.ch-hover{
  position:absolute;inset:0;
  padding:28px;
  display:flex;flex-direction:column;justify-content:flex-end;
  opacity:0;transform:translateY(12px);
  transition:opacity .35s var(--ease),transform .4s var(--ease);
  pointer-events:none;
}
.ch-card:hover .ch-hover{
  opacity:1;
  transform:translateY(0);
  pointer-events:all;
}
.ch-h-position{
  font-size:8px;letter-spacing:.24em;text-transform:uppercase;
  color:var(--gold);margin-bottom:6px;
}
.ch-h-name{
  font-family:'Cormorant Garamond',serif;font-weight:400;
  font-size:1.5rem;color:#fff;line-height:1.1;margin-bottom:4px;
}
.ch-h-exp{
  font-size:10px;color:rgba(205,164,94,.6);margin-bottom:12px;
  display:flex;align-items:center;gap:6px;
}
.ch-h-exp::before{content:'';width:14px;height:1px;background:rgba(205,164,94,.35);}
.ch-h-sep{width:28px;height:1px;background:rgba(205,164,94,.3);margin-bottom:12px;}
.ch-h-specialty{
  font-size:11px;color:rgba(240,236,227,.55);margin-bottom:8px;
  display:flex;align-items:center;gap:6px;
}
.ch-h-specialty i{color:var(--gold);font-size:10px;flex-shrink:0;}
.ch-h-desc{
  font-size:11px;color:rgba(240,236,227,.45);line-height:1.7;
  margin-bottom:12px;
  display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;
}
.ch-h-quote{
  font-family:'Cormorant Garamond',serif;font-style:italic;
  font-size:.9rem;color:rgba(205,164,94,.55);
  border-left:2px solid rgba(205,164,94,.25);
  padding-left:10px;margin-bottom:14px;
  line-height:1.5;
}
.ch-h-social{display:flex;gap:8px;flex-wrap:wrap;}
.ch-h-social a{
  width:32px;height:32px;border-radius:50%;
  border:1px solid rgba(205,164,94,.3);
  display:flex;align-items:center;justify-content:center;
  color:rgba(240,236,227,.55);font-size:13px;text-decoration:none;
  transition:.2s;
}
.ch-h-social a:hover{border-color:var(--gold);color:var(--gold);}

/* EMPTY */
.ch-empty{text-align:center;padding:100px 20px;color:var(--mu);}
.ch-empty i{font-size:52px;opacity:.2;display:block;margin-bottom:16px;}
</style>

<div class="page-space"></div>

<!-- HERO -->
<section class="ch-hero">
  <div class="container" style="position:relative;z-index:1">
    <div class="ch-hero-eyebrow">Restaurantly</div>
    <h1>Những <em>nghệ nhân</em><br>đứng sau mỗi món ăn</h1>
    <p class="ch-hero-sub">Đội ngũ đầu bếp của chúng tôi mang trong mình đam mê, kỹ năng và câu chuyện riêng — để biến từng bữa ăn thành một trải nghiệm đáng nhớ.</p>
  </div>
</section>

<!-- STATS STRIP -->
<?php if(!empty($chefs)): ?>
<div class="ch-strip">
  <div class="strip-item">
    <div class="strip-num"><?= count($chefs) ?>+</div>
    <div class="strip-lbl">Đầu bếp chuyên nghiệp</div>
  </div>
  <div class="strip-item">
    <div class="strip-num"><?php
      $exp = array_sum(array_filter(array_column($chefs,'experience')));
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
    <?php foreach($chefs as $chef):
      $img = !empty($chef['image'])
        ? '/restaurant-project/public/assets/img/chefs/'.htmlspecialchars($chef['image'])
        : 'https://placehold.co/400x530/143B36/cda45e?text=Chef';
      $hasSocial = !empty($chef['facebook']) || !empty($chef['instagram']) || !empty($chef['email']);
    ?>
    <div class="ch-card">
      <div class="ch-img">
        <img src="<?= $img ?>"
             alt="<?= htmlspecialchars($chef['name']) ?>"
             onerror="this.src='https://placehold.co/400x530/143B36/cda45e?text=Chef'">
        <div class="ch-img-overlay"></div>
      </div>

      <!-- Mặc định: chỉ tên + vị trí -->
      <div class="ch-default">
        <div class="ch-position-d"><?= htmlspecialchars($chef['position'] ?? 'Đầu Bếp') ?></div>
        <div class="ch-name-d"><?= htmlspecialchars($chef['name']) ?></div>
      </div>

      <!-- Hover: thông tin đầy đủ -->
      <div class="ch-hover">
        <div class="ch-h-position"><?= htmlspecialchars($chef['position'] ?? 'Đầu Bếp') ?></div>
        <div class="ch-h-name"><?= htmlspecialchars($chef['name']) ?></div>

        <?php if(!empty($chef['experience'])): ?>
        <div class="ch-h-exp"><?= (int)$chef['experience'] ?> năm kinh nghiệm</div>
        <?php endif; ?>

        <div class="ch-h-sep"></div>

        <?php if(!empty($chef['specialty'])): ?>
        <div class="ch-h-specialty">
          <i class="bi bi-stars"></i>
          <?= htmlspecialchars($chef['specialty']) ?>
        </div>
        <?php endif; ?>

        <?php if(!empty($chef['description'])): ?>
        <p class="ch-h-desc"><?= htmlspecialchars($chef['description']) ?></p>
        <?php endif; ?>

        <?php if(!empty($chef['quote'])): ?>
        <div class="ch-h-quote">"<?= htmlspecialchars($chef['quote']) ?>"</div>
        <?php endif; ?>

        <?php if($hasSocial): ?>
        <div class="ch-h-social">
          <?php if(!empty($chef['facebook'])): ?>
          <a href="<?= htmlspecialchars($chef['facebook']) ?>" target="_blank" rel="noopener">
            <i class="bi bi-facebook"></i>
          </a>
          <?php endif; ?>
          <?php if(!empty($chef['instagram'])): ?>
          <a href="<?= htmlspecialchars($chef['instagram']) ?>" target="_blank" rel="noopener">
            <i class="bi bi-instagram"></i>
          </a>
          <?php endif; ?>
          <?php if(!empty($chef['email'])): ?>
          <a href="mailto:<?= htmlspecialchars($chef['email']) ?>">
            <i class="bi bi-envelope"></i>
          </a>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>

    </div>
    <?php endforeach; ?>
  </div>

  <?php else: ?>
  <div class="ch-empty">
    <i class="bi bi-people"></i>
    <p style="font-family:'Cormorant Garamond',serif;font-style:italic;font-size:1.2rem;">
      Thông tin đội ngũ đang được cập nhật...
    </p>
  </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/views/client/layouts/footer.php'; ?>