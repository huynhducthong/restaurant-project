<?php
require_once __DIR__ . '/config/database.php';
$database = new Database();
$db = $database->getConnection();

$all_categories = $db->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$all_combos     = $db->query("SELECT * FROM combos WHERE is_active = 1 ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$all_foods      = $db->query("SELECT f.*, c.name as cat_name FROM foods f LEFT JOIN categories c ON f.category_id = c.id WHERE f.is_active = 1 ORDER BY f.id DESC")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/views/client/layouts/header.php';
?>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=Be+Vietnam+Pro:wght@300;400;500&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet">
<style>
/* ══ TOKENS ══ */
:root{
  --F:  #143B36;
  --F1: #1a4d46;
  --F2: #0d2b27;
  --F3: #091e1b;
  --G:  #D4B06A;
  --G2: #edd9a3;
  --G3: rgba(212,176,106,.15);
  --ink:#080e0d;
  --ch: #f0ece3;
  --mu: rgba(240,236,227,.45);
  --ease:cubic-bezier(.4,0,.2,1);
}
*{box-sizing:border-box;margin:0;padding:0;}
html{scroll-behavior:smooth;}
body{background:var(--F2);color:var(--ch);font-family:'Be Vietnam Pro',sans-serif;overflow-x:hidden;}
img{display:block;}

/* ══ HERO ══ */
.hero{
  position:relative;height:100vh;min-height:640px;
  display:flex;align-items:flex-end;overflow:hidden;
}
.hero-bg{
  position:absolute;inset:0;z-index:0;
  background:
    radial-gradient(ellipse 70% 80% at 70% 50%, rgba(20,59,54,.3) 0%, transparent 70%),
    linear-gradient(160deg, var(--F3) 0%, var(--F2) 45%, #0a1f1c 100%);
}
.hero-grain{
  position:absolute;inset:0;z-index:1;opacity:.04;
  background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='1'/%3E%3C/svg%3E");
  background-size:200px;
}
.hero-img{
  position:absolute;right:0;top:0;bottom:0;width:58%;z-index:0;
  background:center/cover no-repeat url('public/assets/img/about-bg.jpg');
  mask-image:linear-gradient(to left,rgba(0,0,0,.75) 30%,transparent 100%);
  -webkit-mask-image:linear-gradient(to left,rgba(0,0,0,.75) 30%,transparent 100%);
  filter:brightness(.35) saturate(.7);
}
.hero-line{
  position:absolute;left:0;top:0;bottom:0;width:4px;
  background:linear-gradient(to bottom,transparent,var(--G),transparent);
  opacity:.4;z-index:2;
}
.hero-content{
  position:relative;z-index:3;
  padding:0 max(48px,7vw) 80px;max-width:680px;
}
.hero-eyebrow{
  display:inline-flex;align-items:center;gap:14px;
  font-size:10px;letter-spacing:.28em;text-transform:uppercase;color:var(--G);
  margin-bottom:28px;
}
.hero-eyebrow::before{content:'';width:36px;height:1px;background:var(--G);opacity:.5;}
.hero h1{
  font-family:'Cormorant Garamond',serif;font-weight:300;
  font-size:clamp(3.2rem,7vw,6rem);color:#fff;line-height:1.06;
  letter-spacing:-.01em;margin-bottom:22px;
}
.hero h1 em{font-style:italic;color:var(--G);}
.hero-sub{
  font-size:14px;color:var(--mu);font-weight:300;
  line-height:1.85;max-width:380px;margin-bottom:44px;
}
.hero-ctas{display:flex;gap:14px;flex-wrap:wrap;}
.btn-g{
  padding:15px 36px;background:var(--G);color:var(--ink);
  font-size:11px;font-weight:500;letter-spacing:.16em;text-transform:uppercase;
  text-decoration:none;border:none;cursor:pointer;
  transition:all .25s var(--ease);display:inline-block;font-family:'Be Vietnam Pro',sans-serif;
}
.btn-g:hover{background:var(--G2);transform:translateY(-2px);}
.btn-outline{
  padding:14px 36px;background:transparent;
  border:1px solid rgba(212,176,106,.35);color:var(--G);
  font-size:11px;font-weight:500;letter-spacing:.16em;text-transform:uppercase;
  text-decoration:none;cursor:pointer;
  transition:all .25s var(--ease);display:inline-block;font-family:'Be Vietnam Pro',sans-serif;
}
.btn-outline:hover{border-color:var(--G);background:var(--G3);}
.hero-scroll{
  position:absolute;bottom:36px;left:50%;transform:translateX(-50%);
  z-index:3;display:flex;flex-direction:column;align-items:center;gap:10px;
}
.hero-scroll span{font-size:9px;letter-spacing:.22em;text-transform:uppercase;color:rgba(212,176,106,.4);}
.scroll-bar{width:1px;height:52px;background:linear-gradient(to bottom,var(--G),transparent);animation:spulse 2.2s ease-in-out infinite;}
@keyframes spulse{0%,100%{opacity:.25;transform:scaleY(.5)}50%{opacity:1;transform:scaleY(1)}}

/* ══ STICKY CAT BAR ══ */
.cat-bar{
  position:sticky;top:0;z-index:200;
  background:rgba(9,30,27,.92);backdrop-filter:blur(24px);
  border-bottom:1px solid rgba(212,176,106,.1);
}
.cat-inner{
  max-width:1280px;margin:0 auto;padding:0 max(40px,5vw);
  display:flex;align-items:stretch;overflow-x:auto;gap:0;
  scrollbar-width:none;
}
.cat-inner::-webkit-scrollbar{display:none;}
.cat-btn{
  flex-shrink:0;padding:18px 22px;
  font-size:10px;letter-spacing:.2em;text-transform:uppercase;
  color:rgba(240,236,227,.35);background:transparent;border:none;
  cursor:pointer;position:relative;transition:color .2s;white-space:nowrap;
  font-family:'Be Vietnam Pro',sans-serif;
}
.cat-btn::after{
  content:'';position:absolute;bottom:0;left:0;right:0;
  height:2px;background:var(--G);transform:scaleX(0);
  transition:transform .25s var(--ease);transform-origin:center;
}
.cat-btn:hover{color:rgba(240,236,227,.65);}
.cat-btn.on{color:var(--G);}
.cat-btn.on::after{transform:scaleX(1);}

/* ══ LAYOUT ══ */
.wrap{max-width:1280px;margin:0 auto;padding:0 max(40px,5vw);}

/* ══ SECTION HEADER ══ */
.sec-tag{
  display:flex;align-items:center;gap:14px;
  font-size:9px;letter-spacing:.26em;text-transform:uppercase;
  color:var(--G);margin-bottom:14px;
}
.sec-tag::after{content:'';flex:1;height:1px;background:rgba(212,176,106,.15);}
.sec-h{
  font-family:'Cormorant Garamond',serif;font-weight:300;
  font-size:clamp(1.8rem,3.5vw,2.8rem);color:#fff;line-height:1.15;
}
.sec-h em{font-style:italic;color:var(--G);}

/* ══ COMBO BENTO ══ */
.combo-bento{
  display:grid;gap:3px;margin-top:36px;
  grid-template-columns:1fr 1fr 1fr;
}
.combo-bento.few1{grid-template-columns:1fr;}
.combo-bento.few2{grid-template-columns:1fr 1fr;}
@media(max-width:768px){.combo-bento{grid-template-columns:1fr!important;}}

.cb-card{
  position:relative;overflow:hidden;cursor:pointer;
  background:var(--F1);
}
.cb-card:first-child{grid-row:span 1;}
.cb-img{
  position:absolute;inset:0;
  background:center/cover no-repeat;
  transition:transform .65s var(--ease);
  filter:brightness(.38);
}
.cb-card:hover .cb-img{transform:scale(1.06);filter:brightness(.28);}
.cb-body{
  position:relative;z-index:1;
  padding:clamp(24px,3vw,40px);
  min-height:260px;display:flex;flex-direction:column;justify-content:flex-end;
  background:linear-gradient(to top,rgba(9,30,27,.95) 0%,rgba(9,30,27,.2) 55%,transparent 100%);
}
.cb-badge{
  display:inline-block;font-size:8px;letter-spacing:.2em;text-transform:uppercase;
  padding:3px 10px;border:1px solid rgba(212,176,106,.4);color:var(--G);
  margin-bottom:10px;width:fit-content;
}
.cb-name{
  font-family:'Cormorant Garamond',serif;font-weight:400;font-size:1.5rem;
  color:#fff;line-height:1.2;margin-bottom:8px;
}
.cb-desc{font-size:12px;color:var(--mu);line-height:1.7;margin-bottom:14px;}
.cb-price{font-family:'Cormorant Garamond',serif;font-weight:300;font-size:1.3rem;color:var(--G);}
.cb-card-overlay{
  position:absolute;inset:0;z-index:2;
  display:flex;align-items:center;justify-content:center;
  opacity:0;transition:opacity .3s;
}
.cb-card:hover .cb-card-overlay{opacity:1;}
.view-btn{
  padding:12px 28px;border:1px solid var(--G);color:var(--G);
  font-size:10px;letter-spacing:.18em;text-transform:uppercase;
  background:transparent;cursor:pointer;
  transition:all .2s;font-family:'Be Vietnam Pro',sans-serif;
}
.view-btn:hover{background:var(--G);color:var(--ink);}

/* ══ LUXURY DIVIDER ══ */
.lux-div{
  display:flex;align-items:center;gap:24px;
  padding:64px max(40px,5vw) 0;max-width:1280px;margin:0 auto;
}
.lux-div-line{flex:1;height:1px;background:rgba(212,176,106,.1);}
.lux-div-txt{
  font-family:'Cormorant Garamond',serif;font-style:italic;
  font-size:1rem;color:rgba(212,176,106,.35);white-space:nowrap;letter-spacing:.1em;
}

/* ══ CHEF SECTION ══ */
.chef-wrap{background:var(--F);border-top:1px solid rgba(212,176,106,.1);border-bottom:1px solid rgba(212,176,106,.1);}
.chef-grid{
  display:grid;grid-template-columns:1.2fr 1fr;gap:3px;margin-top:36px;
}
@media(max-width:768px){.chef-grid{grid-template-columns:1fr;}}
.chef-hero{position:relative;overflow:hidden;min-height:480px;cursor:pointer;}
.chef-hero-img{
  position:absolute;inset:0;background:center/cover no-repeat;
  transition:transform .65s var(--ease);filter:brightness(.35);
}
.chef-hero:hover .chef-hero-img{transform:scale(1.04);}
.chef-hero-body{
  position:absolute;inset:0;padding:40px;
  display:flex;flex-direction:column;justify-content:flex-end;
  background:linear-gradient(to top,rgba(9,30,27,.97) 0%,transparent 55%);
}
.chef-stack{display:grid;grid-template-rows:1fr 1fr;gap:3px;}
.chef-sm{position:relative;overflow:hidden;cursor:pointer;min-height:240px;}
.chef-sm-img{position:absolute;inset:0;background:center/cover no-repeat;transition:transform .65s var(--ease);filter:brightness(.32);}
.chef-sm:hover .chef-sm-img{transform:scale(1.06);}
.chef-sm-body{
  position:absolute;inset:0;padding:28px;
  display:flex;flex-direction:column;justify-content:flex-end;
  background:linear-gradient(to top,rgba(9,30,27,.95) 0%,transparent 60%);
}
.clabel{font-size:8px;letter-spacing:.24em;text-transform:uppercase;color:var(--G);margin-bottom:8px;}
.cname{font-family:'Cormorant Garamond',serif;font-weight:400;font-size:1.6rem;color:#fff;line-height:1.2;margin-bottom:6px;}
.cdesc{font-size:12px;color:var(--mu);line-height:1.7;margin-bottom:12px;}
.cprice{font-family:'Cormorant Garamond',serif;font-weight:300;font-size:1.2rem;color:var(--G);}

/* ══ FOOD GRID ══ */
.food-grid{
  display:grid;margin-top:36px;gap:3px;
  grid-template-columns:repeat(auto-fill,minmax(280px,1fr));
}
.food-card{
  background:var(--F2);cursor:pointer;overflow:hidden;
  border:1px solid rgba(212,176,106,.05);
  transition:background .3s;position:relative;
}
.food-card:hover{background:#0f2f2a;}
.food-card-img{width:100%;aspect-ratio:4/3;overflow:hidden;position:relative;}
.food-card-img img{
  width:100%;height:100%;object-fit:cover;
  transition:transform .65s var(--ease);filter:brightness(.65);
}
.food-card:hover .food-card-img img{transform:scale(1.06);filter:brightness(.5);}
.food-img-overlay{position:absolute;inset:0;background:linear-gradient(to bottom,transparent 45%,rgba(9,30,27,.6));}
.food-badges{position:absolute;top:12px;left:12px;display:flex;gap:5px;flex-wrap:wrap;}
.fb{font-size:8px;letter-spacing:.14em;text-transform:uppercase;padding:3px 8px;backdrop-filter:blur(10px);}
.fb.sig{background:rgba(212,176,106,.2);color:var(--G);border:1px solid rgba(212,176,106,.35);}
.fb.prem{background:rgba(255,255,255,.08);color:#fff;border:1px solid rgba(255,255,255,.18);}
.fb.seas{background:rgba(20,59,54,.7);color:var(--G2);border:1px solid rgba(212,176,106,.25);}
.food-body{padding:22px 24px;}
.food-cat-label{font-size:9px;letter-spacing:.2em;text-transform:uppercase;color:var(--G);opacity:.65;margin-bottom:7px;}
.food-name{font-family:'Cormorant Garamond',serif;font-weight:400;font-size:1.15rem;color:#fff;line-height:1.3;margin-bottom:8px;}
.food-story{font-size:12px;color:var(--mu);line-height:1.75;margin-bottom:18px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
.food-foot{display:flex;align-items:center;justify-content:space-between;padding-top:14px;border-top:1px solid rgba(212,176,106,.08);}
.food-price{font-family:'Cormorant Garamond',serif;font-weight:300;font-size:1.15rem;color:var(--G);}
.food-more{font-size:9px;letter-spacing:.14em;text-transform:uppercase;color:rgba(212,176,106,.3);transition:color .2s;background:none;border:none;cursor:pointer;font-family:'Be Vietnam Pro',sans-serif;}
.food-card:hover .food-more{color:var(--G);}

/* ══ MODAL ══ */
.modal-ov{
  position:fixed;inset:0;z-index:1000;
  background:rgba(9,30,27,.97);backdrop-filter:blur(20px);
  display:flex;align-items:center;justify-content:center;
  padding:24px;opacity:0;pointer-events:none;transition:opacity .35s var(--ease);
}
.modal-ov.open{opacity:1;pointer-events:all;}
.modal-box{
  background:var(--F2);border:1px solid rgba(212,176,106,.12);
  width:100%;max-width:860px;max-height:90vh;overflow-y:auto;
  transform:translateY(28px);transition:transform .35s var(--ease);
  scrollbar-width:thin;scrollbar-color:rgba(212,176,106,.15) transparent;
  position:relative;
}
.modal-ov.open .modal-box{transform:translateY(0);}
.modal-img-wrap{width:100%;aspect-ratio:16/7;overflow:hidden;position:relative;}
.modal-img-wrap img{width:100%;height:100%;object-fit:cover;filter:brightness(.75);}
.modal-img-grad{position:absolute;bottom:0;left:0;right:0;height:50%;background:linear-gradient(to top,var(--F2),transparent);}
.modal-body{padding:36px max(36px,4vw) 44px;}
.modal-eyebrow{font-size:9px;letter-spacing:.22em;text-transform:uppercase;color:var(--G);margin-bottom:10px;}
.modal-title{font-family:'Cormorant Garamond',serif;font-weight:300;font-size:clamp(1.6rem,3vw,2.5rem);color:#fff;line-height:1.15;margin-bottom:14px;}
.modal-desc{font-size:14px;color:var(--mu);line-height:1.85;margin-bottom:28px;}
.modal-specs{display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:1px;border:1px solid rgba(212,176,106,.08);margin-bottom:28px;}
.spec{padding:15px 16px;background:rgba(212,176,106,.025);}
.spec-l{font-size:9px;letter-spacing:.18em;text-transform:uppercase;color:rgba(212,176,106,.45);margin-bottom:5px;}
.spec-v{font-family:'Cormorant Garamond',serif;font-size:1rem;color:var(--ch);}
.modal-foot{display:flex;align-items:center;justify-content:space-between;padding-top:20px;border-top:1px solid rgba(212,176,106,.08);}
.modal-price{font-family:'Cormorant Garamond',serif;font-weight:300;font-size:2rem;color:var(--G);}
.modal-close{
  position:absolute;top:16px;right:16px;z-index:10;
  width:40px;height:40px;background:rgba(9,30,27,.8);
  border:1px solid rgba(212,176,106,.2);color:var(--mu);font-size:18px;
  cursor:pointer;display:flex;align-items:center;justify-content:center;
  transition:.2s;
}
.modal-close:hover{color:#fff;border-color:var(--G);}

/* ══ FLOAT BOOKING ══ */
.float-book{
  position:fixed;right:28px;bottom:28px;z-index:500;
  opacity:0;transform:translateY(16px);
  transition:all .4s var(--ease);pointer-events:none;
}
.float-book.show{opacity:1;transform:translateY(0);pointer-events:all;}
.float-inner{
  background:rgba(9,30,27,.95);backdrop-filter:blur(24px);
  border:1px solid rgba(212,176,106,.25);padding:18px 22px;
  min-width:190px;
}
.float-label{font-size:9px;letter-spacing:.2em;text-transform:uppercase;color:var(--G);opacity:.65;margin-bottom:3px;}
.float-title{font-family:'Cormorant Garamond',serif;font-size:.95rem;color:#fff;margin-bottom:14px;line-height:1.3;}
.float-cta{
  display:block;width:100%;padding:11px;
  background:var(--G);color:var(--ink);
  font-size:10px;font-weight:500;letter-spacing:.14em;text-transform:uppercase;
  text-align:center;text-decoration:none;border:none;cursor:pointer;
  transition:background .2s;font-family:'Be Vietnam Pro',sans-serif;
}
.float-cta:hover{background:var(--G2);}
@media(max-width:600px){.float-book{right:16px;bottom:16px;}}

/* ══ HIDDEN FILTER ══ */
.food-card.hidden{display:none;}

/* ══ EMPTY STATE ══ */
.empty-state{
  grid-column:1/-1;text-align:center;padding:80px 20px;
}
.empty-state p{font-family:'Cormorant Garamond',serif;font-style:italic;font-size:1.2rem;color:var(--mu);}

/* ══ SECTION SPACING ══ */
.sec-pad{padding:72px max(40px,5vw);}
@media(max-width:600px){.sec-pad{padding:52px 20px;}}

/* ══ FADE AOS FALLBACK ══ */
[data-aos]{opacity:0;transform:translateY(24px);transition:opacity .7s,transform .7s;}
[data-aos].aos-animate{opacity:1;transform:translateY(0);}
</style>

<!-- HERO -->
<section class="hero" id="top">
  <div class="hero-bg"></div>
  <div class="hero-grain"></div>
  <div class="hero-img"></div>
  <div class="hero-line"></div>
  <div class="hero-content">
    <div class="hero-eyebrow">Signature Culinary Experience</div>
    <h1>Tinh hoa<br>ẩm thực<br><em>Restaurantly</em></h1>
    <p class="hero-sub">Hành trình ẩm thực cao cấp — được chắt lọc từ nguyên liệu tươi ngon nhất, bởi bàn tay đầu bếp chuyên nghiệp.</p>
    <div class="hero-ctas">
      <a href="#menu-section" class="btn-g">Khám phá thực đơn</a>
      <a href="booking_service.php" class="btn-outline">Đặt bàn ngay</a>
    </div>
  </div>
  <div class="hero-scroll">
    <span>Khám phá</span>
    <div class="scroll-bar"></div>
  </div>
</section>

<!-- STICKY CATEGORY BAR -->
<nav class="cat-bar" id="menu-section">
  <div class="cat-inner">
    <button class="cat-btn on" data-cat="all">Tất cả</button>
    <button class="cat-btn" data-cat="combo">Combo</button>
    <?php foreach($all_categories as $cat): ?>
    <button class="cat-btn" data-cat="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></button>
    <?php endforeach; ?>
  </div>
</nav>

<!-- COMBO SECTION -->
<?php if(!empty($all_combos)): ?>
<section class="sec-pad" id="sec-combo">
  <div class="wrap">
    <div data-aos="fade-up">
      <div class="sec-tag">Combo đặc biệt</div>
      <h2 class="sec-h">Gói trải nghiệm <em>được tuyển chọn</em></h2>
    </div>
    <div class="combo-bento <?= count($all_combos)===1?'few1':(count($all_combos)===2?'few2':'') ?>" data-aos="fade-up" data-aos-delay="100">
      <?php foreach($all_combos as $i=>$cb): ?>
      <div class="cb-card" onclick="openModal(<?= htmlspecialchars(json_encode([
        'type'=>'combo','name'=>$cb['name'],'desc'=>$cb['description'],
        'price'=>$cb['price'],'img'=>'public/assets/img/combos/'.$cb['image'],
        'cat'=>'Combo Đặc Biệt'
      ])) ?>)">
        <div class="cb-img" style="background-image:url('public/assets/img/combos/<?= htmlspecialchars($cb['image']) ?>')"></div>
        <div class="cb-body">
          <span class="cb-badge">Combo <?= $i+1 ?> · Ưu đãi</span>
          <h3 class="cb-name"><?= htmlspecialchars($cb['name']) ?></h3>
          <p class="cb-desc"><?= htmlspecialchars(mb_strimwidth($cb['description'],0,90,'…')) ?></p>
          <div class="cb-price"><?= number_format($cb['price'],0,',','.') ?> đ</div>
        </div>
        <div class="cb-card-overlay">
          <button class="view-btn">Xem chi tiết</button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<div class="lux-div" data-aos="fade-up">
  <div class="lux-div-line"></div>
  <span class="lux-div-txt">A la Carte · Món lẻ</span>
  <div class="lux-div-line"></div>
</div>
<?php endif; ?>

<!-- CHEF RECOMMENDATION -->
<?php if(count($all_foods)>=3): ?>
<section class="chef-wrap sec-pad" id="sec-chef">
  <div class="wrap">
    <div data-aos="fade-up">
      <div class="sec-tag">Gợi ý từ bếp trưởng</div>
      <h2 class="sec-h"><em>Chef's</em> Recommendation</h2>
    </div>
    <div class="chef-grid" data-aos="fade-up" data-aos-delay="100">
      <?php $cp=$all_foods; $c0=$cp[0]??null; $c1=$cp[1]??null; $c2=$cp[2]??null; ?>
      <?php if($c0): ?>
      <div class="chef-hero" onclick="openModal(<?= htmlspecialchars(json_encode([
        'type'=>'food','name'=>$c0['name'],'desc'=>$c0['description'],
        'price'=>$c0['price'],'img'=>'public/assets/img/menu/'.$c0['image'],
        'cat'=>$c0['cat_name']??''
      ])) ?>)">
        <div class="chef-hero-img" style="background-image:url('public/assets/img/menu/<?= htmlspecialchars($c0['image']) ?>')"></div>
        <div class="chef-hero-body">
          <div class="clabel">Signature Dish · Chef's Choice</div>
          <h3 class="cname"><?= htmlspecialchars($c0['name']) ?></h3>
          <p class="cdesc"><?= htmlspecialchars(mb_strimwidth($c0['description'],0,110,'…')) ?></p>
          <div class="cprice"><?= number_format($c0['price'],0,',','.') ?> đ</div>
        </div>
      </div>
      <?php endif; ?>
      <div class="chef-stack">
        <?php foreach([$c1,$c2] as $cs): if(!$cs) continue; ?>
        <div class="chef-sm" onclick="openModal(<?= htmlspecialchars(json_encode([
          'type'=>'food','name'=>$cs['name'],'desc'=>$cs['description'],
          'price'=>$cs['price'],'img'=>'public/assets/img/menu/'.$cs['image'],
          'cat'=>$cs['cat_name']??''
        ])) ?>)">
          <div class="chef-sm-img" style="background-image:url('public/assets/img/menu/<?= htmlspecialchars($cs['image']) ?>')"></div>
          <div class="chef-sm-body">
            <div class="clabel">Premium Selection</div>
            <h3 class="cname" style="font-size:1.2rem"><?= htmlspecialchars($cs['name']) ?></h3>
            <div class="cprice" style="font-size:1rem"><?= number_format($cs['price'],0,',','.') ?> đ</div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ALL FOODS GRID -->
<section class="sec-pad" id="sec-foods">
  <div class="wrap">
    <div data-aos="fade-up">
      <div class="sec-tag">Thực đơn đầy đủ</div>
      <h2 class="sec-h">Món <em>A la Carte</em></h2>
    </div>
    <div class="food-grid" id="food-grid">
      <?php foreach($all_foods as $i=>$f):
        $badges=[];
        if($i<3) $badges[]=['sig','Signature'];
        if(($f['price']??0)>300000) $badges[]=['prem','Premium'];
      ?>
      <div class="food-card" data-cat="<?= $f['category_id'] ?>"
           onclick="openModal(<?= htmlspecialchars(json_encode([
             'type'=>'food','name'=>$f['name'],'desc'=>$f['description'],
             'price'=>$f['price'],'img'=>'public/assets/img/menu/'.$f['image'],
             'cat'=>$f['cat_name']??''
           ])) ?>)"
           data-aos="fade-up" data-aos-delay="<?= ($i%4)*60 ?>">
        <div class="food-card-img">
          <img src="public/assets/img/menu/<?= htmlspecialchars($f['image']) ?>"
               onerror="this.src='public/assets/img/default.jpg'"
               alt="<?= htmlspecialchars($f['name']) ?>">
          <div class="food-img-overlay"></div>
          <?php if(!empty($badges)): ?>
          <div class="food-badges">
            <?php foreach($badges as [$cls,$lbl]): ?>
            <span class="fb <?= $cls ?>"><?= $lbl ?></span>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
        <div class="food-body">
          <?php if(!empty($f['cat_name'])): ?>
          <div class="food-cat-label"><?= htmlspecialchars($f['cat_name']) ?></div>
          <?php endif; ?>
          <h3 class="food-name"><?= htmlspecialchars($f['name']) ?></h3>
          <p class="food-story"><?= htmlspecialchars($f['description']) ?></p>
          <div class="food-foot">
            <div class="food-price"><?= number_format($f['price'],0,',','.') ?> đ</div>
            <button class="food-more">Chi tiết →</button>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if(empty($all_foods)): ?>
      <div class="empty-state"><p>Thực đơn đang được cập nhật...</p></div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- MODAL -->
<div class="modal-ov" id="modal" onclick="closeModal(event)">
  <div class="modal-box">
    <button class="modal-close" onclick="closeModal(null)">✕</button>
    <div class="modal-img-wrap">
      <img id="m-img" src="" alt="">
      <div class="modal-img-grad"></div>
    </div>
    <div class="modal-body">
      <div class="modal-eyebrow" id="m-cat"></div>
      <h2 class="modal-title" id="m-name"></h2>
      <p class="modal-desc" id="m-desc"></p>
      <div class="modal-specs">
        <div class="spec"><div class="spec-l">Phong cách</div><div class="spec-v">Fine Dining</div></div>
        <div class="spec"><div class="spec-l">Phục vụ</div><div class="spec-v">15–20 phút</div></div>
        <div class="spec"><div class="spec-l">Wine Pairing</div><div class="spec-v">Có thể chọn</div></div>
        <div class="spec"><div class="spec-l">Chef Note</div><div class="spec-v">Nguyên liệu tươi ngon nhất</div></div>
      </div>
      <div class="modal-foot">
        <div class="modal-price" id="m-price"></div>
        <a href="booking_service.php" class="btn-g" style="font-size:11px;padding:12px 28px;">Đặt bàn ngay</a>
      </div>
    </div>
  </div>
</div>

<!-- FLOATING RESERVATION -->
<div class="float-book" id="floatBook">
  <div class="float-inner">
    <div class="float-label">Restaurantly</div>
    <div class="float-title">Trải nghiệm fine dining<br>hôm nay</div>
    <a href="booking_service.php" class="float-cta">Đặt bàn ngay</a>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
<script>
AOS.init({once:true,duration:700,offset:60});

/* Category filter */
var catBtns=document.querySelectorAll('.cat-btn');
catBtns.forEach(function(btn){
  btn.addEventListener('click',function(){
    catBtns.forEach(function(b){b.classList.remove('on');});
    btn.classList.add('on');
    var cat=btn.dataset.cat;
    var cards=document.querySelectorAll('.food-card');
    cards.forEach(function(c){
      if(cat==='all'||cat===c.dataset.cat){c.classList.remove('hidden');}
      else{c.classList.add('hidden');}
    });
    /* Combo section */
    var secCombo=document.getElementById('sec-combo');
    if(secCombo) secCombo.style.display=(cat==='all'||cat==='combo')?'':'none';
    var secChef=document.getElementById('sec-chef');
    if(secChef) secChef.style.display=(cat==='all')?'':'none';
    /* Scroll to foods */
    if(cat!=='all') document.getElementById('sec-foods').scrollIntoView({behavior:'smooth',block:'start'});
  });
});

/* Modal */
function openModal(data){
  document.getElementById('m-img').src=data.img||'';
  document.getElementById('m-img').alt=data.name||'';
  document.getElementById('m-cat').textContent=data.cat||'';
  document.getElementById('m-name').textContent=data.name||'';
  document.getElementById('m-desc').textContent=data.desc||'';
  document.getElementById('m-price').textContent=data.price?Number(data.price).toLocaleString('vi-VN')+' đ':'';
  document.getElementById('modal').classList.add('open');
  document.body.style.overflow='hidden';
}
function closeModal(e){
  if(e&&e.target!==document.getElementById('modal'))return;
  document.getElementById('modal').classList.remove('open');
  document.body.style.overflow='';
}
document.addEventListener('keydown',function(e){if(e.key==='Escape')closeModal(null);});

/* Float booking */
var fb=document.getElementById('floatBook');
var heroH=document.querySelector('.hero').offsetHeight;
window.addEventListener('scroll',function(){
  fb.classList.toggle('show',window.scrollY>heroH*.6);
});
</script>

<?php include __DIR__ . '/views/client/layouts/footer.php'; ?>