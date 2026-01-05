<?php
/**
 * 删除积分商品API（管理员）
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
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => '商品ID无效']);
        exit;
    }
    
    $shopModel = new Shop();
    $result = $shopModel->deleteProduct($id);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    Logger::error('删除商品错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '删除失败']);
}

