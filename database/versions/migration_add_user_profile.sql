-- 添加用户个人资料字段
ALTER TABLE `users` 
ADD COLUMN `nickname` VARCHAR(50) DEFAULT NULL COMMENT '昵称' AFTER `username`,
ADD COLUMN `email` VARCHAR(100) DEFAULT NULL COMMENT '邮箱' AFTER `nickname`,
ADD COLUMN `email_verified` TINYINT(1) DEFAULT 0 COMMENT '邮箱是否已验证：1-已验证，0-未验证' AFTER `email`,
ADD COLUMN `email_notify_photo` TINYINT(1) DEFAULT 0 COMMENT '收到照片时是否邮件提醒：1-是，0-否' AFTER `email_verified`;

-- 添加邮箱唯一索引
ALTER TABLE `users` ADD UNIQUE KEY `email` (`email`);

-- 创建邮箱验证码表
CREATE TABLE IF NOT EXISTS `email_verification_codes` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` INT(11) UNSIGNED NOT NULL COMMENT '用户ID',
  `email` VARCHAR(100) NOT NULL COMMENT '邮箱地址',
  `code` VARCHAR(10) NOT NULL COMMENT '验证码',
  `type` VARCHAR(20) NOT NULL DEFAULT 'verify' COMMENT '类型：verify-验证邮箱，reset-重置密码',
  `expire_time` DATETIME NOT NULL COMMENT '过期时间',
  `used` TINYINT(1) DEFAULT 0 COMMENT '是否已使用：1-已使用，0-未使用',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `email` (`email`),
  KEY `code` (`code`),
  KEY `expire_time` (`expire_time`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='邮箱验证码表';

-- 创建登录日志表
CREATE TABLE IF NOT EXISTS `login_logs` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` INT(11) UNSIGNED NOT NULL COMMENT '用户ID',
  `login_ip` VARCHAR(50) NOT NULL COMMENT '登录IP',
  `login_ua` TEXT DEFAULT NULL COMMENT '登录时浏览器信息',
  `login_time` DATETIME NOT NULL COMMENT '登录时间',
  `is_success` TINYINT(1) DEFAULT 1 COMMENT '是否成功：1-成功，0-失败',
  `fail_reason` VARCHAR(200) DEFAULT NULL COMMENT '失败原因',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `login_time` (`login_time`),
  KEY `login_ip` (`login_ip`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='登录日志表';

