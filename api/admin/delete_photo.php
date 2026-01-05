<?php
/**
 * 管理员删除照片API（硬删除，删除文件）
 */
session_start();
require_once __DIR__ . '/../../core/autoload.php';

header('Content-Type: application/json; charset=utf-8');

// 检查管理员登录状态
$adminModel = new Admin();
if (!$adminModel->isLoggedIn()) {
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
    $photoModel = new Photo();
    
    // 获取照片信息用于日志
    $photo = $photoModel->getPhotoById($photoId, null, true);
    
    $result = $photoModel->hardDeletePhoto($photoId);
    
    // 记录操作日志
    if ($result['success'] && $photo) {
        $adminModel->logOperation(
            'photo_delete',
            'photo',
            $photoId,
            "删除照片：照片ID {$photoId}，用户ID {$photo['user_id']}"
        );
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    Logger::error('管理员删除照片错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '删除失败：' . $e->getMessage()]);
}

