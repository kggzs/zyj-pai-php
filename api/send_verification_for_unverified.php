<?php
/**
 * 为未验证用户发送邮箱验证码API（不需要登录）
 */
session_start();
require_once __DIR__ . '/../core/autoload.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // API频率限制检查
    $middleware = new ApiMiddleware();
    $securityCheck = $middleware->checkSecurity('api/send_verification_for_unverified.php', [
        'rate_limit' => true,
        'api_key' => false,
        'signature' => false,
        'csrf' => false
    ]);
    
    if ($securityCheck) {
        echo json_encode($securityCheck, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $token = $_POST['token'] ?? '';
    
    if (empty($token)) {
        echo json_encode(['success' => false, 'message' => '缺少验证token'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $userModel = new User();
    
    // 验证token（严格验证，包括过期检查）
    $tokenVerify = $userModel->verifyEmailVerifyToken($token);
    if (!$tokenVerify['valid']) {
        // token无效或过期，直接返回错误，不发送邮件
        echo json_encode(['success' => false, 'message' => $tokenVerify['message']], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 再次确认token在session中存在且匹配（双重验证）
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (empty($_SESSION['email_verify_token']) || 
        $_SESSION['email_verify_token'] !== $token ||
        time() > ($_SESSION['email_verify_expire'] ?? 0)) {
        echo json_encode(['success' => false, 'message' => '验证token无效或已过期，请重新登录'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 从token中获取用户信息
    $userId = $tokenVerify['user_id'];
    $username = $tokenVerify['username'];
    
    // 根据用户ID查找用户
    $db = Database::getInstance();
    $user = $db->fetchOne(
        "SELECT * FROM users WHERE id = ? AND status = 1",
        [$userId]
    );
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => '用户不存在'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 检查是否开启了强制邮箱验证
    require_once __DIR__ . '/../core/Helper.php';
    $requireVerification = Helper::getSystemConfig('register_require_email_verification') == '1';
    if (!$requireVerification) {
        echo json_encode(['success' => false, 'message' => '系统未开启强制邮箱验证'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 检查是否已经验证过
    if (!empty($user['email']) && $user['email_verified'] == 1) {
        echo json_encode(['success' => false, 'message' => '该邮箱已经验证过了'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 如果用户没有邮箱，需要先提供邮箱
    $email = $_POST['email'] ?? '';
    if (empty($user['email'])) {
        if (empty($email)) {
            echo json_encode(['success' => false, 'message' => '该账号未绑定邮箱，请先输入邮箱地址', 'need_email' => true], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // 验证邮箱格式
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => '邮箱格式不正确'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // 检查邮箱是否已被其他用户使用
        $emailExists = $db->fetchOne(
            "SELECT id FROM users WHERE email = ? AND id != ? AND email != ''",
            [$email, $user['id']]
        );
        if ($emailExists) {
            echo json_encode(['success' => false, 'message' => '该邮箱已被其他用户使用'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // 先更新用户的邮箱（但未验证）
        $db->execute(
            "UPDATE users SET email = ? WHERE id = ?",
            [$email, $user['id']]
        );
        
        // 更新用户对象中的邮箱
        $user['email'] = $email;
    } else {
        // 如果用户已有邮箱，使用用户的邮箱
        $email = $user['email'];
    }
    
    // 发送验证码
    $result = $userModel->sendEmailVerificationCode($user['id'], $email, 'email');
    
    if ($result['success']) {
        // 返回邮箱（部分隐藏，与前端保持一致）
        $email = $user['email'];
        $emailMask = $email;
        if (strlen($email) > 4 && strpos($email, '@') !== false) {
            // 格式：前2位 + **** + @后面的部分
            $atPos = strpos($email, '@');
            $emailMask = substr($email, 0, 2) . '****' . substr($email, $atPos);
        }
        $result['email'] = $emailMask;
    }
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log('发送验证码错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '发送失败'], JSON_UNESCAPED_UNICODE);
}

