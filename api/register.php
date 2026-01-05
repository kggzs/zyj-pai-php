<?php
/**
 * 用户注册API
 */
session_start();
require_once __DIR__ . '/../core/autoload.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // API频率限制检查（注册接口需要更严格的限制）
    $middleware = new ApiMiddleware();
    $securityCheck = $middleware->checkSecurity('api/register.php', [
        'rate_limit' => true,
        'api_key' => false,
        'signature' => false,
        'csrf' => false
    ]);
    
    if ($securityCheck) {
        echo json_encode($securityCheck);
        exit;
    }
    
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $email = $_POST['email'] ?? '';
    $inviteCode = $_POST['invite_code'] ?? null;
    $agreeTerms = isset($_POST['agree_terms']) ? $_POST['agree_terms'] : '';
    
    // 检查是否同意用户协议
    if (empty($agreeTerms) || $agreeTerms !== '1') {
        echo json_encode(['success' => false, 'message' => '请先阅读并同意《用户服务协议》']);
        exit;
    }
    
    if (empty($password)) {
        echo json_encode(['success' => false, 'message' => '密码不能为空']);
        exit;
    }
    
    $userModel = new User();
    $result = $userModel->register($username, $password, $inviteCode, $email);
    
    // 如果开启了强制邮箱验证，将用户ID保存到session，以便后续验证
    if ($result['success'] && !empty($result['require_verification'])) {
        $_SESSION['pending_verification_user_id'] = $result['user_id'];
        $_SESSION['pending_verification_email'] = $email;
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log('注册错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '注册失败']);
}
