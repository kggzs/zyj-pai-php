-- 修改邀请链接表，允许expire_time为NULL（表示无限制）
ALTER TABLE `invites` MODIFY COLUMN `expire_time` DATETIME NULL COMMENT '有效期（NULL表示无限制）';

-- 添加用户VIP字段（可选，用于判断VIP用户）
ALTER TABLE `users` ADD COLUMN `is_vip` TINYINT(1) DEFAULT 0 COMMENT '是否VIP用户：1-是，0-否' AFTER `points`;

