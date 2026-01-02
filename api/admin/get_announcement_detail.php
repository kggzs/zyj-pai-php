<?php
/**
 * 获取公告详情API（管理员）
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
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => '参数不完整']);
        exit;
    }
    
    $announcementModel = new Announcement();
    $announcement = $announcementModel->getAnnouncementDetail($id);
    
    if (!$announcement) {
        echo json_encode(['success' => false, 'message' => '公告不存在']);
        exit;
    }
    
    echo json_encode(['success' => true, 'data' => $announcement]);
    
} catch (Exception $e) {
    error_log('获取公告详情错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '获取失败']);
}

