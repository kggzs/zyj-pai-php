<?php
/**
 * 安全工具类
 * 提供CSRF防护、请求签名验证、API密钥认证等功能
 */
class Security {
    private static $csrfTokenName = 'csrf_token';
    private static $apiKeyHeader = 'X-API-Key';
    private static $signatureHeader = 'X-Signature';
    private static $timestampHeader = 'X-Timestamp';
    
    /**
     * 生成CSRF Token
     */
    public static function generateCsrfToken() {
        if (!isset($_SESSION[self::$csrfTokenName])) {
            $_SESSION[self::$csrfTokenName] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::$csrfTokenName];
    }
    
    /**
     * 验证CSRF Token
     * 支持从POST参数、GET参数或HTTP头（X-CSRF-Token）中读取token
     */
    public static function verifyCsrfToken($token = null) {
        if ($token === null) {
            // 优先级：POST参数 > HTTP头 > GET参数
            $token = $_POST['csrf_token'] 
                  ?? $_SERVER['HTTP_X_CSRF_TOKEN'] 
                  ?? $_GET['csrf_token'] 
                  ?? '';
        }
        
        if (empty($token) || !isset($_SESSION[self::$csrfTokenName])) {
            return false;
        }
        
        return hash_equals($_SESSION[self::$csrfTokenName], $token);
    }
    
    /**
     * 验证API密钥
     * @param string $apiKey API密钥
     * @return bool
     */
    public static function verifyApiKey($apiKey) {
        if (empty($apiKey)) {
            return false;
        }
        
        $config = require __DIR__ . '/../config/config.php';
        $validKeys = $config['api']['keys'] ?? [];
        
        return in_array($apiKey, $validKeys);
    }
    
    /**
     * 生成请求签名
     * @param array $params 请求参数
     * @param string $secret 密钥
     * @param int $timestamp 时间戳
     * @return string
     */
    public static function generateSignature($params, $secret, $timestamp) {
        // 排序参数
        ksort($params);
        
        // 构建签名字符串
        $signString = http_build_query($params) . '&timestamp=' . $timestamp . '&secret=' . $secret;
        
        // 生成签名
        return hash_hmac('sha256', $signString, $secret);
    }
    
    /**
     * 验证请求签名
     * @param array $params 请求参数
     * @param string $signature 签名
     * @param int $timestamp 时间戳
     * @param string $secret 密钥
     * @param int $timeWindow 时间窗口（秒），默认300秒（5分钟）
     * @return bool
     */
    public static function verifySignature($params, $signature, $timestamp, $secret, $timeWindow = 300) {
        // 检查时间戳是否在有效窗口内
        $now = time();
        if (abs($now - $timestamp) > $timeWindow) {
            return false;
        }
        
        // 生成期望的签名
        $expectedSignature = self::generateSignature($params, $secret, $timestamp);
        
        // 使用时间安全比较
        return hash_equals($expectedSignature, $signature);
    }
    
    /**
     * 验证文件类型（通过文件头）
     * @param string $fileData 文件数据
     * @param array $allowedTypes 允许的MIME类型
     * @return array ['valid' => bool, 'mime' => string, 'type' => int]
     */
    public static function verifyFileType($fileData, $allowedTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG]) {
        $imageInfo = @getimagesizefromstring($fileData);
        
        if ($imageInfo === false) {
            return ['valid' => false, 'mime' => null, 'type' => null];
        }
        
        $mimeType = $imageInfo['mime'];
        $imageType = $imageInfo[2];
        
        return [
            'valid' => in_array($imageType, $allowedTypes),
            'mime' => $mimeType,
            'type' => $imageType,
            'width' => $imageInfo[0],
            'height' => $imageInfo[1]
        ];
    }
    
    /**
     * 检测恶意文件内容
     * @param string $fileData 文件数据
     * @return array ['safe' => bool, 'threats' => array]
     */
    public static function detectMaliciousContent($fileData) {
        $threats = [];
        
        // 检查文件大小
        if (strlen($fileData) > 10 * 1024 * 1024) { // 10MB
            $threats[] = '文件过大';
        }
        
        // 检查是否包含可执行代码特征
        $dangerousPatterns = [
            '/<\?php/i',
            '/<script/i',
            '/eval\s*\(/i',
            '/base64_decode/i',
            '/exec\s*\(/i',
            '/system\s*\(/i',
            '/shell_exec/i',
            '/passthru/i',
            '/proc_open/i',
            '/popen/i'
        ];
        
        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $fileData)) {
                $threats[] = '检测到可疑代码：' . $pattern;
            }
        }
        
        // 检查文件头是否匹配
        $fileHeader = substr($fileData, 0, 12);
        $validImageHeaders = [
            "\xFF\xD8\xFF", // JPEG
            "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A", // PNG
        ];
        
        $isValidImage = false;
        foreach ($validImageHeaders as $header) {
            if (strpos($fileHeader, $header) === 0) {
                $isValidImage = true;
                break;
            }
        }
        
        if (!$isValidImage) {
            $threats[] = '文件头不匹配，可能不是有效的图片文件';
        }
        
        return [
            'safe' => empty($threats),
            'threats' => $threats
        ];
    }
    
    /**
     * 获取客户端IP地址（兼容CDN和反向代理）
     * 优先级：CDN专用头 > X-Forwarded-For > X-Real-IP > REMOTE_ADDR
     * 
     * 支持的CDN头部：
     * - Cloudflare: CF-Connecting-IP
     * - 阿里云CDN: Ali-CDN-Real-IP, X-Forwarded-For
     * - 腾讯云CDN: X-Forwarded-For, X-Real-IP
     * - 百度云CDN: X-Forwarded-For, X-Real-IP
     * - 其他CDN: X-Forwarded-For, X-Real-IP, True-Client-IP, X-Client-IP
     */
    public static function getClientIp() {
        // 读取配置
        $config = require __DIR__ . '/../config/config.php';
        $cdnConfig = $config['cdn'] ?? [];
        $ipHeaders = $cdnConfig['ip_headers'] ?? [
            'HTTP_CF_CONNECTING_IP',           // Cloudflare
            'HTTP_ALI_CDN_REAL_IP',            // 阿里云CDN
            'HTTP_TRUE_CLIENT_IP',              // Cloudflare Enterprise, Akamai
            'HTTP_X_CLIENT_IP',                 // 部分CDN
            'HTTP_X_FORWARDED_FOR',             // 通用CDN（可能包含多个IP，取第一个）
            'HTTP_X_REAL_IP',                   // Nginx反向代理
            'HTTP_X_FORWARDED',                 // 较少使用
            'HTTP_CLIENT_IP'                    // 较少使用
        ];
        $allowPrivateIp = $cdnConfig['allow_private_ip'] ?? true;
        
        // 辅助函数：清理和验证IP
        $cleanAndValidateIp = function($ip) use ($allowPrivateIp) {
            if (empty($ip)) {
                return null;
            }
            
            $ip = trim((string)$ip);
            if ($ip === '') {
                return null;
            }

            if (preg_match('/^\[([^\]]+)\](?::\d+)?$/', $ip, $m)) {
                $ip = $m[1];
            } elseif (preg_match('/^(\d{1,3}(?:\.\d{1,3}){3}):\d+$/', $ip, $m)) {
                $ip = $m[1];
            }

            if (strpos($ip, '%') !== false) {
                $ip = explode('%', $ip, 2)[0];
            }
            
            // 处理IPv6映射的IPv4地址
            if (strpos($ip, '::ffff:') === 0) {
                $ip = substr($ip, 7);
            }
            
            // 验证IP格式（允许私有IP）
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                // 如果不允许私有IP，检查是否为私有IP
                if (!$allowPrivateIp && self::isPrivateIp($ip)) {
                    return null;
                }
                return $ip;
            }
            
            return null;
        };
        
        // 1. 优先检查CDN专用头（按配置的优先级排序）
        foreach ($ipHeaders as $header) {
            if (!empty($_SERVER[$header])) {
                $value = $_SERVER[$header];
                
                // X-Forwarded-For 可能包含多个IP（用逗号分隔），需要遍历找到第一个有效的公网IP
                if ($header === 'HTTP_X_FORWARDED_FOR' || $header === 'HTTP_X_FORWARDED') {
                    $ips = explode(',', $value);
                    foreach ($ips as $testIp) {
                        $testIp = trim($testIp);
                        $ip = $cleanAndValidateIp($testIp);
                        if ($ip !== null) {
                            // 如果允许私有IP，直接返回；否则继续查找公网IP
                            if ($allowPrivateIp || !self::isPrivateIp($ip)) {
                                return $ip;
                            }
                        }
                    }
                } else {
                    $ip = $cleanAndValidateIp($value);
                    if ($ip !== null) {
                        // 如果允许私有IP，直接返回；否则继续查找公网IP
                        if ($allowPrivateIp || !self::isPrivateIp($ip)) {
                            return $ip;
                        }
                    }
                }
            }
        }
        
        // 2. 最后使用 REMOTE_ADDR（最可靠，但可能是代理IP）
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $ip = $cleanAndValidateIp($ip);
        
        return $ip !== null ? $ip : '0.0.0.0';
    }
    
    /**
     * 判断是否为私有IP地址
     */
    private static function isPrivateIp($ip) {
        if (empty($ip)) {
            return false;
        }
        
        // IPv4私有地址范围
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            if (count($parts) === 4) {
                $first = (int)$parts[0];
                $second = (int)$parts[1];
                
                // 10.0.0.0/8
                if ($first === 10) {
                    return true;
                }
                
                // 172.16.0.0/12
                if ($first === 172 && $second >= 16 && $second <= 31) {
                    return true;
                }
                
                // 192.168.0.0/16
                if ($first === 192 && $second === 168) {
                    return true;
                }
                
                // 127.0.0.0/8 (localhost)
                if ($first === 127) {
                    return true;
                }
                
                // 169.254.0.0/16 (link-local)
                if ($first === 169 && $second === 254) {
                    return true;
                }
            }
        }
        
        // IPv6私有地址范围
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            // fc00::/7 (Unique Local Address)
            // fe80::/10 (Link-Local Address)
            // ::1 (localhost)
            if (strpos($ip, 'fc') === 0 || strpos($ip, 'fe8') === 0 || strpos($ip, 'fe9') === 0 || 
                strpos($ip, 'fea') === 0 || strpos($ip, 'feb') === 0 || $ip === '::1') {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 设置安全响应头
     * 包括CSP、X-Content-Type-Options、X-Frame-Options、X-XSS-Protection、HSTS等
     * @param array $options 配置选项，可覆盖默认配置
     */
    public static function setSecurityHeaders($options = []) {
        // 如果响应头已发送，则跳过
        if (headers_sent()) {
            return;
        }
        
        // 读取配置
        $config = require __DIR__ . '/../config/config.php';
        $securityConfig = $config['security_headers'] ?? [];
        
        // 合并配置
        $options = array_merge([
            'enabled' => $securityConfig['enabled'] ?? true,
            'csp' => $securityConfig['csp'] ?? true,
            'x_content_type_options' => $securityConfig['x_content_type_options'] ?? true,
            'x_frame_options' => $securityConfig['x_frame_options'] ?? true,
            'x_xss_protection' => $securityConfig['x_xss_protection'] ?? true,
            'strict_transport_security' => $securityConfig['strict_transport_security'] ?? true,
            'csp_policy' => $securityConfig['csp_policy'] ?? "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob: https:; font-src 'self' data:; connect-src 'self';",
            'hsts_max_age' => $securityConfig['hsts_max_age'] ?? 31536000, // 1年
            'hsts_include_subdomains' => $securityConfig['hsts_include_subdomains'] ?? true,
        ], $options);
        
        // 如果安全响应头被禁用，则跳过
        if (!$options['enabled']) {
            return;
        }
        
        // 1. 内容安全策略（CSP）
        if ($options['csp']) {
            header("Content-Security-Policy: " . $options['csp_policy']);
        }
        
        // 2. X-Content-Type-Options: nosniff
        // 防止浏览器MIME类型嗅探，强制使用声明的Content-Type
        if ($options['x_content_type_options']) {
            header("X-Content-Type-Options: nosniff");
        }
        
        // 3. X-Frame-Options: DENY
        // 防止点击劫持攻击，禁止页面在iframe中加载
        if ($options['x_frame_options']) {
            $frameOptions = $securityConfig['x_frame_options_value'] ?? 'DENY';
            header("X-Frame-Options: " . $frameOptions);
        }
        
        // 4. X-XSS-Protection
        // 启用浏览器的XSS过滤器（虽然现代浏览器已内置，但保留以兼容旧浏览器）
        if ($options['x_xss_protection']) {
            header("X-XSS-Protection: 1; mode=block");
        }
        
        // 5. Strict-Transport-Security (HSTS)
        // 仅在HTTPS环境下设置，强制使用HTTPS连接
        if ($options['strict_transport_security']) {
            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
                    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
                    || (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on')
                    || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
            
            if ($isHttps) {
                $hstsValue = "max-age=" . $options['hsts_max_age'];
                if ($options['hsts_include_subdomains']) {
                    $hstsValue .= "; includeSubDomains";
                }
                header("Strict-Transport-Security: " . $hstsValue);
            }
        }
        
        // 6. Referrer-Policy（可选，但推荐）
        $referrerPolicy = $securityConfig['referrer_policy'] ?? 'strict-origin-when-cross-origin';
        if ($referrerPolicy) {
            header("Referrer-Policy: " . $referrerPolicy);
        }
        
        // 7. Permissions-Policy（可选，但推荐）
        // 限制浏览器功能的使用，增强隐私和安全
        $permissionsPolicy = $securityConfig['permissions_policy'] ?? "geolocation=(), microphone=(), camera=()";
        if ($permissionsPolicy) {
            header("Permissions-Policy: " . $permissionsPolicy);
        }
    }
    
    /**
     * 验证密码强度
     * @param string $password 待验证的密码
     * @return array ['valid' => bool, 'message' => string] 验证结果
     */
    public static function validatePasswordStrength($password) {
        // 读取配置
        $config = require __DIR__ . '/../config/config.php';
        $passwordConfig = $config['password_strength'] ?? [];
        
        // 默认配置
        $minLength = $passwordConfig['min_length'] ?? 8;
        $requireLetter = $passwordConfig['require_letter'] ?? true;
        $requireNumber = $passwordConfig['require_number'] ?? true;
        $requireSpecialChar = $passwordConfig['require_special_char'] ?? false;
        $maxLength = $passwordConfig['max_length'] ?? 128;
        
        // 检查密码是否为空
        if (empty($password)) {
            return [
                'valid' => false,
                'message' => '密码不能为空'
            ];
        }
        
        // 检查最小长度
        if (mb_strlen($password) < $minLength) {
            return [
                'valid' => false,
                'message' => "密码长度至少为{$minLength}个字符"
            ];
        }
        
        // 检查最大长度
        if (mb_strlen($password) > $maxLength) {
            return [
                'valid' => false,
                'message' => "密码长度不能超过{$maxLength}个字符"
            ];
        }
        
        // 检查是否包含字母
        if ($requireLetter && !preg_match('/[a-zA-Z]/', $password)) {
            return [
                'valid' => false,
                'message' => '密码必须包含至少一个字母'
            ];
        }
        
        // 检查是否包含数字
        if ($requireNumber && !preg_match('/[0-9]/', $password)) {
            return [
                'valid' => false,
                'message' => '密码必须包含至少一个数字'
            ];
        }
        
        // 检查是否包含特殊字符
        if ($requireSpecialChar && !preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) {
            return [
                'valid' => false,
                'message' => '密码必须包含至少一个特殊字符（如!@#$%等）'
            ];
        }
        
        // 检查常见弱密码
        $commonWeakPasswords = [
            'password', '123456', '12345678', 'qwerty', 'abc123',
            'password123', 'admin', '123456789', '1234567890',
            'letmein', 'welcome', 'monkey', '1234567', 'sunshine',
            'master', '123123', 'shadow', 'ashley', 'bailey'
        ];
        
        if (in_array(strtolower($password), $commonWeakPasswords)) {
            return [
                'valid' => false,
                'message' => '密码过于简单，请使用更复杂的密码'
            ];
        }
        
        // 检查是否包含用户名（如果提供）
        if (isset($passwordConfig['check_username']) && $passwordConfig['check_username']) {
            $username = $passwordConfig['username'] ?? '';
            if (!empty($username) && stripos($password, $username) !== false) {
                return [
                    'valid' => false,
                    'message' => '密码不能包含用户名'
                ];
            }
        }
        
        return [
            'valid' => true,
            'message' => '密码强度符合要求'
        ];
    }
    
    /**
     * 计算密码强度等级
     * @param string $password 密码
     * @return array ['level' => int, 'text' => string] 强度等级（0-4，0最弱，4最强）
     */
    public static function calculatePasswordStrength($password) {
        $score = 0;
        
        // 长度评分
        $length = mb_strlen($password);
        if ($length >= 8) $score++;
        if ($length >= 12) $score++;
        if ($length >= 16) $score++;
        
        // 包含小写字母
        if (preg_match('/[a-z]/', $password)) $score++;
        
        // 包含大写字母
        if (preg_match('/[A-Z]/', $password)) $score++;
        
        // 包含数字
        if (preg_match('/[0-9]/', $password)) $score++;
        
        // 包含特殊字符
        if (preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) $score++;
        
        // 字符种类多样性
        $charTypes = 0;
        if (preg_match('/[a-z]/', $password)) $charTypes++;
        if (preg_match('/[A-Z]/', $password)) $charTypes++;
        if (preg_match('/[0-9]/', $password)) $charTypes++;
        if (preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) $charTypes++;
        
        if ($charTypes >= 3) $score++;
        if ($charTypes >= 4) $score++;
        
        // 限制最高分数为4
        $level = min($score, 4);
        
        $levels = [
            0 => '非常弱',
            1 => '弱',
            2 => '中等',
            3 => '强',
            4 => '非常强'
        ];
        
        return [
            'level' => $level,
            'text' => $levels[$level] ?? '未知'
        ];
    }
    
    /**
     * 获取密码要求说明
     * @return array 密码要求数组
     */
    public static function getPasswordRequirements() {
        $config = require __DIR__ . '/../config/config.php';
        $passwordConfig = $config['password_strength'] ?? [];
        
        $minLength = $passwordConfig['min_length'] ?? 8;
        $maxLength = $passwordConfig['max_length'] ?? 64;
        $requireLetter = $passwordConfig['require_letter'] ?? true;
        $requireNumber = $passwordConfig['require_number'] ?? true;
        $requireSpecialChar = $passwordConfig['require_special_char'] ?? false;
        
        $requirements = [];
        $requirements[] = "长度：{$minLength}-{$maxLength}个字符";
        if ($requireLetter) {
            $requirements[] = "必须包含字母";
        }
        if ($requireNumber) {
            $requirements[] = "必须包含数字";
        }
        if ($requireSpecialChar) {
            $requirements[] = "必须包含特殊字符";
        }
        
        return $requirements;
    }
    
    /**
     * 获取密码要求详情（包括每个要求的满足状态）
     * @param string $password 密码（可选，用于检查满足状态）
     * @return array 密码要求详情数组，每个元素包含 ['text' => string, 'met' => bool]
     */
    public static function getPasswordRequirementsDetail($password = '') {
        $config = require __DIR__ . '/../config/config.php';
        $passwordConfig = $config['password_strength'] ?? [];
        
        $minLength = $passwordConfig['min_length'] ?? 8;
        $maxLength = $passwordConfig['max_length'] ?? 64;
        $requireLetter = $passwordConfig['require_letter'] ?? true;
        $requireNumber = $passwordConfig['require_number'] ?? true;
        $requireSpecialChar = $passwordConfig['require_special_char'] ?? false;
        
        $requirements = [];
        
        // 长度要求
        $length = mb_strlen($password);
        $lengthMet = $length >= $minLength && $length <= $maxLength;
        $requirements[] = [
            'text' => "长度：{$minLength}-{$maxLength}个字符",
            'met' => $lengthMet,
            'type' => 'length'
        ];
        
        // 字母要求
        if ($requireLetter) {
            $hasLetter = !empty($password) && preg_match('/[a-zA-Z]/', $password);
            $requirements[] = [
                'text' => '必须包含字母',
                'met' => $hasLetter,
                'type' => 'letter'
            ];
        }
        
        // 数字要求
        if ($requireNumber) {
            $hasNumber = !empty($password) && preg_match('/[0-9]/', $password);
            $requirements[] = [
                'text' => '必须包含数字',
                'met' => $hasNumber,
                'type' => 'number'
            ];
        }
        
        // 特殊字符要求
        if ($requireSpecialChar) {
            $hasSpecialChar = !empty($password) && preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password);
            $requirements[] = [
                'text' => '必须包含特殊字符（如!@#$%等）',
                'met' => $hasSpecialChar,
                'type' => 'special'
            ];
        }
        
        return $requirements;
    }
}
