<?php
/**
 * 验证邮箱API
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../core/autoload.php';

error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');

// 检查登录状态
$userModel = new User();
if (!$userModel->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => '请先登录'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $currentUser = $userModel->getCurrentUser();
    $email = $_POST['email'] ?? '';
    $code = $_POST['code'] ?? '';
    
    if (empty($email) || empty($code)) {
        echo json_encode(['success' => false, 'message' => '请填写完整信息'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $result = $userModel->verifyEmail($currentUser['id'], $email, $code);
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    Logger::error('验证邮箱错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '验证失败'], JSON_UNESCAPED_UNICODE);
}

