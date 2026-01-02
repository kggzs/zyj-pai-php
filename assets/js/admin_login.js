const form = document.getElementById('loginForm');
const messageDiv = document.getElementById('message');

function showMessage(text, type) {
    messageDiv.innerHTML = `<div class="message message-${type}">${text}</div>`;
}

form.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(form);
    
    try {
        const response = await fetch('api/admin_login.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage('登录成功！正在跳转...', 'success');
            setTimeout(() => {
                window.location.href = 'admin.php';
            }, 1500);
        } else {
            showMessage(data.message || '登录失败', 'error');
        }
    } catch (err) {
        showMessage('登录失败，请重试', 'error');
    }
});

