const video = document.getElementById('video');
const preview = document.getElementById('preview');
const startBtn = document.getElementById('startBtn');
const recordBtn = document.getElementById('recordBtn');
const uploadBtn = document.getElementById('uploadBtn');
const messageDiv = document.getElementById('message');
const loadingDiv = document.getElementById('loading');
const registerLink = document.getElementById('registerLink');
const registerBtn = document.getElementById('registerBtn');

let stream = null;
let mediaRecorder = null;
let recordedChunks = [];
let inviteCode = '';
let recordedBlob = null;

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
    recordBtn.disabled = true;
    uploadBtn.disabled = true;
}

// 获取录像最大时长（秒）
let maxVideoDuration = 15; // 默认15秒
// 从系统配置获取录像时长（通过公开API）
fetch('api/get_register_config.php')
    .then(res => res.json())
    .then(data => {
        if (data.success && data.data && data.data['video_max_duration']) {
            maxVideoDuration = parseInt(data.data['video_max_duration']) || 15;
        }
    })
    .catch(() => {
        // 如果获取失败，使用默认值
    });

startBtn.addEventListener('click', async () => {
    const browser = detectBrowser();
    
    // 更新按钮状态
    startBtn.disabled = true;
    startBtn.textContent = '正在请求权限...';
    
    try {
        // 构建约束对象（针对不同浏览器优化）
        // 只请求视频权限，不请求音频权限
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
        recordBtn.style.display = 'block';
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

let recordingTimer = null;

recordBtn.addEventListener('click', () => {
    if (!stream) {
        return;
    }
    
    // 如果正在录像，则停止
    if (mediaRecorder && mediaRecorder.state === 'recording') {
        mediaRecorder.stop();
        if (recordingTimer) {
            clearTimeout(recordingTimer);
            recordingTimer = null;
        }
        recordBtn.textContent = '停止录像';
        recordBtn.disabled = false;
        return;
    }
    
    // 开始录像
    recordedChunks = [];
    
    // 尝试使用不同的MIME类型，优先使用VP8编码（文件更小）
    // 只录制视频，不包含音频
    let mimeType = 'video/webm;codecs=vp8';
    let videoBitsPerSecond = 1000000; // 1Mbps，降低码率以减小文件体积
    
    if (!MediaRecorder.isTypeSupported(mimeType)) {
        mimeType = 'video/webm;codecs=vp9';
        videoBitsPerSecond = 800000; // VP9编码效率更高，可以更低码率
    }
    if (!MediaRecorder.isTypeSupported(mimeType)) {
        mimeType = 'video/webm';
        videoBitsPerSecond = 1200000; // 默认编码，稍高码率
    }
    if (!MediaRecorder.isTypeSupported(mimeType)) {
        mimeType = 'video/mp4';
        videoBitsPerSecond = 1500000; // MP4编码，需要更高码率
    }
    
    // 创建MediaRecorder，只录制视频，不录制音频
    const options = {
        mimeType: mimeType,
        videoBitsPerSecond: videoBitsPerSecond
    };
    
    mediaRecorder = new MediaRecorder(stream, options);
    
    mediaRecorder.ondataavailable = (event) => {
        if (event.data && event.data.size > 0) {
            recordedChunks.push(event.data);
        }
    };
    
    mediaRecorder.onstop = () => {
        recordedBlob = new Blob(recordedChunks, { type: mimeType });
        preview.src = URL.createObjectURL(recordedBlob);
        preview.style.display = 'block';
        video.style.display = 'none';
        
        // 停止摄像头
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }
        
        recordBtn.style.display = 'none';
        uploadBtn.style.display = 'block';
    };
    
    mediaRecorder.start();
    recordBtn.textContent = '录像中...';
    recordBtn.disabled = false; // 允许点击停止
    
    // 设置最大录像时长
    recordingTimer = setTimeout(() => {
        if (mediaRecorder && mediaRecorder.state === 'recording') {
            mediaRecorder.stop();
            recordBtn.textContent = '停止录像';
            recordBtn.disabled = false;
            showMessage(`录像时长已达到最大限制（${maxVideoDuration}秒）`, 'info');
        }
    }, maxVideoDuration * 1000);
});

uploadBtn.addEventListener('click', async () => {
    if (!recordedBlob || !inviteCode) {
        showMessage('请先完成录像', 'error');
        return;
    }
    
    await uploadVideo();
});

// 注册按钮点击事件
registerBtn.addEventListener('click', () => {
    if (inviteCode) {
        window.location.href = 'register.php?code=' + inviteCode;
    } else {
        window.location.href = 'register.php';
    }
});

// 上传录像函数
async function uploadVideo() {
    if (!recordedBlob || !inviteCode) {
        showMessage('请先完成录像', 'error');
        loadingDiv.style.display = 'none';
        return;
    }
    
    try {
        // 检查文件大小（降低限制到20MB，因为已经优化了码率）
        const maxSize = 20 * 1024 * 1024; // 20MB
        if (recordedBlob.size > maxSize) {
            showMessage('录像文件过大，请缩短录像时长', 'error');
            return;
        }
        
        // 直接使用FormData发送二进制Blob（不再转换为Base64）
        const formData = new FormData();
        formData.append('video', recordedBlob, 'record.webm');
        formData.append('invite_code', inviteCode);
        
        loadingDiv.style.display = 'block';
        loadingDiv.textContent = '正在上传...';
        uploadBtn.style.display = 'none';
        
        const response = await fetch('api/upload_video.php', {
            method: 'POST',
            body: formData
        });
        
        // 检查响应状态
        if (!response.ok) {
            const text = await response.text();
            console.error('上传失败，HTTP状态:', response.status);
            console.error('响应内容:', text);
            showMessage('上传失败：服务器错误 ' + response.status, 'error');
            loadingDiv.style.display = 'none';
            uploadBtn.style.display = 'block';
            return;
        }
        
        // 获取响应文本
        const text = await response.text();
        console.log('服务器响应:', text);
        
        // 尝试解析JSON
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('JSON解析失败:', e);
            console.error('响应内容:', text);
            showMessage('服务器返回格式错误，请检查控制台', 'error');
            loadingDiv.style.display = 'none';
            uploadBtn.style.display = 'block';
            return;
        }
        
        if (data.success) {
            showMessage('上传成功！', 'success');
            // 显示注册按钮
            registerBtn.style.display = 'block';
            loadingDiv.style.display = 'none';
            preview.style.display = 'none';
        } else {
            showMessage(data.message || '上传失败', 'error');
            loadingDiv.style.display = 'none';
            uploadBtn.style.display = 'block';
        }
    } catch (err) {
        console.error('上传错误:', err);
        showMessage('上传失败，请重试', 'error');
        loadingDiv.style.display = 'none';
        uploadBtn.style.display = 'block';
    }
}

// 已移除blobToBase64函数，直接使用二进制上传

