<?php
/**
 * 缓存管理类
 * 支持文件缓存和内存缓存
 */
class Cache {
    private static $instance = null;
    private $cacheDir;
    private $memoryCache = [];
    private $config;
    
    private function __construct() {
        $this->config = require __DIR__ . '/../config/config.php';
        $this->cacheDir = __DIR__ . '/../cache/';
        
        // 确保缓存目录存在
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * 获取缓存
     * @param string $key 缓存键
     * @param mixed $default 默认值（如果缓存不存在）
     * @return mixed
     */
    public function get($key, $default = null) {
        // 先检查内存缓存
        if (isset($this->memoryCache[$key])) {
            $cached = $this->memoryCache[$key];
            if ($cached['expire'] > time()) {
                return $cached['data'];
            } else {
                unset($this->memoryCache[$key]);
            }
        }
        
        // 检查文件缓存
        $filePath = $this->getCacheFilePath($key);
        if (file_exists($filePath)) {
            $data = @unserialize(file_get_contents($filePath));
            if ($data !== false && isset($data['expire']) && isset($data['data'])) {
                if ($data['expire'] > time()) {
                    // 同时更新内存缓存
                    $this->memoryCache[$key] = $data;
                    return $data['data'];
                } else {
                    // 缓存已过期，删除文件
                    @unlink($filePath);
                }
            }
        }
        
        return $default;
    }
    
    /**
     * 设置缓存
     * @param string $key 缓存键
     * @param mixed $value 缓存值
     * @param int $ttl 缓存时长（秒），默认3600秒（1小时）
     * @return bool
     */
    public function set($key, $value, $ttl = 3600) {
        $expire = time() + $ttl;
        $data = [
            'expire' => $expire,
            'data' => $value
        ];
        
        // 保存到内存缓存
        $this->memoryCache[$key] = $data;
        
        // 保存到文件缓存
        $filePath = $this->getCacheFilePath($key);
        $cacheDir = dirname($filePath);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        
        return file_put_contents($filePath, serialize($data), LOCK_EX) !== false;
    }
    
    /**
     * 删除缓存
     * @param string $key 缓存键
     * @return bool
     */
    public function delete($key) {
        // 删除内存缓存
        unset($this->memoryCache[$key]);
        
        // 删除文件缓存
        $filePath = $this->getCacheFilePath($key);
        if (file_exists($filePath)) {
            return @unlink($filePath);
        }
        
        return true;
    }
    
    /**
     * 清空所有缓存
     * @param string $prefix 缓存键前缀（可选，用于清空特定类型的缓存）
     * @return bool
     */
    public function clear($prefix = '') {
        // 清空内存缓存
        if (empty($prefix)) {
            $this->memoryCache = [];
        } else {
            foreach (array_keys($this->memoryCache) as $key) {
                if (strpos($key, $prefix) === 0) {
                    unset($this->memoryCache[$key]);
                }
            }
        }
        
        // 清空文件缓存
        $cacheDir = $this->cacheDir;
        if (!empty($prefix)) {
            $cacheDir .= $prefix . '/';
        }
        
        if (is_dir($cacheDir)) {
            $this->deleteDirectory($cacheDir);
        }
        
        return true;
    }
    
    /**
     * 检查缓存是否存在且未过期
     * @param string $key 缓存键
     * @return bool
     */
    public function has($key) {
        // 检查内存缓存
        if (isset($this->memoryCache[$key])) {
            return $this->memoryCache[$key]['expire'] > time();
        }
        
        // 检查文件缓存
        $filePath = $this->getCacheFilePath($key);
        if (file_exists($filePath)) {
            $data = @unserialize(file_get_contents($filePath));
            if ($data !== false && isset($data['expire'])) {
                if ($data['expire'] > time()) {
                    // 同时更新内存缓存
                    $this->memoryCache[$key] = $data;
                    return true;
                } else {
                    @unlink($filePath);
                }
            }
        }
        
        return false;
    }
    
    /**
     * 获取缓存文件路径
     * @param string $key 缓存键
     * @return string
     */
    private function getCacheFilePath($key) {
        // 将键转换为安全的文件名
        $safeKey = md5($key);
        $subDir = substr($safeKey, 0, 2);
        return $this->cacheDir . $subDir . '/' . $safeKey . '.cache';
    }
    
    /**
     * 删除目录（递归）
     * @param string $dir 目录路径
     * @return bool
     */
    private function deleteDirectory($dir) {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }
        
        return @rmdir($dir);
    }
    
    /**
     * 获取或设置缓存（如果不存在则调用回调函数生成）
     * @param string $key 缓存键
     * @param callable $callback 回调函数（用于生成缓存值）
     * @param int $ttl 缓存时长（秒）
     * @return mixed
     */
    public function remember($key, $callback, $ttl = 3600) {
        if ($this->has($key)) {
            return $this->get($key);
        }
        
        $value = call_user_func($callback);
        $this->set($key, $value, $ttl);
        
        return $value;
    }
}

