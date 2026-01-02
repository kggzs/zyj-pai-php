const form = document.getElementById('registerForm');
const messageDiv = document.getElementById('message');
const container = document.querySelector('.container');

// 获取URL中的注册码或拍摄链接码
const urlParams = new URLSearchParams(window.location.search);
const inviteCode = urlParams.get('code') || '';

let registerConfig = {
    require_email: false,
    require_email_verification: false
};

let pendingEmail = '';

// 加载注册配置
async function loadRegisterConfig() {
    try {
        const response = await fetch('api/get_register_config.php');
        const data = await response.json();
        if (data.success) {
            registerConfig = data.data;
            updateRegisterForm();
        }
    } catch (err) {
        console.error('加载注册配置失败:', err);
    }
}

// 更新注册表单
function updateRegisterForm() {
    const emailLabel = document.getElementById('emailLabel');
    const emailInput = document.getElementById('emailInput');
    
    if (emailLabel && emailInput) {
        // 如果开启了强制邮箱验证，邮箱自动变为必填
        if (registerConfig.require_email_verification) {
            emailLabel.innerHTML = '邮箱 <span style="color: red;">*</span>';
            emailInput.required = true;
            emailInput.placeholder = '请输入邮箱地址';
        } else {
            emailLabel.textContent = '邮箱（可选）';
            emailInput.required = false;
            emailInput.placeholder = '请输入邮箱地址（可选）';
        }
    }
}

function showMessage(text, type) {
    messageDiv.innerHTML = `<div class="message message-${type}">${text}</div>`;
}

// 显示邮箱验证界面
function showVerificationForm(email) {
    pendingEmail = email;
    const emailMask = email.replace(/(.{2})(.*)(@.*)/, '$1****$3');
    
    container.innerHTML = `
        <h1>验证邮箱</h1>
        <div id="message"></div>
        <div class="verification-info">
            <p>我们已向您的邮箱 <strong>${emailMask}</strong> 发送了验证码</p>
            <p style="color: #666; font-size: 14px; margin-top: 10px;">请查收邮件并输入验证码完成注册</p>
        </div>
        <form id="verificationForm">
            <div class="form-group">
                <label>验证码</label>
                <input type="text" name="code" id="verificationCode" placeholder="请输入6位验证码" maxlength="6" required>
            </div>
            <button type="submit" class="btn">验证邮箱</button>
            <button type="button" class="btn btn-secondary" onclick="resendVerificationCode()" style="margin-top: 10px; background: #6c757d;">重新发送验证码</button>
        </form>
        <div class="login-link" style="margin-top: 20px;">
            <a href="register.php${inviteCode ? '?code=' + inviteCode : ''}">返回注册</a>
        </div>
    `;
    
    // 绑定验证表单提交事件
    document.getElementById('verificationForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        await verifyEmail();
    });
    
    // 自动聚焦验证码输入框
    document.getElementById('verificationCode').focus();
}

// 验证邮箱
async function verifyEmail() {
    const code = document.getElementById('verificationCode').value.trim();
    const messageDiv = document.getElementById('message');
    
    if (!code || code.length !== 6) {
        showMessage('请输入6位验证码', 'error');
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('code', code);
        
        const response = await fetch('api/verify_register_email.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage('邮箱验证成功！正在跳转到登录页面...', 'success');
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 2000);
        } else {
            showMessage(data.message || '验证失败', 'error');
        }
    } catch (err) {
        showMessage('验证失败，请重试', 'error');
    }
}

// 重新发送验证码
async function resendVerificationCode() {
    const messageDiv = document.getElementById('message');
    const btn = event.target;
    const originalText = btn.textContent;
    
    btn.disabled = true;
    btn.textContent = '发送中...';
    
    try {
        const response = await fetch('api/resend_register_verification.php', {
            method: 'POST'
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage('验证码已重新发送，请查收', 'success');
            // 60秒后才能再次发送
            let countdown = 60;
            const timer = setInterval(() => {
                btn.textContent = `重新发送(${countdown}秒)`;
                countdown--;
                if (countdown < 0) {
                    clearInterval(timer);
                    btn.disabled = false;
                    btn.textContent = originalText;
                }
            }, 1000);
        } else {
            showMessage(data.message || '发送失败', 'error');
            btn.disabled = false;
            btn.textContent = originalText;
        }
    } catch (err) {
        showMessage('发送失败，请重试', 'error');
        btn.disabled = false;
        btn.textContent = originalText;
    }
}

form.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(form);
    
    if (inviteCode) {
        formData.append('invite_code', inviteCode);
    }
    
    try {
        const response = await fetch('api/register.php', {
            method: 'POST',
            body: formData
        });
        
        // 检查响应状态
        if (!response.ok) {
            throw new Error('网络请求失败：' + response.status);
        }
        
        // 检查响应内容类型
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('非JSON响应：', text);
            throw new Error('服务器返回格式错误');
        }
        
        const data = await response.json();
        
        if (data.success) {
            if (data.require_verification) {
                // 需要验证邮箱，显示验证界面
                const emailInput = form.querySelector('input[name="email"]');
                showVerificationForm(emailInput.value);
            } else {
                // 不需要验证，直接跳转
                showMessage('注册成功！正在跳转...', 'success');
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 2000);
            }
        } else {
            showMessage(data.message || '注册失败', 'error');
        }
    } catch (err) {
        console.error('注册错误：', err);
        showMessage('注册失败：' + (err.message || '请重试'), 'error');
    }
});

// 页面加载时获取配置
loadRegisterConfig();

