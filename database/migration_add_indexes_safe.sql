-- ============================================
-- 数据库索引优化脚本（安全版本）
-- 添加必要索引以提升查询性能
-- 此版本会检查索引是否存在，避免重复创建
-- ============================================

USE `xiaochuo`;

-- ============================================
-- 创建辅助存储过程：安全添加索引
-- ============================================

DELIMITER $$

DROP PROCEDURE IF EXISTS `add_index_if_not_exists`$$
CREATE PROCEDURE `add_index_if_not_exists`(
    IN p_table_name VARCHAR(64),
    IN p_index_name VARCHAR(64),
    IN p_index_columns VARCHAR(500)
)
BEGIN
    DECLARE v_index_exists INT DEFAULT 0;
    DECLARE v_db_name VARCHAR(64);
    
    -- 获取当前数据库名
    SELECT DATABASE() INTO v_db_name;
    
    -- 检查索引是否存在（使用不同的变量名避免冲突）
    SELECT COUNT(*) INTO v_index_exists
    FROM information_schema.statistics
    WHERE table_schema = v_db_name
      AND table_name = p_table_name
      AND index_name = p_index_name;
    
    -- 如果索引不存在，则创建
    IF v_index_exists = 0 THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table_name, '` ADD INDEX `', p_index_name, '` (', p_index_columns, ')');
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

DELIMITER ;

-- ============================================
-- 1. 用户表索引优化
-- ============================================

CALL add_index_if_not_exists('users', 'idx_register_time', '`register_time`');
CALL add_index_if_not_exists('users', 'idx_last_login_time', '`last_login_time`');
CALL add_index_if_not_exists('users', 'idx_status_register_time', '`status`, `register_time`');
CALL add_index_if_not_exists('users', 'idx_is_vip', '`is_vip`');
CALL add_index_if_not_exists('users', 'idx_vip_expire_time', '`vip_expire_time`');

-- ============================================
-- 2. 照片表索引优化
-- ============================================

CALL add_index_if_not_exists('photos', 'idx_user_upload_time', '`user_id`, `upload_time`');
CALL add_index_if_not_exists('photos', 'idx_invite_upload_time', '`invite_code`, `upload_time`');
CALL add_index_if_not_exists('photos', 'idx_deleted_at_upload_time', '`deleted_at`, `upload_time`');
CALL add_index_if_not_exists('photos', 'idx_upload_time_date', '`upload_time`');

-- ============================================
-- 3. 邀请码表索引优化
-- ============================================

CALL add_index_if_not_exists('invites', 'idx_user_status_expire', '`user_id`, `status`, `expire_time`');
CALL add_index_if_not_exists('invites', 'idx_create_time', '`create_time`');

-- ============================================
-- 4. 积分日志表索引优化
-- ============================================

CALL add_index_if_not_exists('points_log', 'idx_user_create_time', '`user_id`, `create_time`');
CALL add_index_if_not_exists('points_log', 'idx_type_create_time', '`type`, `create_time`');

-- ============================================
-- 5. 登录日志表索引优化
-- ============================================

CALL add_index_if_not_exists('login_logs', 'idx_user_login_time', '`user_id`, `login_time`');
CALL add_index_if_not_exists('login_logs', 'idx_login_ip', '`login_ip`');
CALL add_index_if_not_exists('login_logs', 'idx_success_login_time', '`is_success`, `login_time`');

-- ============================================
-- 6. 签到记录表索引优化
-- ============================================

CALL add_index_if_not_exists('checkins', 'idx_user_checkin_date', '`user_id`, `checkin_date`');

-- ============================================
-- 7. 标签表索引优化
-- ============================================

CALL add_index_if_not_exists('tags', 'idx_user_name', '`user_id`, `name`');

-- ============================================
-- 8. 照片标签关联表索引优化
-- ============================================

CALL add_index_if_not_exists('photo_tags', 'idx_photo_tag', '`photo_id`, `tag_id`');

-- ============================================
-- 9. 系统配置表索引优化
-- ============================================

CALL add_index_if_not_exists('system_config', 'idx_config_key', '`config_key`');

-- ============================================
-- 10. 管理员操作日志表索引优化
-- ============================================

CALL add_index_if_not_exists('admin_operation_logs', 'idx_admin_operation_time', '`admin_id`, `created_at`');
CALL add_index_if_not_exists('admin_operation_logs', 'idx_operation_type_created', '`operation_type`, `created_at`');

-- ============================================
-- 11. 异常行为记录表索引优化
-- ============================================

CALL add_index_if_not_exists('abnormal_behavior_logs', 'idx_user_created_at', '`user_id`, `created_at`');
CALL add_index_if_not_exists('abnormal_behavior_logs', 'idx_behavior_type_handled_created', '`behavior_type`, `is_handled`, `created_at`');
CALL add_index_if_not_exists('abnormal_behavior_logs', 'idx_ip_address', '`ip_address`');

-- ============================================
-- 清理临时存储过程
-- ============================================

DROP PROCEDURE IF EXISTS `add_index_if_not_exists`;

-- ============================================
-- 优化完成
-- ============================================
SELECT '数据库索引优化完成！所有索引已检查并创建（如果不存在）。' AS message;

