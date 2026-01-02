<?php
require_once __DIR__ . '/core/autoload.php';

// 检查是否有有效的验证token
$token = $_GET['token'] ?? '';
$userModel = new User();

if (empty($token)) {
    // 没有token，跳转到登录页面
    header('Location: login.php');
    exit;
}

// 验证token
$tokenVerify = $userModel->verifyEmailVerifyToken($token);
if (!$tokenVerify['valid']) {
    // token无效，跳转到登录页面并显示错误信息
    header('Location: login.php?error=' . urlencode($tokenVerify['message']));
    exit;
}

$verifyUserId = $tokenVerify['user_id'];
$verifyUsername = $tokenVerify['username'];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📷</text></svg>">
    <title>验证邮箱 - <?php echo htmlspecialchars(Helper::getProjectName()); ?></title>
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
    <div class="container">
        <h1>验证邮箱</h1>
        
        <div id="message"></div>
        
        <div id="usernameFormContainer">
            <p style="color: #666; margin-bottom: 20px;">点击下方按钮发送验证码到您的邮箱</p>
            <form id="usernameForm">
                <input type="hidden" id="verifyToken" value="<?php echo htmlspecialchars($token); ?>">
                <input type="hidden" id="verifyUserId" value="<?php echo htmlspecialchars($verifyUserId); ?>">
                <input type="hidden" id="verifyUsername" value="<?php echo htmlspecialchars($verifyUsername); ?>">
                <div class="form-group" id="emailInputGroup" style="display: none;">
                    <label>邮箱地址 <span style="color: red;">*</span></label>
                    <input type="email" name="email" id="emailInput" placeholder="请输入邮箱地址">
                    <p style="color: #666; font-size: 12px; margin-top: 5px;">您的账号未绑定邮箱，请先输入邮箱地址</p>
                </div>
                <button type="submit" class="btn" id="submitBtn">发送验证码</button>
            </form>
            <div class="login-link" style="margin-top: 20px;">
                <a href="login.php">返回登录</a>
            </div>
        </div>
        
        <div id="verificationFormContainer" style="display: none;">
            <div class="verification-info">
                <p>我们已向您的邮箱 <strong id="emailDisplay"></strong> 发送了验证码</p>
                <p style="color: #666; font-size: 14px; margin-top: 10px;">请查收邮件并输入验证码完成验证</p>
            </div>
            <form id="verificationForm">
                <div class="form-group">
                    <label>验证码</label>
                    <input type="text" name="code" id="verificationCode" placeholder="请输入6位验证码" maxlength="6" required>
                </div>
                <button type="submit" class="btn">验证邮箱</button>
                <button type="button" class="btn btn-secondary" id="resendBtn" style="margin-top: 10px; background: #6c757d;">重新发送验证码</button>
            </form>
            <div class="login-link" style="margin-top: 20px;">
                <a href="verify_email_page.php">返回</a>
            </div>
        </div>
    </div>

    <script src="assets/js/verify_email_page.js"></script>
</body>
</html>

