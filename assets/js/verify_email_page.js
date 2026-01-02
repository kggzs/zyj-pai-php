const usernameForm = document.getElementById('usernameForm');
const verificationForm = document.getElementById('verificationForm');
const usernameFormContainer = document.getElementById('usernameFormContainer');
const verificationFormContainer = document.getElementById('verificationFormContainer');
const messageDiv = document.getElementById('message');
const resendBtn = document.getElementById('resendBtn');
const emailInputGroup = document.getElementById('emailInputGroup');
const emailInput = document.getElementById('emailInput');
const submitBtn = document.getElementById('submitBtn');
const verifyTokenInput = document.getElementById('verifyToken');
const verifyUserIdInput = document.getElementById('verifyUserId');
const verifyUsernameInput = document.getElementById('verifyUsername');

let currentUsername = '';
let currentEmail = '';
let needEmail = false;
let verifyToken = '';
let verifyUserId = '';
let verifyUsername = '';

// 初始化：从隐藏字段获取token和用户信息
if (verifyTokenInput) {
    verifyToken = verifyTokenInput.value;
    verifyUserId = verifyUserIdInput ? verifyUserIdInput.value : '';
    verifyUsername = verifyUsernameInput ? verifyUsernameInput.value : '';
    currentUsername = verifyUsername;
    
    // 不再自动发送验证码，需要用户手动点击按钮
    // 这样可以避免token无效时仍然发送邮件
}

function showMessage(text, type) {
    messageDiv.innerHTML = `<div class="message message-${type}">${text}</div>`;
}

// 用户名表单提交（如果表单存在）
if (usernameForm) {
    usernameForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        // 如果需要邮箱，检查是否已输入
        if (needEmail) {
            const email = emailInput.value.trim();
            if (!email) {
                showMessage('请输入邮箱地址', 'error');
                emailInput.focus();
                return;
            }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                showMessage('邮箱格式不正确', 'error');
                emailInput.focus();
                return;
            }
        }
        
        await sendVerificationCode(verifyUsername);
    });
}

// 验证表单提交
verificationForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const code = document.getElementById('verificationCode').value.trim();
    
    if (!code || code.length !== 6) {
        showMessage('请输入6位验证码', 'error');
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('username', verifyUsername);
        formData.append('code', code);
        formData.append('token', verifyToken);
        
        const response = await fetch('api/verify_email_for_unverified.php', {
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
});

// 重新发送验证码
resendBtn.addEventListener('click', async () => {
    const btn = resendBtn;
    const originalText = btn.textContent;
    
    btn.disabled = true;
    btn.textContent = '发送中...';
    
    try {
        const formData = new FormData();
        formData.append('username', verifyUsername);
        formData.append('token', verifyToken);
        
        // 如果之前需要邮箱，重新发送时不需要再传邮箱（因为已经绑定了）
        
        const response = await fetch('api/send_verification_for_unverified.php', {
            method: 'POST',
            body: formData
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
});

// 不再使用URL参数，改为使用token

// 发送验证码函数
async function sendVerificationCode(username) {
    try {
        const formData = new FormData();
        formData.append('username', username);
        formData.append('token', verifyToken);
        
        // 如果需要邮箱，添加邮箱参数
        if (needEmail && emailInput) {
            const email = emailInput.value.trim();
            if (email) {
                formData.append('email', email);
            }
        }
        
        const response = await fetch('api/send_verification_for_unverified.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            // API已经返回掩码后的邮箱，直接使用
            const emailMask = data.email || '';
            document.getElementById('emailDisplay').textContent = emailMask;
            
            // 显示验证表单
            usernameFormContainer.style.display = 'none';
            verificationFormContainer.style.display = 'block';
            
            // 自动聚焦验证码输入框
            document.getElementById('verificationCode').focus();
            
            showMessage('验证码已发送，请查收', 'success');
        } else {
            // 检查是否需要输入邮箱
            if (data.need_email) {
                needEmail = true;
                emailInputGroup.style.display = 'block';
                emailInput.required = true;
                submitBtn.textContent = '添加邮箱并发送验证码';
                emailInput.focus();
                showMessage(data.message || '请先输入邮箱地址', 'error');
            } else {
                showMessage(data.message || '发送失败', 'error');
            }
        }
    } catch (err) {
        showMessage('发送失败，请重试', 'error');
    }
}

