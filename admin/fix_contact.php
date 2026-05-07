<?php
$file = '../contact.php';
$content = file_get_contents($file);

$old_code = <<<EOD
            // Gửi mail cho admin
            \$mail = new PHPMailer(true);
            try {
                // Cấu hình SMTP từ biến môi trường (.env) – bạn có thể thay thế bằng mail() nếu muốn
                \$mail->isSMTP();
                \$mail->Host       = \$_ENV['MAIL_HOST']       ?? 'smtp.gmail.com';
                \$mail->SMTPAuth   = true;
                \$mail->Username   = \$_ENV['MAIL_USERNAME']   ?? '';
                \$mail->Password   = \$_ENV['MAIL_PASSWORD']   ?? '';
                \$mail->SMTPSecure = \$_ENV['MAIL_ENCRYPTION'] ?? 'tls';
                \$mail->Port       = \$_ENV['MAIL_PORT']       ?? 587;
                \$mail->CharSet    = 'UTF-8';

                \$mail->setFrom(\$email, \$name);
                \$mail->addAddress(\$ft['email'] ?? 'contact@restaurantly.com', \$ft['restaurant_name'] ?? 'Restaurantly');
                \$mail->addReplyTo(\$email, \$name);

                \$mail->isHTML(false);
                \$mail->Subject = "Liên hệ từ website: \$subject";
                \$mail->Body    = "Họ tên: \$name\\nEmail: \$email\\n\\nNội dung:\\n\$message";

                \$mail->send();
                \$messageSent = true;
            } catch (Exception \$e) {
                \$errorMsg = 'Không thể gửi tin nhắn. Vui lòng thử lại sau. Lỗi: ' . \$e->getMessage();
            }
EOD;

$new_code = <<<EOD
            try {
                // Lưu vào CSDL Contacts
                \$stmt = \$db->prepare("INSERT INTO contacts (name, email, subject, message) VALUES (?, ?, ?, ?)");
                \$stmt->execute([\$name, \$email, \$subject, \$message]);
                
                \$messageSent = true;
            } catch (Exception \$e) {
                \$errorMsg = 'Không thể lưu tin nhắn. Lỗi hệ thống: ' . \$e->getMessage();
            }
EOD;

$new_content = str_replace($old_code, $new_code, $content);
file_put_contents($file, $new_content);
echo "Done";
