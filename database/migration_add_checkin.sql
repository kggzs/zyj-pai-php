-- 创建签到表
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
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='签到记录表';

