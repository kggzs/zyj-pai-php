<?php
require_once __DIR__ . '/core/autoload.php';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员登录 - <?php echo htmlspecialchars(Helper::getProjectName()); ?></title>
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
    <div class="container">
        <h1>管理员登录</h1>
        
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
    </div>

    <script src="assets/js/admin_login.js"></script>
</body>
</html>

