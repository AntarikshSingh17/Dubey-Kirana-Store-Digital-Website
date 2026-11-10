<?php
require_once "../includes/db.php";
require_once "../includes/auth.php";

$error = "";
$success = "";

// Fetch categories
$categoryStmt = $pdo->query("SELECT id, category_name FROM categories ORDER BY category_name");
$categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $product_name = trim($_POST["product_name"] ?? "");
    $category_id = $_POST["category_id"] ?? "";
    $description = trim($_POST["description"] ?? "");
    $price = $_POST["price"] ?? "";
    $stock_quantity = $_POST["stock_quantity"] ?? "";
    $status = $_POST["status"] ?? "active";

    if (
        $product_name === "" ||
        $category_id === "" ||
        $price === "" ||
        $stock_quantity === ""
    ) {
        $error = "Please fill in all required fields.";
    } elseif (!is_numeric($price) || $price < 0) {
        $error = "Please enter a valid price.";
    } elseif (!filter_var($stock_quantity, FILTER_VALIDATE_INT) || $stock_quantity < 0) {
        $error = "Please enter a valid stock quantity.";
    } elseif (!in_array($status, ["active", "inactive"], true)) {
        $error = "Invalid product status.";
    } else {

        $imageName = "";

        // Handle image upload
        if (isset($_FILES["image"]) && $_FILES["image"]["error"] !== UPLOAD_ERR_NO_FILE) {

            if ($_FILES["image"]["error"] !== UPLOAD_ERR_OK) {
                $error = "Image upload failed.";
            } elseif ($_FILES["image"]["size"] > 3 * 1024 * 1024) {
                $error = "Image size must be less than 3 MB.";
            } else {

                $allowedTypes = [
                    "image/jpeg" => "jpg",
                    "image/png" => "png",
                    "image/webp" => "webp"
                ];

                $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($fileInfo, $_FILES["image"]["tmp_name"]);
                finfo_close($fileInfo);

                if (!isset($allowedTypes[$mimeType])) {
                    $error = "Only JPG, PNG, and WEBP images are allowed.";
                } else {

                    $uploadDir = "../uploads/products/";

                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $imageName = uniqid("product_", true) . "." . $allowedTypes[$mimeType];

                    if (!move_uploaded_file(
                        $_FILES["image"]["tmp_name"],
                        $uploadDir . $imageName
                    )) {
                        $error = "Could not save the uploaded image.";
                    }
                }
            }
        }

        // Save product in database
        if ($error === "") {

            try {

                $stmt = $pdo->prepare("
                    INSERT INTO products
                    (product_name, category_id, description, price, stock_quantity, image, status)
                    VALUES
                    (:product_name, :category_id, :description, :price, :stock_quantity, :image, :status)
                ");

                $stmt->execute([
                    ":product_name" => $product_name,
                    ":category_id" => $category_id,
                    ":description" => $description,
                    ":price" => $price,
                    ":stock_quantity" => $stock_quantity,
                    ":image" => $imageName,
                    ":status" => $status
                ]);

                header("Location: products.php?added=1");
                exit;

            } catch (PDOException $e) {
                $error = "Database error. Please check your product table columns.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add Product | Dubey Kirana Store</title>

    <link rel="stylesheet" href="../assets/css/admin.css">

    <style>
        .form-container {
            max-width: 800px;
            background: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-top: 25px;
        }

        .product-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            width: 100%;
            box-sizing: border-box;
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-buttons {
            grid-column: 1 / -1;
            display: flex;
            gap: 15px;
            margin-top: 10px;
        }

        .btn-primary,
        .btn-secondary {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-primary {
            background: #198754;
            color: white;
        }

        .btn-secondary {
            background: #eeeeee;
            color: #333;
        }

        .error-message {
            background: #ffe5e5;
            color: #b00020;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        @media (max-width: 600px) {
            .product-form {
                grid-template-columns: 1fr;
            }

            .form-group.full-width,
            .form-buttons {
                grid-column: auto;
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
            <a href="products.php" class="active">Products</a>
            <a href="orders.php">Orders</a>
            <a href="inventory.php">Inventory</a>
            <a href="customers.php">Customers</a>
            <a href="logout.php">Logout</a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">

        <header class="topbar">
            <h1>Add New Product</h1>
            <span>Admin Panel</span>
        </header>

        <div class="form-container">

            <h2>Product Information</h2>

            <?php if ($error !== ""): ?>
                <div class="error-message">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if (empty($categories)): ?>
                <div class="error-message">
                    No categories found. Please add categories to the database first.
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="product-form">

                <div class="form-group">
                    <label for="product_name">Product Name *</label>
                    <input
                        type="text"
                        id="product_name"
                        name="product_name"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="category_id">Category *</label>

                    <select id="category_id" name="category_id" required>
                        <option value="">Select Category</option>

                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>">
                                <?= htmlspecialchars($category['category_name']) ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="form-group">
                    <label for="price">Price (₹) *</label>
                    <input
                        type="number"
                        id="price"
                        name="price"
                        min="0"
                        step="0.01"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="stock_quantity">Stock Quantity *</label>
                    <input
                        type="number"
                        id="stock_quantity"
                        name="stock_quantity"
                        min="0"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="status">Product Status</label>

                    <select id="status" name="status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="image">Product Image</label>
                    <input
                        type="file"
                        id="image"
                        name="image"
                        accept="image/jpeg,image/png,image/webp"
                    >
                    <small>JPG, PNG, WEBP — Maximum 3 MB.</small>
                </div>

                <div class="form-group full-width">
                    <label for="description">Product Description</label>

                    <textarea
                        id="description"
                        name="description"
                        placeholder="Enter product details..."
                    ></textarea>
                </div>

                <div class="form-buttons">

                    <button type="submit" class="btn-primary">
                        Save Product
                    </button>

                    <a href="products.php" class="btn-secondary">
                        Cancel
                    </a>

                </div>

            </form>

        </div>

    </main>

</div>

</body>
</html>