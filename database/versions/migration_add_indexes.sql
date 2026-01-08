-- ============================================
-- 数据库索引优化脚本
-- 添加必要索引以提升查询性能
-- ============================================

USE `xiaochuo`;

-- ============================================
-- 1. 用户表索引优化
-- ============================================

-- 注册时间索引（用于按时间排序和统计）
-- 注意：如果索引已存在会报错，可以忽略
ALTER TABLE `users` ADD INDEX `idx_register_time` (`register_time`);

-- 最后登录时间索引（用于统计活跃用户）
ALTER TABLE `users` ADD INDEX `idx_last_login_time` (`last_login_time`);

-- 状态+注册时间复合索引（用于查询正常用户列表）
ALTER TABLE `users` ADD INDEX `idx_status_register_time` (`status`, `register_time`);

-- VIP状态索引（用于查询VIP用户）
ALTER TABLE `users` ADD INDEX `idx_is_vip` (`is_vip`);

-- VIP到期时间索引（用于查询即将到期的VIP）
ALTER TABLE `users` ADD INDEX `idx_vip_expire_time` (`vip_expire_time`);

-- ============================================
-- 2. 照片表索引优化
-- ============================================

-- 用户ID+上传时间复合索引（用于查询用户照片列表）
ALTER TABLE `photos` ADD INDEX `idx_user_upload_time` (`user_id`, `upload_time`);

-- 邀请码+上传时间复合索引（用于按邀请码查询照片）
ALTER TABLE `photos` ADD INDEX `idx_invite_upload_time` (`invite_code`, `upload_time`);

-- 删除时间索引（用于查询未删除的照片）
ALTER TABLE `photos` ADD INDEX `idx_deleted_at_upload_time` (`deleted_at`, `upload_time`);

-- 上传时间索引（用于按日期统计）
ALTER TABLE `photos` ADD INDEX `idx_upload_time_date` (`upload_time`);

-- ============================================
-- 3. 邀请码表索引优化
-- ============================================

-- 用户ID+状态+过期时间复合索引（用于查询有效邀请码）
ALTER TABLE `invites` ADD INDEX `idx_user_status_expire` (`user_id`, `status`, `expire_time`);

-- 创建时间索引（用于按时间排序）
ALTER TABLE `invites` ADD INDEX `idx_create_time` (`create_time`);

-- ============================================
-- 4. 积分日志表索引优化
-- ============================================

-- 用户ID+创建时间复合索引（用于查询用户积分明细）
ALTER TABLE `points_log` ADD INDEX `idx_user_create_time` (`user_id`, `create_time`);

-- 类型+创建时间复合索引（用于按类型统计）
ALTER TABLE `points_log` ADD INDEX `idx_type_create_time` (`type`, `create_time`);

-- ============================================
-- 5. 登录日志表索引优化
-- ============================================

-- 用户ID+登录时间复合索引（用于查询用户登录历史）
ALTER TABLE `login_logs` ADD INDEX `idx_user_login_time` (`user_id`, `login_time`);

-- 登录IP索引（用于统计IP登录情况）
ALTER TABLE `login_logs` ADD INDEX `idx_login_ip` (`login_ip`);

-- 成功状态+登录时间复合索引（用于统计登录成功率）
ALTER TABLE `login_logs` ADD INDEX `idx_success_login_time` (`is_success`, `login_time`);

-- ============================================
-- 6. 签到记录表索引优化
-- ============================================

-- 用户ID+签到日期复合索引（用于查询用户签到记录）
ALTER TABLE `checkins` ADD INDEX `idx_user_checkin_date` (`user_id`, `checkin_date`);

-- ============================================
-- 7. 标签表索引优化
-- ============================================

-- 用户ID+标签名复合索引（用于查询用户标签）
ALTER TABLE `tags` ADD INDEX `idx_user_name` (`user_id`, `name`);

-- ============================================
-- 8. 照片标签关联表索引优化
-- ============================================

-- 照片ID+标签ID复合索引（用于查询照片标签）
ALTER TABLE `photo_tags` ADD INDEX `idx_photo_tag` (`photo_id`, `tag_id`);

-- ============================================
-- 9. 系统配置表索引优化
-- ============================================

-- 配置键索引（已存在，但确保存在）
-- 注意：如果索引已存在会报错，可以忽略
ALTER TABLE `system_config` ADD INDEX `idx_config_key` (`config_key`);

-- ============================================
-- 10. 管理员操作日志表索引优化
-- ============================================

-- 管理员ID+操作时间复合索引（用于查询管理员操作历史）
ALTER TABLE `admin_operation_logs` ADD INDEX `idx_admin_operation_time` (`admin_id`, `created_at`);

-- 操作类型+操作时间复合索引（用于按类型统计）
ALTER TABLE `admin_operation_logs` ADD INDEX `idx_operation_type_created` (`operation_type`, `created_at`);

-- ============================================
-- 11. 异常行为记录表索引优化
-- ============================================

-- 用户ID+创建时间复合索引（用于查询用户异常行为）
ALTER TABLE `abnormal_behavior_logs` ADD INDEX `idx_user_created_at` (`user_id`, `created_at`);

-- 类型+状态+创建时间复合索引（用于查询特定类型的异常行为）
ALTER TABLE `abnormal_behavior_logs` ADD INDEX `idx_behavior_type_handled_created` (`behavior_type`, `is_handled`, `created_at`);

-- IP地址索引（用于统计IP异常行为）
ALTER TABLE `abnormal_behavior_logs` ADD INDEX `idx_ip_address` (`ip_address`);

-- ============================================
-- 优化完成
-- ============================================
SELECT '数据库索引优化完成！' AS message;

