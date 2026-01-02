<?php
/**
 * 处理异常行为API
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
    $logId = isset($_POST['log_id']) ? (int)$_POST['log_id'] : 0;
    $note = isset($_POST['note']) ? trim($_POST['note']) : null;
    
    if ($logId <= 0) {
        echo json_encode(['success' => false, 'message' => '无效的记录ID']);
        exit;
    }
    
    $result = $adminModel->handleAbnormalBehavior($logId, $note);
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log('处理异常行为错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '处理失败']);
}

