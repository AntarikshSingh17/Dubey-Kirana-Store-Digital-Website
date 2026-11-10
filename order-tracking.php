<?php

require_once __DIR__ . '/includes/header.php';

// ==========================================
// 1. INITIALIZE VARIABLES
// ==========================================

$orderNumber = '';
$mobile = '';

$order = null;
$orderItems = [];

$errorMessage = '';


// ==========================================
// 2. HELPER FUNCTION
// ==========================================

function tracking_escape($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}


// ==========================================
// 3. PROCESS ORDER TRACKING FORM
// ==========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $orderNumber = trim($_POST['order_number'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');

    if ($orderNumber === '' || $mobile === '') {

        $errorMessage = "Please enter your order number and mobile number.";

    } elseif (!preg_match('/^[0-9]{10}$/', $mobile)) {

        $errorMessage = "Please enter a valid 10-digit mobile number.";

    } else {

        try {

            // ==========================================
            // 4. VERIFY ORDER NUMBER AND MOBILE NUMBER
            // ==========================================

            $stmt = $pdo->prepare("
                SELECT
                    o.id,
                    o.order_number,
                    o.customer_id,
                    o.total_amount,
                    o.delivery_charge,
                    o.payment_method,
                    o.payment_status,
                    o.order_status,
                    o.created_at,
                    c.customer_name,
                    c.mobile
                FROM orders o
                INNER JOIN customers c
                    ON o.customer_id = c.id
                WHERE o.order_number = :order_number
                  AND c.mobile = :mobile
                LIMIT 1
            ");

            $stmt->execute([
                ':order_number' => $orderNumber,
                ':mobile' => $mobile
            ]);

            $order = $stmt->fetch(PDO::FETCH_ASSOC);


            // ==========================================
            // 5. FETCH ORDER ITEMS
            // ==========================================

            if ($order) {

                $itemsStmt = $pdo->prepare("
                    SELECT
                        product_name,
                        quantity,
                        price,
                        subtotal
                    FROM order_items
                    WHERE order_id = :order_id
                ");

                $itemsStmt->execute([
                    ':order_id' => $order['id']
                ]);

                $orderItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

            } else {

                $errorMessage = "No order found. Please check your order number and mobile number.";

            }

        } catch (PDOException $e) {

            $errorMessage = "Something went wrong while tracking your order. Please try again.";

        }
    }
}


// ==========================================
// 6. ORDER STATUS PROCESSING
// ==========================================

$status = strtolower(trim($order['order_status'] ?? 'pending'));

$statusMap = [
    'pending'          => 0,
    'confirmed'        => 1,
    'packed'           => 2,
    'processing'       => 2,
    'shipped'          => 3,
    'out for delivery' => 3,
    'delivered'        => 4,
    'completed'        => 4,
    'complete'         => 4
];

$statusLabels = [
    'Pending',
    'Confirmed',
    'Packed',
    'Out for Delivery',
    'Delivered'
];

$currentStep = $statusMap[$status] ?? 0;

$isCancelled = ($status === 'cancelled');

?>

<style>
/* ==========================================
   ORDER TRACKING PAGE
========================================== */

.tracking-page {
    padding: 50px 0;
    background: #f8fafc;
    min-height: 70vh;
}

.tracking-container {
    max-width: 850px;
    margin: auto;
    padding: 0 15px;
}

.tracking-heading {
    text-align: center;
    margin-bottom: 35px;
}

.tracking-heading h1 {
    font-size: 32px;
    color: #1f2937;
    margin-bottom: 10px;
}

.tracking-heading p {
    color: #6b7280;
    font-size: 15px;
}

.tracking-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 30px;
    margin-bottom: 25px;
    box-shadow: 0 5px 25px rgba(0, 0, 0, 0.05);
}

.tracking-card h2 {
    font-size: 22px;
    color: #1f2937;
    margin-bottom: 20px;
}

.tracking-form {
    display: grid;
    gap: 18px;
}

.tracking-form .form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.tracking-form label {
    font-size: 14px;
    font-weight: 600;
    color: #374151;
}

.tracking-form input {
    width: 100%;
    padding: 13px 15px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 15px;
    outline: none;
    transition: border-color 0.2s;
}

.tracking-form input:focus {
    border-color: #16a34a;
}

.tracking-btn {
    display: inline-block;
    padding: 13px 20px;
    background: #16a34a;
    color: #ffffff;
    border: none;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}

.tracking-btn:hover {
    background: #15803d;
}

.tracking-error {
    padding: 14px 16px;
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 8px;
    color: #b91c1c;
    margin-bottom: 20px;
    font-size: 14px;
}

/* ORDER DETAILS */

.order-details-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 25px;
}

.order-number {
    color: #16a34a;
    font-weight: 700;
    font-size: 17px;
}

.order-date {
    color: #6b7280;
    font-size: 13px;
    margin-top: 5px;
}

.order-status-badge {
    display: inline-block;
    padding: 8px 14px;
    border-radius: 30px;
    background: #dcfce7;
    color: #166534;
    font-size: 13px;
    font-weight: 700;
}

.order-status-badge.cancelled {
    background: #fee2e2;
    color: #991b1b;
}

/* TRACKING STEPS */

.tracking-progress {
    display: flex;
    justify-content: space-between;
    position: relative;
    margin: 35px 0;
}

.tracking-progress::before {
    content: "";
    position: absolute;
    top: 17px;
    left: 8%;
    right: 8%;
    height: 4px;
    background: #e5e7eb;
    z-index: 0;
}

.tracking-step {
    position: relative;
    z-index: 1;
    text-align: center;
    width: 20%;
}

.tracking-step .step-circle {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #e5e7eb;
    color: #6b7280;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 10px;
    font-weight: 700;
    font-size: 14px;
}

.tracking-step.completed .step-circle {
    background: #16a34a;
    color: #ffffff;
}

.tracking-step.active .step-circle {
    background: #f59e0b;
    color: #ffffff;
}

.tracking-step .step-label {
    font-size: 12px;
    color: #6b7280;
    line-height: 1.4;
}

.tracking-step.completed .step-label,
.tracking-step.active .step-label {
    color: #1f2937;
    font-weight: 600;
}

/* ORDER ITEMS */

.order-items-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

.order-items-table th,
.order-items-table td {
    padding: 13px 10px;
    border-bottom: 1px solid #e5e7eb;
    text-align: left;
    font-size: 14px;
}

.order-items-table th {
    color: #6b7280;
    font-weight: 600;
    background: #f9fafb;
}

.order-items-table td:last-child,
.order-items-table th:last-child {
    text-align: right;
}

.order-total-box {
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #e5e7eb;
}

.total-row {
    display: flex;
    justify-content: space-between;
    padding: 7px 0;
    color: #4b5563;
    font-size: 14px;
}

.total-row.grand-total {
    font-size: 20px;
    font-weight: 700;
    color: #16a34a;
    margin-top: 8px;
}

.order-info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    margin-top: 20px;
}

.order-info-item {
    padding: 15px;
    background: #f9fafb;
    border-radius: 8px;
}

.order-info-item small {
    display: block;
    color: #6b7280;
    margin-bottom: 5px;
}

.order-info-item strong {
    color: #1f2937;
    font-size: 14px;
}

.tracking-actions {
    text-align: center;
    margin-top: 25px;
}

.tracking-actions a {
    display: inline-block;
    text-decoration: none;
    padding: 12px 20px;
    background: #16a34a;
    color: white;
    border-radius: 8px;
    font-weight: 600;
}

.tracking-actions a:hover {
    background: #15803d;
}

/* RESPONSIVE */

@media (max-width: 600px) {

    .tracking-page {
        padding: 30px 0;
    }

    .tracking-heading h1 {
        font-size: 26px;
    }

    .tracking-card {
        padding: 20px 15px;
    }

    .tracking-progress {
        margin: 25px 0;
    }

    .tracking-step .step-circle {
        width: 30px;
        height: 30px;
        font-size: 12px;
    }

    .tracking-progress::before {
        top: 14px;
    }

    .tracking-step .step-label {
        font-size: 10px;
    }

    .order-info-grid {
        grid-template-columns: 1fr;
    }

    .order-items-table th,
    .order-items-table td {
        padding: 10px 5px;
        font-size: 12px;
    }
}
</style>


<section class="tracking-page">

    <div class="tracking-container">

        <div class="tracking-heading">
            <h1>Track Your Order</h1>
            <p>Enter your order number and mobile number to check your order status.</p>
        </div>


        <!-- ORDER TRACKING FORM -->

        <div class="tracking-card">

            <h2>Find Your Order</h2>

            <?php if ($errorMessage !== ''): ?>

                <div class="tracking-error">
                    <?php echo tracking_escape($errorMessage); ?>
                </div>

            <?php endif; ?>

            <form method="POST" action="order-tracking.php" class="tracking-form">

                <div class="form-group">

                    <label for="order_number">
                        Order Number
                    </label>

                    <input
                        type="text"
                        id="order_number"
                        name="order_number"
                        placeholder="Example: DKS202611100001"
                        value="<?php echo tracking_escape($orderNumber); ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="mobile">
                        Registered Mobile Number
                    </label>

                    <input
                        type="tel"
                        id="mobile"
                        name="mobile"
                        placeholder="Enter your 10-digit mobile number"
                        value="<?php echo tracking_escape($mobile); ?>"
                        maxlength="10"
                        pattern="[0-9]{10}"
                        required
                    >

                </div>


                <button type="submit" class="tracking-btn">
                    Track Order
                </button>

            </form>

        </div>


        <!-- ORDER DETAILS -->

        <?php if ($order): ?>

            <div class="tracking-card">

                <div class="order-details-header">

                    <div>

                        <div class="order-number">
                            Order #<?php echo tracking_escape($order['order_number']); ?>
                        </div>

                        <div class="order-date">
                            Placed on
                            <?php
                            echo date(
                                'd M Y, h:i A',
                                strtotime($order['created_at'])
                            );
                            ?>
                        </div>

                    </div>


                    <span class="order-status-badge <?php echo $isCancelled ? 'cancelled' : ''; ?>">

                        <?php
                        echo tracking_escape(
                            ucwords(strtolower($order['order_status']))
                        );
                        ?>

                    </span>

                </div>


                <!-- TRACKING PROGRESS -->

                <?php if (!$isCancelled): ?>

                    <h2>Order Progress</h2>

                    <div class="tracking-progress">

                        <?php foreach ($statusLabels as $index => $label): ?>

                            <?php
                            $stepClass = '';

                            if ($index < $currentStep) {
                                $stepClass = 'completed';
                            } elseif ($index === $currentStep) {
                                $stepClass = 'active';
                            }
                            ?>

                            <div class="tracking-step <?php echo $stepClass; ?>">

                                <div class="step-circle">

                                    <?php
                                    echo ($index < $currentStep)
                                        ? '&#10003;'
                                        : ($index + 1);
                                    ?>

                                </div>

                                <div class="step-label">
                                    <?php echo tracking_escape($label); ?>
                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <div class="tracking-error">
                        This order has been cancelled.
                    </div>

                <?php endif; ?>


                <!-- ORDER ITEMS -->

                <h2>Ordered Products</h2>

                <div style="overflow-x:auto;">

                    <table class="order-items-table">

                        <thead>

                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach ($orderItems as $item): ?>

                                <tr>

                                    <td>
                                        <?php
                                        echo tracking_escape(
                                            $item['product_name']
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        ₹<?php
                                        echo number_format(
                                            (float)$item['price'],
                                            2
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo (int)$item['quantity'];
                                        ?>
                                    </td>

                                    <td>
                                        ₹<?php
                                        echo number_format(
                                            (float)$item['subtotal'],
                                            2
                                        );
                                        ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>


                <!-- ORDER TOTAL -->

                <div class="order-total-box">

                    <div class="total-row">

                        <span>Delivery Charge</span>

                        <span>
                            ₹<?php
                            echo number_format(
                                (float)$order['delivery_charge'],
                                2
                            );
                            ?>
                        </span>

                    </div>


                    <div class="total-row grand-total">

                        <span>Total Amount</span>

                        <span>
                            ₹<?php
                            echo number_format(
                                (float)$order['total_amount'],
                                2
                            );
                            ?>
                        </span>

                    </div>

                </div>


                <!-- PAYMENT INFORMATION -->

                <div class="order-info-grid">

                    <div class="order-info-item">

                        <small>Payment Method</small>

                        <strong>
                            <?php
                            echo tracking_escape(
                                $order['payment_method']
                            );
                            ?>
                        </strong>

                    </div>


                    <div class="order-info-item">

                        <small>Payment Status</small>

                        <strong>
                            <?php
                            echo tracking_escape(
                                $order['payment_status']
                            );
                            ?>
                        </strong>

                    </div>

                </div>


                <!-- ACTION BUTTON -->

                <div class="tracking-actions">

                    <a href="products.php">
                        Continue Shopping
                    </a>

                </div>

            </div>

        <?php endif; ?>

    </div>

</section>


<?php require_once __DIR__ . '/includes/footer.php'; ?>