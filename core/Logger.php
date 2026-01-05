<?php
/**
 * 日志工具类
 * 支持日志级别控制，生产环境可关闭调试日志
 */
class Logger {
    // 日志级别常量
    const DEBUG = 0;
    const INFO = 1;
    const WARNING = 2;
    const ERROR = 3;
    
    private static $logLevel = self::INFO; // 默认日志级别
    private static $enabled = true; // 是否启用日志
    
    /**
     * 初始化日志配置
     */
    public static function init() {
        // 从配置文件读取日志级别
        $config = require __DIR__ . '/../config/config.php';
        $logConfig = $config['logging'] ?? [];
        
        // 设置日志级别（生产环境默认INFO，开发环境可设置为DEBUG）
        $levelName = $logConfig['level'] ?? 'INFO';
        self::$logLevel = constant('self::' . strtoupper($levelName));
        
        // 是否启用日志
        self::$enabled = $logConfig['enabled'] ?? true;
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
     * 记录日志
     */
    private static function log($level, $message, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $logMessage = "[{$timestamp}] [{$level}] {$message}{$contextStr}" . PHP_EOL;
        
        error_log($logMessage);
    }
}

// 初始化日志配置
Logger::init();

