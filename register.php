<?php
require_once __DIR__ . '/core/autoload.php';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📷</text></svg>">
    <title>用户注册 - <?php echo htmlspecialchars(Helper::getProjectName()); ?></title>
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
    <div class="container">
        <h1>用户注册</h1>
        
        <div id="message"></div>
        
        <form id="registerForm">
            <div class="form-group">
                <label>用户名</label>
                <input type="text" name="username" required maxlength="20" placeholder="3-20个字符，只能包含字母、数字和下划线">
                <p style="font-size: 12px; color: #999; margin-top: 5px;">用户名长度：3-20个字符，只能包含字母、数字和下划线</p>
            </div>
            <div class="form-group">
                <label id="emailLabel">邮箱</label>
                <input type="email" name="email" id="emailInput" placeholder="请输入邮箱地址">
            </div>
            <div class="form-group">
                <label>密码</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn">注册</button>
        </form>
        
        <div class="login-link">
            <a href="login.php">已有账号？立即登录</a>
        </div>
    </div>

    <script src="assets/js/register.js"></script>
</body>
</html>
