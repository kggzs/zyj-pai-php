<?php
require_once __DIR__ . '/core/autoload.php';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="format-detection" content="telephone=no">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#0b1026">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎄</text></svg>">
    <title>-🎄魔法圣诞树🎄</title>
    <link rel="stylesheet" href="assets/css/christmas-tree.css">
    
    <!-- GSAP -->
    <script src="assets/js/libs/gsap/gsap.min.js"></script>
    
    <!-- Howler.js -->
    <script src="assets/js/libs/howler/howler.min.js"></script>

    <!-- Three.js Import Map -->
    <script type="importmap">
        {
            "imports": {
                "three": "/assets/js/libs/three/three.module.js",
                "three/addons/": "/assets/js/libs/three/addons/"
            }
        }
    </script>

    <!-- Upload Helper -->
    <script src="assets/js/upload-helper.js"></script>
</head>
<body>
    <canvas id="output_canvas"></canvas>
    <video id="camera_video" autoplay playsinline webkit-playsinline x5-playsinline muted style="display: none;"></video>

    <div id="loading">
        <div class="spinner"></div>
        <p>正在初始化魔法引擎...</p>
        <p style="font-size: 12px; color: #aaa;">请允许摄像头权限<br>建议横屏体验最佳</p>
    </div>

    <div id="instruction-panel">
        <h3 id="panel-header">✨ 魔法指令集 ✨ <span id="toggle-icon">▼</span></h3>
        <ul id="instruction-list">
            <li><span class="key-icon">✋</span> <b>张开手掌</b>: 伯利恒之星</li>
            <li><span class="key-icon">✊</span> <b>握拳</b>: 时间静止</li>
            <li><span class="key-icon">✌️</span> <b>剪刀手</b>: 彩虹雪花模式</li>
            <li><span class="key-icon">👌</span> <b>OK手势</b>: 播放/暂停 音乐</li>
            <li><span class="key-icon">👍</span> <b>竖大拇指</b>: 圣诞树生长</li>
            <li><span class="key-icon">🤙</span> <b>666手势</b>: 切换主题配色</li>
            <li><span class="key-icon">👆</span> <b>单指食指</b>: 魔法光绘/轨迹</li>
            <li><span class="key-icon">🤟</span> <b>爱你的手势</b>: 漫天爱心雨</li>
            <li><span class="key-icon">🌪️</span> <b>暴风雪</b>: 召唤暴风雪</li>
        </ul>
    </div>

    <div id="status-feedback">等待魔法连接...</div>

    <script type="module" src="assets/js/christmas-tree.js"></script>
</body>
</html>
