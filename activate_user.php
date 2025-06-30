<?php
require_once 'auth_check.php';
require_once 'db_connect.php';

header('Content-Type: application/json');

if (empty($_POST['id'])) {  // Using only POST for consistency
    echo json_encode(['success' => false, 'error' => 'User ID required.']);
    exit;
}

$id = intval($_POST['id']);
$stmt = $conn->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Operation failed.']);
}
$stmt->close();