<?php
$file = 'admin/controllers/manage_banners.php';
$content = file_get_contents($file);

// 1. Date Validation
$dateSearch = <<<EOF
        'start_date'      => !empty(\$_POST['start_date']) ? \$_POST['start_date'] : null,
        'end_date'        => !empty(\$_POST['end_date']) ? \$_POST['end_date'] : null,
    ];
EOF;
$dateReplace = <<<EOF
        'start_date'      => !empty(\$_POST['start_date']) ? \$_POST['start_date'] : null,
        'end_date'        => !empty(\$_POST['end_date']) ? \$_POST['end_date'] : null,
    ];

    if (\$data['start_date'] && \$data['end_date']) {
        if (strtotime(\$data['end_date']) <= strtotime(\$data['start_date'])) {
            \$_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Lỗi: Thời gian kết thúc phải diễn ra SAU thời gian bắt đầu!'];
            header('Location: ' . \$_SERVER['PHP_SELF'] . (\$id ? "?edit=\$id" : ''));
            exit;
        }
    }
EOF;
$content = str_replace($dateSearch, $dateReplace, $content);

// 2. Upload Validation
$uploadSearch = <<<EOF
    // ? Validate upload ?nh
    if (!empty(\$_FILES['banner_image']['name'])) {
        \$allowed_ext  = ['jpg', 'jpeg', 'png', 'webp'];
EOF;
$uploadReplace = <<<EOF
    // ? Validate upload ?nh
    if (!empty(\$_FILES['banner_image']['name'])) {
        if (\$_FILES['banner_image']['error'] !== UPLOAD_ERR_OK) {
            \$err_code = \$_FILES['banner_image']['error'];
            \$msg = 'Lỗi tải ảnh không xác định.';
            if (\$err_code === UPLOAD_ERR_INI_SIZE || \$err_code === UPLOAD_ERR_FORM_SIZE) {
                \$msg = 'Kích thước ảnh quá lớn, vượt quá giới hạn của máy chủ!';
            }
            \$_SESSION['flash'] = ['type' => 'danger', 'msg' => \$msg];
            header('Location: ' . \$_SERVER['PHP_SELF'] . (\$id ? "?edit=\$id" : ''));
            exit;
        }

        \$allowed_ext  = ['jpg', 'jpeg', 'png', 'webp'];
EOF;
$content = str_replace($uploadSearch, $uploadReplace, $content);

file_put_contents($file, $content);
echo "Patched successfully.";
