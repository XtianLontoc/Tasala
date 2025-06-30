<?php
    require_once 'auth_check.php';
    require_once 'db_connect.php';

    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }

    // Only allow admins (role_id = 1)
    if ($_SESSION['user_role'] != 1) {
        header("Location: user_dashboard.php");
        exit();
    }

    // Query for fetching bookings and active sessions
    $query = "SELECT b.*, u.username, u.first_name, u.last_name, s.is_active 
              FROM bookings b
              JOIN users u ON b.user_id = u.id
              LEFT JOIN sessions s ON b.user_id = s.user_id AND s.is_active = 1
              ORDER BY b.time_in DESC";
    $result = $conn->query($query);

    // Total users
    $result = $conn->query("SELECT COUNT(*) as total_users FROM users");
    $totalUsers = $result->fetch_assoc()['total_users'];

    // Active users
    $result = $conn->query("SELECT COUNT(*) as active_users FROM users WHERE is_active = 1");
    $activeUsers = $result->fetch_assoc()['active_users'];

    // Recent contacts
    $result = $conn->query("SELECT * FROM contacts ORDER BY submitted_at DESC LIMIT 5");
    $recentContacts = [];
    while ($row = $result->fetch_assoc()) {
        $recentContacts[] = $row;
    }

    // Recent activities
    $result = $conn->query("SELECT ua.*, u.username FROM user_activities ua
                            JOIN users u ON ua.user_id = u.id
                            ORDER BY ua.created_at DESC LIMIT 10");
    $recentActivities = [];
    while ($row = $result->fetch_assoc()) {
        $recentActivities[] = $row;
    }

    // Recent bookings
    $bookingResult = $conn->query("SELECT b.*, u.username FROM bookings b 
                                   JOIN users u ON b.user_id = u.id 
                                   ORDER BY b.time_in DESC LIMIT 10");
    $recentBookings = [];
    while ($row = $bookingResult->fetch_assoc()) {
        $recentBookings[] = $row;
    }

    // Handle ending an active session (end_session.php)
    if (isset($_GET['end_session_id'])) {
        $sessionId = $_GET['end_session_id'];
        $updateQuery = "UPDATE sessions SET is_active = 0, time_out = NOW() WHERE id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("i", $sessionId);
        $stmt->execute();
        header("Location: admin_dashboard.php"); // Refresh the page after ending the session
        exit();
    }

    // Update all bookings' statuses
    $conn->query("UPDATE bookings SET status = 'activated' WHERE status = 'confirmed' AND NOW() >= time_in AND NOW() < time_out");
    $conn->query("UPDATE bookings SET status = 'completed' WHERE status = 'activated' AND NOW() >= time_out");
    $conn->query("UPDATE bookings SET status = 'cancelled' WHERE status = 'pending' AND NOW() > time_in");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Tasala</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f4f8fb 0%, #eaf0fa 100%) !important;
            min-height: 100vh;
        }
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        }
        .sidebar .nav-link {
            color: #fff;
            font-weight: 500;
            border-radius: 0.75rem;
            margin-bottom: 0.5rem;
            transition: background 0.2s;
        }
        .sidebar .nav-link.active, .sidebar .nav-link:hover {
            background: rgba(255,255,255,0.12);
            color: #fff;
        }
        .dashboard-card {
            border-radius: 1.5rem;
            box-shadow: 0 6px 32px 0 rgba(30,60,114,0.08), 0 1.5px 4px 0 rgba(42,82,152,0.08);
            margin-bottom: 2rem;
            background: #fff;
            transition: transform 0.2s;
        }
        .dashboard-card:hover {
            transform: translateY(-4px) scale(1.01);
        }
        .card-header {
            background: linear-gradient(90deg, #1e3c72 60%, #2a5298 100%);
            color: #fff;
            border-radius: 1.5rem 1.5rem 0 0 !important;
        }
        .table thead th {
            background: #f4f8fb;
            color: #1e3c72;
            font-weight: 600;
        }
        .badge.bg-success { background: #28a745 !important; }
        .badge.bg-warning { background: #ffc107 !important; color: #212529 !important; }
        .badge.bg-danger { background: #dc3545 !important; }
        .badge.bg-info { background: #17a2b8 !important; }
        .table-striped>tbody>tr:nth-of-type(odd) {
            background-color: #f8fafc;
        }
        .table-striped>tbody>tr:nth-of-type(even) {
            background-color: #fff;
        }
        .table td, .table th {
            vertical-align: middle;
        }
        .activity-list {
            max-height: 300px;
            overflow-y: auto;
        }
        .recent-contact-msg {
            max-width: 250px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        @media (max-width: 991px) {
            .sidebar {
                min-height: auto;
            }
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-lg-2 col-md-3 d-none d-md-block sidebar py-4 px-3">
            <div class="mb-4 text-center">
                <h4 class="fw-bold text-white mb-0">Tasala Admin</h4>
                <hr class="bg-white opacity-50">
            </div>
            <ul class="nav flex-column">
                <li class="nav-item mb-2">
                    <a class="nav-link active" href="admin_dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link" href="manage_users.php">
                        <i class="fas fa-users me-2"></i>Manage Users
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link" href="admins_booking.php">
                        <i class="fas fa-calendar-check me-2"></i>Bookings
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link" href="manage_contacts.php">
                        <i class="fas fa-envelope me-2"></i>Contact Forms
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link" href="system_settings.php">
                        <i class="fas fa-cog me-2"></i>Settings
                    </a>
                </li>
                <li class="nav-item mt-4">
                    <a class="nav-link text-danger" href="logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a>
                </li>
            </ul>
        </nav>
        <!-- Main Content -->
        <main class="col-lg-10 col-md-9 ms-sm-auto px-md-5 py-4">
            <h2 class="h3 mb-4 fw-bold text-primary">Admin Dashboard</h2>
            <!-- Stats Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="dashboard-card p-4 text-center border-0">
                        <div class="mb-2">
                            <i class="fas fa-users fa-2x text-primary"></i>
                        </div>
                        <h5 class="fw-semibold mb-1">Total Users</h5>
                        <h2 class="fw-bold"><?= $totalUsers ?></h2>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="dashboard-card p-4 text-center border-0">
                        <div class="mb-2">
                            <i class="fas fa-user-check fa-2x text-success"></i>
                        </div>
                        <h5 class="fw-semibold mb-1">Active Users</h5>
                        <h2 class="fw-bold"><?= $activeUsers ?></h2>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="dashboard-card p-4 text-center border-0">
                        <div class="mb-2">
                            <i class="fas fa-envelope fa-2x text-info"></i>
                        </div>
                        <h5 class="fw-semibold mb-1">Recent Contacts</h5>
                        <h2 class="fw-bold"><?= count($recentContacts) ?></h2>
                    </div>
                </div>
            </div>
            <!-- Recent Contacts -->
            <div class="dashboard-card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-envelope me-2"></i>Recent Contact Submissions</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Message</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentContacts as $contact): ?>
                                <tr>
                                    <td><?= htmlspecialchars($contact['username']) ?></td>
                                    <td><?= htmlspecialchars($contact['email']) ?></td>
                                    <td class="recent-contact-msg"><?= htmlspecialchars(substr($contact['message'], 0, 50)) ?><?= strlen($contact['message']) > 50 ? '...' : '' ?></td>
                                    <td><?= date('M j, Y', strtotime($contact['submitted_at'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- Recent Bookings -->
            <div class="dashboard-card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Recent Bookings</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Room Type</th>
                                    <th>Time In</th>
                                    <th>Time Out</th>
                                    <th>Duration (hrs)</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentBookings as $booking): ?>
                                <tr>
                                    <td><?= htmlspecialchars($booking['username']) ?></td>
                                    <td><?= ucfirst($booking['room_type']) ?></td>
                                    <td><?= date('M j, Y h:i A', strtotime($booking['time_in'])) ?></td>
                                    <td><?= date('M j, Y h:i A', strtotime($booking['time_out'])) ?></td>
                                    <td><?= number_format($booking['duration_hours'], 1) ?></td>
                                    <td>₱<?= number_format($booking['amount_paid'], 2) ?></td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $booking['status'] == 'confirmed' ? 'success' : 
                                            ($booking['status'] == 'pending' ? 'warning' : 
                                            ($booking['status'] == 'completed' ? 'info' : 'danger')) 
                                        ?>">
                                            <?= ucfirst($booking['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" action="update_booking_status.php" style="display:inline;">
                                            <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                            <select name="status" class="form-select form-select-sm d-inline-block w-auto">
                                                <option value="pending" <?= $booking['status']=='pending'?'selected':'' ?>>Pending</option>
                                                <option value="approved" <?= $booking['status']=='approved'?'selected':'' ?>>Approved</option>
                                                <option value="rejected" <?= $booking['status']=='rejected'?'selected':'' ?>>Rejected</option>
                                                <option value="completed" <?= $booking['status']=='completed'?'selected':'' ?>>Completed</option>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-primary ms-1">Update</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- Recent Activities -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Activities</h5>
                </div>
                <div class="card-body activity-list">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($recentActivities as $activity): ?>
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <strong><?= htmlspecialchars($activity['username']) ?></strong>
                                    <?= htmlspecialchars($activity['description']) ?>
                                </div>
                                <small class="text-muted"><?= 
                                    date('M j, H:i', strtotime($activity['created_at'])) 
                                ?></small>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>