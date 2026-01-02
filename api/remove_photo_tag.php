<?php
/**
 * 移除照片标签API
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
$tagId = isset($_POST['tag_id']) ? (int)$_POST['tag_id'] : 0;

if (!$photoId || !$tagId) {
    echo json_encode(['success' => false, 'message' => '参数错误']);
    exit;
}

try {
    $currentUser = $userModel->getCurrentUser();
    $photoModel = new Photo();
    
    $result = $photoModel->removeTagFromPhoto($photoId, $tagId, $currentUser['id']);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log('移除标签错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '移除失败：' . $e->getMessage()]);
}

