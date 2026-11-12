<?php
// delete.php — Delete a product (Owner only)
include 'auth.php';
include 'config.php';
require_owner();

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = mysqli_prepare($conn, "DELETE FROM product WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
}

header("Location: products.php");
exit;
?>
