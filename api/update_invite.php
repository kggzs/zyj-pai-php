<?php
/**
 * 更新邀请码标签和状态API（VIP用户功能）
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
    $inviteId = isset($_POST['invite_id']) ? (int)$_POST['invite_id'] : 0;
    $label = isset($_POST['label']) ? trim($_POST['label']) : null;
    $status = isset($_POST['status']) ? (int)$_POST['status'] : null;
    
    if ($inviteId <= 0) {
        echo json_encode(['success' => false, 'message' => '邀请码ID无效']);
        exit;
    }
    
    // 如果status不为null，验证是否为有效值
    if ($status !== null && $status !== 0 && $status !== 1) {
        echo json_encode(['success' => false, 'message' => '状态值无效']);
        exit;
    }
    
    // 验证标签长度（如果提供）
    if ($label !== null && $label !== '') {
        if (mb_strlen($label) > 10) {
            echo json_encode(['success' => false, 'message' => '标签长度不能超过10个字符']);
            exit;
        }
        // 验证标签格式（只允许中文、英文、数字、下划线、连字符）
        if (!preg_match('/^[\x{4e00}-\x{9fa5}a-zA-Z0-9_-]+$/u', $label)) {
            echo json_encode(['success' => false, 'message' => '标签只能包含中文、英文、数字、下划线和连字符']);
            exit;
        }
    }
    
    // 如果label为空字符串，转换为null（清空标签）
    if ($label !== null && $label === '') {
        $label = null;
    }
    
    $inviteModel = new Invite();
    $result = $inviteModel->updateInvite($inviteId, $currentUser['id'], $label, $status);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    Logger::error('更新邀请码错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '更新失败']);
}

