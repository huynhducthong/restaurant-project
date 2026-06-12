<?php
$is_admin = $is_admin ?? false;
// Fallback if data is not passed
$t_open = $t_open ?? [];
$t_room = $t_room ?? [];
$all_tables = array_merge($t_open, $t_room);
?>
<style>
.fp-container {
    width: 1200px; /* Mở rộng chiều ngang thành hình chữ nhật */
    height: 800px;
    background: var(--bg-cream, #F6F2E9);
    position: relative;
    margin: 0 auto;
    font-family: 'Inter', sans-serif;
    color: var(--text-main, #222222);
    /* Bỏ lưới CAD, thêm border viền mờ tinh tế và shadow nhẹ */
    border: 1px solid rgba(201, 166, 107, 0.3);
    box-shadow: 0 20px 50px rgba(0,0,0,0.05);
    border-radius: 2px; /* Không bo tròn nhiều để giữ nét thanh lịch */
}
.fp-area {
    position: absolute;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    font-family: 'Cormorant Garamond', serif;
    font-size: 15px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 3px;
    color: var(--gold, #C9A66B);
    background: rgba(201, 166, 107, 0.03); /* Nền gold cực kỳ nhạt */
    border: 1px solid rgba(201, 166, 107, 0.2); /* Viền gold nhạt, nét liền, không dashed */
    pointer-events: none;
}
.fp-entrance { bottom: 0; left: 400px; width: 200px; height: 50px; border-bottom: none; }
.fp-reception { bottom: 70px; left: 400px; width: 200px; height: 60px; }
.fp-bar { top: 300px; left: 20px; width: 100px; height: 250px; }
.fp-stage { bottom: 20px; left: 20px; width: 250px; height: 120px; border-radius: 150px 150px 0 0; border-bottom: none; }
.fp-restroom { top: 20px; left: 20px; width: 120px; height: 120px; }
.fp-wine-cellar { top: 20px; left: 810px; width: 280px; height: 140px; border-radius: 4px; border: 1px solid rgba(201, 166, 107, 0.3); background: rgba(201, 166, 107, 0.05); }

/* BOH Areas (Quản trị viên) */
.fp-boh-pass { top: 120px; left: 350px; width: 350px; height: 40px; border: 1px solid #ff4757; color: #ff4757; background: rgba(255, 71, 87, 0.05); z-index: 2; font-family: 'Inter', sans-serif; letter-spacing: 1px; font-size: 12px;}
.fp-boh-hot { top: 20px; left: 350px; width: 170px; height: 90px; border: 1px solid #ff4757; color: #ff4757; background: rgba(255, 71, 87, 0.02); font-family: 'Inter', sans-serif; letter-spacing: 1px; font-size: 12px;}
.fp-boh-cold { top: 20px; left: 530px; width: 170px; height: 90px; border: 1px solid #1e90ff; color: #1e90ff; background: rgba(30, 144, 255, 0.02); font-family: 'Inter', sans-serif; letter-spacing: 1px; font-size: 12px;}

/* Thiết kế BÀN ĂN FINE DINING */
.fp-table {
    position: absolute;
    background: #ffffff; 
    border: 2px solid var(--forest, #4F5B3A); /* Viền màu Olive sang trọng */
    border-radius: 0; /* Bàn vuông/chữ nhật theo yêu cầu */
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center; 
    cursor: pointer; 
    transition: all 0.4s cubic-bezier(0.25, 1, 0.5, 1);
    transform: translate(-50%, -50%);
    z-index: 10;
    box-shadow: 0 10px 20px rgba(79, 91, 58, 0.08); /* Đổ bóng nhẹ nhàng */
}

/* Các phòng VIP sẽ là hình chữ nhật bo góc nhẹ */
.fp-table[data-cat="room"] {
    border-radius: 8px;
}

.fp-table.available:hover { 
    background: var(--forest, #4F5B3A);
    color: #ffffff;
    transform: translate(-50%, -50%) scale(1.08); 
    z-index: 12; 
    box-shadow: 0 15px 30px rgba(79, 91, 58, 0.2); 
}
.fp-table.available:hover .fp-t-code,
.fp-table.available:hover .fp-t-cap {
    color: #ffffff;
}

.fp-table.booked { 
    background: var(--forest, #4F5B3A);
    border: 2px solid var(--forest, #4F5B3A);
    color: #ffffff;
    cursor: not-allowed; 
    box-shadow: none;
    opacity: 0.8;
}
.fp-table.booked .fp-t-code,
.fp-table.booked .fp-t-cap {
    color: rgba(255,255,255,0.8);
}
.fp-table.booked:hover {
    transform: translate(-50%, -50%);
}

.fp-table.selected { 
    background: var(--gold, #C9A66B) !important; 
    border-color: var(--gold, #C9A66B) !important; 
    transform: translate(-50%, -50%) scale(1.08); 
    z-index: 15; 
    box-shadow: 0 15px 30px rgba(201, 166, 107, 0.3);
}

.fp-t-code { 
    font-family: 'Cormorant Garamond', serif; 
    font-size: 1.6rem; /* Chữ to, rõ ràng, quyền lực */
    color: var(--forest, #4F5B3A); 
    display: block; 
    font-weight: 600;
    line-height: 1;
    transition: color 0.4s ease;
}
.fp-table.selected .fp-t-code { color: #ffffff; }

.fp-t-cap { 
    font-size: 11px; 
    color: var(--text-muted, #666666); 
    display: flex; 
    align-items: center;
    gap: 4px;
    margin-top: 6px;
    font-weight: 500;
    transition: color 0.4s ease;
}
.fp-table.selected .fp-t-cap { color: rgba(255,255,255,0.9); }

/* Legend gọn gàng */
.fp-legend {
    position: absolute;
    bottom: 25px;
    right: 25px;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--text-main, #222222);
    background: rgba(255,255,255,0.9);
    padding: 15px 20px;
    border-radius: 4px;
    border: 1px solid rgba(201, 166, 107, 0.2);
    z-index: 20;
    display: flex;
    gap: 20px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}
.fp-legend-item {
    display: flex;
    align-items: center;
}
.fp-legend-box {
    width: 14px; height: 14px; margin-right: 8px; border-radius: 50%;
}
</style>

<div style="overflow-x: auto; padding: 0; display: flex; justify-content: center; background: var(--bg-cream, #F6F2E9);">
    <div class="fp-container" id="floor-plan-canvas">
        <!-- Static Areas -->
        <div class="fp-area fp-entrance">Lối Vào</div>
        <div class="fp-area fp-reception">Lễ Tân</div>
        <div class="fp-area fp-bar"><div style="transform: rotate(-90deg); white-space: nowrap;">Cocktail Bar</div></div>
        
        <!-- Bar Stools -->
        <div style="position:absolute; top:360px; left:135px; width:22px; height:22px; border-radius:50%; border:1px solid rgba(201,166,107,0.5); background:rgba(201,166,107,0.1);"></div>
        <div style="position:absolute; top:405px; left:135px; width:22px; height:22px; border-radius:50%; border:1px solid rgba(201,166,107,0.5); background:rgba(201,166,107,0.1);"></div>
        <div style="position:absolute; top:450px; left:135px; width:22px; height:22px; border-radius:50%; border:1px solid rgba(201,166,107,0.5); background:rgba(201,166,107,0.1);"></div>

        <div class="fp-area fp-stage">Grand Piano</div>
        <div class="fp-area fp-restroom">Restroom</div>
        <div class="fp-area fp-wine-cellar"><i class="fas fa-wine-glass-alt me-2"></i>Hầm Rượu Vang<br><span style="font-size:10px; opacity:0.7; letter-spacing:1px; margin-top:5px; display:block;">(Premium Wine Cellar)</span></div>

        <!-- VIP Area Wrapper -->
        <div class="fp-area" style="top:210px; left:810px; width:280px; height:450px; background:rgba(201,166,107,0.02); border:1px solid rgba(201,166,107,0.1); border-radius:8px; z-index: 1;">
            <div style="position:absolute; top:-10px; background:var(--bg-cream, #F6F2E9); padding:0 10px; color:var(--gold, #C9A66B); font-size:12px; letter-spacing:2px; font-weight:700;">PRIVATE VIP ROOMS</div>
        </div>

        <!-- Back of House (Open Kitchen Style) -->
        <div class="fp-area fp-boh-pass"><i class="fas fa-bell me-2"></i> Trạm Ra Món (The Pass)</div>
        <div class="fp-area fp-boh-hot"><i class="fas fa-fire me-2"></i>Bếp Nóng (Hot Station)</div>
        <div class="fp-area fp-boh-cold"><i class="fas fa-snowflake me-2"></i>Bếp Lạnh / Bánh</div>

        <!-- Dynamic Tables -->
        <?php foreach ($all_tables as $t): ?>
            <?php 
                $st = $t['is_available'] ? 'available' : 'booked';
                // Dùng hình tròn cho bàn sảnh, chữ nhật cho VIP
                $cat = $t['category'] ?? 'open';
                $w = $cat === 'room' ? 100 : 70;
                $h = $cat === 'room' ? 80 : 70;
                $x = $t['pos_x'] ?? 0;
                $y = $t['pos_y'] ?? 0;
            ?>
            <!-- Bắt buộc phải có class 'seat-lux' để kế thừa JS cũ -->
            <div class="fp-table <?= $st ?> seat-lux" 
                 style="width: <?= $w ?>px; height: <?= $h ?>px; left: <?= $x ?>px; top: <?= $y ?>px;"
                 data-id="<?= $t['id'] ?>" 
                 data-price="<?= $t['price'] ?>" 
                 data-code="<?= htmlspecialchars($t['table_code']) ?>" 
                 data-cat="<?= $cat ?>"
                 title="<?= htmlspecialchars($t['table_code']) ?> - <?= $st === 'available' ? 'Trống' : 'Đã đặt' ?>">
                <span class="fp-t-code"><?= htmlspecialchars(str_replace('VIP', 'V', $t['table_code'])) ?></span>
            </div>
        <?php endforeach; ?>

        <div class="fp-legend">
            <div class="fp-legend-item"><div class="fp-legend-box" style="background: #ffffff; border: 2px solid var(--forest, #4F5B3A);"></div> Trống</div>
            <div class="fp-legend-item"><div class="fp-legend-box" style="background: var(--gold, #C9A66B);"></div> Đang chọn</div>
            <div class="fp-legend-item"><div class="fp-legend-box" style="background: var(--forest, #4F5B3A); opacity: 0.8;"></div> Đã đặt</div>
        </div>
    </div>
</div>
