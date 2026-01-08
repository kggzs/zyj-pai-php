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
  KEY `create_time` (`create_time`)
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
  KEY `is_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户公告已读状态表';

