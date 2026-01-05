<?php
/**
 * 获取排行榜API
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../core/autoload.php';

error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');

// 检查登录状态
$userModel = new User();
if (!$userModel->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => '请先登录'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $type = isset($_GET['type']) ? $_GET['type'] : 'total';
    // 月度排行榜只使用当前月，不接受year和month参数
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
    
    $pointsModel = new Points();
    $currentUser = $userModel->getCurrentUser();
    $userId = $currentUser['id'] ?? null;
    
    $ranking = [];
    $userRanking = null;
    $year = date('Y');
    $month = date('m');
    
    switch ($type) {
        case 'total':
            $ranking = $pointsModel->getTotalPointsRanking($limit);
            if ($userId) {
                $userRanking = $pointsModel->getUserRanking($userId, 'total');
            }
            break;
            
        case 'monthly':
            // 月度排行榜只显示当前月
            $ranking = $pointsModel->getMonthlyPointsRanking($year, $month, $limit);
            if ($userId) {
                $userRanking = $pointsModel->getUserRanking($userId, 'monthly');
            }
            break;
            
        case 'invite':
            $ranking = $pointsModel->getInviteRanking($limit);
            if ($userId) {
                $userRanking = $pointsModel->getUserRanking($userId, 'invite');
            }
            break;
            
        case 'photo':
            $ranking = $pointsModel->getPhotoCountRanking($limit);
            if ($userId) {
                $userRanking = $pointsModel->getUserRanking($userId, 'photo');
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => '无效的排行榜类型'], JSON_UNESCAPED_UNICODE);
            exit;
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'type' => $type,
            'ranking' => $ranking,
            'user_ranking' => $userRanking,
            'year' => $year,
            'month' => $month
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    Logger::error('获取排行榜错误：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '获取失败'], JSON_UNESCAPED_UNICODE);
}

