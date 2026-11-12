<?php
// products.php — Owner product inventory management
include 'auth.php';
include 'config.php';
require_owner();

$result = mysqli_query($conn, "SELECT * FROM product ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Kopipiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <div class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="fw-bold mb-0">Manage Products</h3>
            <a href="create.php" class="btn btn-success btn-sm">Add Product</a>
        </div>

        <div class="card shadow-sm border-0">
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
                            <?php $no = 1; while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= ucfirst(htmlspecialchars($row['type'])) ?></td>
                                <td>
                                    <img src="<?= htmlspecialchars($row['url_image']) ?>" width="80" height="60" class="rounded object-fit-cover">
                                </td>
                                <td class="fw-bold"><?= htmlspecialchars($row['name']) ?></td>
                                <td>
                                    Rp <?= number_format($row['price'], 0, ',', '.') ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($row['rating'] ?? '0.00') ?>/5.0
                                </td>
                                <td>
                                    <small class="text-muted"><?= htmlspecialchars($row['description'] ?? '') ?></small>
                                </td>
                                <td class="text-center">
                                    <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                    <a href="delete.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this product?')">Delete</a>
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
