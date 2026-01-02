<?php
/**
 * 获取积分商品列表API（管理员）
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
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $pageSize = isset($_GET['page_size']) ? (int)$_GET['page_size'] : 20;
    $status = isset($_GET['status']) ? (int)$_GET['status'] : null;
    
    $shopModel = new Shop();
    $result = $shopModel->getProductList($page, $pageSize, $status);
    
    echo json_encode(['success' => true, 'data' => $result]);
    
} catch (Exception $e) {
    error_log('获取商品列表错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '获取失败']);
}

