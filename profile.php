<?php
require_once 'auth_check.php';
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle Delete Account
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_account'])) {
    error_log("Delete account attempt for user ID: " . $userId);
    
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    
    if ($stmt->execute()) {
        error_log("Account deleted successfully for user ID: " . $userId);
        session_destroy();
        header("Location: goodbye.php");
        exit();
    } else {
        error_log("Account deletion failed: " . $stmt->error);
        $error = "Failed to delete account. Please try again.";
    }
    $stmt->close();
} 
// Handle Password Change
elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['current_password'], $_POST['new_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];

    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($db_password);
    
    if ($stmt->fetch()) {
        if ($current_password === $db_password) {
            $stmt->close();
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $new_password, $userId);
            
            if ($stmt->execute()) {
                $_SESSION['profile_success'] = "Password updated successfully.";
            } else {
                $_SESSION['profile_error'] = "Error updating password: " . $stmt->error;
            }
        } else {
            $_SESSION['profile_error'] = "Current password is incorrect.";
        }
    } else {
        $_SESSION['profile_error'] = "User not found.";
    }
    $stmt->close();
    header("Location: profile.php");
    exit();
} 
// Handle Profile Update
elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    
    // Profile picture upload
    $profile_picture = null;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['profile_picture']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            $ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $fileName = 'user_' . $userId . '_' . time() . '.' . $ext;
            $uploadDir = 'uploads/';
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $targetFile = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetFile)) {
                $profile_picture = $fileName;
                // Delete old profile picture if it exists
                if (!empty($user['profile_picture']) && file_exists($uploadDir . $user['profile_picture'])) {
                    unlink($uploadDir . $user['profile_picture']);
                }
            } else {
                $error = "Failed to upload profile picture.";
            }
        } else {
            $error = "Only JPG, PNG, and GIF files are allowed.";
        }
    }

    // Update query
    $sql = "UPDATE users SET first_name = ?, last_name = ?, username = ?, email = ?";
    $params = [$first_name, $last_name, $username, $email];
    $types = "ssss";
    
    if ($profile_picture) {
        $sql .= ", profile_picture = ?";
        $params[] = $profile_picture;
        $types .= "s";
    }
    
    $sql .= " WHERE id = ?";
    $params[] = $userId;
    $types .= "i";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        $_SESSION['profile_success'] = "Profile updated successfully.";
    } else {
        $_SESSION['profile_error'] = "Error updating profile: " . $stmt->error;
    }
    $stmt->close();
    header("Location: profile.php");
    exit();
}

// Get user info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile | Tasala</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            min-height: 100vh;
            font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
            background: linear-gradient(135deg, #f4f8fb 0%, #eaf0fa 100%) !important;
        }
        .profile-img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #fff;
            box-shadow: 0 2px 12px 0 rgba(30,60,114,0.12);
        }
        .card, .dashboard-card {
            background: #fff;
            border-radius: 1.5rem;
            box-shadow: 0 6px 32px 0 rgba(30,60,114,0.08), 0 1.5px 4px 0 rgba(42,82,152,0.08);
            margin-bottom: 2rem;
        }
        .card-header {
            border-radius: 1.5rem 1.5rem 0 0 !important;
            background: linear-gradient(90deg, #1e3c72 60%, #2a5298 100%);
        }
        .nav-pills .nav-link.active {
            background: linear-gradient(90deg, #1e3c72 60%, #2a5298 100%);
            color: #fff;
        }
    </style>
</head>
<body>
<?php include 'user_nav.php'; ?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-8 col-lg-7">
      <div class="card shadow-lg border-0">
        <div class="card-header text-white">
          <h4 class="mb-0"><i class="fa fa-user-circle me-2"></i>Profile Settings</h4>
        </div>
        <div class="card-body">
          <?php if (isset($_SESSION['profile_success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['profile_success']; unset($_SESSION['profile_success']); ?></div>
          <?php endif; ?>
          <?php if (isset($_SESSION['profile_error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['profile_error']; unset($_SESSION['profile_error']); ?></div>
          <?php endif; ?>
          <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
          <?php endif; ?>

          <form method="POST" action="profile.php" enctype="multipart/form-data">
            <div class="d-flex align-items-center mb-4">
              <div class="me-3">
                <?php if (!empty($user['profile_picture'])): ?>
                  <img src="uploads/<?= htmlspecialchars($user['profile_picture']) ?>" class="profile-img shadow" alt="Profile Picture">
                <?php else: ?>
                  <div class="profile-img shadow bg-light d-flex align-items-center justify-content-center">
                    <i class="fa fa-user fa-3x text-secondary"></i>
                  </div>
                <?php endif; ?>
              </div>
              <div class="flex-grow-1">
                <label for="profile_picture" class="form-label fw-semibold mb-1">Change Profile Picture</label>
                <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/jpeg, image/png, image/gif">
                <div class="form-text">Max 2MB. JPG, PNG or GIF.</div>
              </div>
            </div>
            
            <div class="row g-3 mb-4">
              <div class="col-md-6">
                <label class="form-label fw-semibold">First Name</label>
                <input type="text" class="form-control rounded-pill" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Last Name</label>
                <input type="text" class="form-control rounded-pill" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Username</label>
                <input type="text" class="form-control rounded-pill" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Email</label>
                <input type="email" class="form-control rounded-pill" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
              </div>
            </div>
            
            <div class="d-flex justify-content-between mt-4">
              <button type="submit" class="btn btn-primary px-4">
                <i class="fa fa-save me-1"></i>Save Changes
              </button>
            </div>
          </form>
          
          <hr class="my-4">
          
          <div class="mb-4">
            <h5 class="mb-3"><i class="fa fa-key me-2"></i>Change Password</h5>
            <form method="POST" action="profile.php">
              <div class="row g-2">
                <div class="col-md-6">
                  <input type="password" class="form-control rounded-pill" name="current_password" placeholder="Current password" required>
                </div>
                <div class="col-md-6">
                  <input type="password" class="form-control rounded-pill" name="new_password" placeholder="New password" required>
                </div>
              </div>
              <button type="submit" class="btn btn-outline-primary mt-2">
                <i class="fa fa-lock me-1"></i>Update Password
              </button>
            </form>
          </div>
          
          <hr class="my-4">
          
          <div class="mb-3">
            <h5 class="mb-3"><i class="fa fa-exclamation-triangle me-2 text-danger"></i>Delete Account</h5>
            <button type="button" class="btn btn-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
              <i class="fa fa-trash me-1"></i> Delete Account Permanently
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Delete Account Modal -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="deleteAccountModalLabel">Confirm Account Deletion</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="profile.php">
        <div class="modal-body">
          <div class="alert alert-danger">
            <i class="fa fa-exclamation-circle me-2"></i>
            <strong>This action cannot be undone!</strong> All your data will be permanently deleted.
          </div>
          <p>Please type your password to confirm:</p>
          <input type="password" class="form-control" name="confirm_password" placeholder="Enter your password" required>
          <input type="hidden" name="delete_account" value="1">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">
            <i class="fa fa-trash me-1"></i>Delete My Account
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>