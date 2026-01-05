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
    
    // 获取有照片的用户列表（包括已封禁的用户，因为管理员需要看到所有照片）
    $sql = "SELECT DISTINCT 
                u.id as user_id,
                u.username as user_name,
                u.nickname,
                u.status
            FROM photos p
            INNER JOIN users u ON p.user_id = u.id
            GROUP BY u.id, u.username, u.nickname, u.status";
    
    // 获取每个用户的照片数量（包括已删除的照片）
    $countSql = "SELECT 
                    u.id as user_id,
                    COUNT(p.id) as photo_count
                 FROM photos p
                 INNER JOIN users u ON p.user_id = u.id
                 GROUP BY u.id";
    
    $users = $db->fetchAll($sql);
    $photoCounts = $db->fetchAll($countSql);
    
    // 将照片数量转换为以user_id为键的数组
    $photoCountMap = [];
    foreach ($photoCounts as $count) {
        $photoCountMap[$count['user_id']] = (int)$count['photo_count'];
    }
    
    // 格式化数据
    $result = [];
    foreach ($users as $user) {
        $result[] = [
            'user_id' => $user['user_id'],
            'user_name' => $user['user_name'] ?: ($user['nickname'] ?: '未知用户'),
            'photo_count' => $photoCountMap[$user['user_id']] ?? 0,
            'status' => (int)$user['status'] // 用户状态：1=正常，0=封禁
        ];
    }
    
    // 按照片数量降序排序
    usort($result, function($a, $b) {
        if ($a['photo_count'] == $b['photo_count']) {
            return strcmp($a['user_name'], $b['user_name']);
        }
        return $b['photo_count'] - $a['photo_count'];
    });
    
    echo json_encode(['success' => true, 'data' => $result], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // 清除所有输出
    if (ob_get_level()) {
        ob_clean();
    }
    Logger::error('获取用户列表错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '获取失败'], JSON_UNESCAPED_UNICODE);
} catch (Error $e) {
    // 清除所有输出
    if (ob_get_level()) {
        ob_clean();
    }
    Logger::error('获取用户列表致命错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '获取失败'], JSON_UNESCAPED_UNICODE);
}

