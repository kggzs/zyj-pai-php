<?php
/**
 * 更新公告API（管理员）
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
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $level = $_POST['level'] ?? 'normal';
    $requireRead = isset($_POST['require_read']) && $_POST['require_read'] == '1';
    $isVisible = isset($_POST['is_visible']) ? ($_POST['is_visible'] == '1') : true;
    
    if (empty($id) || empty($title) || empty($content)) {
        echo json_encode(['success' => false, 'message' => '参数不完整']);
        exit;
    }
    
    $announcementModel = new Announcement();
    $result = $announcementModel->updateAnnouncement(
        $id,
        $title,
        $content,
        $level,
        $requireRead,
        $isVisible
    );
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log('更新公告错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '更新失败']);
}

