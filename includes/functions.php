<?php

/**
 * Dubey Kirana Store
 * Common Helper Functions
 */

// ==========================================
// 1. SESSION INITIALIZATION
// ==========================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// ==========================================
// 2. SAFE OUTPUT FUNCTION
// ==========================================

function clean($data)
{
    if (!is_scalar($data) && $data !== null) {
        return "";
    }

    return htmlspecialchars(
        (string)($data ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}


// ==========================================
// 3. USER LOGIN CHECK
// ==========================================

function isLoggedIn()
{
    return !empty($_SESSION["user_id"]);
}


// ==========================================
// 4. ADMIN LOGIN CHECK
// ==========================================

function isAdmin()
{
    // Admin login stores admin_id in the session.
    return !empty($_SESSION["admin_id"]);
}


// ==========================================
// 5. REDIRECT FUNCTION
// ==========================================

function redirect($url)
{
    header("Location: " . $url);
    exit;
}


// ==========================================
// 6. GET CART COUNT
// ==========================================

function getCartCount()
{
    if (
        !isset($_SESSION["cart"]) ||
        !is_array($_SESSION["cart"])
    ) {
        return 0;
    }

    $count = 0;

    foreach ($_SESSION["cart"] as $productId => $quantity) {

        $productId = filter_var(
            $productId,
            FILTER_VALIDATE_INT
        );

        $quantity = filter_var(
            $quantity,
            FILTER_VALIDATE_INT
        );

        if (
            $productId !== false &&
            $productId > 0 &&
            $quantity !== false &&
            $quantity > 0
        ) {
            $count += $quantity;
        }
    }

    return $count;
}


// ==========================================
// 7. GET CART TOTAL
// ==========================================

function getCartTotal($pdo)
{
    $total = 0;

    if (
        !isset($_SESSION["cart"]) ||
        !is_array($_SESSION["cart"])
    ) {
        return 0;
    }

    foreach ($_SESSION["cart"] as $productId => $quantity) {

        // Validate product ID
        $productId = filter_var(
            $productId,
            FILTER_VALIDATE_INT
        );

        // Validate quantity
        $quantity = filter_var(
            $quantity,
            FILTER_VALIDATE_INT
        );

        if (
            $productId === false ||
            $productId <= 0 ||
            $quantity === false ||
            $quantity <= 0
        ) {
            continue;
        }

        // Fetch product price
        $stmt = $pdo->prepare("
            SELECT price
            FROM products
            WHERE id = ?
              AND LOWER(status) = 'active'
        ");

        $stmt->execute([$productId]);

        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {

            $price = (float)$product["price"];

            $total += $price * $quantity;
        }
    }

    return round($total, 2);
}


// ==========================================
// 8. CHECK PRODUCT IN CART
// ==========================================

function isProductInCart($productId)
{
    if (
        !isset($_SESSION["cart"]) ||
        !is_array($_SESSION["cart"])
    ) {
        return false;
    }

    return isset($_SESSION["cart"][$productId]);
}


// ==========================================
// 9. GET PRODUCT QUANTITY IN CART
// ==========================================

function getProductCartQuantity($productId)
{
    if (
        !isset($_SESSION["cart"]) ||
        !is_array($_SESSION["cart"])
    ) {
        return 0;
    }

    if (!isset($_SESSION["cart"][$productId])) {
        return 0;
    }

    $quantity = filter_var(
        $_SESSION["cart"][$productId],
        FILTER_VALIDATE_INT
    );

    if ($quantity === false || $quantity <= 0) {
        return 0;
    }

    return $quantity;
}

?>