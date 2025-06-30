<?php
require_once 'auth_check.php';
require_once 'db_connect.php';

// Update ALL bookings' statuses for admin
$conn->query("UPDATE bookings SET status = 'activated' WHERE status = 'confirmed' AND NOW() >= time_in AND NOW() < time_out");
$conn->query("UPDATE bookings SET status = 'completed' WHERE status = 'activated' AND NOW() >= time_out");
$conn->query("UPDATE bookings SET status = 'cancelled' WHERE status = 'pending' AND NOW() > time_in");

// Redirect non-admin users to the user dashboard
if ($_SESSION['user_role'] != 1) {
    header("Location: user_dashboard.php");
    exit();
}

// Fetch all bookings made by users
$bookingQuery = "SELECT b.id, u.first_name, u.last_name, b.room_type, b.time_in, b.time_out, b.amount_paid
                 FROM bookings b
                 JOIN users u ON b.user_id = u.id
                 ORDER BY b.time_in DESC";
$bookingResult = $conn->query($bookingQuery);
$bookings = [];

if ($bookingResult->num_rows > 0) {
    while ($row = $bookingResult->fetch_assoc()) {
        $bookings[] = $row;
    }
}

// Handle booking deletion
if (isset($_GET['delete_booking_id'])) {
    $bookingId = $_GET['delete_booking_id'];
    $deleteQuery = "DELETE FROM bookings WHERE id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("i", $bookingId);

    if ($stmt->execute()) {
        header("Location: admin_bookings.php");
        exit();
    } else {
        echo "Error deleting booking: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | Manage Bookings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Tasala</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="admin_dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="admin_bookings.php">Manage Bookings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_users.php">Manage Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="mb-4">
    <a href="admin_dashboard.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>Go Back
    </a>
</div>

    <div class="container my-5">
        <h2>Manage Bookings</h2>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Room Type</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                    <th>Amount Paid</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $booking): ?>
                <tr>
                    <td><?= htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']) ?></td>
                    <td><?= ucfirst($booking['room_type']) ?></td>
                    <td><?= date('M j, Y H:i', strtotime($booking['time_in'])) ?></td>
                    <td><?= date('M j, Y H:i', strtotime($booking['time_out'])) ?></td>
                    <td>₱<?= number_format($booking['amount_paid'], 2) ?></td>
                    <td>
                        <a href="edit_booking.php?booking_id=<?= $booking['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                        <a href="admin_bookings.php?delete_booking_id=<?= $booking['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this booking?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
