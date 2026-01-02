-- ============================================
-- 修复签到配置（适用于已安装的数据库）
-- ============================================
-- 说明：如果使用 init_latest.sql 安装后签到显示 "今日奖励+null = 0"
-- 请执行此脚本修复签到配置

USE `xiaochuo`;

-- 插入签到相关配置（如果不存在则插入，存在则更新）
INSERT INTO `system_config` (`config_key`, `config_value`, `description`) VALUES
('points_checkin_reward', '5', '每日签到基础奖励积分'),
('points_checkin_vip_bonus', '3', 'VIP用户签到额外奖励积分'),
('points_checkin_consecutive_bonus', '{"3": 5, "7": 10, "15": 20, "30": 50}', '普通用户连续签到额外奖励（JSON格式：天数=>奖励积分）'),
('points_checkin_vip_consecutive_bonus', '{"3": 8, "7": 15, "15": 30, "30": 80}', 'VIP用户连续签到额外奖励（JSON格式：天数=>奖励积分）'),
('points_register_reward', '10', '新用户注册奖励积分'),
('points_invite_reward', '5', '邀请新用户注册奖励积分（邀请人）'),
('points_invite_new_user_reward', '5', '通过邀请码注册奖励积分（新用户）'),
('points_upload_reward', '2', '上传照片/视频奖励积分')
ON DUPLICATE KEY UPDATE `config_value` = VALUES(`config_value`);

-- 执行完成提示
SELECT '签到配置已修复！' AS message;

