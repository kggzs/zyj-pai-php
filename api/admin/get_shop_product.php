<?php
/**
 * 获取单个积分商品详情API（管理员）
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
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => '商品ID无效']);
        exit;
    }
    
    $shopModel = new Shop();
    $product = $shopModel->getProduct($id);
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => '商品不存在']);
        exit;
    }
    
    echo json_encode(['success' => true, 'data' => $product]);
    
} catch (Exception $e) {
    error_log('获取商品详情错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '获取失败']);
}

