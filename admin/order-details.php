<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

// Escape output safely
function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Get and validate order ID
$order_id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$order_id || $order_id <= 0) {
    http_response_code(400);
    die("Invalid Order ID.");
}


// Fetch order and customer details
$orderStmt = $pdo->prepare("
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
        c.mobile,
        c.email,
        c.address
    FROM orders o
    LEFT JOIN customers c ON o.customer_id = c.id
    WHERE o.id = ?
");

$orderStmt->execute([$order_id]);

$order = $orderStmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    http_response_code(404);
    die("Order not found.");
}


// Fetch ordered products
$itemsStmt = $pdo->prepare("
    SELECT
        id,
        order_id,
        product_id,
        product_name,
        price,
        quantity,
        subtotal
    FROM order_items
    WHERE order_id = ?
");

$itemsStmt->execute([$order_id]);

$items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);


// Calculate subtotal
$subtotal = 0;

foreach ($items as $item) {
    $subtotal += (float)$item["subtotal"];
}


// Status class mapping
function getOrderStatusClass($status)
{
    $status = strtolower(trim($status));

    switch ($status) {

        case 'pending':
            return 'pending';

        case 'confirmed':
            return 'confirmed';

        case 'packed':
            return 'packed';

        case 'out for delivery':
            return 'out-for-delivery';

        case 'delivered':
            return 'delivered';

        case 'cancelled':
            return 'cancelled';

        default:
            return 'processing';
    }
}

$statusClass = getOrderStatusClass($order["order_status"]);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Order Details | Dubey Kirana Store</title>

    <link rel="stylesheet" href="../assets/css/admin.css">

    <style>

        .details-container {
            margin-top: 25px;
        }

        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 25px;
        }

        .details-card {
            background: #ffffff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .details-card h2 {
            font-size: 20px;
            margin-bottom: 20px;
            color: #198754;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            color: #666;
            font-weight: 600;
            flex-shrink: 0;
        }

        .detail-value {
            color: #333;
            text-align: right;
            overflow-wrap: anywhere;
        }

        .status-badge {
            padding: 7px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: capitalize;
            display: inline-block;
        }

        .pending {
            background: #fff3cd;
            color: #856404;
        }

        .confirmed {
            background: #cfe2ff;
            color: #084298;
        }

        .packed {
            background: #e2d9f3;
            color: #432874;
        }

        .out-for-delivery {
            background: #d1ecf1;
            color: #0c5460;
        }

        .delivered {
            background: #d1e7dd;
            color: #0f5132;
        }

        .cancelled {
            background: #f8d7da;
            color: #842029;
        }

        .processing {
            background: #dbeafe;
            color: #1e40af;
        }

        .order-total {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px solid #eee;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 10px 0;
        }

        .grand-total {
            font-size: 20px;
            font-weight: bold;
            color: #198754;
        }

        .back-btn {
            display: inline-block;
            background: #198754;
            color: white;
            padding: 11px 20px;
            border-radius: 8px;
            text-decoration: none;
            margin-bottom: 20px;
        }

        .back-btn:hover {
            background: #146c43;
        }

        @media (max-width: 768px) {

            .details-grid {
                grid-template-columns: 1fr;
            }

            .details-card {
                padding: 18px;
            }

        }

    </style>

</head>

<body>

<div class="admin-container">

    <!-- Sidebar -->
    <aside class="sidebar">

        <h2>Dubey Kirana</h2>

        <nav>

            <a href="dashboard.php">Dashboard</a>

            <a href="products.php">Products</a>

            <a href="orders.php" class="active">Orders</a>

            <a href="inventory.php">Inventory</a>

            <a href="customers.php">Customers</a>

            <a href="logout.php">Logout</a>

        </nav>

    </aside>


    <!-- Main Content -->
    <main class="main-content">

        <header class="topbar">

            <h1>Order Details</h1>

            <span>Admin Panel</span>

        </header>


        <div class="details-container">

            <a href="orders.php" class="back-btn">
                ← Back to Orders
            </a>


            <!-- Order and Customer Information -->
            <div class="details-grid">


                <!-- Order Information -->
                <div class="details-card">

                    <h2>Order Information</h2>

                    <div class="detail-row">

                        <span class="detail-label">
                            Order Number
                        </span>

                        <span class="detail-value">
                            #<?= e($order["order_number"]) ?>
                        </span>

                    </div>


                    <div class="detail-row">

                        <span class="detail-label">
                            Order Date
                        </span>

                        <span class="detail-value">
                            <?= e(date('d M Y, h:i A', strtotime($order["created_at"]))) ?>
                        </span>

                    </div>


                    <div class="detail-row">

                        <span class="detail-label">
                            Order Status
                        </span>

                        <span class="detail-value">

                            <span class="status-badge <?= e($statusClass) ?>">
                                <?= e($order["order_status"]) ?>
                            </span>

                        </span>

                    </div>


                    <div class="detail-row">

                        <span class="detail-label">
                            Payment Method
                        </span>

                        <span class="detail-value">
                            <?= e($order["payment_method"] ?? "N/A") ?>
                        </span>

                    </div>


                    <div class="detail-row">

                        <span class="detail-label">
                            Payment Status
                        </span>

                        <span class="detail-value">
                            <?= e($order["payment_status"] ?? "N/A") ?>
                        </span>

                    </div>

                </div>


                <!-- Customer Information -->
                <div class="details-card">

                    <h2>Customer Information</h2>

                    <div class="detail-row">

                        <span class="detail-label">
                            Customer Name
                        </span>

                        <span class="detail-value">
                            <?= e($order["customer_name"] ?? "Unknown") ?>
                        </span>

                    </div>


                    <div class="detail-row">

                        <span class="detail-label">
                            Mobile Number
                        </span>

                        <span class="detail-value">
                            <?= e($order["mobile"] ?? "N/A") ?>
                        </span>

                    </div>


                    <div class="detail-row">

                        <span class="detail-label">
                            Email
                        </span>

                        <span class="detail-value">
                            <?= e($order["email"] ?? "N/A") ?>
                        </span>

                    </div>


                    <div class="detail-row">

                        <span class="detail-label">
                            Delivery Address
                        </span>

                        <span class="detail-value">
                            <?= nl2br(e($order["address"] ?? "N/A")) ?>
                        </span>

                    </div>

                </div>

            </div>


            <!-- Ordered Products -->
            <div class="details-card">

                <h2>Ordered Products</h2>

                <div class="table-container">

                    <table class="orders-table">

                        <thead>

                            <tr>

                                <th>Product Name</th>

                                <th>Price</th>

                                <th>Quantity</th>

                                <th>Subtotal</th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php if (!empty($items)): ?>

                                <?php foreach ($items as $item): ?>

                                    <tr>

                                        <td>
                                            <?= e($item["product_name"]) ?>
                                        </td>

                                        <td>
                                            ₹<?= number_format((float)$item["price"], 2) ?>
                                        </td>

                                        <td>
                                            <?= (int)$item["quantity"] ?>
                                        </td>

                                        <td>
                                            ₹<?= number_format((float)$item["subtotal"], 2) ?>
                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr>

                                    <td colspan="4" style="text-align:center;">
                                        No products found for this order.
                                    </td>

                                </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>


                <!-- Order Total -->
                <div class="order-total">

                    <div class="total-row">

                        <span>Items Subtotal</span>

                        <span>
                            ₹<?= number_format($subtotal, 2) ?>
                        </span>

                    </div>


                    <div class="total-row">

                        <span>Delivery Charge</span>

                        <span>
                            ₹<?= number_format((float)$order["delivery_charge"], 2) ?>
                        </span>

                    </div>


                    <div class="total-row grand-total">

                        <span>Grand Total</span>

                        <span>
                            ₹<?= number_format((float)$order["total_amount"], 2) ?>
                        </span>

                    </div>

                </div>

            </div>

        </div>

    </main>

</div>

</body>

</html>