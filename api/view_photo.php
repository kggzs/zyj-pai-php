<?php
/**
 * 查看照片API（通过ID查看，不暴露路径，支持缓存）
 */
session_start();
require_once __DIR__ . '/../core/autoload.php';

// 检查登录状态（用户或管理员）
$userModel = new User();
$adminModel = new Admin();
$isAdmin = $adminModel->isLoggedIn();
$isUser = $userModel->isLoggedIn();

if (!$isAdmin && !$isUser) {
    http_response_code(403);
    die('请先登录');
}

$photoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$type = isset($_GET['type']) ? trim($_GET['type']) : 'original';
$size = isset($_GET['size']) ? trim($_GET['size']) : 'original';

if (!$photoId) {
    http_response_code(400);
    die('参数错误：照片ID无效');
}

// 白名单验证：type只支持 'original'
$allowedTypes = ['original'];
if (!in_array($type, $allowedTypes)) {
    http_response_code(400);
    die('参数错误：不支持的类型');
}

// 白名单验证：size只支持 'original', 'thumbnail', 'medium', 'large'
$allowedSizes = ['original', 'thumbnail', 'medium', 'large'];
if (!in_array($size, $allowedSizes)) {
    http_response_code(400);
    die('参数错误：不支持的尺寸');
}

try {
    $photoModel = new Photo();
    
    // 获取照片信息（管理员可以查看所有照片，普通用户只能查看自己的照片）
    if ($isAdmin) {
        $photo = $photoModel->getPhotoById($photoId, null, true);
    } else {
        $currentUser = $userModel->getCurrentUser();
        $photo = $photoModel->getPhotoById($photoId, $currentUser['id']);
    }
    
    if (!$photo) {
        http_response_code(404);
        die('照片不存在或无权限访问');
    }
    
    // 使用原图路径（照片或录像）
    $relativePath = $photo['original_path'];
    $fileType = $photo['file_type'] ?? 'photo';
    
    // 安全处理路径
    $relativePath = ltrim($relativePath, '/');
    if (strpos($relativePath, 'uploads/') !== 0) {
        http_response_code(403);
        die('非法路径');
    }
    
    $relativePath = str_replace(['../', '..\\'], '', $relativePath);
    $filePath = __DIR__ . '/../' . $relativePath;
    $filePath = realpath($filePath);
    $uploadsDir = realpath(__DIR__ . '/../uploads');
    
    if ($filePath === false || $uploadsDir === false || strpos($filePath, $uploadsDir) !== 0) {
        http_response_code(403);
        die('非法路径');
    }
    
    if (!file_exists($filePath)) {
        http_response_code(404);
        die('文件不存在');
    }
    
    // 获取配置
    $config = require __DIR__ . '/../config/config.php';
    $cacheEnabled = $config['image']['cache_enabled'] ?? true;
    $cacheDuration = $config['image']['cache_duration'] ?? 86400;
    
    // 如果是视频文件
    if ($fileType === 'video') {
        // 获取视频文件的MIME类型
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $videoMimeTypes = [
            'webm' => 'video/webm',
            'mp4' => 'video/mp4',
            'ogg' => 'video/ogg'
        ];
        $mimeType = $videoMimeTypes[$extension] ?? 'video/webm';
        
        // 只有缩略图请求才返回PNG图标，其他情况返回视频文件
        if ($size === 'thumbnail') {
            // 生成一个简单的视频图标作为缩略图
            $thumbnailSize = 150;
            
            // 创建一个简单的视频图标
            $icon = imagecreatetruecolor($thumbnailSize, $thumbnailSize);
            $bgColor = imagecolorallocate($icon, 40, 40, 40);
            $playColor = imagecolorallocate($icon, 255, 255, 255);
            imagefill($icon, 0, 0, $bgColor);
            
            // 绘制播放按钮（三角形）
            $centerX = $thumbnailSize / 2;
            $centerY = $thumbnailSize / 2;
            $triangleSize = $thumbnailSize / 3;
            $points = [
                $centerX - $triangleSize / 2, $centerY - $triangleSize,
                $centerX - $triangleSize / 2, $centerY + $triangleSize,
                $centerX + $triangleSize, $centerY
            ];
            imagefilledpolygon($icon, $points, 3, $playColor);
            
            // 输出图标
            header('Content-Type: image/png');
            imagepng($icon);
            imagedestroy($icon);
            exit;
        }
        
        // 对于medium、large和original，返回视频文件
        
        // 返回原始视频文件
        $fileSize = filesize($filePath);
        $lastModified = filemtime($filePath);
        
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . $fileSize);
        header('Accept-Ranges: bytes');
        header('Cache-Control: public, max-age=' . $cacheDuration);
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cacheDuration) . ' GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');
        
        // 支持Range请求（视频流式播放）
        if (isset($_SERVER['HTTP_RANGE'])) {
            $range = $_SERVER['HTTP_RANGE'];
            $range = str_replace('bytes=', '', $range);
            $range = explode('-', $range);
            $start = (int)$range[0];
            $end = $range[1] ? (int)$range[1] : $fileSize - 1;
            $length = $end - $start + 1;
            
            http_response_code(206);
            header('Content-Range: bytes ' . $start . '-' . $end . '/' . $fileSize);
            header('Content-Length: ' . $length);
            
            $fp = fopen($filePath, 'rb');
            fseek($fp, $start);
            echo fread($fp, $length);
            fclose($fp);
        } else {
            readfile($filePath);
        }
        exit;
    }
    
    // 处理图片文件
    $imageInfo = @getimagesize($filePath);
    if (!$imageInfo) {
        http_response_code(500);
        die('无法读取图片');
    }
    
    $mimeType = $imageInfo['mime'];
    $width = $imageInfo[0];
    $height = $imageInfo[1];
    
    // 定义尺寸映射
    $sizeMap = [
        'thumbnail' => 150,
        'medium' => 500,
        'large' => 1200,
        'original' => null
    ];
    
    $targetSize = $sizeMap[$size] ?? null;
    
    // 如果需要生成缩略图
    if ($targetSize && ($width > $targetSize || $height > $targetSize)) {
        // 检查缓存
        $cacheKey = md5($filePath . $targetSize);
        $cacheDir = __DIR__ . '/../cache/images/';
        $cacheFile = $cacheDir . $cacheKey . '.jpg';
        
        if ($cacheEnabled && file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheDuration) {
            // 使用缓存
            $filePath = $cacheFile;
            $mimeType = 'image/jpeg';
        } else {
            // 生成缩略图
            $image = null;
            switch ($mimeType) {
                case 'image/jpeg':
                    $image = imagecreatefromjpeg($filePath);
                    break;
                case 'image/png':
                    $image = imagecreatefrompng($filePath);
                    break;
                case 'image/webp':
                    if (function_exists('imagecreatefromwebp')) {
                        $image = imagecreatefromwebp($filePath);
                    }
                    break;
            }
            
            if ($image) {
                // 计算新尺寸
                $ratio = min($targetSize / $width, $targetSize / $height);
                $newWidth = (int)($width * $ratio);
                $newHeight = (int)($height * $ratio);
                
                // 创建新图片
                $newImage = imagecreatetruecolor($newWidth, $newHeight);
                
                // 保持透明度
                if ($mimeType === 'image/png' || $mimeType === 'image/webp') {
                    imagealphablending($newImage, false);
                    imagesavealpha($newImage, true);
                    $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
                    imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
                }
                
                // 缩放图片
                imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                
                // 保存到缓存
                if ($cacheEnabled) {
                    if (!is_dir($cacheDir)) {
                        mkdir($cacheDir, 0755, true);
                    }
                    imagejpeg($newImage, $cacheFile, 85);
                    $filePath = $cacheFile;
                } else {
                    // 直接输出到内存
                    ob_start();
                    imagejpeg($newImage, null, 85);
                    $imageData = ob_get_contents();
                    ob_end_clean();
                    imagedestroy($newImage);
                    imagedestroy($image);
                    
                    // 设置响应头
                    header('Content-Type: image/jpeg');
                    header('Content-Length: ' . strlen($imageData));
                    if ($cacheEnabled) {
                        header('Cache-Control: public, max-age=' . $cacheDuration);
                        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cacheDuration) . ' GMT');
                    }
                    echo $imageData;
                    exit;
                }
                
                imagedestroy($newImage);
                imagedestroy($image);
                $mimeType = 'image/jpeg';
            }
        }
    }
    
    // 获取文件信息
    $fileSize = filesize($filePath);
    $lastModified = filemtime($filePath);
    
    // 设置响应头
    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . $fileSize);
    header('Cache-Control: public, max-age=' . $cacheDuration);
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cacheDuration) . ' GMT');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');
    
    // 检查If-Modified-Since头（304缓存）
    if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
        $ifModifiedSince = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
        if ($ifModifiedSince >= $lastModified) {
            http_response_code(304);
            exit;
        }
    }
    
    // 输出文件
    readfile($filePath);
    exit;
    
} catch (Exception $e) {
    error_log('查看照片错误：' . $e->getMessage());
    http_response_code(500);
    die('查看失败');
}

