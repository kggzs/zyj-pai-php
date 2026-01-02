<?php
/**
 * 删除公告API（管理员）
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
    $id = $_POST['id'] ?? 0;
    
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => '参数不完整']);
        exit;
    }
    
    $announcementModel = new Announcement();
    $result = $announcementModel->deleteAnnouncement($id);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log('删除公告错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '删除失败']);
}

