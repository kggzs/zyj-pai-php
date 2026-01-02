<?php
/**
 * 获取所有照片列表API（管理员）
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

/**
 * 生成缩略图并转换为Base64
 */
function generateThumbnailBase64($imagePath, $maxSize = 150) {
    if (!file_exists($imagePath)) {
        return null;
    }
    
    $imageInfo = @getimagesize($imagePath);
    if (!$imageInfo) {
        return null;
    }
    
    $width = $imageInfo[0];
    $height = $imageInfo[1];
    $mimeType = $imageInfo['mime'];
    
    if ($width > $height) {
        $thumbWidth = $maxSize;
        $thumbHeight = intval($height * $maxSize / $width);
    } else {
        $thumbHeight = $maxSize;
        $thumbWidth = intval($width * $maxSize / $height);
    }
    
    switch ($mimeType) {
        case 'image/jpeg':
        case 'image/jpg':
            $srcImage = imagecreatefromjpeg($imagePath);
            break;
        case 'image/png':
            $srcImage = imagecreatefrompng($imagePath);
            break;
        case 'image/gif':
            $srcImage = imagecreatefromgif($imagePath);
            break;
        default:
            return null;
    }
    
    if (!$srcImage) {
        return null;
    }
    
    $thumbImage = imagecreatetruecolor($thumbWidth, $thumbHeight);
    
    if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
        imagealphablending($thumbImage, false);
        imagesavealpha($thumbImage, true);
        $transparent = imagecolorallocatealpha($thumbImage, 255, 255, 255, 127);
        imagefill($thumbImage, 0, 0, $transparent);
    }
    
    imagecopyresampled($thumbImage, $srcImage, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $height);
    
    ob_start();
    imagejpeg($thumbImage, null, 85);
    $imageData = ob_get_contents();
    ob_end_clean();
    
    imagedestroy($srcImage);
    imagedestroy($thumbImage);
    
    $base64 = base64_encode($imageData);
    return 'data:image/jpeg;base64,' . $base64;
}

try {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $pageSize = isset($_GET['page_size']) ? (int)$_GET['page_size'] : 20;
    $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
    $username = isset($_GET['username']) ? trim($_GET['username']) : null;
    $inviteCode = isset($_GET['invite_code']) ? trim($_GET['invite_code']) : null;
    
    // 记录调试信息
    error_log('get_all_photos.php: 查询照片列表, page: ' . $page . ', pageSize: ' . $pageSize . ', userId: ' . ($userId ?? 'NULL') . ', username: ' . ($username ?? 'NULL') . ', inviteCode: ' . ($inviteCode ?? 'NULL'));
    
    $result = $adminModel->getAllPhotos($page, $pageSize, $userId, $username, $inviteCode);
    
    error_log('get_all_photos.php: 查询结果, 照片数量: ' . count($result['list']) . ', 总数: ' . $result['total']);
    
    $config = require __DIR__ . '/../../config/config.php';
    
    // 获取所有照片的标签
    $photoModel = new Photo();
    $photoIds = array_column($result['list'], 'id');
    $photosTags = $photoModel->getPhotosTags($photoIds);
    
    // 使用API URL生成缩略图（不暴露路径）
    foreach ($result['list'] as &$photo) {
        $photo['thumbnail_url'] = 'api/view_photo.php?id=' . $photo['id'] . '&type=original&size=thumbnail';
        $photo['photo_id'] = $photo['id'];
        $photo['file_type'] = $photo['file_type'] ?? 'photo'; // 文件类型：photo或video
        $photo['video_duration'] = $photo['video_duration'] ?? null; // 录像时长（秒）
        $photo['upload_time'] = $photo['upload_time'];
        $photo['upload_ip'] = $photo['upload_ip'] ?? '';
        $photo['upload_ua'] = $photo['upload_ua'] ?? '';
        $photo['deleted_at'] = $photo['deleted_at'] ?? null; // 保留删除时间字段，用于显示删除状态
        $photo['invite_code'] = $photo['invite_code'] ?? ''; // 保留邀请码字段
        $photo['invite_label'] = $photo['invite_label'] ?? ''; // 邀请码标签
        
        // 添加标签信息
        $photo['tags'] = $photosTags[$photo['id']] ?? [];
        
        // 添加EXIF数据
        $photo['latitude'] = $photo['latitude'] ?? null;
        $photo['longitude'] = $photo['longitude'] ?? null;
        $photo['altitude'] = $photo['altitude'] ?? null;
        $photo['camera_make'] = $photo['camera_make'] ?? null;
        $photo['camera_model'] = $photo['camera_model'] ?? null;
        $photo['lens_model'] = $photo['lens_model'] ?? null;
        $photo['focal_length'] = $photo['focal_length'] ?? null;
        $photo['aperture'] = $photo['aperture'] ?? null;
        $photo['shutter_speed'] = $photo['shutter_speed'] ?? null;
        $photo['iso'] = $photo['iso'] ?? null;
        $photo['width'] = $photo['width'] ?? null;
        $photo['height'] = $photo['height'] ?? null;
        $photo['location_address'] = $photo['location_address'] ?? null;
        
        // 移除路径信息（不暴露给前端）
        unset($photo['original_path'], $photo['result_path']);
    }
    
    echo json_encode(['success' => true, 'data' => $result]);
    
} catch (Exception $e) {
    error_log('获取照片列表错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '获取失败']);
}

