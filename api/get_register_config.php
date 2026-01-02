<?php
/**
 * 获取注册配置API（公开）
 */
require_once __DIR__ . '/../core/autoload.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $requireVerification = Helper::getSystemConfig('register_require_email_verification') == '1';
    $videoMaxDuration = Helper::getSystemConfig('video_max_duration') ?: '60';
    
    // 如果开启了强制邮箱验证，邮箱自动变为必填
    // 为了兼容旧代码，仍然返回require_email，但它的值等于require_email_verification
    echo json_encode([
        'success' => true,
        'data' => [
            'require_email' => $requireVerification, // 开启验证时自动必填
            'require_email_verification' => $requireVerification,
            'video_max_duration' => $videoMaxDuration
        ]
    ]);
} catch (Exception $e) {
    error_log('获取注册配置错误：' . $e->getMessage());
    echo json_encode([
        'success' => true,
        'data' => [
            'require_email' => false,
            'require_email_verification' => false,
            'video_max_duration' => '60'
        ]
    ]);
}

