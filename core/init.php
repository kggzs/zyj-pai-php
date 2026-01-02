<?php
/**
 * 应用初始化文件
 * 用于设置全局配置和隐藏敏感信息
 */

// 隐藏PHP版本信息（移除X-Powered-By头）
if (function_exists('header_remove')) {
    header_remove('X-Powered-By');
}

// 尝试移除Server头（通常在服务器级别配置，这里只是额外保护）
if (function_exists('header_remove')) {
    @header_remove('Server');
}

// 隐藏宝塔面板相关HTTP头
if (function_exists('header_remove')) {
    @header_remove('http_Path');
    @header_remove('http_bt_config');
}

// 设置时区
if (!ini_get('date.timezone')) {
    date_default_timezone_set('Asia/Shanghai');
}

// 禁用错误显示（生产环境）
if (!defined('DEBUG_MODE') || !DEBUG_MODE) {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
}

// 设置错误报告（仍然记录到日志，但不显示）
error_reporting(E_ALL);

// 设置默认字符集
if (!headers_sent()) {
    // 只在还没有设置Content-Type时设置
    if (!isset($GLOBALS['headers_set'])) {
        // 这里不设置Content-Type，由各个API自己设置
        $GLOBALS['headers_set'] = true;
    }
}

