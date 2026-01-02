<?php
/**
 * 获取用户注册码API
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
    $currentUser = $userModel->getCurrentUser();
    
    // 获取或生成注册码
    $registerCode = $userModel->getOrGenerateRegisterCode($currentUser['id']);
    
    // 生成注册链接
    require_once __DIR__ . '/../core/Helper.php';
    $siteUrl = Helper::getSiteUrl();
    $registerUrl = $siteUrl . '/register.php?code=' . $registerCode;
    
    echo json_encode([
        'success' => true,
        'data' => [
            'register_code' => $registerCode,
            'register_url' => $registerUrl
        ]
    ]);
    
} catch (Exception $e) {
    error_log('获取注册码错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '获取失败']);
}

