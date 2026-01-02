<?php
/**
 * 创建公告API（管理员）
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
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $level = $_POST['level'] ?? 'normal';
    $requireRead = isset($_POST['require_read']) && $_POST['require_read'] == '1';
    $isVisible = isset($_POST['is_visible']) ? ($_POST['is_visible'] == '1') : true;
    
    if (empty($title) || empty($content)) {
        echo json_encode(['success' => false, 'message' => '标题和内容不能为空']);
        exit;
    }
    
    $announcementModel = new Announcement();
    $result = $announcementModel->createAnnouncement(
        $_SESSION['admin_id'],
        $title,
        $content,
        $level,
        $requireRead,
        $isVisible
    );
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log('创建公告错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '创建失败']);
}

