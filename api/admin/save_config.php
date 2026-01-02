<?php
/**
 * 保存系统配置API（管理员）
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
    $configType = $_POST['type'] ?? '';
    $configDataJson = $_POST['data'] ?? '';
    
    if (empty($configType) || empty($configDataJson)) {
        echo json_encode(['success' => false, 'message' => '参数不完整']);
        exit;
    }
    
    // 解析JSON数据
    $configData = json_decode($configDataJson, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'message' => '配置数据格式错误']);
        exit;
    }
    
    // 验证配置类型
    $allowedTypes = ['email', 'points', 'invite'];
    if (!in_array($configType, $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => '无效的配置类型']);
        exit;
    }
    
    // 保存到数据库
    $result = $adminModel->setConfigGroup($configType, $configData);
    
    // 记录操作日志
    $adminModel->logOperation(
        'config_update',
        'config',
        null,
        "更新配置组：{$configType}"
    );
    
    echo json_encode($result);
} catch (Exception $e) {
    error_log('保存配置错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '保存失败：' . $e->getMessage()]);
}

