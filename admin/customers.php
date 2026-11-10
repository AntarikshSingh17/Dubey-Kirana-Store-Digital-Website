<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

// Search customers
$search = trim($_GET["search"] ?? "");

$sql = "
    SELECT
        c.id,
        c.customer_name,
        c.mobile,
        c.email,
        c.address,
        c.created_at,
        COUNT(o.id) AS total_orders,
        COALESCE(SUM(
            CASE
                WHEN o.order_status != 'Cancelled'
                THEN o.total_amount
                ELSE 0
            END
        ), 0) AS total_spent
    FROM customers c
    LEFT JOIN orders o ON c.id = o.customer_id
";

if ($search !== "") {
    $sql .= "
        WHERE c.customer_name LIKE ?
        OR c.mobile LIKE ?
        OR c.email LIKE ?
    ";
}

$sql .= "
    GROUP BY
        c.id,
        c.customer_name,
        c.mobile,
        c.email,
        c.address,
        c.created_at
    ORDER BY c.id DESC
";

$stmt = $pdo->prepare($sql);

if ($search !== "") {
    $searchTerm = "%" . $search . "%";
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
} else {
    $stmt->execute();
}

$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total customers
$totalCustomers = $pdo->query("
    SELECT COUNT(*) FROM customers
")->fetchColumn();

// Total orders
$totalOrders = $pdo->query("
    SELECT COUNT(*) FROM orders
")->fetchColumn();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Customer Management | Dubey Kirana Store</title>

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
            color: #14532d;
            font-size: 28px;
        }

        .back-btn {
            background: #14532d;
            color: white;
            text-decoration: none;
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
            min-width: 180px;
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
            padding: 20px;
            border-radius: 10px;
            overflow-x: auto;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .search-form {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .search-form input {
            flex: 1;
            min-width: 200px;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
        }

        .search-btn {
            background: #16a34a;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            cursor: pointer;
        }

        .reset-btn {
            background: #64748b;
            color: white;
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 6px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }

        th {
            background: #14532d;
            color: white;
            padding: 14px;
            text-align: left;
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

        .customer-name {
            font-weight: bold;
            color: #14532d;
        }

        .empty {
            text-align: center;
            padding: 40px;
            color: #64748b;
        }

        @media(max-width: 768px) {

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

        <h1>👥 Customer Management</h1>

        <a href="dashboard.php" class="back-btn">
            ← Back to Dashboard
        </a>

    </div>

    <div class="stats">

        <div class="stat-card">
            <h3>Total Customers</h3>
            <p><?= (int)$totalCustomers ?></p>
        </div>

        <div class="stat-card">
            <h3>Total Orders</h3>
            <p><?= (int)$totalOrders ?></p>
        </div>

        <div class="stat-card">
            <h3>Search Results</h3>
            <p><?= count($customers) ?></p>
        </div>

    </div>

    <div class="table-container">

        <h2 style="margin-bottom:20px;">All Customers</h2>

        <form method="GET" class="search-form">

            <input type="text"
                   name="search"
                   placeholder="Search by customer name, mobile or email..."
                   value="<?= htmlspecialchars($search) ?>">

            <button type="submit" class="search-btn">
                🔍 Search
            </button>

            <a href="customers.php" class="reset-btn">
                Reset
            </a>

        </form>

        <?php if (empty($customers)): ?>

            <div class="empty">

                <h3>No Customers Found</h3>

                <p>Customers will appear here after placing orders.</p>

            </div>

        <?php else: ?>

            <table>

                <thead>

                    <tr>
                        <th>ID</th>
                        <th>Customer Name</th>
                        <th>Mobile</th>
                        <th>Email</th>
                        <th>Address</th>
                        <th>Total Orders</th>
                        <th>Total Spent</th>
                        <th>Registered Date</th>
                    </tr>

                </thead>

                <tbody>

                <?php foreach ($customers as $customer): ?>

                    <tr>

                        <td>
                            <?= (int)$customer["id"] ?>
                        </td>

                        <td>
                            <span class="customer-name">
                                <?= htmlspecialchars($customer["customer_name"]) ?>
                            </span>
                        </td>

                        <td>
                            <?= htmlspecialchars($customer["mobile"]) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($customer["email"] ?? "Not Provided") ?>
                        </td>

                        <td>
                            <?= nl2br(htmlspecialchars($customer["address"] ?? "N/A")) ?>
                        </td>

                        <td>
                            <?= (int)$customer["total_orders"] ?>
                        </td>

                        <td>
                            <strong>
                                ₹<?= number_format((float)$customer["total_spent"], 2) ?>
                            </strong>
                        </td>

                        <td>
                            <?= date("d M Y", strtotime($customer["created_at"])) ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        <?php endif; ?>

    </div>

</div>

</body>
</html>