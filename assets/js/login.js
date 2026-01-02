const form = document.getElementById('loginForm');
const messageDiv = document.getElementById('message');

function showMessage(text, type) {
    messageDiv.innerHTML = `<div class="message message-${type}">${text}</div>`;
}

form.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(form);
    
    try {
        const response = await fetch('api/login.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage('登录成功！正在跳转...', 'success');
            setTimeout(() => {
                window.location.href = 'dashboard.php';
            }, 1500);
        } else {
            // 检查是否需要邮箱验证
            if (data.require_verification && data.verify_token) {
                showMessage(data.message + '，正在跳转到验证页面...', 'error');
                // 2秒后跳转到验证页面，使用token而不是username
                setTimeout(() => {
                    window.location.href = 'verify_email_page.php?token=' + encodeURIComponent(data.verify_token);
                }, 2000);
            } else {
                showMessage(data.message || '登录失败', 'error');
            }
        }
    } catch (err) {
        showMessage('登录失败，请重试', 'error');
    }
});

