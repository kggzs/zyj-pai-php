-- 创建标签表
CREATE TABLE IF NOT EXISTS `tags` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '标签ID',
  `name` VARCHAR(50) NOT NULL COMMENT '标签名称',
  `user_id` INT(11) UNSIGNED NOT NULL COMMENT '创建标签的用户ID',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `name` (`name`),
  UNIQUE KEY `user_tag_unique` (`user_id`, `name`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='标签表';

-- 创建照片标签关联表
CREATE TABLE IF NOT EXISTS `photo_tags` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '关联ID',
  `photo_id` INT(11) UNSIGNED NOT NULL COMMENT '照片ID',
  `tag_id` INT(11) UNSIGNED NOT NULL COMMENT '标签ID',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `photo_id` (`photo_id`),
  KEY `tag_id` (`tag_id`),
  UNIQUE KEY `photo_tag_unique` (`photo_id`, `tag_id`),
  FOREIGN KEY (`photo_id`) REFERENCES `photos` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='照片标签关联表';

