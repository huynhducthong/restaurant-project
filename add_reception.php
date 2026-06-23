<?php
$file = 'views/shared/floor_plan.php';
$content = file_get_contents($file);

// 1. Add CSS for Reception and Lounge
$search_css = '/* The rest of Table CSS */';
$replace_css = <<<EOD
/* Entrance Decor: Reception & Lounge */
.cad-reception {
    position: absolute;
    right: 40px; top: 20px;
    width: 140px; height: 50px;
    background: #f5f5f5;
    border: 2px solid #a89f91;
    border-radius: 4px;
    display: flex; align-items: center; justify-content: center;
    font-size: 10px; font-weight: bold; color: #555;
    letter-spacing: 1px;
}
.cad-reception::before {
    /* The counter top */
    content: "";
    position: absolute;
    left: -2px; bottom: -2px;
    width: calc(100% + 4px); height: 15px;
    background: #e8dcc8;
    border: 2px solid #a89f91;
    border-radius: 0 0 4px 4px;
}
.reception-chair {
    position: absolute;
    width: 20px; height: 20px;
    background: #fff;
    border: 2px solid #888;
    border-radius: 50%;
    top: -10px; left: 50%; transform: translateX(-50%);
}

.cad-sofa-v {
    position: absolute;
    width: 25px; height: 60px;
    background: #d4c8b8;
    border: 2px solid #a89f91;
    border-radius: 8px;
}
.cad-coffee-table {
    position: absolute;
    width: 40px; height: 30px;
    background: #fff;
    border: 2px solid #a89f91;
    border-radius: 4px;
}

/* The rest of Table CSS */
EOD;

if (strpos($content, '.cad-reception') === false) {
    $content = str_replace($search_css, $replace_css, $content);
}

// 2. Modify Entrance HTML
$search_html = <<<EOD
        <!-- Entrance -->
        <div class="fp-cad-room cad-entrance">
            <div style="position:absolute; top:10px; left:10px; font-size:9px; color:#888; letter-spacing: 2px; font-weight: bold;">WAITING LOUNGE</div>
            SẢNH ĐÓN KHÁCH
            <div style="position: absolute; bottom: 0; left: 50%; transform: translateX(-50%); width: 120px; height: 30px; border: 2px dashed #A88746; border-bottom: none; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: bold; color: #A88746;">LỐI VÀO CHÍNH</div>
        </div>
EOD;

$replace_html = <<<EOD
        <!-- Entrance -->
        <div class="fp-cad-room cad-entrance">
            <!-- Waiting Lounge (Left) -->
            <div style="position:absolute; top:5px; left:10px; font-size:9px; color:#888; letter-spacing: 2px; font-weight: bold;">WAITING LOUNGE</div>
            <div class="cad-sofa-v" style="left: 20px; top: 25px;"></div>
            <div class="cad-coffee-table" style="left: 60px; top: 40px;"></div>
            <div class="cad-sofa-v" style="left: 115px; top: 25px;"></div>
            
            <!-- Center Label -->
            <div style="position: absolute; top: 25px; width: 100%; text-align: center;">SẢNH ĐÓN KHÁCH</div>
            <div style="position: absolute; bottom: 0; left: 50%; transform: translateX(-50%); width: 120px; height: 30px; border: 2px dashed #A88746; border-bottom: none; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: bold; color: #A88746;">LỐI VÀO CHÍNH</div>
            
            <!-- Reception / Cashier (Right) -->
            <div class="cad-reception">
                <div class="reception-chair"></div>
                QUẦY THU NGÂN
            </div>
        </div>
EOD;

$content = str_replace($search_html, $replace_html, $content);

file_put_contents($file, $content);
echo "Added Reception and Waiting Lounge furniture.";
