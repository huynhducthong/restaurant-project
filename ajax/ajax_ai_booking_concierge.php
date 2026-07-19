<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');



$db = (new Database())->getConnection();
$gemini_api_key = $_ENV['GEMINI_API_KEY'] ?? '';

if (empty($gemini_api_key)) {
    echo json_encode(['status' => 'error', 'message' => 'Tính năng chưa được cấu hình (Thiếu API Key).']);
    exit;
}

$occasion = $_POST['occasion'] ?? 'Không rõ';
$budget = $_POST['budget'] ?? 'Không giới hạn';
$style = $_POST['style'] ?? 'Bất kỳ';
$guests = $_POST['guests'] ?? '2';

try {
    // Lấy thông tin DNA Ẩm thực của khách
    $stmt = $db->prepare("SELECT allergies, flavor_profile, fav_ingredients, disliked_ingredients FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);

    $dna = [];
    if ($u) {
        if (!empty($u['flavor_profile'])) $dna[] = "Khẩu vị: " . $u['flavor_profile'];
        if (!empty($u['fav_ingredients'])) $dna[] = "Món/nguyên liệu yêu thích: " . $u['fav_ingredients'];
        if (!empty($u['disliked_ingredients'])) $dna[] = "Không thích ăn: " . $u['disliked_ingredients'];
        if (!empty($u['allergies'])) $dna[] = "BỊ DỊ ỨNG NGHIÊM TRỌNG VỚI: " . $u['allergies'] . " (TUYỆT ĐỐI KHÔNG GỢI Ý MÓN CÓ THÀNH PHẦN NÀY)";
    }
    
    $dna_str = empty($dna) ? "Khách hàng có khẩu vị chung, dễ ăn, không có dị ứng." : implode("; ", $dna);

    // Giải phóng session lock để người dùng có thể tải trang khác/F5 không bị treo
    session_write_close();

    // Xây dựng Prompt cho Gemini
    $prompt = "Bạn là Bếp trưởng điều hành tại nhà hàng Fine Dining Nhã. Khách hàng đang muốn đặt một bữa tiệc Thiết kế riêng (Bespoke Dining).

Thông tin bữa tiệc:
- Sự kiện: $occasion
- Số lượng khách: $guests người
- Ngân sách: $budget
- Phong cách mong muốn: $style

Hồ sơ Ẩm thực (DNA) của khách:
$dna_str

YÊU CẦU ĐẶC BIỆT:
1. KHÔNG chào hỏi dài dòng, KHÔNG giới thiệu bản thân.
2. Gợi ý NGẮN GỌN một thực đơn 5 món độc bản (Khai vị, Món chính, Món ăn kèm, Đồ uống, Tráng miệng).
3. Mỗi món trình bày trên 1 dòng ngắn gọn. Tên món bắt đầu bằng dấu gạch ngang (-). KHÔNG cách dòng giữa các món ăn.
4. KHÔNG sử dụng Markdown đậm/nghiêng (** hoặc *).
5. Bắt buộc phải tuân thủ nghiêm ngặt dị ứng của khách.
6. Thêm 1 câu chốt ngắn gọn mời khách gửi yêu cầu.
Độ dài tối đa: 80-100 chữ.";

    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-lite-latest:generateContent?key=" . $gemini_api_key;
    
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
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpcode !== 200) {
        $err_msg = 'Lỗi từ hệ thống AI (Gemini).';
        if ($httpcode == 0) {
            $err_msg = 'Hệ thống AI phản hồi quá chậm (Timeout). Vui lòng thử lại sau.';
        } elseif ($httpcode == 503) {
            $err_msg = 'Hệ thống AI đang bị quá tải hoặc bảo trì. Vui lòng thử lại sau ít phút.';
        } elseif ($httpcode == 429) {
            $err_msg = 'Hệ thống AI đã vượt giới hạn truy cập (Rate Limit). Vui lòng thử lại sau.';
        }
        echo json_encode(['status' => 'error', 'message' => $err_msg]);
        exit;
    }

    $res_data = json_decode($response, true);
    if (!isset($res_data['candidates'][0]['content']['parts'][0]['text'])) {
        echo json_encode(['status' => 'error', 'message' => 'Lỗi định dạng AI.']);
        exit;
    }

    $generated_rec = trim($res_data['candidates'][0]['content']['parts'][0]['text']);
    
    // Xóa các ký tự markdown dư thừa nếu AI vô tình sinh ra
    $generated_rec = str_replace(['**', '*'], '', $generated_rec);

    echo json_encode(['status' => 'success', 'data' => $generated_rec]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}

