<?php
/**
 * 获取上架商品列表API（用户端）
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
    $shopModel = new Shop();
    $products = $shopModel->getAvailableProducts();
    
    // 获取用户积分
    $currentUser = $userModel->getCurrentUser();
    $pointsModel = new Points();
    $userPoints = $pointsModel->getUserPoints($currentUser['id']);
    
    // 计算每个商品的剩余库存和用户是否可兑换
    foreach ($products as &$product) {
        // 剩余库存
        if ($product['total_stock'] !== null) {
            $product['remaining_stock'] = $product['total_stock'] - $product['sold_count'];
        } else {
            $product['remaining_stock'] = null; // 不限
        }
        
        // 用户已兑换次数
        $userExchangeCount = $shopModel->getUserExchangeCount($currentUser['id'], $product['id']);
        $product['user_exchanged_count'] = $userExchangeCount;
        
        // 检查是否可以兑换及原因
        $canExchange = true;
        $exchangeReason = ''; // 不能兑换的原因
        
        // 检查积分
        if ($userPoints < $product['points_price']) {
            $canExchange = false;
            $exchangeReason = '积分不足';
        }
        
        // 检查库存
        if ($product['total_stock'] !== null && $product['remaining_stock'] <= 0) {
            $canExchange = false;
            $exchangeReason = '商品已售罄';
        }
        
        // 检查兑换次数限制
        if ($product['max_per_user'] !== null && $userExchangeCount >= $product['max_per_user']) {
            $canExchange = false;
            if ($product['max_per_user'] == 1) {
                $exchangeReason = '限单次兑换';
            } else {
                $exchangeReason = "已达到兑换上限（最多{$product['max_per_user']}次）";
            }
        }
        
        $product['can_exchange'] = $canExchange;
        $product['exchange_reason'] = $exchangeReason;
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'products' => $products,
            'user_points' => $userPoints
        ]
    ]);
    
} catch (Exception $e) {
    error_log('获取商品列表错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '获取失败']);
}

