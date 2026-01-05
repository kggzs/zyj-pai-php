<?php
/**
 * 兑换积分商品API（用户端）
 */
session_start();
require_once __DIR__ . '/../core/autoload.php';

header('Content-Type: application/json; charset=utf-8');

// 检查登录状态
$userModel = new User();
if (!$userModel->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => '请先登录']);
    exit;
}

try {
    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    
    if ($productId <= 0) {
        echo json_encode(['success' => false, 'message' => '商品ID无效']);
        exit;
    }
    
    $currentUser = $userModel->getCurrentUser();
    $shopModel = new Shop();
    $result = $shopModel->exchangeProduct($currentUser['id'], $productId);
    
    // 清除统计数据缓存
    if ($result['success']) {
        $cache = Cache::getInstance();
        $cache->delete('admin_statistics');
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    Logger::error('兑换商品错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '兑换失败']);
}

