<?php
/**
 * 获取有照片的用户列表API（管理员）
 */
// 禁用错误输出到响应中
ini_set('display_errors', 0);
error_reporting(E_ALL);

// 捕获所有输出，避免在JSON响应前输出任何内容
if (!ob_get_level()) {
    ob_start();
}

session_start();
require_once __DIR__ . '/../../core/autoload.php';

// 清除所有输出缓冲区
if (ob_get_level()) {
    ob_clean();
}

header('Content-Type: application/json; charset=utf-8');

// 检查管理员登录状态
$adminModel = new Admin();
if (!$adminModel->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => '请先登录']);
    exit;
}

try {
    $db = Database::getInstance();
    
    // 获取有照片的用户列表（包括已删除的用户，因为管理员需要看到所有照片）
    $sql = "SELECT DISTINCT 
                u.id as user_id,
                u.username as user_name,
                u.nickname,
                u.status,
                COUNT(p.id) as photo_count,
                MAX(p.deleted_at) as deleted_at
            FROM photos p
            INNER JOIN users u ON p.user_id = u.id
            GROUP BY u.id, u.username, u.nickname, u.status
            ORDER BY photo_count DESC, u.username ASC";
    
    $users = $db->fetchAll($sql);
    
    // 格式化数据
    $result = [];
    foreach ($users as $user) {
        $result[] = [
            'user_id' => $user['user_id'],
            'user_name' => $user['user_name'] ?: ($user['nickname'] ?: '未知用户'),
            'photo_count' => (int)$user['photo_count'],
            'deleted_at' => $user['deleted_at']
        ];
    }
    
    echo json_encode(['success' => true, 'data' => $result], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // 清除所有输出
    if (ob_get_level()) {
        ob_clean();
    }
    error_log('获取用户列表错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '获取失败'], JSON_UNESCAPED_UNICODE);
} catch (Error $e) {
    // 清除所有输出
    if (ob_get_level()) {
        ob_clean();
    }
    error_log('获取用户列表致命错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '获取失败'], JSON_UNESCAPED_UNICODE);
}

