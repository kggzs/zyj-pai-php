<?php
/**
 * 获取系统错误日志API
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
    $lines = isset($_GET['lines']) ? (int)$_GET['lines'] : 100;
    if ($lines < 1 || $lines > 1000) $lines = 100;
    
    $result = $adminModel->getSystemErrorLogs($lines);
    
    echo json_encode(['success' => true, 'data' => $result]);
    
} catch (Exception $e) {
    error_log('获取系统错误日志错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '获取失败']);
}

