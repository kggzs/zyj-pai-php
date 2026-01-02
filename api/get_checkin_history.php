<?php
/**
 * 获取签到记录API
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
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $pageSize = isset($_GET['page_size']) ? (int)$_GET['page_size'] : 30;
    
    $pointsModel = new Points();
    $history = $pointsModel->getCheckinHistory($currentUser['id'], $page, $pageSize);
    
    echo json_encode([
        'success' => true,
        'data' => $history
    ]);
    
} catch (Exception $e) {
    error_log('获取签到记录错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '获取失败']);
}

