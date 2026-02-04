const video = document.getElementById('video');
const canvas = document.getElementById('canvas');
const preview = document.getElementById('preview');
const startBtn = document.getElementById('startBtn');
const captureBtn = document.getElementById('captureBtn');
const messageDiv = document.getElementById('message');
const loadingDiv = document.getElementById('loading');
const registerLink = document.getElementById('registerLink');
const registerBtn = document.getElementById('registerBtn');

let stream = null;
let inviteCode = '';
let capturedImage = null;

// 检测浏览器类型
function detectBrowser() {
    const ua = navigator.userAgent;
    return {
        isMobile: /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(ua),
        isIOS: /iPad|iPhone|iPod/.test(ua),
        isAndroid: /Android/i.test(ua),
        isQQBrowser: /MQQBrowser|QQBrowser|QQ\//i.test(ua),
        isWeChat: /MicroMessenger/i.test(ua),
        isUCBrowser: /UCBrowser/i.test(ua),
        isSafari: /Safari/i.test(ua) && !/Chrome|CriOS|FxiOS|OPiOS|mercury/i.test(ua),
        isChrome: /Chrome/i.test(ua) && !/OPR|Edge|Edg/i.test(ua)
    };
}

// 获取用户媒体的兼容性函数
function getUserMedia(constraints) {
    // 优先使用标准的 mediaDevices API
    if (typeof navigator !== 'undefined' && 
        navigator.mediaDevices && 
        typeof navigator.mediaDevices.getUserMedia === 'function') {
        return navigator.mediaDevices.getUserMedia(constraints);
    }
    
    // 降级到旧版 API（带前缀）
    const legacyGetUserMedia = navigator.getUserMedia || 
                              navigator.webkitGetUserMedia || 
                              navigator.mozGetUserMedia || 
                              navigator.msGetUserMedia;
    
    if (legacyGetUserMedia) {
        return new Promise((resolve, reject) => {
            legacyGetUserMedia.call(navigator, constraints, resolve, reject);
        });
    }
    
    // 都不支持，返回拒绝的 Promise
    return Promise.reject(new Error('您的浏览器不支持摄像头访问功能'));
}

// 获取URL中的邀请码
const urlParams = new URLSearchParams(window.location.search);
inviteCode = urlParams.get('code') || '';

// 设置注册链接
if (inviteCode) {
    registerLink.href = 'register.php?code=' + inviteCode;
}

// 验证邀请码
if (inviteCode) {
    fetch(`api/validate_invite.php?code=${inviteCode}`)
        .then(res => res.json())
        .then(data => {
            if (!data.valid) {
                showMessage(data.message || '邀请链接无效', 'error');
                disableAll();
            }
        });
} else {
    showMessage('邀请链接无效', 'error');
    disableAll();
}

function showMessage(text, type) {
    messageDiv.innerHTML = `<div class="message message-${type}">${text}</div>`;
    setTimeout(() => {
        messageDiv.innerHTML = '';
    }, 5000);
}

function disableAll() {
    startBtn.disabled = true;
    captureBtn.disabled = true;
}

startBtn.addEventListener('click', async () => {
    const browser = detectBrowser();
    
    // 更新按钮状态
    startBtn.disabled = true;
    startBtn.textContent = '正在请求权限...';
    
    try {
        // 构建约束对象（针对不同浏览器优化）
        let constraints = {
            video: { 
                facingMode: 'user',
                width: browser.isMobile ? { ideal: 640, max: 1280 } : { ideal: 1280 },
                height: browser.isMobile ? { ideal: 480, max: 720 } : { ideal: 720 }
            }
        };
        
        // iOS Safari 特殊优化
        if (browser.isIOS) {
            constraints.video.frameRate = { ideal: 30, max: 30 };
        }
        
        // QQ浏览器和微信浏览器：使用更简化的约束
        if (browser.isQQBrowser || browser.isWeChat) {
            constraints = {
                video: {
                    facingMode: 'user'
                }
            };
        }
        
        // 使用兼容性函数获取摄像头
        stream = await getUserMedia(constraints);
        
        // 设置视频流
        if (video.srcObject !== undefined) {
            video.srcObject = stream;
        } else if (video.mozSrcObject !== undefined) {
            video.mozSrcObject = stream;
        } else if (window.URL && window.URL.createObjectURL) {
            video.src = window.URL.createObjectURL(stream);
        }
        
        // iOS Safari 特殊处理
        video.setAttribute('playsinline', 'true');
        video.setAttribute('webkit-playsinline', 'true');
        video.setAttribute('x5-playsinline', 'true');
        video.muted = true; // iOS Safari 需要静音才能自动播放
        
        video.style.display = 'block';
        
        // 等待视频元数据加载后再播放
        video.onloadedmetadata = () => {
            video.play().catch(err => {
                console.error('视频播放失败:', err);
                showMessage('无法播放视频，请刷新页面重试', 'error');
            });
        };
        
        startBtn.style.display = 'none';
        captureBtn.style.display = 'block';
    } catch (err) {
        console.error('摄像头访问错误:', err);
        startBtn.disabled = false;
        startBtn.textContent = '开启摄像头';
        
        let errorMsg = '无法访问摄像头';
        
        if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
            if (browser.isQQBrowser) {
                errorMsg = '请在QQ浏览器设置中允许摄像头权限（设置 > 隐私与安全 > 摄像头权限）';
            } else if (browser.isIOS) {
                errorMsg = '请在Safari设置中允许摄像头权限（设置 > Safari > 摄像头）';
            } else if (browser.isAndroid) {
                errorMsg = '请在浏览器设置中允许摄像头权限';
            } else {
                errorMsg = '请允许访问摄像头权限';
            }
        } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
            errorMsg = '未检测到摄像头设备';
        } else if (err.name === 'NotReadableError' || err.name === 'TrackStartError') {
            errorMsg = '摄像头被其他应用占用，请关闭其他应用后重试';
        } else if (err.name === 'OverconstrainedError' || err.name === 'ConstraintNotSatisfiedError') {
            errorMsg = '摄像头不支持所需设置';
        } else if (err.message && err.message.includes('HTTPS')) {
            errorMsg = '需要 HTTPS 协议访问';
        }
        
        showMessage(errorMsg, 'error');
    }
});

captureBtn.addEventListener('click', async () => {
    // 获取实际视频尺寸
    const videoWidth = video.videoWidth;
    const videoHeight = video.videoHeight;
    
    // 限制最大分辨率到1280x720，如果原图更大则缩放
    const maxWidth = 1280;
    const maxHeight = 720;
    
    let canvasWidth = videoWidth;
    let canvasHeight = videoHeight;
    
    // 如果超过最大尺寸，按比例缩放
    if (canvasWidth > maxWidth || canvasHeight > maxHeight) {
        const ratio = Math.min(maxWidth / canvasWidth, maxHeight / canvasHeight);
        canvasWidth = Math.round(canvasWidth * ratio);
        canvasHeight = Math.round(canvasHeight * ratio);
    }
    
    canvas.width = canvasWidth;
    canvas.height = canvasHeight;
    
    // 使用高质量缩放绘制
    const ctx = canvas.getContext('2d');
    ctx.imageSmoothingEnabled = true;
    ctx.imageSmoothingQuality = 'high';
    ctx.drawImage(video, 0, 0, canvasWidth, canvasHeight);
    
    // 将canvas转换为Blob（二进制），降低初始质量到0.7以减小文件体积
    canvas.toBlob(async (blob) => {
        if (!blob) {
            showMessage('图片生成失败', 'error');
            return;
        }
        
        // 压缩图片（返回Blob）
        capturedImage = await compressImageBlob(blob);
        
        // 停止摄像头并清理资源
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }
        
        // 清理video元素的srcObject
        if (video && video.srcObject) {
            video.srcObject = null;
        }
        
        // 隐藏视频和拍照按钮
        video.style.display = 'none';
        captureBtn.style.display = 'none';
        
        // 显示加载提示
        loadingDiv.style.display = 'block';
        loadingDiv.textContent = '正在上传...';
        
        // 自动上传
        await uploadImage();
    }, 'image/jpeg', 0.7); // 降低质量到0.7以减小文件体积
});

// 注册按钮点击事件
registerBtn.addEventListener('click', () => {
    if (inviteCode) {
        window.location.href = 'register.php?code=' + inviteCode;
    } else {
        window.location.href = 'register.php';
    }
});

// 上传图片函数
async function uploadImage() {
    if (!capturedImage || !inviteCode) {
        showMessage('请先拍摄照片', 'error');
        loadingDiv.style.display = 'none';
        return;
    }

    // 使用 UploadHelper 上传
    if (!window.UploadHelper) {
        showMessage('上传模块加载失败', 'error');
        loadingDiv.style.display = 'none';
        startBtn.style.display = 'block';
        return;
    }

    const uploader = new window.UploadHelper();

    try {
        await uploader.uploadImage(capturedImage, inviteCode, {
            onStart: () => {
                loadingDiv.style.display = 'block';
                loadingDiv.textContent = '正在上传...';
            },
            onSuccess: (data) => {
                showMessage('上传成功！', 'success');
                // 显示注册按钮
                registerBtn.style.display = 'block';
                loadingDiv.style.display = 'none';
            },
            onError: (error) => {
                showMessage(error.message || '上传失败，请重试', 'error');
                loadingDiv.style.display = 'none';
                // 显示重新开始按钮
                startBtn.style.display = 'block';
            }
        });
    } catch (err) {
        console.error('上传错误:', err);
        showMessage('上传失败，请重试', 'error');
        loadingDiv.style.display = 'none';
        // 显示重新开始按钮
        startBtn.style.display = 'block';
    }
}

// 图片压缩（返回Blob）- 优化版本，降低分辨率和质量以减小文件体积
async function compressImageBlob(blob, maxSize = 1 * 1024 * 1024) { // 降低最大文件大小到1MB
    return new Promise((resolve) => {
        const img = new Image();
        const url = URL.createObjectURL(blob);
        
        img.onload = () => {
            const canvas = document.createElement('canvas');
            let width = img.width;
            let height = img.height;
            
            // 限制最大尺寸到1280x720，进一步减小文件体积
            const maxWidth = 1280;
            const maxHeight = 720;
            if (width > maxWidth || height > maxHeight) {
                const ratio = Math.min(maxWidth / width, maxHeight / height);
                width = Math.round(width * ratio);
                height = Math.round(height * ratio);
            }
            
            canvas.width = width;
            canvas.height = height;
            const ctx = canvas.getContext('2d');
            // 启用高质量缩放
            ctx.imageSmoothingEnabled = true;
            ctx.imageSmoothingQuality = 'high';
            ctx.drawImage(img, 0, 0, width, height);
            
            // 释放URL对象
            URL.revokeObjectURL(url);
            
            // 转换为Blob，初始质量0.7（降低质量以减小文件体积）
            let quality = 0.7;
            canvas.toBlob((compressedBlob) => {
                if (!compressedBlob) {
                    resolve(blob); // 如果转换失败，返回原blob
                    return;
                }
                
                // 如果仍然太大，逐步降低质量
                if (compressedBlob.size > maxSize && quality > 0.3) {
                    quality -= 0.1;
                    canvas.toBlob((newBlob) => {
                        resolve(newBlob || compressedBlob);
                    }, 'image/jpeg', quality);
                } else {
                    resolve(compressedBlob);
                }
            }, 'image/jpeg', quality);
        };
        
        img.onerror = () => {
            URL.revokeObjectURL(url);
            resolve(blob); // 如果加载失败，返回原blob
        };
        
        img.src = url;
    });
}

// 页面卸载时释放摄像头资源
function releaseCamera() {
    console.log('释放摄像头资源...');
    
    // 停止所有摄像头轨道
    if (stream) {
        try {
            stream.getTracks().forEach(track => {
                track.stop();
                console.log('停止摄像头轨道:', track.kind, track.label);
            });
        } catch (err) {
            console.error('停止摄像头轨道失败:', err);
        }
        stream = null;
    }
    
    // 清理video元素
    if (video) {
        try {
            video.pause();
            video.srcObject = null;
            video.src = '';
            video.load(); // 重置video元素
            console.log('清理video元素');
        } catch (err) {
            console.error('清理video元素失败:', err);
        }
    }
}

// 监听页面关闭/卸载事件，确保释放摄像头资源
window.addEventListener('beforeunload', releaseCamera);
window.addEventListener('unload', releaseCamera);
window.addEventListener('pagehide', releaseCamera);

// 监听页面隐藏事件（移动端切换应用时）
document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        console.log('页面隐藏，释放摄像头资源');
        releaseCamera();
    }
});

