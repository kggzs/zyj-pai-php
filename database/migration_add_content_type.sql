-- ============================================
-- 添加内容类型支持（HTML和Markdown）
-- ============================================
-- 创建时间：2024
-- 说明：为公告和积分商品添加内容类型字段，支持plain/html/markdown格式
-- ============================================

-- 为公告表添加content_type字段
ALTER TABLE `announcements` 
ADD COLUMN `content_type` VARCHAR(20) DEFAULT 'auto' COMMENT '内容类型：plain-纯文本, html-HTML, markdown-Markdown, auto-自动检测' 
AFTER `content`;

-- 为积分商品表添加description_type字段
ALTER TABLE `points_shop` 
ADD COLUMN `description_type` VARCHAR(20) DEFAULT 'auto' COMMENT '描述类型：plain-纯文本, html-HTML, markdown-Markdown, auto-自动检测' 
AFTER `description`;

