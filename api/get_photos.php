<?php
/**
 * 获取照片列表API（返回Base64缩略图）
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

/**
 * 生成缩略图并转换为Base64
 */
function generateThumbnailBase64($imagePath, $maxSize = 150) {
    if (!file_exists($imagePath)) {
        return null;
    }
    
    // 获取图片信息
    $imageInfo = @getimagesize($imagePath);
    if (!$imageInfo) {
        return null;
    }
    
    $width = $imageInfo[0];
    $height = $imageInfo[1];
    $mimeType = $imageInfo['mime'];
    
    // 计算缩略图尺寸（保持宽高比）
    if ($width > $height) {
        $thumbWidth = $maxSize;
        $thumbHeight = intval($height * $maxSize / $width);
    } else {
        $thumbHeight = $maxSize;
        $thumbWidth = intval($width * $maxSize / $height);
    }
    
    // 创建原图资源
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
    
    // 创建缩略图资源
    $thumbImage = imagecreatetruecolor($thumbWidth, $thumbHeight);
    
    // PNG和GIF需要保持透明度
    if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
        imagealphablending($thumbImage, false);
        imagesavealpha($thumbImage, true);
        $transparent = imagecolorallocatealpha($thumbImage, 255, 255, 255, 127);
        imagefilledrectangle($thumbImage, 0, 0, $thumbWidth, $thumbHeight, $transparent);
    }
    
    // 缩放图片
    imagecopyresampled($thumbImage, $srcImage, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $height);
    
    // 输出到缓冲区并转换为Base64
    ob_start();
    imagejpeg($thumbImage, null, 85); // 使用JPEG格式，质量85%
    $imageData = ob_get_contents();
    ob_end_clean();
    
    // 释放内存
    imagedestroy($srcImage);
    imagedestroy($thumbImage);
    
    // 转换为Base64
    $base64 = base64_encode($imageData);
    return 'data:image/jpeg;base64,' . $base64;
}

try {
    $currentUser = $userModel->getCurrentUser();
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $pageSize = isset($_GET['page_size']) ? (int)$_GET['page_size'] : null;
    $inviteCode = isset($_GET['invite_code']) ? trim($_GET['invite_code']) : null;
    $tagName = isset($_GET['tag']) ? trim($_GET['tag']) : null;
    
    // 验证分页参数
    if ($page < 1) {
        $page = 1;
    }
    // 如果没有指定page_size，默认加载所有照片（设置一个较大的值，比如10000）
    // 这样可以确保所有拍摄码的照片都能显示
    if ($pageSize === null || $pageSize < 1) {
        $pageSize = 10000; // 足够大的值，确保加载所有照片
    } elseif ($pageSize > 10000) {
        $pageSize = 10000; // 限制最大值为10000，防止性能问题
    }
    
    // 验证邀请码格式（如果提供）
    if ($inviteCode !== null && $inviteCode !== '') {
        if (!preg_match('/^[a-zA-Z0-9]{8}$/', $inviteCode)) {
            echo json_encode(['success' => false, 'message' => '邀请码格式错误']);
            exit;
        }
    }
    
    // 验证标签名称长度和格式（如果提供）
    if ($tagName !== null && $tagName !== '') {
        if (mb_strlen($tagName) > 10) {
            echo json_encode(['success' => false, 'message' => '标签名称过长']);
            exit;
        }
        if (!preg_match('/^[\x{4e00}-\x{9fa5}a-zA-Z0-9_-]+$/u', $tagName)) {
            echo json_encode(['success' => false, 'message' => '标签名称格式错误']);
            exit;
        }
    }
    
    $photoModel = new Photo();
    
    // 记录调试信息
    Logger::debug('get_photos.php: 查询照片列表, userId: ' . $currentUser['id'] . ', page: ' . $page . ', pageSize: ' . $pageSize . ', inviteCode: ' . ($inviteCode ?? 'NULL') . ', tagName: ' . ($tagName ?? 'NULL'));
    
    $result = $photoModel->getUserPhotos($currentUser['id'], $page, $pageSize, $inviteCode, $tagName);
    
    Logger::debug('get_photos.php: 查询结果, 照片数量: ' . count($result['list']) . ', 总数: ' . $result['total']);
    
    $config = require __DIR__ . '/../config/config.php';
    
    // 获取所有照片的标签
    $photoIds = array_column($result['list'], 'id');
    $photosTags = $photoModel->getPhotosTags($photoIds);
    
    // 为每张照片生成Base64缩略图（不暴露路径）
    foreach ($result['list'] as &$photo) {
        // 使用API URL生成缩略图（不暴露路径）
        $photo['thumbnail_url'] = 'api/view_photo.php?id=' . $photo['id'] . '&type=original&size=thumbnail';
        
        // 保留照片ID和相关信息用于显示（不暴露文件路径）
        $photo['photo_id'] = $photo['id'];
        $photo['file_type'] = $photo['file_type'] ?? 'photo'; // 文件类型：photo或video
        $photo['video_duration'] = $photo['video_duration'] ?? null; // 录像时长（秒）
        $photo['upload_time'] = $photo['upload_time'];
        $photo['upload_ip'] = $photo['upload_ip'] ?? '';
        $photo['upload_ua'] = $photo['upload_ua'] ?? '';
        $photo['invite_code'] = $photo['invite_code'] ?? '';
        $photo['invite_label'] = $photo['invite_label'] ?? ''; // 邀请码标签
        
        // 添加标签信息
        $photo['tags'] = $photosTags[$photo['id']] ?? [];
        
        // 移除路径信息（不暴露给前端）
        unset($photo['original_path'], $photo['result_path'], $photo['original_url'], $photo['result_url']);
    }
    
    echo json_encode(['success' => true, 'data' => $result]);
    
} catch (Exception $e) {
    Logger::debug('获取照片列表错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '获取失败']);
}
