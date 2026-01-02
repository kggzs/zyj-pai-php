<?php
/**
 * 设置系统配置API（管理员）
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
    $key = $_POST['key'] ?? '';
    $value = $_POST['value'] ?? '';
    $description = $_POST['description'] ?? null;
    
    if (empty($key)) {
        echo json_encode(['success' => false, 'message' => '配置键不能为空']);
        exit;
    }
    
    $result = $adminModel->setSystemConfig($key, $value, $description);
    
    // 记录操作日志
    $adminModel->logOperation(
        'config_update',
        'config',
        null,
        "更新系统配置：{$key} = {$value}"
    );
    
    echo json_encode($result);
} catch (Exception $e) {
    error_log('设置系统配置错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '设置失败']);
}
