<?php
require_once 'auth_check.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
if ($userId) {
    require_once 'db_connect.php';
    $conn->query("UPDATE notifications SET is_read = 1 WHERE user_id = $userId AND is_read = 0");
}   