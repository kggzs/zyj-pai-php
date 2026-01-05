<?php
/**
 * 获取公告的用户已读状态API（管理员）
 */
session_start();
require_once __DIR__ . '/../../core/autoload.php';

header('Content-Type: application/json; charset=utf-8');

// 检查管理员登录状态
$adminModel = new Admin();
if (!$adminModel->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => '请先登录']);
    exit;
}

try {
    $announcementId = isset($_GET['announcement_id']) ? (int)$_GET['announcement_id'] : 0;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $pageSize = isset($_GET['page_size']) ? (int)$_GET['page_size'] : 20;
    
    if (empty($announcementId)) {
        echo json_encode(['success' => false, 'message' => '参数不完整']);
        exit;
    }
    
    $announcementModel = new Announcement();
    $result = $announcementModel->getAnnouncementReadStatus($announcementId, $page, $pageSize);
    
    echo json_encode(['success' => true, 'data' => $result]);
    
} catch (Exception $e) {
    Logger::error('获取公告已读状态错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '获取失败']);
}

