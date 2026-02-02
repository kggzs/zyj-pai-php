<?php
/**
 * 录像上传API
 */
// 禁用错误输出到响应中
ini_set('display_errors', 0);
error_reporting(E_ALL);

// 允许脚本在用户断开连接后继续执行
ignore_user_abort(true);
// 取消脚本执行时间限制
set_time_limit(0);

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
        $limitCheck = $rateLimiter->checkApiLimit('api/upload_video.php');
        
        if (!$limitCheck['allowed']) {
            if (ob_get_level()) {
                ob_clean();
            }
            http_response_code(429);
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
    }
    
    // 接收参数（优先使用二进制上传）
    $inviteCode = trim($_POST['invite_code'] ?? '');
    $videoData = null;
    
    // 优先使用二进制上传（$_FILES）
    if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
        $tmpFile = $_FILES['video']['tmp_name'];
        if (file_exists($tmpFile)) {
            $videoData = file_get_contents($tmpFile);
            Logger::info('接收视频数据', ['size' => strlen($videoData), 'tmp_file' => $tmpFile]);
        } else {
            Logger::error('临时文件不存在', ['tmp_file' => $tmpFile]);
        }
    }
    // 兼容Base64上传（向后兼容）
    elseif (isset($_POST['video']) && !empty($_POST['video'])) {
        $base64Video = $_POST['video'];
        // 移除data:video/webm;base64,前缀
        if (preg_match('/data:video\/(\w+);base64,/', $base64Video, $matches)) {
            $base64Video = preg_replace('/data:video\/\w+;base64,/', '', $base64Video);
        }
        // 清理Base64字符串
        $base64Video = trim($base64Video);
        $base64Video = preg_replace('/\s+/', '', $base64Video);
        $videoData = base64_decode($base64Video, true);
    }
    
    // 验证拍摄链接码格式
    if (empty($inviteCode) || !preg_match('/^[a-zA-Z0-9]{8}$/', $inviteCode)) {
        if (ob_get_level()) {
            ob_clean();
        }
        echo json_encode(['success' => false, 'message' => '拍摄链接码格式错误']);
        exit;
    }
    
    if (empty($videoData)) {
        if (ob_get_level()) {
            ob_clean();
        }
        echo json_encode(['success' => false, 'message' => '录像数据不能为空']);
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
    
    // 处理录像（使用二进制数据）
    $processor = new ImageProcessor();
    Logger::info('开始处理视频', ['invite_code' => $inviteCode]);
    $result = $processor->processVideoBinary($videoData, $inviteCode);
    
    if (!$result['success']) {
        Logger::error('视频处理失败', ['message' => $result['message']]);
        if (ob_get_level()) {
            ob_clean();
        }
        echo json_encode($result);
        exit;
    }
    
    Logger::info('视频处理成功', ['path' => $result['original_path'], 'duration' => $result['duration']]);
    
    // 获取上传IP（兼容CDN和反向代理）
    $uploadIp = Security::getClientIp();
    
    // 获取浏览器信息
    $uploadUa = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // 保存录像信息
    $photoModel = new Photo();
    Logger::info('开始保存视频记录到数据库', ['invite_code' => $inviteCode]);
    $photoId = $photoModel->saveVideo(
        $invite['id'],
        $inviteCode,
        $invite['user_id'],
        $result['original_path'],
        $result['duration'],
        $uploadIp,
        $uploadUa
    );
    
    Logger::info('视频记录保存成功', ['photo_id' => $photoId, 'invite_code' => $inviteCode]);
    
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
        Logger::error('发送录像提醒邮件失败：' . $e->getMessage());
    }
    
} catch (Exception $e) {
    ob_clean();
    Logger::error('上传错误：' . $e->getMessage());
    Logger::error('上传错误堆栈：' . $e->getTraceAsString());
    echo json_encode([
        'success' => false, 
        'message' => '上传失败，请检查录像格式和大小后重试'
    ]);
} catch (Error $e) {
    ob_clean();
    Logger::error('上传致命错误：' . $e->getMessage());
    Logger::error('上传错误堆栈：' . $e->getTraceAsString());
    echo json_encode([
        'success' => false, 
        'message' => '上传失败，请稍后重试'
    ]);
}

