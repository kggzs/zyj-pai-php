<?php
/**
 * 管理员退出登录API
 */
require_once __DIR__ . '/../../core/autoload.php';

session_start();

unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);

echo json_encode(['success' => true, 'message' => '已退出登录']);

