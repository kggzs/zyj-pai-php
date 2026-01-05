<?php
require_once __DIR__ . '/core/autoload.php';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📷</text></svg>">
    <title>用户注册 - <?php echo htmlspecialchars(Helper::getProjectName()); ?></title>
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
    <div class="container">
        <h1>用户注册</h1>
        
        <div id="message"></div>
        
        <form id="registerForm">
            <div class="form-group">
                <label>用户名</label>
                <input type="text" name="username" required maxlength="20" placeholder="3-20个字符，只能包含字母、数字和下划线">
                <p style="font-size: 12px; color: #999; margin-top: 5px;">用户名长度：3-20个字符，只能包含字母、数字和下划线</p>
            </div>
            <div class="form-group">
                <label id="emailLabel">邮箱</label>
                <input type="email" name="email" id="emailInput" placeholder="请输入邮箱地址">
            </div>
            <div class="form-group">
                <label>密码</label>
                <input type="password" name="password" id="passwordInput" required placeholder="请输入密码" oninput="checkPasswordStrength(this.value)">
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
                <label style="display: flex; align-items: flex-start; cursor: pointer;">
                    <input type="checkbox" name="agree_terms" id="agreeTerms" required style="width: auto; margin-right: 8px; margin-top: 3px; cursor: pointer;">
                    <span style="flex: 1; font-size: 14px; line-height: 1.5;">
                        我已阅读并同意
                        <a href="javascript:void(0)" onclick="showUserAgreement()" class="agreement-link">《用户服务协议》</a>
                        <span style="color: red;">*</span>
                    </span>
                </label>
            </div>
            <button type="submit" class="btn">注册</button>
        </form>
        
        <!-- 用户协议模态框 -->
        <div id="agreementModal" class="modal" style="display: none;">
            <div class="modal-content" style="max-width: 800px; max-height: 90vh; overflow-y: auto;">
                <div class="modal-header">
                    <h2>用户服务协议</h2>
                    <span class="close" onclick="closeAgreementModal()">&times;</span>
                </div>
                <div id="agreementContent">
                    <p>加载中...</p>
                </div>
                <div style="margin-top: 30px; text-align: center; padding-top: 20px; border-top: 1px solid #eee;">
                    <button onclick="acceptAgreement()" class="btn" style="width: auto; padding: 10px 30px; margin-right: 10px;">我已阅读并同意</button>
                    <button onclick="closeAgreementModal()" class="btn btn-secondary" style="width: auto; padding: 10px 30px;">关闭</button>
                </div>
            </div>
        </div>
        
        <div class="login-link">
            <a href="login.php">已有账号？立即登录</a>
        </div>
    </div>

    <script src="assets/js/register.js"></script>
</body>
</html>
