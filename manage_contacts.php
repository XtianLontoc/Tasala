<?php
require_once 'auth_check.php';
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header("Location: login.php");
    exit();
}

// Mark as read and notify user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read_id'])) {
    $contact_id = intval($_POST['mark_read_id']);
    $contact = $conn->query("SELECT * FROM contacts WHERE id = $contact_id")->fetch_assoc();
    if ($contact) {
        // Mark as read (even if already read)
        $conn->query("UPDATE contacts SET status = 'read' WHERE id = $contact_id");
        // Find the user by email (if exists)
        $user = $conn->query("SELECT id FROM users WHERE email = '" . $conn->real_escape_string($contact['email']) . "'")->fetch_assoc();
        if ($user) {
            // Insert notification
            $msg = "Your contact message has been marked as read by admin.";
            $conn->query("INSERT INTO notifications (user_id, message, created_at, is_read) VALUES ({$user['id']}, '{$conn->real_escape_string($msg)}', NOW(), 0)");
        }
        $_SESSION['success'] = "Marked as read and user notified.";
    } else {
        $_SESSION['error'] = "Contact not found.";
    }
    header("Location: manage_contacts.php");
    exit();
}

// Mark as unread
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_unread_id'])) {
    $contact_id = intval($_POST['mark_unread_id']);
    $contact = $conn->query("SELECT * FROM contacts WHERE id = $contact_id")->fetch_assoc();
    if ($contact) {
        $conn->query("UPDATE contacts SET status = 'unread' WHERE id = $contact_id");
        $_SESSION['success'] = "Marked as unread.";
    } else {
        $_SESSION['error'] = "Contact not found.";
    }
    header("Location: manage_contacts.php");
    exit();
}

// Delete contact message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_contact_id'])) {
    $contact_id = intval($_POST['delete_contact_id']);
    $deleted = $conn->query("DELETE FROM contacts WHERE id = $contact_id");
    if ($deleted) {
        $_SESSION['success'] = "Contact message deleted.";
    } else {
        $_SESSION['error'] = "Failed to delete contact message.";
    }
    header("Location: manage_contacts.php");
    exit();
}

// Search filter
$search = isset($_GET['search']) ? $_GET['search'] : '';
$searchSql = $search ? "WHERE name LIKE '%$search%' OR email LIKE '%$search%' OR message LIKE '%$search%'" : '';

// Sorting
$order = isset($_GET['order']) && $_GET['order'] === 'asc' ? 'ASC' : 'DESC';

$stmt = $conn->query("SELECT * FROM contacts $searchSql ORDER BY submitted_at $order");
$contacts = $stmt->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Contacts</title>
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

        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .btn-darkblue {
            background-color: #1e3c72;
            color: white;
        }
        .btn-darkblue:hover {
            background-color: #16305b;
        }
        .form-control, .btn {
            border-radius: 8px;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar (reuse your admin sidebar if available) -->
        <nav class="col-lg-2 col-md-3 d-none d-md-block sidebar py-4 px-3">
            <div class="mb-4 text-center">
                <h4 class="fw-bold text-white mb-0">Tasala Admin</h4>
                <hr class="bg-white opacity-50">
            </div>
            <ul class="nav flex-column">
                <li class="nav-item mb-2">
                    <a class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? ' active' : '' ?>" href="admin_dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'manage_users.php' ? ' active' : '' ?>" href="manage_users.php">
                        <i class="fas fa-users me-2"></i>Manage Users
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'admins_booking.php' ? ' active' : '' ?>" href="admins_booking.php">
                        <i class="fas fa-calendar-check me-2"></i>Bookings
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'manage_contacts.php' ? ' active' : '' ?>" href="manage_contacts.php">
                        <i class="fas fa-envelope me-2"></i>Contact Forms
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'system_settings.php' ? ' active' : '' ?>" href="system_settings.php">
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
        <main class="col-lg-10 col-md-9 ms-sm-auto px-md-5 py-4">
            <h2 class="h3 mb-4 fw-bold text-primary">Manage Contact Submissions</h2>
            <div class="dashboard-card p-4">
                <form class="row mb-3" method="GET">
                    <div class="col-md-6">
                        <input type="text" name="search" class="form-control" placeholder="Search by NAME, email, or message" value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="order" class="form-select">
                            <option value="desc" <?= $order === 'DESC' ? 'selected' : '' ?>>Newest First</option>
                            <option value="asc" <?= $order === 'ASC' ? 'selected' : '' ?>>Oldest First</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-darkblue w-100">Search & Sort</button>
                    </div>
                </form>
                <table class="table table-bordered align-middle">
                    <thead>
                    <tr>
                        <th>Username</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Email</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th>Submitted At</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($contacts as $contact): ?>
                        <tr>
                            <td><?= htmlspecialchars($contact['username'] ?? '') ?></td>
                            <td><?= htmlspecialchars($contact['first_name'] ?? '') ?></td>
                            <td><?= htmlspecialchars($contact['last_name'] ?? '') ?></td>
                            <td><?= htmlspecialchars($contact['email'] ?? '') ?></td>
                            <td>
                                <?= htmlspecialchars(substr($contact['message'], 0, 30)) ?>...
                                <button type="button" class="btn btn-link p-0 ms-1" data-bs-toggle="modal" data-bs-target="#msgModal<?= $contact['id'] ?>">
                                    View
                                </button>
                            </td>
                            <td>
                                <span class="badge bg-<?= $contact['status'] == 'unread' ? 'warning' : 'success' ?>">
                                    <?= ucfirst($contact['status']) ?>
                                </span>
                            </td>
                            <td><?= date('M j, Y H:i', strtotime($contact['submitted_at'])) ?></td>
                            <td>
                                <?php if ($contact['status'] == 'unread'): ?>
                                    <form method="POST" action="manage_contacts.php" style="display:inline;">
                                        <input type="hidden" name="mark_read_id" value="<?= $contact['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-darkblue mb-1">
                                            Mark as Read
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" action="manage_contacts.php" style="display:inline;">
                                        <input type="hidden" name="mark_unread_id" value="<?= $contact['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-warning text-dark mb-1">
                                            Mark as Unread
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" action="manage_contacts.php" style="display:inline;" onsubmit="return confirm('Delete this message?');">
                                    <input type="hidden" name="delete_contact_id" value="<?= $contact['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<?php foreach ($contacts as $contact): ?>
    <div class="modal fade" id="msgModal<?= $contact['id'] ?>" tabindex="-1" aria-labelledby="msgModalLabel<?= $contact['id'] ?>" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="msgModalLabel<?= $contact['id'] ?>">
                <?= htmlspecialchars($contact['subject'] ?? 'No Subject') ?>
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <?= nl2br(htmlspecialchars($contact['message'])) ?>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
<?php endforeach; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
