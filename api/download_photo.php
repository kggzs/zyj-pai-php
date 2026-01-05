<?php
/**
 * 下载照片API（通过ID下载，不暴露路径）
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
$type = isset($_GET['type']) ? $_GET['type'] : 'original'; // 只支持 'original'

if (!$photoId || $type !== 'original') {
    http_response_code(400);
    die('参数错误');
}

try {
    $photoModel = new Photo();
    
    // 获取照片信息（管理员可以下载所有照片，普通用户只能下载自己的照片）
    if ($isAdmin) {
        $photo = $photoModel->getPhotoById($photoId);
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
    
    // 根据文件类型确定文件名和扩展名
    if ($fileType === 'video') {
        $extension = pathinfo($relativePath, PATHINFO_EXTENSION);
        $filename = 'video_' . $photo['id'] . '.' . $extension;
    } else {
        $filename = 'photo_' . $photo['id'] . '.jpg';
    }
    
    // 安全处理路径，防止路径遍历攻击
    $relativePath = ltrim($relativePath, '/');
    // 确保路径在uploads目录下
    if (strpos($relativePath, 'uploads/') !== 0) {
        http_response_code(403);
        die('非法路径');
    }
    
    // 移除路径中的..等危险字符
    $relativePath = str_replace(['../', '..\\'], '', $relativePath);
    
    // 构建完整路径
    $filePath = __DIR__ . '/../' . $relativePath;
    
    // 规范化路径，防止绕过
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
    
    // 获取文件信息
    $mimeType = mime_content_type($filePath);
    $fileSize = filesize($filePath);
    
    // 设置下载头
    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . $fileSize);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    // 输出文件
    readfile($filePath);
    exit;
    
} catch (Exception $e) {
    Logger::error('下载照片错误：' . $e->getMessage());
    http_response_code(500);
    die('下载失败');
}

