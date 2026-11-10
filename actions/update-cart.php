<?php

session_start();

require_once "../includes/db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../cart.php");
    exit;
}

$product_id = filter_input(INPUT_POST, "product_id", FILTER_VALIDATE_INT);
$action = $_POST["action"] ?? "";

if (!$product_id || !isset($_SESSION["cart"][$product_id])) {
    header("Location: ../cart.php");
    exit;
}

// Remove product from cart
if ($action === "remove") {

    unset($_SESSION["cart"][$product_id]);

    $_SESSION["cart_message"] = "Product removed from cart.";

    header("Location: ../cart.php");
    exit;
}

// Update quantity
if ($action === "update") {

    $quantity = filter_input(INPUT_POST, "quantity", FILTER_VALIDATE_INT);

    if (!$quantity || $quantity < 1) {
        $_SESSION["cart_message"] = "Invalid quantity.";
        header("Location: ../cart.php");
        exit;
    }

    // Check current product stock
    $stmt = $pdo->prepare("
        SELECT stock_quantity, status
        FROM products
        WHERE id = ?
    ");

    $stmt->execute([$product_id]);

    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product || $product["status"] !== "active") {

        unset($_SESSION["cart"][$product_id]);

        $_SESSION["cart_message"] = "Product is no longer available.";

        header("Location: ../cart.php");
        exit;
    }

    if ($quantity > $product["stock_quantity"]) {

        $_SESSION["cart_message"] = "Only " . $product["stock_quantity"] . " items are available.";

        header("Location: ../cart.php");
        exit;
    }

    $_SESSION["cart"][$product_id]["quantity"] = $quantity;

    $_SESSION["cart_message"] = "Cart updated successfully.";

    header("Location: ../cart.php");
    exit;
}

header("Location: ../cart.php");
exit;

?>