<?php
/**
 * 验证拍摄链接码API
 */
require_once __DIR__ . '/../core/autoload.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $inviteCode = isset($_GET['code']) ? trim($_GET['code']) : '';
    
    if (empty($inviteCode)) {
        echo json_encode(['valid' => false, 'message' => '拍摄链接码不能为空']);
        exit;
    }
    
    // 验证拍摄链接码长度（必须是8位）
    if (strlen($inviteCode) !== 8) {
        echo json_encode(['valid' => false, 'message' => '拍摄链接码格式错误（应为8位）']);
        exit;
    }
    
    // 验证拍摄链接码格式（只允许字母和数字）
    if (!preg_match('/^[a-zA-Z0-9]{8}$/', $inviteCode)) {
        echo json_encode(['valid' => false, 'message' => '拍摄链接码格式错误（只能包含字母和数字）']);
        exit;
    }
    
    $inviteModel = new Invite();
    $result = $inviteModel->validateInvite($inviteCode);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log('验证拍摄链接码错误：' . $e->getMessage());
    echo json_encode(['valid' => false, 'message' => '验证失败']);
}
