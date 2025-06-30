<?php
require_once 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_GET['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User ID not provided']);
    exit();
}

$userId = (int)$_GET['user_id'];

// End the session and set time_out to current time if it's in the future
$stmt = $conn->prepare("
    UPDATE sessions 
    SET is_active = FALSE, 
        time_out = CASE WHEN time_out > NOW() THEN NOW() ELSE time_out END
    WHERE user_id = ? AND is_active = TRUE
");
$stmt->bind_param("i", $userId);
$success = $stmt->execute();

echo json_encode([
    'success' => $success,
    'affected_rows' => $stmt->affected_rows
]);
?>