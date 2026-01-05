<?php
/**
 * 重新发送注册验证码API（不需要登录）
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
    
    $userModel = new User();
    $result = $userModel->sendEmailVerificationCode($userId, $email, 'email');
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    Logger::error('重新发送验证码错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '发送失败'], JSON_UNESCAPED_UNICODE);
}

