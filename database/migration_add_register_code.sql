-- 添加注册码字段到用户表
ALTER TABLE `users` 
ADD COLUMN `register_code` VARCHAR(6) DEFAULT NULL COMMENT '注册码（用于邀请用户注册，6位）' AFTER `email_notify_photo`,
ADD UNIQUE KEY `register_code` (`register_code`);

-- 为现有用户生成注册码（如果还没有）
-- 注意：这个更新语句需要在应用层执行，因为需要生成唯一码

