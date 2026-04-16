/**
 * RESTAURANT PROJECT - MAIN JAVASCRIPT
 * Tích hợp: Tính toán tiền, Điều hướng Header, Quản lý kho và Sơ đồ bàn
 */

$(document).ready(function() {
    
    // --- 1. HÀM TÍNH TOÁN TỔNG TIỀN & ĐẶT CỌC (CLIENT) ---
    function calculateTotal() {
        // A. Lấy giá bàn từ Dropdown (dựa vào data-price của option đang chọn)
        let tableSelect = $('#table_id').find(":selected");
        let tablePrice = parseFloat(tableSelect.data('price')) || 0;
        
        // B. Lấy giá Combo (Quét thẻ combo đang được active/chọn)
        let comboPrice = 0;
        let activeCombo = $('.combo-card-item.active');
        if (activeCombo.length > 0) {
            comboPrice = parseFloat(activeCombo.data('price')) || 0;
        }

        // C. Lấy giá các món lẻ (Duyệt qua các checkbox thực đơn đã tích)
        let menuPrice = 0;
        $('.menu-item-row').each(function() {
            const row = $(this);
            const checkbox = row.find('.menu-check');
            
            if (checkbox.is(':checked')) {
                // Xóa các ký tự không phải số (ví dụ "50.000đ" -> 50000)
                const priceText = row.find('.menu-price').text().replace(/[^0-9]/g, '');
                const price = parseFloat(priceText) || 0;
                const qty = parseInt(row.find('.menu-quantity').val()) || 1;
                menuPrice += (price * qty);
            }
        });

        // TỔNG TIỀN = Phí bàn + Giá Combo + Tổng món lẻ
        const total = tablePrice + comboPrice + menuPrice;
        const deposit = total * 0.3; // Đặt cọc 30%

        // Cập nhật hiển thị lên giao diện (định dạng tiền tệ VN)
        $('#display-table-price').text(tablePrice.toLocaleString('vi-VN') + 'đ');
        $('#display-menu-price').text((comboPrice + menuPrice).toLocaleString('vi-VN') + 'đ');
        $('#display-deposit').text(deposit.toLocaleString('vi-VN') + 'đ');
    }

    // --- 2. XỬ LÝ SỰ KIỆN GIAO DIỆN (HEADER & SCROLL) ---

    function handleHeaderScroll() {
        if ($(window).scrollTop() > 50) {
            // Khi cuộn xuống: Lấp đầy khoảng trống Topbar và đổi màu nền đặc
            $('#header').addClass('header-scrolled');
            $('#topbar').addClass('topbar-scrolled');
        } else {
            // Khi ở trên cùng: Trở về trạng thái trong suốt/có khoảng hở
            $('#header').removeClass('header-scrolled');
            $('#topbar').removeClass('topbar-scrolled');
        }
    }

    // Kiểm tra ngay khi load trang
    handleHeaderScroll();

    // Lắng nghe sự kiện cuộn chuột
    $(window).on('scroll', function() {
        handleHeaderScroll();
    });

    // --- 3. XỬ LÝ CLICK & THAY ĐỔI DỮ LIỆU ---

    // Click chọn bàn trên sơ đồ
    $(document).on('click', '.map-seat.available', function() {
        const guests = parseInt($('#guests_input').val()) || 1;
        const capacity = parseInt($(this).data('capacity'));
        const tableId = $(this).data('id');

        if (guests > capacity) {
            alert(`Vị trí này chỉ tối đa ${capacity} người. Vui lòng chọn khu vực khác!`);
            return;
        }

        $('.map-seat').removeClass('selected');
        $(this).addClass('selected');

        // Đồng bộ ID bàn vào thẻ select và tính lại tiền
        $('#table_id').val(tableId).trigger('change');
    });

    // Click chọn Combo
    $(document).on('click', '.combo-card-item', function() {
        // Đợi một chút để class 'active' được cập nhật rồi tính tiền
        setTimeout(calculateTotal, 100);
    });

    // Tự động vô hiệu hóa bàn trên sơ đồ nếu số khách vượt quá sức chứa
    $('#guests_input').on('input', function() {
        const guests = parseInt($(this).val()) || 0;
        
        $('.map-seat').each(function() {
            const capacity = parseInt($(this).data('capacity'));
            if (guests > capacity && $(this).hasClass('available')) {
                $(this).removeClass('available').addClass('unavailable');
            } else if (guests <= capacity && $(this).hasClass('unavailable')) {
                $(this).removeClass('unavailable').addClass('available');
            }
        });
        calculateTotal();
    });

    // Lắng nghe thay đổi chung để tính lại tiền
    $(document).on('change input', '#table_id, .menu-check, .menu-quantity', function() {
        calculateTotal();
    });

    // --- 4. LOGIC ĐẾM NGƯỢC (ADMIN - QUẢN LÝ BÀN) ---
    function updateCountdowns() {
        $('.admin-seat.seat-booked').each(function() {
            const startTime = parseInt($(this).data('start'));
            const durationMinutes = parseInt($(this).data('duration'));
            const tableId = $(this).data('id');
            
            if (startTime > 0 && durationMinutes > 0) {
                const now = Math.floor(Date.now() / 1000);
                const endTime = startTime + (durationMinutes * 60);
                const timeLeft = endTime - now;

                if (timeLeft > 0) {
                    const m = Math.floor(timeLeft / 60);
                    const s = timeLeft % 60;
                    $(`#timer-${tableId}`).html(`<i class="fa fa-clock me-1"></i>${m}p ${s < 10 ? '0' + s : s}s`);
                } else {
                    $(`#timer-${tableId}`).html('<i class="fa fa-hourglass-end me-1"></i>Hết giờ');
                    $(this).css('border', '2px dashed #ff4444'); 
                }
            }
        });
    }

    // --- 5. KHỞI CHẠY ---
    setInterval(updateCountdowns, 1000);
    calculateTotal();
    updateCountdowns();
});