<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

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

// ==========================================
// SEARCH
// ==========================================

$search = trim($_GET["search"] ?? "");

// ==========================================
// STOCK FILTER
// ==========================================

$filter = $_GET["filter"] ?? "all";

$allowedFilters = [
    "all",
    "available",
    "low",
    "out"
];

if (!in_array($filter, $allowedFilters, true)) {
    $filter = "all";
}

// ==========================================
// FETCH PRODUCTS
// ==========================================

$sql = "
    SELECT
        id,
        product_name,
        price,
        stock_quantity,
        status
    FROM products
    WHERE product_name LIKE :search
";

if ($filter === "low") {

    $sql .= "
        AND stock_quantity > 0
        AND stock_quantity <= 10
    ";

} elseif ($filter === "out") {

    $sql .= "
        AND stock_quantity = 0
    ";

} elseif ($filter === "available") {

    $sql .= "
        AND stock_quantity > 10
    ";
}

$sql .= "
    ORDER BY stock_quantity ASC, product_name ASC
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":search" => "%" . $search . "%"
]);

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ==========================================
// INVENTORY STATISTICS
// ==========================================

// Total products
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM products
");

$total_products = (int)$stmt->fetchColumn();


// Low stock products
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM products
    WHERE stock_quantity > 0
      AND stock_quantity <= 10
");

$low_stock = (int)$stmt->fetchColumn();


// Out of stock products
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM products
    WHERE stock_quantity = 0
");

$out_of_stock = (int)$stmt->fetchColumn();


// Total stock units
$stmt = $pdo->query("
    SELECT COALESCE(SUM(stock_quantity), 0)
    FROM products
");

$total_stock = (int)$stmt->fetchColumn();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Inventory Management | Dubey Kirana Store</title>

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
            gap: 15px;
            flex-wrap: wrap;
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
            font-size: 15px;
            color: #777;
            margin-bottom: 12px;
        }

        .card p {
            font-size: 28px;
            font-weight: bold;
            color: #166534;
        }

        .card.warning {
            border-left-color: #f59e0b;
        }

        .card.warning p {
            color: #d97706;
        }

        .card.danger {
            border-left-color: #dc2626;
        }

        .card.danger p {
            color: #dc2626;
        }

        .filter-box {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .filter-box form {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        input, select, button {
            padding: 11px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        input {
            flex: 1;
            min-width: 200px;
        }

        button {
            background: #166534;
            color: white;
            border: none;
            cursor: pointer;
        }

        button:hover {
            background: #14532d;
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
            min-width: 700px;
            border-collapse: collapse;
        }

        th, td {
            padding: 14px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #f0fdf4;
            color: #166534;
        }

        .badge {
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }

        .in-stock {
            background: #dcfce7;
            color: #166534;
        }

        .low-stock {
            background: #fef3c7;
            color: #92400e;
        }

        .out-stock {
            background: #fee2e2;
            color: #991b1b;
        }

        .inactive {
            background: #e5e7eb;
            color: #374151;
        }

        .empty-message {
            text-align: center;
            padding: 25px;
            color: #777;
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

            .container {
                width: 95%;
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

        <h1>📦 Inventory Management</h1>

        <a href="dashboard.php" class="back-btn">
            ← Dashboard
        </a>

    </div>


    <!-- ========================================== -->
    <!-- INVENTORY STATISTICS -->
    <!-- ========================================== -->

    <div class="stats">

        <div class="card">

            <h3>Total Products</h3>

            <p>
                <?= number_format($total_products) ?>
            </p>

        </div>

        <div class="card">

            <h3>Total Stock Units</h3>

            <p>
                <?= number_format($total_stock) ?>
            </p>

        </div>

        <div class="card warning">

            <h3>Low Stock Products</h3>

            <p>
                <?= number_format($low_stock) ?>
            </p>

        </div>

        <div class="card danger">

            <h3>Out of Stock</h3>

            <p>
                <?= number_format($out_of_stock) ?>
            </p>

        </div>

    </div>


    <!-- ========================================== -->
    <!-- SEARCH AND FILTER -->
    <!-- ========================================== -->

    <div class="filter-box">

        <form method="GET">

            <input
                type="text"
                name="search"
                placeholder="Search product name..."
                maxlength="100"
                value="<?= e($search) ?>"
            >

            <select name="filter">

                <option value="all"
                    <?= $filter === "all" ? "selected" : "" ?>>
                    All Products
                </option>

                <option value="available"
                    <?= $filter === "available" ? "selected" : "" ?>>
                    Available Stock
                </option>

                <option value="low"
                    <?= $filter === "low" ? "selected" : "" ?>>
                    Low Stock
                </option>

                <option value="out"
                    <?= $filter === "out" ? "selected" : "" ?>>
                    Out of Stock
                </option>

            </select>

            <button type="submit">
                Search / Filter
            </button>

        </form>

    </div>


    <!-- ========================================== -->
    <!-- PRODUCT INVENTORY TABLE -->
    <!-- ========================================== -->

    <div class="table-box">

        <h2>📋 Product Inventory</h2>

        <table>

            <thead>

                <tr>

                    <th>ID</th>

                    <th>Product Name</th>

                    <th>Price</th>

                    <th>Available Quantity</th>

                    <th>Stock Status</th>

                    <th>Product Status</th>

                </tr>

            </thead>

            <tbody>

            <?php if (!empty($products)): ?>

                <?php foreach ($products as $product): ?>

                    <?php

                    $quantity = (int)$product["stock_quantity"];

                    if ($quantity === 0) {

                        $stock_class = "out-stock";
                        $stock_text = "Out of Stock";

                    } elseif ($quantity <= 10) {

                        $stock_class = "low-stock";
                        $stock_text = "Low Stock";

                    } else {

                        $stock_class = "in-stock";
                        $stock_text = "In Stock";

                    }

                    $productStatus = strtolower(
                        trim($product["status"] ?? "")
                    );

                    ?>

                    <tr>

                        <td>
                            <?= (int)$product["id"] ?>
                        </td>

                        <td>
                            <?= e($product["product_name"]) ?>
                        </td>

                        <td>
                            ₹<?= number_format(
                                (float)$product["price"],
                                2
                            ) ?>
                        </td>

                        <td>
                            <?= number_format($quantity) ?>
                        </td>

                        <td>

                            <span class="badge <?= e($stock_class) ?>">

                                <?= e($stock_text) ?>

                            </span>

                        </td>

                        <td>

                            <?php if ($productStatus === "active"): ?>

                                <span class="badge in-stock">
                                    Active
                                </span>

                            <?php else: ?>

                                <span class="badge inactive">

                                    <?= e(
                                        ucfirst($productStatus ?: "Inactive")
                                    ) ?>

                                </span>

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>

                    <td colspan="6" class="empty-message">

                        No products found.

                    </td>

                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

</body>

</html>