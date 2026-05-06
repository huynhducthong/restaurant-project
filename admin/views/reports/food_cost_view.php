<?php
// admin/views/reports/food_cost_view.php
include __DIR__ . '/../../../public/admin_layout_header.php';
?>
<div class="container-fluid py-4 bg-light min-vh-100">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-uppercase"><i class="fas fa-chart-pie me-2 text-warning"></i>Báo Cáo Lợi Nhuận Món Ăn</h3>
        <button class="btn btn-dark" onclick="window.print()"><i class="fas fa-print me-2"></i>In Báo Cáo</button>
    </div>

    <div class="card shadow-sm border-0">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Món ăn</th>
                    <th>Thành phần</th>
                    <th>Giá Vốn (Cost)</th>
                    <th>Giá Bán (Price)</th>
                    <th>Lợi nhuận (Profit)</th>
                    <th>Tỷ suất (Margin)</th>
                </tr>
            </thead>
            <tbody class="bg-white">
                <?php foreach ($food_costs as $fc): 
                    $profit = $fc['selling_price'] - $fc['real_cost'];
                    $margin = $fc['selling_price'] > 0 ? ($profit / $fc['selling_price']) * 100 : 0;
                    
                    // Logic phân loại màu sắc
                    $margin_class = 'text-success fw-bold';
                    $bg_class = '';
                    if ($margin < 30) { $margin_class = 'text-danger fw-bold'; $bg_class = 'bg-danger-subtle'; }
                    elseif ($margin < 50) { $margin_class = 'text-warning fw-bold'; }
                ?>
                <tr class="<?= $bg_class ?>">
                    <td>
                        <div class="d-flex align-items-center">
                            <img src="../../public/assets/img/menu/<?= htmlspecialchars($fc['image']) ?>" 
                                 onerror="this.src='../../public/assets/img/menu/default.jpg'" 
                                 style="width:40px;height:40px;object-fit:cover;border-radius:8px;margin-right:10px;">
                            <strong><?= htmlspecialchars($fc['food_name']) ?></strong>
                        </div>
                    </td>
                    <td><span class="badge bg-secondary"><?= $fc['ingredient_count'] ?> NL</span></td>
                    <td class="text-danger fw-bold"><?= number_format($fc['real_cost']) ?> đ</td>
                    <td class="text-primary fw-bold"><?= number_format($fc['selling_price']) ?> đ</td>
                    <td class="fw-bold"><?= number_format($profit) ?> đ</td>
                    <td class="<?= $margin_class ?>"><?= round($margin, 1) ?> %</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>