<?php
require_once 'auth_check.php';
require_once 'db_connect.php';
require_once 'csrf.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'] ?? 0;
$csrfToken = generateCsrfToken();

// Automatically update booking statuses for the current user
$conn->query("UPDATE bookings SET status = 'activated' WHERE status = 'confirmed' AND NOW() >= time_in AND NOW() < time_out");
$conn->query("UPDATE bookings SET status = 'completed' WHERE status = 'activated' AND NOW() >= time_out");
$conn->query("UPDATE bookings SET status = 'cancelled' WHERE status = 'pending' AND NOW() > time_in AND user_id = $userId");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $_SESSION['booking_error'] = "Invalid form submission";
        header("Location: user_booking.php");
        exit();
    }

    // Handle delete request first
    if (isset($_POST['delete_booking_id'])) {
        $deleteId = intval($_POST['delete_booking_id']);
        $stmt = $conn->prepare("DELETE FROM bookings WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $deleteId, $userId);
        if ($stmt->execute()) {
            $_SESSION['booking_success'] = "Booking deleted successfully.";
        } else {
            $_SESSION['booking_error'] = "Failed to delete booking.";
        }
        header("Location: user_booking.php");
        exit();
    }
    // Only process booking creation if it's not a delete request
    else {
        $roomType = $_POST['room_type'] ?? 'regular';
        $bookingDate = $_POST['booking_date'] ?? date('Y-m-d');
        $bookingTime = $_POST['booking_time'] ?? date('H:i');
        $hours = isset($_POST['hours']) ? (int)$_POST['hours'] : 1;
        $extraMinutes = isset($_POST['extra_minutes']) ? (int)$_POST['extra_minutes'] : 0;
        $notes = $_POST['notes'] ?? '';
        
        if (!strtotime($bookingDate) || !strtotime($bookingTime)) {
            $_SESSION['booking_error'] = "Invalid date or time selected";
            header("Location: user_booking.php");
            exit();
        }
        
        $timeIn = date('Y-m-d H:i:s', strtotime("$bookingDate $bookingTime"));
        $timeOut = date('Y-m-d H:i:s', strtotime("$timeIn +$hours hours +$extraMinutes minutes"));
        $totalHours = $hours + ($extraMinutes / 60);

        if (strtotime($timeIn) < time()) {
            $_SESSION['booking_error'] = "You cannot book for a past date/time.";
            header("Location: user_booking.php");
            exit();
        }
        
        $rates = [
            'regular' => 20,
            'premium' => 150,
        ];
        $amountPaid = round($totalHours * ($rates[$roomType] ?? 20), 2);

        try {
            $stmt = $conn->prepare("INSERT INTO bookings 
                                  (user_id, room_type, time_in, time_out, duration_hours, amount_paid, notes, status) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
            
            $stmt->bind_param("isssdds", 
                $userId, 
                $roomType, 
                $timeIn, 
                $timeOut, 
                $totalHours, 
                $amountPaid, 
                $notes
            );
            
            if ($stmt->execute()) {
                $_SESSION['booking_success'] = "Booking submitted successfully!";
                header("Location: user_dashboard.php");
                exit();
            } else {
                throw new Exception("Database error: " . $stmt->error);
            }
        } catch (Exception $e) {
            $_SESSION['booking_error'] = "Error creating booking: " . $e->getMessage();
            header("Location: user_booking.php");
            exit();
        }
    }
}

$bookings = [];
if ($userId) {
    $result = $conn->query("SELECT * FROM bookings WHERE user_id = $userId ORDER BY time_in DESC");
    if ($result) {
        $bookings = $result->fetch_all(MYSQLI_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Bookings | Tasala</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            min-height: 100vh;
            font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
            /* Soft blue gradient background */
            background: linear-gradient(135deg, #f4f8fb 0%, #eaf0fa 100%) !important;
        }
        .dashboard-header {
            background: #fff;
            color: #1e3c72;
            border-radius: 1.5rem 1.5rem 0 0;
            padding: 1.5rem 2.5rem;
            margin-bottom: 0;
            font-weight: bold;
            font-size: 1.5rem;
            box-shadow: 0 2px 8px 0 rgba(30,60,114,0.08);
        }
        .card, .dashboard-card {
            background: #fff;
            border-radius: 1.5rem;
            box-shadow: 0 6px 32px 0 rgba(30,60,114,0.08), 0 1.5px 4px 0 rgba(42,82,152,0.08);
            margin-bottom: 2rem;
        }
        .booking-card {
            transition: box-shadow 0.2s;
        }
        .booking-card:hover {
            box-shadow: 0 8px 32px 0 rgba(30,60,114,0.16), 0 3px 8px 0 rgba(42,82,152,0.12);
        }
        .booking-status {
            font-size: 1rem;
            font-weight: 600;
            padding: 0.5rem 1.2rem;
            border-radius: 2rem;
        }
        .bg-upcoming { background: #1e90ff; color: #fff; }
        .bg-activated { background: #ffc107; color: #212529; }
        .bg-completed { background: #28a745; color: #fff; }
        .bg-cancelled { background: #dc3545; color: #fff; }
        .amount-display {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1e3c72;
        }
        .btn-booking {
            border-radius: 2rem;
            font-weight: 500;
            padding: 0.5rem 1.5rem;
        }
        .modal-content {
            border-radius: 1.5rem;
        }
        .form-control, .form-select {
            border-radius: 2rem;
        }
        input[type="number"]::-webkit-inner-spin-button,
        input[type="number"]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        .nav-pills .nav-link.active {
            background: linear-gradient(90deg, #1e3c72 60%, #2a5298 100%);
            color: #fff;
        }
        .booking-cancelled {
            opacity: 0.7;
            filter: grayscale(0.2);
            border: 2px solid #dc3545;
        }
    </style>
</head>
<body>
<?php include 'user_nav.php'; ?>

<div class="container py-5">
    <!-- Dashboard Header -->
    <div class="dashboard-header d-flex justify-content-between align-items-center mb-4 shadow">
        <div>
            <h2 class="mb-0"><i class="fa fa-calendar-check me-2"></i>Your Bookings</h2>
            <div class="small mt-1">Manage and review your room reservations</div>
        </div>
        <button class="btn btn-light btn-booking" data-bs-toggle="modal" data-bs-target="#bookingModal">
            <i class="fa fa-plus me-1"></i> New Booking
        </button>
    </div>

    <!-- Alerts -->
    <?php if (isset($_SESSION['booking_success'])): ?>
        <div class="alert alert-success shadow-sm" id="booking-alert">
            <?= $_SESSION['booking_success']; unset($_SESSION['booking_success']); ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['booking_error'])): ?>
        <div class="alert alert-danger shadow-sm" id="booking-alert">
            <?= $_SESSION['booking_error']; unset($_SESSION['booking_error']); ?>
        </div>
    <?php endif; ?>

    <!-- Bookings List -->
    <div class="dashboard-card">
        <div class="container py-5">
    <div class="row g-4">
        <?php $hasBookings = false; ?>
        <?php foreach ($bookings as $booking): ?>
            <?php
                $hasBookings = true;
                $currentTime = time();
                $timeIn = strtotime($booking['time_in']);
                $timeOut = strtotime($booking['time_out']);
                switch ($booking['status']) {
                    case 'completed':
                        $status = "Completed";
                        $badge = "bg-completed";
                        $icon = "fa-check-circle";
                        $border = "border-start border-4 border-success";
                        break;
                    case 'activated':
                        $status = "Activated";
                        $badge = "bg-activated";
                        $icon = "fa-bolt";
                        $border = "border-start border-4 border-warning";
                        break;
                    case 'confirmed':
                    case 'pending':
                        $status = "Upcoming";
                        $badge = "bg-upcoming";
                        $icon = "fa-clock";
                        $border = "border-start border-4 border-primary";
                        break;
                    case 'cancelled':
                        $status = "Cancelled";
                        $badge = "bg-cancelled";
                        $icon = "fa-ban";
                        $border = "border-start border-4 border-danger";
                        break;
                    default:
                        $status = ucfirst($booking['status']);
                        $badge = "bg-secondary";
                        $icon = "fa-question-circle";
                        $border = "border-start border-4 border-secondary";
                        break;
                }
            ?>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card booking-card h-100 shadow-sm <?= $border ?> <?= $status === "Cancelled" ? 'bg-light text-muted' : '' ?>">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-center mb-2">
                            <div class="flex-shrink-0">
                                <span class="badge <?= $badge ?> px-3 py-2 fs-6">
                                    <i class="fa <?= $icon ?> me-1"></i><?= $status ?>
                                </span>
                            </div>
                            <div class="ms-3 flex-grow-1">
                                <h5 class="mb-1"><?= htmlspecialchars(ucfirst($booking['room_type'])) ?> Room</h5>
                                <div class="text-muted small">
                                    <i class="fa fa-calendar-alt me-1"></i><?= date('M j, Y', $timeIn) ?>
                                    <i class="fa fa-clock ms-2 me-1"></i><?= date('g:i A', $timeIn) ?> - <?= date('g:i A', $timeOut) ?>
                                </div>
                                <?php if (!empty($booking['notes'])): ?>
                                    <div class="mt-1"><small class="text-muted"><i class="fa fa-sticky-note me-1"></i><?= htmlspecialchars($booking['notes']) ?></small></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2 mb-3">
                            <span class="amount-display">
                                <i class="fa fa-coins me-1"></i>₱<?= number_format($booking['amount_paid'], 2) ?>
                            </span>
                        </div>
                        <div class="d-flex gap-2 mt-auto">
                            <?php if ($status === "Upcoming"): ?>
                                <form method="POST" action="cancel_booking.php" class="flex-grow-1">
                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                    <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm w-100"
                                        onclick="return confirm('Are you sure you want to cancel this booking?');">
                                        <i class="fa fa-times-circle"></i> Cancel
                                    </button>
                                </form>
                            <?php endif; ?>
                            <form method="POST" action="user_booking.php" onsubmit="return confirm('Are you sure you want to delete this booking?');" class="flex-grow-1">
                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                <input type="hidden" name="delete_booking_id" value="<?= $booking['id'] ?>">
                                <button type="submit" class="btn btn-outline-secondary btn-sm w-100">
                                    <i class="fas fa-trash-alt me-1"></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (!$hasBookings): ?>
            <div class="col-12 text-center py-5">
                <i class="fa fa-calendar-times fa-3x text-secondary mb-3"></i>
                <p class="mb-0 text-muted fs-5">You have no bookings yet.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
    </div>
</div>

<!-- Booking Modal -->
<div class="modal fade" id="bookingModal" tabindex="-1" aria-labelledby="bookingModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content rounded-4 shadow">
      <form method="POST" action="user_booking.php">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <div class="modal-header bg-primary text-white rounded-top-4">
          <h5 class="modal-title" id="bookingModalLabel">
            <i class="fa fa-calendar-plus me-2"></i>New Booking
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body bg-light">
          <div class="mb-3">
            <label for="booking_date" class="form-label fw-semibold">
              <i class="fa fa-calendar-alt me-1"></i>Booking Date
            </label>
            <input type="date" class="form-control rounded-pill" id="booking_date" name="booking_date"
              min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="mb-3">
            <label for="booking_time" class="form-label fw-semibold">
              <i class="fa fa-clock me-1"></i>Start Time
            </label>
            <input type="time" class="form-control rounded-pill" id="booking_time" name="booking_time"
              value="<?= date('H:i') ?>" required>
          </div>
          <div class="mb-3">
            <label for="room_type" class="form-label fw-semibold">
              <i class="fa fa-door-open me-1"></i>Room Type
            </label>
            <select class="form-select rounded-pill" id="room_type" name="room_type" required>
              <option value="regular">Regular (₱20/hour)</option>
              <option value="premium">Premium (₱150/hour)</option>
            </select>
          </div>
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="hours" class="form-label fw-semibold">
                <i class="fa fa-hourglass-half me-1"></i>Hours
              </label>
              <div class="input-group">
                <input type="number" class="form-control rounded-start-pill" id="hours" name="hours" min="1" value="1" required>
                <span class="input-group-text rounded-end-pill">hr(s)</span>
              </div>
            </div>
            <div class="col-md-6">
              <label for="extra_minutes" class="form-label fw-semibold">
                <i class="fa fa-stopwatch me-1"></i>Extra Minutes
              </label>
              <div class="input-group">
                <input type="number" class="form-control rounded-start-pill" id="extra_minutes" name="extra_minutes" min="0" max="59" value="0">
                <span class="input-group-text rounded-end-pill">min(s)</span>
              </div>
            </div>
          </div>
          <div class="mb-3">
            <label for="notes" class="form-label fw-semibold">
              <i class="fa fa-sticky-note me-1"></i>Notes (Optional)
            </label>
            <textarea class="form-control rounded-3" id="notes" name="notes" rows="2" placeholder="Any special requests?"></textarea>
          </div>
          <div class="amount-display bg-white border rounded-3 shadow-sm mb-2 py-2 px-3 text-center" id="amount-display">
            <i class="fa fa-receipt me-1"></i>
            <span class="fw-bold">Estimated Amount: ₱20.00</span>
          </div>
        </div>
        <div class="modal-footer bg-light rounded-bottom-4">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
            <i class="fa fa-times me-1"></i>Cancel
          </button>
          <button type="submit" class="btn btn-primary">
            <i class="fa fa-check me-1"></i>Book Now
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function calculateAmount() {
    const roomType = document.getElementById('room_type').value;
    const hours = parseInt(document.getElementById('hours').value) || 0;
    const minutes = parseInt(document.getElementById('extra_minutes').value) || 0;
    if (roomType) {
        const rates = {
            'regular': 20,
            'premium': 150,
        };
        const totalHours = hours + (minutes / 60);
        const amount = (totalHours * rates[roomType]).toFixed(2);
        const amountDisplay = document.getElementById('amount-display');
        if (amountDisplay) {
            amountDisplay.innerHTML = `<i class="fa fa-receipt me-1"></i> <span class="fw-bold">Estimated Amount: ₱${amount}</span>`;
        }
    }
}
document.getElementById('room_type').addEventListener('change', calculateAmount);
document.getElementById('hours').addEventListener('change', calculateAmount);
document.getElementById('extra_minutes').addEventListener('change', calculateAmount);
calculateAmount();

// Auto-dismiss booking alert after 3 seconds
setTimeout(function() {
    var alert = document.getElementById('booking-alert');
    if(alert) {
        alert.classList.add('fade');
        setTimeout(function() {
            alert.style.display = 'none';
        }, 500);
    }
}, 3000);
</script>
</body>
</html>