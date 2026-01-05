<?php
/**
 * 获取备份文件列表API
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
    $result = $adminModel->getBackupList();
    echo json_encode(['success' => true, 'data' => $result]);
    
} catch (Exception $e) {
    Logger::error('获取备份列表错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '获取失败']);
}

