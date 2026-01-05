<?php
/**
 * 获取用户照片上传日志API（管理员）
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
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $pageSize = isset($_GET['page_size']) ? (int)$_GET['page_size'] : 50;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    
    if ($page < 1) $page = 1;
    if ($pageSize < 1 || $pageSize > 200) $pageSize = 50;
    
    $result = $adminModel->getUserPhotoLogs($page, $pageSize, $search);
    
    echo json_encode(['success' => true, 'data' => $result]);
    
} catch (Exception $e) {
    Logger::error('获取照片上传日志错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '获取失败']);
}

