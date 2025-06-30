<?php
require_once 'auth_check.php';
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    echo "User ID not provided.";
    exit();
}

$userId = intval($_GET['id']);

$sessionsQuery = "SELECT * FROM sessions 
                  WHERE user_id = $userId
                  ORDER BY time_in DESC";
$sessionsResult = $conn->query($sessionsQuery);

// Fetch user details
$userStmt = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
$userStmt->bind_param("i", $userId);
$userStmt->execute();
$userResult = $userStmt->get_result();
$user = $userResult->fetch_assoc();

if (!$user) {
    echo "User not found.";
    exit();
}

// Fetch user activity (replace with actual activity table later)
$activityStmt = $conn->prepare("
    SELECT 
        id,
        room_type,
        time_in,
        time_out,
        duration_minutes,
        amount_paid,
        is_active
    FROM sessions 
    WHERE user_id = ? 
    ORDER BY time_in DESC
");
$activityStmt->bind_param("i", $userId);
$activityStmt->execute();
$activityResult = $activityStmt->get_result();
$activities = $activityResult->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Activity</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(to right, #1e3c72, #2a5298);
            color: #333;
        }
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .session-timer {
    font-family: monospace;
    font-size: 1.2em;
    color: #dc3545; 
    font-weight: bold;
}

    </style>
</head>
<body>
<div class="container mt-5">
    <div class="card p-4">
    <h3 class="text-center mb-4">Activity for <?= htmlspecialchars($user['username']) ?> (<?= htmlspecialchars($user['email']) ?>)</h3>

        <?php if (count($activities) > 0): ?>
            <table class="table table-bordered">
    <thead>
        <tr>
            <th>Room Type</th>
            <th>Time In</th>
            <th>Time Out</th>
            <th>Duration (min)</th>
            <th>Amount Paid</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($activities as $activity): ?>
        <tr>
            <td><?= ucfirst($activity['room_type']) ?></td>
            <td><?= date('M j, Y H:i', strtotime($activity['time_in'])) ?></td>
            <td>
                <?= $activity['time_out'] ? date('M j, Y H:i', strtotime($activity['time_out'])) : 'In Progress' ?>
            </td>
            <td><?= $activity['duration_minutes'] ?></td>
            <td><?= number_format($activity['amount_paid'], 2) ?></td>
            <td>
                <span class="badge bg-<?= $activity['is_active'] ? 'success' : 'secondary' ?>">
                    <?= $activity['is_active'] ? 'Active' : 'Completed' ?>
                </span>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
        <?php else: ?>
            <p>No activity records found for this user.</p>
        <?php endif; ?>

        <a href="manage_users.php" class="btn btn-outline-secondary mt-3">← Back to Manage Users</a>
    </div>
</div>
</body>
</html>
