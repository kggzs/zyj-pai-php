<?php
/**
 * 执行签到API
 */
// 确保在输出任何内容之前设置header
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../core/autoload.php';

// 设置错误处理，避免输出错误信息
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');

// 检查登录状态
$userModel = new User();
if (!$userModel->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => '请先登录']);
    exit;
}

try {
    $currentUser = $userModel->getCurrentUser();
    
    if (!$currentUser) {
        echo json_encode(['success' => false, 'message' => '用户信息获取失败']);
        exit;
    }
    
    // 检查是否为VIP
    $db = Database::getInstance();
    $userInfo = $db->fetchOne(
        "SELECT is_vip, vip_expire_time FROM users WHERE id = ?",
        [$currentUser['id']]
    );
    $isVip = false;
    if (($userInfo['is_vip'] ?? 0) == 1) {
        if ($userInfo['vip_expire_time'] === null) {
            $isVip = true; // 永久VIP
        } else {
            $expireTime = strtotime($userInfo['vip_expire_time']);
            $isVip = $expireTime > time();
        }
    }
    
    $pointsModel = new Points();
    $result = $pointsModel->doCheckin($currentUser['id'], $isVip);
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    Logger::error('签到数据库错误：' . $e->getMessage());
    // 检查是否是表不存在的错误
    if (strpos($e->getMessage(), "doesn't exist") !== false || strpos($e->getMessage(), "不存在") !== false) {
        echo json_encode(['success' => false, 'message' => '签到功能未初始化，请先执行数据库迁移'], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['success' => false, 'message' => '数据库错误，请稍后重试'], JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    Logger::error('签到错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '签到失败：' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

