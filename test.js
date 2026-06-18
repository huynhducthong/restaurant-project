
    // ================= BIẾN TOÀN CỤC =================
    const categories = null;
    const units = null;
    const chartRaw = null;
    const PAGE_SIZE = 15;
    let currentPage = 1;
    let activeFilter = sessionStorage.getItem('activeInventoryFilter') || 'all';

    window.clearScrapWarehouse = function() {
        if(confirm('Bạn có chắc chắn muốn tiêu hủy TOÀN BỘ rác trong Kho Hủy không? Hành động này sẽ làm số lượng về 0 và lưu vào lịch sử.')) {
            $.post('InventoryController.php', { action: 'clear_scrap_warehouse' }, function(res) {
                if(res.status === 'success') {
                    alert(res.message);
                    sessionStorage.setItem('activeInventoryWarehouse', 'all');
                    location.reload();
                } else {
                    alert(res.message || 'Có lỗi xảy ra');
                }
            }, 'json').fail(function() {
                alert('Lỗi kết nối đến máy chủ.');
            });
        }
    };

    $(document).ready(function() {
    $('#po-supplier-select').on('change', function() {
        const option = $(this).find('option:selected');
        const expiry = option.data('atvstp-expiry');
        const warningDiv = $('#atvstp-warning');
        
        if (expiry) {
            const expiryDate = new Date(expiry);
            const today = new Date();
            today.setHours(0,0,0,0);
            
            if (expiryDate < today) {
                warningDiv.removeClass('d-none');
            } else {
                warningDiv.addClass('d-none');
            }
        } else {
            if ($(this).val() !== "") {
                warningDiv.removeClass('d-none');
            } else {
                warningDiv.addClass('d-none');
            }
        }
    });
    
    let debounceTimer;
        const urlParams = new URLSearchParams(window.location.search);
        const targetTab = urlParams.get('tab');
        if (targetTab) {
            switchTab(targetTab);
        }
    });

    window.viewBatches = function(id, name) {
        $('#batch-item-name').text(name);
        $('#batch-list-body').html('<tr><td colspan="5" class="text-center py-5"><div class="spinner-border text-info"></div></td></tr>');
        const modal = new bootstrap.Modal(document.getElementById('modalBatchDetails'));
        modal.show();

        $.post('InventoryController.php', { action: 'get_batches', item_id: id }, function(res) {
            if(res.status === 'success') {
                let html = '';
                if(res.data.length === 0) {
                    html = '<tr><td colspan="6" class="text-center py-4 text-muted">Không còn lô hàng nào trong kho.</td></tr>';
                } else {
                    const today = new Date();
                    res.data.forEach(b => {
                        let hsdClass = '', statusText = '<span class="badge badge-ghost-success">Ổn định</span>';
                        if(b.expiry_date) {
                            const exp = new Date(b.expiry_date);
                            const diff = (exp - today) / (1000 * 60 * 60 * 24);
                            if(diff < 0) { hsdClass = 'text-danger fw-bold'; statusText = '<span class="badge badge-ghost-danger">Hết hạn</span>'; }
                            else if(diff <= 7) { hsdClass = 'text-warning fw-bold'; statusText = '<span class="badge badge-ghost-warning">Sắp hết</span>'; }
                        }
                        let tempText = b.receiving_temperature ? b.receiving_temperature + '°C' : '-';
                        html += `<tr><td class="ps-4">#${b.batch_code || 'N/A'}</td><td>${b.warehouse_name}</td><td class="text-center fw-bold">${parseFloat(b.quantity)}</td><td class="text-center text-info">${tempText}</td><td class="text-center ${hsdClass}">${b.expiry_date || '-'}</td><td class="text-center">${statusText}</td></tr>`;
                    });
                }
                $('#batch-list-body').html(html);
            }
        }, 'json').fail(function() {
            $('#batch-list-body').html('<tr><td colspan="5" class="text-center py-4 text-danger">Lỗi kết nối máy chủ khi tải lô hàng.</td></tr>');
        });
    };

    // ================= HÀM CHUYỂN TAB =================
    function switchTab(tabId) {
        // 1. Chuyển tab ngay lập tức
        $('.tab-pane').removeClass('active');
        $('#tab-' + tabId).addClass('active');

        // 2. Cập nhật nút Menu
        $('.btn-menu').removeClass('active');
        $('#btn-' + tabId).addClass('active');

        // 3. Cập nhật URL
        const url = new URL(window.location);
        url.searchParams.set('tab', tabId);
        window.history.pushState({}, '', url);

        if (tabId === 'chart') renderChart();
    }

    // ================= HÀM MỞ MODALS =================
    function openInventoryModal() {
        $('#inv-id, #inv-name, #inv-temp').val('');
        $('.inv-alg-chk').prop('checked', false);
        new bootstrap.Modal(document.getElementById('modalInventory')).show();
    }

    function openEdit(data) {
        $('#inv-id').val(data.id);
        $('#inv-name').val(data.item_name);
        $('#inv-cat').val(data.category);
        $('#inv-unit').val(data.unit_name);
        $('#inv-min').val(data.min_stock || 0);
        $('#inv-temp').val(data.storage_temperature || '');
        
        $('.inv-alg-chk').prop('checked', false);
        if (data.allergens) {
            let algs = data.allergens.split(',').map(s => s.trim());
            $('.inv-alg-chk').each(function() {
                if (algs.includes($(this).val())) {
                    $(this).prop('checked', true);
                }
            });
        }
        
        new bootstrap.Modal(document.getElementById('modalInventory')).show();
    }

    function openSupplierModal() {
        $('#s-id, #s-name, #s-contact, #s-phone, #s-email, #s-address, #s-atvstp-expiry').val('');
        $('#s-atvstp-file').val('');
        $('#s-atvstp-link').html('');
        new bootstrap.Modal(document.getElementById('modalSupplier')).show();
    }

    function openEditSupplier(data) {
        $('#s-id').val(data.id);
        $('#s-name').val(data.name);
        $('#s-contact').val(data.contact_person);
        $('#s-phone').val(data.phone);
        $('#s-email').val(data.email);
        $('#s-address').val(data.address);
        $('#s-atvstp-expiry').val(data.atvstp_expiry || '');
        $('#s-atvstp-file').val('');
        if (data.atvstp_file) {
            $('#s-atvstp-link').html('<a href="../../uploads/suppliers/' + data.atvstp_file + '" target="_blank" class="text-decoration-none"><i class="fas fa-file-pdf me-1"></i>Xem file hiện tại</a>');
        } else {
            $('#s-atvstp-link').html('');
        }
        new bootstrap.Modal(document.getElementById('modalSupplier')).show();
    }

    function openImport(id, name, unit) {
        $('#form-import')[0].reset();
        $('#imp-id').val(id);
        $('#imp-name').text(name);
        $('#imp-unit').text(unit);
        new bootstrap.Modal(document.getElementById('modalImport')).show();
    }

    // Template HTML cho 1 dòng nguyên liệu (dùng khi thêm dòng mới)
    const transRowTemplate = `<tr>
        <td>
            <select name="trans_item_id[]" class="form-select form-select-sm trans-item-select" required>
                ${$('#transferBody tr:first .trans-item-select').prop('outerHTML').match(/<option[\s\S]*<\/option>/g)?.[0] ? 
                  $('#transferBody tr:first .trans-item-select').html() : ''}
            </select>
        </td>
        <td>
            <div class="input-group input-group-sm">
                <input type="number" name="trans_qty[]" class="form-control text-center" step="0.01" min="0.01" placeholder="0.00" required>
                <span class="input-group-text trans-unit-label">đơn vị</span>
            </div>
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-outline-danger btn-sm btn-trans-remove border-0" title="Xóa dòng">
                <i class="fas fa-trash" style="pointer-events:none"></i>
            </button>
        </td>
    </tr>`;

    // Mở modal chuyển kho — pre-select mặt hàng nếu click từ bảng
    function openTransfer(id, name, unit) {
        // Reset bảng về 1 dòng sạch
        const $tbody = $('#transferBody');
        $tbody.find('tr').slice(1).remove(); // Giữ lại dòng đầu, xóa các dòng thêm
        $tbody.find('.trans-item-select').val(''); // Reset dòng đầu
        $tbody.find('input[name="trans_qty[]"]').val('');
        $tbody.find('.trans-unit-label').text('đơn vị');
        $tbody.find('.trans-stock-info').html('');

        filterTransferItems();

        // Nếu mở từ nút cụ thể → pre-select mặt hàng đó
        if (id) {
            const $firstSelect = $tbody.find('.trans-item-select').first();
            $firstSelect.val(id);
            $tbody.find('.trans-unit-label').first().text(unit || 'đơn vị');
            updateTransferStock($tbody.find('tr').first());
        }
        new bootstrap.Modal(document.getElementById('modalTransfer')).show();
    }

    // Thêm dòng mới vào bảng
    $(document).on('click', '#btnAddTransRow', function () {
        const $firstRow = $('#transferBody tr:first').clone();
        $firstRow.find('.trans-item-select').val('');
        $firstRow.find('input[name="trans_qty[]"]').val('');
        $firstRow.find('.trans-unit-label').text('đơn vị');
        $firstRow.find('.trans-stock-info').html('');
        $('#transferBody').append($firstRow);
    });

    // Xóa dòng
    $(document).on('click', '.btn-trans-remove', function () {
        if ($('#transferBody tr').length > 1) {
            $(this).closest('tr').remove();
        } else {
            alert('Phải có ít nhất 1 mặt hàng trong lệnh chuyển kho.');
        }
    });

    // Tự động cập nhật nhãn đơn vị và tồn kho khi chọn nguyên liệu
    $(document).on('change', '.trans-item-select', function () {
        const unit = $(this).find(':selected').data('unit') || 'đơn vị';
        $(this).closest('tr').find('.trans-unit-label').text(unit);
        updateTransferStock($(this).closest('tr'));
    });

    $(document).on('change', '#trans-from-wh', function() {
        filterTransferItems();
        $('#transferBody tr').each(function() {
            updateTransferStock($(this));
        });
    });

    function filterTransferItems() {
        const fromWhId = $('#trans-from-wh').val();
        if(!fromWhId) return;
        
        $('.trans-item-select').each(function() {
            const $select = $(this);
            const currentVal = $select.val();
            let isCurrentValValid = false;

            $select.find('option').each(function() {
                if (!$(this).val()) return; // Skip placeholder
                
                const stocks = $(this).data('stocks') || {};
                const qty = parseFloat(stocks[fromWhId]) || 0;
                
                if (qty > 0) {
                    $(this).removeClass('d-none').prop('disabled', false);
                    if ($(this).val() == currentVal) isCurrentValValid = true;
                } else {
                    $(this).addClass('d-none').prop('disabled', true);
                }
            });

            if (currentVal && !isCurrentValValid) {
                $select.val('');
                $select.closest('tr').find('.trans-stock-info').html('');
                $select.closest('tr').find('.trans-unit-label').text('đơn vị');
            }
        });
    }

    function updateTransferStock($row) {
        const fromWhId = $('#trans-from-wh').val();
        const $select = $row.find('.trans-item-select');
        const $opt = $select.find(':selected');
        if(!$opt.val() || !fromWhId) {
            $row.find('.trans-stock-info').html('');
            return;
        }
        const stocks = $opt.data('stocks') || {};
        const qty = parseFloat(stocks[fromWhId]) || 0;
        const unit = $opt.data('unit') || '';
        $row.find('.trans-stock-info').html(`<small class="text-primary fw-bold"><i class="fas fa-box-open"></i> Tồn trong kho: ${qty} ${unit}</small>`);
    }

    // Xử lý động đổi màu Modal Xuất/Hủy
    function openExport(btn, id, name, type) {
        $('#form-export')[0].reset();
        $('#exp-id').val(id);
        $('#exp-action').val(type);
        $('#exp-name').text(name);

        const stocks = JSON.parse($(btn).closest('tr').attr('data-stocks') || '{}');
        const $select = $('#form-export select[name="warehouse_id"]');
        
        $select.find('option').each(function() {
            if (!this.value) return; // skip placeholder
            const qty = parseFloat(stocks[this.value]) || 0;
            const originalName = $(this).data('name') || $(this).text().split(' (Tồn:')[0];
            if (!$(this).data('name')) $(this).data('name', originalName); // save original name
            
            if (qty > 0) {
                $(this).removeClass('d-none').prop('disabled', false).text(originalName + ' (Tồn: ' + qty + ')');
            } else {
                $(this).addClass('d-none').prop('disabled', true).text(originalName);
            }
        });
        
        $select.off('change').on('change', function() {
            const qty = parseFloat(stocks[this.value]) || 0;
            $('#form-export input[name="quantity"]').attr('max', qty).val(qty); // Auto-fill max
            $('#exp-stock-hint').html('<span class="text-primary fw-bold">Hiện có: ' + qty + '</span>');
        });
        $('#exp-stock-hint').html('');

        if (type === 'loss') {
            $('#modalExportHeader').removeClass('bg-primary').addClass('bg-danger');
            $('#modalExportSubmitBtn').removeClass('btn-dark fw-bold text-white shadow-sm').addClass('btn-outline-danger').text('XÁC NHẬN HỦY');
            $('#exp-lbl').text('Kho thực hiện hủy:');
        } else {
            $('#modalExportHeader').removeClass('bg-danger').addClass('bg-primary');
            $('#modalExportSubmitBtn').removeClass('btn-outline-danger').addClass('btn-dark fw-bold text-white shadow-sm').text('XÁC NHẬN XUẤT');
            $('#exp-lbl').text('Kho thực hiện xuất:');
        }
        new bootstrap.Modal(document.getElementById('modalExport')).show();
    }

    // ================= QUẢN LÝ TAG (DANH MỤC / ĐƠN VỊ) =================
    function openTagManager(type) {
        const data = (type === 'category') ? categories : units;
        $('#tagTitle').text(type === 'category' ? 'Quản lý Danh mục' : 'Quản lý Đơn vị');
        $('#tagTypeInput').val(type);
        let html = '';
        data.forEach(i => {
            html += `<div class="list-group-item d-flex justify-content-between align-items-center py-2"><span>${i.name}</span><div class="btn-group btn-group-sm"><button type="button" class="btn btn-outline-primary" onclick="openEditTag(${i.id},'${i.name.replace(/'/g,"\\'")}','${type}')"><i class="fas fa-edit"></i></button><form method="POST" action="InventoryController.php" style="display:inline"><input type="hidden" name="manage_tag" value="1"><input type="hidden" name="tag_type" value="${type}"><input type="hidden" name="tag_action" value="delete"><input type="hidden" name="tag_id" value="${i.id}"><button type="submit" class="btn btn-outline-danger" onclick="return confirm('Xóa \\"${i.name}\\"?')"><i class="fas fa-trash"></i></button></form></div></div>`;
        });
        $('#tagList').html(html || '<div class="p-2 text-muted small">Chưa có dữ liệu</div>');
        new bootstrap.Modal(document.getElementById('modalTags')).show();
    }

    function openEditTag(id, oldName, type) {
        $('#editTagId').val(id);
        $('#editTagType').val(type);
        $('#editTagName').val(oldName);
        new bootstrap.Modal(document.getElementById('modalEditTag')).show();
    }

    // ================= GIAO DỊCH AJAX =================
    const warehouses = null;

    // 1. TỰ ĐỘNG XÓA DẤU PHẨY TRƯỚC KHI LƯU DB (Của TẤT CẢ các form)
    $(document).on('submit', 'form', function() {
        $(this).find('.money-input').each(function() {
            this.value = this.value.replace(/,/g, '');
        });
    });

    // 1b. Định dạng tiền tệ khi focus/blur
    $(document).on('blur', '.money-input', function() {
        let val = this.value.replace(/[^0-9]/g, '');
        this.value = val !== '' ? parseInt(val, 10).toLocaleString('en-US') : '';
    });
    $(document).on('focus', '.money-input', function() {
        this.value = this.value.replace(/,/g, '');
    });

    // 2a. AJAX cho form Nhập, Xuất (serialize bình thường)
    $(document).on('submit', '#form-import, #form-export', function(e) {
        e.preventDefault();
        const btn = $(this).find('[type=submit]').prop('disabled', true).text('Đang xử lý...');
        $.post('InventoryController.php', $(this).serialize(), function(r) {
            if (r.status === 'success') location.reload();
            else {
                alert('❌ ' + (r.msg || 'Lỗi'));
                btn.prop('disabled', false).text('XÁC NHẬN');
            }
        }, 'json').fail(function() {
            alert('Lỗi kết nối máy chủ.');
            btn.prop('disabled', false);
        });
    });

    // 2b. AJAX cho form Chuyển kho (dùng FormData để gửi mảng đúng chuẩn)
    $(document).on('submit', '#form-transfer', function(e) {
        e.preventDefault();
        const $btn = $(this).find('[type=submit]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Đang xử lý...');
        $.ajax({
            url: 'InventoryController.php',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(r) {
                if (r.status === 'success') location.reload();
                else {
                    alert('❌ ' + (r.msg || 'Lỗi không xác định'));
                    $btn.prop('disabled', false).html('<i class="fas fa-paper-plane me-2"></i>TẠO LỆNH CHUYỂN KHO');
                }
            },
            error: function() {
                alert('Lỗi kết nối máy chủ.');
                $btn.prop('disabled', false).html('<i class="fas fa-paper-plane me-2"></i>TẠO LỆNH CHUYỂN KHO');
            }
        });
    });
    // ================= LỌC & PHÂN TRANG =================
    let activeWarehouse = sessionStorage.getItem('activeInventoryWarehouse') || 'all'; // 'all' hoặc ID kho cụ thể

    // Xử lý click nút filter theo kho
    $(document).on('click', '.wh-filter-btn', function () {
        activeWarehouse = $(this).data('wh').toString();
        sessionStorage.setItem('activeInventoryWarehouse', activeWarehouse);
        // Cập nhật active state
        $('.wh-filter-btn').removeClass('active active-all active-main active-kitchen active-bar active-cold active-supplies active-virtual btn-dark btn-outline-danger btn-warning fw-bold text-white shadow-sm')
                           .addClass('btn-outline-secondary');
        
        $(this).removeClass('btn-outline-secondary').addClass('active');
        
        if (activeWarehouse === 'all') {
            $(this).addClass('active-all');
        } else {
            const type = $(this).data('wh-type');
            const colorMap = { 
                main: 'active-main', 
                kitchen: 'active-kitchen', 
                bar: 'active-bar', 
                cold: 'active-cold',
                supplies: 'active-supplies',
                virtual: 'active-virtual'
            };
            $(this).addClass(colorMap[type] || 'active-virtual');
        }

        if (activeWarehouse === '7') {
            $('#btnClearScrap').removeClass('d-none');
        } else {
            $('#btnClearScrap').addClass('d-none');
        }

        filterTable();
    });

    function filterWarning(type, btn) {
        activeFilter = type;
        sessionStorage.setItem('activeInventoryFilter', type);
        
        // Cập nhật UI nút cảnh báo
        $('#filterButtons button').removeClass('active');
        if (btn) {
            $(btn).addClass('active');
        } else {
            $('#filterButtons button[onclick*="\'" + type + "\'"]').addClass('active');
        }

        // Nếu chọn một cảnh báo cụ thể (low hoặc expiry), tự động chuyển về "Tất cả kho" 
        // để đảm bảo người dùng thấy được mặt hàng bị cảnh báo đó
        if (type !== 'all' && activeWarehouse !== 'all') {
            activeWarehouse = 'all';
            sessionStorage.setItem('activeInventoryWarehouse', 'all');
            $('.wh-filter-btn').removeClass('btn-dark fw-bold active-main active-all text-white shadow').addClass('btn-outline-secondary');
            $('.wh-filter-btn[data-wh="all"]').removeClass('btn-outline-secondary').addClass('btn-dark fw-bold active-all text-white shadow');
        }
        
        filterTable();
    }

    function filterTable() {
        const q = document.getElementById('searchInput').value.toLowerCase();
        const catFilter = document.getElementById('categoryFilter').value.toLowerCase();
        document.querySelectorAll('#invBody .inv-row').forEach(r => {
            const nameMatch   = r.dataset.name.includes(q);
            const rCat = r.dataset.category ? r.dataset.category.toLowerCase() : '';
            const catMatch    = catFilter === '' || rCat === catFilter;
            const filterMatch = (activeFilter === 'all') ? true
                              : (activeFilter === 'low' ? r.dataset.low === '1' 
                               : (activeFilter === 'expired' ? r.dataset.expired === '1' : r.dataset.expiry === '1'));

            // Filter theo kho: kiểm tra data-wh-stock có chứa ID kho đang chọn không
            let whMatch = true;
            if (activeWarehouse !== 'all') {
                try {
                    const whStock = JSON.parse(r.dataset.whStock || '[]');
                    whMatch = whStock.map(String).includes(activeWarehouse);
                } catch(e) { whMatch = true; }
            }

            r.setAttribute('data-visible', (nameMatch && catMatch && filterMatch && whMatch) ? '1' : '0');

            // --- ĐIỀU CHỈNH HIỂN THỊ CHI TIẾT KHO TRONG DÒNG ---
            const badges = r.querySelectorAll('.wh-badge');
            const totalDiv = r.querySelector('.wh-total');
            const emptyDiv = r.querySelector('.wh-empty');

            // Luôn hiển thị kho và tồn kho
            if (totalDiv) totalDiv.style.display = '';
            if (emptyDiv) emptyDiv.style.display = '';
            badges.forEach(b => {
                if (activeWarehouse === 'all' || activeWarehouse === '1') {
                    b.style.display = '';
                } else {
                    b.style.display = (b.dataset.whId === activeWarehouse) ? '' : 'none';
                }
            });
        });
        currentPage = 1;
        renderPagination();
    }

    function renderPagination() {
        const allRows = document.querySelectorAll('#invBody .inv-row');
        allRows.forEach(r => r.style.display = 'none'); // Ẩn hết toàn bộ

        const visibleRows = [...allRows].filter(r => r.getAttribute('data-visible') === '1');
        const t = visibleRows.length;
        const pgs = Math.ceil(t / PAGE_SIZE) || 1;
        currentPage = Math.min(currentPage, pgs);

        visibleRows.forEach((r, i) => {
            if (i >= (currentPage - 1) * PAGE_SIZE && i < currentPage * PAGE_SIZE) r.style.display = '';
        });

        document.getElementById('paginInfo').textContent = t > 0 ? `Hiển thị ${(currentPage-1)*PAGE_SIZE+1} – ${Math.min(currentPage*PAGE_SIZE, t)} / Tổng ${t}` : 'Không tìm thấy kết quả';

        let html = `<button class="btn btn-outline-secondary" onclick="goPage(${currentPage-1})" ${currentPage<=1?'disabled':''}>‹</button>`;
        for (let p = 1; p <= pgs; p++) {
            if (pgs <= 7 || Math.abs(p - currentPage) <= 1 || p === 1 || p === pgs) {
                html += `<button class="btn ${p===currentPage?'btn-dark fw-bold text-white shadow-sm':'btn-outline-secondary'}" onclick="goPage(${p})">${p}</button>`;
            } else if (Math.abs(p - currentPage) === 2) {
                html += `<button class="btn btn-outline-secondary" disabled>…</button>`;
            }
        }
        html += `<button class="btn btn-outline-secondary" onclick="goPage(${currentPage+1})" ${currentPage>=pgs?'disabled':''}>›</button>`;
        document.getElementById('paginBtns').innerHTML = html;
    }

    function goPage(p) {
        currentPage = p;
        renderPagination();
    }

    // ================= XUẤT EXCEL CAO CẤP (MA TRẬN KHO & GIÁ TRỊ) =================
    window.exportFilteredExcel = function() {
        const visibleRows = document.querySelectorAll('#invBody .inv-row[data-visible="1"]');
        if (visibleRows.length === 0) {
            alert('Không có dữ liệu nào để xuất!');
            return;
        }

        // Tạo Header động dựa trên danh sách kho
        let whHeaders = warehouses.map(w => `<th style="background-color: #f8f9fa; font-weight: bold; color: #212529;">${w.name}</th>`).join('');
        
        let tableHTML = `<table border="1">
            <thead>
                <tr style="background-color: #2c3e50; color: white; font-weight: bold;">
                    <th style="color: white;">Nguyên Liệu</th>
                    <th style="color: white;">Danh Mục</th>
                    ${whHeaders}
                    <th style="background-color: #d1e7dd; color: #0f5132;">Tổng Tồn</th>
                    <th style="color: white;">Đơn Vị</th>
                    <th style="color: white;">Giá Vốn (đ)</th>
                    <th style="background-color: #fff3cd; color: #664d03;">Thành Tiền (đ)</th>
                    <th style="color: white;">HSD</th>
                </tr>
            </thead>
            <tbody>`;

        let grandTotalValue = 0;

        visibleRows.forEach(r => {
            let name = r.querySelector('strong').innerText;
            let catText = r.querySelector('.text-muted').innerText;
            let cat = catText.split('|')[0].trim();
            
            // Lấy stocks từ data attribute
            let stocks = {};
            try { stocks = JSON.parse(r.dataset.stocks || '{}'); } catch(e) {}

            // Lấy tổng tồn và đơn vị
            let totalDiv = r.querySelector('.text-success.fw-bold');
            let totalText = totalDiv ? totalDiv.innerText : '0';
            let totalMatch = totalText.match(/([\d.]+)\s*(.*)/);
            let totalQty = totalMatch ? parseFloat(totalMatch[1]) : 0;
            let unitName = totalMatch ? totalMatch[2] : '';

            // Lấy giá vốn
            let priceText = r.cells[3].innerText.replace(/[^\d]/g, '');
            let price = parseFloat(priceText) || 0;
            let lineValue = totalQty * price;
            grandTotalValue += lineValue;

            let hsd = r.cells[2].innerText;

            // Xây dựng các cột kho
            let whCols = warehouses.map(w => {
                let q = parseFloat(stocks[w.id] || 0);
                return `<td style="text-align: center;">${q > 0 ? q : '-'}</td>`;
            }).join('');

            tableHTML += `<tr>
                <td style="font-weight: bold;">${name}</td>
                <td>${cat}</td>
                ${whCols}
                <td style="text-align: center; font-weight: bold; background-color: #d1e7dd;">${totalQty}</td>
                <td style="text-align: center;">${unitName}</td>
                <td style="text-align: right;">${price.toLocaleString('vi-VN')}</td>
                <td style="text-align: right; font-weight: bold; background-color: #fff3cd;">${lineValue.toLocaleString('vi-VN')}</td>
                <td style="text-align: center;">${hsd}</td>
            </tr>`;
        });

        // Dòng tổng cộng tài sản
        tableHTML += `
            <tr style="background-color: #eee;">
                <td colspan="${2 + warehouses.length}" style="text-align: right; font-weight: bold; padding: 10px;">TỔNG GIÁ TRỊ TÀI SẢN KHO:</td>
                <td colspan="4" style="text-align: right; font-weight: bold; color: #d63384; font-size: 14px;">${grandTotalValue.toLocaleString('vi-VN')} VNĐ</td>
                <td></td>
            </tr>
        </tbody></table>`;

        // Thông tin Footer
        let now = new Date().toLocaleString('vi-VN');
        tableHTML += `<p><i>Báo cáo được xuất tự động vào lúc: ${now}</i></p>`;

        // Download
        let uri = 'data:application/vnd.ms-excel;base64,';
        let template = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"><head><meta charset="utf-8"><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>BaoCaoKho</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head><body>{table}</body></html>';
        let base64 = function(s) { return window.btoa(unescape(encodeURIComponent(s))) };
        let format = function(s, c) { return s.replace(/{(\w+)}/g, function(m, p) { return c[p]; }) };
        
        let ctx = {worksheet: 'BaoCaoKho', table: tableHTML};
        let link = document.createElement("a");
        link.download = "BaoCao_TonKho_ChiTiet_" + new Date().toISOString().slice(0,10) + ".xls";
        link.href = uri + base64(format(template, ctx));
        link.click();
    };

    // ================= LOGIC TAB KIỂM KÊ =================
    $('#auditWarehouseSelect').change(function() {
        let w_id = $(this).val();
        if (w_id === "") {
            $('#auditTable').hide();
            return;
        }
        $('#auditTable').show();
        $('.audit-row').each(function() {
            let stocks = $(this).data('stocks');
            let sys_qty = stocks[w_id] !== undefined ? stocks[w_id] : 0;
            $(this).find('.system-qty').text(sys_qty);
            $(this).find('.physical-input').val(sys_qty);
        });
    });

    // ================= BIỂU ĐỒ =================
    let chartInstance = null;

    function renderChart() {
        if (chartInstance || !chartRaw || chartRaw.length === 0) return;
        chartInstance = new Chart(document.getElementById('inventoryChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: chartRaw.map(d => d.mo),
                datasets: [{
                        label: 'Nhập kho',
                        data: chartRaw.map(d => parseFloat(d.ti)),
                        backgroundColor: 'rgba(25,135,84,.7)'
                    },
                    {
                        label: 'Xuất kho',
                        data: chartRaw.map(d => parseFloat(d.te)),
                        backgroundColor: 'rgba(13,110,253,.7)'
                    },
                    {
                        label: 'Hủy hàng',
                        data: chartRaw.map(d => parseFloat(d.tl)),
                        backgroundColor: 'rgba(220,53,69,.7)'
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // ================= DUYỆT / HỦY CHUYỂN KHO =================
    function approveTransfer(id) {
        if (!confirm('Xác nhận phê duyệt và thực hiện trừ/cộng kho cho lệnh này?')) return;
        $.post('InventoryController.php', {
            action: 'approve_transfer',
            transfer_id: id
        }, function(r) {
            if (r.status === 'success') location.reload();
            else alert('❌ ' + (r.msg || 'Lỗi không xác định'));
        }, 'json');
    }

    function cancelTransfer(id) {
        if (!confirm('Bạn có chắc chắn muốn hủy yêu cầu chuyển kho này?')) return;
        $.post('InventoryController.php', {
            action: 'cancel_transfer',
            transfer_id: id
        }, function(r) {
            if (r.status === 'success') location.reload();
            else alert('❌ ' + (r.msg || 'Lỗi không xác định'));
        }, 'json');
    }

    // ================= PO JS =================
$(document).ready(function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('trigger_suggestion') === '1') {
        const myModal = new bootstrap.Modal(document.getElementById('modalCreatePO'));
        myModal.show();
        // Đợi modal hiện xong rồi mới gọi gợi ý
        document.getElementById('modalCreatePO').addEventListener('shown.bs.modal', function () {
            loadSuggestions();
        }, { once: true });
    } else if (urlParams.get('add_item')) {
        const addItemId = urlParams.get('add_item');
        const addQty = urlParams.get('qty');
        const myModal = new bootstrap.Modal(document.getElementById('modalCreatePO'));
        myModal.show();
        document.getElementById('modalCreatePO').addEventListener('shown.bs.modal', function () {
            const firstRow = $('#poBody tr').first();
            firstRow.find('.item-select').val(addItemId).trigger('change');
            if (addQty) {
                firstRow.find('.qty-input').val(addQty).trigger('input');
            }
        }, { once: true });
    }

    // Định dạng tiền tệ khi focus/blur
    $(document).on('focus', '.money-input', function() {
        let val = $(this).val().replace(/,/g, '');
        $(this).attr('type', 'number');
        $(this).val(val);
    });

    $(document).on('blur', '.money-input', function() {
        let val = parseFloat($(this).val()) || 0;
        $(this).attr('type', 'text');
        $(this).val(val.toLocaleString('en-US'));
        calcTotal();
    });

    $(document).on('change', '.item-select', function() {
        let price = $(this).find(':selected').data('price') || 0;
        let priceInput = $(this).closest('tr').find('.price-input');
        priceInput.attr('type', 'text');
        priceInput.val(parseFloat(price).toLocaleString('en-US'));
        calcTotal();
    });

    $(document).on('input', '.qty-input', function() { calcTotal(); });

    $('#btnAddRow').click(function() {
        let newRow = $('#poBody tr:first').clone();
        newRow.find('input').val('');
        newRow.find('input.price-input').attr('type', 'text').val('');
        newRow.find('.row-total').val('0');
        $('#poBody').append(newRow);
    });

    $(document).on('click', '.btn-remove', function() {
        if ($('#poBody tr').length > 1) { $(this).closest('tr').remove(); calcTotal(); }
    });

    function calcTotal() {
        let grandTotal = 0;
        $('#poBody tr').each(function() {
            let qty = parseFloat($(this).find('.qty-input').val()) || 0;
            let priceStr = $(this).find('.price-input').val() || '0';
            let price = parseFloat(priceStr.replace(/,/g, '')) || 0;
            let total = qty * price;
            $(this).find('.row-total').val(total.toLocaleString('en-US'));
            grandTotal += total;
        });
        $('#poGrandTotal').val(grandTotal.toLocaleString('en-US') + ' đ');
    }

    $('form[action="POController.php"]').on('submit', function() {
        $(this).find('.money-input').each(function() {
            let val = $(this).val().replace(/,/g, '');
            $(this).attr('type', 'number').val(val);
        });
    });

    window.openQuickAddIng = function() {
        new bootstrap.Modal(document.getElementById('modalQuickAddIng')).show();
    };

    $('#btnSaveQuickIng').click(function() {
        const name = $('#quick-ing-name').val();
        const unit = $('#quick-ing-unit').val();
        const cat  = $('#quick-ing-cat').val();
        if(!name || !unit) return alert('Vui lòng nhập đủ Tên và Đơn vị!');
        $(this).prop('disabled', true).text('Đang lưu...');

        $.post('POController.php', { action: 'quick_add_ingredient', name: name, unit: unit, category: cat }, function(res) {
            $('#btnSaveQuickIng').prop('disabled', false).text('LƯU & CHỌN');
            if(res.status === 'success') {
                const newOpt = `<option value="${res.id}" data-price="0" selected>${name} (${unit})</option>`;
                $('.item-select').append(newOpt);
                bootstrap.Modal.getInstance(document.getElementById('modalQuickAddIng')).hide();
                $('#quick-ing-name, #quick-ing-unit').val('');
                alert('Đã thêm nguyên liệu mới!');
            } else {
                alert('Lỗi: ' + res.message);
            }
        }, 'json');
    });

    window.viewPO = function(id, code) {
        $('#view-po-code').text(code);
        $('#view-po-body').html('<tr><td colspan="4" class="text-center py-5"><div class="spinner-border text-warning"></div></td></tr>');
        $('#view-po-cert-btn').remove(); // Xóa nút cũ nếu có
        new bootstrap.Modal(document.getElementById('modalViewPO')).show();
        $.post('POController.php', { action: 'get_details', po_id: id }, function(res) {
            if(res.status === 'success') {
                let html = '', grandTotal = 0;
                res.data.forEach(item => {
                    let qty = parseFloat(item.expected_qty || item.quantity || 0);
                    let price = parseFloat(item.expected_price || item.price || 0);
                    let total = qty * price;
                    grandTotal += total;
                    html += `<tr><td class="ps-4"><div class="fw-bold">${item.item_name}</div></td><td class="text-center"><strong>${qty}</strong> <small class="text-muted">${item.unit_name}</small></td><td class="text-end">${price.toLocaleString('en-US')} đ</td><td class="text-end fw-bold text-danger pe-4">${total.toLocaleString('en-US')} đ</td></tr>`;
                });
                html += `<tr class="bg-light"><td colspan="3" class="text-end fw-bold py-3 text-muted">TỔNG CỘNG:</td><td class="text-end fw-bold text-danger py-3 fs-5 pe-4">${grandTotal.toLocaleString('en-US')} đ</td></tr>`;
                $('#view-po-body').html(html);

                // Nếu có file giấy kiểm dịch, thêm nút vào header
                if (res.batch_cert_file) {
                    const btn = `<a href="../../uploads/po_certs/${res.batch_cert_file}" target="_blank" id="view-po-cert-btn" class="btn btn-sm btn-outline-danger ms-3 fw-bold shadow-sm"><i class="fas fa-file-pdf me-1"></i>Xem Chứng Từ Lô Hàng</a>`;
                    $('#view-po-code').after(btn);
                }
                
                // Nếu có giấy ATVSTP của nhà cung cấp
                if (res.supplier_atvstp) {
                    const atvstpBtn = `<a href="../../uploads/suppliers/${res.supplier_atvstp}" target="_blank" id="view-po-supplier-atvstp" class="btn btn-sm btn-outline-warning ms-2"><i class="fas fa-file-certificate me-1"></i>Xem ATVSTP Nhà Cung Cấp</a>`;
                    $('#view-po-code').after(atvstpBtn);
                }
            }
        }, 'json');
    };

    window.cancelPO = function(id, code) {
        if(confirm(`Bạn có chắc chắn muốn hủy phiếu đặt hàng #${code} không?`)) {
            $.post('POController.php', { action: 'cancel_po', po_id: id }, function(res) {
                if(res.status === 'success') {
                    alert(res.message);
                    location.reload();
                } else {
                    alert(res.message || 'Có lỗi xảy ra');
                }
            });
        }
    };

    window.openReceivePO = function(id, code) {
        $('#receive-po-id').val(id);
        $('#receive-po-code').text(code);
        $('#receive-po-body').html('<tr><td colspan="5" class="text-center py-5"><div class="spinner-border text-success"></div></td></tr>');
        new bootstrap.Modal(document.getElementById('modalReceivePO')).show();
        $.post('POController.php', { action: 'get_details', po_id: id }, function(res) {
            if(res.status === 'success') {
                let html = '';
                res.data.forEach(item => {
                    let qty = parseFloat(item.expected_qty || 0);
                    let price = parseFloat(item.expected_price || 0);
                    html += `<tr><td class="ps-4"><div class="fw-bold">${item.item_name}</div><input type="hidden" name="ingredient_id[]" value="${item.ingredient_id}"></td><td class="text-center text-muted">${qty} ${item.unit_name}</td><td><div class="input-group input-group-sm"><input type="number" name="received_qty[]" class="form-control text-center fw-bold" step="0.01" value="${qty}" required><span class="input-group-text">${item.unit_name}</span></div></td><td><input type="text" name="received_price[]" class="form-control form-control-sm text-end money-input" value="${price.toLocaleString('en-US')}" required></td><td><input type="text" name="receiving_temperature[]" class="form-control form-control-sm text-center" placeholder="VD: -18, 4" required></td><td class="pe-4"><input type="date" name="expiry_date[]" class="form-control form-control-sm" required></td></tr>`;
                });
                $('#receive-po-body').html(html);

                $('#receive-po-supplier-atvstp').remove(); // Xóa link cũ nếu có
                if (res.supplier_atvstp) {
                    const atvstpLink = `<a href="../../uploads/suppliers/${res.supplier_atvstp}" target="_blank" id="receive-po-supplier-atvstp" class="btn btn-sm btn-outline-light ms-3"><i class="fas fa-file-certificate me-1"></i>Xem Giấy ATVSTP Nhà Cung Cấp</a>`;
                    $('#receive-po-code').after(atvstpLink);
                }
            }
        }, 'json');
    };

    // ================= GỢI Ý NHẬP HÀNG TỰ ĐỘNG =================
    window.loadSuggestions = function() {
        const btn = event.target.closest('button');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Đang tải...';

        $.post('../controllers/InventoryController.php', { action: 'get_reorder_list' }, function(res) {
            btn.disabled = false;
            btn.innerHTML = originalText;

            if(res.status === 'success' && res.data.length > 0) {
                // Xóa dòng đầu tiên nếu nó trống
                const firstRow = $('#poBody tr').first();
                if(firstRow.find('.item-select').val() === '') {
                    firstRow.remove();
                }

                res.data.forEach(item => {
                    // Kiểm tra xem item đã có trong danh sách chưa
                    let exists = false;
                    $('.item-select').each(function() {
                        if($(this).val() == item.id) exists = true;
                    });
                    if(exists) return;

                    const min = parseFloat(item.min_stock) || 5;
                    const stock = parseFloat(item.total_stock);
                    // Gợi ý: Nhập bù đủ min + 50% dự phòng
                    const suggestQty = Math.ceil((min - stock) + (min * 0.5));
                    
                    const newRow = `
                        <tr>
                            <td>
                                <select name="item_id[]" class="form-select border-0 bg-light item-select" required>
                                    <option value="${item.id}" selected>${item.item_name} (${item.unit_name})</option>
                                </select>
                            </td>
                            <td><input type="number" name="qty[]" class="form-control border-0 bg-light qty-input" step="0.01" min="0.01" value="${suggestQty}" required></td>
                            <td><input type="text" name="price[]" class="form-control border-0 bg-light price-input money-input" value="${parseInt(item.cost_price).toLocaleString('en-US')}" required></td>
                            <td><input type="text" class="form-control border-0 bg-light text-danger fw-bold row-total" readonly value="0"></td>
                            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger btn-remove border-0"><i class="fas fa-times"></i></button></td>
                        </tr>
                    `;
                    $('#poBody').append(newRow);
                });
                // Cập nhật lại thành tiền cho các dòng mới
                $('#poBody tr').each(function() {
                    const qty = parseFloat($(this).find('.qty-input').val()) || 0;
                    const price = parseInt($(this).find('.price-input').val().replace(/[^0-9]/g, '')) || 0;
                    $(this).find('.row-total').val((qty * price).toLocaleString('en-US'));
                });
                updateGrandTotal();
                alert('✅ Đã tự động thêm ' + res.data.length + ' nguyên liệu cần nhập hàng.');
            } else {
                alert('ℹ️ Hiện tại không có nguyên liệu nào dưới mức tồn tối thiểu.');
            }
        }, 'json').fail(function() {
            btn.disabled = false;
            btn.innerHTML = originalText;
            alert('❌ Lỗi kết nối máy chủ.');
        });
    };
});

    // ================= KHỞI CHẠY =================
    $(function() {
        const tab = new URLSearchParams(window.location.search).get('tab');
        if (tab) switchTab(tab);

        // Khôi phục UI của filter cảnh báo
        $('#filterButtons button').removeClass('active');
        $('#filterButtons button[onclick*="\'" + activeFilter + "\'"]').addClass('active');

        // Khôi phục UI của filter kho
        $('.wh-filter-btn').removeClass('btn-dark fw-bold active-main active-all text-white shadow').addClass('btn-outline-secondary');
        const activeBtn = $('.wh-filter-btn[data-wh="' + activeWarehouse + '"]');
        if (activeBtn.length) {
            if (activeWarehouse === 'all') {
                activeBtn.removeClass('btn-outline-secondary').addClass('btn-dark fw-bold active-all text-white shadow');
            } else {
                activeBtn.removeClass('btn-outline-secondary').addClass('btn-dark fw-bold active-main text-white shadow');
            }
        } else {
            activeWarehouse = 'all';
            sessionStorage.setItem('activeInventoryWarehouse', 'all');
            $('.wh-filter-btn[data-wh="all"]').removeClass('btn-outline-secondary').addClass('btn-dark fw-bold active-all text-white shadow');
        }

        filterTable();

        setTimeout(() => {
            const body = document.getElementById('invBody');
            if(body) body.style.opacity = '1';
        }, 50);
    });
