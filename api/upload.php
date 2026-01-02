<?php
/**
 * 图片上传API
 */
// 禁用错误输出到响应中
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../core/autoload.php';

// 设置JSON响应头
header('Content-Type: application/json; charset=utf-8');

// 捕获所有输出，避免在JSON响应前输出任何内容
if (!ob_get_level()) {
    ob_start();
}

try {
    $config = require __DIR__ . '/../config/config.php';
    
    // API频率限制检查
    if ($config['rate_limit']['enabled'] ?? true) {
        $rateLimiter = new RateLimiter();
        $limitCheck = $rateLimiter->checkApiLimit('api/upload.php');
        
        if (!$limitCheck['allowed']) {
            if (ob_get_level()) {
                ob_clean();
            }
            http_response_code(429); // Too Many Requests
            header('X-RateLimit-Limit: ' . $limitCheck['limit']);
            header('X-RateLimit-Remaining: ' . $limitCheck['remaining']);
            header('X-RateLimit-Reset: ' . $limitCheck['reset_time']);
            echo json_encode([
                'success' => false,
                'message' => '请求过于频繁，请稍后再试',
                'retry_after' => $limitCheck['reset_time'] - time()
            ]);
            exit;
        }
        
        // 设置响应头
        header('X-RateLimit-Limit: ' . $limitCheck['limit']);
        header('X-RateLimit-Remaining: ' . $limitCheck['remaining']);
        header('X-RateLimit-Reset: ' . $limitCheck['reset_time']);
    }
    
    // 上传频率限制检查（按IP）
    if ($config['upload_security']['max_upload_per_hour'] ?? false) {
        $rateLimiter = new RateLimiter();
        $uploadIp = Security::getClientIp();
        $hourlyLimit = $rateLimiter->checkLimit(
            'upload_hourly:' . $uploadIp,
            $config['upload_security']['max_upload_per_hour'],
            3600
        );
        
        if (!$hourlyLimit['allowed']) {
            if (ob_get_level()) {
                ob_clean();
            }
            // 记录异常行为
            try {
                $adminModel = new Admin();
                $adminModel->logAbnormalBehavior(
                    'upload_rate_limit_exceeded',
                    null,
                    "IP {$uploadIp} 超过每小时上传限制",
                    2, // 中等严重程度
                    $uploadIp,
                    $_SERVER['HTTP_USER_AGENT'] ?? null,
                    $_SERVER['REQUEST_URI'] ?? null,
                    ['limit' => $config['upload_security']['max_upload_per_hour']]
                );
            } catch (Exception $e) {
                error_log('记录异常行为失败：' . $e->getMessage());
            }
            
            http_response_code(429);
            echo json_encode([
                'success' => false,
                'message' => '上传次数过多，请稍后再试（每小时最多 ' . $config['upload_security']['max_upload_per_hour'] . ' 次）'
            ]);
            exit;
        }
        
        // 检查每日限制
        if ($config['upload_security']['max_upload_per_day'] ?? false) {
            $dailyLimit = $rateLimiter->checkLimit(
                'upload_daily:' . $uploadIp,
                $config['upload_security']['max_upload_per_day'],
                86400
            );
            
            if (!$dailyLimit['allowed']) {
                if (ob_get_level()) {
                    ob_clean();
                }
                http_response_code(429);
                echo json_encode([
                    'success' => false,
                    'message' => '今日上传次数已达上限（每天最多 ' . $config['upload_security']['max_upload_per_day'] . ' 次）'
                ]);
                exit;
            }
        }
    }
    
    // 接收参数（支持二进制上传和Base64兼容）
    $inviteCode = trim($_POST['invite_code'] ?? '');
    $imageData = null;
    
    // 优先使用二进制上传（$_FILES）
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $tmpFile = $_FILES['image']['tmp_name'];
        if (file_exists($tmpFile)) {
            $imageData = file_get_contents($tmpFile);
        }
    } 
    // 兼容Base64上传（向后兼容）
    elseif (isset($_POST['image']) && !empty($_POST['image'])) {
        $base64Image = $_POST['image'];
        // 移除data:image/jpeg;base64,前缀
        if (preg_match('/data:image\/(\w+);base64,/', $base64Image, $matches)) {
            $base64Image = preg_replace('/data:image\/\w+;base64,/', '', $base64Image);
        }
        $imageData = base64_decode($base64Image, true);
    }
    
    // 验证拍摄链接码格式（只允许字母数字，长度8位）
    if (empty($inviteCode) || !preg_match('/^[a-zA-Z0-9]{8}$/', $inviteCode)) {
        if (ob_get_level()) {
            ob_clean();
        }
        echo json_encode(['success' => false, 'message' => '拍摄链接码格式错误']);
        exit;
    }
    
    if (empty($imageData)) {
        if (ob_get_level()) {
            ob_clean();
        }
        echo json_encode(['success' => false, 'message' => '图片数据不能为空']);
        exit;
    }
    
    // 验证拍摄链接码
    $inviteModel = new Invite();
    $inviteResult = $inviteModel->validateInvite($inviteCode);
    
    if (!$inviteResult['valid']) {
        if (ob_get_level()) {
            ob_clean();
        }
        echo json_encode(['success' => false, 'message' => $inviteResult['message']]);
        exit;
    }
    
    $invite = $inviteResult['invite'];
    
    // 记录调试信息
    error_log('upload.php: 开始处理图片上传, inviteCode: ' . $inviteCode . ', imageData大小: ' . strlen($imageData) . ' bytes');
    
    // 处理图片（使用二进制数据）
    $processor = new ImageProcessor();
    $result = $processor->processImageBinary($imageData, $inviteCode);
    
    if (!$result['success']) {
        error_log('upload.php: 图片处理失败, 错误信息: ' . ($result['message'] ?? '未知错误'));
        if (ob_get_level()) {
            ob_clean();
        }
        echo json_encode($result);
        exit;
    }
    
    error_log('upload.php: 图片处理成功, 保存路径: ' . $result['original_path']);
    
    // 获取上传IP（兼容CDN和反向代理）
    $uploadIp = Security::getClientIp();
    
    // 获取浏览器信息
    $uploadUa = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // 保存照片信息（包含EXIF数据）
    try {
    $photoModel = new Photo();
    $exifData = $result['exif_data'] ?? [];
        
        error_log('upload.php: 准备保存照片信息, inviteId: ' . $invite['id'] . ', inviteCode: ' . $inviteCode . ', userId: ' . $invite['user_id'] . ', originalPath: ' . $result['original_path']);
        
        $photoId = $photoModel->savePhoto(
        $invite['id'],
        $inviteCode,
        $invite['user_id'],
        $result['original_path'],
        $uploadIp,
        $uploadUa,
        $exifData
    );
        
        error_log('upload.php: 照片信息保存成功, photoId: ' . $photoId . ', inviteId: ' . $invite['id'] . ', userId: ' . $invite['user_id'] . ', inviteCode: ' . $inviteCode);
        
        // 验证照片是否真的保存到数据库
        $db = Database::getInstance();
        $savedPhoto = $db->fetchOne("SELECT id, user_id, invite_code, original_path, deleted_at FROM photos WHERE id = ?", [$photoId]);
        if ($savedPhoto) {
            error_log('upload.php: 验证照片已保存到数据库, photoId: ' . $savedPhoto['id'] . ', userId: ' . $savedPhoto['user_id'] . ', inviteCode: ' . $savedPhoto['invite_code'] . ', deleted_at: ' . ($savedPhoto['deleted_at'] ?? 'NULL'));
        } else {
            error_log('upload.php: 警告！照片未保存到数据库, photoId: ' . $photoId);
        }
    } catch (Exception $e) {
        error_log('upload.php: 保存照片信息失败, 错误: ' . $e->getMessage() . ', 堆栈: ' . $e->getTraceAsString());
        // 即使保存失败，也返回成功（因为文件已保存）
        // 但记录错误以便排查
    }
    
    // 异步更新邀请上传数量（不阻塞响应）
    register_shutdown_function(function() use ($inviteModel, $invite) {
        try {
            $inviteModel->incrementUploadCount($invite['id']);
        } catch (Exception $e) {
            // 静默失败，不影响主流程
        }
    });
    
    // 先返回响应，提升用户体验
    if (ob_get_level()) {
        ob_clean();
    }
    
    // 立即返回成功信息，不等待邮件发送
    echo json_encode([
        'success' => true,
        'message' => '上传成功'
    ]);
    
    // 如果使用FastCGI，立即发送响应给客户端，然后继续执行后续任务
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    } else {
        // 如果不是FastCGI，也需要立即刷新输出
        if (ob_get_level()) {
            ob_end_flush();
        }
        flush();
    }
    
    // 异步发送邮件提醒（在响应返回后执行，不阻塞用户）
    try {
        $db = Database::getInstance();
        $user = $db->fetchOne(
            "SELECT email, email_verified, email_notify_photo, nickname, username 
             FROM users WHERE id = ?",
            [$invite['user_id']]
        );
        
        if ($user && $user['email_verified'] == 1 && $user['email_notify_photo'] == 1 && !empty($user['email'])) {
            // 统计今天通过该邀请码上传的照片数量
            $todayCount = $db->fetchOne(
                "SELECT COUNT(*) as count FROM photos 
                 WHERE user_id = ? AND invite_code = ? AND DATE(upload_time) = CURDATE()",
                [$invite['user_id'], $inviteCode]
            );
            
            $photoCount = $todayCount['count'] ?? 1;
            $displayName = $user['nickname'] ?? $user['username'];
            
            $email = new Email();
            $email->sendPhotoNotification($user['email'], $displayName, $photoCount);
        }
    } catch (Exception $e) {
        // 邮件发送失败不影响上传流程
        error_log('发送照片提醒邮件失败：' . $e->getMessage());
    }
    
} catch (Exception $e) {
    // 清除所有输出
    ob_clean();
    
    error_log('上传错误：' . $e->getMessage());
    error_log('上传错误堆栈：' . $e->getTraceAsString());
    
    // 不向客户端泄露详细错误信息
    echo json_encode([
        'success' => false, 
        'message' => '上传失败，请检查图片格式和大小后重试'
    ]);
} catch (Error $e) {
    // 清除所有输出
    ob_clean();
    
    error_log('上传致命错误：' . $e->getMessage());
    error_log('上传错误堆栈：' . $e->getTraceAsString());
    
    // 不向客户端泄露详细错误信息
    echo json_encode([
        'success' => false, 
        'message' => '上传失败，请稍后重试'
    ]);
}
