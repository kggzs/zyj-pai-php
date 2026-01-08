-- ============================================
-- 创建异常行为记录表
-- ============================================
-- 说明：此脚本创建异常行为记录表，用于记录系统检测到的异常行为
-- 执行时间：2024-01-XX
-- ============================================

USE `xiaochuo`;

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
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`handled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='异常行为记录表';

-- 系统操作日志表（管理员操作记录）
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
  FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员操作日志表';

SELECT '异常行为记录表和管理员操作日志表创建完成！' AS message;

