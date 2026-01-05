<?php
/**
 * 为照片添加标签API
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

$photoId = isset($_POST['photo_id']) ? (int)$_POST['photo_id'] : 0;
$tagName = isset($_POST['tag_name']) ? trim($_POST['tag_name']) : '';

if (!$photoId) {
    echo json_encode(['success' => false, 'message' => '照片ID无效']);
    exit;
}

if (empty($tagName)) {
    echo json_encode(['success' => false, 'message' => '标签名称不能为空']);
    exit;
}

// 验证标签名称长度和格式
if (mb_strlen($tagName) > 10) {
    echo json_encode(['success' => false, 'message' => '标签名称长度不能超过10个字符']);
    exit;
}

// 验证标签名称格式（只允许中文、英文、数字、下划线、连字符）
if (!preg_match('/^[\x{4e00}-\x{9fa5}a-zA-Z0-9_-]+$/u', $tagName)) {
    echo json_encode(['success' => false, 'message' => '标签名称只能包含中文、英文、数字、下划线和连字符']);
    exit;
}

try {
    $currentUser = $userModel->getCurrentUser();
    $photoModel = new Photo();
    
    $result = $photoModel->addTagToPhoto($photoId, $tagName, $currentUser['id']);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    Logger::error('添加标签错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '添加失败：' . $e->getMessage()]);
}

