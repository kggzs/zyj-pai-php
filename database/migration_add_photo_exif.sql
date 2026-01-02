-- ============================================
-- 添加照片EXIF数据字段
-- ============================================
-- 说明：为photos表添加EXIF相关字段，用于存储照片的经纬度、相机参数、地理位置等信息
-- 执行时间：2024
-- ============================================

USE `xiaochuo`;

-- 添加EXIF相关字段
ALTER TABLE `photos` 
ADD COLUMN `latitude` DECIMAL(10, 8) DEFAULT NULL COMMENT '纬度' AFTER `upload_ua`,
ADD COLUMN `longitude` DECIMAL(11, 8) DEFAULT NULL COMMENT '经度' AFTER `latitude`,
ADD COLUMN `altitude` DECIMAL(10, 2) DEFAULT NULL COMMENT '海拔高度（米）' AFTER `longitude`,
ADD COLUMN `camera_make` VARCHAR(100) DEFAULT NULL COMMENT '相机品牌' AFTER `altitude`,
ADD COLUMN `camera_model` VARCHAR(100) DEFAULT NULL COMMENT '相机型号' AFTER `camera_make`,
ADD COLUMN `lens_model` VARCHAR(100) DEFAULT NULL COMMENT '镜头型号' AFTER `camera_model`,
ADD COLUMN `focal_length` VARCHAR(20) DEFAULT NULL COMMENT '焦距' AFTER `lens_model`,
ADD COLUMN `aperture` VARCHAR(20) DEFAULT NULL COMMENT '光圈值' AFTER `focal_length`,
ADD COLUMN `shutter_speed` VARCHAR(20) DEFAULT NULL COMMENT '快门速度' AFTER `aperture`,
ADD COLUMN `iso` INT(11) DEFAULT NULL COMMENT 'ISO感光度' AFTER `shutter_speed`,
ADD COLUMN `exposure_mode` VARCHAR(50) DEFAULT NULL COMMENT '曝光模式' AFTER `iso`,
ADD COLUMN `white_balance` VARCHAR(50) DEFAULT NULL COMMENT '白平衡' AFTER `exposure_mode`,
ADD COLUMN `flash` VARCHAR(50) DEFAULT NULL COMMENT '闪光灯状态' AFTER `white_balance`,
ADD COLUMN `orientation` INT(11) DEFAULT NULL COMMENT '照片方向（1-8）' AFTER `flash`,
ADD COLUMN `width` INT(11) DEFAULT NULL COMMENT '照片宽度（像素）' AFTER `orientation`,
ADD COLUMN `height` INT(11) DEFAULT NULL COMMENT '照片高度（像素）' AFTER `width`,
ADD COLUMN `location_address` VARCHAR(500) DEFAULT NULL COMMENT '地理位置（地址）' AFTER `height`,
ADD COLUMN `exif_data` TEXT DEFAULT NULL COMMENT '完整EXIF数据（JSON格式）' AFTER `location_address`;

-- 添加索引以支持按地理位置查询
ALTER TABLE `photos` 
ADD INDEX `idx_latitude_longitude` (`latitude`, `longitude`),
ADD INDEX `idx_camera_make_model` (`camera_make`, `camera_model`);

