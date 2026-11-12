<?php
// owner_dashboard.php — Owner management: all orders, sales analytics & chart, excel export
include 'auth.php';
include 'config.php';
require_owner();

// Handle status updates from owner
$message = "";
if (isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['status'];
    if (in_array($new_status, ['pending', 'completed', 'cancelled'])) {
        $stmt = mysqli_prepare($conn, "UPDATE orders SET status = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $new_status, $order_id);
        if (mysqli_stmt_execute($stmt)) {
            $message = "<div class='alert alert-success alert-dismissible fade show'>Order #$order_id status updated.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        }
    }
}

// 1. KPI Summary Cards
$summary_res = mysqli_query($conn, "SELECT 
    COUNT(id) as total_orders,
    SUM(CASE WHEN status != 'cancelled' THEN final_amount ELSE 0 END) as total_revenue,
    SUM(discount_amount) as total_discounts,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders
FROM orders");
$summary = mysqli_fetch_assoc($summary_res);

// 2. Daily Sales Data for Chart.js
$daily_chart_query = mysqli_query($conn, "SELECT DATE(order_date) as odate, SUM(final_amount) as total_sales, COUNT(id) as order_count 
                                          FROM orders 
                                          WHERE status != 'cancelled' 
                                          GROUP BY DATE(order_date) 
                                          ORDER BY odate ASC LIMIT 14");
$chart_labels = [];
$chart_sales = [];
$chart_orders = [];
while ($d = mysqli_fetch_assoc($daily_chart_query)) {
    $chart_labels[] = date('d M', strtotime($d['odate']));
    $chart_sales[] = (int)$d['total_sales'];
    $chart_orders[] = (int)$d['order_count'];
}

// 3. Category Breakdown Data for Pie Chart
$cat_chart_query = mysqli_query($conn, "SELECT product_type, SUM(subtotal) as type_total FROM order_items GROUP BY product_type");
$cat_labels = [];
$cat_totals = [];
while ($c = mysqli_fetch_assoc($cat_chart_query)) {
    $cat_labels[] = ucfirst($c['product_type']);
    $cat_totals[] = (int)$c['type_total'];
}

// 4. Fetch All Orders List
$all_orders = mysqli_query($conn, "SELECT o.*, u.username as cashier_name, c.username as customer_username 
                                   FROM orders o 
                                   LEFT JOIN users u ON o.cashier_id = u.id 
                                   LEFT JOIN users c ON o.customer_id = c.id 
                                   ORDER BY o.id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard - Kopipiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Chart.js CDN for visual sales charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <div class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h3 class="fw-bold mb-0">Owner Dashboard</h3>
                <small class="text-muted">Orders overview and sales reports</small>
            </div>
            <div class="d-flex gap-2">
                <a href="export.php" class="btn btn-success btn-sm">Export Excel (.xls)</a>
                <a href="products.php" class="btn btn-outline-dark btn-sm">Manage Products</a>
            </div>
        </div>

        <?= $message ?>

        <!-- KPI Metric Cards -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm bg-white p-3">
                    <small class="text-muted fw-bold">TOTAL REVENUE</small>
                    <h4 class="fw-bold text-success mb-0">Rp <?= number_format($summary['total_revenue'] ?? 0, 0, ',', '.') ?></h4>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm bg-white p-3">
                    <small class="text-muted fw-bold">TOTAL ORDERS</small>
                    <h4 class="fw-bold text-dark mb-0"><?= number_format($summary['total_orders'] ?? 0) ?></h4>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm bg-white p-3">
                    <small class="text-muted fw-bold">TOTAL DISCOUNTS</small>
                    <h4 class="fw-bold text-danger mb-0">Rp <?= number_format($summary['total_discounts'] ?? 0, 0, ',', '.') ?></h4>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm bg-white p-3">
                    <small class="text-muted fw-bold">COMPLETED ORDERS</small>
                    <h4 class="fw-bold text-primary mb-0"><?= number_format($summary['completed_orders'] ?? 0) ?></h4>
                </div>
            </div>
        </div>

        <!-- Sales Charts Section -->
        <div class="row g-4 mb-4">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-dark text-white py-2">
                        <h6 class="mb-0 fw-bold">Sales by Date</h6>
                    </div>
                    <div class="card-body p-3">
                        <?php if (empty($chart_sales)): ?>
                            <div class="text-center py-4 text-muted">No sales data recorded yet.</div>
                        <?php else: ?>
                            <canvas id="salesTrendChart" height="120"></canvas>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-dark text-white py-2">
                        <h6 class="mb-0 fw-bold">Sales by Category</h6>
                    </div>
                    <div class="card-body p-3 d-flex align-items-center justify-content-center">
                        <?php if (empty($cat_totals)): ?>
                            <div class="text-center py-4 text-muted">No category data available yet.</div>
                        <?php else: ?>
                            <canvas id="categoryPieChart" height="190"></canvas>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Master Orders Table -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-2">
                <h5 class="mb-0">All Orders</h5>
                <a href="export.php" class="btn btn-outline-light btn-sm">Export Excel</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Items</th>
                                <th>Date</th>
                                <th>Cashier</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th class="text-center" style="width: 170px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($all_orders) === 0): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">No orders found.</td>
                                </tr>
                            <?php endif; ?>

                            <?php while ($o = mysqli_fetch_assoc($all_orders)): 
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
                                        <small class="text-muted"><?= date('d M Y, H:i', strtotime($o['order_date'])) ?></small>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($o['cashier_name'] ?? 'Unassigned') ?>
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
                                            <select name="status" class="form-select form-select-sm" style="width: 105px;">
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

    <!-- Chart.js Initialization -->
    <script>
        <?php if (!empty($chart_sales)): ?>
        const trendCtx = document.getElementById('salesTrendChart').getContext('2d');
        new Chart(trendCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chart_labels) ?>,
                datasets: [{
                    label: 'Revenue (Rp)',
                    data: <?= json_encode($chart_sales) ?>,
                    backgroundColor: 'rgba(50, 50, 50, 0.7)',
                    borderColor: 'rgb(50, 50, 50)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        <?php endif; ?>

        <?php if (!empty($cat_totals)): ?>
        const pieCtx = document.getElementById('categoryPieChart').getContext('2d');
        new Chart(pieCtx, {
            type: 'pie',
            data: {
                labels: <?= json_encode($cat_labels) ?>,
                datasets: [{
                    data: <?= json_encode($cat_totals) ?>,
                    backgroundColor: ['#6c757d', '#adb5bd', '#dee2e6']
                }]
            },
            options: {
                responsive: true
            }
        });
        <?php endif; ?>
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
