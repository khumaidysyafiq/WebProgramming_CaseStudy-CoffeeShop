<?php
// users.php — Staff and user management (Owner only)
include 'auth.php';
include 'config.php';
require_owner();

$message = "";

if (isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    if (strlen($password) < 6) {
        $message = "<div class='alert alert-danger'>Password must be at least 6 characters.</div>";
    } elseif (!in_array($role, ['owner', 'cashier', 'customer'])) {
        $message = "<div class='alert alert-danger'>Invalid role selected.</div>";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, "INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sss", $username, $hash, $role);

        try {
            mysqli_stmt_execute($stmt);
            $message = "<div class='alert alert-success'>User <strong>" . htmlspecialchars($username) . "</strong> (" . htmlspecialchars($role) . ") added.</div>";
        } catch (mysqli_sql_exception $e) {
            $message = "<div class='alert alert-danger'>Username is already taken.</div>";
        }
    }
}

$users = mysqli_query($conn, "SELECT id, username, role FROM users ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - Kopipiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <div class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="fw-bold mb-0">Staff Management</h3>
            <a href="owner_dashboard.php" class="btn btn-outline-dark btn-sm">&larr; Back to Dashboard</a>
        </div>

        <?= $message ?>

        <div class="row g-4">
            <!-- Add user form -->
            <div class="col-md-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-dark text-white py-2">
                        <h6 class="mb-0 fw-bold">Add User</h6>
                    </div>
                    <div class="card-body p-3">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required autofocus>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" minlength="6" placeholder="Min 6 chars" required>
                            </div>
                            <div class="mb-4">
                                <label for="role" class="form-label">Role</label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="cashier" selected>Cashier</option>
                                    <option value="owner">Owner</option>
                                    <option value="customer">Customer</option>
                                </select>
                            </div>
                            <div class="d-grid">
                                <button type="submit" name="add_user" class="btn btn-dark">Save User</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- User list -->
            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-dark text-white py-2">
                        <h6 class="mb-0 fw-bold">User List</h6>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50px;">No</th>
                                    <th>Username</th>
                                    <th style="width: 150px;">Role</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; while ($u = mysqli_fetch_assoc($users)): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($u['username']) ?></strong>
                                            <?php if ($u['id'] == $_SESSION['user_id']): ?>
                                                <small class="text-muted">(You)</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= ucfirst(htmlspecialchars($u['role'])) ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
