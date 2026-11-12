<?php
// cart.php — View and edit shopping cart
include 'auth.php';
include 'config.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle updates (quantity change / remove)
if (isset($_POST['update_cart'])) {
    foreach ($_POST['qty'] as $pid => $new_qty) {
        $pid = (int)$pid;
        $new_qty = (int)$new_qty;
        if ($new_qty <= 0) {
            unset($_SESSION['cart'][$pid]);
        } else {
            $_SESSION['cart'][$pid] = $new_qty;
        }
    }
    header("Location: cart.php");
    exit;
}

if (isset($_GET['remove'])) {
    $remove_id = (int)$_GET['remove'];
    unset($_SESSION['cart'][$remove_id]);
    header("Location: cart.php");
    exit;
}

if (isset($_GET['clear'])) {
    $_SESSION['cart'] = [];
    header("Location: cart.php");
    exit;
}

// Fetch products in cart
$cart_items = [];
$total_estimated = 0;
$is_logged_customer = is_customer();

if (!empty($_SESSION['cart'])) {
    $ids = array_map('intval', array_keys($_SESSION['cart']));
    $id_list = implode(',', $ids);
    $query = "SELECT id, name, price, url_image, type FROM product WHERE id IN ($id_list)";
    $res = mysqli_query($conn, $query);

    while ($p = mysqli_fetch_assoc($res)) {
        $pid = $p['id'];
        $qty = $_SESSION['cart'][$pid];
        $is_coffee = strtolower($p['type']) === 'coffee';
        $unit_price = ($is_logged_customer && $is_coffee) ? round($p['price'] * 0.9) : $p['price'];
        $subtotal = $unit_price * $qty;
        $total_estimated += $subtotal;

        $p['qty'] = $qty;
        $p['effective_price'] = $unit_price;
        $p['subtotal'] = $subtotal;
        $p['has_discount'] = ($is_logged_customer && $is_coffee);
        $cart_items[] = $p;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart - Kopipiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <div class="container my-4">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-2">
                        <h5 class="mb-0">Cart</h5>
                        <?php if (!empty($cart_items)): ?>
                            <a href="cart.php?clear=1" class="btn btn-outline-danger btn-sm" onclick="return confirm('Clear cart?')">Empty Cart</a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-4">
                        <?php if (empty($cart_items)): ?>
                            <div class="text-center py-5">
                                <h4 class="fw-bold">Your cart is empty.</h4>
                                <p class="text-muted">Explore the menu to add products.</p>
                                <a href="index.php" class="btn btn-dark mt-2">&larr; Back to Menu</a>
                            </div>
                        <?php else: ?>
                            <form method="POST" action="">
                                <div class="table-responsive">
                                    <table class="table align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Item</th>
                                                <th>Type</th>
                                                <th>Unit Price</th>
                                                <th style="width: 110px;" class="text-center">Quantity</th>
                                                <th class="text-end">Subtotal</th>
                                                <th style="width: 50px;"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($cart_items as $item): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center gap-3">
                                                            <img src="<?= htmlspecialchars($item['url_image']) ?>" width="60" height="50" class="rounded object-fit-cover">
                                                            <span class="fw-bold"><?= htmlspecialchars($item['name']) ?></span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?= ucfirst(htmlspecialchars($item['type'])) ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($item['has_discount']): ?>
                                                            <del class="text-muted small">Rp <?= number_format($item['price'], 0, ',', '.') ?></del><br>
                                                            <span class="text-success fw-bold">Rp <?= number_format($item['effective_price'], 0, ',', '.') ?></span>
                                                        <?php else: ?>
                                                            <span>Rp <?= number_format($item['price'], 0, ',', '.') ?></span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <input type="number" name="qty[<?= $item['id'] ?>]" value="<?= $item['qty'] ?>" min="1" max="99" class="form-control form-control-sm text-center">
                                                    </td>
                                                    <td class="text-end fw-bold">
                                                        Rp <?= number_format($item['subtotal'], 0, ',', '.') ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <a href="cart.php?remove=<?= $item['id'] ?>" class="text-danger text-decoration-none fw-bold" title="Remove">&times;</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr class="fs-5">
                                                <th colspan="4" class="text-end">Total:</th>
                                                <th class="text-end text-success fw-bold">Rp <?= number_format($total_estimated, 0, ',', '.') ?></th>
                                                <th></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>

                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-4 pt-3 border-top">
                                    <div class="d-flex gap-2">
                                        <a href="index.php" class="btn btn-outline-secondary btn-sm">&larr; Back to Menu</a>
                                        <button type="submit" name="update_cart" class="btn btn-outline-dark btn-sm">Update Quantities</button>
                                    </div>
                                    <a href="checkout.php" class="btn btn-dark fw-bold">Checkout &rarr;</a>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
