-- ============================================
-- 积分商城功能数据库迁移
-- ============================================
-- 创建时间：2024
-- 说明：添加积分商城相关的数据表
-- ============================================

-- 积分商品表
CREATE TABLE IF NOT EXISTS `points_shop` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '商品ID',
  `name` VARCHAR(100) NOT NULL COMMENT '商品名称',
  `description` TEXT DEFAULT NULL COMMENT '商品描述',
  `type` VARCHAR(50) NOT NULL COMMENT '商品类型：vip_temporary-临时VIP, vip_permanent-永久VIP, invite_limit-邀请链接数量',
  `points_price` INT(11) NOT NULL COMMENT '所需积分',
  `value` INT(11) DEFAULT NULL COMMENT '商品数值（如VIP天数、邀请链接数量等）',
  `status` TINYINT(1) DEFAULT 1 COMMENT '状态：1-上架，0-下架',
  `total_stock` INT(11) DEFAULT NULL COMMENT '总库存（NULL表示不限）',
  `sold_count` INT(11) DEFAULT 0 COMMENT '已兑换数量',
  `max_per_user` INT(11) DEFAULT NULL COMMENT '每个用户最多可兑换次数（NULL表示不限）',
  `sort_order` INT(11) DEFAULT 0 COMMENT '排序顺序（数字越大越靠前）',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `sort_order` (`sort_order`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='积分商品表';

-- 积分兑换记录表
CREATE TABLE IF NOT EXISTS `points_exchange_log` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '记录ID',
  `user_id` INT(11) UNSIGNED NOT NULL COMMENT '用户ID',
  `shop_id` INT(11) UNSIGNED NOT NULL COMMENT '商品ID',
  `shop_name` VARCHAR(100) NOT NULL COMMENT '商品名称（冗余字段，防止商品删除后无法查看）',
  `points_cost` INT(11) NOT NULL COMMENT '消耗积分',
  `status` VARCHAR(20) DEFAULT 'completed' COMMENT '状态：completed-已完成, failed-失败',
  `result` TEXT DEFAULT NULL COMMENT '兑换结果（JSON格式，存储具体的奖励信息）',
  `exchange_time` DATETIME NOT NULL COMMENT '兑换时间',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `shop_id` (`shop_id`),
  KEY `exchange_time` (`exchange_time`),
  KEY `status` (`status`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`shop_id`) REFERENCES `points_shop` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='积分兑换记录表';

