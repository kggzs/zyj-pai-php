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
    
    // 获取提交按钮
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn ? submitBtn.textContent : '';
    
    // 防止重复提交：禁用提交按钮
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = '注册中...';
    }
    
    // 检查是否同意用户协议
    const agreeTerms = document.getElementById('agreeTerms');
    if (!agreeTerms || !agreeTerms.checked) {
        showMessage('请先阅读并同意《用户服务协议》', 'error');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = originalBtnText;
        }
        return;
    }
    
    const formData = new FormData(form);
    
    if (inviteCode) {
        formData.append('invite_code', inviteCode);
    }
    
    // 添加协议同意标记
    formData.append('agree_terms', '1');
    
    try {
        const response = await fetch('api/register.php', {
            method: 'POST',
            body: formData
        });
        
        // 检查响应状态
        if (!response.ok) {
            // 处理429错误（请求过于频繁）
            if (response.status === 429) {
                // 尝试从响应头获取重置时间
                const resetTime = response.headers.get('X-RateLimit-Reset');
                const remaining = response.headers.get('X-RateLimit-Remaining');
                
                let errorMessage = '请求过于频繁，请稍后再试';
                
                // 如果有重置时间，计算剩余等待时间
                if (resetTime) {
                    const resetTimestamp = parseInt(resetTime);
                    const now = Math.floor(Date.now() / 1000);
                    const waitSeconds = Math.max(0, resetTimestamp - now);
                    
                    if (waitSeconds > 0) {
                        const waitMinutes = Math.ceil(waitSeconds / 60);
                        errorMessage = `请求过于频繁，请等待约 ${waitMinutes} 分钟后再试`;
                    }
                }
                
                // 尝试解析JSON响应获取更详细的错误信息
                try {
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/json')) {
                        const data = await response.json();
                        if (data.message) {
                            errorMessage = data.message;
                        }
                        if (data.retry_after !== undefined) {
                            const waitMinutes = Math.ceil(data.retry_after / 60);
                            errorMessage = `请求过于频繁，请等待约 ${waitMinutes} 分钟后再试`;
                        }
                    }
                } catch (parseErr) {
                    // 如果解析失败，使用默认错误信息
                }
                
                showMessage(errorMessage, 'error');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalBtnText;
                }
                return;
            }
            
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
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = originalBtnText;
            }
        }
    } catch (err) {
        console.error('注册错误：', err);
        showMessage('注册失败：' + (err.message || '请重试'), 'error');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = originalBtnText;
        }
    }
});

// 显示用户协议
function showUserAgreement() {
    const modal = document.getElementById('agreementModal');
    const content = document.getElementById('agreementContent');
    
    if (!modal || !content) {
        // 如果模态框不存在，在新窗口打开
        window.open('terms/user_agreement.php', '_blank', 'width=900,height=700,scrollbars=yes');
        return;
    }
    
    // 加载协议内容
    fetch('terms/user_agreement.php')
        .then(res => {
            if (!res.ok) {
                throw new Error('HTTP ' + res.status);
            }
            return res.text();
        })
        .then(html => {
            // 提取样式和body内容
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            
            // 提取head中的style标签
            const styleTags = doc.head.querySelectorAll('style');
            let styles = '';
            styleTags.forEach(style => {
                styles += style.outerHTML;
            });
            
            // 提取body内容
            const bodyContent = doc.body.innerHTML;
            
            // 组合样式和内容
            content.innerHTML = styles + bodyContent;
            modal.style.display = 'block';
            
            // 滚动到顶部
            const modalContent = modal.querySelector('.modal-content');
            if (modalContent) {
                modalContent.scrollTop = 0;
            }
        })
        .catch(err => {
            console.error('加载协议失败:', err);
            content.innerHTML = '<div style="text-align: center; padding: 40px;"><p style="color: red; margin-bottom: 20px;">加载协议失败，请刷新页面重试</p><p style="color: #999; font-size: 14px;">错误信息：' + err.message + '</p><button onclick="window.open(\'terms/user_agreement.php\', \'_blank\')" class="btn" style="width: auto; padding: 10px 20px; margin-top: 20px;">在新窗口打开协议</button></div>';
            modal.style.display = 'block';
        });
}

// 关闭协议模态框
function closeAgreementModal() {
    const modal = document.getElementById('agreementModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// 同意协议
function acceptAgreement() {
    const agreeTerms = document.getElementById('agreeTerms');
    if (agreeTerms) {
        agreeTerms.checked = true;
    }
    closeAgreementModal();
}

// 点击模态框外部关闭
document.addEventListener('DOMContentLoaded', function() {
    const agreementModal = document.getElementById('agreementModal');
    if (agreementModal) {
        agreementModal.addEventListener('click', function(e) {
            if (e.target === agreementModal) {
                closeAgreementModal();
            }
        });
    }
});

// 页面加载时获取配置
loadRegisterConfig();

// 更新密码要求显示
function updateRequirementsDisplay(requirements) {
    const requirementsList = document.getElementById('requirementsList');
    if (!requirementsList) return;
    
    requirementsList.innerHTML = requirements.map(req => {
        const icon = req.met ? '✓' : '○';
        const color = req.met ? '#00C851' : '#999';
        return `<div style="color: ${color}; margin-bottom: 3px;">
            <span style="margin-right: 5px; font-weight: bold;">${icon}</span>
            ${req.text}
        </div>`;
    }).join('');
}

// 加载密码要求
async function loadPasswordRequirements() {
    try {
        const response = await fetch('api/get_password_strength.php?password=');
        const data = await response.json();
        if (data.success && data.requirements) {
            updateRequirementsDisplay(data.requirements);
        }
    } catch (err) {
        console.error('加载密码要求失败:', err);
    }
}

// 检查密码强度
async function checkPasswordStrength(password) {
    const strengthDiv = document.getElementById('passwordStrength');
    const requirementsList = document.getElementById('requirementsList');
    
    if (!password) {
        if (strengthDiv) {
            strengthDiv.style.display = 'none';
        }
        // 显示初始要求（未满足状态）
        try {
            const response = await fetch('api/get_password_strength.php?password=');
            const data = await response.json();
            if (data.success && data.requirements) {
                updateRequirementsDisplay(data.requirements);
            }
        } catch (err) {
            console.error('加载密码要求失败:', err);
        }
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('password', password);
        
        const response = await fetch('api/get_password_strength.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            // 更新要求显示
            if (data.requirements) {
                updateRequirementsDisplay(data.requirements);
            }
            
            // 更新强度显示
            if (strengthDiv) {
                const strengthBarFill = document.getElementById('strengthBarFill');
                const strengthText = document.getElementById('strengthText');
                
                if (strengthBarFill && strengthText) {
                    strengthDiv.style.display = 'block';
                    
                    const level = data.level || 0;
                    const text = data.text || '未知';
                    
                    // 设置进度条
                    const percentages = [0, 25, 50, 75, 100];
                    const colors = ['#ff4444', '#ff8800', '#ffbb33', '#00C851', '#007E33'];
                    const percentage = percentages[level] || 0;
                    const color = colors[level] || '#999';
                    
                    strengthBarFill.style.width = percentage + '%';
                    strengthBarFill.style.background = color;
                    strengthText.textContent = text;
                    strengthText.style.color = color;
                }
            }
        }
    } catch (err) {
        console.error('检查密码强度失败:', err);
    }
}

// 监听密码输入
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('passwordInput');
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            checkPasswordStrength(this.value);
        });
        
        // 加载密码要求
        loadPasswordRequirements();
    }
});

