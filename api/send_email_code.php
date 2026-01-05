<?php
/**
 * 发送邮箱验证码API
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
    $type = $_POST['type'] ?? 'verify'; // verify: 验证邮箱, reset: 重置密码
    
    if (empty($email)) {
        echo json_encode(['success' => false, 'message' => '请填写邮箱'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $result = $userModel->sendEmailVerificationCode($currentUser['id'], $email, $type);
    
    // 如果发送失败，记录详细错误信息
    if (!$result['success']) {
        Logger::error('发送验证码失败详情：' . json_encode($result, JSON_UNESCAPED_UNICODE));
    }
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    Logger::error('发送验证码异常：' . $e->getMessage());
    Logger::error('堆栈：' . $e->getTraceAsString());
    echo json_encode(['success' => false, 'message' => '发送失败：' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

