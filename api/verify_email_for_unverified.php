<?php
/**
 * 验证未验证用户的邮箱API（不需要登录）
 */
session_start();
require_once __DIR__ . '/../core/autoload.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // API频率限制检查
    $middleware = new ApiMiddleware();
    $securityCheck = $middleware->checkSecurity('api/verify_email_for_unverified.php', [
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
    $code = $_POST['code'] ?? '';
    
    if (empty($token)) {
        echo json_encode(['success' => false, 'message' => '缺少验证token'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if (empty($code)) {
        echo json_encode(['success' => false, 'message' => '请输入验证码'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $userModel = new User();
    
    // 验证token（严格验证，包括过期检查）
    $tokenVerify = $userModel->verifyEmailVerifyToken($token);
    if (!$tokenVerify['valid']) {
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
    
    // 检查是否有邮箱
    if (empty($user['email'])) {
        echo json_encode(['success' => false, 'message' => '该账号未绑定邮箱，请先添加邮箱'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 验证邮箱（verifyEmail方法会自动更新email_verified状态）
    $result = $userModel->verifyEmail($user['id'], $user['email'], $code, 'email');
    
    // 验证成功后，清除验证token
    if ($result['success']) {
        $userModel->clearEmailVerifyToken();
    }
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log('验证邮箱错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '验证失败'], JSON_UNESCAPED_UNICODE);
}

