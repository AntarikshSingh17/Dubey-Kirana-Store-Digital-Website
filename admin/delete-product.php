<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

// Only allow POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: products.php");
    exit;
}

// Validate CSRF token
if (
    !isset($_POST["csrf_token"]) ||
    !isset($_SESSION["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
) {
    header("Location: products.php?error=invalid_request");
    exit;
}

// Validate product ID
$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    header("Location: products.php?error=invalid_id");
    exit;
}

try {

    // Fetch product image
    $stmt = $pdo->prepare("
        SELECT image
        FROM products
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        header("Location: products.php?error=not_found");
        exit;
    }

    // Delete product from database
    $deleteStmt = $pdo->prepare("
        DELETE FROM products
        WHERE id = ?
    ");

    $deleteStmt->execute([$id]);

    // Delete product image from uploads folder
    if (!empty($product["image"])) {

        $imageName = basename($product["image"]);

        $imagePath = "../uploads/products/" . $imageName;

        if (is_file($imagePath)) {

            if (!unlink($imagePath)) {
                error_log("Failed to delete product image: " . $imageName);
            }

        }
    }

    // Redirect after successful deletion
    header("Location: products.php?deleted=1");
    exit;

} catch (PDOException $e) {

    // Log actual error for administrator
    error_log("Product deletion error: " . $e->getMessage());

    header("Location: products.php?error=delete_failed");
    exit;
}

?>