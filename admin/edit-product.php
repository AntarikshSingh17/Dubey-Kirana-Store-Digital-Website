<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

$error = "";

// Escape output safely
function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get product ID
$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    http_response_code(400);
    die("Invalid product ID.");
}

// Fetch product
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);

$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    http_response_code(404);
    die("Product not found.");
}

// Fetch categories
$categoryStmt = $pdo->query(
    "SELECT id, category_name FROM categories ORDER BY category_name"
);

$categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Validate CSRF token
    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $error = "Invalid form request. Please refresh the page.";
    }

    $product_name = trim($_POST["product_name"] ?? "");
    $category_id = filter_var(
        $_POST["category_id"] ?? "",
        FILTER_VALIDATE_INT
    );
    $description = trim($_POST["description"] ?? "");
    $price = $_POST["price"] ?? "";
    $stock_quantity = $_POST["stock_quantity"] ?? "";
    $status = $_POST["status"] ?? "active";

    // Required fields validation
    if ($error === "" && (
        $product_name === "" ||
        !$category_id ||
        $price === "" ||
        $stock_quantity === ""
    )) {
        $error = "Please fill in all required fields.";
    }

    // Product name validation
    if ($error === "" && strlen($product_name) > 255) {
        $error = "Product name must not exceed 255 characters.";
    }

    // Price validation
    if (
        $error === "" &&
        (!is_numeric($price) || !is_finite((float)$price) || (float)$price < 0)
    ) {
        $error = "Please enter a valid price.";
    }

    // Stock validation
    if (
        $error === "" &&
        (!filter_var($stock_quantity, FILTER_VALIDATE_INT) ||
        (int)$stock_quantity < 0)
    ) {
        $error = "Please enter a valid stock quantity.";
    }

    // Status validation
    if (
        $error === "" &&
        !in_array($status, ["active", "inactive"], true)
    ) {
        $error = "Invalid product status.";
    }

    // Validate category exists
    if ($error === "") {

        $categoryCheck = $pdo->prepare(
            "SELECT id FROM categories WHERE id = ?"
        );

        $categoryCheck->execute([$category_id]);

        if (!$categoryCheck->fetch()) {
            $error = "Please select a valid category.";
        }
    }

    $imageName = $product["image"];

    // Handle image upload
    if (
        $error === "" &&
        isset($_FILES["image"]) &&
        $_FILES["image"]["error"] !== UPLOAD_ERR_NO_FILE
    ) {

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

            $mimeType = finfo_file(
                $fileInfo,
                $_FILES["image"]["tmp_name"]
            );

            finfo_close($fileInfo);

            if (!isset($allowedTypes[$mimeType])) {

                $error = "Only JPG, PNG, and WEBP images are allowed.";

            } elseif (
                getimagesize($_FILES["image"]["tmp_name"]) === false
            ) {

                $error = "Uploaded file is not a valid image.";

            } else {

                $uploadDir = "../uploads/products/";

                if (!is_dir($uploadDir)) {

                    if (!mkdir($uploadDir, 0755, true)) {
                        $error = "Could not create upload directory.";
                    }
                }

                if ($error === "") {

                    $newImageName = bin2hex(random_bytes(16))
                        . "."
                        . $allowedTypes[$mimeType];

                    if (move_uploaded_file(
                        $_FILES["image"]["tmp_name"],
                        $uploadDir . $newImageName
                    )) {

                        $imageName = $newImageName;

                    } else {

                        $error = "Could not save the uploaded image.";

                    }
                }
            }
        }
    }

    // Update product
    if ($error === "") {

        try {

            $updateStmt = $pdo->prepare("
                UPDATE products
                SET
                    product_name = :product_name,
                    category_id = :category_id,
                    description = :description,
                    price = :price,
                    stock_quantity = :stock_quantity,
                    image = :image,
                    status = :status
                WHERE id = :id
            ");

            $updateStmt->execute([
                ":product_name" => $product_name,
                ":category_id" => $category_id,
                ":description" => $description,
                ":price" => $price,
                ":stock_quantity" => $stock_quantity,
                ":image" => $imageName,
                ":status" => $status,
                ":id" => $id
            ]);

            // Delete old image after successful update
            if (
                $imageName !== $product["image"] &&
                !empty($product["image"])
            ) {

                $oldImagePath = "../uploads/products/"
                    . basename($product["image"]);

                if (is_file($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }

            header("Location: products.php?updated=1");
            exit;

        } catch (PDOException $e) {

            $error = "Database error while updating product.";

        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Product | Dubey Kirana Store</title>

    <link rel="stylesheet" href="../assets/css/admin.css">

    <style>

        .form-container {
            max-width: 800px;
            background: #fff;
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
            background: #eee;
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

            <h1>Edit Product</h1>

            <span>Admin Panel</span>

        </header>


        <div class="form-container">

            <h2>Update Product Information</h2>

            <?php if ($error !== ""): ?>

                <div class="error-message">
                    <?= e($error) ?>
                </div>

            <?php endif; ?>


            <form method="POST" enctype="multipart/form-data" class="product-form">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e($_SESSION['csrf_token']) ?>"
                >


                <div class="form-group">

                    <label for="product_name">
                        Product Name *
                    </label>

                    <input
                        type="text"
                        id="product_name"
                        name="product_name"
                        value="<?= e($_POST['product_name'] ?? $product['product_name']) ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="category_id">
                        Category *
                    </label>

                    <select id="category_id" name="category_id" required>

                        <?php foreach ($categories as $category): ?>

                            <option
                                value="<?= (int)$category['id'] ?>"
                                <?= (
                                    (int)($_POST['category_id'] ?? $product['category_id'])
                                    === (int)$category['id']
                                ) ? 'selected' : '' ?>
                            >

                                <?= e($category['category_name']) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label for="price">
                        Price (₹) *
                    </label>

                    <input
                        type="number"
                        id="price"
                        name="price"
                        min="0"
                        step="0.01"
                        value="<?= e($_POST['price'] ?? $product['price']) ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="stock_quantity">
                        Stock Quantity *
                    </label>

                    <input
                        type="number"
                        id="stock_quantity"
                        name="stock_quantity"
                        min="0"
                        value="<?= e($_POST['stock_quantity'] ?? $product['stock_quantity']) ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="status">
                        Product Status
                    </label>

                    <select id="status" name="status">

                        <option
                            value="active"
                            <?= ($_POST['status'] ?? $product['status']) === 'active'
                                ? 'selected'
                                : '' ?>
                        >
                            Active
                        </option>

                        <option
                            value="inactive"
                            <?= ($_POST['status'] ?? $product['status']) === 'inactive'
                                ? 'selected'
                                : '' ?>
                        >
                            Inactive
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label for="image">
                        Replace Product Image
                    </label>

                    <input
                        type="file"
                        id="image"
                        name="image"
                        accept="image/jpeg,image/png,image/webp"
                    >

                    <small>
                        Leave empty to keep the current image.
                    </small>

                </div>


                <div class="form-group full-width">

                    <label>Current Product Image</label>

                    <?php if (!empty($product['image'])): ?>

                        <img
                            src="../uploads/products/<?= e(basename($product['image'])) ?>"
                            width="120"
                            height="120"
                            style="object-fit:cover;border-radius:8px;"
                            alt="Current Product Image"
                        >

                    <?php else: ?>

                        <p>No image uploaded.</p>

                    <?php endif; ?>

                </div>


                <div class="form-group full-width">

                    <label for="description">
                        Product Description
                    </label>

                    <textarea
                        id="description"
                        name="description"
                    ><?= e($_POST['description'] ?? ($product['description'] ?? '')) ?></textarea>

                </div>


                <div class="form-buttons">

                    <button type="submit" class="btn-primary">
                        Update Product
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