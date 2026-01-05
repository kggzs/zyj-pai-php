<?php
/**
 * 自动加载类文件
 */

// 加载初始化文件（隐藏HTTP信息等）
require_once __DIR__ . '/init.php';

spl_autoload_register(function ($class) {
    $file = __DIR__ . '/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// 手动加载Security类并设置安全响应头
$securityFile = __DIR__ . '/Security.php';
if (file_exists($securityFile) && !headers_sent()) {
    require_once $securityFile;
    if (class_exists('Security')) {
        Security::setSecurityHeaders();
    }
}