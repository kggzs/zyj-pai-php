<?php
/**
 * 辅助函数类
 */
class Helper {
    /**
     * 获取当前站点基础URL
     */
    public static function getBaseUrl() {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        // 获取SCRIPT_NAME，用于判断项目是否在子目录
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        
        // 如果SCRIPT_NAME包含多个路径段，提取项目根目录
        // 例如: /photo-upload/api/get_photos.php -> /photo-upload
        if ($scriptName && strpos($scriptName, '/') !== false) {
            // 标准化路径分隔符
            $scriptName = str_replace('\\', '/', $scriptName);
            $parts = explode('/', trim($scriptName, '/'));
            
            // 移除文件名
            if (!empty($parts)) {
                array_pop($parts);
            }
            
            // 如果是api目录下的文件，再移除api目录
            if (!empty($parts) && end($parts) === 'api') {
                array_pop($parts);
            }
            
            // 如果还有路径段，说明项目在子目录中
            if (!empty($parts)) {
                return $protocol . $host . '/' . implode('/', $parts);
            }
        }
        
        // 默认返回协议+主机（图片路径相对于域名根目录）
        return $protocol . $host;
    }
    
    /**
     * 获取站点URL（优先使用配置，否则动态获取）
     */
    public static function getSiteUrl() {
        $configFile = __DIR__ . '/../config/config.php';
        if (file_exists($configFile)) {
            $config = require $configFile;
            $siteUrl = $config['site_url'] ?? '';
            
            // 如果配置了非空的URL，且不是localhost，使用配置的URL
            if (!empty($siteUrl) && $siteUrl !== 'http://localhost' && $siteUrl !== 'http://127.0.0.1') {
                return rtrim($siteUrl, '/');
            }
        }
        
        // 否则动态获取当前请求的URL
        return self::getBaseUrl();
    }
    
    /**
     * 获取系统配置
     */
    public static function getSystemConfig($key = null) {
        $db = Database::getInstance();
        
        if ($key === null) {
            // 获取所有配置
            $configs = $db->fetchAll("SELECT config_key, config_value FROM system_config");
            $result = [];
            foreach ($configs as $config) {
                $result[$config['config_key']] = $config['config_value'];
            }
            return $result;
        } else {
            // 获取单个配置
            $config = $db->fetchOne(
                "SELECT config_value FROM system_config WHERE config_key = ?",
                [$key]
            );
            return $config ? $config['config_value'] : null;
        }
    }
    
    /**
     * 获取项目名称
     */
    public static function getProjectName() {
        $projectName = self::getSystemConfig('project_name');
        return $projectName ?: '拍摄上传系统';
    }
    
    /**
     * 获取配置组（从数据库读取，如果不存在则从配置文件读取）
     */
    public static function getConfigGroup($groupName) {
        $db = Database::getInstance();
        
        // 从数据库读取
        $configs = $db->fetchAll(
            "SELECT config_key, config_value FROM system_config WHERE config_key LIKE ?",
            ["{$groupName}_%"]
        );
        
        if (!empty($configs)) {
            // 如果数据库有配置，解析并返回
            $result = [];
            foreach ($configs as $config) {
                $key = str_replace("{$groupName}_", '', $config['config_key']);
                $value = $config['config_value'];
                
                // 尝试解析JSON
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $result[$key] = $decoded;
                } else {
                    // 尝试转换为布尔值或数字
                    if ($value === 'true' || $value === '1') {
                        $result[$key] = true;
                    } elseif ($value === 'false' || $value === '0') {
                        $result[$key] = false;
                    } elseif (is_numeric($value)) {
                        $result[$key] = is_float($value) ? (float)$value : (int)$value;
                    } else {
                        $result[$key] = $value;
                    }
                }
            }
            return $result;
        }
        
        // 如果数据库没有，从配置文件读取（兼容旧配置）
        $configFile = __DIR__ . '/../config/config.php';
        if (file_exists($configFile)) {
            $config = require $configFile;
            return $config[$groupName] ?? [];
        }
        
        return [];
    }
    
    /**
     * 设置配置组（保存到数据库）
     */
    public static function setConfigGroup($groupName, $configData) {
        $db = Database::getInstance();
        
        foreach ($configData as $key => $value) {
            $configKey = "{$groupName}_{$key}";
            
            // 将值转换为字符串（数组/对象转为JSON）
            if (is_array($value) || is_object($value)) {
                $configValue = json_encode($value, JSON_UNESCAPED_UNICODE);
            } elseif (is_bool($value)) {
                $configValue = $value ? '1' : '0';
            } else {
                $configValue = (string)$value;
            }
            
            // 检查是否存在
            $existing = $db->fetchOne(
                "SELECT id FROM system_config WHERE config_key = ?",
                [$configKey]
            );
            
            if ($existing) {
                // 更新
                $db->execute(
                    "UPDATE system_config SET config_value = ? WHERE config_key = ?",
                    [$configValue, $configKey]
                );
            } else {
                // 插入
                $db->execute(
                    "INSERT INTO system_config (config_key, config_value, description) VALUES (?, ?, ?)",
                    [$configKey, $configValue, "{$groupName}配置：{$key}"]
                );
            }
        }
        
        return true;
    }
}
