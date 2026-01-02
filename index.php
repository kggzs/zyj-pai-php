<?php
/**
 * 首页入口
 */
session_start();
require_once __DIR__ . '/core/autoload.php';

$userModel = new User();
if ($userModel->isLoggedIn()) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
