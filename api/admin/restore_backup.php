<?php
/**
 * 恢复数据库备份API
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
    $backupFileName = isset($_POST['backup_file']) ? trim($_POST['backup_file']) : '';
    
    if (empty($backupFileName)) {
        echo json_encode(['success' => false, 'message' => '备份文件名不能为空']);
        exit;
    }
    
    // 验证文件名安全性（防止路径遍历攻击）
    if (strpos($backupFileName, '..') !== false || strpos($backupFileName, '/') !== false) {
        echo json_encode(['success' => false, 'message' => '无效的备份文件名']);
        exit;
    }
    
    $result = $adminModel->restoreDatabaseBackup($backupFileName);
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log('恢复备份错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '恢复失败：' . $e->getMessage()]);
}

