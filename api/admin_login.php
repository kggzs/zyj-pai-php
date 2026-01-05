<?php
/**
 * 管理员登录API
 */
session_start();
require_once __DIR__ . '/../core/autoload.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => '用户名和密码不能为空']);
        exit;
    }
    
    $adminModel = new Admin();
    $result = $adminModel->login($username, $password);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    Logger::error('管理员登录错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '登录失败']);
}

