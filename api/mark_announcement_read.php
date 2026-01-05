<?php
/**
 * 标记公告为已读API
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
    $announcementId = $_POST['announcement_id'] ?? 0;
    
    if (empty($announcementId)) {
        echo json_encode(['success' => false, 'message' => '参数不完整']);
        exit;
    }
    
    $currentUser = $userModel->getCurrentUser();
    $announcementModel = new Announcement();
    $result = $announcementModel->markAsRead($currentUser['id'], $announcementId);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    Logger::error('标记公告已读错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '操作失败']);
}

