<?php
// checkout.php — Checkout and order confirmation
include 'auth.php';
include 'config.php';

if (empty($_SESSION['cart'])) {
    header("Location: index.php");
    exit;
}

$is_logged_customer = is_customer();
$current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$default_name = isset($_SESSION['username']) ? $_SESSION['username'] : '';

// Fetch products in cart
$ids = array_map('intval', array_keys($_SESSION['cart']));
$id_list = implode(',', $ids);
$query = "SELECT id, name, price, url_image, type FROM product WHERE id IN ($id_list)";
$res = mysqli_query($conn, $query);

$items = [];
$total_regular = 0;
$total_discount = 0;
$final_total = 0;

while ($p = mysqli_fetch_assoc($res)) {
    $pid = $p['id'];
    $qty = $_SESSION['cart'][$pid];
    $is_coffee = strtolower($p['type']) === 'coffee';
    
    // 10% discount on coffee if customer is logged in
    $has_discount = ($is_logged_customer && $is_coffee);
    $unit_price = $p['price'];
    $discounted_price = $has_discount ? round($p['price'] * 0.9) : $unit_price;
    
    $item_regular_subtotal = $unit_price * $qty;
    $item_final_subtotal = $discounted_price * $qty;
    $item_discount = $item_regular_subtotal - $item_final_subtotal;

    $total_regular += $item_regular_subtotal;
    $total_discount += $item_discount;
    $final_total += $item_final_subtotal;

    $p['qty'] = $qty;
    $p['unit_price'] = $unit_price;
    $p['discounted_price'] = $discounted_price;
    $p['subtotal'] = $item_final_subtotal;
    $p['has_discount'] = $has_discount;
    $items[] = $p;
}

// Fetch list of cashiers for assignment
$cashiers_res = mysqli_query($conn, "SELECT id, username FROM users WHERE role = 'cashier' ORDER BY username");

// Handle order submission
$error = "";
if (isset($_POST['place_order'])) {
    $customer_name = trim($_POST['customer_name']);
    $cashier_id = !empty($_POST['cashier_id']) ? (int)$_POST['cashier_id'] : null;

    if (empty($customer_name)) {
        $error = "Please enter your name.";
    } else {
        // 1. Insert into orders table
        $stmt = mysqli_prepare($conn, "INSERT INTO orders (customer_id, customer_name, cashier_id, total_amount, discount_amount, final_amount, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
        mysqli_stmt_bind_param($stmt, "isiiii", $current_user_id, $customer_name, $cashier_id, $total_regular, $total_discount, $final_total);
        
        if (mysqli_stmt_execute($stmt)) {
            $order_id = mysqli_insert_id($conn);

            // 2. Insert order items
            $item_stmt = mysqli_prepare($conn, "INSERT INTO order_items (order_id, product_id, product_name, product_type, price, discounted_price, quantity, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            
            foreach ($items as $it) {
                mysqli_stmt_bind_param($item_stmt, "iissiiii", 
                    $order_id, 
                    $it['id'], 
                    $it['name'], 
                    $it['type'], 
                    $it['unit_price'], 
                    $it['discounted_price'], 
                    $it['qty'], 
                    $it['subtotal']
                );
                mysqli_stmt_execute($item_stmt);
            }

            // Clear cart
            $_SESSION['cart'] = [];

            // Redirect to receipt
            header("Location: order_success.php?id=" . $order_id);
            exit;
        } else {
            $error = "Failed to place order: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Kopipiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <div class="container my-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="fw-bold mb-0">Checkout</h3>
                    <a href="cart.php" class="btn btn-outline-secondary btn-sm">&larr; Back to Cart</a>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <?php if ($is_logged_customer): ?>
                    <div class="alert alert-success py-2 mb-3">
                        Customer 10% discount applied to coffee items.
                    </div>
                <?php else: ?>
                    <div class="alert alert-secondary py-2 mb-3">
                        Ordering as Guest. <a href="login.php">Login</a> or <a href="register.php">Register</a> to get 10% off coffee.
                    </div>
                <?php endif; ?>

                <div class="row g-4">
                    <!-- Product List with Discounts -->
                    <div class="col-md-7">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-dark text-white py-2">
                                <h6 class="mb-0 fw-bold">Order Summary</h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Item</th>
                                                <th>Type</th>
                                                <th>Price</th>
                                                <th class="text-center">Qty</th>
                                                <th class="text-end">Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($items as $it): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?= htmlspecialchars($it['name']) ?></strong>
                                                    </td>
                                                    <td>
                                                        <?= ucfirst(htmlspecialchars($it['type'])) ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($it['has_discount']): ?>
                                                            <del class="text-muted small">Rp <?= number_format($it['unit_price'], 0, ',', '.') ?></del><br>
                                                            <span class="text-success fw-bold">Rp <?= number_format($it['discounted_price'], 0, ',', '.') ?></span>
                                                        <?php else: ?>
                                                            <span>Rp <?= number_format($it['unit_price'], 0, ',', '.') ?></span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-center fw-bold">x<?= $it['qty'] ?></td>
                                                    <td class="text-end fw-bold">
                                                        Rp <?= number_format($it['subtotal'], 0, ',', '.') ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer bg-white p-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Subtotal:</span>
                                    <span>Rp <?= number_format($total_regular, 0, ',', '.') ?></span>
                                </div>
                                <?php if ($total_discount > 0): ?>
                                    <div class="d-flex justify-content-between mb-1 text-success">
                                        <span>Discount (10% on Coffee):</span>
                                        <span>- Rp <?= number_format($total_discount, 0, ',', '.') ?></span>
                                    </div>
                                <?php endif; ?>
                                <hr class="my-2">
                                <div class="d-flex justify-content-between fs-5 fw-bold text-dark">
                                    <span>Total:</span>
                                    <span class="text-success">Rp <?= number_format($final_total, 0, ',', '.') ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Customer & Checkout Form -->
                    <div class="col-md-5">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-dark text-white py-2">
                                <h6 class="mb-0 fw-bold">Customer Details</h6>
                            </div>
                            <div class="card-body p-3">
                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label for="customer_name" class="form-label">Customer Name</label>
                                        <input type="text" class="form-control" id="customer_name" name="customer_name" value="<?= htmlspecialchars($default_name) ?>" placeholder="Enter name" required autofocus>
                                    </div>

                                    <div class="mb-4">
                                        <label for="cashier_id" class="form-label">Serving Cashier (Optional)</label>
                                        <select class="form-select" id="cashier_id" name="cashier_id">
                                            <option value="">-- Any Cashier --</option>
                                            <?php while ($c = mysqli_fetch_assoc($cashiers_res)): ?>
                                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['username']) ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>

                                    <div class="d-grid">
                                        <button type="submit" name="place_order" class="btn btn-success fw-bold">
                                            Place Order (Rp <?= number_format($final_total, 0, ',', '.') ?>)
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
