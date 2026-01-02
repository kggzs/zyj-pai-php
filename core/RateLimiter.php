<?php
/**
 * API访问频率限制类
 */
class RateLimiter {
    private $cache;
    private $config;
    
    public function __construct() {
        $this->cache = Cache::getInstance();
        $this->config = require __DIR__ . '/../config/config.php';
    }
    
    /**
     * 检查是否超过频率限制
     * @param string $key 限制键（通常是IP地址或用户ID）
     * @param int $maxRequests 最大请求数
     * @param int $windowSeconds 时间窗口（秒）
     * @return array ['allowed' => bool, 'remaining' => int, 'reset_time' => int]
     */
    public function checkLimit($key, $maxRequests = 60, $windowSeconds = 60) {
        $cacheKey = 'rate_limit:' . md5($key);
        $now = time();
        
        // 获取当前计数
        $data = $this->cache->get($cacheKey, [
            'count' => 0,
            'reset_time' => $now + $windowSeconds
        ]);
        
        // 如果时间窗口已过期，重置计数
        if ($now >= $data['reset_time']) {
            $data = [
                'count' => 0,
                'reset_time' => $now + $windowSeconds
            ];
        }
        
        // 增加计数
        $data['count']++;
        
        // 计算剩余请求数
        $remaining = max(0, $maxRequests - $data['count']);
        
        // 保存计数（缓存到重置时间）
        $ttl = $data['reset_time'] - $now;
        if ($ttl > 0) {
            $this->cache->set($cacheKey, $data, $ttl);
        }
        
        return [
            'allowed' => $data['count'] <= $maxRequests,
            'remaining' => $remaining,
            'reset_time' => $data['reset_time'],
            'limit' => $maxRequests
        ];
    }
    
    /**
     * 获取客户端标识（IP地址，兼容CDN和反向代理）
     */
    public function getClientKey() {
        return Security::getClientIp();
    }
    
    /**
     * 检查API频率限制（根据配置）
     * @param string $endpoint API端点
     * @return array
     */
    public function checkApiLimit($endpoint) {
        $key = $this->getClientKey() . ':' . $endpoint;
        
        // 从配置获取限制规则
        $rateLimitConfig = $this->config['rate_limit'] ?? [];
        $defaultLimit = $rateLimitConfig['default'] ?? ['max' => 60, 'window' => 60];
        $endpointLimit = $rateLimitConfig['endpoints'][$endpoint] ?? $defaultLimit;
        
        return $this->checkLimit($key, $endpointLimit['max'], $endpointLimit['window']);
    }
}

