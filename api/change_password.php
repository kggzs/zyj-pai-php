<?php
/**
 * 修改密码API
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
    $oldPassword = $_POST['old_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    
    if (empty($oldPassword) || empty($newPassword)) {
        echo json_encode(['success' => false, 'message' => '请填写完整信息'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $result = $userModel->changePassword($currentUser['id'], $oldPassword, $newPassword);
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    Logger::error('修改密码错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '修改失败'], JSON_UNESCAPED_UNICODE);
}

