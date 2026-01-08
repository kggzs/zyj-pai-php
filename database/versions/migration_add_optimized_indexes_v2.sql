-- ============================================
-- 数据库索引优化补充脚本（修复版本）
-- 根据代码审查报告添加的优化索引
-- 此脚本会检查索引是否存在，避免重复创建
-- 修复了存储过程中的命令不同步问题
-- ============================================

USE `xiaochuo`;

-- ============================================
-- 创建辅助存储过程：安全添加索引（无返回结果集）
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
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION
    BEGIN
        -- 忽略索引已存在的错误（1061: Duplicate key name）
        GET DIAGNOSTICS CONDITION 1
            @errno = MYSQL_ERRNO, @errmsg = MESSAGE_TEXT;
        -- 1061: Duplicate key name, 1062: Duplicate entry
        IF @errno != 1061 AND @errno != 1062 THEN
            RESIGNAL;
        END IF;
    END;
    
    -- 获取当前数据库名
    SELECT DATABASE() INTO v_db_name;
    
    -- 检查索引是否存在
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
-- 1. 照片表优化索引（针对 getUserPhotos 查询优化）
-- ============================================

-- 用户ID + 删除状态 + 上传时间复合索引（最常用查询）
-- 用于：WHERE user_id = ? AND deleted_at IS NULL ORDER BY upload_time DESC
CALL add_index_if_not_exists('photos', 'idx_user_deleted_upload_time', '`user_id`, `deleted_at`, `upload_time`');

-- 用户ID + 邀请码 + 删除状态 + 上传时间复合索引
-- 用于：WHERE user_id = ? AND invite_code = ? AND deleted_at IS NULL ORDER BY upload_time DESC
CALL add_index_if_not_exists('photos', 'idx_user_invite_deleted_upload', '`user_id`, `invite_code`, `deleted_at`, `upload_time`');

-- 邀请码索引（如果还没有）
CALL add_index_if_not_exists('photos', 'idx_invite_code', '`invite_code`');

-- ============================================
-- 2. 积分日志表优化索引
-- ============================================

-- 用户ID + 创建时间复合索引（用于查询用户积分明细）
CALL add_index_if_not_exists('points_log', 'idx_user_create_time', '`user_id`, `create_time`');

-- 类型 + 创建时间复合索引（用于按类型统计）
CALL add_index_if_not_exists('points_log', 'idx_type_create_time', '`type`, `create_time`');

-- ============================================
-- 3. 照片标签关联表优化索引
-- ============================================

-- 照片ID + 标签ID复合索引（用于查询照片标签）
CALL add_index_if_not_exists('photo_tags', 'idx_photo_tag', '`photo_id`, `tag_id`');

-- 标签ID索引（用于反向查询）
CALL add_index_if_not_exists('photo_tags', 'idx_tag_id', '`tag_id`');

-- ============================================
-- 4. 标签表优化索引
-- ============================================

-- 用户ID + 标签名称复合索引（用于查询用户标签）
CALL add_index_if_not_exists('tags', 'idx_user_name', '`user_id`, `name`');

-- ============================================
-- 5. 邀请码表优化索引
-- ============================================

-- 邀请码索引（用于快速查找）
CALL add_index_if_not_exists('invites', 'idx_invite_code', '`invite_code`');

-- 用户ID + 状态 + 过期时间复合索引（用于查询有效邀请码）
CALL add_index_if_not_exists('invites', 'idx_user_status_expire', '`user_id`, `status`, `expire_time`');

-- ============================================
-- 6. 用户表优化索引
-- ============================================

-- 注册码索引（用于快速查找）
CALL add_index_if_not_exists('users', 'idx_register_code', '`register_code`');

-- 状态 + 注册时间复合索引（用于查询正常用户列表）
CALL add_index_if_not_exists('users', 'idx_status_register_time', '`status`, `register_time`');

-- ============================================
-- 清理临时存储过程
-- ============================================

DROP PROCEDURE IF EXISTS `add_index_if_not_exists`;

-- ============================================
-- 显示索引创建结果
-- ============================================

SELECT '数据库索引优化补充完成！' AS message;
SELECT '请检查上述输出，确认所有索引都已正确创建。' AS note;

