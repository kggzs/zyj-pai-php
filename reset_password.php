<?php
require_once __DIR__ . '/core/autoload.php';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📷</text></svg>">
    <title>找回密码 - <?php echo htmlspecialchars(Helper::getProjectName()); ?></title>
    <link rel="stylesheet" href="assets/css/common.css">
    <style>
        body {
            background: linear-gradient(135deg, #87CEEB 0%, #5B9BD5 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .reset-container {
            background: white;
            border-radius: 12px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #666;
            font-weight: 500;
        }
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
        }
        .btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #87CEEB 0%, #5B9BD5 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
        }
        .btn:hover {
            opacity: 0.9;
        }
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .step {
            display: none;
        }
        .step.active {
            display: block;
        }
        .message {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: none;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        .back-link a {
            color: #5B9BD5;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <h1>找回密码</h1>
        
        <div id="message" class="message"></div>
        
        <!-- 步骤1：发送验证码 -->
        <div id="step1" class="step active">
            <div class="form-group">
                <label>邮箱地址</label>
                <input type="email" id="emailInput" class="form-control" placeholder="请输入绑定的邮箱地址" required>
            </div>
            <button class="btn" onclick="sendResetCode()">发送验证码</button>
        </div>
        
        <!-- 步骤2：重置密码 -->
        <div id="step2" class="step">
            <div class="form-group">
                <label>邮箱地址</label>
                <input type="email" id="emailInput2" class="form-control" readonly>
            </div>
            <div class="form-group">
                <label>验证码</label>
                <input type="text" id="codeInput" class="form-control" placeholder="请输入验证码" maxlength="6" required>
            </div>
            <div class="form-group">
                <label>新密码</label>
                <input type="password" id="newPasswordInput" class="form-control" placeholder="请输入新密码" required oninput="checkPasswordStrength(this.value)">
                <div id="passwordRequirements" style="font-size: 12px; color: #666; margin-top: 5px; line-height: 1.6;">
                    <div style="margin-bottom: 5px;"><strong>密码要求：</strong></div>
                    <div id="requirementsList"></div>
                </div>
                <div id="passwordStrength" style="margin-top: 8px; display: none;">
                    <div style="display: flex; align-items: center; margin-bottom: 5px;">
                        <span style="font-size: 12px; color: #666; margin-right: 10px;">密码强度：</span>
                        <div id="strengthBar" style="flex: 1; height: 6px; background: #eee; border-radius: 3px; overflow: hidden;">
                            <div id="strengthBarFill" style="height: 100%; width: 0%; transition: all 0.3s; border-radius: 3px;"></div>
                        </div>
                        <span id="strengthText" style="font-size: 12px; margin-left: 10px; font-weight: bold;"></span>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>确认新密码</label>
                <input type="password" id="confirmPasswordInput" class="form-control" placeholder="请再次输入新密码" required>
            </div>
            <button class="btn" onclick="resetPassword()">重置密码</button>
        </div>
        
        <div class="back-link">
            <a href="login.php">返回登录</a>
        </div>
    </div>
    
    <script>
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
        
        // 页面加载时加载密码要求
        document.addEventListener('DOMContentLoaded', function() {
            loadPasswordRequirements();
        });
        
        function showMessage(text, type) {
            const messageDiv = document.getElementById('message');
            messageDiv.textContent = text;
            messageDiv.className = 'message ' + type;
            messageDiv.style.display = 'block';
            
            if (type === 'success') {
                setTimeout(() => {
                    messageDiv.style.display = 'none';
                }, 3000);
            }
        }
        
        function sendResetCode() {
            const email = document.getElementById('emailInput').value.trim();
            
            if (!email) {
                showMessage('请填写邮箱地址', 'error');
                return;
            }
            
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = '发送中...';
            
            const formData = new FormData();
            formData.append('action', 'send_code');
            formData.append('email', email);
            
            fetch('api/reset_password.php', {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showMessage('验证码已发送到您的邮箱，请查收', 'success');
                        // 切换到步骤2
                        document.getElementById('step1').classList.remove('active');
                        document.getElementById('step2').classList.add('active');
                        document.getElementById('emailInput2').value = email;
                    } else {
                        showMessage('发送失败：' + (data.message || '未知错误'), 'error');
                        btn.disabled = false;
                        btn.textContent = '发送验证码';
                    }
                })
                .catch(err => {
                    console.error('发送验证码错误:', err);
                    showMessage('发送失败，请重试', 'error');
                    btn.disabled = false;
                    btn.textContent = '发送验证码';
                });
        }
        
        function resetPassword() {
            const email = document.getElementById('emailInput2').value.trim();
            const code = document.getElementById('codeInput').value.trim();
            const newPassword = document.getElementById('newPasswordInput').value;
            const confirmPassword = document.getElementById('confirmPasswordInput').value;
            
            if (!email || !code || !newPassword || !confirmPassword) {
                showMessage('请填写完整信息', 'error');
                return;
            }
            
            // 密码强度验证（前端验证，后端会再次验证）
            if (newPassword.length < 8) {
                showMessage('新密码长度至少为8个字符', 'error');
                return;
            }
            
            // 检查是否包含字母和数字
            const hasLetter = /[a-zA-Z]/.test(newPassword);
            const hasNumber = /[0-9]/.test(newPassword);
            if (!hasLetter || !hasNumber) {
                showMessage('密码必须包含至少一个字母和一个数字', 'error');
                return;
            }
            
            if (newPassword !== confirmPassword) {
                showMessage('两次输入的密码不一致', 'error');
                return;
            }
            
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = '重置中...';
            
            const formData = new FormData();
            formData.append('action', 'reset');
            formData.append('email', email);
            formData.append('code', code);
            formData.append('new_password', newPassword);
            
            fetch('api/reset_password.php', {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showMessage('密码重置成功，请使用新密码登录', 'success');
                        setTimeout(() => {
                            window.location.href = 'login.php';
                        }, 2000);
                    } else {
                        showMessage('重置失败：' + (data.message || '未知错误'), 'error');
                        btn.disabled = false;
                        btn.textContent = '重置密码';
                    }
                })
                .catch(err => {
                    console.error('重置密码错误:', err);
                    showMessage('重置失败，请重试', 'error');
                    btn.disabled = false;
                    btn.textContent = '重置密码';
                });
        }
    </script>
</body>
</html>

