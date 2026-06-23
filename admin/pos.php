<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS Thu Ngân - KDS System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f0f2f5; height: 100vh; overflow: hidden; }
        
        /* Layout Grid */
        .pos-container { display: grid; grid-template-columns: 300px 1fr 380px; height: 100vh; }
        
        /* Panes */
        .pane { background: #fff; display: flex; flex-direction: column; overflow: hidden; border-right: 1px solid #e2e8f0; }
        .pane-header { padding: 15px 20px; background: #fff; border-bottom: 1px solid #e2e8f0; font-weight: 600; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.02); z-index: 10; }
        .pane-content { flex: 1; overflow-y: auto; padding: 15px; background: #f8fafc; }
        
        /* Tables */
        .tables-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 20px; }
        .table-category-title { font-weight: 700; color: #475569; font-size: 0.85rem; text-transform: uppercase; margin-bottom: 10px; border-bottom: 1px solid #e2e8f0; padding-bottom: 5px; margin-top: 10px; }
        .table-category-title:first-child { margin-top: 0; }
        .table-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; cursor: pointer; transition: all 0.2s; position: relative; }
        .table-card:hover { border-color: #cbd5e1; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .table-card.active { border-color: #3b82f6; background: #eff6ff; box-shadow: 0 0 0 2px rgba(59,130,246,0.2); }
        .table-card.occupied { border-left: 4px solid #ef4444; }
        .table-card.available { border-left: 4px solid #10b981; }
        .table-name { font-weight: 600; font-size: 1.1rem; color: #1e293b; }
        .table-status { font-size: 0.8rem; color: #64748b; margin-top: 5px; }
        
        /* Menu Items */
        .menu-filter { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 10px; margin-bottom: 15px; }
        .menu-filter::-webkit-scrollbar { height: 4px; }
        .menu-filter::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 4px; }
        .btn-filter { padding: 8px 16px; border-radius: 20px; border: 1px solid #e2e8f0; background: #fff; color: #475569; font-weight: 500; white-space: nowrap; cursor: pointer; transition: all 0.2s; }
        .btn-filter.active { background: #1e293b; color: #fff; border-color: #1e293b; }
        
        .menu-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 15px; }
        .menu-item { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; cursor: pointer; transition: all 0.2s; display: flex; flex-direction: column; }
        .menu-item:hover { transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); border-color: #cbd5e1; }
        .menu-img { width: 100%; height: 120px; object-fit: cover; background: #f1f5f9; }
        .menu-info { padding: 12px; flex: 1; display: flex; flex-direction: column; justify-content: space-between; }
        .menu-name { font-weight: 600; color: #1e293b; font-size: 0.95rem; line-height: 1.4; margin-bottom: 8px; }
        .menu-price { color: #3b82f6; font-weight: 700; font-size: 0.9rem; }
        
        /* Cart */
        .cart-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: #94a3b8; text-align: center; }
        .cart-empty i { font-size: 3rem; margin-bottom: 15px; opacity: 0.5; }
        
        .cart-item { background: #fff; border-radius: 8px; padding: 12px; margin-bottom: 10px; border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 12px; }
        .cart-item-img { width: 50px; height: 50px; border-radius: 6px; object-fit: cover; }
        .cart-item-info { flex: 1; }
        .cart-item-name { font-weight: 600; font-size: 0.9rem; color: #1e293b; margin-bottom: 4px; }
        .cart-item-price { color: #64748b; font-size: 0.85rem; }
        .cart-item-status { font-size: 0.7rem; padding: 2px 6px; border-radius: 4px; display: inline-block; margin-top: 4px; }
        .status-draft { background: #e2e8f0; color: #475569; }
        .status-pending { background: #fef08a; color: #854d0e; }
        .status-cooking { background: #fed7aa; color: #9a3412; }
        .status-ready { background: #bbf7d0; color: #166534; }
        .status-served { background: #e2e8f0; color: #475569; }
        
        /* Quantity Controls */
        .qty-controls { display: flex; align-items: center; border: 1px solid #e2e8f0; border-radius: 4px; overflow: hidden; width: max-content; }
        .qty-btn { background: #f8fafc; border: none; padding: 2px 8px; cursor: pointer; color: #64748b; font-size: 0.9rem; }
        .qty-btn:hover { background: #e2e8f0; color: #1e293b; }
        .qty-input { width: 35px; border: none; text-align: center; border-left: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; font-size: 0.9rem; font-weight: 600; padding: 2px 0; }
        
        /* Loading Overlay */
        .loader-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(255,255,255,0.8); z-index: 9999; display: flex; justify-content: center; align-items: center; flex-direction: column; }
        .loader-overlay.hidden { display: none; }
        .spinner-border { width: 3rem; height: 3rem; color: #3b82f6; }
        
        /* Payment Methods */
        .payment-method-card { border: 2px solid #e2e8f0; border-radius: 8px; padding: 15px; cursor: pointer; text-align: center; transition: all 0.2s; }
        .payment-method-card:hover { border-color: #cbd5e1; background: #f8fafc; }
        .payment-method-card.active { border-color: #3b82f6; background: #eff6ff; color: #1d4ed8; }
        .payment-method-card i { font-size: 2rem; margin-bottom: 10px; }
        .qr-container { display: none; text-align: center; padding: 20px; background: #f8fafc; border-radius: 8px; margin-top: 15px; }

        /* Print Layout */
        @media screen {
            #print-section { display: none; }
        }
        @media print {
            body * { visibility: hidden; }
            #print-section, #print-section * { visibility: visible; }
            #print-section { position: absolute; left: 0; top: 0; width: 100%; font-family: monospace; padding: 10px; font-size: 12px; color: #000; }
            .print-header { text-align: center; margin-bottom: 15px; }
            .print-header h2 { font-size: 18px; margin: 0; padding: 0; }
            .print-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
            .print-table th, .print-table td { border-bottom: 1px dashed #000; padding: 5px 0; }
            .print-table th { text-align: left; }
            .print-table .text-right { text-align: right; }
            .print-total { font-weight: bold; font-size: 14px; }
            .print-footer { text-align: center; font-size: 11px; margin-top: 20px; border-top: 1px dashed #000; padding-top: 10px; }
        }
        
        .cart-footer { background: #fff; padding: 20px; border-top: 1px solid #e2e8f0; box-shadow: 0 -4px 6px -1px rgba(0,0,0,0.05); z-index: 10; }
        .cart-total-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .cart-total-label { font-size: 1.1rem; color: #64748b; font-weight: 500; }
        .cart-total-value { font-size: 1.5rem; color: #1e293b; font-weight: 700; }
        
        .btn-checkout { background: #10b981; color: #fff; border: none; padding: 15px; width: 100%; border-radius: 8px; font-weight: 600; font-size: 1.1rem; display: flex; justify-content: center; align-items: center; gap: 10px; transition: all 0.2s; }
        .btn-checkout:hover { background: #059669; }
        .btn-checkout:disabled { background: #94a3b8; cursor: not-allowed; }
        
        .btn-action { background: #3b82f6; color: #fff; border: none; padding: 10px; width: 100%; border-radius: 8px; font-weight: 600; margin-bottom: 10px; transition: all 0.2s; }
        .btn-action:hover { background: #2563eb; }

        /* Loader */
        .loader-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.7); display: flex; align-items: center; justify-content: center; z-index: 9999; display: none; }
        .spinner { width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3b82f6; border-radius: 50%; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>

<div class="loader-overlay" id="loader"><div class="spinner"></div></div>

<div class="pos-container">
    <!-- Pane 1: Tables -->
    <div class="pane">
        <div class="pane-header">
            <span><i class="fas fa-border-all me-2 text-warning"></i> SƠ ĐỒ BÀN</span>
            <a href="admin_dashboard.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-sign-out-alt"></i> Thoát</a>
        </div>
        <div class="pane-content" id="tables-container">
            <!-- Tables rendered here -->
        </div>
    </div>

    <!-- Pane 2: Menu -->
    <div class="pane">
        <div class="pane-header">
            <span><i class="fas fa-utensils me-2 text-warning"></i> THỰC ĐƠN</span>
            <span id="selected-table-label" class="badge bg-dark text-warning px-3 py-2" style="display: none; font-size: 0.9rem; cursor: pointer;" onclick="deselectTable()" title="Bỏ chọn bàn này"></span>
        </div>
        <div class="pane-content">
            <div class="menu-filter" id="menu-filters">
                <button class="btn-filter active" data-filter="all">Tất cả</button>
                <button class="btn-filter" data-filter="combo">Set Menu</button>
                <!-- Categories rendered here -->
            </div>
            <div class="menu-grid" id="menu-container">
                <!-- Menu items rendered here -->
            </div>
        </div>
    </div>

    <!-- Pane 3: Cart -->
    <div class="pane">
        <div class="pane-header">
            <span><i class="fas fa-file-invoice-dollar me-2 text-warning"></i> HÓA ĐƠN</span>
            <span id="order-id-label" class="text-muted" style="font-size: 0.85rem;"></span>
        </div>
        <div class="pane-content" id="cart-container" style="padding: 10px;">
            <div class="cart-empty">
                <i class="fas fa-hand-pointer"></i>
                <p>Vui lòng chọn bàn để bắt đầu gọi món</p>
            </div>
        </div>
        <div class="cart-footer">
            <div class="cart-total-row">
                <span class="cart-total-label">Tổng cộng</span>
                <span class="cart-total-value" id="cart-total">0đ</span>
            </div>
            <button class="btn btn-dark w-100 mb-2 font-weight-bold" id="btn-send-kitchen" style="display: none; border-radius: 8px; padding: 12px; background: #333; color: #A88746; border: 1px solid #333;" onclick="sendToKitchen()">
                <i class="fas fa-paper-plane me-2"></i> GỬI BẾP (<span id="draft-count">0</span> món mới)
            </button>
            <div style="display: flex; gap: 10px;">
                <button class="btn btn-outline-secondary" id="btn-cancel-order" disabled onclick="cancelOrder()" style="font-weight: 600; padding: 15px; border-radius: 8px;">
                    <i class="fas fa-trash-alt"></i> HỦY BÀN
                </button>
                <button class="btn-checkout" id="btn-checkout" disabled onclick="showCheckoutModal()" style="flex: 1;">
                    <i class="fas fa-cash-register"></i> THANH TOÁN
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentTableId = null;
let currentOrderId = null;
let globalMenu = { categories: [], foods: [], combos: [] };
let globalTables = [];
let currentFilter = 'all';

// Format currency
const formatMoney = (amount) => {
    return new Intl.NumberFormat('vi-VN').format(amount) + 'đ';
};

const showLoader = () => document.getElementById('loader').style.display = 'flex';
const hideLoader = () => document.getElementById('loader').style.display = 'none';

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadTables();
    loadMenu();
});

// Load Tables
async function loadTables() {
    try {
        const res = await fetch('controllers/pos_controller.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=get_tables'
        });
        const json = await res.json();
        if (json.success) {
            globalTables = json.data;
            renderTables(json.data);
        }
    } catch (e) {
        console.error(e);
    }
}

function renderTables(tables) {
    const container = document.getElementById('tables-container');
    container.innerHTML = '';
    
    const openTables = tables.filter(t => t.category !== 'room');
    const roomTables = tables.filter(t => t.category === 'room');
    
    const renderGroup = (title, groupTables) => {
        if (groupTables.length === 0) return;
        
        const titleEl = document.createElement('div');
        titleEl.className = 'table-category-title';
        titleEl.innerText = title;
        container.appendChild(titleEl);
        
        const grid = document.createElement('div');
        grid.className = 'tables-grid';
        
        groupTables.forEach(t => {
            const isOccupied = t.order_status === 'open';
            const hasBooking = t.upcoming_booking_id !== null;
            const statusClass = isOccupied ? 'occupied' : 'available';
            const statusText = isOccupied ? `${formatMoney(t.total_amount)}` : 'Trống';
            const activeClass = currentTableId == t.id ? 'active' : '';
            
            const bookingIcon = hasBooking ? `<i class="fas fa-clock text-warning position-absolute shadow-sm" style="top: -5px; right: -5px; font-size: 16px; background: #fff; border-radius: 50%; padding: 2px;" title="Bàn này đã được khách đặt trước trong hôm nay!"></i>` : '';
            
            const card = document.createElement('div');
            card.className = `table-card ${statusClass} ${activeClass} position-relative`;
            card.onclick = () => selectTable(t.id, t.table_code);
            card.innerHTML = `
                ${bookingIcon}
                <div class="table-name"><i class="fas fa-chair me-1 text-muted"></i> ${t.table_code}</div>
                <div class="table-status">${statusText}</div>
            `;
            grid.appendChild(card);
        });
        
        container.appendChild(grid);
    };
    
    renderGroup('Khu Phổ Thông', openTables);
    renderGroup('Phòng VIP', roomTables);
}

// Select Table
async function selectTable(tableId, tableCode) {
    if (currentTableId === tableId) {
        return deselectTable(); // Bấm lại lần nữa để bỏ chọn
    }
    
    // Check if table has booking but NO active order
    const tableData = globalTables.find(t => t.id == tableId);
    if (tableData && tableData.upcoming_booking_id && !tableData.current_order_id) {
        if (confirm(`Bàn ${tableCode} này có khách ĐẶT TRƯỚC (Booking #${tableData.upcoming_booking_id}). Khách đã đến và bạn muốn Nhận bàn (Check-in) bây giờ?`)) {
            checkinBooking(tableData.upcoming_booking_id, tableId);
            return;
        }
    }
    
    currentTableId = tableId;
    document.getElementById('selected-table-label').style.display = 'inline-block';
    document.getElementById('selected-table-label').innerHTML = `Bàn: ${tableCode} <i class="fas fa-times ms-2"></i>`;
    
    // Highlight table
    document.querySelectorAll('.table-card').forEach(el => el.classList.remove('active'));
    event.currentTarget.classList.add('active');
    
    await loadOrder();
}

function deselectTable() {
    currentTableId = null;
    currentOrderId = null;
    document.getElementById('selected-table-label').style.display = 'none';
    document.querySelectorAll('.table-card').forEach(el => el.classList.remove('active'));
    renderCart(null);
}

// Load Menu
async function loadMenu() {
    try {
        const res = await fetch('controllers/pos_controller.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=get_menu'
        });
        const json = await res.json();
        if (json.success) {
            globalMenu = json.data;
            renderMenuFilters();
            renderMenuItems();
        }
    } catch (e) {
        console.error(e);
    }
}

function renderMenuFilters() {
    const container = document.getElementById('menu-filters');
    // Keep 'all' and 'combo', then add categories
    const html = `
        <button class="btn-filter ${currentFilter === 'all' ? 'active' : ''}" onclick="setFilter('all')">Tất cả</button>
        <button class="btn-filter ${currentFilter === 'combo' ? 'active' : ''}" onclick="setFilter('combo')">Set Menu (Combo)</button>
        ${globalMenu.categories.map(c => `<button class="btn-filter ${currentFilter == c.id ? 'active' : ''}" onclick="setFilter(${c.id})">${c.name}</button>`).join('')}
    `;
    container.innerHTML = html;
}

function setFilter(filter) {
    currentFilter = filter;
    renderMenuFilters();
    renderMenuItems();
}

function renderMenuItems() {
    const container = document.getElementById('menu-container');
    container.innerHTML = '';
    
    let items = [];
    
    if (currentFilter === 'all') {
        items = [
            ...globalMenu.combos.map(c => ({...c, type: 'combo'})),
            ...globalMenu.foods.map(f => ({...f, type: 'food'}))
        ];
    } else if (currentFilter === 'combo') {
        items = globalMenu.combos.map(c => ({...c, type: 'combo'}));
    } else {
        items = globalMenu.foods.filter(f => f.category_id == currentFilter).map(f => ({...f, type: 'food'}));
    }
    
    items.forEach(item => {
        const imgPath = item.type === 'combo' 
            ? `../public/assets/img/combos/${item.image || 'default.jpg'}` 
            : `../public/assets/img/menu/${item.image || 'default.jpg'}`;
            
        const card = document.createElement('div');
        card.className = 'menu-item';
        card.onclick = () => addItemToOrder(item.type, item.id, item.price);
        card.innerHTML = `
            <img src="${imgPath}" class="menu-img" onerror="this.src='../public/assets/img/placeholder.jpg'">
            <div class="menu-info">
                <div class="menu-name">${item.name}</div>
                <div class="d-flex justify-content-between align-items-center mt-1">
                    <div class="menu-price">${formatMoney(item.price)}</div>
                    <button class="btn btn-sm btn-light rounded-circle" style="width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center; border: 1px solid #e2e8f0;" onclick="event.stopPropagation(); showItemDetail(${item.id}, '${item.type}')" title="Xem chi tiết">
                        <i class="fas fa-info text-warning"></i>
                    </button>
                </div>
            </div>
        `;
        container.appendChild(card);
    });
}

// CHECKIN BOOKING
function checkinBooking(bookingId, tableId) {
    showLoader();
    const formData = new URLSearchParams();
    formData.append('action', 'checkin_booking');
    formData.append('booking_id', bookingId);
    formData.append('table_id', tableId);

    fetch('controllers/pos_controller.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: formData.toString()
    })
    .then(res => res.json())
    .then(json => {
        hideLoader();
        if(json.success) {
            loadTables();
            setTimeout(() => {
                const updatedTable = globalTables.find(t => t.id == tableId);
                if (updatedTable && updatedTable.current_order_id) {
                    selectTable(tableId, updatedTable.table_code);
                }
            }, 500);
        } else {
            alert('Lỗi: ' + json.message);
        }
    })
    .catch(e => {
        hideLoader();
        console.error(e);
    });
}

// Load Order
async function loadOrder(showLoading = true) {
    if (!currentTableId) return;
    
    if (showLoading) showLoader();
    try {
        const res = await fetch(`controllers/pos_controller.php?action=get_order&table_id=${currentTableId}`);
        const json = await res.json();
        
        if (json.success) {
            window.currentOrderData = json.data && json.data.order ? json.data.order : null;
            renderCart(json.data);
            if(json.data && json.data.order) {
                currentOrderId = json.data.order.id;
            } else {
                currentOrderId = null;
            }
        }
    } catch (e) {
        console.error(e);
    } finally {
        if (showLoading) hideLoader();
    }
}

const statusMap = {
    'draft': { label: 'Chưa gửi bếp', class: 'status-draft' },
    'pending': { label: 'Chờ chế biến', class: 'status-pending' },
    'cooking': { label: 'Đang nấu', class: 'status-cooking' },
    'ready': { label: 'Đã xong', class: 'status-ready' },
    'served': { label: 'Đã lên món', class: 'status-served' }
};

function renderCart(data) {
    const container = document.getElementById('cart-container');
    const totalEl = document.getElementById('cart-total');
    const checkoutBtn = document.getElementById('btn-checkout');
    const cancelBtn = document.getElementById('btn-cancel-order');
    const orderLabel = document.getElementById('order-id-label');
    
    if (!data || !data.order) {
        container.innerHTML = `
            <div class="cart-empty">
                <i class="fas fa-utensils"></i>
                <p>${currentTableId ? 'Bàn này chưa gọi món.<br>Click vào món ăn bên cạnh để bắt đầu.' : 'Vui lòng chọn bàn để bắt đầu gọi món'}</p>
            </div>
        `;
        totalEl.innerText = '0đ';
        checkoutBtn.disabled = true;
        cancelBtn.disabled = true;
        orderLabel.innerText = '';
        return;
    }
    
    orderLabel.innerText = `Order #${data.order.id}`;
    totalEl.innerText = formatMoney(data.order.total_amount);
    cancelBtn.disabled = false;
    
    if (!data.items || data.items.length === 0) {
        container.innerHTML = `
            <div class="cart-empty">
                <i class="fas fa-receipt"></i>
                <p>Hóa đơn đang trống.<br>Bạn có thể bấm "Hủy Bàn" bên dưới để đóng hóa đơn này.</p>
            </div>
        `;
        checkoutBtn.disabled = true;
        document.getElementById('btn-send-kitchen').style.display = 'none';
        return;
    }
    
    checkoutBtn.disabled = false;
    
    let draftCount = 0;
    
    container.innerHTML = data.items.map(item => {
        if (item.status === 'draft') draftCount++;
        
        const imgPath = item.item_type === 'combo' 
            ? `../public/assets/img/combos/${item.image || 'default.jpg'}` 
            : `../public/assets/img/menu/${item.image || 'default.jpg'}`;
        
        const statusInfo = statusMap[item.status] || statusMap['pending'];
        
        // Chỉ cho phép đổi số lượng nếu món đang 'draft' hoặc 'pending'
        const canEdit = (item.status === 'draft' || item.status === 'pending');
        
        return `
            <div class="cart-item">
                <img src="${imgPath}" class="cart-item-img" onerror="this.src='../public/assets/img/placeholder.jpg'">
                <div class="cart-item-info">
                    <div class="cart-item-name">${item.name}</div>
                    ${item.notes ? `<div style="font-size: 0.75rem; color: #dc2626; margin-top: 2px;">${item.notes}</div>` : ''}
                    <div class="cart-item-price">${formatMoney(item.price)}</div>
                    <div class="cart-item-status ${statusInfo.class}">${statusInfo.label}</div>
                </div>
                <div class="qty-controls">
                    <button class="qty-btn" onclick="updateQty(${item.id}, ${item.quantity - 1})" ${!canEdit ? 'disabled' : ''}><i class="fas fa-minus"></i></button>
                    <input type="text" class="qty-input" value="${item.quantity}" readonly>
                    <button class="qty-btn" onclick="updateQty(${item.id}, ${item.quantity + 1})" ${!canEdit ? 'disabled' : ''}><i class="fas fa-plus"></i></button>
                </div>
            </div>
        `;
    }).join('');
    
    const btnSendKitchen = document.getElementById('btn-send-kitchen');
    if (draftCount > 0) {
        btnSendKitchen.style.display = 'block';
        document.getElementById('draft-count').innerText = draftCount;
    } else {
        btnSendKitchen.style.display = 'none';
    }

    let totalHtml = `
        <div class="d-flex justify-content-between mb-2">
            <span>Tổng tiền món:</span>
            <strong>${formatMoney(data.order.total_amount)}</strong>
        </div>
    `;

    if (data.order.deposit_amount && parseFloat(data.order.deposit_amount) > 0) {
        const deposit = parseFloat(data.order.deposit_amount);
        const remain = data.order.total_amount - deposit;
        totalHtml += `
            <div class="d-flex justify-content-between mb-2 text-danger">
                <span>Đã cọc (B #${data.order.booking_id}):</span>
                <strong>- ${formatMoney(deposit)}</strong>
            </div>
            <div class="d-flex justify-content-between mt-2 pt-2 border-top">
                <span class="fs-5">Cần thanh toán:</span>
                <strong class="fs-4 text-success">${formatMoney(Math.max(0, remain))}</strong>
            </div>
        `;
        totalEl.innerHTML = formatMoney(Math.max(0, remain));
    } else {
        totalEl.innerText = formatMoney(data.order.total_amount);
    }
    
    container.innerHTML += `
        <div class="cart-summary mt-3 pt-3 border-top">
            ${totalHtml}
        </div>
    `;
}

// Actions
async function addItemToOrder(item_type, item_id, price, notes = '') {
    if (!currentTableId) {
        alert('Vui lòng chọn bàn trước khi gọi món!');
        return;
    }
    
    showLoader();
    try {
        const formData = new URLSearchParams();
        formData.append('action', 'add_item');
        formData.append('table_id', currentTableId);
        formData.append('item_type', item_type);
        formData.append('item_id', item_id);
        formData.append('price', price);
        formData.append('quantity', 1);
        formData.append('notes', notes);

        const res = await fetch('controllers/pos_controller.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: formData.toString()
        });
        
        const json = await res.json();
        if (json.success) {
            await loadOrder();
            loadTables(); // Update table status/total on left pane
        } else {
            alert(json.message);
        }
    } catch (e) {
        console.error(e);
    } finally {
        hideLoader();
    }
}

async function sendToKitchen() {
    if (!currentOrderId) return;
    
    showLoader();
    try {
        const formData = new URLSearchParams();
        formData.append('action', 'send_to_kitchen');
        formData.append('order_id', currentOrderId);

        const res = await fetch('controllers/pos_controller.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: formData.toString()
        });
        
        const json = await res.json();
        if (json.success) {
            await loadOrder();
            // Thêm hiệu ứng toast hoặc thông báo nhỏ gọn thay vì alert để luồng mượt hơn
            // alert('Đã gửi bếp thành công!');
        }
    } catch (e) {
        console.error(e);
    } finally {
        hideLoader();
    }
}

async function updateQty(item_id, new_qty) {
    showLoader();
    try {
        const formData = new URLSearchParams();
        formData.append('action', 'update_qty');
        formData.append('item_id', item_id);
        formData.append('quantity', new_qty);

        const res = await fetch('controllers/pos_controller.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: formData.toString()
        });
        
        const json = await res.json();
        if (json.success) {
            await loadOrder();
            loadTables();
        }
    } catch (e) {
        console.error(e);
    } finally {
        hideLoader();
    }
}

// ---- CHECKOUT MODAL LOGIC ----
let currentCheckoutTotal = 0;
let currentPaymentMethod = 'cash';

function showCheckoutModal() {
    if (!currentOrderId) return;
    
    if (window.currentOrderData && parseFloat(window.currentOrderData.deposit_amount) > 0) {
        const deposit = parseFloat(window.currentOrderData.deposit_amount);
        const remain = window.currentOrderData.total_amount - deposit;
        currentCheckoutTotal = Math.max(0, remain);
    } else {
        const totalText = document.getElementById('cart-total').innerText;
        currentCheckoutTotal = parseInt(totalText.replace(/\D/g,'')) || 0;
    }
    
    document.getElementById('checkout-modal-total').innerText = formatMoney(currentCheckoutTotal);
    
    // Reset payment method to cash
    selectPaymentMethod('cash');
    
    // Default check print bill
    document.getElementById('printBillCheck').checked = true;
    
    const modal = new bootstrap.Modal(document.getElementById('checkoutModal'));
    modal.show();
}

function selectPaymentMethod(method) {
    currentPaymentMethod = method;
    document.querySelectorAll('.payment-method-card').forEach(el => el.classList.remove('active'));
    document.getElementById(`pm-${method}`).classList.add('active');
    
    const qrContainer = document.getElementById('qr-container');
    if (method === 'transfer') {
        qrContainer.style.display = 'block';
        // Generate VietQR
        // BANK INFO: Placeholder
        const bankId = 'MB'; // MB Bank
        const accountNo = '000000000'; // Placeholder
        const accountName = 'NHA HANG DEMO';
        const addInfo = `Thanh toan HD ${currentOrderId}`;
        
        const qrUrl = `https://img.vietqr.io/image/${bankId}-${accountNo}-compact2.png?amount=${currentCheckoutTotal}&addInfo=${encodeURIComponent(addInfo)}&accountName=${encodeURIComponent(accountName)}`;
        document.getElementById('vietqr-img').src = qrUrl;
    } else {
        qrContainer.style.display = 'none';
    }
}

async function processCheckout() {
    if (!currentOrderId) return;
    
    showLoader();
    try {
        const formData = new URLSearchParams();
        formData.append('action', 'checkout');
        formData.append('order_id', currentOrderId);
        formData.append('payment_method', currentPaymentMethod);

        const res = await fetch('controllers/pos_controller.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: formData.toString()
        });
        
        const json = await res.json();
        if (json.success) {
            bootstrap.Modal.getInstance(document.getElementById('checkoutModal')).hide();
            
            // Print bill if checked
            const printChecked = document.getElementById('printBillCheck').checked;
            if (printChecked && json.data && json.data.order) {
                printBill(json.data.order, json.data.items);
            } else {
                alert('Thanh toán thành công!');
            }
            
            deselectTable();
            loadTables();
        } else {
            alert(json.message);
        }
    } catch (e) {
        console.error(e);
        alert('Lỗi kết nối khi thanh toán');
    } finally {
        hideLoader();
    }
}

function printBill(order, items) {
    const tableCode = document.getElementById('selected-table-label').innerText.replace('Bàn: ', '').replace(' Bỏ chọn', '').trim();
    
    let itemsHtml = '';
    items.forEach(i => {
        itemsHtml += `
            <tr>
                <td>${i.name}</td>
                <td class="text-right">${i.quantity}</td>
                <td class="text-right">${formatMoney(i.price)}</td>
                <td class="text-right">${formatMoney(i.price * i.quantity)}</td>
            </tr>
        `;
    });
    
    const printHtml = `
        <div class="print-header">
            <h2>NHÀ HÀNG CAO CẤP</h2>
            <div>123 Đường ABC, Quận XYZ, TP.HCM</div>
            <div>SĐT: 0123.456.789</div>
            <br>
            <div style="font-weight: bold; font-size: 14px;">HÓA ĐƠN THANH TOÁN</div>
            <div>Order: #${order.id} | Bàn: ${tableCode}</div>
            <div>Ngày: ${new Date().toLocaleString('vi-VN')}</div>
            <div>Thu ngân: Admin</div>
        </div>
        
        <table class="print-table">
            <thead>
                <tr>
                    <th>Tên món</th>
                    <th class="text-right">SL</th>
                    <th class="text-right">Đơn giá</th>
                    <th class="text-right">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                ${itemsHtml}
            </tbody>
        </table>
        
        <div class="print-table" style="border: none; border-top: 1px dashed #000; padding-top: 10px;">
            <div style="display: flex; justify-content: space-between;" class="print-total">
                <span>TỔNG CỘNG:</span>
                <span>${formatMoney(order.total_amount)}</span>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 12px; margin-top: 5px;">
                <span>Hình thức TT:</span>
                <span>${currentPaymentMethod === 'cash' ? 'Tiền mặt' : 'Chuyển khoản'}</span>
            </div>
        </div>
        
        <div class="print-footer">
            <div>Cảm ơn quý khách và hẹn gặp lại!</div>
            <div>Powered by Hệ thống POS</div>
        </div>
    `;
    
    document.getElementById('print-section').innerHTML = printHtml;
    
    // Call print
    window.print();
}

async function cancelOrder() {
    if (!currentOrderId) return;
    
    if (!confirm('Bạn có MƯỚN HỦY TOÀN BỘ hóa đơn của bàn này? Hành động này không thể hoàn tác.')) return;
    
    showLoader();
    try {
        const formData = new URLSearchParams();
        formData.append('action', 'cancel_order');
        formData.append('order_id', currentOrderId);

        const res = await fetch('controllers/pos_controller.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: formData.toString()
        });
        
        const json = await res.json();
        if (json.success) {
            deselectTable();
            loadTables();
        } else {
            alert(json.message);
        }
    } catch (e) {
        console.error(e);
    } finally {
        hideLoader();
    }
}

let currentDetailItem = null;

function showItemDetail(id, type) {
    let item = null;
    if (type === 'combo') {
        item = globalMenu.combos.find(c => c.id == id);
    } else {
        item = globalMenu.foods.find(f => f.id == id);
    }
    if (!item) return;
    
    currentDetailItem = { ...item, type };
    
    const imgPath = type === 'combo' 
        ? `../public/assets/img/combos/${item.image || 'default.jpg'}` 
        : `../public/assets/img/menu/${item.image || 'default.jpg'}`;
        
    document.getElementById('detail-img').src = imgPath;
    document.getElementById('detail-name').innerText = item.name;
    document.getElementById('detail-price').innerText = formatMoney(item.price);
    
    let detailHtml = '';
    
    // Bắt đầu nhóm mô tả chung
    if (item.description) {
        detailHtml += `<p class="mb-3 text-start">${item.description.replace(/\n/g, '<br>')}</p>`;
    } else {
        detailHtml += `<p class="mb-3 text-muted text-start"><i>Chưa có mô tả chung cho món này.</i></p>`;
    }

    if (type === 'combo') {
        if (item.combo_items_list) {
            detailHtml += `
                <div class="text-start p-3 mt-2" style="background: #e0f2fe; border-radius: 8px; border-left: 4px solid #0284c7;">
                    <div style="font-weight: 700; color: #0284c7; margin-bottom: 5px;"><i class="fas fa-list-ul me-2"></i> Thành phần Set:</div>
                    <div style="color: #0f172a;">${item.combo_items_list.replace(/, /g, '<br>• ')}</div>
                </div>
            `;
        }
    } else {
        // Food details
        let badgesHtml = '';
        if (item.category_name) {
            badgesHtml += `<span class="badge bg-secondary me-2">${item.category_name}</span>`;
        }
        if (item.is_chef_recommended == 1) {
            badgesHtml += `<span class="badge bg-warning text-dark"><i class="fas fa-star text-danger"></i> Chef Recommended</span>`;
        }
        if (badgesHtml) {
            detailHtml = `<div class="text-start mb-2">${badgesHtml}</div>` + detailHtml;
        }
        
        let extraInfo = '';
        if (item.chef_note) {
            extraInfo += `<div class="mb-2"><i class="fas fa-utensils text-warning me-2"></i> <strong>Ghi chú Bếp:</strong> ${item.chef_note}</div>`;
        }
        if (item.allergens) {
            extraInfo += `<div class="text-danger"><i class="fas fa-exclamation-triangle me-2"></i> <strong>Cảnh báo Dị ứng:</strong> ${item.allergens}</div>`;
        }
        if (extraInfo) {
            detailHtml += `<div class="text-start mt-3" style="font-size: 0.9rem; background: #fff3cd; border-left: 4px solid #ffc107; padding: 12px; border-radius: 4px;">${extraInfo}</div>`;
        }
        
        if (item.recipe_list) {
            const ingredients = item.recipe_list.split('||');
            let recipeHtml = `
                <div class="text-start p-3 mt-3" style="background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <div style="font-weight: 700; color: #475569; margin-bottom: 8px;"><i class="fas fa-balance-scale me-2 text-secondary"></i> Định mức nguyên liệu:</div>
                    <div style="color: #334155; font-size: 0.9rem;">
            `;
            ingredients.forEach(ing => {
                recipeHtml += `<div class="mb-1">• ${ing}</div>`;
            });
            recipeHtml += `</div></div>`;
            detailHtml += recipeHtml;
        }
        if (item.topping_list) {
            const toppings = item.topping_list.split('||');
            let toppingHtml = `
                <div class="text-start p-3 mt-3" style="background: #fdf8f6; border-radius: 8px; border: 1px solid #fecdd3;">
                    <div style="font-weight: 700; color: #9f1239; margin-bottom: 8px;"><i class="fas fa-plus-circle me-2 text-danger"></i> Topping / Lựa chọn thêm:</div>
                    <div style="color: #4c0519; font-size: 0.9rem;">
            `;
            toppings.forEach((top, index) => {
                const parts = top.split('(+');
                const name = parts[0].trim();
                const priceStr = parts[1] ? parts[1].replace('đ)', '').trim() : '0';
                const priceNum = parseInt(priceStr.replace(/\./g, '')) || 0;
                toppingHtml += `
                    <div class="form-check mb-1">
                        <input class="form-check-input topping-checkbox" type="checkbox" value="${name}" data-price="${priceNum}" id="top_${item.id}_${index}">
                        <label class="form-check-label" style="cursor:pointer;" for="top_${item.id}_${index}">${top}</label>
                    </div>`;
            });
            toppingHtml += `</div></div>`;
            detailHtml += toppingHtml;
        }
    }

    document.getElementById('detail-desc').innerHTML = detailHtml;
    
    const modal = new bootstrap.Modal(document.getElementById('itemDetailModal'));
    modal.show();
}

function addCurrentDetailItem() {
    if (currentDetailItem) {
        let selectedToppings = [];
        let extraPrice = 0;
        
        document.querySelectorAll('.topping-checkbox:checked').forEach(cb => {
            selectedToppings.push(cb.value);
            extraPrice += parseInt(cb.getAttribute('data-price')) || 0;
        });
        
        let notes = selectedToppings.length > 0 ? "Topping: " + selectedToppings.join(", ") : "";
        let finalPrice = parseInt(currentDetailItem.price) + extraPrice;
        
        addItemToOrder(currentDetailItem.type, currentDetailItem.id, finalPrice, notes);
        bootstrap.Modal.getInstance(document.getElementById('itemDetailModal')).hide();
    }
}
</script>

<!-- Modal Chi tiết món -->
<div class="modal fade" id="itemDetailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center pt-0">
        <img id="detail-img" src="" style="width: 100%; height: 220px; object-fit: cover; border-radius: 8px; margin-bottom: 15px; background: #f1f5f9;">
        <h5 id="detail-name" style="font-weight: 700; color: #1e293b; font-size: 1.25rem; margin-bottom: 5px;">Tên món</h5>
        <div id="detail-price" style="color: #3b82f6; font-weight: 700; font-size: 1.1rem; margin-bottom: 15px;">0đ</div>
        <div id="detail-desc" style="color: #475569; font-size: 0.95rem; line-height: 1.6; text-align: justify; padding: 0 10px; max-height: 150px; overflow-y: auto;">Mô tả chi tiết...</div>
      </div>
      <div class="modal-footer border-0 d-flex justify-content-center pb-4">
        <button type="button" class="btn btn-primary px-4 py-2" style="border-radius: 20px; font-weight: 600;" onclick="addCurrentDetailItem()">
            <i class="fas fa-plus me-2"></i> THÊM VÀO BÀN
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Print Section -->
<div id="print-section"></div>

<!-- Checkout Modal -->
<div class="modal fade" id="checkoutModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title font-weight-bold">Xác Nhận Thanh Toán</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="text-center mb-4">
            <div class="text-muted mb-1">Cần thanh toán</div>
            <div id="checkout-modal-total" style="font-size: 2rem; font-weight: 700; color: #1e293b;">0đ</div>
        </div>
        
        <div class="mb-3 font-weight-bold">Phương thức thanh toán:</div>
        <div class="row g-3">
            <div class="col-6">
                <div class="payment-method-card active" id="pm-cash" onclick="selectPaymentMethod('cash')">
                    <i class="fas fa-money-bill-wave"></i>
                    <div class="font-weight-bold">Tiền mặt</div>
                </div>
            </div>
            <div class="col-6">
                <div class="payment-method-card" id="pm-transfer" onclick="selectPaymentMethod('transfer')">
                    <i class="fas fa-qrcode"></i>
                    <div class="font-weight-bold">Chuyển khoản (QR)</div>
                </div>
            </div>
        </div>
        
        <div id="qr-container" class="qr-container">
            <div class="mb-2 font-weight-bold text-warning">Quét mã để thanh toán</div>
            <img id="vietqr-img" src="" style="max-width: 100%; height: auto; border-radius: 8px;">
            <div class="mt-2 text-muted" style="font-size: 0.85rem;">Ngân hàng: MB Bank<br>STK: 000000000<br>Tên tài khoản: NHA HANG DEMO</div>
        </div>
        
        <div class="form-check mt-4 ms-1">
            <input class="form-check-input" type="checkbox" id="printBillCheck" checked>
            <label class="form-check-label" for="printBillCheck">
                In hóa đơn sau khi thanh toán
            </label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
        <button type="button" class="btn btn-primary px-4 font-weight-bold" onclick="processCheckout()">XÁC NHẬN</button>
      </div>
    </div>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Initial load
loadTables();

// Auto-refresh mechanism (Polling every 10 seconds)
setInterval(() => {
    // Only refresh tables silently
    loadTables(false);
    
    // If an order is currently open, refresh it silently to get kitchen updates
    if (currentTableId) {
        loadOrder(false); // false means don't show loader overlay to avoid flickering
    }
}, 10000);
</script>
</body>
</html>
