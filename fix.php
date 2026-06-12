<?php
$content = file_get_contents('c:/xampp/htdocs/restaurant-project/booking_service.php');

// Fix event_type options
$old_event_types = <<<EOT
                            <select name="event_type" class="input-lux" onchange="us()">
                                <option value="Sinh nhật">Tiệc Sinh Nhật</option>
                                <option value="Kỷ niệm ngày cưới">Kỷ Niệm Ngày Cưới</option>
                                <option value="Tiệc tỏ tình/Cầu hôn">Tỏ Tình / Cầu Hôn</option>
                                <option value="Tiệc công ty/Họp mặt">Họp Mặt / Công Ty</option>
                                <option value="Khác">Khác</option>
                            </select>
EOT;

$new_event_types = <<<EOT
                            <select name="event_type" id="event_type" class="input-lux" onchange="selEvent()">
                                <?php foreach(\$event_types as \$et): ?>
                                    <option value="<?= htmlspecialchars(\$et['name']) ?>" data-id="<?= \$et['id'] ?>"><?= htmlspecialchars(\$et['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
EOT;
$content = str_replace($old_event_types, $new_event_types, $content);

// Fix decor_package options
$old_decor = <<<EOT
                        <div class="input-group-lux">
                            <select name="decor_package" class="input-lux" onchange="us()">
                                <option value="Mặc định">Gói mặc định (Nến & Hoa bàn)</option>
                                <option value="Lãng mạn">Gói lãng mạn (+ Bóng bay, nhạc nhẹ)</option>
                                <option value="Hoàng gia">Gói hoàng gia (+ Rượu vang, backdrop)</option>
                            </select>
                            <label class="label-lux" >Gói trang trí</label>
                        </div>
EOT;

$new_decor = <<<EOT
                        <div class="input-group-lux w-100">
                            <select name="decor_package" id="decor_package" class="input-lux" onchange="us()">
                                <?php 
                                    \$first_event_id = !empty(\$event_types) ? \$event_types[0]['id'] : '';
                                    \$has_decor = false;
                                    foreach(\$decor_pkgs as \$dp) {
                                        if (\$dp['event_type_id'] == \$first_event_id) {
                                            \$price_num = floatval(\$dp['price']);
                                            \$price_str = \$price_num > 0 ? ' (+ ' . number_format(\$price_num, 0, ',', '.') . 'đ)' : '';
                                            echo '<option value="' . \$dp['id'] . '" data-price="' . \$price_num . '" data-name="' . htmlspecialchars(\$dp['name']) . '">' . htmlspecialchars(\$dp['name']) . \$price_str . '</option>';
                                            \$has_decor = true;
                                        }
                                    }
                                    if (!\$has_decor) {
                                        echo '<option value="" data-price="0" data-name="">Không có gói trang trí nào</option>';
                                    }
                                ?>
                            </select>
                            <label class="label-lux">Gói trang trí</label>
                        </div>
EOT;
$content = str_replace($old_decor, $new_decor, $content);

// Add selEvent before us()
$old_us_start = <<<EOT
/* CẬP NHẬT TÓM TẮT */
function us(){
    // Check table availability remotely
EOT;

$new_us_start = <<<EOT
/* CẬP NHẬT TÓM TẮT */
const decorPackages = <?= json_encode(\$decor_pkgs) ?>;

function selEvent() {
    try {
        let evtSelect = document.getElementById('event_type');
        if (!evtSelect || evtSelect.selectedIndex === -1) return;
        
        let opt = evtSelect.options[evtSelect.selectedIndex];
        let eventId = opt.getAttribute('data-id');
        let decorSelect = document.getElementById('decor_package');
        
        if (!decorSelect) return;
        
        let html = '';
        let hasDecor = false;
        decorPackages.forEach(dp => {
            if (dp.event_type_id == eventId) {
                let numPrice = parseInt(dp.price) || 0;
                let priceStr = numPrice > 0 ? ' (+ ' + new Intl.NumberFormat('vi-VN').format(numPrice) + 'đ)' : '';
                html += '<option value="' + dp.id + '" data-price="' + numPrice + '" data-name="' + dp.name + '">' + dp.name + priceStr + '</option>';
                hasDecor = true;
            }
        });
        
        if (!hasDecor) {
            html = '<option value="" data-price="0" data-name="">Không có gói trang trí nào</option>';
        }
        decorSelect.innerHTML = html;
        us();
    } catch (e) {
        console.error("Lỗi selEvent:", e);
    }
}

// Gọi us() ngay khi trang vừa tải
document.addEventListener('DOMContentLoaded', function() {
    us();
});

function us(){
    try {
    // Check table availability remotely
        if (typeof checkTableAvailability === 'function') {
            checkTableAvailability();
        }
EOT;
$content = str_replace($old_us_start, $new_us_start, $content);

// Replace decor logic in us()
$old_decor_logic = <<<EOT
    var evType = document.querySelector('[name="event_type"]');
    var decorPrice = 0;
    if (evType) {
        document.getElementById('m-event-sum').textContent = evType.options ? evType.options[evType.selectedIndex].text : evType.value;
        
        let decorEl = document.getElementById('decor_package');
        if (decorEl && decorEl.options.length > 0 && decorEl.value !== '') {
            let opt = decorEl.options[decorEl.selectedIndex];
            decorPrice = parseFloat(opt.getAttribute('data-price')) || 0;
            let decorName = opt.getAttribute('data-name') || 'Gói trang trí';
            
            let cake = document.querySelector('[name="has_cake"]');
            let flower = document.querySelector('[name="has_flower"]');
            let isCake = cake ? cake.checked : false;
            let isFlower = flower ? flower.checked : false;
            
            if (isCake) decorPrice += 300000;
            if (isFlower) decorPrice += 200000;

            let addonTxt = decorName;
            if (isCake) addonTxt += ' + Bánh';
            if (isFlower) addonTxt += ' + Hoa';
            document.getElementById('m-addon-sum').textContent = addonTxt;
        } else {
            document.getElementById('m-addon-sum').textContent = '—';
        }
    }
EOT;

// I need to use the old string exactly as it is in the reverted file! Wait, what is the exact string in the reverted file?
// In the reverted file, the logic uses string matching!
// Let's replace the string matching logic.
$old_decor_logic_reverted = <<<EOT
    var evType = document.querySelector('[name="event_type"]');
    var decorPrice = 0;
    if (evType) {
        document.getElementById('m-event-sum').textContent = evType.value;
        
        let decor = document.querySelector('[name="decor_package"]').value;
        let cake = document.querySelector('[name="has_cake"]').checked;
        let flower = document.querySelector('[name="has_flower"]').checked;
        
        if (decor.includes('Mặc định')) decorPrice = 500000;
        else if (decor.includes('Lãng mạn')) decorPrice = 1500000;
        else if (decor.includes('Hoàng gia')) decorPrice = 3000000;
        
        if (cake) decorPrice += 300000;
        if (flower) decorPrice += 200000;

        let addonTxt = decor.split(' ')[0];
        if (cake) addonTxt += ' + Bánh';
        if (flower) addonTxt += ' + Hoa';
        document.getElementById('m-addon-sum').textContent = addonTxt;
    }
EOT;

$new_decor_logic = <<<EOT
    var evType = document.querySelector('[name="event_type"]');
    var decorPrice = 0;
    if (evType) {
        let eventSum = document.getElementById('m-event-sum');
        if (eventSum) {
            if (evType.options && evType.selectedIndex >= 0) {
                eventSum.textContent = evType.options[evType.selectedIndex].text;
            } else {
                eventSum.textContent = evType.value || '—';
            }
        }
        
        let decorEl = document.querySelector('[name="decor_package"]');
        let decorName = 'Mặc định';
        if (decorEl && decorEl.options && decorEl.selectedIndex >= 0 && decorEl.value !== '') {
            let opt = decorEl.options[decorEl.selectedIndex];
            decorPrice = parseFloat(opt.getAttribute('data-price')) || 0;
            decorName = opt.getAttribute('data-name') || 'Gói trang trí';
        }

        let cakeEl = document.querySelector('[name="has_cake"]');
        let flowerEl = document.querySelector('[name="has_flower"]');
        let cake = cakeEl ? cakeEl.checked : false;
        let flower = flowerEl ? flowerEl.checked : false;
        
        if (cake) decorPrice += 300000;
        if (flower) decorPrice += 200000;

        let addonTxt = decorName;
        if (cake) addonTxt += ' + Bánh';
        if (flower) addonTxt += ' + Hoa';
        let addonSum = document.getElementById('m-addon-sum');
        if (addonSum) addonSum.textContent = addonTxt;
    }
EOT;
$content = str_replace($old_decor_logic_reverted, $new_decor_logic, $content);

// Replace end of us()
$old_us_end = <<<EOT
    var bespokePrice = 0;
    
    // Tính ngân sách thiết kế riêng (nếu có)
    var budgetSel = document.getElementById('chef_budget');
    if (budgetSel && typeStr === 'bespoke') {
        var opt = budgetSel.options[budgetSel.selectedIndex];
        var bPrice = parseInt(opt.getAttribute('data-price')) || 0;
        bespokePrice += bPrice * guestsNum;
    }
    var cCandle = document.getElementById('bespoke-candle');
    var cFlower = document.getElementById('bespoke-flower');
    var cCard = document.getElementById('bespoke-card');
    
    if (cCandle && cCandle.checked) bespokePrice += 50000;
    if (cFlower && cFlower.checked) bespokePrice += 200000;
    if (cCard && cCard.checked) bespokePrice += 30000;
    
    var sBespoke = document.getElementById('s-bespoke');
    if (sBespoke) sBespoke.textContent = bespokePrice > 0 ? bespokePrice.toLocaleString('vi-VN') + ' đ' : '0 đ';

    var total = food + (typeof selPrice !== 'undefined' ? selPrice : 0) + decorPrice + bespokePrice + chefServiceFee;
    
    // Cập nhật số tiền đặt cọc 30%
    var btnGo = document.getElementById('btn-go');
    if (sid === -1 && total === 0) {
        document.getElementById('sdep').innerHTML = '<span style="font-size:1.2rem; color:var(--gold);">Liên hệ báo giá cọc</span>';
        if (btnGo) {
            var btnTxt = document.getElementById('btn-txt');
            if (btnTxt) {
                btnTxt.innerHTML = 'Gửi Yêu Cầu Thiết Kế Thực Đơn';
            }
        }
    } else {
        var deposit = Math.ceil(total * 0.3);
        document.getElementById('sdep').innerHTML = deposit.toLocaleString('vi-VN')+'<span style="font-size:1.2rem; color:#fff;"> đ</span>';
        
        if (btnGo) {
            var btnTxt = document.getElementById('btn-txt');
            if (btnTxt) {
                if (deposit > 0) {
                    btnTxt.innerHTML = 'Xác nhận & Thanh toán cọc (' + deposit.toLocaleString('vi-VN') + ' đ)';
                } else {
                    btnTxt.innerHTML = 'Gửi Yêu Cầu Đặt Chỗ';
                }
            }
        }
    }
}
EOT;

$new_us_end = <<<EOT
    var bespokePrice = 0;
    
    // Tính ngân sách thiết kế riêng (nếu có)
    var budgetSel = document.getElementById('chef_budget');
    if (budgetSel && typeStr === 'bespoke' && budgetSel.options && budgetSel.selectedIndex >= 0) {
        var opt = budgetSel.options[budgetSel.selectedIndex];
        var bPrice = parseInt(opt.getAttribute('data-price')) || 0;
        bespokePrice += bPrice * guestsNum;
    }
    var cCandle = document.getElementById('bespoke-candle');
    var cFlower = document.getElementById('bespoke-flower');
    var cCard = document.getElementById('bespoke-card');
    
    if (cCandle && cCandle.checked) bespokePrice += 50000;
    if (cFlower && cFlower.checked) bespokePrice += 200000;
    if (cCard && cCard.checked) bespokePrice += 30000;
    
    var sBespoke = document.getElementById('s-bespoke');
    if (sBespoke) sBespoke.textContent = bespokePrice > 0 ? bespokePrice.toLocaleString('vi-VN') + ' đ' : '0 đ';

    var total = food + (typeof selPrice !== 'undefined' ? selPrice : 0) + decorPrice + bespokePrice + chefServiceFee;
    
    // Cập nhật số tiền đặt cọc 30%
    var btnGo = document.getElementById('btn-go');
    if (sid === -1 && total === 0) {
        document.getElementById('sdep').innerHTML = '<span style="font-size:1.2rem; color:var(--gold);">Liên hệ báo giá cọc</span>';
        if (btnGo) {
            var btnTxt = document.getElementById('btn-txt');
            if (btnTxt) {
                btnTxt.innerHTML = 'Gửi Yêu Cầu Thiết Kế Thực Đơn';
            }
        }
    } else {
        var deposit = Math.ceil(total * 0.3);
        document.getElementById('sdep').innerHTML = deposit.toLocaleString('vi-VN')+'<span style="font-size:1.2rem; color:#fff;"> đ</span>';
        
        if (btnGo) {
            var btnTxt = document.getElementById('btn-txt');
            if (btnTxt) {
                if (deposit > 0) {
                    btnTxt.innerHTML = 'Xác nhận & Thanh toán cọc (' + deposit.toLocaleString('vi-VN') + ' đ)';
                } else {
                    btnTxt.innerHTML = 'Gửi Yêu Cầu Đặt Chỗ';
                }
            }
        }
    }
    } catch (e) {
        console.error("Lỗi us():", e);
    }
}
EOT;
$content = str_replace($old_us_end, $new_us_end, $content);

file_put_contents('c:/xampp/htdocs/restaurant-project/booking_service.php', $content);
echo "DONE";
