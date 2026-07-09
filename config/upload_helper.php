<?php
// File: config/upload_helper.php

/**
 * Hàm xử lý upload ảnh dùng chung cho toàn bộ hệ thống
 * * @param string $file_input_name Tên của thẻ <input type="file" name="...">
 * @param string $target_dir Thư mục đích để lưu ảnh
 * @param array &$errors Mảng tham chiếu chứa lỗi (nếu có)
 * @param string $old_image Tên ảnh cũ để xóa (nếu đang sửa)
 * @return string Tên file ảnh mới (hoặc ảnh cũ nếu không upload gì)
 */
function process_image_upload($file_input_name, $target_dir, &$errors, $old_image = '')
{
    // 1. Không có file mới tải lên -> Giữ nguyên ảnh cũ
    if (empty($_FILES[$file_input_name]['name'])) {
        return $old_image;
    }

    $allowed_ext = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'mp4', 'webm'];
    $allowed_mime = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'video/mp4', 'video/webm'];

    $file_name = $_FILES[$file_input_name]['name'];
    $tmp_path = $_FILES[$file_input_name]['tmp_name'];
    $file_size = $_FILES[$file_input_name]['size'];
    $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    // 2. Kiểm tra phần mở rộng (đuôi file)
    if (!in_array($ext, $allowed_ext)) {
        $errors[] = "Chỉ chấp nhận file ảnh (JPG, PNG, WEBP, GIF) hoặc video (MP4, WEBM).";
        return $old_image;
    }

    // 3. Kiểm tra MIME type thực sự của file (Chống giả mạo đuôi)
    $mime = mime_content_type($tmp_path);
    if (!in_array($mime, $allowed_mime)) {
        $errors[] = "Định dạng file không hợp lệ hoặc file bị giả mạo.";
        return $old_image;
    }

    // 4. Kiểm tra dung lượng (Tối đa 50MB)
    if ($file_size > 50 * 1024 * 1024) {
        $errors[] = "Dung lượng file không được vượt quá 50MB.";
        return $old_image;
    }

    // 5. Tạo thư mục nếu chưa tồn tại
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // 6. Đổi tên file để tránh trùng lặp
    $new_file_name = time() . '_' . uniqid() . '.' . $ext;
    $dest_path = rtrim($target_dir, '/') . '/' . $new_file_name;

    // 7. Thực hiện di chuyển file
    if (move_uploaded_file($tmp_path, $dest_path)) {
        // Xóa ảnh cũ đi cho nhẹ server
        if (!empty($old_image) && file_exists(rtrim($target_dir, '/') . '/' . $old_image)) {
            unlink(rtrim($target_dir, '/') . '/' . $old_image);
        }
        return $new_file_name;
    } else {
        $errors[] = "Lỗi hệ thống khi lưu file ảnh lên máy chủ.";
        return $old_image;
    }
}
?>