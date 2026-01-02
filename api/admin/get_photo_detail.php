<?php
/**
 * 获取照片详情API（管理员）
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

try {
    $photoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($photoId <= 0) {
        echo json_encode(['success' => false, 'message' => '照片ID无效']);
        exit;
    }
    
    $db = Database::getInstance();
    
    // 获取照片详情（包括已删除的照片）
    $photo = $db->fetchOne(
        "SELECT p.*, u.username as user_name, i.label as invite_label
         FROM photos p
         LEFT JOIN users u ON p.user_id = u.id
         LEFT JOIN invites i ON p.invite_code = i.invite_code
         WHERE p.id = ?",
        [$photoId]
    );
    
    if (!$photo) {
        echo json_encode(['success' => false, 'message' => '照片不存在']);
        exit;
    }
    
    // 获取照片标签
    $photoModel = new Photo();
    $tags = $photoModel->getPhotoTags($photoId);
    $photo['tags'] = $tags;
    
    // 添加缩略图URL
    $photo['thumbnail_url'] = 'api/view_photo.php?id=' . $photo['id'] . '&type=original&size=thumbnail';
    $photo['photo_id'] = $photo['id'];
    
    // 移除路径信息（不暴露给前端）
    unset($photo['original_path'], $photo['result_path']);
    
    echo json_encode(['success' => true, 'data' => $photo]);
    
} catch (Exception $e) {
    error_log('获取照片详情错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '获取失败']);
}

