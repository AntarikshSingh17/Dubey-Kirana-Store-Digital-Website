<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

// Escape output safely
function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Validate date format
function isValidDate($date)
{
    $d = DateTime::createFromFormat('!Y-m-d', $date);

    return $d && $d->format('Y-m-d') === $date;
}

// Default date filters
$from_date = $_GET['from_date'] ?? date('Y-m-01');
$to_date = $_GET['to_date'] ?? date('Y-m-d');

// Validate date inputs
if (!isValidDate($from_date)) {
    $from_date = date('Y-m-01');
}

if (!isValidDate($to_date)) {
    $to_date = date('Y-m-d');
}

// Ensure From Date is not greater than To Date
if ($from_date > $to_date) {
    $temp = $from_date;
    $from_date = $to_date;
    $to_date = $temp;
}

// Total Revenue
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(total_amount), 0)
    FROM orders
    WHERE order_status != 'Cancelled'
    AND DATE(created_at) BETWEEN ? AND ?
");

$stmt->execute([$from_date, $to_date]);

$total_revenue = $stmt->fetchColumn();


// Total Orders
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM orders
    WHERE DATE(created_at) BETWEEN ? AND ?
");

$stmt->execute([$from_date, $to_date]);

$total_orders = $stmt->fetchColumn();


// Completed Orders
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM orders
    WHERE order_status = 'Delivered'
    AND DATE(created_at) BETWEEN ? AND ?
");

$stmt->execute([$from_date, $to_date]);

$completed_orders = $stmt->fetchColumn();


// Pending Orders
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM orders
    WHERE order_status = 'Pending'
    AND DATE(created_at) BETWEEN ? AND ?
");

$stmt->execute([$from_date, $to_date]);

$pending_orders = $stmt->fetchColumn();


// Best Selling Products
$stmt = $pdo->prepare("
    SELECT
        oi.product_name,
        SUM(oi.quantity) AS total_quantity,
        SUM(oi.subtotal) AS total_sales
    FROM order_items oi
    INNER JOIN orders o ON oi.order_id = o.id
    WHERE o.order_status != 'Cancelled'
    AND DATE(o.created_at) BETWEEN ? AND ?
    GROUP BY oi.product_name
    ORDER BY total_quantity DESC
    LIMIT 10
");

$stmt->execute([$from_date, $to_date]);

$best_selling_products = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Recent Orders Report
$stmt = $pdo->prepare("
    SELECT
        order_number,
        total_amount,
        payment_method,
        payment_status,
        order_status,
        created_at
    FROM orders
    WHERE DATE(created_at) BETWEEN ? AND ?
    ORDER BY created_at DESC
");

$stmt->execute([$from_date, $to_date]);

$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Status class mapping
function getStatusClass($status)
{
    $status = strtolower(trim($status));

    switch ($status) {
        case 'delivered':
            return 'delivered';

        case 'pending':
            return 'pending';

        case 'cancelled':
            return 'cancelled';

        default:
            return 'processing';
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Sales Reports | Dubey Kirana Store</title>

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
            max-width: 1300px;
            margin: 30px auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .header h1 {
            color: #166534;
        }

        .header a {
            text-decoration: none;
            background: #166534;
            color: white;
            padding: 10px 18px;
            border-radius: 6px;
        }

        .header a:hover {
            background: #14532d;
        }

        .filter-box {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .filter-box form {
            display: flex;
            align-items: end;
            gap: 15px;
            flex-wrap: wrap;
        }

        .filter-box label {
            display: block;
            margin-bottom: 7px;
            font-weight: bold;
        }

        .filter-box input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .filter-box button {
            padding: 10px 20px;
            background: #166534;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .filter-box button:hover {
            background: #14532d;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border-left: 5px solid #166534;
        }

        .stat-card h3 {
            color: #777;
            font-size: 15px;
            margin-bottom: 12px;
        }

        .stat-card p {
            font-size: 27px;
            font-weight: bold;
            color: #166534;
        }

        .section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            overflow-x: auto;
        }

        .section h2 {
            margin-bottom: 20px;
            color: #166534;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 650px;
        }

        th,
        td {
            padding: 13px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #f0fdf4;
            color: #166534;
        }

        .status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }

        .delivered {
            background: #dcfce7;
            color: #166534;
        }

        .pending {
            background: #fef3c7;
            color: #92400e;
        }

        .cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        .processing {
            background: #dbeafe;
            color: #1e40af;
        }

        .empty {
            text-align: center;
            color: #777;
            padding: 25px;
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

            .header h1 {
                font-size: 23px;
            }

            .stat-card p {
                font-size: 23px;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <!-- Header -->
    <div class="header">

        <h1>📊 Sales Reports</h1>

        <a href="dashboard.php">
            ← Back to Dashboard
        </a>

    </div>


    <!-- Date Filter -->
    <div class="filter-box">

        <form method="GET">

            <div>
                <label for="from_date">From Date</label>

                <input
                    type="date"
                    id="from_date"
                    name="from_date"
                    value="<?= e($from_date) ?>"
                    required
                >
            </div>

            <div>
                <label for="to_date">To Date</label>

                <input
                    type="date"
                    id="to_date"
                    name="to_date"
                    value="<?= e($to_date) ?>"
                    required
                >
            </div>

            <button type="submit">
                Generate Report
            </button>

        </form>

    </div>


    <!-- Statistics -->
    <div class="stats">

        <div class="stat-card">

            <h3>💰 Total Revenue</h3>

            <p>
                ₹<?= number_format((float)$total_revenue, 2) ?>
            </p>

        </div>


        <div class="stat-card">

            <h3>📦 Total Orders</h3>

            <p>
                <?= (int)$total_orders ?>
            </p>

        </div>


        <div class="stat-card">

            <h3>✅ Completed Orders</h3>

            <p>
                <?= (int)$completed_orders ?>
            </p>

        </div>


        <div class="stat-card">

            <h3>⏳ Pending Orders</h3>

            <p>
                <?= (int)$pending_orders ?>
            </p>

        </div>

    </div>


    <!-- Best Selling Products -->
    <div class="section">

        <h2>🏆 Top 10 Best-Selling Products</h2>

        <table>

            <thead>

                <tr>
                    <th>Product Name</th>
                    <th>Quantity Sold</th>
                    <th>Total Sales</th>
                </tr>

            </thead>

            <tbody>

                <?php if (!empty($best_selling_products)): ?>

                    <?php foreach ($best_selling_products as $product): ?>

                        <tr>

                            <td>
                                <?= e($product['product_name']) ?>
                            </td>

                            <td>
                                <?= (int)$product['total_quantity'] ?>
                            </td>

                            <td>
                                ₹<?= number_format((float)$product['total_sales'], 2) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="3" class="empty">
                            No product sales found for this period.
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>


    <!-- Orders Report -->
    <div class="section">

        <h2>📋 Orders Report</h2>

        <table>

            <thead>

                <tr>
                    <th>Order Number</th>
                    <th>Order Date</th>
                    <th>Total Amount</th>
                    <th>Payment Method</th>
                    <th>Payment Status</th>
                    <th>Order Status</th>
                </tr>

            </thead>

            <tbody>

                <?php if (!empty($orders)): ?>

                    <?php foreach ($orders as $order): ?>

                        <tr>

                            <td>
                                <?= e($order['order_number']) ?>
                            </td>

                            <td>
                                <?= e(date('d M Y', strtotime($order['created_at']))) ?>
                            </td>

                            <td>
                                ₹<?= number_format((float)$order['total_amount'], 2) ?>
                            </td>

                            <td>
                                <?= e($order['payment_method']) ?>
                            </td>

                            <td>
                                <?= e($order['payment_status']) ?>
                            </td>

                            <td>

                                <span class="status <?= e(getStatusClass($order['order_status'])) ?>">
                                    <?= e($order['order_status']) ?>
                                </span>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="6" class="empty">
                            No orders found for this period.
                        </td>

                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

</body>

</html>