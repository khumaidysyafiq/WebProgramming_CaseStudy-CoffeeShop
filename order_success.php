<?php
// order_success.php — Receipt and Order Confirmation
include 'auth.php';
include 'config.php';

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = mysqli_prepare($conn, "SELECT o.*, u.username as cashier_name 
                               FROM orders o 
                               LEFT JOIN users u ON o.cashier_id = u.id 
                               WHERE o.id = ?");
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$order) {
    header("Location: index.php");
    exit;
}

$items_stmt = mysqli_prepare($conn, "SELECT * FROM order_items WHERE order_id = ?");
mysqli_stmt_bind_param($items_stmt, "i", $order_id);
mysqli_stmt_execute($items_stmt);
$items = mysqli_stmt_get_result($items_stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #<?= $order['id'] ?> - Kopipiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            .card { border: none !important; box-shadow: none !important; }
        }
    </style>
</head>
<body class="bg-light">
    <div class="no-print">
        <?php include 'navbar.php'; ?>
    </div>

    <div class="container my-4">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-6">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-dark text-white text-center py-3">
                        <h4 class="mb-0 fw-bold">Order Receipt</h4>
                    </div>
                    <div class="card-body p-4">
                        <!-- Receipt Metadata -->
                        <div class="border-bottom pb-3 mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Order ID:</span>
                                <strong>#<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Customer:</span>
                                <strong><?= htmlspecialchars($order['customer_name']) ?></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Date & Time:</span>
                                <span><?= date('d M Y, H:i', strtotime($order['order_date'])) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Cashier:</span>
                                <span><?= htmlspecialchars($order['cashier_name'] ?? 'Unassigned') ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Status:</span>
                                <span><?= ucfirst(htmlspecialchars($order['status'])) ?></span>
                            </div>
                        </div>

                        <!-- Items List -->
                        <h6 class="fw-bold mb-3">Items Ordered</h6>
                        <table class="table table-sm align-middle mb-3">
                            <tbody>
                                <?php while ($it = mysqli_fetch_assoc($items)): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?= htmlspecialchars($it['product_name']) ?></div>
                                            <small class="text-muted"><?= ucfirst(htmlspecialchars($it['product_type'])) ?> &times; <?= $it['quantity'] ?></small>
                                        </td>
                                        <td class="text-end">
                                            Rp <?= number_format($it['subtotal'], 0, ',', '.') ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>

                        <!-- Totals -->
                        <div class="bg-light p-3 rounded mb-4">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Subtotal:</span>
                                <span>Rp <?= number_format($order['total_amount'], 0, ',', '.') ?></span>
                            </div>
                            <?php if ($order['discount_amount'] > 0): ?>
                                <div class="d-flex justify-content-between mb-1 text-success">
                                    <span>Discount (10%):</span>
                                    <span>- Rp <?= number_format($order['discount_amount'], 0, ',', '.') ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="d-flex justify-content-between fs-5 fw-bold text-dark pt-2 border-top">
                                <span>Total Paid:</span>
                                <span class="text-success">Rp <?= number_format($order['final_amount'], 0, ',', '.') ?></span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="d-flex gap-2 no-print">
                            <a href="index.php" class="btn btn-outline-dark w-100">&larr; Back to Menu</a>
                            <button onclick="window.print()" class="btn btn-dark w-100">Print Receipt</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
