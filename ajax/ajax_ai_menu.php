<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Vui lòng đăng nhập để sử dụng tính năng này.']);
    exit;
}

// 1. Kiểm tra Cache
// if (isset($_SESSION['ai_menu_recommendation']) && !empty($_SESSION['ai_menu_recommendation'])) {
//     echo json_encode(['status' => 'success', 'data' => $_SESSION['ai_menu_recommendation']]);
//     exit;
// }

$db = (new Database())->getConnection();

// Lấy API Key từ .env
$gemini_api_key = $_ENV['GEMINI_API_KEY'] ?? '';

if (empty($gemini_api_key)) {
    echo json_encode(['status' => 'error', 'message' => 'Tính năng chưa được cấu hình (Thiếu API Key).']);
    exit;
}

try {
    // 2. Fetch User DNA
    $stmt = $db->prepare("SELECT allergies, flavor_profile, fav_ingredients, disliked_ingredients FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$u) {
        echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy thông tin người dùng.']);
        exit;
    }

    $user_allergies = $u['allergies'] ? array_map('trim', explode(',', mb_strtolower($u['allergies'], 'UTF-8'))) : [];

    // Giải phóng session lock để người dùng có thể bấm chuyển trang trong lúc chờ AI phản hồi
    session_write_close();
    
    // Hàm kiểm tra dị ứng (bản copy từ menu.php để dùng chung logic)
    function hasAllergenLocal($food_allergens_str, $user_allergies) {
        if (empty($user_allergies)) return false;
        $food_allergens = array_map('trim', explode(',', mb_strtolower($food_allergens_str, 'UTF-8')));
        
        $aliases = [
            'hải sản' => ['tôm', 'cua', 'ghẹ', 'cá', 'mực', 'bạch tuộc', 'ốc', 'hàu', 'sò', 'nghêu', 'tuna', 'salmon', 'scallop'],
            'sữa' => ['bơ', 'phô mai', 'cheese', 'cream', 'sữa tươi', 'sữa đặc', 'yoghurt', 'sữa chua'],
            'đậu phộng' => ['lạc', 'peanut'],
            'gluten' => ['lúa mì', 'bột mì', 'wheat', 'bread', 'bánh mì', 'pasta', 'pizza'],
            'trứng' => ['egg', 'trứng gà', 'trứng vịt', 'trứng cút']
        ];

        foreach ($user_allergies as $ua) {
            if (empty($ua)) continue;
            
            $check_terms = [$ua];
            if (isset($aliases[$ua])) {
                $check_terms = array_merge($check_terms, $aliases[$ua]);
            }
            
            foreach($food_allergens as $fa) {
                if (empty($fa)) continue;
                foreach ($check_terms as $term) {
                    if (strpos($fa, $term) !== false) return true;
                }
            }
        }
        return false;
    }

    // 3. Chuẩn bị Menu data (Loại bỏ các món bị dị ứng)
    $foods = $db->query("SELECT f.name, f.description, f.allergens, c.name as cat_name, (SELECT GROUP_CONCAT(CONCAT(i.item_name, ',', IFNULL(i.category, '')) SEPARATOR ',') FROM food_recipes fr JOIN inventory i ON fr.ingredient_id = i.id WHERE fr.food_id = f.id) as recipe_ingredients FROM foods f LEFT JOIN categories c ON f.category_id = c.id WHERE f.is_active = 1")->fetchAll(PDO::FETCH_ASSOC);
    
    $safe_foods_list = [];
    foreach ($foods as $f) {
        $all_ingredients_str = ($f['allergens'] ?? '') . ',' . ($f['recipe_ingredients'] ?? '') . ',' . ($f['cat_name'] ?? '') . ',' . ($f['name'] ?? '');
        if (!hasAllergenLocal($all_ingredients_str, $user_allergies)) {
            $safe_foods_list[] = "- Món Lẻ: " . $f['name'] . " (Mô tả: " . $f['description'] . ")";
        }
    }

    if (empty($safe_foods_list)) {
        echo json_encode(['status' => 'error', 'message' => 'Xin lỗi, hiện tại nhà hàng không có món nào phù hợp với dị ứng của bạn.']);
        exit;
    }

    shuffle($safe_foods_list);
    $safe_foods_list = array_slice($safe_foods_list, 0, 30);
    $menu_str = implode("\n", $safe_foods_list);
    
    // 3.5 Lấy danh sách Set Menu
    $combos = $db->query("
        SELECT c.id, c.name, c.description, GROUP_CONCAT(f.name SEPARATOR ', ') as list_foods, GROUP_CONCAT(f.allergens SEPARATOR ',') as list_allergens
        FROM combos c
        LEFT JOIN combo_items ci ON c.id = ci.combo_id
        LEFT JOIN foods f ON ci.food_id = f.id
        WHERE c.status = 1
        GROUP BY c.id
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    $safe_combo_list = [];
    foreach ($combos as $c) {
        if (!hasAllergenLocal($c['list_allergens'], $user_allergies)) {
            $safe_combo_list[] = "- Set Menu: " . $c['name'] . " (Bao gồm các món: " . $c['list_foods'] . ")";
        }
    }
    $combo_str = empty($safe_combo_list) ? "Không có Set Menu nào an toàn." : implode("\n", $safe_combo_list);

    // 4. Chuẩn bị DNA string
    $dna = [];
    if (!empty($u['flavor_profile'])) $dna[] = "Khẩu vị: " . $u['flavor_profile'];
    if (!empty($u['fav_ingredients'])) $dna[] = "Món/nguyên liệu yêu thích: " . $u['fav_ingredients'];
    if (!empty($u['disliked_ingredients'])) $dna[] = "Không thích ăn: " . $u['disliked_ingredients'];
    if (!empty($u['allergies'])) $dna[] = "Bị dị ứng nghiêm trọng với: " . $u['allergies'] . " (TUYỆT ĐỐI KHÔNG GỢI Ý MÓN CÓ THÀNH PHẦN NÀY)";
    
    $dna_str = empty($dna) ? "Khách hàng có khẩu vị chung, dễ ăn." : implode("; ", $dna);

    // 5. Xây dựng Prompt Gửi Cho Gemini
    $prompt = "Bạn là Bếp trưởng điều hành tại nhà hàng cao cấp Nhã. Một thực khách đang xem menu của nhà hàng và muốn nhận được lời khuyên.

DNA Ẩm thực của khách:
$dna_str

Danh sách các món ăn lẻ AN TOÀN có trong nhà hàng (Đã được lọc bỏ dị ứng):
$menu_str

Danh sách các Set Menu AN TOÀN có trong nhà hàng (Đã được lọc bỏ dị ứng):
$combo_str

NHIỆM VỤ:
- Hãy chọn ra đúng 1 Set Menu và 3 món ăn lẻ phù hợp nhất với khẩu vị và sở thích của khách từ danh sách trên.
- Nếu khách không có sở thích đặc biệt nào, hãy chọn ngẫu nhiên các món Signature ngon nhất.
- Viết 1 đoạn văn ngắn gọn, thân thiện (khoảng 3-4 câu) giới thiệu bản thân là Bếp trưởng Nhã, sau đó đề xuất các món này và giải thích ngắn gọn vì sao nó hợp với khách.
- Xưng hô 'Tôi' (Bếp trưởng) và 'Bạn' (Thực khách).
- Trả về kết quả trực tiếp dưới định dạng Markdown để in ra HTML.

ĐỊNH DẠNG YÊU CẦU:
Chào bạn, tôi là Bếp trưởng của Nhã...
...
* **[Tên Set Menu]**: [Lý do ngắn gọn]
* **[Tên món lẻ 1]**: [Lý do ngắn gọn]
* **[Tên món lẻ 2]**: [Lý do ngắn gọn]
* **[Tên món lẻ 3]**: [Lý do ngắn gọn]";

    // 6. Gọi API Gemini
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=" . $gemini_api_key;
    
    $payload = json_encode([
        "contents" => [
            [
                "parts" => [
                    ["text" => $prompt]
                ]
            ]
        ]
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpcode !== 200) {
        $err = json_decode($response, true);
        echo json_encode(['status' => 'error', 'message' => 'Lỗi từ hệ thống AI (Gemini).']);
        exit;
    }

    $res_data = json_decode($response, true);
    if (!isset($res_data['candidates'][0]['content']['parts'][0]['text'])) {
        echo json_encode(['status' => 'error', 'message' => 'Lỗi định dạng AI.']);
        exit;
    }

    $generated_rec = $res_data['candidates'][0]['content']['parts'][0]['text'];

    // 7. Lưu Cache vào Session
    session_start();
    $_SESSION['ai_menu_recommendation'] = $generated_rec;
    session_write_close();

    echo json_encode(['status' => 'success', 'data' => $generated_rec]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}
