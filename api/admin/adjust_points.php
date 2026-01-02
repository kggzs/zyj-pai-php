<?php
/**
 * 调整用户积分API（管理员）
 */
session_start();
require_once __DIR__ . '/../../core/autoload.php';

header('Content-Type: application/json; charset=utf-8');

// 检查管理员登录状态
$adminModel = new Admin();
if (!$adminModel->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => '请先登录'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $points = isset($_POST['points']) ? (int)$_POST['points'] : 0;
    $remark = isset($_POST['remark']) ? trim($_POST['remark']) : '';
    
    if ($userId <= 0) {
        echo json_encode(['success' => false, 'message' => '用户ID无效'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if ($points == 0) {
        echo json_encode(['success' => false, 'message' => '积分不能为0'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $result = $adminModel->adjustUserPoints($userId, $points, $remark);
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log('调整用户积分错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '调整失败'], JSON_UNESCAPED_UNICODE);
}

