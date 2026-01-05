<?php
/**
 * 日志工具类
 * 支持日志级别控制，生产环境可关闭调试日志
 * 所有日志统一写入 logs 文件夹
 */
class Logger {
    // 日志级别常量
    const DEBUG = 0;
    const INFO = 1;
    const WARNING = 2;
    const ERROR = 3;
    
    private static $logLevel = self::INFO; // 默认日志级别
    private static $enabled = true; // 是否启用日志
    private static $logDir = null; // 日志目录
    private static $initialized = false; // 是否已初始化
    
    /**
     * 初始化日志配置
     */
    public static function init() {
        if (self::$initialized) {
            return;
        }
        
        // 从配置文件读取日志级别
        $config = require __DIR__ . '/../config/config.php';
        $logConfig = $config['logging'] ?? [];
        
        // 设置日志级别（生产环境默认INFO，开发环境可设置为DEBUG）
        $levelName = $logConfig['level'] ?? 'INFO';
        self::$logLevel = constant('self::' . strtoupper($levelName));
        
        // 是否启用日志
        self::$enabled = $logConfig['enabled'] ?? true;
        
        // 设置日志目录
        self::$logDir = __DIR__ . '/../logs';
        
        // 确保日志目录存在
        self::ensureLogDir();
        
        self::$initialized = true;
    }
    
    /**
     * 确保日志目录存在且可写
     */
    private static function ensureLogDir() {
        if (!file_exists(self::$logDir)) {
            // 创建日志目录，权限设置为 0755
            @mkdir(self::$logDir, 0755, true);
        }
        
        // 检查目录是否可写
        if (!is_writable(self::$logDir)) {
            // 如果目录不可写，尝试设置权限
            @chmod(self::$logDir, 0755);
        }
        
        // 创建 .htaccess 文件防止直接访问日志文件
        $htaccessFile = self::$logDir . '/.htaccess';
        if (!file_exists($htaccessFile)) {
            @file_put_contents($htaccessFile, "Order deny,allow\nDeny from all\n");
        }
        
        // 创建 index.php 文件防止目录列表
        $indexFile = self::$logDir . '/index.php';
        if (!file_exists($indexFile)) {
            @file_put_contents($indexFile, "<?php\n// Access denied\n");
        }
    }
    
    /**
     * 记录调试日志
     */
    public static function debug($message, $context = []) {
        if (self::shouldLog(self::DEBUG)) {
            self::log('DEBUG', $message, $context);
        }
    }
    
    /**
     * 记录信息日志
     */
    public static function info($message, $context = []) {
        if (self::shouldLog(self::INFO)) {
            self::log('INFO', $message, $context);
        }
    }
    
    /**
     * 记录警告日志
     */
    public static function warning($message, $context = []) {
        if (self::shouldLog(self::WARNING)) {
            self::log('WARNING', $message, $context);
        }
    }
    
    /**
     * 记录错误日志
     */
    public static function error($message, $context = []) {
        if (self::shouldLog(self::ERROR)) {
            self::log('ERROR', $message, $context);
        }
    }
    
    /**
     * 判断是否应该记录日志
     */
    private static function shouldLog($level) {
        if (!self::$enabled) {
            return false;
        }
        return $level >= self::$logLevel;
    }
    
    /**
     * 获取日志文件路径（按日期分割）
     */
    private static function getLogFilePath() {
        $date = date('Y-m-d');
        $logFile = self::$logDir . '/app-' . $date . '.log';
        return $logFile;
    }
    
    /**
     * 安全地写入日志文件
     */
    private static function writeLogFile($logMessage) {
        $logFile = self::getLogFilePath();
        
        // 使用文件锁确保并发安全
        $fp = @fopen($logFile, 'a');
        if ($fp === false) {
            // 如果打开失败，尝试创建目录后重试
            self::ensureLogDir();
            $fp = @fopen($logFile, 'a');
            if ($fp === false) {
                // 如果仍然失败，静默失败（避免日志记录导致的问题）
                return false;
            }
        }
        
        // 获取独占锁
        if (flock($fp, LOCK_EX)) {
            // 写入日志
            fwrite($fp, $logMessage);
            // 释放锁
            flock($fp, LOCK_UN);
        }
        
        fclose($fp);
        
        // 设置文件权限（如果文件是新创建的）
        @chmod($logFile, 0644);
        
        return true;
    }
    
    /**
     * 记录日志
     */
    private static function log($level, $message, $context = []) {
        // 确保已初始化
        if (!self::$initialized) {
            self::init();
        }
        
        if (!self::$enabled) {
            return;
        }
        
        try {
            $timestamp = date('Y-m-d H:i:s');
            $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
            $logMessage = "[{$timestamp}] [{$level}] {$message}{$contextStr}" . PHP_EOL;
            
            // 写入日志文件
            self::writeLogFile($logMessage);
        } catch (Exception $e) {
            // 日志记录失败时，不应该抛出异常（避免日志记录导致的问题）
            // 可以尝试使用 PHP 的系统日志作为最后的备选方案
            // 但这里我们选择静默失败，因为用户要求不使用 error_log
        }
    }
}

// 初始化日志配置
Logger::init();
