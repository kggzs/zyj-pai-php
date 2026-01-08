-- 添加VIP到期时间字段
ALTER TABLE `users` ADD COLUMN `vip_expire_time` DATETIME NULL COMMENT 'VIP到期时间（NULL表示永久VIP）' AFTER `is_vip`;

