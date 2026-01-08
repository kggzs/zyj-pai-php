-- 添加照片表的浏览器信息字段
ALTER TABLE `photos` ADD COLUMN `upload_ua` VARCHAR(500) DEFAULT NULL COMMENT '上传浏览器信息' AFTER `upload_ip`;

