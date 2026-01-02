-- 为照片表添加软删除字段
ALTER TABLE `photos` ADD COLUMN `deleted_at` DATETIME DEFAULT NULL COMMENT '删除时间（软删除）' AFTER `upload_time`;

-- 添加索引以提高查询性能
ALTER TABLE `photos` ADD INDEX `idx_deleted_at` (`deleted_at`);

