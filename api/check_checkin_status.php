<?php
/**
 * 检查签到状态API
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../core/autoload.php';

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
        echo json_encode(['success' => false, 'message' => '用户信息获取失败'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $pointsModel = new Points();
    
    $isCheckedIn = $pointsModel->checkCheckinStatus($currentUser['id']);
    $consecutiveDays = $pointsModel->getConsecutiveDays($currentUser['id']);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'is_checked_in' => $isCheckedIn,
            'consecutive_days' => $consecutiveDays
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    error_log('检查签到状态数据库错误：' . $e->getMessage());
    // 如果表不存在，返回默认值
    if (strpos($e->getMessage(), "doesn't exist") !== false || strpos($e->getMessage(), "不存在") !== false) {
        echo json_encode([
            'success' => true,
            'data' => [
                'is_checked_in' => false,
                'consecutive_days' => 0
            ]
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['success' => false, 'message' => '获取失败'], JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    error_log('检查签到状态错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '获取失败'], JSON_UNESCAPED_UNICODE);
}

