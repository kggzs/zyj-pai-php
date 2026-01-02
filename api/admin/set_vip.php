<?php
/**
 * 设置用户VIP状态API
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
    $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $isVip = isset($_POST['is_vip']) ? (int)$_POST['is_vip'] : 0;
    $vipExpireTime = isset($_POST['vip_expire_time']) ? trim($_POST['vip_expire_time']) : null;
    
    if ($userId <= 0) {
        echo json_encode(['success' => false, 'message' => '无效的用户ID']);
        exit;
    }
    
    // 检查用户是否存在
    $db = Database::getInstance();
    $user = $db->fetchOne("SELECT id, is_admin FROM users WHERE id = ?", [$userId]);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => '用户不存在']);
        exit;
    }
    
    // 不能修改管理员的VIP状态
    if ($user['is_admin'] == 1) {
        echo json_encode(['success' => false, 'message' => '不能修改管理员的VIP状态']);
        exit;
    }
    
    // 处理VIP到期时间
    $expireTimeValue = null;
    if ($isVip == 1) {
        if ($vipExpireTime === 'unlimited' || empty($vipExpireTime)) {
            // 永久VIP
            $expireTimeValue = null;
        } else {
            // 验证日期格式
            $timestamp = strtotime($vipExpireTime);
            if ($timestamp === false) {
                echo json_encode(['success' => false, 'message' => '无效的到期时间格式']);
                exit;
            }
            $expireTimeValue = date('Y-m-d H:i:s', $timestamp);
        }
    } else {
        // 取消VIP，清空到期时间
        $expireTimeValue = null;
    }
    
    // 更新用户VIP状态
    $db->execute(
        "UPDATE users SET is_vip = ?, vip_expire_time = ? WHERE id = ?",
        [$isVip, $expireTimeValue, $userId]
    );
    
    // 记录操作日志
    $adminModel->logOperation(
        'vip_set',
        'user',
        $userId,
        $isVip == 1 ? "设置用户VIP：用户ID {$userId}，到期时间：" . ($expireTimeValue ?: '永久') : "取消用户VIP：用户ID {$userId}"
    );
    
    echo json_encode([
        'success' => true,
        'message' => $isVip == 1 ? 'VIP设置成功' : 'VIP已取消',
        'is_vip' => $isVip,
        'vip_expire_time' => $expireTimeValue
    ]);
    
} catch (Exception $e) {
    error_log('设置VIP错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '设置失败']);
}

