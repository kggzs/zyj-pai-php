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
     */
    public static function verifyCsrfToken($token = null) {
        if ($token === null) {
            $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
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
            
            // 移除端口号（如果有）
            if (strpos($ip, ':') !== false) {
                $ipParts = explode(':', $ip);
                $ip = trim($ipParts[0]);
            } else {
                $ip = trim($ip);
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
}

