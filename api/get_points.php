<?php
/**
 * 获取积分信息API
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
    $pageSize = isset($_GET['page_size']) ? (int)$_GET['page_size'] : 20;
    
    $pointsModel = new Points();
    $totalPoints = $pointsModel->getUserPoints($currentUser['id']);
    $pointsLog = $pointsModel->getPointsLog($currentUser['id'], $page, $pageSize);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'total_points' => $totalPoints,
            'points_log' => $pointsLog
        ]
    ]);
    
} catch (Exception $e) {
    Logger::error('获取积分信息错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '获取失败']);
}
