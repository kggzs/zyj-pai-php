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
