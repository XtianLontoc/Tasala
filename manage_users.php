<?php
require_once 'auth_check.php';
require_once 'db_connect.php';

// Fetch users
$users = [];
$result = $conn->query("SELECT * FROM users");
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users | Tasala</title>
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
                    <a class="nav-link" href="admin_dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link active" href="manage_users.php">
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
            <h2 class="h3 mb-4 fw-bold text-primary">Manage Users</h2>
            
            <div class="d-flex justify-content-end mb-3">
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="fas fa-user-plus"></i> Add User
                </button>
            </div>

            <div class="dashboard-card p-4">
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>First Name</th>
                                <th>Last Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Actions</th> 
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr data-user-id="<?= $user['id'] ?>">
                                <td><?= htmlspecialchars($user['id']) ?></td>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td><?= htmlspecialchars($user['first_name']) ?></td>
                                <td><?= htmlspecialchars($user['last_name']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td>
                                    <?php
                                        if ($user['role_id'] == 1) echo '<span class="badge bg-info">Admin</span>';
                                        else echo '<span class="badge bg-success">User</span>';
                                    ?>
                                </td>
                                <td class="user-status">
                                    <?php if ($user['is_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <!-- Example action buttons -->
                                    <button type="button"
                                        class="btn btn-sm btn-primary edit-btn"
                                        data-id="<?= $user['id'] ?>"
                                        data-username="<?= htmlspecialchars($user['username']) ?>"
                                        data-first_name="<?= htmlspecialchars($user['first_name']) ?>"
                                        data-last_name="<?= htmlspecialchars($user['last_name']) ?>"
                                        data-email="<?= htmlspecialchars($user['email']) ?>"
                                        data-role="<?= $user['role_id'] ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="delete_user.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <?php if ($user['is_active']): ?>
                                        <button type="button"
                                            class="btn btn-sm btn-warning deactivate-btn"
                                            data-id="<?= $user['id'] ?>">
                                            <i class="fas fa-user-slash"></i>
                                        </button>
                                    <?php else: ?>
                                        <button type="button"
                                            class="btn btn-sm btn-success activate-btn"
                                            data-id="<?= $user['id'] ?>">
                                            <i class="fas fa-user-check"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>
<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="editUserForm" class="modal-content" autocomplete="off">
      <div class="modal-header" style="background: linear-gradient(90deg, #1e3c72 60%, #2a5298 100%); color: #fff;">
        <h5 class="modal-title" id="editUserModalLabel"><i class="fas fa-user-edit me-2"></i>Edit User</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <div id="modalAlert"></div>
        <input type="hidden" name="id" id="editUserId">
        <div class="mb-3">
          <label class="form-label" for="editUsername"><i class="fas fa-user me-1"></i>Username</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-user"></i></span>
            <input type="text" name="username" id="editUsername" class="form-control" required>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label" for="editFirstName"><i class="fas fa-id-card me-1"></i>First Name</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-id-card"></i></span>
            <input type="text" name="first_name" id="editFirstName" class="form-control" required>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label" for="editLastName"><i class="fas fa-id-card me-1"></i>Last Name</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-id-card"></i></span>
            <input type="text" name="last_name" id="editLastName" class="form-control" required>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label" for="editEmail"><i class="fas fa-envelope me-1"></i>Email</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
            <input type="email" name="email" id="editEmail" class="form-control" required>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label" for="editRole"><i class="fas fa-user-tag me-1"></i>Role</label>
          <select name="role_id" id="editRole" class="form-select" required>
            <option value="1">Admin</option>
            <option value="2">User</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label" for="editPassword"><i class="fas fa-key me-1"></i>New Password</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-key"></i></span>
            <input type="password" name="password" id="editPassword" class="form-control" placeholder="Leave blank to keep current">
          </div>
          <div class="form-text">Leave blank to keep the current password.</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="fas fa-times"></i> Cancel
        </button>
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-save"></i> Save Changes
        </button>
      </div>
    </form>
  </div>
</div>
<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="addUserForm" class="modal-content" autocomplete="off">
      <div class="modal-header" style="background: linear-gradient(90deg, #1e3c72 60%, #2a5298 100%); color: #fff;">
        <h5 class="modal-title" id="addUserModalLabel"><i class="fas fa-user-plus me-2"></i>Add User</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <div id="addModalAlert"></div>
        <div class="mb-3">
          <label class="form-label" for="addUsername"><i class="fas fa-user me-1"></i>Username</label>
          <input type="text" name="username" id="addUsername" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label" for="addFirstName"><i class="fas fa-id-card me-1"></i>First Name</label>
          <input type="text" name="first_name" id="addFirstName" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label" for="addLastName"><i class="fas fa-id-card me-1"></i>Last Name</label>
          <input type="text" name="last_name" id="addLastName" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label" for="addEmail"><i class="fas fa-envelope me-1"></i>Email</label>
          <input type="email" name="email" id="addEmail" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label" for="addRole"><i class="fas fa-user-tag me-1"></i>Role</label>
          <select name="role_id" id="addRole" class="form-select" required>
            <option value="1">Admin</option>
            <option value="2">User</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label" for="addPassword"><i class="fas fa-key me-1"></i>Password</label>
          <input type="password" name="password" id="addPassword" class="form-control" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="fas fa-times"></i> Cancel
        </button>
        <button type="submit" class="btn btn-success">
          <i class="fas fa-user-plus"></i> Add User
        </button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Open modal and fill form
document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('editUserId').value = this.dataset.id;
        document.getElementById('editUsername').value = this.dataset.username;
        document.getElementById('editFirstName').value = this.dataset.first_name;
        document.getElementById('editLastName').value = this.dataset.last_name;
        document.getElementById('editEmail').value = this.dataset.email;
        document.getElementById('editRole').value = this.dataset.role;
        document.getElementById('editPassword').value = '';
        document.getElementById('modalAlert').innerHTML = '';
        var modal = new bootstrap.Modal(document.getElementById('editUserModal'));
        modal.show();
    });
});

// AJAX form submit for editing user
document.getElementById('editUserForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var form = this;
    var formData = new FormData(form);
    fetch('update_user_credentials.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('modalAlert').innerHTML =
                '<div class="alert alert-success">User updated successfully.</div>';
            // Optionally update the table row without reload:
            var row = document.querySelector('tr[data-user-id="' + formData.get('id') + '"]');
            row.querySelector('td:nth-child(2)').textContent = formData.get('username');
            row.querySelector('td:nth-child(3)').textContent = formData.get('first_name');
            row.querySelector('td:nth-child(4)').textContent = formData.get('last_name');
            row.querySelector('td:nth-child(5)').textContent = formData.get('email');
            setTimeout(() => {
                document.getElementById('modalAlert').innerHTML = '';
                bootstrap.Modal.getInstance(document.getElementById('editUserModal')).hide();
            }, 1500); // 1.5 seconds
        } else {
            document.getElementById('modalAlert').innerHTML =
                '<div class="alert alert-danger">' + (data.error || 'Update failed.') + '</div>';
            setTimeout(() => {
                document.getElementById('modalAlert').innerHTML = '';
            }, 3000); // 3 seconds
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('addUserForm').addEventListener('submit', function(e) {
        e.preventDefault();
        var form = this;
        var formData = new FormData(form);
        fetch('add_user.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showTempMessage('User added successfully!', 'success');
                setTimeout(() => location.reload(), 1500); // 1.5 seconds
            } else {
                showTempMessage(data.error || 'Failed to add user.', 'danger');
            }
        });
    });
});

document.querySelector('table').addEventListener('click', function(e) {
    // Deactivate
    if (e.target.closest('.deactivate-btn')) {
        const btn = e.target.closest('.deactivate-btn');
        if (!confirm('Are you sure you want to deactivate this user?')) return;
        const userId = btn.dataset.id;
        const row = btn.closest('tr');
        fetch('deactivate_user.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=' + encodeURIComponent(userId)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                row.querySelector('.user-status').innerHTML = '<span class="badge bg-danger">Inactive</span>';
                btn.outerHTML = `<button type="button" class="btn btn-sm btn-success activate-btn" data-id="${userId}">
                    <i class="fas fa-user-check"></i>
                </button>`;
                showTempMessage('User deactivated successfully!', 'success');
            } else {
                showTempMessage(data.error || 'Failed to deactivate user.', 'danger');
            }
        });
    }

    // Activate
    if (e.target.closest('.activate-btn')) {
        const btn = e.target.closest('.activate-btn');
        const userId = btn.dataset.id;
        const row = btn.closest('tr');
        fetch('activate_user.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=' + encodeURIComponent(userId)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                row.querySelector('.user-status').innerHTML = '<span class="badge bg-success">Active</span>';
                btn.outerHTML = `<button type="button" class="btn btn-sm btn-warning deactivate-btn" data-id="${userId}">
                    <i class="fas fa-user-slash"></i>
                </button>`;
                showTempMessage('User activated successfully!', 'success');
            } else {
                showTempMessage(data.error || 'Failed to activate user.', 'danger');
            }
        });
    }
});

// Temporary message function
function showTempMessage(msg, type) {
    let alert = document.createElement('div');
    alert.className = `alert alert-${type} position-fixed top-0 end-0 m-3`;
    alert.style.zIndex = 9999;
    alert.innerText = msg;
    document.body.appendChild(alert);
    setTimeout(() => alert.remove(), 2000);
}
</script>
</body>
</html>
