<?php
/**
 * 注册时邮箱验证API（不需要登录）
 */
session_start();
require_once __DIR__ . '/../core/autoload.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // 检查是否有待验证的用户
    if (empty($_SESSION['pending_verification_user_id']) || empty($_SESSION['pending_verification_email'])) {
        echo json_encode(['success' => false, 'message' => '没有待验证的邮箱，请重新注册'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $userId = $_SESSION['pending_verification_user_id'];
    $email = $_SESSION['pending_verification_email'];
    $code = $_POST['code'] ?? '';
    
    if (empty($code)) {
        echo json_encode(['success' => false, 'message' => '请输入验证码'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $userModel = new User();
    $result = $userModel->verifyEmail($userId, $email, $code, 'email');
    
    // 验证成功后，清除session中的待验证信息
    if ($result['success']) {
        unset($_SESSION['pending_verification_user_id']);
        unset($_SESSION['pending_verification_email']);
    }
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log('验证邮箱错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '验证失败'], JSON_UNESCAPED_UNICODE);
}

