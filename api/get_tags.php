<?php
/**
 * 获取用户标签列表API（支持搜索）
 */
session_start();
require_once __DIR__ . '/../core/autoload.php';

header('Content-Type: application/json; charset=utf-8');

// 检查登录状态
$userModel = new User();
if (!$userModel->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => '请先登录']);
    exit;
}

try {
    $currentUser = $userModel->getCurrentUser();
    $search = isset($_GET['search']) ? trim($_GET['search']) : null;
    
    $photoModel = new Photo();
    $tags = $photoModel->getUserTags($currentUser['id'], $search);
    
    echo json_encode(['success' => true, 'data' => $tags]);
    
} catch (Exception $e) {
    Logger::error('获取标签列表错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '获取失败']);
}

