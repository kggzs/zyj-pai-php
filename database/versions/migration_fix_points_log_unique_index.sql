-- 修复积分明细表唯一索引问题
-- 问题：唯一索引 unique_reward (invite_code, new_user_id) 导致邀请人和新用户的记录冲突
-- 解决方案：移除该唯一索引，改用应用层检查

-- 删除旧的唯一索引
ALTER TABLE `points_log` DROP INDEX `unique_reward`;

-- 添加新的唯一索引，包含 user_id 和 remark，确保同一用户对同一新用户只能有一条邀请奖励记录
-- 注意：这个索引只对邀请奖励类型有效，不会影响其他类型的记录
ALTER TABLE `points_log` 
ADD UNIQUE KEY `unique_invite_reward` (`user_id`, `invite_code`, `new_user_id`, `remark`(50));

