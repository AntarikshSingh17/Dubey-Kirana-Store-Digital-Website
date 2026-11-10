<?php

session_start();

require_once __DIR__ . '/../config/config.php';

// ============================================
// 1. CHECK REQUEST METHOD
// ============================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . 'checkout.php');
    exit;
}

// ============================================
// 2. CHECK USER LOGIN
// ============================================

if (!isLoggedIn()) {
    redirect(SITE_URL . 'login.php');
    exit;
}

// ============================================
// 3. GET CART
// ============================================

$cart = $_SESSION['cart'] ?? [];

if (empty($cart)) {
    redirect(SITE_URL . 'cart.php');
    exit;
}

// ============================================
// 4. GET CHECKOUT FORM DATA
// ============================================

$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');

$payment_method = $_POST['payment_method'] ?? '';

// ============================================
// 5. VALIDATE CHECKOUT INFORMATION
// ============================================

if (
    $name === '' ||
    $phone === '' ||
    $address === '' ||
    $payment_method !== 'COD'
) {
    $_SESSION['checkout_error'] =
        "Please enter valid checkout information.";

    redirect(SITE_URL . 'checkout.php');
    exit;
}

// Validate phone number
if (!preg_match('/^[0-9]{10}$/', $phone)) {
    $_SESSION['checkout_error'] =
        "Please enter a valid 10-digit mobile number.";

    redirect(SITE_URL . 'checkout.php');
    exit;
}

// ============================================
// 6. PROCESS ORDER
// ============================================

try {

    // Start database transaction
    $pdo->beginTransaction();

    $user_id = (int) $_SESSION['user_id'];

    $total = 0;

    $order_products = [];

    // ========================================
    // 7. VALIDATE PRODUCTS AND STOCK
    // ========================================

    foreach ($cart as $product_id => $cart_item) {

        $product_id = (int) $product_id;

        // Support both cart structures
        if (is_array($cart_item)) {

            $quantity = (int) ($cart_item['quantity'] ?? 0);

        } else {

            $quantity = (int) $cart_item;
        }

        // Validate product ID and quantity
        if ($product_id <= 0 || $quantity <= 0) {
            throw new Exception("Invalid cart item.");
        }

        // Fetch product and lock row
        $stmt = $pdo->prepare("
            SELECT
                id,
                name,
                price,
                stock,
                active
            FROM products
            WHERE id = ?
            FOR UPDATE
        ");

        $stmt->execute([$product_id]);

        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check product availability
        if (!$product || $product['active'] != 1) {
            throw new Exception(
                "A product in your cart is unavailable."
            );
        }

        // Check available stock
        if ($quantity > (int) $product['stock']) {
            throw new Exception(
                "Insufficient stock for " . $product['name'] . "."
            );
        }

        // Calculate subtotal
        $subtotal = (float) $product['price'] * $quantity;

        $total += $subtotal;

        // Store product information
        $order_products[] = [
            'id'       => (int) $product['id'],
            'quantity' => $quantity,
            'price'    => (float) $product['price']
        ];
    }

    // ========================================
    // 8. CREATE ORDER
    // ========================================

    $stmt = $pdo->prepare("
        INSERT INTO orders
        (
            user_id,
            total_amount,
            status,
            payment_method,
            shipping_address,
            created_at
        )
        VALUES
        (
            ?,
            ?,
            'Pending',
            ?,
            ?,
            NOW()
        )
    ");

    $stmt->execute([
        $user_id,
        $total,
        $payment_method,
        $address
    ]);

    // Get newly created order ID
    $order_id = $pdo->lastInsertId();

    // ========================================
    // 9. INSERT ORDER ITEMS
    // ========================================

    foreach ($order_products as $item) {

        $stmt = $pdo->prepare("
            INSERT INTO order_items
            (
                order_id,
                product_id,
                quantity,
                price
            )
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([
            $order_id,
            $item['id'],
            $item['quantity'],
            $item['price']
        ]);

        // ====================================
        // 10. REDUCE PRODUCT STOCK
        // ====================================

        $stmt = $pdo->prepare("
            UPDATE products
            SET stock = stock - ?
            WHERE id = ?
              AND stock >= ?
        ");

        $stmt->execute([
            $item['quantity'],
            $item['id'],
            $item['quantity']
        ]);

        // Ensure stock was successfully updated
        if ($stmt->rowCount() !== 1) {
            throw new Exception(
                "Unable to update stock for a product."
            );
        }
    }

    // ========================================
    // 11. COMMIT TRANSACTION
    // ========================================

    $pdo->commit();

    // ========================================
    // 12. CLEAR CART
    // ========================================

    unset($_SESSION['cart']);

    // ========================================
    // 13. SAVE ORDER ID
    // ========================================

    $_SESSION['last_order_id'] = $order_id;

    // ========================================
    // 14. SUCCESS REDIRECT
    // ========================================

    redirect(SITE_URL . 'my-orders.php');
    exit;

} catch (Exception $e) {

    // Rollback transaction
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Store error message
    $_SESSION['checkout_error'] = $e->getMessage();

    // Redirect to checkout
    redirect(SITE_URL . 'checkout.php');
    exit;
}

?>