# 📤 UploadHelper 使用说明

> `upload-helper.js` 是一个独立的图片/视频上传助手模块，封装了与后端 API 的上传交互逻辑，支持图片压缩、上传状态管理和错误处理。

---

## 📋 目录

- [功能概述](#功能概述)
- [快速开始](#快速开始)
- [API 参考](#api-参考)
- [simple_upload.php 演示模板](#simple_uploadphp-演示模板)
- [使用示例](#使用示例)
- [常见问题](#常见问题)

---

## 功能概述

### ✨ 主要功能

| 功能 | 说明 |
|:---:|:---|
| 📷 **图片上传** | 支持 Blob 对象上传，自动调用 `api/upload.php` |
| 🎥 **视频上传** | 支持视频 Blob 上传，自动调用 `api/upload_video.php` |
| 🗜️ **图片压缩** | 自动压缩图片，支持自定义尺寸和质量 |
| 🔄 **状态管理** | 防止重复上传，管理上传状态 |
| ❌ **错误处理** | 完整的错误处理和回调机制 |

### 📁 文件位置

```
assets/js/upload-helper.js    # 上传助手模块
simple_upload.php             # 演示模板页面
```

---

## 快速开始

### 1. 引入模块

```html
<!-- 在页面中引入 UploadHelper -->
<script src="assets/js/upload-helper.js"></script>
```

### 2. 创建实例

```javascript
// 创建上传助手实例
const uploader = new UploadHelper();
```

### 3. 上传图片

```javascript
// 上传图片示例
const imageBlob = await fetch('photo.jpg').then(r => r.blob());

await uploader.uploadImage(imageBlob, 'ABC12345', {
    onStart: () => {
        console.log('开始上传...');
    },
    onSuccess: (data) => {
        console.log('上传成功:', data);
    },
    onError: (error) => {
        console.error('上传失败:', error);
    }
});
```

---

## API 参考

### UploadHelper 类

#### 构造函数

```javascript
const uploader = new UploadHelper();
```

#### 方法

##### `uploadImage(imageBlob, inviteCode, options)`

上传图片到服务器。

| 参数 | 类型 | 必填 | 说明 |
|:---:|:---:|:---:|:---|
| `imageBlob` | Blob | ✅ | 图片 Blob 对象 |
| `inviteCode` | string | ✅ | 8位拍摄链接码 |
| `options` | Object | ❌ | 配置选项 |

**options 配置：**

| 选项 | 类型 | 说明 |
|:---:|:---:|:---|
| `onStart` | Function | 开始上传回调 |
| `onSuccess` | Function | 上传成功回调，参数为服务器返回数据 |
| `onError` | Function | 上传失败回调，参数为 Error 对象 |

**返回值：**
- `Promise` - 上传完成后的 Promise

**示例：**

```javascript
const uploader = new UploadHelper();

await uploader.uploadImage(blob, 'ABC12345', {
    onStart: () => {
        showLoading('正在上传...');
    },
    onSuccess: (data) => {
        hideLoading();
        alert('上传成功！照片ID: ' + data.photo_id);
    },
    onError: (error) => {
        hideLoading();
        alert('上传失败: ' + error.message);
    }
});
```

---

##### `uploadVideo(videoBlob, inviteCode, options)`

上传视频到服务器。

| 参数 | 类型 | 必填 | 说明 |
|:---:|:---:|:---:|:---|
| `videoBlob` | Blob | ✅ | 视频 Blob 对象 |
| `inviteCode` | string | ✅ | 8位拍摄链接码 |
| `options` | Object | ❌ | 配置选项 |

**options 额外配置：**

| 选项 | 类型 | 默认值 | 说明 |
|:---:|:---:|:---:|:---|
| `maxSize` | number | 20971520 (20MB) | 最大文件大小（字节） |

**示例：**

```javascript
const uploader = new UploadHelper();

await uploader.uploadVideo(videoBlob, 'ABC12345', {
    maxSize: 50 * 1024 * 1024,  // 50MB
    onStart: () => {
        console.log('开始上传视频...');
    },
    onSuccess: (data) => {
        console.log('视频上传成功:', data);
    },
    onError: (error) => {
        console.error('视频上传失败:', error);
    }
});
```

---

##### `compressImage(blob, options)`

压缩图片，返回压缩后的 Blob。

| 参数 | 类型 | 必填 | 说明 |
|:---:|:---:|:---:|:---|
| `blob` | Blob | ✅ | 原始图片 Blob |
| `options` | Object | ❌ | 压缩选项 |

**options 配置：**

| 选项 | 类型 | 默认值 | 说明 |
|:---:|:---:|:---:|:---|
| `maxSize` | number | 1048576 (1MB) | 目标文件大小（字节） |
| `maxWidth` | number | 1280 | 最大宽度 |
| `maxHeight` | number | 720 | 最大高度 |
| `quality` | number | 0.7 | 初始压缩质量 (0-1) |

**返回值：**
- `Promise<Blob>` - 压缩后的 Blob 对象

**示例：**

```javascript
const uploader = new UploadHelper();

// 压缩图片
const compressedBlob = await uploader.compressImage(originalBlob, {
    maxWidth: 1920,
    maxHeight: 1080,
    quality: 0.8,
    maxSize: 2 * 1024 * 1024  // 2MB
});

// 上传压缩后的图片
await uploader.uploadImage(compressedBlob, 'ABC12345', options);
```

---

##### `isUploadingActive()`

检查是否正在上传中。

**返回值：**
- `boolean` - true 表示正在上传

**示例：**

```javascript
const uploader = new UploadHelper();

if (uploader.isUploadingActive()) {
    console.log('正在上传中，请等待...');
}
```

---

## simple_upload.php 演示模板

### 简介

`simple_upload.php` 是一个完整的照片上传演示页面，展示了如何使用 `UploadHelper` 实现：

- 📹 摄像头调用和预览
- 📷 拍照功能
- 📤 图片上传
- ✅ 结果反馈

### 使用方式

在 URL 中添加邀请码参数访问：

```
simple_upload.php?code=ABC12345
```

### 页面结构

```html
<!DOCTYPE html>
<html>
<head>
    <!-- 页面样式 -->
</head>
<body>
    <div class="container">
        <h1>📸 简单照片上传</h1>
        
        <!-- 视频预览区域 -->
        <div class="video-container">
            <video id="cameraVideo" autoplay playsinline></video>
            <canvas id="previewCanvas"></canvas>
        </div>
        
        <!-- 操作按钮 -->
        <button id="startBtn">📹 开启摄像头</button>
        <button id="captureBtn">📷 拍照上传</button>
        
        <!-- 状态显示 -->
        <div id="loading">...</div>
        <div id="message">...</div>
    </div>
    
    <!-- 引入 UploadHelper -->
    <script src="assets/js/upload-helper.js"></script>
    
    <script>
        // 页面逻辑代码
    </script>
</body>
</html>
```

### 核心代码解析

#### 1. 获取邀请码

```javascript
const urlParams = new URLSearchParams(window.location.search);
const inviteCode = urlParams.get('code') || '';
```

#### 2. 开启摄像头

```javascript
const stream = await navigator.mediaDevices.getUserMedia({
    video: {
        facingMode: 'user',
        width: { ideal: 1280 },
        height: { ideal: 720 }
    }
});
video.srcObject = stream;
```

#### 3. 拍照并上传

```javascript
// 将视频帧绘制到 canvas
ctx.drawImage(video, 0, 0, videoWidth, videoHeight);

// 转换为 Blob
canvas.toBlob(async (blob) => {
    // 创建 UploadHelper 实例
    const uploader = new UploadHelper();
    
    // 上传图片
    await uploader.uploadImage(blob, inviteCode, {
        onStart: () => {
            loadingDiv.style.display = 'block';
        },
        onSuccess: (data) => {
            showMessage('✅ 上传成功！', 'success');
        },
        onError: (error) => {
            showMessage('❌ 上传失败：' + error.message, 'error');
        }
    });
}, 'image/jpeg', 0.8);
```

---

## 使用示例

### 完整集成示例

```html
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>照片上传</title>
    <style>
        #video { width: 100%; max-width: 400px; }
        #canvas { display: none; }
        .btn { padding: 10px 20px; margin: 10px; }
    </style>
</head>
<body>
    <h1>拍照上传</h1>
    
    <video id="video" autoplay playsinline></video>
    <canvas id="canvas"></canvas>
    <br>
    
    <button class="btn" onclick="startCamera()">开启摄像头</button>
    <button class="btn" onclick="captureAndUpload()">拍照上传</button>
    
    <div id="status"></div>

    <!-- 1. 引入 UploadHelper -->
    <script src="assets/js/upload-helper.js"></script>
    
    <script>
        let stream = null;
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const status = document.getElementById('status');
        
        // 2. 创建 UploadHelper 实例
        const uploader = new UploadHelper();
        
        // 从 URL 获取邀请码
        const urlParams = new URLSearchParams(window.location.search);
        const inviteCode = urlParams.get('code');
        
        if (!inviteCode) {
            status.innerHTML = '❌ 请在 URL 中添加邀请码：?code=ABC12345';
        }
        
        // 开启摄像头
        async function startCamera() {
            try {
                stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'user' }
                });
                video.srcObject = stream;
                status.innerHTML = '✅ 摄像头已开启';
            } catch (err) {
                status.innerHTML = '❌ 无法访问摄像头: ' + err.message;
            }
        }
        
        // 拍照并上传
        async function captureAndUpload() {
            if (!stream) {
                status.innerHTML = '❌ 请先开启摄像头';
                return;
            }
            
            // 绘制到 canvas
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);
            
            // 转换为 Blob 并上传
            canvas.toBlob(async (blob) => {
                try {
                    // 3. 调用 uploadImage 方法
                    const result = await uploader.uploadImage(blob, inviteCode, {
                        onStart: () => {
                            status.innerHTML = '📤 正在上传...';
                        },
                        onSuccess: (data) => {
                            status.innerHTML = '✅ 上传成功！照片ID: ' + data.photo_id;
                        },
                        onError: (error) => {
                            status.innerHTML = '❌ 上传失败: ' + error.message;
                        }
                    });
                } catch (err) {
                    status.innerHTML = '❌ 错误: ' + err.message;
                }
            }, 'image/jpeg', 0.8);
        }
    </script>
</body>
</html>
```

---

## 常见问题

### Q1: 如何限制上传文件大小？

**图片上传**：使用 `compressImage` 方法预先压缩：

```javascript
const compressedBlob = await uploader.compressImage(blob, {
    maxSize: 500 * 1024  // 限制 500KB
});
await uploader.uploadImage(compressedBlob, inviteCode, options);
```

**视频上传**：使用 `maxSize` 选项：

```javascript
await uploader.uploadVideo(videoBlob, inviteCode, {
    maxSize: 10 * 1024 * 1024  // 限制 10MB
});
```

### Q2: 如何防止重复上传？

UploadHelper 内置了上传状态管理，当 `isUploading` 为 true 时会阻止新的上传请求：

```javascript
// 检查是否正在上传
if (uploader.isUploadingActive()) {
    alert('正在上传中，请稍候...');
    return;
}
```

### Q3: 上传失败如何处理？

`uploadImage` 和 `uploadVideo` 方法会自动处理常见错误：

- 网络错误
- 服务器错误（非 200 状态码）
- JSON 解析错误
- 业务错误（服务器返回 success: false）

所有错误都会通过 `onError` 回调返回。

### Q4: 如何自定义上传接口地址？

目前 UploadHelper 使用固定接口地址：
- 图片上传：`api/upload.php`
- 视频上传：`api/upload_video.php`

如需修改，请直接编辑 `upload-helper.js` 文件中的接口 URL。

### Q5: 支持哪些浏览器？

UploadHelper 基于现代 Web API，支持：

- ✅ Chrome 60+
- ✅ Firefox 60+
- ✅ Safari 12+
- ✅ Edge 79+
- ✅ iOS Safari 12+
- ✅ Chrome Android 60+

---

## 📌 总结

| 特性 | 说明 |
|:---:|:---|
| **简单易用** | 只需几行代码即可完成上传功能 |
| **功能完整** | 支持图片/视频上传、压缩、状态管理 |
| **错误处理** | 完善的错误处理和回调机制 |
| **即拿即用** | 参考 `simple_upload.php` 即可快速集成 |

如需更多帮助，请参考项目文档或查看 `simple_upload.php` 完整代码。
