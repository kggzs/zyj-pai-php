<?php
/**
 * API中间件类
 * 提供统一的API安全验证功能
 */
class ApiMiddleware {
    private $config;
    private $rateLimiter;
    
    public function __construct() {
        $this->config = require __DIR__ . '/../config/config.php';
        $this->rateLimiter = new RateLimiter();
    }
    
    /**
     * 检查API频率限制
     * @param string $endpoint API端点
     * @return array|null 如果超过限制返回错误信息，否则返回null
     */
    public function checkRateLimit($endpoint) {
        if (!($this->config['rate_limit']['enabled'] ?? true)) {
            return null;
        }
        
        $limitCheck = $this->rateLimiter->checkApiLimit($endpoint);
        
        if (!$limitCheck['allowed']) {
            http_response_code(429);
            header('X-RateLimit-Limit: ' . $limitCheck['limit']);
            header('X-RateLimit-Remaining: ' . $limitCheck['remaining']);
            header('X-RateLimit-Reset: ' . $limitCheck['reset_time']);
            
            return [
                'success' => false,
                'message' => '请求过于频繁，请稍后再试',
                'retry_after' => $limitCheck['reset_time'] - time()
            ];
        }
        
        // 设置响应头
        header('X-RateLimit-Limit: ' . $limitCheck['limit']);
        header('X-RateLimit-Remaining: ' . $limitCheck['remaining']);
        header('X-RateLimit-Reset: ' . $limitCheck['reset_time']);
        
        return null;
    }
    
    /**
     * 验证API密钥
     * @return array|null 如果验证失败返回错误信息，否则返回null
     */
    public function verifyApiKey() {
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? $_POST['api_key'] ?? '';
        
        if (empty($apiKey)) {
            return null; // 如果没有提供密钥，跳过验证（可选）
        }
        
        if (!Security::verifyApiKey($apiKey)) {
            http_response_code(401);
            return [
                'success' => false,
                'message' => '无效的API密钥'
            ];
        }
        
        return null;
    }
    
    /**
     * 验证请求签名
     * @return array|null 如果验证失败返回错误信息，否则返回null
     */
    public function verifySignature() {
        $config = $this->config['api'] ?? [];
        
        if (!($config['require_signature'] ?? false)) {
            return null; // 未启用签名验证
        }
        
        $secret = $config['signature_secret'] ?? '';
        if (empty($secret)) {
            return null; // 未配置密钥
        }
        
        $signature = $_SERVER['HTTP_X_SIGNATURE'] ?? $_GET['signature'] ?? $_POST['signature'] ?? '';
        $timestamp = (int)($_SERVER['HTTP_X_TIMESTAMP'] ?? $_GET['timestamp'] ?? $_POST['timestamp'] ?? 0);
        
        if (empty($signature) || $timestamp === 0) {
            http_response_code(400);
            return [
                'success' => false,
                'message' => '缺少签名或时间戳'
            ];
        }
        
        // 获取请求参数（排除签名相关参数）
        $params = $_POST;
        unset($params['signature'], $params['timestamp']);
        
        if (!Security::verifySignature($params, $signature, $timestamp, $secret, $config['signature_time_window'] ?? 300)) {
            http_response_code(401);
            return [
                'success' => false,
                'message' => '签名验证失败'
            ];
        }
        
        return null;
    }
    
    /**
     * 验证CSRF Token（仅用于需要CSRF保护的API）
     * @return array|null 如果验证失败返回错误信息，否则返回null
     */
    public function verifyCsrfToken() {
        $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
        
        if (!Security::verifyCsrfToken($token)) {
            http_response_code(403);
            return [
                'success' => false,
                'message' => 'CSRF Token验证失败'
            ];
        }
        
        return null;
    }
    
    /**
     * 执行所有安全检查
     * @param string $endpoint API端点
     * @param array $options 选项 ['rate_limit' => bool, 'api_key' => bool, 'signature' => bool, 'csrf' => bool]
     * @return array|null 如果验证失败返回错误信息，否则返回null
     */
    public function checkSecurity($endpoint, $options = []) {
        $defaultOptions = [
            'rate_limit' => true,
            'api_key' => false,
            'signature' => false,
            'csrf' => false
        ];
        
        $options = array_merge($defaultOptions, $options);
        
        // 频率限制
        if ($options['rate_limit']) {
            $result = $this->checkRateLimit($endpoint);
            if ($result) {
                return $result;
            }
        }
        
        // API密钥验证
        if ($options['api_key']) {
            $result = $this->verifyApiKey();
            if ($result) {
                return $result;
            }
        }
        
        // 签名验证
        if ($options['signature']) {
            $result = $this->verifySignature();
            if ($result) {
                return $result;
            }
        }
        
        // CSRF验证
        if ($options['csrf']) {
            $result = $this->verifyCsrfToken();
            if ($result) {
                return $result;
            }
        }
        
        return null;
    }
}

