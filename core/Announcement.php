<?php
/**
 * 系统公告管理类
 */
class Announcement {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * 发布公告
     * @param int $adminId 管理员ID
     * @param string $title 公告标题
     * @param string $content 公告内容
     * @param string $level 公告级别：important, normal, notice
     * @param bool $requireRead 是否强制已读
     * @param bool $isVisible 是否显示
     * @return array
     */
    public function createAnnouncement($adminId, $title, $content, $level = 'normal', $requireRead = false, $isVisible = true) {
        // 验证级别
        if (!in_array($level, ['important', 'normal', 'notice'])) {
            $level = 'normal';
        }
        
        $sql = "INSERT INTO announcements (title, content, level, require_read, is_visible, admin_id) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $this->db->execute($sql, [
            $title,
            $content,
            $level,
            $requireRead ? 1 : 0,
            $isVisible ? 1 : 0,
            $adminId
        ]);
        
        $announcementId = $this->db->lastInsertId();
        
        // 如果要求强制已读，为所有用户创建未读记录
        if ($requireRead && $isVisible) {
            $this->createUnreadRecordsForAllUsers($announcementId);
        }
        
        return [
            'success' => true,
            'announcement_id' => $announcementId
        ];
    }
    
    /**
     * 为所有用户创建未读记录
     */
    private function createUnreadRecordsForAllUsers($announcementId) {
        $users = $this->db->fetchAll("SELECT id FROM users WHERE status = 1");
        foreach ($users as $user) {
            $this->db->execute(
                "INSERT IGNORE INTO user_announcements (user_id, announcement_id, is_read) VALUES (?, ?, 0)",
                [$user['id'], $announcementId]
            );
        }
    }
    
    /**
     * 更新公告
     */
    public function updateAnnouncement($announcementId, $title, $content, $level, $requireRead, $isVisible) {
        // 验证级别
        if (!in_array($level, ['important', 'normal', 'notice'])) {
            $level = 'normal';
        }
        
        // 获取原公告信息
        $oldAnnouncement = $this->db->fetchOne(
            "SELECT require_read, is_visible FROM announcements WHERE id = ?",
            [$announcementId]
        );
        
        if (!$oldAnnouncement) {
            return ['success' => false, 'message' => '公告不存在'];
        }
        
        $sql = "UPDATE announcements SET title = ?, content = ?, level = ?, require_read = ?, is_visible = ? WHERE id = ?";
        $this->db->execute($sql, [
            $title,
            $content,
            $level,
            $requireRead ? 1 : 0,
            $isVisible ? 1 : 0,
            $announcementId
        ]);
        
        // 如果新设置为强制已读且可见，为所有用户创建未读记录
        if ($requireRead && $isVisible && (!$oldAnnouncement['require_read'] || !$oldAnnouncement['is_visible'])) {
            $this->createUnreadRecordsForAllUsers($announcementId);
        }
        
        return ['success' => true];
    }
    
    /**
     * 删除公告
     */
    public function deleteAnnouncement($announcementId) {
        // 删除用户已读记录
        $this->db->execute("DELETE FROM user_announcements WHERE announcement_id = ?", [$announcementId]);
        
        // 删除公告
        $this->db->execute("DELETE FROM announcements WHERE id = ?", [$announcementId]);
        
        return ['success' => true];
    }
    
    /**
     * 获取公告列表（管理员用）
     */
    public function getAnnouncementList($page = 1, $pageSize = 20) {
        $offset = ($page - 1) * $pageSize;
        
        $announcements = $this->db->fetchAll(
            "SELECT a.*, u.username as admin_username 
             FROM announcements a 
             LEFT JOIN users u ON a.admin_id = u.id 
             ORDER BY a.create_time DESC 
             LIMIT ? OFFSET ?",
            [$pageSize, $offset]
        );
        
        $total = $this->db->fetchOne("SELECT COUNT(*) as total FROM announcements");
        
        return [
            'list' => $announcements,
            'total' => $total['total'] ?? 0,
            'page' => $page,
            'page_size' => $pageSize
        ];
    }
    
    /**
     * 获取公告详情
     */
    public function getAnnouncementDetail($announcementId) {
        return $this->db->fetchOne(
            "SELECT a.*, u.username as admin_username 
             FROM announcements a 
             LEFT JOIN users u ON a.admin_id = u.id 
             WHERE a.id = ?",
            [$announcementId]
        );
    }
    
    /**
     * 获取用户的公告列表（用户用）
     */
    public function getUserAnnouncements($userId, $limit = 10) {
        $announcements = $this->db->fetchAll(
            "SELECT a.*, ua.is_read, ua.read_time 
             FROM announcements a 
             LEFT JOIN user_announcements ua ON a.id = ua.announcement_id AND ua.user_id = ?
             WHERE a.is_visible = 1 
             ORDER BY a.level DESC, a.create_time DESC 
             LIMIT ?",
            [$userId, $limit]
        );
        
        return $announcements;
    }
    
    /**
     * 标记公告为已读
     */
    public function markAsRead($userId, $announcementId) {
        // 检查公告是否存在且可见
        $announcement = $this->db->fetchOne(
            "SELECT id FROM announcements WHERE id = ? AND is_visible = 1",
            [$announcementId]
        );
        
        if (!$announcement) {
            return ['success' => false, 'message' => '公告不存在'];
        }
        
        // 插入或更新已读状态
        $this->db->execute(
            "INSERT INTO user_announcements (user_id, announcement_id, is_read, read_time) 
             VALUES (?, ?, 1, NOW()) 
             ON DUPLICATE KEY UPDATE is_read = 1, read_time = NOW()",
            [$userId, $announcementId]
        );
        
        return ['success' => true];
    }
    
    /**
     * 获取公告的用户已读状态列表
     */
    public function getAnnouncementReadStatus($announcementId, $page = 1, $pageSize = 20) {
        $offset = ($page - 1) * $pageSize;
        
        $users = $this->db->fetchAll(
            "SELECT u.id, u.username, u.nickname, ua.is_read, ua.read_time 
             FROM users u 
             LEFT JOIN user_announcements ua ON u.id = ua.user_id AND ua.announcement_id = ?
             WHERE u.status = 1 
             ORDER BY ua.is_read ASC, ua.read_time DESC, u.id ASC 
             LIMIT ? OFFSET ?",
            [$announcementId, $pageSize, $offset]
        );
        
        $total = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM users WHERE status = 1"
        );
        
        return [
            'list' => $users,
            'total' => $total['total'] ?? 0,
            'page' => $page,
            'page_size' => $pageSize
        ];
    }
    
    /**
     * 获取用户未读公告数量
     */
    public function getUnreadCount($userId) {
        $result = $this->db->fetchOne(
            "SELECT COUNT(DISTINCT a.id) as count 
             FROM announcements a 
             LEFT JOIN user_announcements ua ON a.id = ua.announcement_id AND ua.user_id = ?
             WHERE a.is_visible = 1 
             AND a.require_read = 1 
             AND (ua.is_read IS NULL OR ua.is_read = 0)",
            [$userId]
        );
        
        return $result['count'] ?? 0;
    }
}

