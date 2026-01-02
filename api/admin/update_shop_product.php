<?php
/**
 * 更新积分商品API（管理员）
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
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $descriptionType = isset($_POST['description_type']) ? trim($_POST['description_type']) : 'auto';
    $type = isset($_POST['type']) ? trim($_POST['type']) : '';
    $pointsPrice = isset($_POST['points_price']) ? (int)$_POST['points_price'] : 0;
    $value = isset($_POST['value']) && $_POST['value'] !== '' ? (int)$_POST['value'] : null;
    $totalStock = isset($_POST['total_stock']) && $_POST['total_stock'] !== '' ? (int)$_POST['total_stock'] : null;
    $maxPerUser = isset($_POST['max_per_user']) && $_POST['max_per_user'] !== '' ? (int)$_POST['max_per_user'] : null;
    $sortOrder = isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : 0;
    $status = isset($_POST['status']) ? (int)$_POST['status'] : 1;
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => '商品ID无效']);
        exit;
    }
    
    $shopModel = new Shop();
    $result = $shopModel->updateProduct($id, $name, $description, $type, $pointsPrice, $value, $totalStock, $maxPerUser, $sortOrder, $status, $descriptionType);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log('更新商品错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '更新失败']);
}

