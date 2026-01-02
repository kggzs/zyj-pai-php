<?php
/**
 * 获取用户详情API（管理员）
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
    $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    
    if ($userId <= 0) {
        echo json_encode(['success' => false, 'message' => '用户ID无效']);
        exit;
    }
    
    $result = $adminModel->getUserDetail($userId);
    
    if ($result === null) {
        echo json_encode(['success' => false, 'message' => '用户不存在']);
        exit;
    }
    
    echo json_encode(['success' => true, 'data' => $result]);
    
} catch (Exception $e) {
    error_log('获取用户详情错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '获取失败']);
}

