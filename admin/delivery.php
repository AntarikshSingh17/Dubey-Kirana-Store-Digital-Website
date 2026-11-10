<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

// ==========================================
// HELPER FUNCTION
// ==========================================

function e($value)
{
    return htmlspecialchars(
        (string)($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

// ==========================================
// CSRF TOKEN
// ==========================================

if (empty($_SESSION["delivery_csrf"])) {
    $_SESSION["delivery_csrf"] = bin2hex(random_bytes(32));
}

$message = "";
$messageType = "success";

// ==========================================
// UPDATE DELIVERY DETAILS
// ==========================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $submittedToken = $_POST["csrf_token"] ?? "";

    if (
        !is_string($submittedToken) ||
        !hash_equals($_SESSION["delivery_csrf"], $submittedToken)
    ) {
        die("Invalid request. Please refresh the page and try again.");
    }

    $orderId = (int)($_POST["order_id"] ?? 0);

    $deliveryPerson = trim($_POST["delivery_person"] ?? "");

    $orderStatus = trim($_POST["order_status"] ?? "");

    $allowedStatuses = [
        "Pending",
        "Processing",
        "Out for Delivery",
        "Delivered"
    ];

    // Validate order ID
    if ($orderId <= 0) {

        $message = "Invalid order ID.";
        $messageType = "error";

    // Validate delivery person
    } elseif (
        $deliveryPerson === "" ||
        strlen($deliveryPerson) > 100
    ) {

        $message = "Please enter a valid delivery person name.";
        $messageType = "error";

    // Validate order status
    } elseif (!in_array($orderStatus, $allowedStatuses, true)) {

        $message = "Invalid delivery status.";
        $messageType = "error";

    } else {

        // Check if order exists and is not cancelled
        $checkOrder = $pdo->prepare("
            SELECT id
            FROM orders
            WHERE id = ?
              AND order_status != 'Cancelled'
        ");

        $checkOrder->execute([$orderId]);

        if (!$checkOrder->fetch()) {

            $message = "Order not found or has been cancelled.";
            $messageType = "error";

        } else {

            // Update delivery information
            $stmt = $pdo->prepare("
                UPDATE orders
                SET delivery_person = ?,
                    order_status = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $deliveryPerson,
                $orderStatus,
                $orderId
            ]);

            $message = "Delivery details updated successfully!";
        }
    }
}

// ==========================================
// FETCH DELIVERY ORDERS
// ==========================================

$stmt = $pdo->query("
    SELECT
        o.id,
        o.order_number,
        o.order_status,
        o.delivery_person,
        o.created_at,
        c.customer_name,
        c.mobile,
        c.address
    FROM orders o
    LEFT JOIN customers c
        ON o.customer_id = c.id
    WHERE o.order_status != 'Cancelled'
    ORDER BY o.created_at DESC
");

$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ==========================================
// DELIVERY STATISTICS
// ==========================================

// Total active orders
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM orders
    WHERE order_status != 'Cancelled'
");

$totalOrders = (int)$stmt->fetchColumn();


// Pending orders
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM orders
    WHERE order_status = 'Pending'
");

$pendingOrders = (int)$stmt->fetchColumn();


// Out for delivery
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM orders
    WHERE order_status = 'Out for Delivery'
");

$outForDelivery = (int)$stmt->fetchColumn();


// Delivered orders
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM orders
    WHERE order_status = 'Delivered'
");

$deliveredOrders = (int)$stmt->fetchColumn();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Delivery Management | Dubey Kirana Store</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            color: #333;
        }

        .container {
            width: 95%;
            max-width: 1400px;
            margin: 30px auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
        }

        h1 {
            color: #166534;
        }

        .back-btn {
            background: #166534;
            color: white;
            padding: 10px 18px;
            text-decoration: none;
            border-radius: 6px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }

        .card {
            background: white;
            padding: 22px;
            border-radius: 10px;
            border-left: 5px solid #166534;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .card h3 {
            color: #777;
            font-size: 15px;
            margin-bottom: 12px;
        }

        .card p {
            font-size: 28px;
            font-weight: bold;
            color: #166534;
        }

        .card.warning {
            border-color: #f59e0b;
        }

        .card.warning p {
            color: #d97706;
        }

        .card.info {
            border-color: #2563eb;
        }

        .card.info p {
            color: #2563eb;
        }

        .card.success {
            border-color: #16a34a;
        }

        .table-box {
            background: white;
            padding: 20px;
            border-radius: 10px;
            overflow-x: auto;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .table-box h2 {
            color: #166534;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            min-width: 1100px;
            border-collapse: collapse;
        }

        th, td {
            padding: 13px;
            text-align: left;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }

        th {
            background: #f0fdf4;
            color: #166534;
        }

        input, select {
            width: 100%;
            padding: 9px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 8px;
        }

        button {
            background: #166534;
            color: white;
            padding: 9px 14px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background: #14532d;
        }

        .status {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .pending {
            background: #fef3c7;
            color: #92400e;
        }

        .processing {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .out-for-delivery {
            background: #ede9fe;
            color: #6d28d9;
        }

        .delivered {
            background: #dcfce7;
            color: #166534;
        }

        .cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        .message {
            padding: 14px;
            margin-bottom: 20px;
            border-radius: 6px;
        }

        .message.success {
            background: #dcfce7;
            color: #166534;
        }

        .message.error {
            background: #fee2e2;
            color: #991b1b;
        }

        @media (max-width: 900px) {

            .stats {
                grid-template-columns: repeat(2, 1fr);
            }

        }

        @media (max-width: 500px) {

            .stats {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>

<div class="container">

    <!-- ========================================== -->
    <!-- HEADER -->
    <!-- ========================================== -->

    <div class="header">

        <h1>🚚 Delivery Management</h1>

        <a href="dashboard.php" class="back-btn">
            ← Dashboard
        </a>

    </div>


    <!-- ========================================== -->
    <!-- MESSAGE -->
    <!-- ========================================== -->

    <?php if ($message !== ""): ?>

        <div class="message <?= e($messageType) ?>">

            <?= e($message) ?>

        </div>

    <?php endif; ?>


    <!-- ========================================== -->
    <!-- DELIVERY STATISTICS -->
    <!-- ========================================== -->

    <div class="stats">

        <div class="card">

            <h3>Total Active Orders</h3>

            <p>
                <?= number_format($totalOrders) ?>
            </p>

        </div>

        <div class="card warning">

            <h3>Pending Orders</h3>

            <p>
                <?= number_format($pendingOrders) ?>
            </p>

        </div>

        <div class="card info">

            <h3>Out for Delivery</h3>

            <p>
                <?= number_format($outForDelivery) ?>
            </p>

        </div>

        <div class="card success">

            <h3>Delivered Orders</h3>

            <p>
                <?= number_format($deliveredOrders) ?>
            </p>

        </div>

    </div>


    <!-- ========================================== -->
    <!-- DELIVERY ORDERS TABLE -->
    <!-- ========================================== -->

    <div class="table-box">

        <h2>📦 Manage Deliveries</h2>

        <table>

            <thead>

                <tr>

                    <th>Order Number</th>

                    <th>Customer</th>

                    <th>Mobile</th>

                    <th>Delivery Address</th>

                    <th>Order Date</th>

                    <th>Delivery Person</th>

                    <th>Status</th>

                    <th>Update</th>

                </tr>

            </thead>

            <tbody>

            <?php if (!empty($orders)): ?>

                <?php foreach ($orders as $order): ?>

                    <?php

                    $statusClass = strtolower(
                        str_replace(
                            " ",
                            "-",
                            $order["order_status"] ?? ""
                        )
                    );

                    $orderDate = !empty($order["created_at"])
                        ? strtotime($order["created_at"])
                        : false;

                    ?>

                    <tr>

                        <td>
                            <?= e($order["order_number"]) ?>
                        </td>

                        <td>
                            <?= e($order["customer_name"] ?? "N/A") ?>
                        </td>

                        <td>
                            <?= e($order["mobile"] ?? "N/A") ?>
                        </td>

                        <td>
                            <?= e($order["address"] ?? "N/A") ?>
                        </td>

                        <td>

                            <?= $orderDate
                                ? date("d M Y", $orderDate)
                                : "N/A" ?>

                        </td>

                        <td>

                            <?= e(
                                $order["delivery_person"] ?? "Not Assigned"
                            ) ?>

                        </td>

                        <td>

                            <span class="status <?= e($statusClass) ?>">

                                <?= e($order["order_status"]) ?>

                            </span>

                        </td>

                        <td>

                            <form method="POST">

                                <!-- CSRF TOKEN -->

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= e($_SESSION["delivery_csrf"]) ?>"
                                >

                                <!-- ORDER ID -->

                                <input
                                    type="hidden"
                                    name="order_id"
                                    value="<?= (int)$order["id"] ?>"
                                >

                                <!-- DELIVERY PERSON -->

                                <input
                                    type="text"
                                    name="delivery_person"
                                    placeholder="Delivery person name"
                                    maxlength="100"
                                    value="<?= e($order["delivery_person"] ?? "") ?>"
                                    required
                                >

                                <!-- ORDER STATUS -->

                                <select name="order_status" required>

                                    <?php

                                    $statuses = [
                                        "Pending",
                                        "Processing",
                                        "Out for Delivery",
                                        "Delivered"
                                    ];

                                    ?>

                                    <?php foreach ($statuses as $status): ?>

                                        <option
                                            value="<?= e($status) ?>"
                                            <?= ($order["order_status"] === $status)
                                                ? "selected"
                                                : "" ?>
                                        >

                                            <?= e($status) ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                                <!-- UPDATE BUTTON -->

                                <button type="submit">
                                    Update
                                </button>

                            </form>

                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>

                    <td colspan="8" style="text-align:center;padding:25px;">

                        No delivery orders found.

                    </td>

                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

</body>

</html>