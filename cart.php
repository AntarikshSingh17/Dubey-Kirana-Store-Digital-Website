
<?php

require_once "config/config.php";

// ==========================================
// 1. INITIALIZE CART
// ==========================================

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}


// ==========================================
// 2. HELPER FUNCTION
// ==========================================

if (!function_exists('cart_clean')) {

    function cart_clean($value)
    {
        return htmlspecialchars(
            (string)$value,
            ENT_QUOTES,
            'UTF-8'
        );
    }

}


// ==========================================
// 3. UPDATE CART
// ==========================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_cart'])
) {

    if (
        isset($_POST['quantities']) &&
        is_array($_POST['quantities'])
    ) {

        foreach ($_POST['quantities'] as $product_id => $quantity) {

            $product_id = filter_var(
                $product_id,
                FILTER_VALIDATE_INT
            );

            $quantity = filter_var(
                $quantity,
                FILTER_VALIDATE_INT
            );

            if (!$product_id || $product_id <= 0) {
                continue;
            }

            // Remove product if quantity is invalid or zero
            if ($quantity === false || $quantity <= 0) {

                unset($_SESSION['cart'][$product_id]);

                continue;

            }

            // Fetch latest stock
            $stmt = $pdo->prepare("
                SELECT stock_quantity, status
                FROM products
                WHERE id = ?
                LIMIT 1
            ");

            $stmt->execute([$product_id]);

            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product || $product['status'] !== 'active') {

                unset($_SESSION['cart'][$product_id]);

                continue;

            }

            $available_stock = (int)$product['stock_quantity'];

            if ($available_stock <= 0) {

                unset($_SESSION['cart'][$product_id]);

                continue;

            }

            $new_quantity = min(
                $quantity,
                $available_stock
            );

            // Maintain array-based cart structure
            if (isset($_SESSION['cart'][$product_id]) &&
                is_array($_SESSION['cart'][$product_id])) {

                $_SESSION['cart'][$product_id]['quantity'] = $new_quantity;

            } else {

                // Compatibility with older quantity-only cart
                $_SESSION['cart'][$product_id] = $new_quantity;

            }

        }

    }

    $_SESSION['cart_message'] = 'Cart updated successfully.';

    header("Location: cart.php");
    exit;

}


// ==========================================
// 4. REMOVE PRODUCT FROM CART
// ==========================================

if (isset($_GET['remove'])) {

    $product_id = filter_input(
        INPUT_GET,
        'remove',
        FILTER_VALIDATE_INT
    );

    if ($product_id && $product_id > 0) {

        unset($_SESSION['cart'][$product_id]);

        $_SESSION['cart_message'] = 'Product removed from cart.';

    }

    header("Location: cart.php");
    exit;

}


// ==========================================
// 5. FETCH CART PRODUCTS
// ==========================================

$cart_products = [];

$subtotal = 0;

$delivery_charge = 0;

$total = 0;


// ==========================================
// 6. NORMALIZE CART DATA
// ==========================================

$normalized_cart = [];

foreach ($_SESSION['cart'] as $product_id => $cart_item) {

    $product_id = filter_var(
        $product_id,
        FILTER_VALIDATE_INT
    );

    if (!$product_id || $product_id <= 0) {
        continue;
    }

    // Array-based cart
    if (is_array($cart_item)) {

        $quantity = (int)($cart_item['quantity'] ?? 0);

    } else {

        // Legacy quantity-based cart
        $quantity = (int)$cart_item;

    }

    if ($quantity <= 0) {
        continue;
    }

    $normalized_cart[$product_id] = [
        'quantity' => $quantity
    ];

}

$_SESSION['cart'] = $normalized_cart;


// ==========================================
// 7. FETCH PRODUCTS FROM DATABASE
// ==========================================

if (!empty($_SESSION['cart'])) {

    $product_ids = array_keys($_SESSION['cart']);

    $placeholders = implode(
        ',',
        array_fill(0, count($product_ids), '?')
    );

    $stmt = $pdo->prepare("
        SELECT
            id,
            product_name,
            price,
            stock_quantity,
            image,
            status
        FROM products
        WHERE id IN ($placeholders)
        AND status = 'active'
    ");

    $stmt->execute($product_ids);

    $cart_products = $stmt->fetchAll(PDO::FETCH_ASSOC);


    // ==========================================
    // 8. CALCULATE SUBTOTAL
    // ==========================================

    foreach ($cart_products as $product) {

        $product_id = (int)$product['id'];

        $quantity = (int)(
            $_SESSION['cart'][$product_id]['quantity'] ?? 0
        );

        $price = (float)$product['price'];

        $item_total = $price * $quantity;

        $subtotal += $item_total;

    }

}


// ==========================================
// 9. CALCULATE TOTAL
// ==========================================

$delivery_charge = 0;

$total = $subtotal + $delivery_charge;


// ==========================================
// 10. INCLUDE HEADER
// ==========================================

require_once "includes/header.php";

?>

<!-- ==========================================
     CART CSS
========================================== -->

<style>

.cart-container {
    max-width: 1200px;
    margin: 40px auto;
    padding: 20px;
}

.cart-title {
    font-size: 32px;
    margin-bottom: 30px;
    color: #222;
    font-weight: 700;
}

.cart-layout {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 30px;
    align-items: start;
}

.cart-main {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 3px 15px rgba(0,0,0,0.06);
    overflow: hidden;
}

.cart-table {
    width: 100%;
    border-collapse: collapse;
}

.cart-table th {
    background: #f5f7f5;
    padding: 18px 15px;
    text-align: center;
    font-weight: 600;
    color: #333;
}

.cart-table td {
    padding: 18px 15px;
    border-bottom: 1px solid #eee;
    text-align: center;
}

.cart-product {
    display: flex;
    align-items: center;
    gap: 15px;
    text-align: left;
}

.cart-product img {
    width: 75px;
    height: 75px;
    object-fit: contain;
    border-radius: 8px;
    background: #f8f8f8;
    padding: 5px;
}

.cart-product-name {
    font-weight: 600;
    color: #222;
}

.cart-product-price {
    color: #777;
    font-size: 14px;
    margin-top: 5px;
}

.quantity-input {
    width: 70px;
    padding: 10px;
    text-align: center;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 15px;
}

.remove-btn {
    color: #e53935;
    text-decoration: none;
    font-weight: 600;
}

.remove-btn:hover {
    text-decoration: underline;
}

.cart-bottom {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 25px;
    margin-top: 30px;
    flex-wrap: wrap;
}

.cart-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.cart-btn {
    padding: 12px 22px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    text-decoration: none;
    font-size: 15px;
    font-weight: 600;
    display: inline-block;
    text-align: center;
    transition: 0.3s;
}

.update-btn {
    background: #333;
    color: #fff;
}

.update-btn:hover {
    background: #111;
}

.shop-btn {
    background: #f1f1f1;
    color: #333;
}

.shop-btn:hover {
    background: #e5e5e5;
}

.checkout-btn {
    background: #198754;
    color: #fff;
    display: block;
    margin-top: 20px;
}

.checkout-btn:hover {
    background: #146c43;
}

.cart-summary {
    width: 100%;
    box-sizing: border-box;
    padding: 25px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 3px 15px rgba(0,0,0,0.06);
}

.cart-summary h3 {
    margin-bottom: 20px;
    font-size: 22px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    margin: 15px 0;
    color: #555;
}

.summary-total {
    font-size: 21px;
    font-weight: bold;
    color: #222;
    border-top: 1px solid #ddd;
    padding-top: 18px;
}

.cart-summary-note {
    margin-top: 20px;
    font-size: 13px;
    color: #777;
    text-align: center;
}

.empty-cart {
    text-align: center;
    padding: 70px 20px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 3px 15px rgba(0,0,0,0.05);
}

.empty-cart i {
    font-size: 65px;
    color: #198754;
    margin-bottom: 20px;
}

.empty-cart h2 {
    margin-bottom: 15px;
    font-size: 26px;
}

.empty-cart p {
    color: #777;
    margin-bottom: 25px;
}

.cart-stock-warning {
    color: #d97706;
    font-size: 12px;
    margin-top: 5px;
}

@media(max-width: 900px) {

    .cart-layout {
        grid-template-columns: 1fr;
    }

}

@media(max-width: 768px) {

    .cart-container {
        padding: 12px;
    }

    .cart-title {
        font-size: 26px;
    }

    .cart-main {
        overflow-x: auto;
    }

    .cart-table {
        min-width: 650px;
    }

    .cart-summary {
        padding: 20px;
    }

}

</style>


<!-- ==========================================
     CART PAGE
========================================== -->

<div class="cart-container">

    <h1 class="cart-title">

        <i class="fas fa-shopping-cart"></i>

        Shopping Cart

    </h1>


    <!-- CART MESSAGE -->

    <?php if (!empty($_SESSION['cart_message'])): ?>

        <div style="padding:12px;margin-bottom:20px;background:#e8f5e9;color:#198754;border-radius:6px;">

            <?= cart_clean($_SESSION['cart_message']) ?>

        </div>

        <?php unset($_SESSION['cart_message']); ?>

    <?php endif; ?>


    <!-- EMPTY CART -->

    <?php if (empty($cart_products)): ?>

        <div class="empty-cart">

            <i class="fas fa-basket-shopping"></i>

            <h2>Your Cart is Empty!</h2>

            <p>You haven't added any products to your cart yet.</p>

            <a
                href="products.php"
                class="cart-btn checkout-btn"
                style="display:inline-block;"
            >

                <i class="fas fa-store"></i>

                Continue Shopping

            </a>

        </div>

    <?php else: ?>


        <!-- CART LAYOUT -->

        <div class="cart-layout">


            <!-- CART PRODUCTS -->

            <div class="cart-main">

                <form method="POST" action="cart.php">

                    <table class="cart-table">

                        <thead>

                            <tr>

                                <th>Product</th>

                                <th>Price</th>

                                <th>Quantity</th>

                                <th>Total</th>

                                <th>Remove</th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach ($cart_products as $product): ?>

                                <?php

                                $product_id = (int)$product['id'];

                                $quantity = (int)(
                                    $_SESSION['cart'][$product_id]['quantity'] ?? 0
                                );

                                $price = (float)$product['price'];

                                $item_total = $price * $quantity;

                                $stock = (int)$product['stock_quantity'];

                                ?>


                                <tr>


                                    <!-- PRODUCT -->

                                    <td>

                                        <div class="cart-product">

                                            <?php if (!empty($product['image'])): ?>

                                                <img
                                                    src="<?= SITE_URL . cart_clean($product['image']) ?>"
                                                    alt="<?= cart_clean($product['product_name']) ?>"
                                                >

                                            <?php else: ?>

                                                <img
                                                    src="https://placehold.co/75x75?text=Product"
                                                    alt="Product image unavailable"
                                                >

                                            <?php endif; ?>


                                            <div>

                                                <div class="cart-product-name">

                                                    <?= cart_clean($product['product_name']) ?>

                                                </div>


                                                <div class="cart-product-price">

                                                    <?= CURRENCY ?>

                                                    <?= number_format($price, 2) ?>

                                                </div>


                                                <?php if ($quantity > $stock): ?>

                                                    <div class="cart-stock-warning">

                                                        Stock has changed. Please update quantity.

                                                    </div>

                                                <?php endif; ?>

                                            </div>

                                        </div>

                                    </td>


                                    <!-- PRICE -->

                                    <td>

                                        <?= CURRENCY ?>

                                        <?= number_format($price, 2) ?>

                                    </td>


                                    <!-- QUANTITY -->

                                    <td>

                                        <input
                                            type="number"
                                            name="quantities[<?= $product_id ?>]"
                                            value="<?= $quantity ?>"
                                            min="1"
                                            max="<?= max(1, $stock) ?>"
                                            class="quantity-input"
                                            required
                                        >

                                    </td>


                                    <!-- ITEM TOTAL -->

                                    <td>

                                        <strong>

                                            <?= CURRENCY ?>

                                            <?= number_format($item_total, 2) ?>

                                        </strong>

                                    </td>


                                    <!-- REMOVE -->

                                    <td>

                                        <a
                                            href="cart.php?remove=<?= $product_id ?>"
                                            class="remove-btn"
                                            onclick="return confirm('Are you sure you want to remove this product?')"
                                        >

                                            <i class="fas fa-trash"></i>

                                            Remove

                                        </a>

                                    </td>


                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>


                    <!-- CART ACTIONS -->

                    <div class="cart-bottom">

                        <div class="cart-actions">

                            <button
                                type="submit"
                                name="update_cart"
                                value="1"
                                class="cart-btn update-btn"
                            >

                                <i class="fas fa-sync-alt"></i>

                                Update Cart

                            </button>


                            <a
                                href="products.php"
                                class="cart-btn shop-btn"
                            >

                                <i class="fas fa-store"></i>

                                Continue Shopping

                            </a>

                        </div>

                    </div>

                </form>

            </div>



            <!-- CART SUMMARY -->

            <div class="cart-summary">

                <h3>

                    <i class="fas fa-receipt"></i>

                    Cart Summary

                </h3>


                <!-- SUBTOTAL -->

                <div class="summary-row">

                    <span>Subtotal</span>

                    <span>

                        <?= CURRENCY ?>

                        <?= number_format($subtotal, 2) ?>

                    </span>

                </div>


                <!-- DELIVERY -->

                <div class="summary-row">

                    <span>Delivery</span>

                    <span>Free</span>

                </div>


                <!-- TOTAL -->

                <div class="summary-row summary-total">

                    <span>Total</span>

                    <span>

                        <?= CURRENCY ?>

                        <?= number_format($total, 2) ?>

                    </span>

                </div>


                <!-- CHECKOUT -->

                <a
                    href="checkout.php"
                    class="cart-btn checkout-btn"
                >

                    <i class="fas fa-lock"></i>

                    Proceed to Checkout

                    <i class="fas fa-arrow-right"></i>

                </a>


                <!-- NOTE -->

                <p class="cart-summary-note">

                    <i class="fas fa-shield-halved"></i>

                    Secure checkout with Cash on Delivery.

                </p>

            </div>

        </div>

    <?php endif; ?>

</div>


<?php

// ==========================================
// 11. INCLUDE FOOTER
// ==========================================

require_once "includes/footer.php";

?>
