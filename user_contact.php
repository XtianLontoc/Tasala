<?php
require_once 'auth_check.php';
require_once 'db_connect.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$username = $_SESSION['username'] ?? '';
$first_name = $_SESSION['first_name'] ?? '';
$last_name = $_SESSION['last_name'] ?? '';
$email = $_SESSION['email'] ?? '';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    if ($subject === '' || $message === '') {
        $msg = '<div class="alert alert-danger">Subject and message cannot be empty.</div>';
    } else {
        $stmt = $conn->prepare("INSERT INTO contacts (username, first_name, last_name, email, subject, message, status, submitted_at) VALUES (?, ?, ?, ?, ?, ?, 'unread', NOW())");
        $stmt->bind_param("ssssss", $username, $first_name, $last_name, $email, $subject, $message);
        if ($stmt->execute()) {
            $_SESSION['contact_success'] = 'Your message has been sent!';
            header("Location: user_contact.php");
            exit();
        } else {
            $msg = '<div class="alert alert-danger">Failed to send message.</div>';
        }
        $stmt->close();
    }
}



?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Contact Support | Tasala</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            min-height: 100vh;
            font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
            /* Soft blue gradient background */
            background: linear-gradient(135deg, #f4f8fb 0%, #eaf0fa 100%) !important;
        }
        .card, .dashboard-card {
            background: #fff;
            border-radius: 1.5rem;
            box-shadow: 0 6px 32px 0 rgba(30,60,114,0.08), 0 1.5px 4px 0 rgba(42,82,152,0.08);
            margin-bottom: 2rem;
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
        .nav-pills .nav-link.active {
            background: linear-gradient(90deg, #1e3c72 60%, #2a5298 100%);
            color: #fff;
        }
        .amount-display {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1e3c72;
        }
    </style>
</head>
<body>
<?php include 'user_nav.php'; ?>
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-7">
      <div class="card shadow-lg border-0 mb-4">
        <div class="card-header bg-primary text-white">
          <h4 class="mb-0"><i class="fa fa-headset me-2"></i>Contact Support</h4>
        </div>
        <div class="card-body bg-light">
          <?php
          if (isset($_SESSION['contact_success'])) {
              echo '<div class="alert alert-success">' . $_SESSION['contact_success'] . '</div>';
              unset($_SESSION['contact_success']);
          }
          if (!empty($msg)) echo $msg;
          ?>
          <form method="POST">
            <div class="mb-3">
              <label class="form-label">Subject</label>
              <input type="text" name="subject" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Message</label>
              <textarea name="message" class="form-control" rows="5" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">
              <i class="fa fa-paper-plane me-1"></i>Send
            </button>
          </form>
        </div>
      </div>
      <!-- Company Info Card -->
      <div class="card shadow-sm border-0">
        <div class="card-header bg-secondary text-white">
          <h5 class="mb-0"><i class="fa fa-building me-2"></i>Company Information</h5>
        </div>
        <div class="card-body bg-light">
          <ul class="list-unstyled mb-0">
            <li class="mb-2">
              <i class="fa fa-map-marker-alt me-2 text-primary"></i>
              123 Tasala St., City, Country
            </li>
            <li class="mb-2">
              <i class="fa fa-phone me-2 text-primary"></i>
              +63 912 345 6789
            </li>
            <li class="mb-2">
              <i class="fa fa-envelope me-2 text-primary"></i>
              support@tasala.com
            </li>
            <li>
              <i class="fa fa-globe me-2 text-primary"></i>
              www.tasala.com
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</html>
