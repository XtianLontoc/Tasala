<?php
require_once 'auth_check.php';
require_once 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_POST['id'], $_POST['username'], $_POST['email'], $_POST['first_name'], $_POST['last_name'], $_POST['role_id'])) {
    echo json_encode(['success' => false, 'error' => 'Missing data.']);
    exit;
}

$id = intval($_POST['id']);
$username = trim($_POST['username']);
$first_name = trim($_POST['first_name']);
$last_name = trim($_POST['last_name']);
$email = trim($_POST['email']);
$role_id = intval($_POST['role_id']);
$password = trim($_POST['password']);

if ($username === '' || $email === '' || $first_name === '' || $last_name === '' || !in_array($role_id, [1,2])) {
    echo json_encode(['success' => false, 'error' => 'All fields except password are required.']);
    exit;
}

if ($password !== '') {
    $stmt = $conn->prepare("UPDATE users SET username=?, email=?, first_name=?, last_name=?, role_id=?, password=? WHERE id=?");
    $stmt->bind_param("ssssisi", $username, $email, $first_name, $last_name, $role_id, $password, $id);
} else {
    $stmt = $conn->prepare("UPDATE users SET username=?, email=?, first_name=?, last_name=?, role_id=? WHERE id=?");
    $stmt->bind_param("ssssii", $username, $email, $first_name, $last_name, $role_id, $id);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error.']);
}
$stmt->close();