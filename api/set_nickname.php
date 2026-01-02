<?php
/**
 * 设置昵称API
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
    echo json_encode(['success' => false, 'message' => '请先登录'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $currentUser = $userModel->getCurrentUser();
    $nickname = isset($_POST['nickname']) ? trim($_POST['nickname']) : '';
    
    // API层验证（虽然模型层也有验证，但API层应该先验证）
    if (mb_strlen($nickname) > 18) {
        echo json_encode(['success' => false, 'message' => '昵称长度不能超过18个字符'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 验证昵称格式（允许中文、英文、数字、下划线、连字符、空格）
    if (!empty($nickname) && !preg_match('/^[\x{4e00}-\x{9fa5}a-zA-Z0-9_\s-]+$/u', $nickname)) {
        echo json_encode(['success' => false, 'message' => '昵称只能包含中文、英文、数字、下划线、连字符和空格'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $result = $userModel->setNickname($currentUser['id'], $nickname);
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log('设置昵称错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '设置失败'], JSON_UNESCAPED_UNICODE);
}

