<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Vui lòng đăng nhập để sử dụng tính năng này.']);
    exit;
}

$db = (new Database())->getConnection();
$gemini_api_key = $_ENV['GEMINI_API_KEY'] ?? '';

if (empty($gemini_api_key)) {
    echo json_encode(['status' => 'error', 'message' => 'Tính năng chưa được cấu hình (Thiếu API Key).']);
    exit;
}

try {
    // 1. Lấy thông tin DNA Ẩm thực của khách
    $stmt = $db->prepare("SELECT allergies, flavor_profile, fav_ingredients, disliked_ingredients FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);

    $dna = [];
    if ($u) {
        if (!empty($u['flavor_profile'])) $dna[] = "Khẩu vị: " . $u['flavor_profile'];
        if (!empty($u['fav_ingredients'])) $dna[] = "Sở thích: " . $u['fav_ingredients'];
        if (!empty($u['disliked_ingredients'])) $dna[] = "Không thích ăn: " . $u['disliked_ingredients'];
        if (!empty($u['allergies'])) $dna[] = "DỊ ỨNG (TUYỆT ĐỐI TRÁNH): " . $u['allergies'];
    }
    
    $dna_str = empty($dna) ? "Khách hàng có khẩu vị chung, dễ ăn, không có dị ứng đặc biệt." : implode("; ", $dna);

    // 2. Lấy danh sách các Set Menu (Combos) đang active và các món bên trong
    $combos = $db->query("
        SELECT c.id, c.name, c.description, GROUP_CONCAT(f.name SEPARATOR ', ') as list_foods
        FROM combos c
        LEFT JOIN combo_items ci ON c.id = ci.combo_id
        LEFT JOIN foods f ON ci.food_id = f.id
        WHERE c.status = 1
        GROUP BY c.id
    ")->fetchAll(PDO::FETCH_ASSOC);

    if (empty($combos)) {
        echo json_encode(['status' => 'error', 'message' => 'Hiện không có Set Menu nào để gợi ý.']);
        exit;
    }

    $combo_list_str = "";
    foreach ($combos as $cb) {
        $combo_list_str .= "- [ID: " . $cb['id'] . "] Tên: " . $cb['name'] . " (Món gồm: " . $cb['list_foods'] . ")\n";
    }

    // 3. Xây dựng Prompt cho Gemini
    $prompt = "Bạn là Bếp trưởng điều hành tại nhà hàng Fine Dining Nhã. Khách hàng đang cần gợi ý 1 Set Menu phù hợp nhất với khẩu vị của họ.
    
Hồ sơ Ẩm thực (DNA) của khách:
$dna_str

Danh sách các Set Menu hiện có:
$combo_list_str

YÊU CẦU BẮT BUỘC:
1. Bạn PHẢI chọn ra chính xác 1 ID từ danh sách trên phù hợp nhất với DNA của khách.
2. TUYỆT ĐỐI KHÔNG chọn Set Menu chứa món ăn có nguyên liệu mà khách bị Dị ứng.
3. Giải thích ngắn gọn (tối đa 2 câu) lý do bạn chọn Set Menu này, xưng 'Tôi' (Bếp trưởng) và 'Bạn' (Khách hàng). Giải thích bắt đầu bằng chữ 'Dựa vào...'.
4. Trả về KẾT QUẢ ĐÚNG CHUẨN ĐỊNH DẠNG JSON NHƯ SAU (KHÔNG BAO GỒM BẤT KỲ VĂN BẢN NÀO KHÁC BÊN NGOÀI JSON, KHÔNG DÙNG BLOCK MARKDOWN ```json):
{
  \"combo_id\": <ID nguyên bằng số>,
  \"reason\": \"<lời giải thích>\"
}";

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
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpcode !== 200) {
        echo json_encode(['status' => 'error', 'message' => 'Lỗi từ API AI.']);
        exit;
    }

    $res_data = json_decode($response, true);
    if (!isset($res_data['candidates'][0]['content']['parts'][0]['text'])) {
        echo json_encode(['status' => 'error', 'message' => 'Lỗi định dạng AI.']);
        exit;
    }

    $generated_text = trim($res_data['candidates'][0]['content']['parts'][0]['text']);
    
    // Loại bỏ markdown ```json nếu AI lỡ sinh ra
    $generated_text = str_replace(['```json', '```'], '', $generated_text);
    $generated_text = trim($generated_text);

    $json_result = json_decode($generated_text, true);

    if (json_last_error() === JSON_ERROR_NONE && isset($json_result['combo_id'])) {
        echo json_encode(['status' => 'success', 'data' => $json_result]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'AI trả về dữ liệu không hợp lệ.']);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}
