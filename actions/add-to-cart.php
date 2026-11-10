
<?php

// ==========================================
// 1. LOAD CONFIGURATION AND START SESSION
// ==========================================

require_once __DIR__ . '/../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// ==========================================
// 2. INITIALIZE CART
// ==========================================

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}


// ==========================================
// 3. CHECK REQUEST METHOD
// ==========================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . SITE_URL . "products.php");
    exit;
}


// ==========================================
// 4. GET PRODUCT ID AND QUANTITY
// ==========================================

$product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

if (!$product_id || $product_id <= 0) {
    $_SESSION['cart_message'] = "Invalid product selected.";
    header("Location: " . SITE_URL . "products.php");
    exit;
}

if (!$quantity || $quantity <= 0) {
    $quantity = 1;
}


// ==========================================
// 5. FETCH PRODUCT FROM DATABASE
// ==========================================

try {

    $stmt = $pdo->prepare("
        SELECT
            id,
            product_name,
            price,
            stock_quantity,
            image,
            status
        FROM products
        WHERE id = :id
        LIMIT 1
    ");

    $stmt->execute([
        ':id' => $product_id
    ]);

    $product = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $_SESSION['cart_message'] = "Database error. Please try again.";

    header("Location: " . SITE_URL . "products.php");
    exit;

}


// ==========================================
// 6. CHECK PRODUCT AVAILABILITY
// ==========================================

if (!$product) {

    $_SESSION['cart_message'] = "Product not found.";

    header("Location: " . SITE_URL . "products.php");
    exit;

}

if ($product['status'] !== 'active') {

    $_SESSION['cart_message'] = "This product is currently unavailable.";

    header("Location: " . SITE_URL . "products.php");
    exit;

}


// ==========================================
// 7. CHECK STOCK
// ==========================================

$available_stock = (int)$product['stock_quantity'];

if ($available_stock <= 0) {

    $_SESSION['cart_message'] = "This product is out of stock.";

    header("Location: " . SITE_URL . "products.php");
    exit;

}


// ==========================================
// 8. GET CURRENT CART QUANTITY
// ==========================================

$current_quantity = 0;

if (isset($_SESSION['cart'][$product_id])) {

    $cart_item = $_SESSION['cart'][$product_id];

    if (is_array($cart_item)) {

        $current_quantity = (int)($cart_item['quantity'] ?? 0);

    } else {

        $current_quantity = (int)$cart_item;

    }

}


// ==========================================
// 9. CALCULATE NEW QUANTITY
// ==========================================

$new_quantity = $current_quantity + $quantity;


// ==========================================
// 10. CHECK STOCK LIMIT
// ==========================================

if ($new_quantity > $available_stock) {

    $_SESSION['cart_message'] =
        "Only " . $available_stock . " units are available.";

    header("Location: " . SITE_URL . "cart.php");
    exit;

}


// ==========================================
// 11. ADD PRODUCT TO CART
// ==========================================

$_SESSION['cart'][$product_id] = [

    'id' => (int)$product['id'],

    'name' => $product['product_name'],

    'price' => (float)$product['price'],

    'image' => $product['image'],

    'quantity' => $new_quantity

];


// ==========================================
// 12. SUCCESS MESSAGE
// ==========================================

$_SESSION['cart_message'] = "Product added to cart successfully!";


// ==========================================
// 13. REDIRECT TO CART
// ==========================================

header("Location: " . SITE_URL . "cart.php");

exit;

?>