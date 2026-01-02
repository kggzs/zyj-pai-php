-- ============================================
-- 为邀请码表添加标签字段
-- ============================================
-- 说明：此脚本为invites表添加label字段，用于标记邀请码
-- 执行时间：2024-01-XX
-- ============================================

USE `xiaochuo`;

-- 添加标签字段
ALTER TABLE `invites` 
ADD COLUMN `label` VARCHAR(50) DEFAULT NULL COMMENT '邀请码标签' AFTER `invite_code`;

-- 添加索引以便按标签查询
ALTER TABLE `invites` 
ADD KEY `idx_label` (`label`);

SELECT '邀请码标签字段添加完成！' AS message;

