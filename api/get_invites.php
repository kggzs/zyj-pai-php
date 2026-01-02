<?php
/**
 * 获取拍摄链接列表API
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
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $pageSize = isset($_GET['page_size']) ? (int)$_GET['page_size'] : 20;
    
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
    
    $inviteModel = new Invite();
    $result = $inviteModel->getUserInvites($currentUser['id'], $page, $pageSize);
    
    // 添加VIP状态到返回数据
    $result['is_vip'] = $isVip;
    
    echo json_encode(['success' => true, 'data' => $result]);
    
} catch (Exception $e) {
    error_log('获取拍摄链接列表错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '获取失败']);
}
