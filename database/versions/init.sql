-- ============================================
-- 拍摄上传系统 - 完整数据库初始化脚本
-- ============================================
-- 说明：此文件包含所有数据库表结构和字段
-- 适用于全新安装，直接执行即可
-- 如果数据库已存在，请先备份数据
-- ============================================

-- 创建数据库
CREATE DATABASE IF NOT EXISTS `xiaochuo` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `xiaochuo`;

-- ============================================
-- 1. 用户表（包含所有扩展字段）
-- ============================================

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '用户ID',
  `username` VARCHAR(50) NOT NULL COMMENT '账号',
  `nickname` VARCHAR(50) DEFAULT NULL COMMENT '昵称',
  `password` VARCHAR(255) NOT NULL COMMENT '加密密码',
  `email` VARCHAR(100) DEFAULT NULL COMMENT '邮箱',
  `email_verified` TINYINT(1) DEFAULT 0 COMMENT '邮箱是否已验证：1-已验证，0-未验证',
  `email_notify_photo` TINYINT(1) DEFAULT 0 COMMENT '收到照片时是否邮件提醒：1-是，0-否',
  `register_code` VARCHAR(6) DEFAULT NULL COMMENT '注册码（用于邀请用户注册，6位）',
  `register_ip` VARCHAR(50) DEFAULT NULL COMMENT '注册IP',
  `register_ua` TEXT DEFAULT NULL COMMENT '注册时浏览器信息',
  `register_time` DATETIME NOT NULL COMMENT '注册时间',
  `last_login_time` DATETIME DEFAULT NULL COMMENT '上次登录时间',
  `last_login_ip` VARCHAR(50) DEFAULT NULL COMMENT '上次登录IP',
  `status` TINYINT(1) DEFAULT 1 COMMENT '账号状态：1-正常，0-禁用',
  `is_admin` TINYINT(1) DEFAULT 0 COMMENT '是否管理员：1-是，0-否',
  `points` INT(11) DEFAULT 0 COMMENT '积分总额',
  `is_vip` TINYINT(1) DEFAULT 0 COMMENT '是否VIP用户：1-是，0-否',
  `vip_expire_time` DATETIME NULL COMMENT 'VIP到期时间（NULL表示永久VIP）',
  `invite_quota_bonus` INT(11) DEFAULT 0 COMMENT '额外邀请链接配额（通过积分兑换获得）',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `register_code` (`register_code`),
  KEY `status` (`status`),
  KEY `is_admin` (`is_admin`),
  KEY `idx_register_time` (`register_time`),
  KEY `idx_last_login_time` (`last_login_time`),
  KEY `idx_status_register_time` (`status`, `register_time`),
  KEY `idx_is_vip` (`is_vip`),
  KEY `idx_vip_expire_time` (`vip_expire_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表';

-- ============================================
-- 2. 邀请链接表
-- ============================================

CREATE TABLE IF NOT EXISTS `invites` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '邀请ID',
  `invite_code` VARCHAR(8) NOT NULL COMMENT '拍摄链接码（8位）',
  `user_id` INT(11) UNSIGNED NOT NULL COMMENT '用户ID',
  `invite_url` TEXT NOT NULL COMMENT '邀请链接',
  `create_time` DATETIME NOT NULL COMMENT '生成时间',
  `expire_time` DATETIME NULL COMMENT '有效期（NULL表示无限制）',
  `status` TINYINT(1) DEFAULT 1 COMMENT '状态：1-有效，0-失效',
  `upload_count` INT(11) DEFAULT 0 COMMENT '上传数量',
  `label` VARCHAR(50) DEFAULT NULL COMMENT '拍摄链接标签',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invite_code` (`invite_code`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  KEY `expire_time` (`expire_time`),
  KEY `idx_user_status_expire` (`user_id`, `status`, `expire_time`),
  KEY `idx_create_time` (`create_time`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='邀请链接表';

-- ============================================
-- 3. 照片表（包含所有扩展字段）
-- ============================================

CREATE TABLE IF NOT EXISTS `photos` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '照片ID',
  `invite_id` INT(11) UNSIGNED NOT NULL COMMENT '邀请ID',
  `invite_code` VARCHAR(8) NOT NULL COMMENT '拍摄链接码（8位）',
  `user_id` INT(11) UNSIGNED NOT NULL COMMENT '用户ID',
  `original_path` VARCHAR(500) NOT NULL COMMENT '原图路径',
  `result_path` VARCHAR(500) NOT NULL COMMENT '结果图路径',
  `file_type` VARCHAR(10) DEFAULT 'photo' COMMENT '文件类型：photo-照片，video-录像',
  `video_duration` INT(11) DEFAULT NULL COMMENT '录像时长（秒）',
  `upload_ip` VARCHAR(50) DEFAULT NULL COMMENT '上传IP',
  `upload_ua` VARCHAR(500) DEFAULT NULL COMMENT '上传浏览器信息',
  `upload_time` DATETIME NOT NULL COMMENT '上传时间',
  `deleted_at` DATETIME DEFAULT NULL COMMENT '删除时间（软删除）',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `invite_id` (`invite_id`),
  KEY `invite_code` (`invite_code`),
  KEY `user_id` (`user_id`),
  KEY `upload_time` (`upload_time`),
  KEY `idx_deleted_at` (`deleted_at`),
  KEY `idx_file_type` (`file_type`),
  KEY `idx_file_type_upload_time` (`file_type`, `upload_time`),
  KEY `idx_user_upload_time` (`user_id`, `upload_time`),
  KEY `idx_invite_upload_time` (`invite_code`, `upload_time`),
  KEY `idx_deleted_at_upload_time` (`deleted_at`, `upload_time`),
  KEY `idx_upload_time_date` (`upload_time`),
  FOREIGN KEY (`invite_id`) REFERENCES `invites` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='照片表';

-- ============================================
-- 4. 积分变动明细表（已修复唯一索引）
-- ============================================

CREATE TABLE IF NOT EXISTS `points_log` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '记录ID',
  `user_id` INT(11) UNSIGNED NOT NULL COMMENT '用户ID',
  `type` VARCHAR(20) NOT NULL COMMENT '变动类型：invite_reward-邀请奖励，checkin_reward-签到奖励，admin_adjust-管理员调整',
  `points` INT(11) NOT NULL COMMENT '变动数量（正数为增加，负数为减少）',
  `invite_code` VARCHAR(100) DEFAULT NULL COMMENT '关联邀请码（可能是注册码6位或拍摄链接码8位）',
  `new_user_id` INT(11) UNSIGNED DEFAULT NULL COMMENT '关联新用户ID',
  `remark` VARCHAR(500) DEFAULT NULL COMMENT '备注',
  `create_time` DATETIME NOT NULL COMMENT '变动时间',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `invite_code` (`invite_code`),
  KEY `new_user_id` (`new_user_id`),
  KEY `create_time` (`create_time`),
  KEY `idx_user_create_time` (`user_id`, `create_time`),
  KEY `idx_type_create_time` (`type`, `create_time`),
  UNIQUE KEY `unique_invite_reward` (`user_id`, `invite_code`, `new_user_id`, `remark`(50)),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`new_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='积分变动明细表';

-- ============================================
-- 5. 标签相关表
-- ============================================

-- 标签表
CREATE TABLE IF NOT EXISTS `tags` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '标签ID',
  `name` VARCHAR(50) NOT NULL COMMENT '标签名称',
  `user_id` INT(11) UNSIGNED NOT NULL COMMENT '创建标签的用户ID',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `name` (`name`),
  KEY `idx_user_name` (`user_id`, `name`),
  UNIQUE KEY `user_tag_unique` (`user_id`, `name`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='标签表';

-- 照片标签关联表
CREATE TABLE IF NOT EXISTS `photo_tags` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '关联ID',
  `photo_id` INT(11) UNSIGNED NOT NULL COMMENT '照片ID',
  `tag_id` INT(11) UNSIGNED NOT NULL COMMENT '标签ID',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `photo_id` (`photo_id`),
  KEY `tag_id` (`tag_id`),
  KEY `idx_photo_tag` (`photo_id`, `tag_id`),
  UNIQUE KEY `photo_tag_unique` (`photo_id`, `tag_id`),
  FOREIGN KEY (`photo_id`) REFERENCES `photos` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='照片标签关联表';

-- ============================================
-- 6. 签到相关表
-- ============================================

-- 签到记录表
CREATE TABLE IF NOT EXISTS `checkins` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '签到ID',
  `user_id` INT(11) UNSIGNED NOT NULL COMMENT '用户ID',
  `checkin_date` DATE NOT NULL COMMENT '签到日期',
  `consecutive_days` INT(11) DEFAULT 1 COMMENT '连续签到天数',
  `points_earned` INT(11) NOT NULL COMMENT '获得积分',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_date_unique` (`user_id`, `checkin_date`),
  KEY `user_id` (`user_id`),
  KEY `checkin_date` (`checkin_date`),
  KEY `idx_user_checkin_date` (`user_id`, `checkin_date`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='签到记录表';

-- ============================================
-- 7. 用户个人资料相关表
-- ============================================

-- 邮箱验证码表
CREATE TABLE IF NOT EXISTS `email_verification_codes` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` INT(11) UNSIGNED NOT NULL COMMENT '用户ID',
  `email` VARCHAR(100) NOT NULL COMMENT '邮箱地址',
  `code` VARCHAR(10) NOT NULL COMMENT '验证码',
  `type` VARCHAR(20) NOT NULL DEFAULT 'verify' COMMENT '类型：verify-验证邮箱，email-注册验证，reset-重置密码',
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

-- 登录日志表
CREATE TABLE IF NOT EXISTS `login_logs` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` INT(11) UNSIGNED DEFAULT NULL COMMENT '用户ID（登录失败时为NULL）',
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
  KEY `idx_user_login_time` (`user_id`, `login_time`),
  KEY `idx_login_ip` (`login_ip`),
  KEY `idx_success_login_time` (`is_success`, `login_time`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='登录日志表';

-- ============================================
-- 8. 系统配置表
-- ============================================

-- 系统配置表
CREATE TABLE IF NOT EXISTS `system_config` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `config_key` VARCHAR(100) NOT NULL UNIQUE COMMENT '配置键',
  `config_value` TEXT COMMENT '配置值',
  `description` VARCHAR(255) COMMENT '配置说明',
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  INDEX `idx_config_key` (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统配置表';

-- 插入默认配置
INSERT INTO `system_config` (`config_key`, `config_value`, `description`) VALUES
('register_require_email', '0', '注册时是否要求填写邮箱：1-必填，0-可选'),
('register_require_email_verification', '0', '注册时是否强制邮箱验证：1-必须验证后才能登录，0-不需要验证'),
('points_checkin_reward', '5', '每日签到基础奖励积分'),
('points_checkin_vip_bonus', '3', 'VIP用户签到额外奖励积分'),
('points_checkin_consecutive_bonus', '{"3": 5, "7": 10, "15": 20, "30": 50}', '普通用户连续签到额外奖励（JSON格式：天数=>奖励积分）'),
('points_checkin_vip_consecutive_bonus', '{"3": 8, "7": 15, "15": 30, "30": 80}', 'VIP用户连续签到额外奖励（JSON格式：天数=>奖励积分）'),
('points_register_reward', '10', '新用户注册奖励积分'),
('points_invite_reward', '5', '邀请新用户注册奖励积分（邀请人）'),
('points_invite_new_user_reward', '5', '通过邀请码注册奖励积分（新用户）'),
('points_upload_reward', '2', '上传照片/视频奖励积分')
ON DUPLICATE KEY UPDATE `config_value` = VALUES(`config_value`);

-- ============================================
-- 9. 异常行为记录表和管理员操作日志表
-- ============================================

-- 异常行为记录表
CREATE TABLE IF NOT EXISTS `abnormal_behavior_logs` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` INT(11) UNSIGNED DEFAULT NULL COMMENT '用户ID（如果涉及用户）',
  `behavior_type` VARCHAR(50) NOT NULL COMMENT '行为类型：unusual_login-异常登录，multiple_failed_login-多次失败登录，suspicious_activity-可疑活动等',
  `description` TEXT DEFAULT NULL COMMENT '行为描述',
  `ip_address` VARCHAR(50) DEFAULT NULL COMMENT 'IP地址',
  `user_agent` TEXT DEFAULT NULL COMMENT '浏览器信息',
  `request_url` VARCHAR(500) DEFAULT NULL COMMENT '请求URL',
  `request_data` TEXT DEFAULT NULL COMMENT '请求数据（JSON格式）',
  `severity` TINYINT(1) DEFAULT 1 COMMENT '严重程度：1-低，2-中，3-高',
  `is_handled` TINYINT(1) DEFAULT 0 COMMENT '是否已处理：1-已处理，0-未处理',
  `handled_by` INT(11) UNSIGNED DEFAULT NULL COMMENT '处理人ID（管理员）',
  `handled_time` DATETIME DEFAULT NULL COMMENT '处理时间',
  `handled_note` TEXT DEFAULT NULL COMMENT '处理备注',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `behavior_type` (`behavior_type`),
  KEY `severity` (`severity`),
  KEY `is_handled` (`is_handled`),
  KEY `created_at` (`created_at`),
  KEY `idx_user_created_at` (`user_id`, `created_at`),
  KEY `idx_behavior_type_handled_created` (`behavior_type`, `is_handled`, `created_at`),
  KEY `idx_ip_address` (`ip_address`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`handled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='异常行为记录表';

-- 管理员操作日志表
CREATE TABLE IF NOT EXISTS `admin_operation_logs` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `admin_id` INT(11) UNSIGNED NOT NULL COMMENT '管理员ID',
  `admin_username` VARCHAR(50) NOT NULL COMMENT '管理员用户名',
  `operation_type` VARCHAR(50) NOT NULL COMMENT '操作类型：user_ban-封禁用户，user_unban-解封用户，points_adjust-调整积分，vip_set-设置VIP，config_update-更新配置，backup_create-创建备份，backup_restore-恢复备份等',
  `target_type` VARCHAR(50) DEFAULT NULL COMMENT '目标类型：user-用户，config-配置，system-系统等',
  `target_id` INT(11) UNSIGNED DEFAULT NULL COMMENT '目标ID',
  `description` TEXT DEFAULT NULL COMMENT '操作描述',
  `ip_address` VARCHAR(50) DEFAULT NULL COMMENT '操作IP',
  `user_agent` TEXT DEFAULT NULL COMMENT '浏览器信息',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `admin_id` (`admin_id`),
  KEY `operation_type` (`operation_type`),
  KEY `created_at` (`created_at`),
  KEY `idx_admin_operation_time` (`admin_id`, `created_at`),
  KEY `idx_operation_type_created` (`operation_type`, `created_at`),
  FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员操作日志表';

-- ============================================
-- 10. 积分商城相关表
-- ============================================

-- 积分商品表
CREATE TABLE IF NOT EXISTS `points_shop` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '商品ID',
  `name` VARCHAR(100) NOT NULL COMMENT '商品名称',
  `description` TEXT DEFAULT NULL COMMENT '商品描述',
  `type` VARCHAR(50) NOT NULL COMMENT '商品类型：vip_temporary-临时VIP, vip_permanent-永久VIP, invite_limit-邀请链接数量',
  `points_price` INT(11) NOT NULL COMMENT '所需积分',
  `value` INT(11) DEFAULT NULL COMMENT '商品数值（如VIP天数、邀请链接数量等）',
  `status` TINYINT(1) DEFAULT 1 COMMENT '状态：1-上架，0-下架',
  `total_stock` INT(11) DEFAULT NULL COMMENT '总库存（NULL表示不限）',
  `sold_count` INT(11) DEFAULT 0 COMMENT '已兑换数量',
  `max_per_user` INT(11) DEFAULT NULL COMMENT '每个用户最多可兑换次数（NULL表示不限）',
  `sort_order` INT(11) DEFAULT 0 COMMENT '排序顺序（数字越大越靠前）',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `sort_order` (`sort_order`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='积分商品表';

-- 积分兑换记录表
CREATE TABLE IF NOT EXISTS `points_exchange_log` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '记录ID',
  `user_id` INT(11) UNSIGNED NOT NULL COMMENT '用户ID',
  `shop_id` INT(11) UNSIGNED NOT NULL COMMENT '商品ID',
  `shop_name` VARCHAR(100) NOT NULL COMMENT '商品名称（冗余字段，防止商品删除后无法查看）',
  `points_cost` INT(11) NOT NULL COMMENT '消耗积分',
  `status` VARCHAR(20) DEFAULT 'completed' COMMENT '状态：completed-已完成, failed-失败',
  `result` TEXT DEFAULT NULL COMMENT '兑换结果（JSON格式，存储具体的奖励信息）',
  `exchange_time` DATETIME NOT NULL COMMENT '兑换时间',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `shop_id` (`shop_id`),
  KEY `exchange_time` (`exchange_time`),
  KEY `status` (`status`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`shop_id`) REFERENCES `points_shop` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='积分兑换记录表';

-- ============================================
-- 11. 系统公告相关表
-- ============================================

-- 系统公告表
CREATE TABLE IF NOT EXISTS `announcements` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '公告ID',
  `title` VARCHAR(255) NOT NULL COMMENT '公告标题',
  `content` TEXT NOT NULL COMMENT '公告内容',
  `level` ENUM('important', 'normal', 'notice') NOT NULL DEFAULT 'normal' COMMENT '公告级别：important-重要, normal-一般, notice-通知',
  `require_read` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否强制已读：1-是，0-否',
  `is_visible` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '是否显示：1-是，0-否',
  `admin_id` INT(11) UNSIGNED NOT NULL COMMENT '发布管理员ID',
  `create_time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `admin_id` (`admin_id`),
  KEY `is_visible` (`is_visible`),
  KEY `create_time` (`create_time`),
  FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统公告表';

-- 用户公告已读状态表
CREATE TABLE IF NOT EXISTS `user_announcements` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` INT(11) UNSIGNED NOT NULL COMMENT '用户ID',
  `announcement_id` INT(11) UNSIGNED NOT NULL COMMENT '公告ID',
  `is_read` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否已读：1-已读，0-未读',
  `read_time` DATETIME NULL DEFAULT NULL COMMENT '阅读时间',
  `create_time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_announcement` (`user_id`, `announcement_id`),
  KEY `user_id` (`user_id`),
  KEY `announcement_id` (`announcement_id`),
  KEY `is_read` (`is_read`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户公告已读状态表';

-- ============================================
-- 初始化完成
-- ============================================
SELECT '数据库初始化完成！' AS message;
