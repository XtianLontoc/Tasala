
<?php
require_once 'auth_check.php';
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header("Location: login.php");
    exit();
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'], $_POST['status'])) {
    $booking_id = intval($_POST['booking_id']);
    $status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $booking_id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Booking status updated.";
    } else {
        $_SESSION['error'] = "Failed to update status.";
    }
    $stmt->close();
    header("Location: admins_booking.php");
    exit();
}

// Handle delete booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_booking_id'])) {
    $booking_id = intval($_POST['delete_booking_id']);
    $stmt = $conn->prepare("DELETE FROM bookings WHERE id = ?");
    $stmt->bind_param("i", $booking_id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Booking deleted successfully.";
    } else {
        $_SESSION['error'] = "Failed to delete booking.";
    }
    $stmt->close();
    header("Location: admins_booking.php");
    exit();
}

// Handle user-specific report generation
if (isset($_GET['generate_user_report'])) {
    $user_id = intval($_GET['generate_user_report']);
    $type = $_GET['type'];
    
    $bookingQuery = $conn->prepare("SELECT b.*, u.username, u.email FROM bookings b JOIN users u ON b.user_id = u.id WHERE b.user_id = ? ORDER BY b.time_in DESC");
    $bookingQuery->bind_param("i", $user_id);
    $bookingQuery->execute();
    $result = $bookingQuery->get_result();
    $bookings = $result->fetch_all(MYSQLI_ASSOC);
    
    if ($type === 'pdf') {
        require_once 'tcpdf/tcpdf.php';
        
        // Create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Tasala Admin');
        $pdf->SetTitle('User Bookings Report');
        $pdf->SetSubject('User Bookings Data');
        
        // Add a page
        $pdf->AddPage();
        
        // Set font
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Tasala - User Bookings Report', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 10, 'User: ' . htmlspecialchars($bookings[0]['username']), 0, 1, 'C');
        $pdf->Cell(0, 10, 'Email: ' . htmlspecialchars($bookings[0]['email']), 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 10, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
        $pdf->Ln(10);
        
        // Create table header
        $html = '<table border="1" cellpadding="4">
            <thead>
                <tr style="background-color:#f2f2f2;">
                    <th>Room Type</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                    <th>Status</th>
                    <th>Amount Paid</th>
                </tr>
            </thead>
            <tbody>';
        
        // Add table rows
        foreach ($bookings as $booking) {
            $html .= '<tr>
                <td>'.htmlspecialchars($booking['room_type']).'</td>
                <td>'.date('M j, Y g:i A', strtotime($booking['time_in'])).'</td>
                <td>'.date('M j, Y g:i A', strtotime($booking['time_out'])).'</td>
                <td>'.ucfirst($booking['status']).'</td>
                <td>₱'.number_format($booking['amount_paid'], 2).'</td>
            </tr>';
        }
        
        $html .= '</tbody></table>';
        
        // Output the HTML content
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // Close and output PDF document
        $pdf->Output('user_'.$user_id.'_bookings_report_'.date('Ymd_His').'.pdf', 'D');
        exit();
        
    } elseif ($type === 'excel') {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="user_'.$user_id.'_bookings_report_'.date('Ymd_His').'.xls"');
        header('Cache-Control: max-age=0');
        
        echo '<table border="1">
            <thead>
                <tr>
                    <th colspan="5" style="text-align:center;">User Bookings Report</th>
                </tr>
                <tr>
                    <th colspan="5" style="text-align:center;">User: '.htmlspecialchars($bookings[0]['username']).'</th>
                </tr>
                <tr>
                    <th colspan="5" style="text-align:center;">Email: '.htmlspecialchars($bookings[0]['email']).'</th>
                </tr>
                <tr>
                    <th colspan="5" style="text-align:center;">Generated on: '.date('Y-m-d H:i:s').'</th>
                </tr>
                <tr>
                    <th>Room Type</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                    <th>Status</th>
                    <th>Amount Paid</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($bookings as $booking) {
            echo '<tr>
                <td>'.htmlspecialchars($booking['room_type']).'</td>
                <td>'.date('M j, Y g:i A', strtotime($booking['time_in'])).'</td>
                <td>'.date('M j, Y g:i A', strtotime($booking['time_out'])).'</td>
                <td>'.ucfirst($booking['status']).'</td>
                <td>₱'.number_format($booking['amount_paid'], 2).'</td>
            </tr>';
        }
        
        echo '</tbody></table>';
        exit();
    }
}

// Handle export requests for all bookings
if (isset($_GET['export'])) {
    $type = $_GET['export'];
    
    // Build the base query
    $query = "SELECT b.*, u.username, u.email FROM bookings b JOIN users u ON b.user_id = u.id";
    
    // Check if date range is provided
    $whereClauses = [];
    $params = [];
    $types = '';
    
    if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
        $start_date = $_GET['start_date'];
        $end_date = $_GET['end_date'];
        
        // Validate dates
        if (DateTime::createFromFormat('Y-m-d', $start_date) !== false && 
            DateTime::createFromFormat('Y-m-d', $end_date) !== false) {
            
            $whereClauses[] = "DATE(b.time_in) BETWEEN ? AND ?";
            $params[] = $start_date;
            $params[] = $end_date;
            $types .= 'ss';
        }
    }
    
    // Add WHERE clause if needed
    if (!empty($whereClauses)) {
        $query .= " WHERE " . implode(" AND ", $whereClauses);
    }
    
    $query .= " ORDER BY b.time_in DESC";
    
    // Prepare and execute the query
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $bookings = $result->fetch_all(MYSQLI_ASSOC);
    
    if ($type === 'pdf') {
        require_once 'tcpdf/tcpdf.php';
        
        // Create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Tasala Admin');
        $pdf->SetTitle('Bookings Report');
        $pdf->SetSubject('Bookings Data');
        
        // Add a page
        $pdf->AddPage();
        
        // Set font
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Tasala - Bookings Report', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 10, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
        $pdf->Ln(10);
        
        // Create table header
        $html = '<table border="1" cellpadding="4">
            <thead>
                <tr style="background-color:#f2f2f2;">
                    <th>User</th>
                    <th>Email</th>
                    <th>Room Type</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                    <th>Status</th>
                    <th>Amount Paid</th>
                </tr>
            </thead>
            <tbody>';
        
        // Add table rows
        foreach ($bookings as $booking) {
            $html .= '<tr>
                <td>'.htmlspecialchars($booking['username']).'</td>
                <td>'.htmlspecialchars($booking['email']).'</td>
                <td>'.htmlspecialchars($booking['room_type']).'</td>
                <td>'.date('M j, Y g:i A', strtotime($booking['time_in'])).'</td>
                <td>'.date('M j, Y g:i A', strtotime($booking['time_out'])).'</td>
                <td>'.ucfirst($booking['status']).'</td>
                <td>₱'.number_format($booking['amount_paid'], 2).'</td>
            </tr>';
        }
        
        $html .= '</tbody></table>';
        
        // Output the HTML content
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // Close and output PDF document
        $pdf->Output('bookings_report_'.date('Ymd_His').'.pdf', 'D');
        exit();
        
    } elseif ($type === 'excel') {
        $filename = 'bookings_report_'.date('Ymd_His').'.xls';
        
        // Customize filename if filtered by date
        if (isset($start_date) && isset($end_date)) {
            $filename = 'bookings_'.$start_date.'_to_'.$end_date.'.xls';
        }
        
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$filename.'"');
        header('Cache-Control: max-age=0');
        
        // Add date range info to report if filtered
        $dateRangeInfo = '';
        if (isset($start_date) && isset($end_date)) {
            $dateRangeInfo = '<tr>
                <th colspan="7" style="text-align:center;">Date Range: '.htmlspecialchars($start_date).' to '.htmlspecialchars($end_date).'</th>
            </tr>';
        }
        
        echo '<table border="1">
            <thead>
                <tr>
                    <th colspan="7" style="text-align:center;">Bookings Report</th>
                </tr>
                '.$dateRangeInfo.'
                <tr>
                    <th colspan="7" style="text-align:center;">Generated on: '.date('Y-m-d H:i:s').'</th>
                </tr>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Room Type</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                    <th>Status</th>
                    <th>Amount Paid</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($bookings as $booking) {
            echo '<tr>
                <td>'.htmlspecialchars($booking['username']).'</td>
                <td>'.htmlspecialchars($booking['email']).'</td>
                <td>'.htmlspecialchars($booking['room_type']).'</td>
                <td>'.date('M j, Y g:i A', strtotime($booking['time_in'])).'</td>
                <td>'.date('M j, Y g:i A', strtotime($booking['time_out'])).'</td>
                <td>'.ucfirst($booking['status']).'</td>
                <td>₱'.number_format($booking['amount_paid'], 2).'</td>
            </tr>';
        }
        
        echo '</tbody></table>';
        exit();
    }
}
// Fetch bookings with user info for normal display
$bookingQuery = $conn->query("SELECT b.*, u.username, u.email FROM bookings b JOIN users u ON b.user_id = u.id ORDER BY b.time_in DESC");
$bookings = $bookingQuery->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Bookings</title>
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
        .export-buttons {
            margin-bottom: 20px;
        }
        .btn-generate {
            background-color: #6c757d;
            color: white;
        }
        .btn-generate:hover {
            background-color: #5a6268;
            color: white;
        }

        /* Add these styles to your existing CSS */
.export-section {
    background: #fff;
    padding: 1.25rem;
    border-radius: 0.75rem;
    box-shadow: 0 2px 8px rgba(30, 60, 114, 0.08);
}

.date-range-filter .input-group-text {
    color: #1e3c72;
    font-weight: 500;
    border-color: #dee2e6;
}

.date-range-filter .date-input {
    min-width: 140px;
    border-color: #dee2e6;
}

.date-range-filter .date-input:focus {
    border-color: #1e3c72;
    box-shadow: 0 0 0 0.25rem rgba(30, 60, 114, 0.15);
}

.date-range-filter .btn {
    border-radius: 0 0.375rem 0.375rem 0 !important;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .export-section .row {
        gap: 1rem;
    }
    
    .date-range-filter .input-group {
        flex-wrap: wrap;
    }
    
    .date-range-filter .input-group > * {
        flex: 1 1 100%;
        border-radius: 0.375rem !important;
        margin-bottom: 0.5rem;
    }
    
    .date-range-filter .btn {
        width: 100%;
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
                    <a class="nav-link" href="admin_dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link" href="manage_users.php">
                        <i class="fas fa-users me-2"></i>Manage Users
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link active" href="admins_booking.php">
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

        <main class="col-lg-10 col-md-9 ms-sm-auto px-md-5 py-4">
            <h2 class="h3 mb-4 fw-bold text-primary">Manage Bookings</h2>
            
            <!-- Export Buttons -->
           <!-- Replace the existing export buttons section with this: -->
<div class="export-section mb-4">
    <div class="row g-3 align-items-center">
        <!-- Export All Buttons -->
        <div class="col-md-auto">
            <div class="btn-group" role="group">
                <a href="admins_booking.php?export=pdf" class="btn btn-danger">
                    <i class="fas fa-file-pdf me-2"></i>Export PDF
                </a>
                <a href="admins_booking.php?export=excel" class="btn btn-success">
                    <i class="fas fa-file-excel me-2"></i>Export Excel
                </a>
            </div>
        </div>
                
        <!-- Date Range Filter -->
        <div class="col-md-auto">
            <form method="GET" action="admins_booking.php" class="date-range-filter">
                <input type="hidden" name="export" value="excel">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="fas fa-calendar-alt text-primary"></i>
                    </span>
                    <input type="date" name="start_date" class="form-control date-input" placeholder="Start Date" required>
                    <span class="input-group-text bg-white">to</span>
                    <input type="date" name="end_date" class="form-control date-input" placeholder="End Date" required>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-download me-2"></i>Export
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
            
            <div class="dashboard-card p-4">
                <div class="table-responsive">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success" id="success-alert"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger" id="error-alert"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
                    <?php endif; ?>
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Room Type</th>
                                <th>Time In</th>
                                <th>Time Out</th>
                                <th>Status</th>
                                <th>Amount Paid</th>
                                <th>Change Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td><?= htmlspecialchars($booking['username']) ?></td>
                                    <td><?= htmlspecialchars($booking['email']) ?></td>
                                    <td><?= htmlspecialchars($booking['room_type']) ?></td>
                                    <td><?= date('M j, Y g:i A', strtotime($booking['time_in'])) ?></td>
                                    <td><?= date('M j, Y g:i A', strtotime($booking['time_out'])) ?></td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $booking['status'] == 'confirmed' ? 'success' : 
                                            ($booking['status'] == 'pending' ? 'warning' : 
                                            ($booking['status'] == 'completed' ? 'info' : 'danger')) 
                                        ?>">
                                            <?= ucfirst($booking['status']) ?>
                                        </span>
                                    </td>
                                    <td>₱<?= number_format($booking['amount_paid'], 2) ?></td>
                                    <td>
                                        <form method="POST" action="admins_booking.php" class="d-flex align-items-center gap-1">
                                            <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                            <select name="status" class="form-select form-select-sm">
                                                <option value="pending" <?= $booking['status']=='pending'?'selected':'' ?>>Pending</option>
                                                <option value="confirmed" <?= $booking['status']=='confirmed'?'selected':'' ?>>Confirmed</option>
                                                <option value="completed" <?= $booking['status']=='completed'?'selected':'' ?>>Completed</option>
                                                <option value="cancelled" <?= $booking['status']=='cancelled'?'selected':'' ?>>Cancelled</option>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-primary">Update</button>
                                        </form>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-generate dropdown-toggle" type="button" id="generateDropdown<?= $booking['id'] ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-file-export me-1"></i>Generate
                                                </button>
                                                <ul class="dropdown-menu" aria-labelledby="generateDropdown<?= $booking['id'] ?>">
                                                    <li><a class="dropdown-item" href="admins_booking.php?generate_user_report=<?= $booking['user_id'] ?>&type=pdf"><i class="fas fa-file-pdf me-2"></i>PDF Report</a></li>
                                                    <li><a class="dropdown-item" href="admins_booking.php?generate_user_report=<?= $booking['user_id'] ?>&type=excel"><i class="fas fa-file-excel me-2"></i>Excel Report</a></li>
                                                </ul>
                                            </div>
                                            <form method="POST" action="admins_booking.php" onsubmit="return confirm('Are you sure you want to delete this booking?');">
                                                <input type="hidden" name="delete_booking_id" value="<?= $booking['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($bookings)): ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted">No bookings found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        var success = document.getElementById('success-alert');
        if (success) success.style.display = 'none';
        var error = document.getElementById('error-alert');
        if (error) error.style.display = 'none';
    }, 3000); // 3 seconds
});

document.addEventListener('DOMContentLoaded', function() {
    // Set default dates (last 30 days)
    const startDate = document.querySelector('input[name="start_date"]');
    const endDate = document.querySelector('input[name="end_date"]');
    
    if (startDate && endDate) {
        // Set end date to today
        const today = new Date().toISOString().split('T')[0];
        endDate.value = today;
        
        // Set start date to 30 days ago
        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
        startDate.value = thirtyDaysAgo.toISOString().split('T')[0];
        
        // Set max date for both to today
        startDate.max = today;
        endDate.max = today;
        
        // Update min/max when dates change
        startDate.addEventListener('change', function() {
            endDate.min = this.value;
        });
        
        endDate.addEventListener('change', function() {
            if (startDate.value && this.value < startDate.value) {
                this.value = startDate.value;
            }
        });
    }
});

</script>
</body>
</html>