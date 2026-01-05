<?php
/**
 * 找回密码API（通过邮箱）
 */
session_start();
require_once __DIR__ . '/../core/autoload.php';

error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');

try {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'send_code') {
        // 发送验证码
        $email = $_POST['email'] ?? '';
        if (empty($email)) {
            echo json_encode(['success' => false, 'message' => '请填写邮箱'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // 查找用户
        $db = Database::getInstance();
        $user = $db->fetchOne("SELECT id FROM users WHERE email = ? AND email_verified = 1", [$email]);
        if (!$user) {
            echo json_encode(['success' => false, 'message' => '该邮箱未绑定或未验证'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $userModel = new User();
        $result = $userModel->sendEmailVerificationCode($user['id'], $email, 'reset');
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        
    } else if ($action === 'reset') {
        // 重置密码
        $email = $_POST['email'] ?? '';
        $code = $_POST['code'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        
        if (empty($email) || empty($code) || empty($newPassword)) {
            echo json_encode(['success' => false, 'message' => '请填写完整信息'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $userModel = new User();
        $result = $userModel->resetPasswordByEmail($email, $code, $newPassword);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        
    } else {
        echo json_encode(['success' => false, 'message' => '无效的操作'], JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    Logger::error('找回密码错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '操作失败'], JSON_UNESCAPED_UNICODE);
}

