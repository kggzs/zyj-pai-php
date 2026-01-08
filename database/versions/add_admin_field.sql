-- 添加管理员字段
ALTER TABLE `users` ADD COLUMN `is_admin` TINYINT(1) DEFAULT 0 COMMENT '是否管理员：1-是，0-否' AFTER `status`;

-- 创建管理员索引
ALTER TABLE `users` ADD KEY `is_admin` (`is_admin`);

