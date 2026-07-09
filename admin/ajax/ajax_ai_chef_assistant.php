<?php
// File: admin/ajax/ajax_ai_chef_assistant.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

// Check admin role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff', 'manager'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if (!isset($_POST['booking_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing ID']);
    exit;
}

$booking_id = (int)$_POST['booking_id'];
$db = (new Database())->getConnection();

// Lấy API Key từ .env
$gemini_api_key = $_ENV['GEMINI_API_KEY'] ?? '';

if (empty($gemini_api_key)) {
    echo json_encode(['status' => 'error', 'message' => 'Chưa cấu hình GEMINI_API_KEY trong file .env. Vui lòng mở file .env ở thư mục gốc và thêm GEMINI_API_KEY=YOUR_KEY.']);
    exit;
}

try {
    // 1. Fetch Booking and User DNA details
    $stmt = $db->prepare("
        SELECT sb.chef_requirements, sb.ai_suggested_menu, 
               u.doneness, u.flavor_profile, u.fav_ingredients, u.disliked_ingredients, u.allergies
        FROM service_bookings sb
        LEFT JOIN users u ON sb.user_id = u.id
        WHERE sb.id = ?
    ");
    $stmt->execute([$booking_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        echo json_encode(['status' => 'error', 'message' => 'Booking không tồn tại.']);
        exit;
    }

    // Nếu đã có menu tạo sẵn trong DB, không gọi lại API để tiết kiệm chi phí/thời gian
    if (!empty($data['ai_suggested_menu'])) {
        echo json_encode(['status' => 'success', 'data' => $data['ai_suggested_menu'], 'cached' => true]);
        exit;
    }

    // 2. Chuẩn bị thông tin khách hàng (DNA)
    $dna = [];
    if (!empty($data['doneness'])) $dna[] = "- Độ chín thịt bò: " . $data['doneness'];
    if (!empty($data['flavor_profile'])) $dna[] = "- Khẩu vị: " . $data['flavor_profile'];
    if (!empty($data['fav_ingredients'])) $dna[] = "- Món yêu thích: " . $data['fav_ingredients'];
    if (!empty($data['disliked_ingredients'])) $dna[] = "- Không thích ăn: " . $data['disliked_ingredients'];
    if (!empty($data['allergies'])) $dna[] = "- DỊ ỨNG (QUAN TRỌNG): " . $data['allergies'];
    
    $dna_str = empty($dna) ? "Khách chưa thiết lập DNA Ẩm thực." : implode("\n", $dna);
    $req_str = !empty($data['chef_requirements']) ? $data['chef_requirements'] : "Không có yêu cầu thêm.";

    // 3. Xây dựng Prompt Gửi Cho Gemini
    $prompt = "Bạn là Bếp trưởng điều hành (Executive Chef) tại nhà hàng Nhã (đạt chứng nhận Food Made Good 3 sao quốc tế), một nhà hàng fine-dining cao cấp tại Việt Nam.

Một khách hàng vừa đặt bàn với 'Trải nghiệm thiết kế riêng' (Bespoke Dining). Hãy giúp tôi tạo ra một thực đơn Tasting Menu gồm 5 món (Amuse-Bouche, Khai vị, Món phụ, Món chính, Tráng miệng) bằng tiếng Việt cho khách hàng này.

DỮ LIỆU KHÁCH HÀNG:
Hồ sơ ẩm thực (DNA):
$dna_str

Yêu cầu đặc biệt từ khách:
$req_str

YÊU CẦU CHO THỰC ĐƠN:
1. TUYỆT ĐỐI tuân thủ phần DỊ ỨNG và KHÔNG THÍCH ĂN. Nếu khách dị ứng thành phần nào, tuyệt đối không đưa vào thực đơn.
2. Tận dụng thông tin 'Món yêu thích' và 'Khẩu vị' để sáng tạo. Món chính (Main Course) ưu tiên dùng 'Độ chín thịt bò' nếu có.
3. Vì là nhà hàng fine-dining bền vững, hãy sáng tạo tên món ăn thật kêu, sang trọng, mang âm hưởng Việt Nam kết hợp hiện đại.
4. Mỗi món ăn, hãy viết một dòng mô tả ngắn về thành phần và vì sao món này phù hợp với DNA của khách.
5. Gợi ý 1 loại rượu vang (Wine pairing) ăn kèm.

ĐỊNH DẠNG ĐẦU RA (Trả về định dạng Markdown, không cần chào hỏi, đi thẳng vào menu):
**TÊN THỰC ĐƠN: [Tự nghĩ một cái tên bay bổng]**

**1. Khai vị nhỏ (Amuse-Bouche)**
* [Tên món]
* *Mô tả:* ...

**2. Khai vị (Appetizer)**
...
(Và tiếp tục cho đến món thứ 5)

**Gợi ý Rượu Vang:**
* [Tên rượu] - *Lý do:* ...";

    // 4. Gọi API Gemini (Sử dụng model gemini-flash-latest cho tốc độ nhanh)
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
    
    // Tắt kiểm tra SSL tạm thời (cho môi trường localhost XAMPP)
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        echo json_encode(['status' => 'error', 'message' => 'Lỗi cURL: ' . $error_msg]);
        exit;
    }
    curl_close($ch);

    if ($httpcode !== 200) {
        $err = json_decode($response, true);
        echo json_encode(['status' => 'error', 'message' => 'Lỗi từ Gemini API: ' . ($err['error']['message'] ?? 'Unknown error')]);
        exit;
    }

    $res_data = json_decode($response, true);
    if (!isset($res_data['candidates'][0]['content']['parts'][0]['text'])) {
        echo json_encode(['status' => 'error', 'message' => 'API trả về định dạng không đúng.']);
        exit;
    }

    $generated_menu = $res_data['candidates'][0]['content']['parts'][0]['text'];

    // 5. Lưu vào Database
    $update = $db->prepare("UPDATE service_bookings SET ai_suggested_menu = ? WHERE id = ?");
    $update->execute([$generated_menu, $booking_id]);

    echo json_encode(['status' => 'success', 'data' => $generated_menu]);

} catch (PDOException $e) {
    error_log("DB Error in ajax_ai_chef_assistant: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Lỗi cơ sở dữ liệu.']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}
