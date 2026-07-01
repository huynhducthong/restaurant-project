<?php
$content = file_get_contents('profile.php');
// Normalize newlines
$content = str_replace("\r\n", "\n", $content);

$content = preg_replace('/require_once __DIR__ \. \'\/app\/models\/UserVip\.php\';\n\$userVipModel = new UserVip\(\$db\);\n\n\$user_id = \$_SESSION\[\'user_id\'\];\n\$current_vip = \$userVipModel->getActiveVipStatus\(\$user_id\);\n\$tab = \$_GET\[\'tab\'\] \?\? \'profile\';/s', "\$user_id = \$_SESSION['user_id'];\n\$tab = \$_GET['tab'] ?? 'profile';", $content);

$content = preg_replace('/\/\/ Lấy danh sách VIP plans\nrequire_once __DIR__ \. \'\/app\/models\/VipPlan\.php\';\n\$vipPlanModel = new VipPlan\(\$db\);\n\$plans = \$vipPlanModel->getAllPlans\(\);\n/s', "", $content);

$content = preg_replace('/\s*\/\/ 6\. Tính năng Nâng cấp VIP được chuyển sang vip_checkout\.php\n/s', "\n", $content);

$content = preg_replace('/\.vip-crown-badge \{[\s\S]*?margin-bottom: 2px; \}\n/s', "", $content);

$content = preg_replace('/\s*<\?php if\(\$current_vip\): \?>\s*<span class="vip-crown-badge" title="Thành viên VIP">[\s\S]*?<\/span>\s*<\?php endif; \?>\n/s', "\n", $content);

$content = preg_replace('/\s*<a href="\?tab=vip".*?<\/a>\n/s', "\n", $content);

$content = str_replace("'vip'=>'bi-gem',", "", $content);
$content = str_replace("'vip'=>'Đặc quyền Hội viên',", "", $content);

$content = preg_replace('/\s*<!-- ── TAB: THẺ VIP ── -->\s*<\?php elseif\(\$tab==\'vip\'\): \?>[\s\S]*?<!-- ── TAB: GASTRONOMY PROFILE ── -->/s', "\n\n        <!-- ── TAB: GASTRONOMY PROFILE ── -->", $content);

file_put_contents('profile.php', $content);
echo "Done";
?>
