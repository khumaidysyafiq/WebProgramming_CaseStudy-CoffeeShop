<?php
// create.php — Add a new product (Owner only)
include 'auth.php';
include 'config.php';
require_owner();

$error = "";

if (isset($_POST['submit'])) {
    $name = trim($_POST['name']);
    $type = $_POST['type'];
    $price = (int)$_POST['price'];
    $rating = !empty($_POST['rating']) ? (float)$_POST['rating'] : 4.5;
    $description = trim($_POST['description']);
    $url_image = trim($_POST['url_image']);

    // Check if an image file was uploaded
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_name = time() . '_' . basename($_FILES['image_file']['name']);
        $target_file = $upload_dir . $file_name;
        if (move_uploaded_file($_FILES['image_file']['tmp_name'], $target_file)) {
            $url_image = $target_file;
        }
    }

    if (empty($url_image)) {
        $url_image = 'https://images.unsplash.com/photo-1506372023823-741c83b836fe?w=600&auto=format&fit=crop&q=60';
    }

    if (empty($name) || $price <= 0) {
        $error = "Please fill in all required fields.";
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO product (name, type, price, rating, description, url_image) VALUES (?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssidss", $name, $type, $price, $rating, $description, $url_image);
        
        if (mysqli_stmt_execute($stmt)) {
            header("Location: products.php");
            exit;
        } else {
            $error = "Database error: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - Kopipiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <div class="container my-4">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-6">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-dark text-white py-2">
                        <h5 class="mb-0 fw-bold">Add Product</h5>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>

                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="name" class="form-label">Product Name</label>
                                <input type="text" class="form-control" id="name" name="name" required autofocus>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="type" class="form-label">Type</label>
                                    <select class="form-select" id="type" name="type" required>
                                        <option value="coffee">Coffee</option>
                                        <option value="toast">Toast</option>
                                        <option value="beverage">Beverage</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="price" class="form-label">Price (Rp)</label>
                                    <input type="number" class="form-control" id="price" name="price" min="1000" step="500" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="rating" class="form-label">Rating (0.00 - 5.00)</label>
                                <input type="number" class="form-control" id="rating" name="rating" min="1" max="5" step="0.1" value="4.5">
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="url_image" class="form-label">Image URL</label>
                                <input type="url" class="form-control" id="url_image" name="url_image" placeholder="https://example.com/image.jpg">
                            </div>

                            <div class="mb-4">
                                <label for="image_file" class="form-label">Or Upload Image File</label>
                                <input type="file" class="form-control" id="image_file" name="image_file" accept="image/*">
                            </div>

                            <div class="d-flex justify-content-between pt-2 border-top">
                                <a href="products.php" class="btn btn-outline-secondary">Cancel</a>
                                <button type="submit" name="submit" class="btn btn-dark">Save Product</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
