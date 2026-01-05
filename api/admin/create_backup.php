<?php
/**
 * 创建数据库备份API
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
    $result = $adminModel->createDatabaseBackup();
    echo json_encode($result);
    
} catch (Exception $e) {
    Logger::error('创建备份错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '创建备份失败：' . $e->getMessage()]);
}

