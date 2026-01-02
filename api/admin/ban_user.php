<?php
/**
 * 封禁/解封用户API（管理员）
 */
session_start();
require_once __DIR__ . '/../../core/autoload.php';

header('Content-Type: application/json; charset=utf-8');

// 检查管理员登录状态
$adminModel = new Admin();
if (!$adminModel->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => '请先登录']);
    exit;
}

try {
    $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $status = isset($_POST['status']) ? (int)$_POST['status'] : 0;
    
    if ($userId <= 0) {
        echo json_encode(['success' => false, 'message' => '用户ID无效']);
        exit;
    }
    
    $result = $adminModel->banUser($userId, $status);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log('封禁用户错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '操作失败']);
}

