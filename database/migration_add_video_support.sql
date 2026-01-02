-- 添加录像支持
-- 1. 在photos表中添加文件类型字段
ALTER TABLE `photos` ADD COLUMN `file_type` VARCHAR(10) DEFAULT 'photo' COMMENT '文件类型：photo-照片，video-录像' AFTER `result_path`;

-- 2. 添加录像时长字段（秒）
ALTER TABLE `photos` ADD COLUMN `video_duration` INT(11) DEFAULT NULL COMMENT '录像时长（秒）' AFTER `file_type`;

-- 3. 添加索引
ALTER TABLE `photos` ADD KEY `idx_file_type` (`file_type`);
ALTER TABLE `photos` ADD KEY `idx_file_type_upload_time` (`file_type`, `upload_time`);

