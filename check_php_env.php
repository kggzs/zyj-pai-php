<?php
/**
 * PHP 环境检测器
 * 检测 PHP 环境是否满足项目运行要求
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP 环境检测 - 拍摄上传系统</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .header p {
            opacity: 0.9;
            font-size: 14px;
        }
        .content {
            padding: 30px;
        }
        .section {
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 8px;
        }
        .check-item {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            margin-bottom: 8px;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 4px solid #ddd;
        }
        .check-item.pass {
            border-left-color: #28a745;
            background: #d4edda;
        }
        .check-item.fail {
            border-left-color: #dc3545;
            background: #f8d7da;
        }
        .check-item.warning {
            border-left-color: #ffc107;
            background: #fff3cd;
        }
        .check-label {
            flex: 1;
            font-weight: 500;
            color: #333;
        }
        .check-value {
            margin-left: 15px;
            color: #666;
            font-size: 14px;
        }
        .check-status {
            margin-left: 15px;
            font-size: 18px;
        }
        .summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
        }
        .summary-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .summary-item:last-child {
            border-bottom: none;
        }
        .summary-label {
            color: #666;
        }
        .summary-value {
            font-weight: 600;
            color: #333;
        }
        .summary-value.pass {
            color: #28a745;
        }
        .summary-value.fail {
            color: #dc3545;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 13px;
            color: #e83e8c;
        }
        .recommendation {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin-top: 20px;
            border-radius: 4px;
        }
        .recommendation-title {
            font-weight: 600;
            margin-bottom: 8px;
            color: #1976D2;
        }
        .recommendation-content {
            color: #555;
            font-size: 14px;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 PHP 环境检测</h1>
            <p>拍摄上传系统运行环境检查</p>
        </div>
        <div class="content">
            <?php
            $checks = [];
            $totalChecks = 0;
            $passedChecks = 0;
            
            // PHP 版本检查
            $totalChecks++;
            $phpVersion = PHP_VERSION;
            $phpVersionRequired = '7.2.0';
            $phpVersionOk = version_compare($phpVersion, $phpVersionRequired, '>=');
            if ($phpVersionOk) {
                $passedChecks++;
            }
            $checks[] = [
                'label' => 'PHP 版本',
                'value' => $phpVersion . ' (要求: >= ' . $phpVersionRequired . ')',
                'status' => $phpVersionOk ? 'pass' : 'fail',
                'icon' => $phpVersionOk ? '✅' : '❌'
            ];
            
            // 操作系统
            $totalChecks++;
            $os = PHP_OS . ' (' . PHP_OS_FAMILY . ')';
            $checks[] = [
                'label' => '操作系统',
                'value' => $os,
                'status' => 'pass',
                'icon' => 'ℹ️'
            ];
            
            // GD 库检查
            $totalChecks++;
            $gdLoaded = extension_loaded('gd');
            if ($gdLoaded) {
                $passedChecks++;
                $gdInfo = gd_info();
                $gdVersion = $gdInfo['GD Version'] ?? '未知';
                $gdValue = $gdVersion . ' (已启用)';
            } else {
                $gdValue = '未安装';
            }
            $checks[] = [
                'label' => 'GD 库扩展',
                'value' => $gdValue,
                'status' => $gdLoaded ? 'pass' : 'fail',
                'icon' => $gdLoaded ? '✅' : '❌'
            ];
            
            // PDO 扩展检查
            $totalChecks++;
            $pdoLoaded = extension_loaded('pdo');
            if ($pdoLoaded) {
                $passedChecks++;
            }
            $checks[] = [
                'label' => 'PDO 扩展',
                'value' => $pdoLoaded ? '已启用' : '未安装',
                'status' => $pdoLoaded ? 'pass' : 'fail',
                'icon' => $pdoLoaded ? '✅' : '❌'
            ];
            
            // PDO MySQL 驱动检查
            $totalChecks++;
            $pdoMysqlLoaded = extension_loaded('pdo_mysql');
            if ($pdoMysqlLoaded) {
                $passedChecks++;
            }
            $checks[] = [
                'label' => 'PDO MySQL 驱动',
                'value' => $pdoMysqlLoaded ? '已启用' : '未安装',
                'status' => $pdoMysqlLoaded ? 'pass' : 'fail',
                'icon' => $pdoMysqlLoaded ? '✅' : '❌'
            ];
            
            // Session 支持检查
            $totalChecks++;
            $sessionOk = function_exists('session_start');
            if ($sessionOk) {
                $passedChecks++;
            }
            $checks[] = [
                'label' => 'Session 支持',
                'value' => $sessionOk ? '已启用' : '未启用',
                'status' => $sessionOk ? 'pass' : 'fail',
                'icon' => $sessionOk ? '✅' : '❌'
            ];
            
            // JSON 支持检查
            $totalChecks++;
            $jsonOk = function_exists('json_encode') && function_exists('json_decode');
            if ($jsonOk) {
                $passedChecks++;
            }
            $checks[] = [
                'label' => 'JSON 支持',
                'value' => $jsonOk ? '已启用' : '未启用',
                'status' => $jsonOk ? 'pass' : 'fail',
                'icon' => $jsonOk ? '✅' : '❌'
            ];
            
            // 文件上传支持检查
            $totalChecks++;
            $uploadOk = ini_get('file_uploads') == '1';
            if ($uploadOk) {
                $passedChecks++;
            }
            $uploadMaxSize = ini_get('upload_max_filesize');
            $checks[] = [
                'label' => '文件上传支持',
                'value' => $uploadOk ? '已启用 (最大: ' . $uploadMaxSize . ')' : '未启用',
                'status' => $uploadOk ? 'pass' : 'fail',
                'icon' => $uploadOk ? '✅' : '❌'
            ];
            
            // 目录权限检查
            $writableDirs = [
                'uploads/original' => '原图上传目录',
                'uploads/video' => '视频上传目录',
                'cache' => '缓存目录'
            ];
            
            foreach ($writableDirs as $dir => $label) {
                $totalChecks++;
                $dirPath = __DIR__ . '/' . $dir;
                $exists = is_dir($dirPath);
                $writable = $exists && is_writable($dirPath);
                
                if ($writable) {
                    $passedChecks++;
                } else if (!$exists) {
                    // 尝试创建目录
                    if (@mkdir($dirPath, 0755, true)) {
                        $writable = true;
                        $passedChecks++;
                    }
                }
                
                $checks[] = [
                    'label' => $label,
                    'value' => $exists ? ($writable ? '可写' : '不可写') : '不存在',
                    'status' => $writable ? 'pass' : 'fail',
                    'icon' => $writable ? '✅' : '❌'
                ];
            }
            
            // 禁用的函数检查
            $disabledFunctions = ini_get('disable_functions');
            
            // 项目中使用的函数：exec（用于数据库备份功能）
            $usedFunctions = ['exec'];
            // 项目中未使用的函数（仅检查，不影响功能）
            $unusedFunctions = ['shell_exec', 'proc_open', 'system'];
            
            $disabledUsed = [];
            $disabledUnused = [];
            
            // 检查使用的函数
            foreach ($usedFunctions as $func) {
                if ($disabledFunctions && strpos($disabledFunctions, $func) !== false) {
                    $disabledUsed[] = $func;
                }
            }
            
            // 检查未使用的函数（仅用于信息展示）
            foreach ($unusedFunctions as $func) {
                if ($disabledFunctions && strpos($disabledFunctions, $func) !== false) {
                    $disabledUnused[] = $func;
                }
            }
            
            // 如果使用的函数未被禁用，则通过检查
            if (empty($disabledUsed)) {
                $passedChecks++;
            }
            $totalChecks++;
            
            // 构建显示信息
            $valueText = '';
            if (empty($disabledUsed) && empty($disabledUnused)) {
                $valueText = '所有函数可用';
            } else {
                $parts = [];
                if (!empty($disabledUsed)) {
                    $parts[] = '已禁用（影响功能）: ' . implode(', ', $disabledUsed) . '（数据库备份功能将无法使用）';
                }
                if (!empty($disabledUnused)) {
                    $parts[] = '已禁用（未使用）: ' . implode(', ', $disabledUnused);
                }
                $valueText = implode('；', $parts);
            }
            
            $checks[] = [
                'label' => '系统函数可用性',
                'value' => $valueText,
                'status' => empty($disabledUsed) ? 'pass' : 'warning',
                'icon' => empty($disabledUsed) ? '✅' : '⚠️'
            ];
            
            // 内存限制检查
            $memoryLimit = ini_get('memory_limit');
            $memoryLimitBytes = return_bytes($memoryLimit);
            $recommendedMemory = 128 * 1024 * 1024; // 128MB
            $memoryOk = $memoryLimitBytes >= $recommendedMemory;
            
            $checks[] = [
                'label' => '内存限制',
                'value' => $memoryLimit . ($memoryOk ? ' (推荐: >= 128M)' : ' (推荐: >= 128M)'),
                'status' => $memoryOk ? 'pass' : 'warning',
                'icon' => $memoryOk ? '✅' : '⚠️'
            ];
            
            // 时区设置检查
            $timezone = date_default_timezone_get();
            $checks[] = [
                'label' => '时区设置',
                'value' => $timezone,
                'status' => 'pass',
                'icon' => 'ℹ️'
            ];
            
            // 配置文件检查
            $configFile = __DIR__ . '/config/config.php';
            $configExists = file_exists($configFile);
            $configReadable = $configExists && is_readable($configFile);
            
            $totalChecks++;
            if ($configReadable) {
                $passedChecks++;
            }
            $checks[] = [
                'label' => '系统配置文件',
                'value' => $configReadable ? '存在且可读' : ($configExists ? '存在但不可读' : '不存在'),
                'status' => $configReadable ? 'pass' : 'fail',
                'icon' => $configReadable ? '✅' : '❌'
            ];
            
            $dbConfigFile = __DIR__ . '/config/database.php';
            $dbConfigExists = file_exists($dbConfigFile);
            $dbConfigReadable = $dbConfigExists && is_readable($dbConfigFile);
            
            // 数据库配置文件是警告级别，不计入必须通过的检查
            $checks[] = [
                'label' => '数据库配置文件',
                'value' => $dbConfigReadable ? '存在且可读' : ($dbConfigExists ? '存在但不可读' : '不存在'),
                'status' => $dbConfigReadable ? 'pass' : 'warning',
                'icon' => $dbConfigReadable ? '✅' : '⚠️'
            ];
            
            
            // 显示检查结果
            echo '<div class="section">';
            echo '<div class="section-title">环境检查结果</div>';
            foreach ($checks as $check) {
                echo '<div class="check-item ' . $check['status'] . '">';
                echo '<span class="check-status">' . $check['icon'] . '</span>';
                echo '<span class="check-label">' . $check['label'] . '</span>';
                echo '<span class="check-value">' . $check['value'] . '</span>';
                echo '</div>';
            }
            echo '</div>';
            
            // 计算通过状态（只计算必须通过的检查项，warning 不计入）
            $requiredChecks = 0;
            $requiredPassed = 0;
            foreach ($checks as $check) {
                // 只统计 fail 和 pass 状态的检查项，warning 和 info 不计入
                if ($check['status'] === 'fail' || $check['status'] === 'pass') {
                    $requiredChecks++;
                    if ($check['status'] === 'pass') {
                        $requiredPassed++;
                    }
                }
            }
            
            // 显示摘要
            $allPassed = $requiredPassed == $requiredChecks;
            echo '<div class="summary">';
            echo '<div class="summary-title">检查摘要</div>';
            echo '<div class="summary-item">';
            echo '<span class="summary-label">总检查项</span>';
            echo '<span class="summary-value">' . count($checks) . '</span>';
            echo '</div>';
            echo '<div class="summary-item">';
            echo '<span class="summary-label">必须通过项</span>';
            echo '<span class="summary-value">' . $requiredChecks . '</span>';
            echo '</div>';
            echo '<div class="summary-item">';
            echo '<span class="summary-label">已通过项</span>';
            echo '<span class="summary-value ' . ($allPassed ? 'pass' : '') . '">' . $requiredPassed . '</span>';
            echo '</div>';
            echo '<div class="summary-item">';
            echo '<span class="summary-label">失败/警告项</span>';
            echo '<span class="summary-value ' . ($allPassed ? '' : 'fail') . '">' . ($requiredChecks - $requiredPassed) . '</span>';
            echo '</div>';
            echo '<div class="summary-item">';
            echo '<span class="summary-label">整体状态</span>';
            echo '<span class="summary-value ' . ($allPassed ? 'pass' : 'fail') . '">' . ($allPassed ? '✅ 通过' : '❌ 需要修复') . '</span>';
            echo '</div>';
            echo '</div>';
            
            // 显示建议
            if (!$allPassed) {
                echo '<div class="recommendation">';
                echo '<div class="recommendation-title">💡 修复建议</div>';
                echo '<div class="recommendation-content">';
                
                if (!$phpVersionOk) {
                    echo '<p><strong>PHP 版本过低：</strong>请升级到 PHP 7.2 或更高版本。</p>';
                }
                
                if (!$gdLoaded) {
                    echo '<p><strong>GD 库未安装：</strong>请安装 PHP GD 扩展。';
                    if (PHP_OS_FAMILY === 'Linux') {
                        echo ' Ubuntu/Debian: <code>sudo apt-get install php-gd</code>，CentOS/RHEL: <code>sudo yum install php-gd</code>';
                    } elseif (PHP_OS_FAMILY === 'Windows') {
                        echo ' 在 php.ini 中取消注释 <code>extension=gd</code>';
                    }
                    echo '</p>';
                }
                
                if (!$pdoLoaded || !$pdoMysqlLoaded) {
                    echo '<p><strong>PDO 扩展未安装：</strong>请安装 PHP PDO 和 PDO MySQL 扩展。';
                    if (PHP_OS_FAMILY === 'Linux') {
                        echo ' Ubuntu/Debian: <code>sudo apt-get install php-mysql</code>，CentOS/RHEL: <code>sudo yum install php-mysql</code>';
                    }
                    echo '</p>';
                }
                
                if (!$sessionOk) {
                    echo '<p><strong>Session 未启用：</strong>请在 php.ini 中启用 Session 支持。</p>';
                }
                
                if (!$jsonOk) {
                    echo '<p><strong>JSON 支持未启用：</strong>JSON 扩展通常是 PHP 核心扩展，请检查 PHP 安装。</p>';
                }
                
                if (!$uploadOk) {
                    echo '<p><strong>文件上传未启用：</strong>请在 php.ini 中设置 <code>file_uploads = On</code>。</p>';
                }
                
                if (!empty($disabledUsed)) {
                    echo '<p><strong>关键函数被禁用：</strong>以下函数被禁用将影响系统功能：<code>' . implode(', ', $disabledUsed) . '</code>。';
                    echo '<br>• <code>exec</code> 函数用于数据库备份和恢复功能，如果被禁用，管理员后台的数据库备份功能将无法使用，但其他功能不受影响。';
                    echo '<br>建议在 php.ini 中移除这些函数以启用完整功能。</p>';
                }
                if (!empty($disabledUnused)) {
                    echo '<p><strong>未使用的函数被禁用：</strong>以下函数在项目中未使用，禁用不影响功能：<code>' . implode(', ', $disabledUnused) . '</code>。</p>';
                }
                
                if (!$memoryOk) {
                    echo '<p><strong>内存限制较低：</strong>建议在 php.ini 中设置 <code>memory_limit = 128M</code> 或更高。</p>';
                }
                
                if (!$configReadable) {
                    echo '<p><strong>配置文件问题：</strong>请确保 <code>config/config.php</code> 文件存在且可读。</p>';
                }
                
                
                echo '</div>';
                echo '</div>';
            } else {
                echo '<div class="recommendation">';
                echo '<div class="recommendation-title">🎉 恭喜！</div>';
                echo '<div class="recommendation-content">';
                echo '<p>您的 PHP 环境完全满足项目运行要求，可以正常使用所有功能。</p>';
                if (!$dbConfigReadable) {
                    echo '<p><strong>注意：</strong>请配置数据库连接信息（<code>config/database.php</code>）。</p>';
                }
                echo '</div>';
                echo '</div>';
            }
            ?>
        </div>
    </div>
</body>
</html>

<?php
/**
 * 将内存限制字符串转换为字节数
 */
function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = (int)$val;
    switch($last) {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }
    return $val;
}
?>

