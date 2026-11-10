
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "config/config.php";

// Check customer login
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit;
}

$customer_id = $_SESSION['customer_id'];

// Fetch customer's orders
$stmt = $pdo->prepare("
    SELECT
        id,
        order_number,
        total_amount,
        delivery_charge,
        payment_method,
        payment_status,
        order_status
    FROM orders
    WHERE customer_id = ?
    ORDER BY id DESC
");

$stmt->execute([$customer_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once "includes/header.php";

?>

<style>
.orders-container {
    max-width: 1100px;
    margin: 50px auto;
    padding: 20px;
}

.orders-heading {
    margin-bottom: 30px;
}

.orders-heading h1 {
    font-size: 32px;
    color: #222;
}

.orders-heading p {
    color: #777;
}

.order-card {
    background: #fff;
    border: 1px solid #e5e5e5;
    border-radius: 12px;
    margin-bottom: 25px;
    overflow: hidden;
}

.order-header {
    background: #f7f7f7;
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.order-number {
    font-size: 17px;
    font-weight: 700;
    color: #333;
}

.order-status {
    display: inline-block;
    padding: 7px 13px;
    border-radius: 20px;
    background: #fff3cd;
    color: #856404;
    font-size: 13px;
    font-weight: 600;
}

.order-body {
    padding: 20px;
}

.order-details {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    margin-bottom: 25px;
}

.detail-box {
    background: #fafafa;
    padding: 15px;
    border-radius: 8px;
}

.detail-box span {
    display: block;
    font-size: 13px;
    color: #777;
    margin-bottom: 7px;
}

.detail-box strong {
    color: #222;
    font-size: 16px;
}

.products-heading {
    font-size: 18px;
    margin-bottom: 15px;
}

.products-table {
    width: 100%;
    border-collapse: collapse;
}

.products-table th,
.products-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.products-table th {
    background: #f8f8f8;
    font-size: 14px;
}

.products-table td {
    font-size: 14px;
}

.order-total {
    text-align: right;
    font-size: 20px;
    font-weight: 700;
    margin-top: 20px;
    color: #388e3c;
}

.empty-orders {
    text-align: center;
    background: #fff;
    padding: 50px 20px;
    border: 1px solid #eee;
    border-radius: 12px;
}

.empty-orders h2 {
    margin-bottom: 15px;
}

.empty-orders p {
    color: #777;
    margin-bottom: 25px;
}

.shop-btn {
    display: inline-block;
    background: #388e3c;
    color: white;
    padding: 12px 25px;
    border-radius: 7px;
    text-decoration: none;
    font-weight: 600;
}

.shop-btn:hover {
    background: #2e7d32;
}

@media (max-width: 768px) {
    .order-details {
        grid-template-columns: 1fr;
    }

    .products-table {
        min-width: 550px;
    }

    .table-wrapper {
        overflow-x: auto;
    }

    .orders-container {
        margin: 20px auto;
    }
}
</style>

<div class="orders-container">

    <div class="orders-heading">
        <h1>My Orders</h1>
        <p>View your order history and track your purchases.</p>
    </div>

    <?php if (empty($orders)): ?>

        <div class="empty-orders">

            <h2>No Orders Found</h2>

            <p>You haven't placed any orders yet.</p>

            <a href="products.php" class="shop-btn">
                Start Shopping
            </a>

        </div>

    <?php else: ?>

        <?php foreach ($orders as $order): ?>

            <?php

            // Fetch products for this order
            $item_stmt = $pdo->prepare("
                SELECT
                    product_name,
                    price,
                    quantity,
                    subtotal
                FROM order_items
                WHERE order_id = ?
            ");

            $item_stmt->execute([$order['id']]);

            $items = $item_stmt->fetchAll(PDO::FETCH_ASSOC);

            $status = $order['order_status'] ?? 'Pending';

            ?>

            <div class="order-card">

                <!-- Order Header -->
                <div class="order-header">

                    <div>
                        <div class="order-number">
                            Order #<?php echo htmlspecialchars($order['order_number']); ?>
                        </div>
                    </div>

                    <div class="order-status">
                        <?php echo htmlspecialchars($status); ?>
                    </div>

                </div>

                <!-- Order Body -->
                <div class="order-body">

                    <div class="order-details">

                        <div class="detail-box">

                            <span>Payment Method</span>

                            <strong>
                                <?php echo htmlspecialchars($order['payment_method'] ?? 'N/A'); ?>
                            </strong>

                        </div>

                        <div class="detail-box">

                            <span>Payment Status</span>

                            <strong>
                                <?php echo htmlspecialchars($order['payment_status'] ?? 'Pending'); ?>
                            </strong>

                        </div>

                        <div class="detail-box">

                            <span>Delivery Charge</span>

                            <strong>
                                ₹<?php echo number_format((float)$order['delivery_charge'], 2); ?>
                            </strong>

                        </div>

                    </div>

                    <h3 class="products-heading">
                        Ordered Products
                    </h3>

                    <div class="table-wrapper">

                        <table class="products-table">

                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($items as $item): ?>

                                    <tr>

                                        <td>
                                            <?php echo htmlspecialchars($item['product_name']); ?>
                                        </td>

                                        <td>
                                            ₹<?php echo number_format((float)$item['price'], 2); ?>
                                        </td>

                                        <td>
                                            <?php echo (int)$item['quantity']; ?>
                                        </td>

                                        <td>
                                            ₹<?php echo number_format((float)$item['subtotal'], 2); ?>
                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                    <div class="order-total">

                        Total Amount:
                        ₹<?php echo number_format((float)$order['total_amount'], 2); ?>

                    </div>

                </div>

            </div>

        <?php endforeach; ?>

    <?php endif; ?>

</div>

<?php require_once "includes/footer.php"; ?>