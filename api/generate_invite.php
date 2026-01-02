<?php
/**
 * 生成拍摄链接API
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
    
    // 获取自定义到期时间参数
    $customExpireTime = null;
    if (isset($_POST['expire_time']) && !empty($_POST['expire_time'])) {
        $expireTimeInput = trim($_POST['expire_time']);
        if ($expireTimeInput === 'unlimited') {
            $customExpireTime = 'unlimited';
        } else {
            // 验证日期格式
            $timestamp = strtotime($expireTimeInput);
            if ($timestamp !== false && $timestamp > time()) {
                $customExpireTime = date('Y-m-d H:i:s', $timestamp);
            } else {
                echo json_encode(['success' => false, 'message' => '无效的到期时间']);
                exit;
            }
        }
    }
    
    $inviteModel = new Invite();
    $result = $inviteModel->generateInvite($currentUser['id'], $customExpireTime);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log('生成邀请链接错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '生成失败']);
}
