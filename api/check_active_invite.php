<?php
/**
 * 检查用户是否有未过期的邀请链接API
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
    
    // 获取用户VIP状态（包括检查是否过期）
    $db = Database::getInstance();
    $userInfo = $db->fetchOne(
        "SELECT is_vip, vip_expire_time FROM users WHERE id = ?",
        [$currentUser['id']]
    );
    $isVip = false;
    if (($userInfo['is_vip'] ?? 0) == 1) {
        // 检查VIP是否过期
        if ($userInfo['vip_expire_time'] === null) {
            // 永久VIP
            $isVip = true;
        } else {
            // 检查是否过期
            $expireTime = strtotime($userInfo['vip_expire_time']);
            $isVip = $expireTime > time();
            
            // 如果过期，自动更新VIP状态
            if (!$isVip) {
                $db->execute(
                    "UPDATE users SET is_vip = 0, vip_expire_time = NULL WHERE id = ?",
                    [$currentUser['id']]
                );
            }
        }
    }
    
    // 只有普通用户才需要检查
    if ($isVip) {
        echo json_encode([
            'success' => true,
            'has_active_invite' => false,
            'is_vip' => true
        ]);
        exit;
    }
    
    // 检查普通用户是否有未过期的链接
    $activeInvite = $db->fetchOne(
        "SELECT expire_time, create_time FROM invites WHERE user_id = ? AND status = 1 AND (expire_time IS NULL OR expire_time > NOW()) ORDER BY create_time DESC LIMIT 1",
        [$currentUser['id']]
    );
    
    $hasActiveInvite = !empty($activeInvite);
    $expireTime = null;
    if ($hasActiveInvite && $activeInvite['expire_time']) {
        $expireTime = $activeInvite['expire_time'];
    }
    
    echo json_encode([
        'success' => true,
        'has_active_invite' => $hasActiveInvite,
        'is_vip' => false,
        'expire_time' => $expireTime
    ]);
    
} catch (Exception $e) {
    Logger::error('检查未过期邀请链接错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '检查失败']);
}

