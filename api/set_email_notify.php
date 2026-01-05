<?php
/**
 * 设置邮箱提醒API
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
    $notifyPhoto = isset($_POST['notify_photo']) ? (int)$_POST['notify_photo'] : 0;
    
    $result = $userModel->setEmailNotify($currentUser['id'], $notifyPhoto);
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    Logger::error('设置邮箱提醒错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '设置失败'], JSON_UNESCAPED_UNICODE);
}

