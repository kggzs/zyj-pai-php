-- 创建管理员账号示例
-- 注意：将 'admin' 和 'your_password' 替换为你想要的管理员账号和密码

-- 方式1：将现有用户设置为管理员
-- UPDATE users SET is_admin = 1 WHERE username = 'your_username';

-- 方式2：创建新的管理员账号
-- 密码需要使用 password_hash() 函数加密，这里提供一个PHP脚本示例：
-- 
-- <?php
-- $password = 'your_password'; // 替换为你的密码
-- $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
-- echo $hashedPassword;
-- ?>
-- 
-- 然后将生成的hash值插入到下面的SQL中：

-- INSERT INTO users (username, password, register_ip, register_ua, register_time, last_login_time, status, is_admin, points) 
-- VALUES ('admin', 'your_hashed_password', '127.0.0.1', 'Admin Setup', NOW(), NOW(), 1, 1, 0);

