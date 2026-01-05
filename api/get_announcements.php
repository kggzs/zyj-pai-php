<?php
/**
 * 获取用户公告列表API
 */
session_start();
require_once __DIR__ . '/../core/autoload.php';

header('Content-Type: application/json; charset=utf-8');

// 检查登录状态
$userModel = new User();
if (!$userModel->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => '请先登录']);
    exit;
}

try {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $currentUser = $userModel->getCurrentUser();
    
    $announcementModel = new Announcement();
    $announcements = $announcementModel->getUserAnnouncements($currentUser['id'], $limit);
    
    // 获取未读数量
    $unreadCount = $announcementModel->getUnreadCount($currentUser['id']);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'list' => $announcements,
            'unread_count' => $unreadCount
        ]
    ]);
    
} catch (Exception $e) {
    Logger::error('获取公告列表错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '获取失败']);
}

