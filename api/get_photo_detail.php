<?php
/**
 * 获取照片详情API
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

$photoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$photoId) {
    echo json_encode(['success' => false, 'message' => '参数错误']);
    exit;
}

try {
    $currentUser = $userModel->getCurrentUser();
    $photoModel = new Photo();
    
    // 获取照片信息
    $photo = $photoModel->getPhotoById($photoId, $currentUser['id']);
    
    if (!$photo) {
        echo json_encode(['success' => false, 'message' => '照片不存在或无权限访问']);
        exit;
    }
    
    // 获取照片标签
    $tags = $photoModel->getPhotoTags($photoId, $currentUser['id']);
    
    // 使用API URL生成图片（不暴露路径）
    $fileType = $photo['file_type'] ?? 'photo';
    $imageUrl = 'api/view_photo.php?id=' . $photoId . '&type=original&size=large';
    
    // 构建返回数据（包含EXIF数据）
    $photoData = [
        'id' => $photo['id'],
        'invite_code' => $photo['invite_code'] ?? '',
        'upload_time' => $photo['upload_time'] ?? '',
        'upload_ip' => $photo['upload_ip'] ?? '未知',
        'upload_ua' => $photo['upload_ua'] ?? '',
        'file_type' => $fileType,
        'video_duration' => $photo['video_duration'] ?? null,
        'tags' => $tags,
        'image_url' => $imageUrl,
        // EXIF数据
        'latitude' => $photo['latitude'] ?? null,
        'longitude' => $photo['longitude'] ?? null,
        'altitude' => $photo['altitude'] ?? null,
        'camera_make' => $photo['camera_make'] ?? null,
        'camera_model' => $photo['camera_model'] ?? null,
        'lens_model' => $photo['lens_model'] ?? null,
        'focal_length' => $photo['focal_length'] ?? null,
        'aperture' => $photo['aperture'] ?? null,
        'shutter_speed' => $photo['shutter_speed'] ?? null,
        'iso' => $photo['iso'] ?? null,
        'exposure_mode' => $photo['exposure_mode'] ?? null,
        'white_balance' => $photo['white_balance'] ?? null,
        'flash' => $photo['flash'] ?? null,
        'width' => $photo['width'] ?? null,
        'height' => $photo['height'] ?? null,
        'location_address' => $photo['location_address'] ?? null
    ];
    
    // 移除路径信息（不暴露给前端）
    unset($photo['original_path'], $photo['result_path']);
    
    echo json_encode(['success' => true, 'data' => $photoData]);
    
} catch (Exception $e) {
    Logger::error('获取照片详情错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '获取失败']);
}

