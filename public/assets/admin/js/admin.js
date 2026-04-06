$(document).ready(function() {

    // Sự kiện click nút Thêm nguyên liệu khác trong Modal
    $('#btn-add-ingredient-row').click(function() {
        addIngredientRow(); 
    });
    
    // --- PHẦN 2: ĐỊNH MỨC MÓN ĂN (Recipes) ---
    function addIngredientRow(data = null) {
        const rowId = Date.now();
        
        // 1. Tạo danh sách nguyên liệu
        let optionsHtml = '<option value="">-- Chọn nguyên liệu --</option>';
        if (window.allIngredients) {
            window.allIngredients.forEach(ing => {
                optionsHtml += `<option value="${ing.id}">${ing.item_name} (${ing.unit_name})</option>`;
            });
        }

        // 2. TẠO DANH SÁCH ĐƠN VỊ TỪ DATABASE (Sửa lỗi thiếu đơn vị 'chai')
        let unitOptions = '';
        if (window.allUnits && window.allUnits.length > 0) {
            window.allUnits.forEach(unit => {
                unitOptions += `<option value="${unit}">${unit}</option>`;
            });
        } else {
            // Phòng hờ trường hợp biến allUnits không tồn tại
            unitOptions = '<option value="kg">kg</option><option value="g">g</option><option value="L">L</option><option value="ml">ml</option>';
        }

        // 3. Ghép vào chuỗi HTML
        const html = `
            <div class="row mb-2 ingredient-row align-items-end" id="row-${rowId}">
                <div class="col-md-5">
                    <label class="small text-muted mb-1">Nguyên liệu</label>
                    <select name="ingredients[]" class="form-select border-0 bg-light shadow-sm" required>${optionsHtml}</select>
                </div>
                <div class="col-md-3">
                    <label class="small text-muted mb-1">Số lượng</label>
                    <input type="number" name="quantities[]" class="form-control border-0 bg-light shadow-sm" step="0.001" required placeholder="0.000">
                </div>
                <div class="col-md-3">
                    <label class="small text-muted mb-1">Đơn vị tính</label>
                    <select name="units[]" class="form-select border-0 bg-light shadow-sm">
                        ${unitOptions}
                    </select>
                </div>
                <div class="col-md-1 text-center">
                    <button type="button" class="btn btn-link text-danger p-0 mb-2" onclick="$('#row-${rowId}').remove()">
                        <i class="fas fa-times-circle fa-lg"></i>
                    </button>
                </div>
            </div>`;

        $('#recipe-items-list').append(html);

        // 4. Nếu là load dữ liệu cũ để sửa
        if(data) {
            const row = $(`#row-${rowId}`);
            row.find('select[name="ingredients[]"]').val(data.ingredient_id);
            row.find('input[name="quantities[]"]').val(data.quantity_required);
            row.find('select[name="units[]"]').val(data.unit);
        }
    }

    // Các phần confirm booking, view detail và save recipe giữ nguyên như cũ...
    $(document).on('click', '.btn-add-recipe', function() {
        const foodId = $(this).data('id');
        $('#recipe-food-id').val(foodId);
        $('#recipe-food-name').text($(this).data('name'));
        $('#recipe-items-list').empty();
        $.getJSON('ajax_get_recipes.php?food_id=' + foodId, function(data) {
            if(data && data.length > 0) {
                data.forEach(item => addIngredientRow(item));
            } else {
                addIngredientRow();
            }
        });
        new bootstrap.Modal(document.getElementById('modalRecipe')).show();
    });

    $('#form-save-recipe').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: 'ajax_save_recipe.php',
            type: 'POST',
            data: $(this).serialize(),
            success: function() { location.reload(); },
            error: function() { alert("Lỗi khi lưu!"); }
        });
    });

    // Phần nhập kho giữ nguyên...
    $(document).on('click', '.btn-import', function() {
        $('#import-item-id').val($(this).data('id'));
        $('#import-item-name').text($(this).data('name'));
        $('#import-unit').text($(this).data('unit'));
        new bootstrap.Modal(document.getElementById('modalImport')).show();
    });
    $(document).on('click', '.btn-view-detail', function() {
    const id = $(this).data('id');
    const name = $(this).data('name');
    const status = $(this).data('status');

    // Gán dữ liệu cơ bản vào Modal
    $('#m-name').text(name);
    $('#m-avatar').text(name.charAt(0).toUpperCase());
    $('#m-status').html(status === 'Pending' 
        ? '<span class="badge bg-warning text-dark">Chờ duyệt</span>' 
        : '<span class="badge bg-success">Đã xác nhận</span>');
    
    // Cập nhật link xuất PDF
    $('#btn-export-pdf').attr('href', 'export_pdf.php?id=' + id);

    // Gọi AJAX để lấy thông tin chi tiết (ngày, khách, ghi chú)
    $.getJSON('ajax_get_booking_detail.php?id=' + id, function(data) {
        if(data) {
            $('#m-phone').text(data.customer_phone);
            $('#m-type').text(data.service_type.toUpperCase());
            $('#m-date').text(data.booking_date);
            $('#m-guests').text(data.guests + ' người');
            $('#m-msg').text(data.message || 'Không có ghi chú.');
        }
    });

    new bootstrap.Modal(document.getElementById('modalDetail')).show();
});
$(document).on('click', '.btn-confirm-booking', function() {
    const id = $(this).data('id');
    if (confirm('Bạn có chắc chắn muốn xác nhận yêu cầu này?')) {
        // Chuyển hướng đến xử lý PHP đã có sẵn trong manage_services.php
        window.location.href = 'manage_services.php?action=confirm&id=' + id;
    }
});
});