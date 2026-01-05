<?php
/**
 * 获取用户的邀请码列表API（用于照片分组）
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
    $photoModel = new Photo();
    $inviteCodes = $photoModel->getUserInviteCodes($currentUser['id']);
    
    echo json_encode(['success' => true, 'data' => $inviteCodes]);
    
} catch (Exception $e) {
    Logger::error('获取邀请码列表错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '获取失败']);
}

