<?php
/**
 * 服务器时间同步检查工具
 * 使用阿里云 NTP 服务器 (ntp.aliyun.com) 进行时间同步
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📷</text></svg>">
    <title>服务器时间同步检查 - 拍摄上传系统</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #87CEEB 0%, #5B9BD5 100%);
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
            background: linear-gradient(135deg, #87CEEB 0%, #5B9BD5 100%);
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
            border-bottom: 2px solid #5B9BD5;
            padding-bottom: 8px;
        }
        .time-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .time-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .time-item:last-child {
            border-bottom: none;
        }
        .time-label {
            font-weight: 500;
            color: #333;
        }
        .time-value {
            font-family: 'Courier New', monospace;
            font-size: 16px;
            color: #666;
        }
        .time-diff {
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 4px;
        }
        .time-diff.sync {
            background: #d4edda;
            color: #155724;
        }
        .time-diff.warning {
            background: #fff3cd;
            color: #856404;
        }
        .time-diff.error {
            background: #f8d7da;
            color: #721c24;
        }
        .action-btn {
            display: inline-block;
            padding: 12px 24px;
            background: #5B9BD5;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            margin: 5px;
            transition: background 0.3s;
        }
        .action-btn:hover {
            background: #5568d3;
        }
        .action-btn.danger {
            background: #dc3545;
        }
        .action-btn.danger:hover {
            background: #c82333;
        }
        .action-btn.success {
            background: #28a745;
        }
        .action-btn.success:hover {
            background: #218838;
        }
        .action-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-box-title {
            font-weight: 600;
            margin-bottom: 8px;
            color: #1976D2;
        }
        .info-box-content {
            color: #555;
            font-size: 14px;
            line-height: 1.6;
        }
        .code-block {
            background: #f4f4f4;
            padding: 15px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            margin: 10px 0;
            overflow-x: auto;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-badge.success {
            background: #d4edda;
            color: #155724;
        }
        .status-badge.warning {
            background: #fff3cd;
            color: #856404;
        }
        .status-badge.error {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🕐 服务器时间同步检查</h1>
            <p>使用阿里云 NTP 服务器 (ntp.aliyun.com) 进行时间同步</p>
        </div>
        <div class="content">
            <?php
            // 设置时区
            date_default_timezone_set('Asia/Shanghai');
            
            $ntpServer = 'ntp.aliyun.com';
            $serverTime = time();
            $serverTimeFormatted = date('Y-m-d H:i:s', $serverTime);
            $serverTimezone = date_default_timezone_get();
            
            // 尝试获取 NTP 服务器时间
            $ntpTime = null;
            $ntpTimeFormatted = null;
            $timeDiff = null;
            $timeDiffSeconds = null;
            $ntpError = null;
            
            // 方法1: 使用 socket 连接 NTP 服务器
            function getNtpTime($server) {
                try {
                    // 检查是否允许打开网络连接
                    if (!function_exists('fsockopen')) {
                        return ['error' => 'fsockopen 函数不可用'];
                    }
                    
                    // 设置超时时间
                    $timeout = 3;
                    $socket = @fsockopen('udp://' . $server, 123, $errno, $errstr, $timeout);
                    if (!$socket) {
                        return ['error' => "无法连接到 NTP 服务器: $errstr ($errno)"];
                    }
                    
                    // 设置 socket 超时
                    stream_set_timeout($socket, $timeout);
                    
                    // 构建 NTP 请求包
                    // NTP 协议格式：前 48 字节
                    // 第 1 字节：LI (2 bits) + VN (3 bits) + Mode (3 bits)
                    // LI = 0 (无警告), VN = 3 (版本 3), Mode = 3 (客户端模式)
                    // 二进制: 00 011 011 = 0x1B = 27 (十进制)
                    $msg = "\x1B" . str_repeat("\0", 47);
                    
                    // 发送请求
                    if (fwrite($socket, $msg) === false) {
                        fclose($socket);
                        return ['error' => '发送 NTP 请求失败'];
                    }
                    
                    // 接收响应（最多等待 3 秒）
                    $response = '';
                    $startTime = microtime(true);
                    while (strlen($response) < 48 && (microtime(true) - $startTime) < $timeout) {
                        $chunk = fread($socket, 48 - strlen($response));
                        if ($chunk === false || $chunk === '') {
                            break;
                        }
                        $response .= $chunk;
                    }
                    fclose($socket);
                    
                    if (strlen($response) < 48) {
                        return ['error' => 'NTP 响应数据不完整（可能超时）'];
                    }
                    
                    // 解析 NTP 时间戳
                    // 从字节 40-43 读取传输时间戳（Transmit Timestamp）
                    $timestamp = unpack('N', substr($response, 40, 4));
                    if ($timestamp === false) {
                        return ['error' => '解析 NTP 时间戳失败'];
                    }
                    $timestamp = $timestamp[1];
                    
                    // NTP 时间戳是从 1900-01-01 00:00:00 UTC 开始的秒数
                    // Unix 时间戳是从 1970-01-01 00:00:00 UTC 开始的秒数
                    // 两者相差 2208988800 秒 (70年 * 365.25天 * 24小时 * 3600秒)
                    $unixTimestamp = $timestamp - 2208988800;
                    
                    // 验证时间戳是否合理（应该在 2020-2100 年之间）
                    if ($unixTimestamp < 1577836800 || $unixTimestamp > 4102444800) {
                        return ['error' => 'NTP 返回的时间戳异常'];
                    }
                    
                    return ['time' => $unixTimestamp];
                } catch (Exception $e) {
                    return ['error' => '异常: ' . $e->getMessage()];
                } catch (Error $e) {
                    return ['error' => '错误: ' . $e->getMessage()];
                }
            }
            
            // 方法2: 使用 HTTP 请求获取时间（备用方案）
            function getTimeFromHttp($server) {
                // 这个方法不适用于 NTP，但可以尝试其他时间 API
                // 这里作为备用方案，实际使用 NTP
                return null;
            }
            
            // 尝试获取 NTP 时间
            $ntpResult = getNtpTime($ntpServer);
            if (isset($ntpResult['time'])) {
                $ntpTime = $ntpResult['time'];
                $ntpTimeFormatted = date('Y-m-d H:i:s', $ntpTime);
                $timeDiffSeconds = $ntpTime - $serverTime;
                $timeDiff = abs($timeDiffSeconds);
            } else {
                $ntpError = $ntpResult['error'] ?? '未知错误';
            }
            
            // 判断时间差异状态
            $diffStatus = 'sync';
            $diffStatusText = '已同步';
            if ($timeDiff !== null) {
                if ($timeDiff <= 1) {
                    $diffStatus = 'sync';
                    $diffStatusText = '已同步';
                } elseif ($timeDiff <= 5) {
                    $diffStatus = 'warning';
                    $diffStatusText = '轻微偏差';
                } else {
                    $diffStatus = 'error';
                    $diffStatusText = '时间偏差较大';
                }
            }
            
            // 检查是否有执行系统命令的权限
            $canExecuteCommands = function_exists('exec') && !in_array('exec', explode(',', ini_get('disable_functions')));
            $isRoot = false;
            if ($canExecuteCommands) {
                // 检查是否是 root 用户（Linux）或管理员（Windows）
                if (PHP_OS_FAMILY === 'Linux') {
                    $whoami = @exec('whoami 2>&1');
                    $isRoot = ($whoami === 'root');
                } elseif (PHP_OS_FAMILY === 'Windows') {
                    // Windows 下检查管理员权限比较复杂，这里假设可以尝试
                    $isRoot = false; // 通常 PHP 在 Windows 下不是管理员
                }
            }
            ?>
            
            <!-- 当前时间信息 -->
            <div class="section">
                <div class="section-title">当前服务器时间</div>
                <div class="time-info">
                    <div class="time-item">
                        <span class="time-label">服务器时间：</span>
                        <span class="time-value"><?php echo htmlspecialchars($serverTimeFormatted); ?></span>
                    </div>
                    <div class="time-item">
                        <span class="time-label">时区设置：</span>
                        <span class="time-value"><?php echo htmlspecialchars($serverTimezone); ?></span>
                    </div>
                    <div class="time-item">
                        <span class="time-label">Unix 时间戳：</span>
                        <span class="time-value"><?php echo $serverTime; ?></span>
                    </div>
                </div>
            </div>
            
            <!-- NTP 服务器时间 -->
            <div class="section">
                <div class="section-title">NTP 服务器时间 (<?php echo htmlspecialchars($ntpServer); ?>)</div>
                <div class="time-info">
                    <?php if ($ntpTime !== null): ?>
                        <div class="time-item">
                            <span class="time-label">NTP 服务器时间：</span>
                            <span class="time-value"><?php echo htmlspecialchars($ntpTimeFormatted); ?></span>
                        </div>
                        <div class="time-item">
                            <span class="time-label">时间差异：</span>
                            <span class="time-value">
                                <?php 
                                $diffSign = $timeDiffSeconds >= 0 ? '+' : '';
                                echo $diffSign . number_format($timeDiffSeconds, 3) . ' 秒';
                                ?>
                            </span>
                        </div>
                        <div class="time-item">
                            <span class="time-label">同步状态：</span>
                            <span class="time-diff <?php echo $diffStatus; ?>">
                                <?php echo $diffStatusText; ?> (差异: <?php echo number_format($timeDiff, 3); ?> 秒)
                            </span>
                        </div>
                    <?php else: ?>
                        <div class="time-item">
                            <span class="time-label">状态：</span>
                            <span class="time-value" style="color: #dc3545;">
                                ❌ 无法获取 NTP 时间: <?php echo htmlspecialchars($ntpError ?? '未知错误'); ?>
                            </span>
                        </div>
                        <div class="info-box">
                            <div class="info-box-title">💡 提示</div>
                            <div class="info-box-content">
                                <p>无法连接到 NTP 服务器，可能的原因：</p>
                                <ul style="margin-left: 20px; margin-top: 8px;">
                                    <li>服务器防火墙阻止了 UDP 123 端口</li>
                                    <li>网络连接问题</li>
                                    <li>NTP 服务器暂时不可用</li>
                                </ul>
                                <p style="margin-top: 10px;">建议在服务器上直接使用系统命令进行时间同步。</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- 时间同步操作 -->
            <div class="section">
                <div class="section-title">时间同步操作</div>
                <div class="info-box">
                    <div class="info-box-title">⚠️ 重要提示</div>
                    <div class="info-box-content">
                        <p>PHP 脚本无法直接修改系统时间，需要 root/管理员权限。以下提供系统级别的同步方法：</p>
                    </div>
                </div>
                
                <?php if ($canExecuteCommands && $isRoot): ?>
                    <div style="margin: 20px 0;">
                        <p style="margin-bottom: 10px;"><strong>检测到您有执行系统命令的权限，可以尝试同步时间：</strong></p>
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="action" value="sync_time">
                            <button type="submit" class="action-btn success" onclick="return confirm('确定要同步服务器时间吗？这需要 root 权限。');">
                                🔄 立即同步时间
                            </button>
                        </form>
                    </div>
                    
                    <?php
                    // 处理时间同步请求
                    if (isset($_POST['action']) && $_POST['action'] === 'sync_time') {
                        echo '<div class="info-box" style="background: #d4edda; border-left-color: #28a745;">';
                        echo '<div class="info-box-title">🔄 正在同步时间...</div>';
                        echo '<div class="info-box-content">';
                        
                        // 尝试使用 ntpdate 同步时间
                        $ntpdateCmd = "ntpdate -u $ntpServer 2>&1";
                        $output = [];
                        $returnVar = 0;
                        @exec($ntpdateCmd, $output, $returnVar);
                        
                        if ($returnVar === 0) {
                            echo '<p style="color: #155724;">✅ 时间同步成功！</p>';
                            echo '<pre style="background: #f4f4f4; padding: 10px; border-radius: 4px; margin-top: 10px;">';
                            echo htmlspecialchars(implode("\n", $output));
                            echo '</pre>';
                            echo '<p style="margin-top: 10px;"><a href="?" class="action-btn">刷新页面查看最新时间</a></p>';
                        } else {
                            echo '<p style="color: #721c24;">❌ 时间同步失败！</p>';
                            echo '<p>可能的原因：</p>';
                            echo '<ul style="margin-left: 20px; margin-top: 8px;">';
                            echo '<li>ntpdate 命令未安装</li>';
                            echo '<li>权限不足</li>';
                            echo '<li>网络连接问题</li>';
                            echo '</ul>';
                            echo '<pre style="background: #f4f4f4; padding: 10px; border-radius: 4px; margin-top: 10px;">';
                            echo htmlspecialchars(implode("\n", $output));
                            echo '</pre>';
                        }
                        
                        echo '</div>';
                        echo '</div>';
                    }
                    ?>
                <?php elseif ($canExecuteCommands && !$isRoot): ?>
                    <div class="info-box" style="background: #fff3cd; border-left-color: #ffc107;">
                        <div class="info-box-title">⚠️ 权限不足</div>
                        <div class="info-box-content">
                            <p>检测到可以执行系统命令，但当前用户不是 root/管理员，无法直接同步系统时间。</p>
                            <p style="margin-top: 10px;">请使用以下方法在服务器上手动同步时间：</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="info-box" style="background: #fff3cd; border-left-color: #ffc107;">
                        <div class="info-box-title">⚠️ 无法执行系统命令</div>
                        <div class="info-box-content">
                            <p>PHP 的 exec 函数被禁用或不可用，无法通过网页直接同步时间。</p>
                            <p style="margin-top: 10px;">请使用以下方法在服务器上手动同步时间：</p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- 系统命令说明 -->
                <div class="info-box">
                    <div class="info-box-title">📖 系统级别时间同步方法</div>
                    <div class="info-box-content">
                        <p><strong>Linux 系统（推荐使用 chronyd，现代系统）：</strong></p>
                        <div class="code-block">
# 安装 chrony（如果未安装）
# Ubuntu/Debian:
sudo apt-get update && sudo apt-get install -y chrony

# CentOS/RHEL:
sudo yum install -y chrony

# 配置 chrony 使用阿里云 NTP 服务器
sudo sed -i 's/^pool.*/server ntp.aliyun.com iburst/' /etc/chrony.conf
# 或者手动编辑 /etc/chrony.conf，添加：
# server ntp.aliyun.com iburst

# 重启 chrony 服务
sudo systemctl restart chronyd
sudo systemctl enable chronyd

# 立即同步时间
sudo chronyd -q 'server ntp.aliyun.com iburst'
                        </div>
                        
                        <p style="margin-top: 15px;"><strong>Linux 系统（使用 ntpdate，传统方法）：</strong></p>
                        <div class="code-block">
# 安装 ntpdate（如果未安装）
# Ubuntu/Debian:
sudo apt-get install -y ntpdate

# CentOS/RHEL:
sudo yum install -y ntpdate

# 立即同步时间
sudo ntpdate -u ntp.aliyun.com

# 设置定时任务（每小时同步一次）
sudo crontab -e
# 添加以下行：
# 0 * * * * /usr/sbin/ntpdate -u ntp.aliyun.com >/dev/null 2>&1
                        </div>
                        
                        <p style="margin-top: 15px;"><strong>Windows 系统：</strong></p>
                        <div class="code-block">
# 在命令提示符（管理员权限）中执行：
w32tm /config /manualpeerlist:ntp.aliyun.com /syncfromflags:manual /reliable:yes /update
w32tm /resync
                        </div>
                        
                        <p style="margin-top: 15px;"><strong>验证时间同步：</strong></p>
                        <div class="code-block">
# Linux:
timedatectl status
# 或
chronyc sources

# Windows:
w32tm /query /status
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 刷新按钮 -->
            <div style="text-align: center; margin-top: 30px;">
                <a href="?" class="action-btn">🔄 刷新时间信息</a>
            </div>
        </div>
    </div>
</body>
</html>

