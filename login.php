<?php
require_once __DIR__ . '/core/autoload.php';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户登录 - <?php echo htmlspecialchars(Helper::getProjectName()); ?></title>
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
    <div class="container">
        <h1>用户登录</h1>
        
        <div id="message"></div>
        
        <form id="loginForm">
            <div class="form-group">
                <label>用户名</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>密码</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn">登录</button>
        </form>
        
        <div class="forgot-password-link" style="text-align: center; margin-top: 15px;">
            <a href="reset_password.php" style="color: #5B9BD5; text-decoration: none; font-size: 14px;">忘记密码？</a>
        </div>
        
        <div class="register-link">
            <a href="register.php">还没有账号？立即注册</a>
        </div>
    </div>

    <script src="assets/js/login.js"></script>
</body>
</html>
