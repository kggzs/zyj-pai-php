<?php
/**
 * 创建管理员账号页面
 * 
 * 功能：
 * 1. 先输入脚本专属密码验证
 * 2. 然后输入管理员用户名、密码、邮箱（均为必填）
 * 3. 默认创建为永久VIP会员
 */

// 脚本专属密码（请修改为安全的密码）
define('SCRIPT_PASSWORD', 'Admin2024Secure!@#'); // 请修改此密码

// 引入必要的文件
require_once __DIR__ . '/core/autoload.php';

// 处理POST请求
$error = '';
$success = '';
$step = isset($_POST['step']) ? $_POST['step'] : 'password'; // password 或 create

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 'password') {
        // 第一步：验证脚本专属密码
        $scriptPassword = isset($_POST['script_password']) ? trim($_POST['script_password']) : '';
        if ($scriptPassword !== SCRIPT_PASSWORD) {
            $error = '脚本专属密码不正确！';
            $step = 'password';
        } else {
            // 密码验证成功，进入创建账号步骤
            $step = 'create';
            // 使用Session保存验证状态（简单的安全措施）
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['admin_create_verified'] = true;
            $_SESSION['admin_create_verified_time'] = time();
        }
    } elseif ($step === 'create') {
        // 检查验证状态
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_create_verified']) || !$_SESSION['admin_create_verified']) {
            $error = '请先验证脚本专属密码！';
            $step = 'password';
        } elseif (time() - $_SESSION['admin_create_verified_time'] > 600) { // 10分钟超时
            unset($_SESSION['admin_create_verified']);
            unset($_SESSION['admin_create_verified_time']);
            $error = '验证已过期，请重新验证脚本专属密码！';
            $step = 'password';
        } else {
            // 第二步：创建管理员账号
            $username = isset($_POST['username']) ? trim($_POST['username']) : '';
            $password = isset($_POST['password']) ? trim($_POST['password']) : '';
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            
            // 验证必填项
            if (empty($username)) {
                $error = '用户名不能为空！';
            } elseif (empty($password)) {
                $error = '密码不能为空！';
            } elseif (empty($email)) {
                $error = '邮箱不能为空！';
            } else {
                // 验证用户名格式
                if (mb_strlen($username) < 3 || mb_strlen($username) > 20) {
                    $error = '用户名长度必须在3-20个字符之间！';
                } elseif (!preg_match('/^[a-zA-Z0-9_]+$/u', $username)) {
                    $error = '用户名只能包含字母、数字和下划线！';
                } else {
                    try {
                        $db = Database::getInstance();
                        
                        // 检查用户名是否已存在
                        $existing = $db->fetchOne("SELECT id FROM users WHERE username = ?", [$username]);
                        if ($existing) {
                            $error = '用户名已存在！';
                        } else {
                            // 验证邮箱格式
                            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                $error = '邮箱格式不正确！';
                            } else {
                                // 检查邮箱是否已存在
                                $emailExists = $db->fetchOne("SELECT id FROM users WHERE email = ? AND email != ''", [$email]);
                                if ($emailExists) {
                                    $error = '该邮箱已被使用！';
                                } else {
                                    // 验证密码强度
                                    try {
                                        $passwordValidation = Security::validatePasswordStrength($password);
                                        if (!$passwordValidation['valid']) {
                                            $error = $passwordValidation['message'];
                                        } else {
                                            // 所有验证通过，创建管理员账号
                                            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                                            $registerIp = Security::getClientIp();
                                            $registerUa = $_SERVER['HTTP_USER_AGENT'] ?? 'Admin Setup Page';
                                            $registerTime = date('Y-m-d H:i:s');
                                            
                                            // 插入管理员账号
                                            // is_admin = 1, is_vip = 1, vip_expire_time = NULL (永久VIP), status = 1, email_verified = 1
                                            $sql = "INSERT INTO users (
                                                        username, 
                                                        password, 
                                                        email, 
                                                        email_verified, 
                                                        register_ip, 
                                                        register_ua, 
                                                        register_time, 
                                                        last_login_time, 
                                                        status, 
                                                        is_admin, 
                                                        points, 
                                                        is_vip, 
                                                        vip_expire_time
                                                    ) VALUES (?, ?, ?, 1, ?, ?, ?, ?, 1, 1, 0, 1, NULL)";
                                            
                                            $params = [
                                                $username,
                                                $hashedPassword,
                                                $email,
                                                $registerIp,
                                                $registerUa,
                                                $registerTime,
                                                $registerTime
                                            ];
                                            
                                            $db->execute($sql, $params);
                                            $adminId = $db->lastInsertId();
                                            
                                            // 清除验证状态
                                            unset($_SESSION['admin_create_verified']);
                                            unset($_SESSION['admin_create_verified_time']);
                                            
                                            $success = "管理员账号创建成功！用户ID: {$adminId}，用户名: {$username}，邮箱: {$email}，VIP状态: 永久VIP";
                                            $step = 'password'; // 重置为第一步
                                        }
                                    } catch (Exception $e) {
                                        // 如果Security类不存在，使用简单验证
                                        if (mb_strlen($password) < 6) {
                                            $error = '密码长度至少6个字符！';
                                        } else {
                                            // 密码验证通过
                                            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                                            $registerIp = Security::getClientIp();
                                            $registerUa = $_SERVER['HTTP_USER_AGENT'] ?? 'Admin Setup Page';
                                            $registerTime = date('Y-m-d H:i:s');
                                            
                                            $sql = "INSERT INTO users (
                                                        username, 
                                                        password, 
                                                        email, 
                                                        email_verified, 
                                                        register_ip, 
                                                        register_ua, 
                                                        register_time, 
                                                        last_login_time, 
                                                        status, 
                                                        is_admin, 
                                                        points, 
                                                        is_vip, 
                                                        vip_expire_time
                                                    ) VALUES (?, ?, ?, 1, ?, ?, ?, ?, 1, 1, 0, 1, NULL)";
                                            
                                            $params = [
                                                $username,
                                                $hashedPassword,
                                                $email,
                                                $registerIp,
                                                $registerUa,
                                                $registerTime,
                                                $registerTime
                                            ];
                                            
                                            $db->execute($sql, $params);
                                            $adminId = $db->lastInsertId();
                                            
                                            unset($_SESSION['admin_create_verified']);
                                            unset($_SESSION['admin_create_verified_time']);
                                            
                                            $success = "管理员账号创建成功！用户ID: {$adminId}，用户名: {$username}，邮箱: {$email}，VIP状态: 永久VIP";
                                            $step = 'password';
                                        }
                                    }
                                }
                            }
                        }
                    } catch (Exception $e) {
                        $error = '创建管理员账号失败：' . htmlspecialchars($e->getMessage());
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📷</text></svg>">
    <title>创建管理员账号 - <?php echo htmlspecialchars(Helper::getProjectName()); ?></title>
    <link rel="stylesheet" href="assets/css/auth.css">
    <style>
        .info-box {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            color: #004085;
            font-size: 14px;
            line-height: 1.6;
        }
        .info-box strong {
            color: #0056b3;
        }
        .step-indicator {
            text-align: center;
            margin-bottom: 20px;
            font-size: 14px;
            color: #666;
        }
        .step-indicator span {
            display: inline-block;
            padding: 5px 15px;
            margin: 0 5px;
            border-radius: 20px;
            background: #f0f0f0;
            color: #999;
        }
        .step-indicator span.active {
            background: #5B9BD5;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container" style="max-width: 500px;">
        <h1>创建管理员账号</h1>
        
        <div class="step-indicator">
            <span class="<?php echo $step === 'password' ? 'active' : ''; ?>">1. 验证密码</span>
            <span class="<?php echo $step === 'create' ? 'active' : ''; ?>">2. 创建账号</span>
        </div>
        
        <?php if ($error): ?>
            <div class="message message-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="message message-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($step === 'password'): ?>
            <!-- 第一步：验证脚本专属密码 -->
            <div class="info-box">
                <strong>提示：</strong>请先输入脚本专属密码进行验证。验证成功后进入下一步创建管理员账号。
            </div>
            
            <form method="POST">
                <input type="hidden" name="step" value="password">
                <div class="form-group">
                    <label>脚本专属密码</label>
                    <input type="password" name="script_password" required autofocus placeholder="请输入脚本专属密码">
                </div>
                <button type="submit" class="btn">下一步</button>
            </form>
        <?php else: ?>
            <!-- 第二步：创建管理员账号 -->
            <div class="info-box">
                <strong>说明：</strong>
                <ul style="margin: 10px 0 0 20px; padding: 0;">
                    <li>用户名、密码、邮箱均为必填项</li>
                    <li>用户名：3-20个字符，只能包含字母、数字和下划线</li>
                    <li>密码需符合密码强度要求</li>
                    <li>创建的管理员账号将自动设置为永久VIP会员</li>
                </ul>
            </div>
            
            <form method="POST" id="createForm">
                <input type="hidden" name="step" value="create">
                <div class="form-group">
                    <label>用户名 <span style="color: red;">*</span></label>
                    <input type="text" name="username" required maxlength="20" placeholder="3-20个字符，只能包含字母、数字和下划线" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    <p style="font-size: 12px; color: #999; margin-top: 5px;">用户名长度：3-20个字符，只能包含字母、数字和下划线</p>
                </div>
                <div class="form-group">
                    <label>密码 <span style="color: red;">*</span></label>
                    <input type="password" name="password" id="passwordInput" required placeholder="请输入密码" oninput="checkPasswordStrength(this.value)">
                    <div id="passwordStrength" style="margin-top: 8px; display: none;">
                        <div style="display: flex; align-items: center; margin-bottom: 5px;">
                            <span style="font-size: 12px; color: #666; margin-right: 10px;">密码强度：</span>
                            <div id="strengthBar" style="flex: 1; height: 6px; background: #eee; border-radius: 3px; overflow: hidden;">
                                <div id="strengthBarFill" style="height: 100%; width: 0%; transition: all 0.3s; border-radius: 3px;"></div>
                            </div>
                            <span id="strengthText" style="font-size: 12px; margin-left: 10px; font-weight: bold;"></span>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>邮箱 <span style="color: red;">*</span></label>
                    <input type="email" name="email" required placeholder="请输入邮箱地址" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                <button type="submit" class="btn">创建管理员账号</button>
            </form>
            
            <div style="text-align: center; margin-top: 15px;">
                <a href="create_admin.php" style="color: #5B9BD5; text-decoration: none; font-size: 14px;">返回重新验证</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // 密码强度检测（如果API可用）
        function checkPasswordStrength(password) {
            if (!password) {
                document.getElementById('passwordStrength').style.display = 'none';
                return;
            }
            
            document.getElementById('passwordStrength').style.display = 'block';
            
            // 简单的密码强度检测
            let strength = 0;
            let strengthText = '';
            let strengthColor = '';
            
            if (password.length >= 6) strength++;
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            
            if (strength <= 1) {
                strengthText = '弱';
                strengthColor = '#dc3545';
            } else if (strength <= 3) {
                strengthText = '中';
                strengthColor = '#ffc107';
            } else {
                strengthText = '强';
                strengthColor = '#28a745';
            }
            
            const percentage = (strength / 5) * 100;
            document.getElementById('strengthBarFill').style.width = percentage + '%';
            document.getElementById('strengthBarFill').style.background = strengthColor;
            document.getElementById('strengthText').textContent = strengthText;
            document.getElementById('strengthText').style.color = strengthColor;
        }
    </script>
</body>
</html>
