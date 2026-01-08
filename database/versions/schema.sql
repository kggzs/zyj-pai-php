-- 数据库创建
CREATE DATABASE IF NOT EXISTS `xiaochuo` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `xiaochuo`;

-- 用户表
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '用户ID',
  `username` VARCHAR(50) NOT NULL COMMENT '账号',
  `password` VARCHAR(255) NOT NULL COMMENT '加密密码',
  `register_ip` VARCHAR(50) DEFAULT NULL COMMENT '注册IP',
  `register_ua` TEXT DEFAULT NULL COMMENT '注册时浏览器信息',
  `register_time` DATETIME NOT NULL COMMENT '注册时间',
  `last_login_time` DATETIME DEFAULT NULL COMMENT '上次登录时间',
  `last_login_ip` VARCHAR(50) DEFAULT NULL COMMENT '上次登录IP',
  `status` TINYINT(1) DEFAULT 1 COMMENT '账号状态：1-正常，0-禁用',
  `points` INT(11) DEFAULT 0 COMMENT '积分总额',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表';

-- 邀请链接表
CREATE TABLE IF NOT EXISTS `invites` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '邀请ID',
  `invite_code` VARCHAR(100) NOT NULL COMMENT '邀请码',
  `user_id` INT(11) UNSIGNED NOT NULL COMMENT '用户A ID',
  `invite_url` TEXT NOT NULL COMMENT '邀请链接',
  `create_time` DATETIME NOT NULL COMMENT '生成时间',
  `expire_time` DATETIME NOT NULL COMMENT '有效期',
  `status` TINYINT(1) DEFAULT 1 COMMENT '状态：1-有效，0-失效',
  `upload_count` INT(11) DEFAULT 0 COMMENT '上传数量',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invite_code` (`invite_code`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  KEY `expire_time` (`expire_time`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='邀请链接表';

-- 照片表
CREATE TABLE IF NOT EXISTS `photos` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '照片ID',
  `invite_id` INT(11) UNSIGNED NOT NULL COMMENT '邀请ID',
  `invite_code` VARCHAR(100) NOT NULL COMMENT '邀请码',
  `user_id` INT(11) UNSIGNED NOT NULL COMMENT '用户A ID',
  `original_path` VARCHAR(500) NOT NULL COMMENT '原图路径',
  `result_path` VARCHAR(500) NOT NULL COMMENT '结果图路径',
  `upload_ip` VARCHAR(50) DEFAULT NULL COMMENT '上传IP',
  `upload_time` DATETIME NOT NULL COMMENT '上传时间',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `invite_id` (`invite_id`),
  KEY `invite_code` (`invite_code`),
  KEY `user_id` (`user_id`),
  KEY `upload_time` (`upload_time`),
  FOREIGN KEY (`invite_id`) REFERENCES `invites` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='照片表';

-- 积分变动明细表
CREATE TABLE IF NOT EXISTS `points_log` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '记录ID',
  `user_id` INT(11) UNSIGNED NOT NULL COMMENT '用户A ID',
  `type` VARCHAR(20) NOT NULL COMMENT '变动类型：register_reward-注册奖励',
  `points` INT(11) NOT NULL COMMENT '变动数量（正数为增加，负数为减少）',
  `invite_code` VARCHAR(100) DEFAULT NULL COMMENT '关联邀请码',
  `new_user_id` INT(11) UNSIGNED DEFAULT NULL COMMENT '关联新用户ID',
  `remark` VARCHAR(500) DEFAULT NULL COMMENT '备注',
  `create_time` DATETIME NOT NULL COMMENT '变动时间',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `invite_code` (`invite_code`),
  KEY `new_user_id` (`new_user_id`),
  KEY `create_time` (`create_time`),
  UNIQUE KEY `unique_reward` (`invite_code`, `new_user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`new_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='积分变动明细表';
