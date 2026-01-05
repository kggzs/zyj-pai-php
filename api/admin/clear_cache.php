<?php
/**
 * 清除缓存API（管理员）
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
    $cache = Cache::getInstance();
    $type = isset($_GET['type']) ? $_GET['type'] : 'all';
    
    switch ($type) {
        case 'statistics':
            // 只清除统计数据缓存
            $cache->delete('admin_statistics');
            $message = '统计数据缓存已清除';
            break;
            
        case 'config':
            // 只清除配置缓存
            $cache->clear('system_config');
            $message = '配置缓存已清除';
            break;
            
        case 'all':
        default:
            // 清除所有缓存
            $cache->clear();
            $message = '所有缓存已清除';
            break;
    }
    
    // 记录操作日志
    $adminModel->logOperation(
        'clear_cache',
        'system',
        null,
        "清除缓存：{$type}"
    );
    
    echo json_encode(['success' => true, 'message' => $message]);
    
} catch (Exception $e) {
    Logger::error('清除缓存错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '清除失败']);
}

