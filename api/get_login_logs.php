<?php
/**
 * 获取登录日志API
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
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $pageSize = isset($_GET['page_size']) ? (int)$_GET['page_size'] : 20;
    
    $logs = $userModel->getLoginLogs($currentUser['id'], $page, $pageSize);
    
    echo json_encode([
        'success' => true,
        'data' => $logs
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log('获取登录日志错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '获取失败'], JSON_UNESCAPED_UNICODE);
}

