<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

// ==========================================
// TOTAL PRODUCTS
// ==========================================

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM products
");

$totalProducts = (int)$stmt->fetchColumn();


// ==========================================
// TOTAL ORDERS
// ==========================================

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM orders
");

$totalOrders = (int)$stmt->fetchColumn();


// ==========================================
// TOTAL CUSTOMERS
// ==========================================

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM customers
");

$totalCustomers = (int)$stmt->fetchColumn();


// ==========================================
// TODAY'S SALES
// Excluding cancelled orders
// ==========================================

$stmt = $pdo->query("
    SELECT COALESCE(SUM(total_amount), 0)
    FROM orders
    WHERE created_at >= CURDATE()
      AND created_at < CURDATE() + INTERVAL 1 DAY
      AND order_status != 'Cancelled'
");

$todaySales = (float)$stmt->fetchColumn();


// ==========================================
// RECENT ORDERS
// ==========================================

$stmt = $pdo->query("
    SELECT
        o.id,
        o.order_number,
        o.total_amount,
        o.order_status,
        o.created_at,
        c.customer_name
    FROM orders o
    INNER JOIN customers c
        ON o.customer_id = c.id
    ORDER BY o.created_at DESC
    LIMIT 5
");

$recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ==========================================
// HELPER FUNCTION FOR SAFE OUTPUT
// ==========================================

function e($value)
{
    return htmlspecialchars(
        (string)($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard | Dubey Kirana Store</title>

    <link rel="stylesheet" href="../assets/css/admin.css">

</head>

<body>

<div class="admin-layout">

    <!-- ========================================== -->
    <!-- SIDEBAR -->
    <!-- ========================================== -->

    <aside class="sidebar">

        <div class="sidebar-logo">
            🛒 Dubey Kirana
        </div>

        <p class="sidebar-subtitle">
            STORE ADMIN PANEL
        </p>

        <nav>

            <a href="dashboard.php" class="active">
                📊 Dashboard
            </a>

            <a href="products.php">
                📦 Manage Products
            </a>

            <a href="orders.php">
                🛍️ Manage Orders
            </a>

            <a href="categories.php">
                📂 Manage Categories
            </a>

            <a href="reports.php">
                📈 Sales Reports
            </a>

            <a href="inventory.php">
                📋 Inventory Management
            </a>

            <a href="delivery.php">
                🚚 Delivery Management
            </a>

            <a href="customers.php">
                👥 Manage Customers
            </a>

        </nav>

        <div class="sidebar-bottom">

            <a href="../index.php" target="_blank" rel="noopener noreferrer">
                🌐 View Store
            </a>

            <a href="logout.php" class="logout">
                🚪 Logout
            </a>

        </div>

    </aside>


    <!-- ========================================== -->
    <!-- MAIN CONTENT -->
    <!-- ========================================== -->

    <main class="main-content">

        <!-- TOPBAR -->

        <header class="topbar">

            <div>

                <h1>Dashboard</h1>

                <p>
                    Welcome to Dubey Kirana Store Admin Panel
                </p>

            </div>

            <div class="admin-profile">

                <div class="profile-icon">
                    A
                </div>

                <div>

                    <strong>
                        <?= e($_SESSION["admin_username"] ?? "Admin") ?>
                    </strong>

                    <small>
                        Store Administrator
                    </small>

                </div>

            </div>

        </header>


        <!-- ========================================== -->
        <!-- STATISTICS CARDS -->
        <!-- ========================================== -->

        <section class="stats-grid">

            <!-- TOTAL PRODUCTS -->

            <div class="stat-card">

                <div class="stat-icon green">
                    📦
                </div>

                <div>

                    <p>Total Products</p>

                    <h2>
                        <?= number_format($totalProducts) ?>
                    </h2>

                </div>

            </div>


            <!-- TOTAL ORDERS -->

            <div class="stat-card">

                <div class="stat-icon blue">
                    🛍️
                </div>

                <div>

                    <p>Total Orders</p>

                    <h2>
                        <?= number_format($totalOrders) ?>
                    </h2>

                </div>

            </div>


            <!-- TOTAL CUSTOMERS -->

            <div class="stat-card">

                <div class="stat-icon orange">
                    👥
                </div>

                <div>

                    <p>Total Customers</p>

                    <h2>
                        <?= number_format($totalCustomers) ?>
                    </h2>

                </div>

            </div>


            <!-- TODAY'S SALES -->

            <div class="stat-card">

                <div class="stat-icon purple">
                    💰
                </div>

                <div>

                    <p>Today's Sales</p>

                    <h2>
                        ₹<?= number_format($todaySales, 2) ?>
                    </h2>

                </div>

            </div>

        </section>


        <!-- ========================================== -->
        <!-- RECENT ORDERS -->
        <!-- ========================================== -->

        <section class="orders-section">

            <div class="section-header">

                <div>

                    <h2>Recent Orders</h2>

                    <p>
                        Latest customer orders
                    </p>

                </div>

                <a href="orders.php" class="view-all">
                    View All →
                </a>

            </div>


            <div class="table-container">

                <table>

                    <thead>

                        <tr>

                            <th>Order ID</th>

                            <th>Customer</th>

                            <th>Amount</th>

                            <th>Status</th>

                            <th>Date</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if (!empty($recentOrders)): ?>

                        <?php foreach ($recentOrders as $order): ?>

                            <tr>

                                <td>
                                    <?= e($order["order_number"]) ?>
                                </td>

                                <td>
                                    <?= e($order["customer_name"]) ?>
                                </td>

                                <td>
                                    ₹<?= number_format(
                                        (float)$order["total_amount"],
                                        2
                                    ) ?>
                                </td>

                                <td>

                                    <span class="status-badge">

                                        <?= e($order["order_status"]) ?>

                                    </span>

                                </td>

                                <td>

                                    <?php

                                    $orderDate = !empty($order["created_at"])
                                        ? strtotime($order["created_at"])
                                        : false;

                                    ?>

                                    <?= $orderDate
                                        ? date("d M Y", $orderDate)
                                        : "N/A" ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>

                            <td colspan="5" class="empty-message">

                                No orders have been received yet.

                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>


        <!-- ========================================== -->
        <!-- QUICK ACTIONS -->
        <!-- ========================================== -->

        <section class="quick-actions">

            <h2>Quick Actions</h2>

            <div class="actions-grid">

                <!-- ADD PRODUCT -->

                <a href="products.php" class="action-card">

                    <span>➕</span>

                    <div>

                        <h3>Add New Product</h3>

                        <p>
                            Add grocery products to your store
                        </p>

                    </div>

                </a>


                <!-- MANAGE ORDERS -->

                <a href="orders.php" class="action-card">

                    <span>🛍️</span>

                    <div>

                        <h3>Manage Orders</h3>

                        <p>
                            View and update customer orders
                        </p>

                    </div>

                </a>


                <!-- INVENTORY -->

                <a href="inventory.php" class="action-card">

                    <span>📋</span>

                    <div>

                        <h3>Check Inventory</h3>

                        <p>
                            Manage available stock
                        </p>

                    </div>

                </a>

            </div>

        </section>

    </main>

</div>

</body>

</html>