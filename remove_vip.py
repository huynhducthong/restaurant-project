import re

with open('profile.php', 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Remove VIP Models
content = re.sub(r'require_once __DIR__ \. ''/app/models/UserVip\.php'';\n\ = new UserVip\(\\);\n\n\ = \\[''user_id''\];\n\ = \->getActiveVipStatus\(\\);\n\ = \\[''tab''\] \?\? ''profile'';', '\ = \[''user_id''];\n\ = \[''tab''] ?? ''profile'';', content)

# 2. Remove VipPlan 
content = re.sub(r'// Lấy danh sách VIP plans\nrequire_once __DIR__ \. ''/app/models/VipPlan\.php'';\n\ = new VipPlan\(\\);\n\ = \->getAllPlans\(\);\n', '', content)

# 3. Remove VIP Nâng cấp note
content = re.sub(r'\s*// 6\. Tính năng Nâng cấp VIP được chuyển sang vip_checkout\.php\n', '\n', content)

# 4. Remove VIP CSS badge
content = re.sub(r'\.vip-crown-badge \{[\s\S]*?margin-bottom: 2px; \}\n', '', content)

# 5. Remove HTML badge
content = re.sub(r'\s*<\?php if\(\\): \?>\s*<span class="vip-crown-badge" title="Thành viên VIP">[\s\S]*?</span>\s*<\?php endif; \?>\n', '\n', content)

# 6. Remove Tab link
content = re.sub(r'\s*<a href="\?tab=vip" class="\<\?= \==''vip'' \? ''active'':'''' \?\>">[\s\S]*?</a>\n', '\n', content)
content = re.sub(r'\s*<a href="\?tab=vip" class="\<\?= \==''vip'' \? ''on'':'''' \?\>">[\s\S]*?</a>\n', '\n', content)

# 7. Remove array items
content = content.replace(r"''vip''=>''bi-gem'',", "")
content = content.replace(r"''vip''=>''Đặc quyền Hội viên'',", "")

# 8. Remove the whole VIP Tab section
content = re.sub(r'\s*<!-- ── TAB: THẺ VIP ── -->\s*<\?php elseif\(\==''vip''\): \?>[\s\S]*?<!-- ── TAB: GASTRONOMY PROFILE ── -->', '\n\n        <!-- ── TAB: GASTRONOMY PROFILE ── -->', content)

with open('profile.php', 'w', encoding='utf-8') as f:
    f.write(content)
