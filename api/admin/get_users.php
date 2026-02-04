<?php
/**
 * 获取用户列表API（管理员）
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
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $pageSize = isset($_GET['page_size']) ? (int)$_GET['page_size'] : 20;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $filter = isset($_GET['filter']) ? trim($_GET['filter']) : 'all';
    
    // 验证分页参数
    if ($page < 1) {
        $page = 1;
    }
    if ($pageSize < 1 || $pageSize > 100) {
        $pageSize = 20;
    }
    
    // 验证搜索参数长度（防止过长的搜索字符串）
    if (mb_strlen($search) > 100) {
        echo json_encode(['success' => false, 'message' => '搜索关键词过长']);
        exit;
    }
    
    $result = $adminModel->getUserList($page, $pageSize, $search, $filter);
    
    echo json_encode(['success' => true, 'data' => $result]);
    
} catch (Exception $e) {
    Logger::error('获取用户列表错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '获取失败']);
}

