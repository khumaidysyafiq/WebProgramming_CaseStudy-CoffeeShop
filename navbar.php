<?php
// navbar.php — include right after the <body> tag
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $qty) {
        $cart_count += (int)$qty;
    }
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">Kopipiro</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">Menu</a>
                </li>
                <?php if (is_owner()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">Manage Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="owner_dashboard.php">Orders & Chart</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">Staff Management</a>
                    </li>
                <?php elseif (is_cashier()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="cashier_orders.php">Cashier Orders</a>
                    </li>
                <?php endif; ?>
            </ul>

            <div class="d-flex align-items-center gap-2">
                <a href="cart.php" class="btn btn-outline-light btn-sm">
                    Cart (<?= $cart_count ?>)
                </a>

                <?php if (is_logged_in()): ?>
                    <span class="text-white small me-1">
                        <?= htmlspecialchars($_SESSION['username']) ?> (<?= htmlspecialchars($_SESSION['role']) ?>)
                    </span>
                    <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-light btn-sm">Login</a>
                    <a href="register.php" class="btn btn-light btn-sm">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
