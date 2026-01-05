<?php
/**
 * 获取CSRF Token API
 */
session_start();
require_once __DIR__ . '/../core/autoload.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $token = Security::generateCsrfToken();
    
    echo json_encode([
        'success' => true,
        'csrf_token' => $token
    ]);
    
} catch (Exception $e) {
    Logger::error('获取CSRF Token错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '获取失败']);
}

