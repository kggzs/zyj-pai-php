<?php
/**
 * 查看PHP错误日志工具
 * 用于快速查看最近的错误日志
 */
session_start();
require_once __DIR__ . '/core/autoload.php';

// 检查是否登录（用户或管理员都可以查看）
$userModel = new User();
$adminModel = new Admin();
$isLoggedIn = $userModel->isLoggedIn() || $adminModel->isLoggedIn();

if (!$isLoggedIn) {
    die('请先登录');
}

// 获取日志文件路径
$logFile = ini_get('error_log');
if (empty($logFile) || !file_exists($logFile)) {
    // 尝试常见的日志位置
    $possiblePaths = [
        __DIR__ . '/logs/error.log',
        __DIR__ . '/error.log',
        '/var/log/php_errors.log',
        '/var/log/apache2/error.log',
        '/var/log/nginx/error.log',
    ];
    
    $logFile = null;
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            $logFile = $path;
            break;
        }
    }
}

// 获取要显示的行数
$lines = isset($_GET['lines']) ? (int)$_GET['lines'] : 100;
if ($lines < 1 || $lines > 1000) {
    $lines = 100;
}

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>查看错误日志</title>
    <style>
        body {
            font-family: 'Consolas', 'Monaco', monospace;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            margin: 0;
        }
        .header {
            background: #2d2d2d;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0 0 10px 0;
            color: #fff;
        }
        .controls {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .controls select, .controls button {
            padding: 6px 12px;
            background: #3e3e3e;
            color: #d4d4d4;
            border: 1px solid #555;
            border-radius: 4px;
            cursor: pointer;
        }
        .controls button:hover {
            background: #4e4e4e;
        }
        .log-info {
            color: #888;
            font-size: 12px;
            margin-top: 10px;
        }
        .log-content {
            background: #1e1e1e;
            padding: 15px;
            border-radius: 4px;
            border: 1px solid #333;
            max-height: 70vh;
            overflow-y: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
            font-size: 12px;
            line-height: 1.5;
        }
        .log-line {
            margin-bottom: 2px;
        }
        .log-line.error {
            color: #f48771;
        }
        .log-line.warning {
            color: #dcdcaa;
        }
        .log-line.info {
            color: #4ec9b0;
        }
        .no-log {
            color: #888;
            text-align: center;
            padding: 40px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>PHP错误日志查看器</h1>
        <div class="controls">
            <label>显示行数：</label>
            <select id="linesSelect" onchange="loadLogs()">
                <option value="50" <?php echo $lines == 50 ? 'selected' : ''; ?>>最近50行</option>
                <option value="100" <?php echo $lines == 100 ? 'selected' : ''; ?>>最近100行</option>
                <option value="200" <?php echo $lines == 200 ? 'selected' : ''; ?>>最近200行</option>
                <option value="500" <?php echo $lines == 500 ? 'selected' : ''; ?>>最近500行</option>
            </select>
            <button onclick="loadLogs()">刷新</button>
            <button onclick="location.href='dashboard.php'">返回</button>
        </div>
        <div class="log-info">
            <?php if ($logFile): ?>
                日志文件：<?php echo htmlspecialchars($logFile); ?>
                <?php if (file_exists($logFile)): ?>
                    | 文件大小：<?php echo number_format(filesize($logFile) / 1024, 2); ?> KB
                    | 最后修改：<?php echo date('Y-m-d H:i:s', filemtime($logFile)); ?>
                <?php endif; ?>
            <?php else: ?>
                未找到日志文件。请检查PHP配置中的 error_log 设置。
            <?php endif; ?>
        </div>
    </div>
    
    <div class="log-content" id="logContent">
        <?php
        if ($logFile && file_exists($logFile)) {
            $content = file_get_contents($logFile);
            $logLines = explode("\n", $content);
            $logLines = array_filter($logLines, function($line) {
                return !empty(trim($line));
            });
            $logLines = array_slice($logLines, -$lines);
            $logLines = array_reverse($logLines);
            
            foreach ($logLines as $line) {
                $line = htmlspecialchars($line);
                $class = '';
                if (stripos($line, 'error') !== false || stripos($line, 'fatal') !== false) {
                    $class = 'error';
                } elseif (stripos($line, 'warning') !== false) {
                    $class = 'warning';
                } elseif (stripos($line, 'upload.php') !== false || stripos($line, 'get_photos.php') !== false) {
                    $class = 'info';
                }
                echo '<div class="log-line ' . $class . '">' . $line . '</div>';
            }
        } else {
            echo '<div class="no-log">未找到日志文件。日志可能位于以下位置之一：<br><br>';
            echo '1. PHP配置的 error_log 路径<br>';
            echo '2. ' . __DIR__ . '/logs/error.log<br>';
            echo '3. ' . __DIR__ . '/error.log<br>';
            echo '4. 服务器日志目录<br><br>';
            echo '请检查PHP配置或联系服务器管理员。</div>';
        }
        ?>
    </div>
    
    <script>
        function loadLogs() {
            const lines = document.getElementById('linesSelect').value;
            location.href = 'view_logs.php?lines=' + lines;
        }
        
        // 自动滚动到底部（最新日志）
        const logContent = document.getElementById('logContent');
        logContent.scrollTop = 0;
    </script>
</body>
</html>

