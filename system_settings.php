<?php
require_once 'auth_check.php';
require_once 'db_connect.php';

if ($_SESSION['user_role'] != 1) {
    header("Location: user_dashboard.php");
    exit();
}

// Fetch current settings
$settings = $conn->query("SELECT * FROM settings LIMIT 1")->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $site_name = trim($_POST['site_name'] ?? '');
    $support_email = trim($_POST['support_email'] ?? '');
    $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;

    $stmt = $conn->prepare("UPDATE settings SET site_name=?, support_email=?, maintenance_mode=? WHERE id=?");
    $stmt->bind_param("ssii", $site_name, $support_email, $maintenance_mode, $settings['id']);
    if ($stmt->execute()) {
        $_SESSION['settings_success'] = "Settings updated successfully!";
    } else {
        $_SESSION['settings_error'] = "Failed to update settings.";
    }
    header("Location: system_settings.php");
    exit();
}

// Fetch updated settings after redirect or on GET
$settings = $conn->query("SELECT * FROM settings LIMIT 1")->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
    <title>System Settings | Tasala</title>
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
        .form-control, .btn {
            border-radius: 8px;
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
        <!-- Main Content -->
        <main class="col-lg-10 col-md-9 ms-sm-auto px-md-5 py-4">
            <h2 class="mb-4">System Settings</h2>
            <div class="dashboard-card p-4">
                <?php if (!empty($_SESSION['settings_success'])): ?>
                    <div class="alert alert-success"><?= $_SESSION['settings_success']; unset($_SESSION['settings_success']); ?></div>
                <?php elseif (!empty($_SESSION['settings_error'])): ?>
                    <div class="alert alert-danger"><?= $_SESSION['settings_error']; unset($_SESSION['settings_error']); ?></div>
                <?php endif; ?>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="site_name" class="form-label">Site Name</label>
                        <input type="text" class="form-control" id="site_name" name="site_name" value="<?= htmlspecialchars($settings['site_name'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="support_email" class="form-label">Support Email</label>
                        <input type="email" class="form-control" id="support_email" name="support_email" value="<?= htmlspecialchars($settings['support_email'] ?? '') ?>" required>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="maintenance_mode" name="maintenance_mode" value="1" <?= !empty($settings['maintenance_mode']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="maintenance_mode">Maintenance Mode</label>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </main>
    </div>
</div>
</body>
</html>
