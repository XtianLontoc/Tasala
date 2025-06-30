<?php
// user_nav.php
require_once 'auth_check.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

$notifCount = 0;
$notifList = [];
if ($userId) {
    $notifResult = $conn->query("SELECT * FROM notifications WHERE user_id = $userId ORDER BY created_at DESC LIMIT 5");
    $notifList = $notifResult ? $notifResult->fetch_all(MYSQLI_ASSOC) : [];

    $result = $conn->query("SELECT COUNT(*) FROM notifications WHERE user_id = $userId AND is_read = 0");
    if ($result) {
        $notifCount = (int)$result->fetch_row()[0];
    }
}
?>
<!-- REMOVE Bootstrap CSS/JS from here -->
<nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(90deg, #1e3c72 60%, #2a5298 100%);">
  <div class="container">
    <a class="navbar-brand fw-bold" href="user_booking.php">
      <i class="fa fa-gem me-2"></i>Tasala
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#userNavbar" aria-controls="userNavbar" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="userNavbar">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
        <li class="nav-item">
          <a class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'user_dashboard.php' ? ' active' : '' ?>" href="user_dashboard.php">
            <i class="fa fa-home me-1"></i>Dashboard
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'user_booking.php' ? ' active' : '' ?>" href="user_booking.php">
            <i class="fa fa-calendar-check me-1"></i>Bookings
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'profile.php' ? ' active' : '' ?>" href="profile.php">
            <i class="fa fa-user-circle me-1"></i>Profile
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'user_contact.php' ? ' active' : '' ?>" href="user_contact.php">
            <i class="fa fa-headset me-1"></i>Contact Support
          </a>
        </li>
        <!-- Notification Bell in Navbar -->
        <li class="nav-item dropdown">
            <a class="nav-link position-relative" href="#" id="notifDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-bell" id="notification-bell"></i>
                <?php if ($notifCount > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <?= $notifCount ?>
                    </span>
                <?php endif; ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notifDropdown" style="min-width: 300px;">
                <li class="dropdown-header">Notifications</li>
                <?php if (count($notifList)): ?>
                    <?php foreach ($notifList as $notif): ?>
                        <li>
                            <div class="dropdown-item<?= $notif['is_read'] ? '' : ' fw-bold' ?>">
                                <?= htmlspecialchars($notif['message']) ?>
                                <br>
                                <small class="text-muted"><?= date('M j, Y H:i', strtotime($notif['created_at'])) ?></small>
                            </div>
                        </li>
                    <?php endforeach; ?>s
                <?php else: ?>
                    <li><span class="dropdown-item text-muted">No notifications</span></li>
                <?php endif; ?>
            </ul>
        </li>
        <li class="nav-item ms-lg-3">
          <a class="btn btn-outline-light btn-sm px-3" href="logout.php">
            <i class="fa fa-sign-out-alt me-1"></i>Logout
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>
<script>
// Listen for dropdown show event on the parent .dropdown
document.querySelectorAll('.dropdown').forEach(function(dropdown) {
    dropdown.addEventListener('show.bs.dropdown', function () {
        fetch('mark_notifications_read.php', {method: 'POST'})
            .then(() => {
                // Optionally, remove the badge after marking as read
                const badge = dropdown.querySelector('.badge');
                if (badge) badge.remove();
            });
    });
});



</script>