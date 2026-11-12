<?php
// index.php
include 'auth.php';
include 'config.php';

// Handle Add to Cart action
$alert_msg = "";
if (isset($_POST['add_to_cart'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;

    // Verify product exists
    $stmt = mysqli_prepare($conn, "SELECT id, name FROM product WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $prod = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($prod) {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = $quantity;
        }
        $alert_msg = "<div class='alert alert-success alert-dismissible fade show' role='alert'>
            Added <strong>" . htmlspecialchars($prod['name']) . " (x{$quantity})</strong> to cart. 
            <a href='cart.php' class='alert-link'>View Cart</a>
            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
        </div>";
    }
}

// Category filter
$category_filter = isset($_GET['category']) ? $_GET['category'] : 'all';
if ($category_filter !== 'all') {
    $stmt = mysqli_prepare($conn, "SELECT id, name, price, url_image, type, rating, description FROM product WHERE type = ? ORDER BY id DESC");
    mysqli_stmt_bind_param($stmt, "s", $category_filter);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = mysqli_query($conn, "SELECT id, name, price, url_image, type, rating, description FROM product ORDER BY id DESC");
}

$is_logged_customer = is_customer();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kopipiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <div class="container my-4">
        <div class="mb-4">
            <h2 class="fw-bold">Kopipiro Menu</h2>
            <?php if ($is_logged_customer): ?>
                <div class="alert alert-success py-2">Logged in as customer: 10% discount applies to coffee.</div>
            <?php else: ?>
                <div class="alert alert-secondary py-2">Login or register as customer to get a 10% discount on coffee.</div>
            <?php endif; ?>
        </div>

        <?= $alert_msg ?>

        <!-- Category Filters -->
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div class="btn-group" role="group">
                <a href="index.php?category=all" class="btn btn-sm <?= $category_filter === 'all' ? 'btn-dark' : 'btn-outline-dark' ?>">All</a>
                <a href="index.php?category=coffee" class="btn btn-sm <?= $category_filter === 'coffee' ? 'btn-dark' : 'btn-outline-dark' ?>">Coffee</a>
                <a href="index.php?category=toast" class="btn btn-sm <?= $category_filter === 'toast' ? 'btn-dark' : 'btn-outline-dark' ?>">Toast</a>
                <a href="index.php?category=beverage" class="btn btn-sm <?= $category_filter === 'beverage' ? 'btn-dark' : 'btn-outline-dark' ?>">Beverage</a>
            </div>
            <div>
                <a href="cart.php" class="btn btn-sm btn-dark">View Cart</a>
                <?php if (is_owner()): ?>
                    <a href="create.php" class="btn btn-sm btn-success ms-1">Add Product</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Product Table View -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-dark text-white py-2">
                <h5 class="mb-0">Product List</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th style="width: 50px;">No</th>
                                <th style="width: 100px;">Type</th>
                                <th style="width: 100px;">Image</th>
                                <th>Name</th>
                                <th style="width: 130px;">Price</th>
                                <th style="width: 90px;">Rating</th>
                                <th>Description</th>
                                <th style="width: 140px;" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1; 
                            while ($row = mysqli_fetch_assoc($result)): 
                                $is_coffee = strtolower($row['type']) === 'coffee';
                                $has_discount = ($is_logged_customer && $is_coffee);
                                $display_price = $has_discount ? round($row['price'] * 0.9) : $row['price'];
                            ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= ucfirst(htmlspecialchars($row['type'])) ?></td>
                                <td>
                                    <img src="<?= htmlspecialchars($row['url_image']) ?>" width="80" height="60" class="img-fluid rounded object-fit-cover" alt="<?= htmlspecialchars($row['name']) ?>">
                                </td>
                                <td class="fw-bold"><?= htmlspecialchars($row['name']) ?></td>
                                <td>
                                    <?php if ($has_discount): ?>
                                        <del class="text-muted small">Rp <?= number_format($row['price'], 0, ',', '.') ?></del><br>
                                        <span class="text-success fw-bold">Rp <?= number_format($display_price, 0, ',', '.') ?></span>
                                    <?php else: ?>
                                        <span>Rp <?= number_format($row['price'], 0, ',', '.') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($row['rating'] ?? '0.00') ?>/5.0
                                </td>
                                <td>
                                    <small class="text-muted"><?= htmlspecialchars($row['description'] ?? '') ?></small>
                                </td>
                                <td>
                                    <form method="POST" action="" class="d-flex align-items-center justify-content-center gap-1">
                                        <input type="hidden" name="product_id" value="<?= $row['id'] ?>">
                                        <input type="number" name="quantity" value="1" min="1" max="99" class="form-control form-control-sm text-center" style="width: 55px;">
                                        <button type="submit" name="add_to_cart" class="btn btn-sm btn-dark text-nowrap">Add</button>
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
