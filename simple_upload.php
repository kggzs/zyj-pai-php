<?php
/**
 * 简单照片上传示例页面
 *
 * 功能说明：
 * 1. 开启摄像头预览
 * 2. 点击拍照按钮拍照
 * 3. 自动调用 upload-helper.js 上传图片
 * 4. 显示上传结果
 *
 * 使用方法：
 * 1. 在URL中添加邀请码参数，例如：simple_upload.php?code=ABC12345
 * 2. 打开页面
 * 3. 点击"开启摄像头"按钮
 * 4. 点击"拍照上传"按钮
 *
 * 技术特点：
 * - 只依赖 upload-helper.js 进行上传
 * - 纯净的上传功能演示
 * - 包含完整的错误处理
 */

require_once __DIR__ . '/core/autoload.php';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#0b1026">
    <title>照片上传模板演示代码</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #1a1a2e;
            color: #fff;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            width: 100%;
            text-align: center;
        }

        h1 {
            margin-bottom: 20px;
            font-size: 24px;
            color: #e94560;
        }

        .video-container {
            position: relative;
            width: 100%;
            max-width: 640px;
            margin: 0 auto 20px;
            background: #0f0f23;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }

        #cameraVideo {
            width: 100%;
            height: auto;
            display: block;
            object-fit: cover;
        }

        #previewCanvas {
            width: 100%;
            height: auto;
            display: none;
            object-fit: cover;
        }

        .info {
            background: rgba(233, 69, 96, 0.1);
            border: 1px solid #e94560;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #ffd700;
        }

        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 40px;
            font-size: 18px;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s;
            margin: 10px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }

        .btn:disabled {
            background: #666;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        #startBtn {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }

        #captureBtn {
            background: linear-gradient(135deg, #fc4a1a 0%, #f7b733 100%);
        }

        .message {
            margin-top: 20px;
            padding: 15px;
            border-radius: 8px;
            font-size: 14px;
            display: none;
        }

        .message.success {
            background: rgba(56, 239, 125, 0.2);
            border: 1px solid #38ef7d;
            color: #38ef7d;
        }

        .message.error {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid #ef4444;
            color: #ef4444;
        }

        .loading {
            display: none;
            margin-top: 20px;
            color: #ffd700;
            font-size: 16px;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .code-display {
            margin-top: 15px;
            padding: 10px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 6px;
            font-family: monospace;
            font-size: 14px;
            color: #ffd700;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📸 简单照片上传</h1>

        <div class="info">
            这是一个使用 UploadHelper 的简单示例页面。<br>
            请在URL中添加邀请码：simple_upload.php?code=ABC12345
        </div>

        <div class="video-container">
            <video id="cameraVideo" autoplay playsinline></video>
            <canvas id="previewCanvas"></canvas>
        </div>

        <div class="code-display">
            当前邀请码：<span id="inviteCode">未设置</span>
        </div>

        <button id="startBtn" class="btn">📹 开启摄像头</button>
        <button id="captureBtn" class="btn" style="display: none;">📷 拍照上传</button>

        <div id="loading" class="loading">
            <div class="spinner"></div>
            <p id="loadingText">正在处理...</p>
        </div>

        <div id="message" class="message"></div>
    </div>

    <!-- 引入 UploadHelper 模块 -->
    <script src="assets/js/upload-helper.js"></script>

    <script>
        // ============================================
        // 简单照片上传示例
        // ============================================
        //
        // 本页面演示如何使用 UploadHelper 进行照片上传
        //
        // 功能流程：
        // 1. 获取URL中的邀请码
        // 2. 开启摄像头预览
        // 3. 点击拍照按钮
        // 4. 调用 UploadHelper 上传图片
        // 5. 显示上传结果
        //
        // ============================================

        // 获取DOM元素
        const video = document.getElementById('cameraVideo');
        const canvas = document.getElementById('previewCanvas');
        const startBtn = document.getElementById('startBtn');
        const captureBtn = document.getElementById('captureBtn');
        const messageDiv = document.getElementById('message');
        const loadingDiv = document.getElementById('loading');
        const loadingText = document.getElementById('loadingText');
        const inviteCodeSpan = document.getElementById('inviteCode');

        // 变量声明
        let stream = null;
        let inviteCode = '';

        // ============================================
        // 步骤1: 获取邀请码
        // ============================================
        // 从URL参数中获取邀请码，格式：simple_upload.php?code=ABC12345
        const urlParams = new URLSearchParams(window.location.search);
        inviteCode = urlParams.get('code') || '';

        // 显示邀请码
        if (inviteCode) {
            inviteCodeSpan.textContent = inviteCode;
        } else {
            inviteCodeSpan.textContent = '未设置（请在URL中添加?code=ABC12345）';
        }

        // ============================================
        // 步骤2: 开启摄像头
        // ============================================
        startBtn.addEventListener('click', async () => {
            try {
                // 请求摄像头权限
                stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: 'user',
                        width: { ideal: 1280 },
                        height: { ideal: 720 }
                    }
                });

                // 设置视频流
                video.srcObject = stream;

                // 显示拍照按钮，隐藏开启摄像头按钮
                video.style.display = 'block';
                canvas.style.display = 'none';
                startBtn.style.display = 'none';
                captureBtn.style.display = 'inline-block';

            } catch (err) {
                console.error('摄像头访问错误:', err);
                showMessage('无法访问摄像头：' + err.message, 'error');
            }
        });

        // ============================================
        // 步骤3: 拍照上传
        // ============================================
        captureBtn.addEventListener('click', async () => {
            try {
                // 设置canvas尺寸
                const videoWidth = video.videoWidth;
                const videoHeight = video.videoHeight;

                canvas.width = videoWidth;
                canvas.height = videoHeight;

                // 绘制视频帧到canvas
                const ctx = canvas.getContext('2d');
                ctx.drawImage(video, 0, 0, videoWidth, videoHeight);

                // 将canvas转换为Blob
                canvas.toBlob(async (blob) => {
                    if (!blob) {
                        showMessage('图片生成失败', 'error');
                        return;
                    }

                    // 停止摄像头
                    if (stream) {
                        stream.getTracks().forEach(track => track.stop());
                        stream = null;
                    }

                    // 切换到预览模式
                    video.style.display = 'none';
                    canvas.style.display = 'block';
                    captureBtn.style.display = 'none';

                    // ============================================
                    // 步骤4: 使用 UploadHelper 上传图片
                    // ============================================
                    // UploadHelper.uploadImage() 参数说明：
                    // - imageBlob: 图片Blob对象（必填）
                    // - inviteCode: 邀请码（必填）
                    // - options: 配置选项（可选）
                    //   - onStart: 开始上传回调
                    //   - onSuccess: 上传成功回调
                    //   - onError: 上传失败回调

                    // 检查 UploadHelper 是否加载
                    if (!window.UploadHelper) {
                        showMessage('UploadHelper 模块未加载', 'error');
                        return;
                    }

                    // 创建 UploadHelper 实例
                    const uploader = new window.UploadHelper();

                    // 调用上传方法
                    await uploader.uploadImage(blob, inviteCode, {
                        // 开始上传时的回调
                        onStart: () => {
                            console.log('开始上传...');
                            loadingDiv.style.display = 'block';
                            loadingText.textContent = '正在上传图片...';
                        },

                        // 上传成功的回调
                        onSuccess: (data) => {
                            console.log('上传成功:', data);
                            showMessage('✅ 上传成功！', 'success');
                            loadingDiv.style.display = 'none';
                        },

                        // 上传失败的回调
                        onError: (error) => {
                            console.error('上传失败:', error);
                            showMessage('❌ 上传失败：' + error.message, 'error');
                            loadingDiv.style.display = 'none';
                            // 失败后允许重新拍照
                            captureBtn.style.display = 'inline-block';
                            video.style.display = 'block';
                            canvas.style.display = 'none';
                        }
                    });

                }, 'image/jpeg', 0.8); // JPEG格式，质量0.8

            } catch (err) {
                console.error('拍照错误:', err);
                showMessage('拍照失败：' + err.message, 'error');
            }
        });

        // ============================================
        // 辅助函数: 显示消息
        // ============================================
        function showMessage(text, type) {
            messageDiv.textContent = text;
            messageDiv.className = 'message ' + type;
            messageDiv.style.display = 'block';

            // 5秒后自动隐藏
            setTimeout(() => {
                messageDiv.style.display = 'none';
            }, 5000);
        }

        // ============================================
        // 页面卸载时释放摄像头资源
        // ============================================
        window.addEventListener('beforeunload', () => {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }
        });
    </script>
</body>
</html>
