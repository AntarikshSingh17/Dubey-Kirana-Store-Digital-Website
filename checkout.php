
<?php

session_start();

require_once "config/config.php";

// ==========================================
// 1. INITIALIZE CART
// ==========================================

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$errors = [];
$success_order = null;


// ==========================================
// 2. HELPER FUNCTION
// ==========================================

function checkout_clean($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}


// ==========================================
// 3. ORDER CONFIRMATION REDIRECT
// ==========================================

if (isset($_GET['order'])) {

    $order_number = trim($_GET['order']);

    if (preg_match('/^DKS[0-9]{8}[A-F0-9]{8}$/', $order_number)) {

        header(
            "Location: order-success.php?order=" .
            urlencode($order_number)
        );

        exit;
    }
}


// ==========================================
// 4. CHECK CART
// ==========================================

if (empty($_SESSION['cart'])) {

    header("Location: cart.php");
    exit;
}


// ==========================================
// 5. DEFAULT CUSTOMER VALUES
// ==========================================

$customer_name = "";
$mobile = "";
$email = "";
$address = "";
$city = "";
$pincode = "";


// ==========================================
// 6. FETCH CART PRODUCTS
// ==========================================

$cart_products = [];

$subtotal = 0;

$delivery_charge = 0;

$total_amount = 0;


if (!empty($_SESSION['cart'])) {

    $valid_cart = [];

    foreach ($_SESSION['cart'] as $product_id => $cart_item) {

        $product_id = filter_var(
            $product_id,
            FILTER_VALIDATE_INT
        );

        // Support both cart formats:
        // Old: cart[product_id] = quantity
        // New: cart[product_id]['quantity'] = quantity

        if (is_array($cart_item)) {

            $quantity = filter_var(
                $cart_item['quantity'] ?? 0,
                FILTER_VALIDATE_INT
            );

        } else {

            $quantity = filter_var(
                $cart_item,
                FILTER_VALIDATE_INT
            );
        }

        if (
            $product_id !== false &&
            $product_id > 0 &&
            $quantity !== false &&
            $quantity > 0
        ) {

            $valid_cart[$product_id] = $quantity;
        }
    }

    $_SESSION['cart'] = $valid_cart;


    if (!empty($valid_cart)) {

        $product_ids = array_keys($valid_cart);

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


        foreach ($cart_products as $product) {

            $product_id = (int)$product['id'];

            $quantity = (int)(
                $_SESSION['cart'][$product_id] ?? 0
            );

            $price = (float)$product['price'];

            $subtotal += $price * $quantity;
        }
    }
}


// ==========================================
// 7. CALCULATE TOTAL
// ==========================================

$delivery_charge = 0;

$total_amount = $subtotal + $delivery_charge;


// ==========================================
// 8. PLACE ORDER
// ==========================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['place_order'])
) {

    $customer_name = trim($_POST['customer_name'] ?? '');

    $mobile = trim($_POST['mobile'] ?? '');

    $email = trim($_POST['email'] ?? '');

    $address = trim($_POST['address'] ?? '');

    $city = trim($_POST['city'] ?? '');

    $pincode = trim($_POST['pincode'] ?? '');


    // ==========================================
    // VALIDATION
    // ==========================================

    if ($customer_name === '') {
        $errors[] = "Please enter your full name.";
    }

    if (strlen($customer_name) > 100) {
        $errors[] = "Name cannot exceed 100 characters.";
    }

    if (!preg_match('/^[0-9]{10}$/', $mobile)) {
        $errors[] = "Please enter a valid 10-digit mobile number.";
    }

    if (
        $email !== '' &&
        !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {
        $errors[] = "Please enter a valid email address.";
    }

    if ($address === '') {
        $errors[] = "Please enter your delivery address.";
    }

    if ($city === '') {
        $errors[] = "Please enter your city.";
    }

    if (!preg_match('/^[0-9]{6}$/', $pincode)) {
        $errors[] = "Please enter a valid 6-digit PIN code.";
    }

    if (empty($_SESSION['cart'])) {
        $errors[] = "Your cart is empty.";
    }


    // ==========================================
    // PROCESS ORDER
    // ==========================================

    if (empty($errors)) {

        try {

            $pdo->beginTransaction();

            $locked_products = [];

            $subtotal = 0;


            // ==========================================
            // LOCK AND VALIDATE PRODUCTS
            // ==========================================

            foreach ($_SESSION['cart'] as $product_id => $quantity) {

                $product_id = (int)$product_id;

                $quantity = (int)$quantity;

                if ($product_id <= 0 || $quantity <= 0) {

                    throw new Exception(
                        "Invalid product quantity in cart."
                    );
                }

                $stmt = $pdo->prepare("
                    SELECT
                        id,
                        product_name,
                        price,
                        stock_quantity,
                        image,
                        status
                    FROM products
                    WHERE id = ?
                    AND status = 'active'
                    FOR UPDATE
                ");

                $stmt->execute([$product_id]);

                $product = $stmt->fetch(PDO::FETCH_ASSOC);


                if (!$product) {

                    throw new Exception(
                        "A product in your cart is unavailable."
                    );
                }


                if ((int)$product['stock_quantity'] < $quantity) {

                    throw new Exception(
                        "Insufficient stock for " .
                        $product['product_name'] .
                        ". Available quantity: " .
                        (int)$product['stock_quantity']
                    );
                }


                $locked_products[] = [
                    'product' => $product,
                    'quantity' => $quantity
                ];
            }


            // ==========================================
            // RECALCULATE TOTAL
            // ==========================================

            foreach ($locked_products as $item) {

                $product = $item['product'];

                $quantity = $item['quantity'];

                $price = (float)$product['price'];

                $subtotal += $price * $quantity;
            }


            $delivery_charge = 0;

            $total_amount = $subtotal + $delivery_charge;


            // ==========================================
            // CREATE FULL ADDRESS
            // ==========================================

            $full_address =
                $address .
                ", " . $city .
                ", PIN: " . $pincode;


            // ==========================================
            // CHECK EXISTING CUSTOMER
            // ==========================================

            $stmt = $pdo->prepare("
                SELECT id
                FROM customers
                WHERE mobile = ?
                LIMIT 1
            ");

            $stmt->execute([$mobile]);

            $existing_customer = $stmt->fetch(PDO::FETCH_ASSOC);


            if ($existing_customer) {

                $customer_id = (int)$existing_customer['id'];

                $stmt = $pdo->prepare("
                    UPDATE customers
                    SET customer_name = ?,
                        email = ?,
                        address = ?
                    WHERE id = ?
                ");

                $stmt->execute([
                    $customer_name,
                    $email !== '' ? $email : null,
                    $full_address,
                    $customer_id
                ]);

            } else {

                $stmt = $pdo->prepare("
                    INSERT INTO customers
                    (
                        customer_name,
                        mobile,
                        email,
                        address
                    )
                    VALUES (?, ?, ?, ?)
                ");

                $stmt->execute([
                    $customer_name,
                    $mobile,
                    $email !== '' ? $email : null,
                    $full_address
                ]);

                $customer_id = (int)$pdo->lastInsertId();
            }


            // ==========================================
            // GENERATE ORDER NUMBER
            // ==========================================

            $order_number = "DKS" .
                date("Ymd") .
                strtoupper(bin2hex(random_bytes(4)));


            // ==========================================
            // INSERT ORDER
            // ==========================================

            $stmt = $pdo->prepare("
                INSERT INTO orders
                (
                    order_number,
                    customer_id,
                    total_amount,
                    delivery_charge,
                    payment_method,
                    payment_status,
                    order_status
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $order_number,
                $customer_id,
                $total_amount,
                $delivery_charge,
                "Cash on Delivery",
                "Pending",
                "Pending"
            ]);

            $order_id = (int)$pdo->lastInsertId();


            // ==========================================
            // INSERT ORDER ITEMS AND REDUCE STOCK
            // ==========================================

            foreach ($locked_products as $item) {

                $product = $item['product'];

                $product_id = (int)$product['id'];

                $product_name = $product['product_name'];

                $price = (float)$product['price'];

                $quantity = (int)$item['quantity'];

                $item_subtotal = $price * $quantity;


                // INSERT ORDER ITEM

                $stmt = $pdo->prepare("
                    INSERT INTO order_items
                    (
                        order_id,
                        product_id,
                        product_name,
                        price,
                        quantity,
                        subtotal
                    )
                    VALUES (?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $order_id,
                    $product_id,
                    $product_name,
                    $price,
                    $quantity,
                    $item_subtotal
                ]);


                // REDUCE STOCK

                $stmt = $pdo->prepare("
                    UPDATE products
                    SET stock_quantity = stock_quantity - ?
                    WHERE id = ?
                    AND stock_quantity >= ?
                ");

                $stmt->execute([
                    $quantity,
                    $product_id,
                    $quantity
                ]);


                if ($stmt->rowCount() !== 1) {

                    throw new Exception(
                        "Unable to update stock for " .
                        $product_name
                    );
                }
            }


            // ==========================================
            // COMMIT TRANSACTION
            // ==========================================

            $pdo->commit();


            // ==========================================
            // CLEAR CART
            // ==========================================

            $_SESSION['cart'] = [];


            // ==========================================
            // REDIRECT TO ORDER SUCCESS PAGE
            // ==========================================

            header(
                "Location: order-success.php?order=" .
                urlencode($order_number)
            );

            exit;


        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $errors[] = $e->getMessage();
        }
    }
}


// ==========================================
// 9. INCLUDE HEADER
// ==========================================

require_once "includes/header.php";

?>


<!-- ==========================================
     CHECKOUT CSS
========================================== -->

<style>

.checkout-container {
    max-width: 1200px;
    margin: 40px auto;
    padding: 20px;
}

.checkout-title {
    font-size: 32px;
    margin-bottom: 30px;
    color: #222;
}

.checkout-grid {
    display: grid;
    grid-template-columns: 1.5fr 1fr;
    gap: 30px;
}

.checkout-box {
    background: #fff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 3px 15px rgba(0,0,0,0.08);
}

.checkout-box h2 {
    margin-bottom: 25px;
    font-size: 23px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 15px;
    box-sizing: border-box;
}

.form-group textarea {
    resize: vertical;
    min-height: 100px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.payment-option {
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background: #f9f9f9;
}

.payment-option input {
    margin-right: 10px;
}

.order-product {
    display: flex;
    justify-content: space-between;
    gap: 15px;
    margin-bottom: 18px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
}

.order-product-name {
    font-weight: 600;
}

.order-product-quantity {
    color: #777;
    font-size: 14px;
}

.order-summary-row {
    display: flex;
    justify-content: space-between;
    margin: 15px 0;
}

.order-total {
    border-top: 1px solid #ddd;
    padding-top: 18px;
    font-size: 21px;
    font-weight: bold;
}

.place-order-btn {
    width: 100%;
    padding: 15px;
    background: #198754;
    color: white;
    border: none;
    border-radius: 7px;
    font-size: 17px;
    font-weight: bold;
    cursor: pointer;
    margin-top: 20px;
}

.place-order-btn:hover {
    background: #146c43;
}

.error-message {
    background: #ffe5e5;
    color: #b00020;
    padding: 15px;
    border-radius: 7px;
    margin-bottom: 20px;
}

.continue-btn {
    display: inline-block;
    margin-top: 20px;
    padding: 12px 25px;
    background: #198754;
    color: white;
    text-decoration: none;
    border-radius: 6px;
}

@media(max-width: 768px) {

    .checkout-grid {
        grid-template-columns: 1fr;
    }

    .form-row {
        grid-template-columns: 1fr;
    }

    .checkout-box {
        padding: 20px;
    }

}

</style>


<!-- ==========================================
     CHECKOUT PAGE
========================================== -->

<div class="checkout-container">

    <h1 class="checkout-title">
        <i class="fas fa-shopping-bag"></i>
        Checkout
    </h1>


    <!-- DISPLAY ERRORS -->

    <?php if (!empty($errors)): ?>

        <div class="error-message">

            <strong>
                Please correct the following errors:
            </strong>

            <?php foreach ($errors as $error): ?>

                <p>
                    <?= checkout_clean($error) ?>
                </p>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>


    <!-- EMPTY CART -->

    <?php if (empty($cart_products)): ?>

        <div class="checkout-box">

            <h2>Your Cart is Empty!</h2>

            <p>
                Add some products before proceeding to checkout.
            </p>

            <a href="products.php" class="continue-btn">
                Continue Shopping
            </a>

        </div>


    <?php else: ?>


        <!-- CHECKOUT GRID -->

        <div class="checkout-grid">


            <!-- DELIVERY INFORMATION -->

            <div class="checkout-box">

                <h2>
                    <i class="fas fa-location-dot"></i>
                    Delivery Information
                </h2>


                <form
                    method="POST"
                    action="checkout.php"
                >


                    <!-- CUSTOMER NAME -->

                    <div class="form-group">

                        <label for="customer_name">
                            Full Name *
                        </label>

                        <input
                            type="text"
                            id="customer_name"
                            name="customer_name"
                            maxlength="100"
                            value="<?= checkout_clean($customer_name) ?>"
                            required
                        >

                    </div>


                    <!-- MOBILE AND EMAIL -->

                    <div class="form-row">

                        <div class="form-group">

                            <label for="mobile">
                                Mobile Number *
                            </label>

                            <input
                                type="tel"
                                id="mobile"
                                name="mobile"
                                maxlength="10"
                                pattern="[0-9]{10}"
                                value="<?= checkout_clean($mobile) ?>"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label for="email">
                                Email Address
                            </label>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="<?= checkout_clean($email) ?>"
                            >

                        </div>

                    </div>


                    <!-- DELIVERY ADDRESS -->

                    <div class="form-group">

                        <label for="address">
                            Delivery Address *
                        </label>

                        <textarea
                            id="address"
                            name="address"
                            required
                        ><?= checkout_clean($address) ?></textarea>

                    </div>


                    <!-- CITY AND PINCODE -->

                    <div class="form-row">

                        <div class="form-group">

                            <label for="city">
                                City *
                            </label>

                            <input
                                type="text"
                                id="city"
                                name="city"
                                value="<?= checkout_clean($city) ?>"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label for="pincode">
                                PIN Code *
                            </label>

                            <input
                                type="text"
                                id="pincode"
                                name="pincode"
                                maxlength="6"
                                pattern="[0-9]{6}"
                                value="<?= checkout_clean($pincode) ?>"
                                required
                            >

                        </div>

                    </div>


                    <!-- PAYMENT METHOD -->

                    <div class="form-group">

                        <label>
                            Payment Method
                        </label>

                        <div class="payment-option">

                            <input
                                type="radio"
                                checked
                                disabled
                            >

                            <i class="fas fa-money-bill-wave"></i>

                            Cash on Delivery

                        </div>

                    </div>


                    <!-- PLACE ORDER -->

                    <button
                        type="submit"
                        name="place_order"
                        value="1"
                        class="place-order-btn"
                    >

                        <i class="fas fa-check-circle"></i>

                        Place Order -

                        <?= CURRENCY ?>

                        <?= number_format($total_amount, 2) ?>

                    </button>


                </form>

            </div>



            <!-- ORDER SUMMARY -->

            <div class="checkout-box">

                <h2>
                    <i class="fas fa-receipt"></i>
                    Your Order
                </h2>


                <!-- PRODUCTS -->

                <?php foreach ($cart_products as $product): ?>

                    <?php

                    $product_id = (int)$product['id'];

                    $quantity = (int)(
                        $_SESSION['cart'][$product_id] ?? 0
                    );

                    $price = (float)$product['price'];

                    $item_total = $price * $quantity;

                    ?>


                    <div class="order-product">

                        <div>

                            <div class="order-product-name">

                                <?= checkout_clean(
                                    $product['product_name']
                                ) ?>

                            </div>

                            <div class="order-product-quantity">

                                Quantity: <?= $quantity ?>

                            </div>

                            <div class="order-product-quantity">

                                <?= CURRENCY ?>

                                <?= number_format($price, 2) ?>

                                per item

                            </div>

                        </div>


                        <div>

                            <strong>

                                <?= CURRENCY ?>

                                <?= number_format($item_total, 2) ?>

                            </strong>

                        </div>

                    </div>


                <?php endforeach; ?>


                <!-- SUBTOTAL -->

                <div class="order-summary-row">

                    <span>Subtotal</span>

                    <span>

                        <?= CURRENCY ?>

                        <?= number_format($subtotal, 2) ?>

                    </span>

                </div>


                <!-- DELIVERY CHARGE -->

                <div class="order-summary-row">

                    <span>Delivery Charge</span>

                    <span>Free</span>

                </div>


                <!-- TOTAL -->

                <div class="order-summary-row order-total">

                    <span>Total</span>

                    <span>

                        <?= CURRENCY ?>

                        <?= number_format($total_amount, 2) ?>

                    </span>

                </div>


                <!-- SECURE CHECKOUT -->

                <p
                    style="
                        text-align:center;
                        color:#777;
                        margin-top:20px;
                        font-size:14px;
                    "
                >

                    <i class="fas fa-shield-halved"></i>

                    Your order will be processed securely.

                </p>


            </div>


        </div>


    <?php endif; ?>


</div>


<?php

// ==========================================
// 10. INCLUDE FOOTER
// ==========================================

require_once "includes/footer.php";

?>
