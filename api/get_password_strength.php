<?php
/**
 * 获取密码强度等级API
 */
session_start();
require_once __DIR__ . '/../core/autoload.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $password = $_POST['password'] ?? $_GET['password'] ?? '';
    
    // 获取密码要求详情（包括每个要求的满足状态）
    $requirementsDetail = Security::getPasswordRequirementsDetail($password);
    
    if (empty($password)) {
        echo json_encode([
            'success' => true,
            'level' => 0,
            'text' => '未输入',
            'requirements' => $requirementsDetail
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 计算密码强度
    $strength = Security::calculatePasswordStrength($password);
    
    echo json_encode([
        'success' => true,
        'level' => $strength['level'],
        'text' => $strength['text'],
        'requirements' => $requirementsDetail
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    Logger::error('获取密码强度错误：' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '获取失败'
    ], JSON_UNESCAPED_UNICODE);
}

