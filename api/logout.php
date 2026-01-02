<?php
/**
 * 退出登录API
 */
require_once __DIR__ . '/../core/autoload.php';

session_start();
session_destroy();
header('Location: ../login.php');
exit;
