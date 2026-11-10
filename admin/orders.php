<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

// ==========================================
// GENERATE CSRF TOKEN
// ==========================================

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// ==========================================
// UPDATE ORDER STATUS
// ==========================================

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_status"])) {

    // Validate CSRF token
    $submitted_token = $_POST["csrf_token"] ?? "";

    if (!hash_equals($_SESSION["csrf_token"], $submitted_token)) {
        die("Invalid CSRF token. Request denied.");
    }

    // Validate order ID
    $order_id = filter_input(INPUT_POST, "order_id", FILTER_VALIDATE_INT);

    // Get order status
    $order_status = $_POST["order_status"] ?? "";

    // Allowed order statuses
    $allowed_statuses = [
        "Pending",
        "Confirmed",
        "Packed",
        "Out for Delivery",
        "Delivered",
        "Cancelled"
    ];

    if ($order_id === false || $order_id === null || $order_id <= 0) {

        die("Invalid order ID.");

    }

    if (in_array($order_status, $allowed_statuses, true)) {

        $stmt = $pdo->prepare("
            UPDATE orders
            SET order_status = ?
            WHERE id = ?
        ");

        $stmt->execute([$order_status, $order_id]);

    } else {

        die("Invalid order status.");

    }

    // Redirect after update
    header("Location: orders.php");
    exit;
}

// ==========================================
// FETCH ALL ORDERS
// ==========================================

$stmt = $pdo->query("
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
    ORDER BY o.id DESC
");

$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ==========================================
// STATUS COLORS
// ==========================================

function statusColor($status)
{
    switch ($status) {

        case "Pending":
            return "#f59e0b";

        case "Confirmed":
            return "#3b82f6";

        case "Packed":
            return "#8b5cf6";

        case "Out for Delivery":
            return "#06b6d4";

        case "Delivered":
            return "#16a34a";

        case "Cancelled":
            return "#ef4444";

        default:
            return "#64748b";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Orders | Dubey Kirana Store</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            color: #1f2937;
        }

        .container {
            padding: 30px;
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
            font-size: 28px;
            color: #14532d;
        }

        .back-btn {
            text-decoration: none;
            background: #14532d;
            color: white;
            padding: 10px 18px;
            border-radius: 6px;
        }

        .stats {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            flex: 1;
            min-width: 150px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .stat-card h3 {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 10px;
        }

        .stat-card p {
            font-size: 25px;
            font-weight: bold;
            color: #14532d;
        }

        .table-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            overflow-x: auto;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1100px;
        }

        th {
            background: #14532d;
            color: white;
            padding: 14px;
            text-align: left;
            font-size: 13px;
        }

        td {
            padding: 14px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
            font-size: 13px;
        }

        tr:hover {
            background: #f9fafb;
        }

        .order-number {
            font-weight: bold;
            color: #14532d;
        }

        .status-badge {
            display: inline-block;
            color: white;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            white-space: nowrap;
        }

        .status-form {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .status-form select {
            padding: 8px;
            border: 1px solid #d1d5db;
            border-radius: 5px;
        }

        .update-btn {
            background: #14532d;
            color: white;
            border: none;
            padding: 8px;
            border-radius: 5px;
            cursor: pointer;
        }

        .update-btn:hover {
            background: #166534;
        }

        .details-btn {
            background: #2563eb;
            color: white;
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .items-box {
            margin-top: 10px;
            background: #f8fafc;
            padding: 12px;
            border-radius: 6px;
        }

        .items-box table {
            min-width: 400px;
        }

        .items-box th {
            background: #334155;
        }

        .empty {
            text-align: center;
            padding: 40px;
            color: #64748b;
        }

        @media (max-width: 768px) {

            .container {
                padding: 15px;
            }

            .header h1 {
                font-size: 22px;
            }

        }

    </style>

</head>

<body>

<div class="container">

    <div class="header">

        <h1>📦 Orders Management</h1>

        <a href="dashboard.php" class="back-btn">
            ← Back to Dashboard
        </a>

    </div>

    <div class="stats">

        <div class="stat-card">
            <h3>Total Orders</h3>
            <p><?= count($orders) ?></p>
        </div>

        <div class="stat-card">
            <h3>Pending Orders</h3>
            <p>
                <?= count(array_filter($orders, fn($o) => $o["order_status"] === "Pending")) ?>
            </p>
        </div>

        <div class="stat-card">
            <h3>Delivered Orders</h3>
            <p>
                <?= count(array_filter($orders, fn($o) => $o["order_status"] === "Delivered")) ?>
            </p>
        </div>

        <div class="stat-card">
            <h3>Cancelled Orders</h3>
            <p>
                <?= count(array_filter($orders, fn($o) => $o["order_status"] === "Cancelled")) ?>
            </p>
        </div>

    </div>

    <div class="table-container">

        <h2 style="margin-bottom:20px;">All Customer Orders</h2>

        <?php if (empty($orders)): ?>

            <div class="empty">
                <h3>No Orders Found</h3>
                <p>Customer orders will appear here.</p>
            </div>

        <?php else: ?>

            <table>

                <thead>

                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Contact</th>
                        <th>Delivery Address</th>
                        <th>Amount</th>
                        <th>Payment</th>
                        <th>Order Status</th>
                        <th>Products</th>
                        <th>Date</th>
                    </tr>

                </thead>

                <tbody>

                <?php foreach ($orders as $order): ?>

                    <tr>

                        <td>
                            <span class="order-number">
                                <?= htmlspecialchars($order["order_number"] ?? "") ?>
                            </span>
                        </td>

                        <td>
                            <strong>
                                <?= htmlspecialchars($order["customer_name"] ?? "N/A") ?>
                            </strong>
                        </td>

                        <td>
                            <?= htmlspecialchars($order["mobile"] ?? "N/A") ?><br>
                            <?= htmlspecialchars($order["email"] ?? "") ?>
                        </td>

                        <td>
                            <?= nl2br(htmlspecialchars($order["address"] ?? "N/A")) ?>
                        </td>

                        <td>
                            <strong>
                                ₹<?= number_format((float)$order["total_amount"], 2) ?>
                            </strong>
                        </td>

                        <td>
                            <?= htmlspecialchars($order["payment_method"] ?? "COD") ?><br>

                            <small>
                                <?= htmlspecialchars($order["payment_status"] ?? "Pending") ?>
                            </small>
                        </td>

                        <td>

                            <span class="status-badge"
                                  style="background:<?= statusColor($order["order_status"] ?? "") ?>">

                                <?= htmlspecialchars($order["order_status"] ?? "Pending") ?>

                            </span>

                            <form method="POST" class="status-form" style="margin-top:10px;">

                                <!-- CSRF TOKEN -->
                                <input type="hidden"
                                       name="csrf_token"
                                       value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">

                                <input type="hidden"
                                       name="order_id"
                                       value="<?= (int)$order["id"] ?>">

                                <select name="order_status">

                                    <?php
                                    $statuses = [
                                        "Pending",
                                        "Confirmed",
                                        "Packed",
                                        "Out for Delivery",
                                        "Delivered",
                                        "Cancelled"
                                    ];

                                    foreach ($statuses as $status):
                                    ?>

                                        <option value="<?= htmlspecialchars($status) ?>"
                                            <?= ($order["order_status"] ?? "") === $status ? "selected" : "" ?>>

                                            <?= htmlspecialchars($status) ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                                <button type="submit"
                                        name="update_status"
                                        class="update-btn">

                                    Update Status

                                </button>

                            </form>

                        </td>

                        <td>

                            <button class="details-btn"
                                    type="button"
                                    onclick="toggleItems(<?= (int)$order["id"] ?>)">

                                View Products

                            </button>

                            <div id="items-<?= (int)$order["id"] ?>"
                                 class="items-box"
                                 style="display:none;">

                                <?php

                                $itemStmt = $pdo->prepare("
                                    SELECT product_name, price, quantity, subtotal
                                    FROM order_items
                                    WHERE order_id = ?
                                ");

                                $itemStmt->execute([$order["id"]]);

                                $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

                                ?>

                                <table>

                                    <thead>

                                        <tr>
                                            <th>Product</th>
                                            <th>Price</th>
                                            <th>Qty</th>
                                            <th>Subtotal</th>
                                        </tr>

                                    </thead>

                                    <tbody>

                                    <?php if (empty($items)): ?>

                                        <tr>
                                            <td colspan="4">No products found.</td>
                                        </tr>

                                    <?php else: ?>

                                        <?php foreach ($items as $item): ?>

                                            <tr>

                                                <td>
                                                    <?= htmlspecialchars($item["product_name"] ?? "") ?>
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

                                    <?php endif; ?>

                                    </tbody>

                                </table>

                            </div>

                        </td>

                        <td>

                            <?= !empty($order["created_at"]) ? date("d M Y", strtotime($order["created_at"])) : "N/A" ?><br>

                            <small>
                                <?= !empty($order["created_at"]) ? date("h:i A", strtotime($order["created_at"])) : "" ?>
                            </small>

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        <?php endif; ?>

    </div>

</div>

<script>

function toggleItems(orderId) {

    const box = document.getElementById("items-" + orderId);

    if (box.style.display === "none") {
        box.style.display = "block";
    } else {
        box.style.display = "none";
    }

}

</script>

</body>
</html>