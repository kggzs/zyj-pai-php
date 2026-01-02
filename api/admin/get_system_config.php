<?php
/**
 * 获取系统配置API（管理员）
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
    $configs = $adminModel->getSystemConfig();
    echo json_encode(['success' => true, 'data' => $configs]);
} catch (Exception $e) {
    error_log('获取系统配置错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '获取失败']);
}
