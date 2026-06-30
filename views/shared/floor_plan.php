<?php
$is_admin = $is_admin ?? false;
$t_open = $t_open ?? [];
$t_room = $t_room ?? [];
$all_tables = array_merge($t_open, $t_room);
?>
<style>
.fp-container {
    width: 1150px; 
    height: 800px;
    background-color: #ffffff;
    /* Lưới CAD Background */
    background-image: 
        linear-gradient(rgba(17, 63, 54, 0.05) 1px, transparent 1px), 
        linear-gradient(90deg, rgba(17, 63, 54, 0.05) 1px, transparent 1px);
    background-size: 20px 20px;
    background-position: center center;
    position: relative;
    margin: 0 auto;
    font-family: 'Source Sans 3', sans-serif;
    color: var(--text-main, #333333);
    border: 3px solid #113f36; /* Outer building wall */
    box-shadow: 0 20px 50px rgba(0,0,0,0.1);
}

/* CAD Static Areas (Solid walls) */
.fp-cad-room {
    position: absolute;
    border: 2px solid #113f36;
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    font-weight: bold;
    font-size: 11px;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: #A88746;
    z-index: 1;
}
.fp-cad-room::after {
    content: attr(data-title);
    position: absolute;
    top: 8px; left: 10px;
    font-size: 10px; color: #A88746;
}

/* Specific Rooms */
.cad-v3 { top: 0; left: 0; width: 250px; height: 180px; }
.cad-restroom { top: 0; left: 250px; width: 150px; height: 120px; border-left: none; }
.cad-hot { top: 0; left: 400px; width: 250px; height: 120px; border-left: none; color: #d63031; }
.cad-cold { top: 0; left: 650px; width: 200px; height: 120px; border-left: none; color: #0984e3; }
.cad-pass { top: 120px; left: 400px; width: 450px; height: 40px; border-top: none; color: #d63031; }
.cad-v2 { top: 0; left: 850px; width: 296px; height: 200px; border-left: none; } /* 1150-850=300 minus borders */

.cad-bar { top: 200px; left: 950px; width: 196px; height: 350px; border-top: none; }
.cad-v4 { top: 550px; left: 850px; width: 296px; height: 246px; border-bottom: none; border-right: none;} /* 800-550=250 */

.cad-v1 { top: 550px; left: 0; width: 250px; height: 246px; border-bottom: none; border-left: none;}
.cad-lounge { top: 680px; left: 250px; width: 600px; height: 116px; border-left: none; border-bottom: none;}
.cad-entrance { top: 760px; left: 475px; width: 150px; height: 40px; background: #fff; border: 2px dashed #A88746; border-bottom: none; z-index: 2; color: #A88746;}

/* Bar Styling */
.bar-counter {
    position: absolute;
    top: 25px;
    left: 20px;
    width: 60px;
    height: 300px;
    background: rgba(168, 135, 70, 0.1);
    border: 2px solid #A88746;
    border-radius: 30px;
    box-shadow: inset 0 0 10px rgba(0,0,0,0.05);
}
.bar-stool {
    position: absolute;
    left: -15px; /* Sticking out into the restaurant */
    width: 18px;
    height: 18px;
    background: #fff;
    border: 2px solid #A88746;
    border-radius: 50%;
}
.bar-stool:nth-child(1) { top: 30px; }
.bar-stool:nth-child(2) { top: 70px; }
.bar-stool:nth-child(3) { top: 110px; }
.bar-stool:nth-child(4) { top: 150px; }
.bar-stool:nth-child(5) { top: 190px; }
.bar-stool:nth-child(6) { top: 230px; }
.bar-stool:nth-child(7) { top: 270px; }

/* Window Styling */
.fp-window-h {
    position: absolute;
    height: 8px;
    background: #e0f7fa; /* Light cyan glass */
    border: 1px solid #00bcd4;
    border-radius: 4px;
    z-index: 5;
    box-shadow: 0 0 8px rgba(0, 188, 212, 0.4);
}
.fp-window-v {
    position: absolute;
    width: 8px;
    background: #e0f7fa;
    border: 1px solid #00bcd4;
    border-radius: 4px;
    z-index: 5;
    box-shadow: 0 0 8px rgba(0, 188, 212, 0.4);
}
.fp-plant {
    position: absolute;
    width: 30px; height: 30px;
    background: #e8f5e9;
    border: 2px dashed #4caf50;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    color: #4caf50; font-size: 14px;
}

/* Kitchen Bar Redesign */
.kitchen-i-shape {
    position: absolute;
    top: 25px;
    right: 15px;
    width: 45px;
    height: 300px;
    background: #f5f5f5;
    border: 2px solid #888;
    border-radius: 4px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: space-evenly;
}
.kitchen-island {
    position: absolute;
    top: 75px;
    left: 40px;
    width: 55px;
    height: 200px;
    background: rgba(168, 135, 70, 0.15);
    border: 2px solid #A88746;
    border-radius: 8px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}
.island-stool {
    position: absolute;
    left: -18px;
    width: 18px;
    height: 18px;
    background: #fff;
    border: 2px solid #A88746;
    border-radius: 50%;
}
.island-stool:nth-child(1) { top: 20px; }
.island-stool:nth-child(2) { top: 70px; }
.island-stool:nth-child(3) { top: 120px; }
.island-stool:nth-child(4) { top: 170px; }

.bar-shelves { width: 30px; height: 120px; border: 2px solid rgba(168, 135, 70, 0.5); border-radius: 2px; background: rgba(168, 135, 70, 0.05); display: flex; flex-direction: column; justify-content: space-evenly; }
.shelf-line { width: 100%; height: 2px; background: rgba(168, 135, 70, 0.5); }
.kitchen-sink { width: 30px; height: 50px; border: 2px solid #aaa; border-radius: 4px; background: #cfd8dc; }







/* VIP Decor: Curtains & Paintings */
.vip-curtain-h {
    position: absolute;
    width: 25px; height: 12px;
    background: #c2b5a3;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    z-index: 6;
}
.vip-curtain-v {
    position: absolute;
    width: 12px; height: 25px;
    background: #c2b5a3;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    z-index: 6;
}
.vip-painting-h {
    position: absolute;
    width: 50px; height: 6px;
    background: #bfa184;
    border: 1px solid #8f7259;
    box-shadow: 0 3px 5px rgba(0,0,0,0.15);
    z-index: 2;
}
.vip-painting-v {
    position: absolute;
    width: 6px; height: 50px;
    background: #bfa184;
    border: 1px solid #8f7259;
    box-shadow: 0 3px 5px rgba(0,0,0,0.15);
    z-index: 2;
}


/* Minimalist Entrance Design */
.lounge-floor {
    position: absolute; inset: 0;
    z-index: 0;
}
.coat-check {
    position: absolute; top: -2px; left: -2px; width: 170px; height: 25px;
    background: #fff; border: 2px solid #A88746; border-radius: 4px 0 4px 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 8px; font-weight: bold; color: #555; z-index: 2;
}
.coat-rack {
    position: absolute; top: 21px; left: -2px; width: 35px; height: 80px;
    background: #fff; border: 2px solid #A88746; border-radius: 0 0 4px 4px; z-index: 2;
}
.coat-rack::after {
    content: "||||||||||"; color: #A88746; font-size: 8px; letter-spacing: 2px;
    position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(90deg);
}
.reception-desk {
    position: absolute; bottom: 5px; left: 60px; width: 90px; height: 60px;
    background: #fff; border: 2px solid #A88746; border-radius: 4px;
    z-index: 2;
}
.reception-counter {
    position: absolute; right: 0; top: 0; width: 25px; height: 100%;
    background: #f5f5f5; border-left: 2px solid #A88746; border-radius: 0 4px 4px 0;
}
.reception-chair2 {
    position: absolute; top: 20px; left: 20px; width: 22px; height: 22px;
    background: #fff; border: 2px solid #A88746; border-radius: 50%; z-index: 1;
}
.lounge-rug {
    position: absolute; top: 5px; right: 5px; width: 190px; height: 106px;
    background: transparent;
    border: 1px dashed #A88746; border-radius: 2px; z-index: 1;
}
.sofa-burgundy-h {
    position: absolute; width: 60px; height: 30px;
    background: #fff; border: 2px solid #A88746; border-radius: 8px; z-index: 2;
}
.sofa-burgundy-v {
    position: absolute; width: 30px; height: 60px;
    background: #fff; border: 2px solid #A88746; border-radius: 8px; z-index: 2;
}
.armchair-burgundy {
    position: absolute; width: 30px; height: 30px;
    background: #fff; border: 2px solid #A88746; border-radius: 8px; z-index: 2;
}
.sofa-pillows {
    display: none;
}
.center-coffee-table {
    position: absolute; width: 45px; height: 45px; top: 35px; right: 75px;
    background: #fff;
    border: 2px solid #A88746; border-radius: 50%; z-index: 2;
}
.side-table {
    position: absolute; width: 20px; height: 20px; background: #fff; border: 2px solid #A88746; border-radius: 4px; z-index: 2;
}

/* The rest of Table CSS */
.fp-chair.d-top { top: -10px; left: -10px; }
.fp-chair.d-right { top: -10px; right: -10px; }
.fp-chair.d-bottom { bottom: -10px; right: -10px; }
.fp-chair.d-left { bottom: -10px; left: -10px; }
.fp-piano { position: absolute; top: 370px; left: 550px; width: 100px; height: 100px; border-radius: 50%; border: 2px solid rgba(168, 135, 70, 0.5); background: #fff; display: flex; align-items: center; justify-content: center; z-index: 5;}

.fp-table {
    position: absolute;
    background: #FFFFFF; 
    border: 2px solid #113f36;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center; 
    cursor: pointer; 
    transition: all 0.4s;
    /* transform handled inline if rotated */
    z-index: 10;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}

.fp-chair {
    position: absolute;
    width: 14px;
    height: 14px;
    background: #fff;
    border: 2px solid #113f36;
    border-radius: 50%;
    transition: all 0.3s;
}
.fp-table.available:hover .fp-chair { background: rgba(17, 63, 54, 0.1); }

.fp-chair.top { top: -20px; left: 50%; transform: translateX(-50%); }
.fp-chair.bottom { bottom: -20px; left: 50%; transform: translateX(-50%); }
.fp-chair.left { left: -20px; top: 50%; transform: translateY(-50%); }
.fp-chair.right { right: -20px; top: 50%; transform: translateY(-50%); }
.fp-chair.r-top-left { top: -5px; left: 5px; }
.fp-chair.r-top-right { top: -5px; right: 5px; }
.fp-chair.r-bottom-left { bottom: -5px; left: 5px; }
.fp-chair.r-bottom-right { bottom: -5px; right: 5px; }
.fp-chair.r-mid-left { left: -15px; top: 50%; transform: translateY(-50%); }
.fp-chair.r-mid-right { right: -15px; top: 50%; transform: translateY(-50%); }
.fp-chair.v-top-left { top: -20px; left: 25%; transform: translateX(-50%); }
.fp-chair.v-top-right { top: -20px; left: 75%; transform: translateX(-50%); }
.fp-chair.v-bottom-left { bottom: -20px; left: 25%; transform: translateX(-50%); }
.fp-chair.v-bottom-right { bottom: -20px; left: 75%; transform: translateX(-50%); }
.fp-chair.rect-top-left { top: -20px; left: 25%; transform: translateX(-50%); }
.fp-chair.rect-top-right { top: -20px; left: 75%; transform: translateX(-50%); }
.fp-chair.rect-bottom-left { bottom: -20px; left: 25%; transform: translateX(-50%); }
.fp-chair.rect-bottom-right { bottom: -20px; left: 75%; transform: translateX(-50%); }

.fp-t-code { font-family: 'Cormorant Garamond', serif; font-size: 1.5rem; font-weight: 600; color: #A88746; }
.fp-table.available:hover { background: rgba(17, 63, 54, 0.03); }
.fp-table.booked { background: #f5f5f5; border-color: #aaa; cursor: not-allowed; }
.fp-table.booked .fp-t-code { color: #888; }
.fp-table.booked .fp-chair { border-color: #aaa; }
.fp-table.selected { background: #113f36 !important; border-color: #113f36 !important; z-index: 15; box-shadow: 0 10px 20px rgba(17, 63, 54, 0.3); }
.fp-table.selected .fp-t-code { color: #A88746; }

.fp-legend { margin-top: 20px; font-size: 11px; text-transform: uppercase; font-weight: bold; color: #113f36; background: #fff; padding: 12px 20px; border: 2px solid #113f36; border-radius: 4px; display: flex; gap: 20px; justify-content: center; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
.fp-legend-item { display: flex; align-items: center; }
.fp-legend-box { width: 14px; height: 14px; margin-right: 8px; border-radius: 50%; }
</style>

<div style="overflow-x: auto; padding: 20px; display: flex; flex-direction: column; align-items: center; background: #e0e0e0;">
    <div class="fp-container" id="floor-plan-canvas">
        
        <!-- Connected CAD Rooms -->
                <div class="fp-cad-room cad-v3" data-title="PHÒNG VIP 3">
            <div class="fp-window-h" style="top: -4px; left: 50px; width: 100px;"></div>
            
            <div class="fp-window-v" style="left: -4px; top: 40px; height: 80px;"></div>
            
            <!-- Painting on the right wall -->
            <div class="vip-painting-v" style="top: 60px; right: 0;"></div>
            <div class="vip-painting-h" style="bottom: 0; right: 60px;"></div>
        </div>
        <div class="fp-cad-room cad-restroom">NHÀ VỆ SINH</div>
        <div class="fp-cad-room cad-hot">BẾP NÓNG</div>
        <div class="fp-cad-room cad-cold">BẾP LẠNH</div>
        <div class="fp-cad-room cad-pass">TRẠM PHỤC VỤ (THE PASS)</div>
        
        
                <div class="fp-cad-room cad-bar" data-title="QUẦY BAR MỞ">
            <!-- I-shape Kitchen (Back Wall) -->
            <div class="kitchen-i-shape">
                <div class="bar-shelves">
                    <div class="shelf-line"></div>
                    <div class="shelf-line"></div>
                    <div class="shelf-line"></div>
                    <div class="shelf-line"></div>
                </div>
                <div class="kitchen-sink"></div>
            </div>
            
            <!-- Kitchen Island (Front) -->
            <div class="kitchen-island">
                <div class="island-stool"></div>
                <div class="island-stool"></div>
                <div class="island-stool"></div>
                <div class="island-stool"></div>
                <div style="transform: rotate(-90deg); position: absolute; left: -10px; top: 90px; white-space: nowrap; font-size: 10px; letter-spacing: 2px; color: #A88746;">BAR & KITCHEN</div>
            </div>
            
            <!-- Decorative Plants -->
            <div class="fp-plant" style="top: 10px; left: 10px;"><i class="fas fa-leaf"></i></div>
            <div class="fp-plant" style="bottom: 10px; left: 10px;"><i class="fas fa-leaf"></i></div>
        </div>
        
                <div class="fp-cad-room cad-v4" data-title="PHÒNG VIP 4">
            <div class="fp-window-h" style="bottom: -4px; right: 50px; width: 120px;"></div>
            
            <div class="fp-window-v" style="right: -4px; top: 60px; height: 100px;"></div>
            
            <!-- Painting on the left wall -->
            <div class="vip-painting-v" style="top: 80px; left: 0;"></div>
            <div class="vip-painting-h" style="top: 0; left: 60px;"></div>
        </div>
        
        
        <div class="fp-cad-room cad-lounge" style="border: 2px solid #555; background: transparent;">
            <div class="lounge-floor"></div>
            
            <!-- Left: Coat Check & Reception -->
            <div class="coat-check">COAT CHECK</div>
            <div class="coat-rack"></div>
            <div class="reception-desk">
                <div class="reception-chair2"></div>
                <div class="reception-counter"></div>
            </div>
            
            <!-- Center: Main Entrance -->
            <div style="position: absolute; top: 40px; width: 100%; text-align: center; font-size: 14px; font-weight: 900; letter-spacing: 2px; color: #222; z-index: 2;">KHU VỰC SẢNH ĐÓN</div>

            
            <!-- Lối đi 4 hướng (Crossway) -->
            <div style="position: absolute; top: 0; bottom: 0; left: 240px; right: 240px; border-left: 1px dashed #ccc; border-right: 1px dashed #ccc; z-index: 0;"></div>
            
            <div style="position: absolute; bottom: -35px; left: 50%; transform: translateX(-50%); background: #D32F2F; color: #fff; padding: 4px 12px; border-radius: 4px; font-weight: bold; font-size: 12px; z-index: 10; letter-spacing: 1px; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">▲ LỐI VÀO CHÍNH</div>

            <!-- Right: Waiting Lounge -->
            <div class="lounge-rug">
                <div style="position:absolute; top: 50%; left: 10px; transform: translateY(-50%); font-size: 10px; font-weight: bold; color: #004d40; text-align: center;">WAITING<br>LOUNGE</div>
            </div>
            
            <div class="sofa-burgundy-h" style="top: 10px; right: 65px;">
                <div class="sofa-pillows" style="bottom: 4px; left: 8px;"></div>
                <div class="sofa-pillows" style="bottom: 4px; right: 8px;"></div>
            </div>
            <div class="sofa-burgundy-h" style="bottom: 10px; right: 65px;">
                <div class="sofa-pillows" style="top: 4px; left: 8px;"></div>
                <div class="sofa-pillows" style="top: 4px; right: 8px;"></div>
            </div>
            <div class="sofa-burgundy-v" style="top: 28px; right: 15px;">
                <div class="sofa-pillows" style="left: 4px; top: 8px;"></div>
                <div class="sofa-pillows" style="left: 4px; bottom: 8px;"></div>
            </div>
            
            <div class="armchair-burgundy" style="top: 15px; right: 145px; transform: rotate(45deg);">
                <div class="sofa-pillows" style="bottom: 4px; left: 8px;"></div>
            </div>
            <div class="armchair-burgundy" style="bottom: 15px; right: 145px; transform: rotate(-45deg);">
                <div class="sofa-pillows" style="top: 4px; left: 8px;"></div>
            </div>
            
            <div class="side-table" style="top: 10px; right: 10px;"></div>
            <div class="side-table" style="bottom: 10px; right: 10px;"></div>
            <div class="center-coffee-table"></div>
        </div>
        
                <div class="fp-piano" style="border: none; background: transparent;">
            <svg width="100" height="100" viewBox="0 0 100 100" style="position: absolute; top: 0; left: 0;">
                <path d="M 20 0 L 80 0 A 20 20 0 0 0 100 20 L 100 80 A 20 20 0 0 0 80 100 L 20 100 A 20 20 0 0 0 0 80 L 0 20 A 20 20 0 0 0 20 0 Z" fill="#fff" stroke="#A88746" stroke-width="2"/>
            </svg>
            <i class="fas fa-music" style="font-size: 30px; opacity: 0.5; z-index: 1;"></i>
        </div>
        <div style="position:absolute; top:480px; left:565px; font-size:10px; font-weight:bold; color:#A88746; z-index: 5;">GRAND PIANO</div>

        <?php foreach ($all_tables as $t): ?>
            <?php 
                $st = $t['is_available'] ? 'available' : 'booked';
                $cat = $t['category'] ?? 'open';
                $tCode = $t['table_code'];
                $x = $t['pos_x'] ?? 0;
                $y = $t['pos_y'] ?? 0;

                $cap = (int)($t['capacity'] ?? 2);
                $w = 60; $h = 60; $borderRadius = '4px';
                $shapeType = 'square_med';
                $rotate = '';

                if ($cap == 2) {
                    $w = 60; $h = 60; $borderRadius = '4px';
                    $shapeType = 'square_small';
                } elseif ($cap == 4) {
                    $w = 70; $h = 70; $borderRadius = '4px';
                    $shapeType = 'square_med';
                    if (in_array($tCode, ['W5', 'W6'])) {
                        $rotate = 'rotate(45deg)';
                    }
                } elseif ($cap == 6) {
                    $w = 90; $h = 90; $borderRadius = '50%';
                    $shapeType = 'round_large';
                } elseif ($cap == 8) {
                    $w = 120; $h = 70; $borderRadius = '6px';
                    $shapeType = 'rect_large';
                } elseif ($cap >= 16) {
                    $w = 140; $h = 80; $borderRadius = '8px';
                    $shapeType = 'vip';
                }
            ?>
            <div class="fp-table <?= $st ?> seat-lux" 
                 style="width: <?= $w ?>px; height: <?= $h ?>px; left: <?= $x ?>px; top: <?= $y ?>px; border-radius: <?= $borderRadius ?>; transform: translate(-50%, -50%) <?= !empty($rotate) ? $rotate : '' ?>;"
                 data-id="<?= $t['id'] ?>" data-price="<?= $t['price'] ?>" data-code="<?= htmlspecialchars($tCode) ?>" data-cat="<?= $cat ?>"
                 title="<?= htmlspecialchars($tCode) ?> - <?= $st === 'available' ? 'Trống' : 'Đã đặt' ?>">
                
                <?php if ($shapeType === 'square_small'): ?>
                    <div class="fp-chair left"></div><div class="fp-chair right"></div>
                <?php elseif ($shapeType === 'square_med'): ?>
                    <div class="fp-chair top"></div><div class="fp-chair bottom"></div>
                    <div class="fp-chair left"></div><div class="fp-chair right"></div>
                <?php elseif ($shapeType === 'round_large'): ?>
                    <div class="fp-chair r-top-left"></div><div class="fp-chair r-top-right"></div>
                    <div class="fp-chair r-bottom-left"></div><div class="fp-chair r-bottom-right"></div>
                    <div class="fp-chair r-mid-left"></div><div class="fp-chair r-mid-right"></div>
                <?php elseif ($shapeType === 'rect_large'): ?>
                    <!-- 8 chairs -->
                    <div class="fp-chair" style="top:-20px; left:15%; transform:translateX(-50%);"></div>
                    <div class="fp-chair" style="top:-20px; left:50%; transform:translateX(-50%);"></div>
                    <div class="fp-chair" style="top:-20px; left:85%; transform:translateX(-50%);"></div>
                    <div class="fp-chair" style="bottom:-20px; left:15%; transform:translateX(-50%);"></div>
                    <div class="fp-chair" style="bottom:-20px; left:50%; transform:translateX(-50%);"></div>
                    <div class="fp-chair" style="bottom:-20px; left:85%; transform:translateX(-50%);"></div>
                    <div class="fp-chair left"></div><div class="fp-chair right"></div>
                <?php elseif ($shapeType === 'vip'): ?>
                    <!-- 10+ chairs representation -->
                    <div class="fp-chair" style="top:-20px; left:10%; transform:translateX(-50%);"></div>
                    <div class="fp-chair" style="top:-20px; left:30%; transform:translateX(-50%);"></div>
                    <div class="fp-chair" style="top:-20px; left:50%; transform:translateX(-50%);"></div>
                    <div class="fp-chair" style="top:-20px; left:70%; transform:translateX(-50%);"></div>
                    <div class="fp-chair" style="top:-20px; left:90%; transform:translateX(-50%);"></div>
                    <div class="fp-chair" style="bottom:-20px; left:10%; transform:translateX(-50%);"></div>
                    <div class="fp-chair" style="bottom:-20px; left:30%; transform:translateX(-50%);"></div>
                    <div class="fp-chair" style="bottom:-20px; left:50%; transform:translateX(-50%);"></div>
                    <div class="fp-chair" style="bottom:-20px; left:70%; transform:translateX(-50%);"></div>
                    <div class="fp-chair" style="bottom:-20px; left:90%; transform:translateX(-50%);"></div>
                    <div class="fp-chair left"></div><div class="fp-chair right"></div>
                <?php endif; ?>

                                <div style="<?= !empty($rotate) ? 'transform: rotate(-45deg); margin-top:20px;' : '' ?>">
                    <span class="fp-t-code"><?= htmlspecialchars(str_replace('VIP', 'V', $tCode)) ?></span>
                </div>
                <?php if (in_array($tCode, ['W1', 'W2', 'W3', 'W4']) || $cat === 'room'): ?>
                    <i class="fas fa-gem" style="position: absolute; top: -8px; right: -8px; font-size: 12px; color: #A88746; background: #fff; border-radius: 50%; padding: 4px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);"></i>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="fp-legend">
        <div class="fp-legend-item"><div class="fp-legend-box" style="background: #ffffff; border: 2px solid #113f36;"></div> TRỐNG</div>
        <div class="fp-legend-item"><div class="fp-legend-box" style="background: #113f36; border: 2px solid #113f36;"></div> ĐANG CHỌN</div>
        <div class="fp-legend-item"><div class="fp-legend-box" style="background: #f5f5f5; border: 2px solid #aaa;"></div> ĐÃ ĐẶT</div>
    </div>
</div>