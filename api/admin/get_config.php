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
    // 从数据库读取配置
    $emailConfig = $adminModel->getConfigGroup('email');
    $pointsConfig = $adminModel->getConfigGroup('points');
    $inviteConfig = $adminModel->getConfigGroup('invite');
    
    // 获取系统配置
    $systemConfigs = $adminModel->getSystemConfig();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'email' => $emailConfig,
            'points' => $pointsConfig,
            'invite' => $inviteConfig,
            'system' => $systemConfigs
        ]
    ]);
} catch (Exception $e) {
    error_log('获取配置错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '获取失败']);
}

