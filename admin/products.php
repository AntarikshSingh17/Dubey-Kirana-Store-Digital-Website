<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

// ==========================================
// CSRF TOKEN GENERATION
// ==========================================

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$uploadDir = "../uploads/products/";

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$message = "";
$messageType = "success";

$editProduct = null;

// ==========================================
// CSRF VALIDATION
// ==========================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $submittedToken = $_POST["csrf_token"] ?? "";

    if (!hash_equals($_SESSION["csrf_token"], $submittedToken)) {
        die("Invalid CSRF token. Request denied.");
    }
}

// ==========================================
// DELETE PRODUCT
// ==========================================

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_product"])) {

    $productId = (int)($_POST["product_id"] ?? 0);

    if ($productId <= 0) {

        $message = "Invalid product ID.";
        $messageType = "error";

    } else {

        // Check whether product is used in any order
        $check = $pdo->prepare("
            SELECT COUNT(*)
            FROM order_items
            WHERE product_id = ?
        ");

        $check->execute([$productId]);

        if ($check->fetchColumn() > 0) {

            $message = "This product is already included in an order. It cannot be deleted.";
            $messageType = "error";

        } else {

            // Get product image
            $stmt = $pdo->prepare("
                SELECT image
                FROM products
                WHERE id = ?
            ");

            $stmt->execute([$productId]);

            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($product) {

                // Delete product
                $delete = $pdo->prepare("
                    DELETE FROM products
                    WHERE id = ?
                ");

                $delete->execute([$productId]);

                // Delete associated image
                if (!empty($product["image"])) {

                    $imagePath = $uploadDir . basename($product["image"]);

                    if (is_file($imagePath)) {
                        unlink($imagePath);
                    }
                }

                $message = "Product deleted successfully!";

            } else {

                $message = "Product not found.";
                $messageType = "error";
            }
        }
    }
}

// ==========================================
// ADD OR UPDATE PRODUCT
// ==========================================

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["save_product"])) {

    $productId = (int)($_POST["product_id"] ?? 0);

    $productName = trim($_POST["product_name"] ?? "");
    $categoryId = (int)($_POST["category_id"] ?? 0);
    $description = trim($_POST["description"] ?? "");
    $price = (float)($_POST["price"] ?? 0);
    $stockQuantity = (int)($_POST["stock_quantity"] ?? 0);
    $status = $_POST["status"] ?? "Active";

    // Validate product details
    if (
        $productName === "" ||
        $categoryId <= 0 ||
        $price <= 0 ||
        $stockQuantity < 0 ||
        !in_array($status, ["Active", "Inactive"], true)
    ) {

        $message = "Please enter valid product details.";
        $messageType = "error";

    } else {

        // Verify category exists
        $categoryCheck = $pdo->prepare("
            SELECT id
            FROM categories
            WHERE id = ?
        ");

        $categoryCheck->execute([$categoryId]);

        if (!$categoryCheck->fetch()) {

            $message = "Selected category does not exist.";
            $messageType = "error";

        } else {

            $imageName = "";

            // ==========================================
            // GET EXISTING IMAGE DURING EDIT
            // ==========================================

            if ($productId > 0) {

                $stmt = $pdo->prepare("
                    SELECT image
                    FROM products
                    WHERE id = ?
                ");

                $stmt->execute([$productId]);

                $existingProduct = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$existingProduct) {

                    $message = "Product not found.";
                    $messageType = "error";

                } else {

                    $imageName = $existingProduct["image"] ?? "";
                }
            }

            // ==========================================
            // IMAGE UPLOAD
            // ==========================================

            if (
                $messageType !== "error" &&
                isset($_FILES["image"]) &&
                $_FILES["image"]["error"] !== UPLOAD_ERR_NO_FILE
            ) {

                if ($_FILES["image"]["error"] !== UPLOAD_ERR_OK) {

                    $message = "Image upload failed.";
                    $messageType = "error";

                } else {

                    $tmpName = $_FILES["image"]["tmp_name"];

                    // Validate file size
                    if ($_FILES["image"]["size"] > 5 * 1024 * 1024) {

                        $message = "Image size must be less than 5 MB.";
                        $messageType = "error";

                    } else {

                        // Validate actual image content
                        $imageInfo = getimagesize($tmpName);

                        if ($imageInfo === false) {

                            $message = "Uploaded file is not a valid image.";
                            $messageType = "error";

                        } else {

                            // Allowed image MIME types
                            $allowedMimeTypes = [
                                "image/jpeg" => "jpg",
                                "image/png" => "png",
                                "image/webp" => "webp"
                            ];

                            $mimeType = $imageInfo["mime"] ?? "";

                            if (!isset($allowedMimeTypes[$mimeType])) {

                                $message = "Only JPG, PNG and WEBP images are allowed.";
                                $messageType = "error";

                            } else {

                                $extension = $allowedMimeTypes[$mimeType];

                                // Generate secure image name
                                $newImageName = bin2hex(random_bytes(16)) . "." . $extension;

                                if (
                                    move_uploaded_file(
                                        $tmpName,
                                        $uploadDir . $newImageName
                                    )
                                ) {

                                    // Delete old image if replacing
                                    if (
                                        $productId > 0 &&
                                        !empty($imageName)
                                    ) {

                                        $oldImagePath = $uploadDir . basename($imageName);

                                        if (is_file($oldImagePath)) {
                                            unlink($oldImagePath);
                                        }
                                    }

                                    $imageName = $newImageName;

                                } else {

                                    $message = "Unable to save uploaded image.";
                                    $messageType = "error";
                                }
                            }
                        }
                    }
                }
            }

            // ==========================================
            // SAVE PRODUCT
            // ==========================================

            if ($messageType !== "error") {

                if ($productId > 0) {

                    // Update product
                    $stmt = $pdo->prepare("
                        UPDATE products
                        SET category_id = ?,
                            product_name = ?,
                            description = ?,
                            price = ?,
                            stock_quantity = ?,
                            image = ?,
                            status = ?
                        WHERE id = ?
                    ");

                    $stmt->execute([
                        $categoryId,
                        $productName,
                        $description,
                        $price,
                        $stockQuantity,
                        $imageName,
                        $status,
                        $productId
                    ]);

                    $message = "Product updated successfully!";

                } else {

                    // Insert new product
                    $stmt = $pdo->prepare("
                        INSERT INTO products
                        (
                            category_id,
                            product_name,
                            description,
                            price,
                            stock_quantity,
                            image,
                            status
                        )
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");

                    $stmt->execute([
                        $categoryId,
                        $productName,
                        $description,
                        $price,
                        $stockQuantity,
                        $imageName,
                        $status
                    ]);

                    $message = "Product added successfully!";
                }
            }
        }
    }
}

// ==========================================
// FETCH PRODUCT FOR EDITING
// ==========================================

if (isset($_GET["edit"])) {

    $editId = (int)$_GET["edit"];

    if ($editId > 0) {

        $stmt = $pdo->prepare("
            SELECT *
            FROM products
            WHERE id = ?
        ");

        $stmt->execute([$editId]);

        $editProduct = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// ==========================================
// FETCH CATEGORIES
// ==========================================

$categories = $pdo->query("
    SELECT *
    FROM categories
    ORDER BY category_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

// ==========================================
// FETCH PRODUCTS
// ==========================================

$products = $pdo->query("
    SELECT p.*, c.category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY p.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Product Management | Dubey Kirana Store</title>

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

        .message {
            padding: 14px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .success {
            background: #dcfce7;
            color: #166534;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
        }

        .form-container {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .form-container h2 {
            color: #14532d;
            margin-bottom: 20px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 8px;
            font-weight: bold;
            font-size: 14px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        .full-width {
            grid-column: 1 / -1;
        }

        .save-btn {
            background: #16a34a;
            color: white;
            border: none;
            padding: 12px 22px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 15px;
        }

        .cancel-btn {
            background: #64748b;
            color: white;
            text-decoration: none;
            padding: 12px 22px;
            border-radius: 6px;
        }

        .table-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            overflow-x: auto;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
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
            vertical-align: middle;
        }

        tr:hover {
            background: #f9fafb;
        }

        .product-image {
            width: 65px;
            height: 65px;
            object-fit: cover;
            border-radius: 6px;
            background: #f1f5f9;
        }

        .edit-btn {
            background: #2563eb;
            color: white;
            padding: 8px 12px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
        }

        .delete-btn {
            background: #dc2626;
            color: white;
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .status {
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .active {
            background: #dcfce7;
            color: #166534;
        }

        .inactive {
            background: #fee2e2;
            color: #991b1b;
        }

        @media(max-width: 768px) {

            .container {
                padding: 15px;
            }

            .form-grid {
                grid-template-columns: 1fr;
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

        <h1>🛒 Product Management</h1>

        <a href="dashboard.php" class="back-btn">
            ← Back to Dashboard
        </a>

    </div>

    <?php if ($message !== ""): ?>

        <div class="message <?= htmlspecialchars($messageType) ?>">
            <?= htmlspecialchars($message) ?>
        </div>

    <?php endif; ?>

    <!-- ========================================== -->
    <!-- ADD / UPDATE PRODUCT FORM -->
    <!-- ========================================== -->

    <div class="form-container">

        <h2>
            <?= $editProduct ? "✏️ Edit Product" : "➕ Add New Product" ?>
        </h2>

        <form method="POST" enctype="multipart/form-data">

            <!-- CSRF TOKEN -->
            <input type="hidden"
                   name="csrf_token"
                   value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">

            <input type="hidden"
                   name="product_id"
                   value="<?= (int)($editProduct["id"] ?? 0) ?>">

            <div class="form-grid">

                <div class="form-group">

                    <label>Product Name</label>

                    <input type="text"
                           name="product_name"
                           required
                           maxlength="255"
                           value="<?= htmlspecialchars($editProduct["product_name"] ?? "") ?>">

                </div>

                <div class="form-group">

                    <label>Category</label>

                    <select name="category_id" required>

                        <option value="">Select Category</option>

                        <?php foreach ($categories as $category): ?>

                            <option value="<?= (int)$category["id"] ?>"
                                <?= isset($editProduct["category_id"]) &&
                                $editProduct["category_id"] == $category["id"]
                                ? "selected" : "" ?>>

                                <?= htmlspecialchars($category["category_name"]) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="form-group">

                    <label>Price (₹)</label>

                    <input type="number"
                           name="price"
                           min="0.01"
                           step="0.01"
                           required
                           value="<?= htmlspecialchars($editProduct["price"] ?? "") ?>">

                </div>

                <div class="form-group">

                    <label>Stock Quantity</label>

                    <input type="number"
                           name="stock_quantity"
                           min="0"
                           required
                           value="<?= htmlspecialchars($editProduct["stock_quantity"] ?? "0") ?>">

                </div>

                <div class="form-group">

                    <label>Product Image</label>

                    <input type="file"
                           name="image"
                           accept=".jpg,.jpeg,.png,.webp">

                    <?php if (!empty($editProduct["image"])): ?>

                        <img src="../uploads/products/<?= htmlspecialchars(basename($editProduct["image"])) ?>"
                             class="product-image"
                             style="margin-top:10px;"
                             alt="Current product image">

                    <?php endif; ?>

                </div>

                <div class="form-group">

                    <label>Status</label>

                    <select name="status">

                        <option value="Active"
                            <?= ($editProduct["status"] ?? "Active") === "Active"
                            ? "selected" : "" ?>>

                            Active

                        </option>

                        <option value="Inactive"
                            <?= ($editProduct["status"] ?? "") === "Inactive"
                            ? "selected" : "" ?>>

                            Inactive

                        </option>

                    </select>

                </div>

                <div class="form-group full-width">

                    <label>Description</label>

                    <textarea name="description"><?= htmlspecialchars($editProduct["description"] ?? "") ?></textarea>

                </div>

                <div class="form-group full-width">

                    <div style="display:flex;gap:10px;flex-wrap:wrap;">

                        <button type="submit"
                                name="save_product"
                                class="save-btn">

                            <?= $editProduct ? "Update Product" : "Add Product" ?>

                        </button>

                        <?php if ($editProduct): ?>

                            <a href="products.php" class="cancel-btn">
                                Cancel Edit
                            </a>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        </form>

    </div>

    <!-- ========================================== -->
    <!-- ALL PRODUCTS TABLE -->
    <!-- ========================================== -->

    <div class="table-container">

        <h2 style="margin-bottom:20px;">All Products</h2>

        <table>

            <thead>

                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>

            </thead>

            <tbody>

            <?php if (empty($products)): ?>

                <tr>
                    <td colspan="8" style="text-align:center;">
                        No products found.
                    </td>
                </tr>

            <?php else: ?>

                <?php foreach ($products as $product): ?>

                    <tr>

                        <td>
                            <?= (int)$product["id"] ?>
                        </td>

                        <td>

                            <?php if (!empty($product["image"])): ?>

                                <img src="../uploads/products/<?= htmlspecialchars(basename($product["image"])) ?>"
                                     class="product-image"
                                     alt="<?= htmlspecialchars($product["product_name"]) ?>">

                            <?php else: ?>

                                <span>No Image</span>

                            <?php endif; ?>

                        </td>

                        <td>

                            <strong>
                                <?= htmlspecialchars($product["product_name"]) ?>
                            </strong>

                        </td>

                        <td>

                            <?= htmlspecialchars($product["category_name"] ?? "Uncategorized") ?>

                        </td>

                        <td>

                            ₹<?= number_format((float)$product["price"], 2) ?>

                        </td>

                        <td>

                            <?= (int)$product["stock_quantity"] ?>

                        </td>

                        <td>

                            <span class="status <?= strtolower(htmlspecialchars($product["status"])) ?>">

                                <?= htmlspecialchars($product["status"]) ?>

                            </span>

                        </td>

                        <td>

                            <a href="products.php?edit=<?= (int)$product["id"] ?>"
                               class="edit-btn">

                                Edit

                            </a>

                            <form method="POST"
                                  style="display:inline;"
                                  onsubmit="return confirm('Are you sure you want to delete this product?');">

                                <!-- CSRF TOKEN -->
                                <input type="hidden"
                                       name="csrf_token"
                                       value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">

                                <input type="hidden"
                                       name="product_id"
                                       value="<?= (int)$product["id"] ?>">

                                <button type="submit"
                                        name="delete_product"
                                        class="delete-btn">

                                    Delete

                                </button>

                            </form>

                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

</body>
</html>