<?php
/**
 * 批量下载照片API（ZIP打包，VIP功能）
 */
session_start();
require_once __DIR__ . '/../core/autoload.php';

// 检查登录状态
$userModel = new User();
if (!$userModel->isLoggedIn()) {
    http_response_code(403);
    die('请先登录');
}

// 检查VIP权限
$currentUser = $userModel->getCurrentUser();
$db = Database::getInstance();
$userInfo = $db->fetchOne(
    "SELECT is_vip, vip_expire_time FROM users WHERE id = ?",
    [$currentUser['id']]
);
$isVip = false;
if (($userInfo['is_vip'] ?? 0) == 1) {
    if ($userInfo['vip_expire_time'] === null) {
        $isVip = true;
    } else {
        $expireTime = strtotime($userInfo['vip_expire_time']);
        $isVip = $expireTime > time();
    }
}

if (!$isVip) {
    http_response_code(403);
    die('此功能仅限VIP会员使用');
}

$photoIds = isset($_GET['ids']) ? $_GET['ids'] : '';

if (empty($photoIds)) {
    http_response_code(400);
    die('请选择要下载的照片');
}

// 解析照片ID列表
$photoIds = explode(',', $photoIds);
$photoIds = array_filter(array_map('intval', $photoIds), function($id) {
    return $id > 0;
});

if (empty($photoIds)) {
    http_response_code(400);
    die('无效的照片ID');
}

// 限制一次最多下载100张照片（防止资源消耗过大）
if (count($photoIds) > 100) {
    http_response_code(400);
    die('一次最多只能下载100张照片');
}

try {
    $photoModel = new Photo();
    $photos = $photoModel->getPhotosByIds($photoIds, $currentUser['id']);
    
    if (empty($photos)) {
        http_response_code(404);
        die('照片不存在或无权限访问');
    }
    
    // 检查ZipArchive类是否可用
    if (!class_exists('ZipArchive')) {
        http_response_code(500);
        die('服务器不支持ZIP功能');
    }
    
    // 创建临时ZIP文件
    $downloadZipFileName = 'photos_' . date('YmdHis') . '_' . uniqid() . '.zip';
    $zipFilePath = sys_get_temp_dir() . '/' . $downloadZipFileName;
    
    $zip = new ZipArchive();
    if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        http_response_code(500);
        die('无法创建ZIP文件');
    }
    
    $addedCount = 0;
    foreach ($photos as $photo) {
        // 获取文件路径（照片或录像）
        $filePath = __DIR__ . '/../' . ltrim($photo['original_path'], '/');
        $fileType = $photo['file_type'] ?? 'photo';
        
        // 安全处理路径
        $filePath = realpath($filePath);
        $uploadsDir = realpath(__DIR__ . '/../uploads');
        
        if ($filePath && strpos($filePath, $uploadsDir) === 0 && file_exists($filePath)) {
            // 根据文件类型确定ZIP内的文件名，从原始路径获取真实扩展名
            $extension = pathinfo($photo['original_path'], PATHINFO_EXTENSION);
            if (empty($extension)) {
                // 如果无法从路径获取扩展名，根据文件类型设置默认扩展名
                $extension = ($fileType === 'video') ? 'webm' : 'jpg';
            }
            
            // ZIP内的文件名（不是ZIP文件本身的名字）
            if ($fileType === 'video') {
                $entryFileName = 'video_' . $photo['id'] . '.' . $extension;
            } else {
                $entryFileName = 'photo_' . $photo['id'] . '.' . $extension;
            }
            $zip->addFile($filePath, $entryFileName);
            $addedCount++;
        }
    }
    
    $zip->close();
    
    if ($addedCount === 0) {
        @unlink($zipFilePath);
        http_response_code(404);
        die('没有可下载的文件');
    }
    
    // 输出ZIP文件
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $downloadZipFileName . '"');
    header('Content-Length: ' . filesize($zipFilePath));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    readfile($zipFilePath);
    
    // 删除临时文件
    @unlink($zipFilePath);
    exit;
    
} catch (Exception $e) {
    error_log('批量下载照片错误：' . $e->getMessage());
    http_response_code(500);
    die('下载失败');
}

