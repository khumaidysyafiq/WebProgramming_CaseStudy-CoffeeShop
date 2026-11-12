<?php
// cashier_orders.php — Cashier order fulfillment portal
include 'auth.php';
include 'config.php';
require_cashier();

$cashier_id = $_SESSION['user_id'];
$message = "";

// Handle status change
if (isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['status'];

    if (in_array($new_status, ['pending', 'completed', 'cancelled'])) {
        // Enforce that cashier only updates orders assigned to them
        $stmt = mysqli_prepare($conn, "UPDATE orders SET status = ? WHERE id = ? AND cashier_id = ?");
        mysqli_stmt_bind_param($stmt, "sii", $new_status, $order_id, $cashier_id);
        if (mysqli_stmt_execute($stmt)) {
            $message = "<div class='alert alert-success alert-dismissible fade show'>Order #$order_id status updated to $new_status.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        }
    }
}

// Handle claiming an unassigned order
if (isset($_POST['claim_order'])) {
    $order_id = (int)$_POST['order_id'];
    $stmt = mysqli_prepare($conn, "UPDATE orders SET cashier_id = ? WHERE id = ? AND cashier_id IS NULL");
    mysqli_stmt_bind_param($stmt, "ii", $cashier_id, $order_id);
    if (mysqli_stmt_execute($stmt)) {
        $message = "<div class='alert alert-success alert-dismissible fade show'>Order #$order_id assigned to your queue.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }
}

// 1. Fetch orders strictly assigned to/managed by this cashier
$my_stmt = mysqli_prepare($conn, "SELECT o.*, u.username as customer_username 
                                  FROM orders o 
                                  LEFT JOIN users u ON o.customer_id = u.id 
                                  WHERE o.cashier_id = ? 
                                  ORDER BY o.id DESC");
mysqli_stmt_bind_param($my_stmt, "i", $cashier_id);
mysqli_stmt_execute($my_stmt);
$my_orders = mysqli_stmt_get_result($my_stmt);

// 2. Fetch unassigned orders available to be picked up
$unassigned_orders = mysqli_query($conn, "SELECT o.*, u.username as customer_username 
                                          FROM orders o 
                                          LEFT JOIN users u ON o.customer_id = u.id 
                                          WHERE o.cashier_id IS NULL 
                                          ORDER BY o.id DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cashier Orders - Kopipiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <div class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h3 class="fw-bold mb-0">Cashier Orders</h3>
                <small class="text-muted">Orders managed by <?= htmlspecialchars($_SESSION['username']) ?></small>
            </div>
            <button class="btn btn-outline-dark btn-sm" onclick="location.reload()">Refresh</button>
        </div>

        <?= $message ?>

        <!-- Unassigned Orders Callout (if any) -->
        <?php if (mysqli_num_rows($unassigned_orders) > 0): ?>
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-secondary text-white py-2 d-flex justify-content-between align-items-center">
                    <strong class="small">Unassigned Orders</strong>
                    <span class="small"><?= mysqli_num_rows($unassigned_orders) ?> pending</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Time</th>
                                    <th>Total</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($u = mysqli_fetch_assoc($unassigned_orders)): ?>
                                    <tr>
                                        <td>#<?= $u['id'] ?></td>
                                        <td><?= htmlspecialchars($u['customer_name']) ?></td>
                                        <td><?= date('H:i', strtotime($u['order_date'])) ?></td>
                                        <td>Rp <?= number_format($u['final_amount'], 0, ',', '.') ?></td>
                                        <td class="text-end">
                                            <form method="POST" action="" class="d-inline">
                                                <input type="hidden" name="order_id" value="<?= $u['id'] ?>">
                                                <button type="submit" name="claim_order" class="btn btn-dark btn-sm">Claim</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- My Managed Orders Table -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-dark text-white py-2">
                <h5 class="mb-0">My Orders</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Items</th>
                                <th>Time</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th class="text-center" style="width: 190px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($my_orders) === 0): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        No orders assigned to you yet.
                                    </td>
                                </tr>
                            <?php endif; ?>

                            <?php while ($o = mysqli_fetch_assoc($my_orders)): 
                                $it_res = mysqli_query($conn, "SELECT product_name, quantity FROM order_items WHERE order_id = {$o['id']}");
                                $item_strs = [];
                                while ($it = mysqli_fetch_assoc($it_res)) {
                                    $item_strs[] = htmlspecialchars($it['product_name']) . " &times; " . $it['quantity'];
                                }
                            ?>
                                <tr>
                                    <td>#<?= $o['id'] ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($o['customer_name']) ?></strong>
                                        <?php if (!empty($o['customer_username'])): ?>
                                            <br><small class="text-muted">(Member: <?= htmlspecialchars($o['customer_username']) ?>)</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small><?= implode(', ', $item_strs) ?></small>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?= date('d M, H:i', strtotime($o['order_date'])) ?></small>
                                    </td>
                                    <td>
                                        <strong>Rp <?= number_format($o['final_amount'], 0, ',', '.') ?></strong>
                                        <?php if ($o['discount_amount'] > 0): ?>
                                            <br><small class="text-muted">(-Rp <?= number_format($o['discount_amount'], 0, ',', '.') ?>)</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= ucfirst(htmlspecialchars($o['status'])) ?>
                                    </td>
                                    <td class="text-center">
                                        <form method="POST" action="" class="d-flex gap-1 justify-content-center">
                                            <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                            <select name="status" class="form-select form-select-sm" style="width: 110px;">
                                                <option value="pending" <?= $o['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="completed" <?= $o['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                                <option value="cancelled" <?= $o['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                            </select>
                                            <button type="submit" name="update_status" class="btn btn-sm btn-dark">Save</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
