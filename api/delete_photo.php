<?php
/**
 * 用户删除照片API（软删除）
 */
session_start();
require_once __DIR__ . '/../core/autoload.php';

header('Content-Type: application/json; charset=utf-8');

// 检查登录状态
$userModel = new User();
if (!$userModel->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => '请先登录']);
    exit;
}

// 只接受POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '请求方法错误']);
    exit;
}

$photoId = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if (!$photoId) {
    echo json_encode(['success' => false, 'message' => '参数错误']);
    exit;
}

try {
    $currentUser = $userModel->getCurrentUser();
    $photoModel = new Photo();
    
    $result = $photoModel->softDeletePhoto($photoId, $currentUser['id']);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log('删除照片错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '删除失败：' . $e->getMessage()]);
}

