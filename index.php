<?php
/**
 * 官网首页
 */
session_start();
require_once __DIR__ . '/core/autoload.php';

$userModel = new User();
$isLoggedIn = $userModel->isLoggedIn();
$projectName = Helper::getProjectName();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📷</text></svg>">
    <title><?php echo htmlspecialchars($projectName); ?> - 智能拍摄上传系统</title>
    <link rel="stylesheet" href="assets/css/common.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            overflow-x: hidden;
        }

        /* 导航栏 */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            z-index: 1000;
            padding: 15px 0;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            background: linear-gradient(135deg, #87CEEB 0%, #5B9BD5 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .nav-links {
            display: flex;
            gap: 30px;
            align-items: center;
        }

        .nav-links a {
            color: #333;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: #5B9BD5;
        }

        .nav-btn {
            padding: 10px 24px;
            background: linear-gradient(135deg, #87CEEB 0%, #5B9BD5 100%);
            color: white;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: transform 0.3s, box-shadow 0.3s;
            display: inline-block;
        }

        .nav-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(91, 155, 213, 0.4);
        }

        /* 英雄区域 */
        .hero {
            background: linear-gradient(135deg, #87CEEB 0%, #5B9BD5 100%);
            color: white;
            padding: 150px 20px 100px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 600px;
            height: 600px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            pointer-events: none;
        }

        .hero::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -5%;
            width: 400px;
            height: 400px;
            background: rgba(255,255,255,0.08);
            border-radius: 50%;
            pointer-events: none;
        }

        .hero-content {
            max-width: 800px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .hero h1 {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 0 2px 20px rgba(0,0,0,0.2);
        }

        .hero p {
            font-size: 20px;
            margin-bottom: 40px;
            opacity: 0.95;
            line-height: 1.8;
        }

        .hero-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-primary {
            padding: 16px 32px;
            background: white;
            color: #5B9BD5;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.3s, box-shadow 0.3s;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
        }

        .btn-secondary {
            padding: 16px 32px;
            background: rgba(255,255,255,0.2);
            color: white;
            border: 2px solid white;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            backdrop-filter: blur(10px);
        }

        .btn-secondary:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-3px);
        }

        /* 功能特性 */
        .features {
            padding: 100px 20px;
            background: #f8f9fa;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-title {
            text-align: center;
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 60px;
            color: #333;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 50px;
        }

        .feature-card {
            background: white;
            padding: 40px 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
            text-align: center;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #87CEEB 0%, #5B9BD5 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: white;
        }

        .feature-card h3 {
            font-size: 24px;
            margin-bottom: 15px;
            color: #333;
        }

        .feature-card p {
            color: #666;
            line-height: 1.8;
            font-size: 16px;
        }

        /* 技术栈 */
        .tech-stack {
            padding: 100px 20px;
            background: white;
        }

        .tech-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            margin-top: 50px;
        }

        .tech-item {
            text-align: center;
            padding: 30px;
            background: #f8f9fa;
            border-radius: 12px;
            transition: transform 0.3s;
        }

        .tech-item:hover {
            transform: scale(1.05);
        }

        .tech-item h4 {
            font-size: 18px;
            color: #5B9BD5;
            margin-top: 15px;
        }

        /* 页脚 */
        .footer {
            background: #2c3e50;
            color: white;
            padding: 50px 20px 30px;
            text-align: center;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer p {
            opacity: 0.8;
            margin-top: 20px;
        }

        /* 响应式设计 */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 32px;
            }

            .hero p {
                font-size: 16px;
            }

            .nav-links {
                gap: 15px;
            }

            .nav-btn {
                padding: 8px 16px;
                font-size: 14px;
            }

            .section-title {
                font-size: 28px;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .hero-buttons {
                flex-direction: column;
            }

            .btn-primary,
            .btn-secondary {
                width: 100%;
                max-width: 300px;
            }
        }

        @media (max-width: 480px) {
            .hero {
                padding: 120px 15px 80px;
            }

            .hero h1 {
                font-size: 28px;
            }

            .nav-container {
                flex-direction: column;
                gap: 15px;
            }

            .nav-links {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- 导航栏 -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo"><?php echo htmlspecialchars($projectName); ?></div>
            <div class="nav-links">
                <?php if ($isLoggedIn): ?>
                    <a href="dashboard.php">用户中心</a>
                    <a href="api/logout.php" class="nav-btn">退出登录</a>
                <?php else: ?>
                    <a href="login.php">登录</a>
                    <a href="register.php" class="nav-btn">立即注册</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- 英雄区域 -->
    <section class="hero">
        <div class="hero-content">
            <h1>智能拍摄上传系统</h1>
            <p>基于移动端的自动拍摄上传平台，支持照片和视频自动拍摄、积分奖励、VIP会员等功能，让拍摄分享更简单</p>
            <div class="hero-buttons">
                <?php if ($isLoggedIn): ?>
                    <a href="dashboard.php" class="btn-primary">进入用户中心</a>
                <?php else: ?>
                    <a href="register.php" class="btn-primary">免费注册</a>
                    <a href="login.php" class="btn-secondary">立即登录</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- 功能特性 -->
    <section class="features">
        <div class="container">
            <h2 class="section-title">核心功能</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">📸</div>
                    <h3>自动拍摄上传</h3>
                    <p>移动端自动调用摄像头拍摄照片/视频，无需用户操作，自动上传到云端，支持3D交互式拍摄界面</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🔗</div>
                    <h3>拍摄链接管理</h3>
                    <p>生成唯一拍摄链接码，轻松分享给他人使用，支持链接有效期设置、标签管理和启用/禁用功能</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">⭐</div>
                    <h3>积分奖励系统</h3>
                    <p>注册奖励、邀请奖励、上传奖励、每日签到等多种积分获取方式，连续签到有额外奖励</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">👑</div>
                    <h3>VIP会员系统</h3>
                    <p>VIP会员享受无限制生成拍摄链接、设置永久有效期、链接启用/禁用等专属特权</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🛒</div>
                    <h3>积分商城</h3>
                    <p>使用积分兑换VIP会员、拍摄链接数量等商品，让积分更有价值</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3>数据统计</h3>
                    <p>查看照片列表、积分明细、排行榜等数据，支持标签搜索和批量操作</p>
                </div>
            </div>
        </div>
    </section>

    <!-- 技术栈 -->
    <section class="tech-stack">
        <div class="container">
            <h2 class="section-title">技术栈</h2>
            <div class="tech-grid">
                <div class="tech-item">
                    <div style="font-size: 48px;">🐘</div>
                    <h4>PHP 7.2+</h4>
                </div>
                <div class="tech-item">
                    <div style="font-size: 48px;">🗄️</div>
                    <h4>MySQL 5.6+</h4>
                </div>
                <div class="tech-item">
                    <div style="font-size: 48px;">🎨</div>
                    <h4>Three.js</h4>
                </div>
                <div class="tech-item">
                    <div style="font-size: 48px;">📹</div>
                    <h4>WebRTC</h4>
                </div>
                <div class="tech-item">
                    <div style="font-size: 48px;">🎬</div>
                    <h4>MediaRecorder</h4>
                </div>
                <div class="tech-item">
                    <div style="font-size: 48px;">✨</div>
                    <h4>GSAP</h4>
                </div>
            </div>
        </div>
    </section>

    <!-- 页脚 -->
    <footer class="footer">
        <div class="footer-content">
            <h3><?php echo htmlspecialchars($projectName); ?></h3>
            <p>基于 PHP 和 MySQL 的智能拍摄上传系统</p>
            <p style="margin-top: 10px; font-size: 14px; opacity: 0.6;">© <?php echo date('Y'); ?> All Rights Reserved</p>
        </div>
    </footer>
</body>
</html>
