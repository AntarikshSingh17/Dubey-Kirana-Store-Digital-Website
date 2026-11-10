
<?php
require_once __DIR__ . '/includes/header.php';

// Get order number from URL
$orderNumber = trim($_GET['order'] ?? $_GET['order_number'] ?? '');

$order = null;
$orderItems = [];
$errorMessage = '';

if ($orderNumber === '') {
    $errorMessage = "No order number was provided.";
} else {
    try {
        // Fetch order details
        $stmt = $pdo->prepare("
            SELECT
                id,
                order_number,
                total_amount,
                delivery_charge,
                payment_method,
                payment_status,
                order_status,
                created_at
            FROM orders
            WHERE order_number = ?
            LIMIT 1
        ");

        $stmt->execute([$orderNumber]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            // Fetch purchased products
            $itemStmt = $pdo->prepare("
                SELECT
                    product_name,
                    price,
                    quantity,
                    subtotal
                FROM order_items
                WHERE order_id = ?
            ");

            $itemStmt->execute([$order['id']]);
            $orderItems = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $errorMessage = "We could not find an order with this order number.";
        }

    } catch (PDOException $e) {
        $errorMessage = "Something went wrong while retrieving your order.";
    }
}
?>

<section class="section">
    <div class="container">

        <?php if ($order): ?>

            <div style="
                max-width: 850px;
                margin: 20px auto;
                background: #ffffff;
                padding: 35px;
                border-radius: 18px;
                box-shadow: 0 5px 25px rgba(0,0,0,0.08);
            ">

                <!-- Success Icon -->
                <div style="text-align: center; margin-bottom: 25px;">
                    <div style="
                        width: 85px;
                        height: 85px;
                        background: #e8f8ed;
                        color: #28a745;
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        margin: 0 auto 20px;
                        font-size: 40px;
                    ">
                        <i class="fa-solid fa-check"></i>
                    </div>

                    <h1 style="color: #218838; margin-bottom: 10px;">
                        Order Placed Successfully!
                    </h1>

                    <p style="color: #666;">
                        Thank you for shopping with Dubey Kirana Store.
                    </p>
                </div>

                <!-- Order Number -->
                <div style="
                    background: #f5f9f5;
                    padding: 20px;
                    border-radius: 12px;
                    text-align: center;
                    margin-bottom: 25px;
                ">
                    <p style="margin-bottom: 8px; color: #666;">
                        Your Order Number
                    </p>

                    <h2 style="color: #218838; margin: 0;">
                        <?= htmlspecialchars($order['order_number']) ?>
                    </h2>
                </div>

                <!-- Order Details -->
                <h3 style="margin-bottom: 15px;">Order Details</h3>

                <div style="overflow-x: auto;">
                    <table style="
                        width: 100%;
                        border-collapse: collapse;
                        margin-bottom: 25px;
                    ">
                        <thead>
                            <tr style="background: #f1f5f1;">
                                <th style="padding: 12px; text-align: left;">Product</th>
                                <th style="padding: 12px;">Price</th>
                                <th style="padding: 12px;">Quantity</th>
                                <th style="padding: 12px; text-align: right;">Subtotal</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($orderItems as $item): ?>
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding: 12px;">
                                        <?= htmlspecialchars($item['product_name']) ?>
                                    </td>

                                    <td style="padding: 12px; text-align: center;">
                                        ₹<?= number_format((float)$item['price'], 2) ?>
                                    </td>

                                    <td style="padding: 12px; text-align: center;">
                                        <?= (int)$item['quantity'] ?>
                                    </td>

                                    <td style="padding: 12px; text-align: right;">
                                        ₹<?= number_format((float)$item['subtotal'], 2) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Payment Summary -->
                <div style="
                    max-width: 400px;
                    margin-left: auto;
                    background: #f8f9fa;
                    padding: 20px;
                    border-radius: 12px;
                ">

                    <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                        <span>Delivery Charge</span>
                        <strong>
                            ₹<?= number_format((float)$order['delivery_charge'], 2) ?>
                        </strong>
                    </div>

                    <div style="
                        display: flex;
                        justify-content: space-between;
                        border-top: 1px solid #ddd;
                        padding-top: 15px;
                        font-size: 20px;
                    ">
                        <strong>Total Amount</strong>

                        <strong style="color: #218838;">
                            ₹<?= number_format((float)$order['total_amount'], 2) ?>
                        </strong>
                    </div>
                </div>

                <!-- Payment and Status -->
                <div style="
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
                    gap: 15px;
                    margin-top: 25px;
                ">

                    <div style="background: #f5f9f5; padding: 15px; border-radius: 10px;">
                        <p style="color: #666; margin-bottom: 5px;">Payment Method</p>
                        <strong>
                            <?= htmlspecialchars($order['payment_method']) ?>
                        </strong>
                    </div>

                    <div style="background: #f5f9f5; padding: 15px; border-radius: 10px;">
                        <p style="color: #666; margin-bottom: 5px;">Payment Status</p>
                        <strong>
                            <?= htmlspecialchars($order['payment_status']) ?>
                        </strong>
                    </div>

                    <div style="background: #f5f9f5; padding: 15px; border-radius: 10px;">
                        <p style="color: #666; margin-bottom: 5px;">Order Status</p>
                        <strong style="color: #218838;">
                            <?= htmlspecialchars($order['order_status']) ?>
                        </strong>
                    </div>

                </div>

                <!-- Buttons -->
                <div style="
                    display: flex;
                    justify-content: center;
                    flex-wrap: wrap;
                    gap: 15px;
                    margin-top: 30px;
                ">
                    <a href="<?= rtrim(SITE_URL, '/') ?>/index.php"
                       class="btn"
                       style="text-decoration: none;">
                        <i class="fa-solid fa-house"></i>
                        Continue Shopping
                    </a>

                    <a href="<?= rtrim(SITE_URL, '/') ?>/order-tracking.php?order=<?= urlencode($order['order_number']) ?>"
                       class="btn"
                       style="
                           background: #ffffff;
                           color: #218838;
                           border: 1px solid #218838;
                           text-decoration: none;
                       ">
                        <i class="fa-solid fa-truck"></i>
                        Track Order
                    </a>
                </div>

            </div>

        <?php else: ?>

            <div style="
                max-width: 600px;
                margin: 40px auto;
                text-align: center;
                background: #ffffff;
                padding: 35px;
                border-radius: 15px;
                box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            ">
                <i class="fa-solid fa-circle-exclamation"
                   style="font-size: 55px; color: #dc3545; margin-bottom: 20px;">
                </i>

                <h2>Order Not Found</h2>

                <p style="color: #666; margin: 15px 0 25px;">
                    <?= htmlspecialchars($errorMessage) ?>
                </p>

                <a href="<?= rtrim(SITE_URL, '/') ?>/index.php"
                   class="btn"
                   style="text-decoration: none;">
                    Return to Home
                </a>
            </div>

        <?php endif; ?>

    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
