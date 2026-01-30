<?php
/**
 * 系统配置文件
 */
return [
    // 系统基础配置
    // 如果设置为空或localhost，将自动从HTTP请求中获取
    'site_url' => '',  // 留空则自动检测，或设置为固定URL如 'http://127.0.0.1:801'
    'timezone' => 'Asia/Shanghai',
    
    // 文件上传配置
    'upload' => [
        'path' => __DIR__ . '/../uploads/',
        'original_path' => __DIR__ . '/../uploads/original/',
        'video_path' => __DIR__ . '/../uploads/video/',
        'max_size' => 1 * 1024 * 1024, // 1MB（图片，已优化压缩）
        'video_max_size' => 20 * 1024 * 1024, // 20MB（录像，已优化码率）
        'allowed_types' => ['image/jpeg', 'image/png', 'image/jpg'],
        'allowed_video_types' => ['video/webm', 'video/mp4'],
    ],
    
    // 录像配置
    'video' => [
        'max_duration' => 15, // 最大录像时长（秒），默认15秒
    ],
    
    // Session配置
    'session' => [
        'name' => 'PHOTO_UPLOAD_SESSION',
        'lifetime' => 7200, // 2小时
    ],
    
    // 图片处理配置
    'image' => [
        'use_webp' => false, // 是否使用WebP格式（需要PHP GD库支持）
        'jpeg_quality' => 75, // JPEG压缩质量（1-100，已优化为75以减小文件体积）
        'webp_quality' => 75, // WebP压缩质量（1-100）
        'max_width' => 1280, // 最大宽度（已优化为1280px以减小文件体积）
        'max_height' => 720, // 最大高度（已优化为720px以减小文件体积）
        'cache_enabled' => true, // 是否启用图片缓存
        'cache_duration' => 86400, // 缓存时长（秒，默认1天）
    ],
    
    // API安全配置
    'api' => [
        'keys' => [], // API密钥列表（用于API密钥认证）
        'signature_secret' => '', // 请求签名密钥（留空则禁用签名验证）
        'require_signature' => false, // 是否要求请求签名
        'signature_time_window' => 300, // 签名时间窗口（秒）
    ],
    
    // 频率限制配置
    'rate_limit' => [
        'enabled' => true, // 是否启用频率限制
        'default' => [
            'max' => 60, // 默认最大请求数
            'window' => 60 // 时间窗口（秒）
        ],
        'endpoints' => [
            'api/upload.php' => ['max' => 10, 'window' => 60], // 上传接口：每分钟10次
            'api/login.php' => ['max' => 5, 'window' => 300], // 登录接口：5分钟内5次
            'api/register.php' => ['max' => 3, 'window' => 3600], // 注册接口：1小时内3次
        ]
    ],
    
    // 文件上传安全配置
    'upload_security' => [
        'strict_type_check' => true, // 严格文件类型检查
        'content_scan' => true, // 内容安全检查
        'malware_detection' => true, // 恶意文件检测
        'max_upload_per_hour' => 50, // 每小时最大上传次数（按IP）
        'max_upload_per_day' => 200, // 每天最大上传次数（按IP）
    ],
    
    // CDN和反向代理IP获取配置
    'cdn' => [
        // CDN IP头部优先级（按顺序检查）
        // 如果您的CDN使用特殊的头部名称，可以在这里配置
        'ip_headers' => [
            'HTTP_CF_CONNECTING_IP',           // Cloudflare
            'HTTP_ALI_CDN_REAL_IP',            // 阿里云CDN
            'HTTP_TRUE_CLIENT_IP',              // Cloudflare Enterprise, Akamai
            'HTTP_X_CLIENT_IP',                 // 部分CDN
            'HTTP_X_FORWARDED_FOR',             // 通用CDN（可能包含多个IP，取第一个）
            'HTTP_X_REAL_IP',                   // Nginx反向代理
            'HTTP_X_FORWARDED',                 // 较少使用
            'HTTP_CLIENT_IP'                    // 较少使用
        ],
        // 是否允许私有IP（内网IP），如果为false，则只返回公网IP
        'allow_private_ip' => true,
        // 是否允许保留IP（如127.0.0.1），如果为false，则过滤这些IP
        'allow_reserved_ip' => true
    ],
    
    // 日志配置
    'logging' => [
        'enabled' => true, // 是否启用日志
        'level' => 'DEBUG', // 日志级别：DEBUG, INFO, WARNING, ERROR（生产环境建议使用INFO）
    ],
    
    // 安全响应头配置
    'security_headers' => [
        'enabled' => true, // 是否启用安全响应头
        'csp' => true, // 内容安全策略（CSP）
        'csp_policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob: https:; font-src 'self' data:; connect-src 'self';", // CSP策略（可根据实际需求调整）
        'x_content_type_options' => true, // X-Content-Type-Options: nosniff
        'x_frame_options' => true, // X-Frame-Options
        'x_frame_options_value' => 'DENY', // X-Frame-Options值：DENY（禁止）或 SAMEORIGIN（同源允许）
        'x_xss_protection' => true, // X-XSS-Protection
        'strict_transport_security' => true, // Strict-Transport-Security (HSTS)
        'hsts_max_age' => 31536000, // HSTS最大年龄（秒），默认1年
        'hsts_include_subdomains' => true, // HSTS是否包含子域名
        'referrer_policy' => 'strict-origin-when-cross-origin', // Referrer-Policy
        'permissions_policy' => "geolocation=(), microphone=(), camera=()", // Permissions-Policy
    ],
    
    // 密码强度配置
    'password_strength' => [
        'min_length' => 8, // 最小长度（至少8位）
        'max_length' => 64, // 最大长度
        'require_letter' => true, // 必须包含字母
        'require_number' => true, // 必须包含数字
        'require_special_char' => false, // 是否必须包含特殊字符（可选，默认false）
        'check_username' => false, // 是否检查密码中不能包含用户名（需要在使用时传入username）
    ]
];
