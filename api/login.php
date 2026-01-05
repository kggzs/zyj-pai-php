<?php
/**
 * 用户登录API
 */
session_start();
require_once __DIR__ . '/../core/autoload.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // API频率限制检查
    $middleware = new ApiMiddleware();
    $securityCheck = $middleware->checkSecurity('api/login.php', [
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
    
    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => '用户名和密码不能为空']);
        exit;
    }
    
    $userModel = new User();
    $result = $userModel->login($username, $password);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    Logger::error('登录错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '登录失败']);
}
