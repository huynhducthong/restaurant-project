<?php
/**
 * Inventory Helper - Xử lý các logic chung về kho và đơn vị
 */

if (!function_exists('convert_to_base_unit')) {
    /**
     * Quy đổi số lượng từ đơn vị định mức về đơn vị tồn kho
     * 
     * @param float $quantity Số lượng cần quy đổi
     * @param string $recipe_unit Đơn vị trong công thức (định mức)
     * @param string $inventory_unit Đơn vị trong kho
     * @return float Số lượng sau khi quy đổi
     */
    function convert_to_base_unit($quantity, $recipe_unit, $inventory_unit) {
        $recipe_unit = strtolower(trim($recipe_unit));
        $inventory_unit = strtolower(trim($inventory_unit));

        // Nếu đơn vị giống nhau, không cần quy đổi
        if ($recipe_unit === $inventory_unit) {
            return (float)$quantity;
        }

        // --- NHÓM KHỐI LƯỢNG (GRAM -> KILOGRAM) ---
        if ($recipe_unit === 'g' && ($inventory_unit === 'kg' || $inventory_unit === 'kí' || $inventory_unit === 'kilogram')) {
            return (float)$quantity / 1000;
        }
        if (($recipe_unit === 'kg' || $recipe_unit === 'kí') && $inventory_unit === 'g') {
            return (float)$quantity * 1000;
        }

        // --- NHÓM THỂ TÍCH (ML -> LÍT) ---
        if ($recipe_unit === 'ml' && ($inventory_unit === 'l' || $inventory_unit === 'lít')) {
            return (float)$quantity / 1000;
        }
        if (($recipe_unit === 'l' || $recipe_unit === 'lít') && $inventory_unit === 'ml') {
            return (float)$quantity * 1000;
        }

        // Tương lai có thể thêm các đơn vị khác ở đây (ví dụ: Lon -> Thùng)

        return (float)$quantity; // Mặc định trả về giá trị gốc nếu không khớp quy tắc
    }
}
