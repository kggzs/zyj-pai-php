<?php
/**
 * 批量删除照片API（VIP功能）
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

// 只接受POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '请求方法错误']);
    exit;
}

// 检查VIP权限
$currentUser = $userModel->getCurrentUser();
$db = Database::getInstance();
$userInfo = $db->fetchOne(
    "SELECT is_vip, vip_expire_time FROM users WHERE id = ?",
    [$currentUser['id']]
);
$isVip = false;
if (($userInfo['is_vip'] ?? 0) == 1) {
    if ($userInfo['vip_expire_time'] === null) {
        $isVip = true;
    } else {
        $expireTime = strtotime($userInfo['vip_expire_time']);
        $isVip = $expireTime > time();
    }
}

if (!$isVip) {
    echo json_encode(['success' => false, 'message' => '此功能仅限VIP会员使用']);
    exit;
}

$photoIdsRaw = isset($_POST['photo_ids']) ? $_POST['photo_ids'] : '[]';

// 如果是JSON字符串，解析它
if (is_string($photoIdsRaw)) {
    $photoIds = json_decode($photoIdsRaw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'message' => '无效的照片ID格式']);
        exit;
    }
} else {
    $photoIds = $photoIdsRaw;
}

if (empty($photoIds) || !is_array($photoIds)) {
    echo json_encode(['success' => false, 'message' => '请选择要删除的照片']);
    exit;
}

// 验证照片ID都是整数
$photoIds = array_filter(array_map('intval', $photoIds), function($id) {
    return $id > 0;
});

if (empty($photoIds)) {
    echo json_encode(['success' => false, 'message' => '无效的照片ID']);
    exit;
}

try {
    $photoModel = new Photo();
    $result = $photoModel->batchSoftDeletePhotos($photoIds, $currentUser['id']);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log('批量删除照片错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '删除失败：' . $e->getMessage()]);
}

