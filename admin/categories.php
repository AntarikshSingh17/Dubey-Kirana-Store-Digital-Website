<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

// Generate CSRF token
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$message = "";
$messageType = "success";

// ==========================================
// CSRF TOKEN VALIDATION
// ==========================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $submitted_token = $_POST["csrf_token"] ?? "";

    if (!hash_equals($_SESSION["csrf_token"], $submitted_token)) {
        die("Invalid CSRF token. Request denied.");
    }
}

// ==========================================
// ADD OR UPDATE CATEGORY
// ==========================================

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["save_category"])) {

    $categoryId = (int)($_POST["category_id"] ?? 0);
    $categoryName = trim($_POST["category_name"] ?? "");

    if ($categoryName === "") {

        $message = "Category name is required.";
        $messageType = "error";

    } else {

        // Check duplicate category name
        $check = $pdo->prepare("
            SELECT id FROM categories
            WHERE category_name = ?
            AND id != ?
        ");

        $check->execute([$categoryName, $categoryId]);

        if ($check->fetch()) {

            $message = "This category already exists.";
            $messageType = "error";

        } else {

            // Update category
            if ($categoryId > 0) {

                $stmt = $pdo->prepare("
                    UPDATE categories
                    SET category_name = ?
                    WHERE id = ?
                ");

                $stmt->execute([$categoryName, $categoryId]);

                $message = "Category updated successfully!";

            } else {

                // Insert new category
                $stmt = $pdo->prepare("
                    INSERT INTO categories (category_name)
                    VALUES (?)
                ");

                $stmt->execute([$categoryName]);

                $message = "Category added successfully!";
            }
        }
    }
}

// ==========================================
// DELETE CATEGORY
// ==========================================

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_category"])) {

    $categoryId = (int)($_POST["category_id"] ?? 0);

    if ($categoryId <= 0) {

        $message = "Invalid category ID.";
        $messageType = "error";

    } else {

        // Check whether category has products
        $check = $pdo->prepare("
            SELECT COUNT(*) FROM products
            WHERE category_id = ?
        ");

        $check->execute([$categoryId]);

        if ($check->fetchColumn() > 0) {

            $message = "Cannot delete this category because products are assigned to it.";
            $messageType = "error";

        } else {

            $stmt = $pdo->prepare("
                DELETE FROM categories
                WHERE id = ?
            ");

            $stmt->execute([$categoryId]);

            if ($stmt->rowCount() > 0) {

                $message = "Category deleted successfully!";

            } else {

                $message = "Category not found.";
                $messageType = "error";
            }
        }
    }
}

// ==========================================
// FETCH CATEGORY FOR EDITING
// ==========================================

$editCategory = null;

if (isset($_GET["edit"])) {

    $editId = (int)$_GET["edit"];

    if ($editId > 0) {

        $stmt = $pdo->prepare("
            SELECT * FROM categories
            WHERE id = ?
        ");

        $stmt->execute([$editId]);

        $editCategory = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// ==========================================
// FETCH ALL CATEGORIES
// ==========================================

$categories = $pdo->query("
    SELECT
        c.id,
        c.category_name,
        c.created_at,
        COUNT(p.id) AS total_products
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id
    GROUP BY c.id, c.category_name, c.created_at
    ORDER BY c.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Category Management | Dubey Kirana Store</title>

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

        .form-group {
            display: flex;
            flex-direction: column;
            max-width: 500px;
        }

        .form-group label {
            font-weight: bold;
            margin-bottom: 8px;
        }

        .form-group input {
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 15px;
        }

        .save-btn {
            background: #16a34a;
            color: white;
            border: none;
            padding: 12px 22px;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 15px;
        }

        .cancel-btn {
            background: #64748b;
            color: white;
            text-decoration: none;
            padding: 12px 22px;
            border-radius: 6px;
            display: inline-block;
            margin-top: 15px;
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
            min-width: 650px;
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
        }

        tr:hover {
            background: #f9fafb;
        }

        .edit-btn {
            background: #2563eb;
            color: white;
            padding: 8px 12px;
            text-decoration: none;
            border-radius: 5px;
        }

        .delete-btn {
            background: #dc2626;
            color: white;
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
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

        <h1>📂 Category Management</h1>

        <a href="dashboard.php" class="back-btn">
            ← Back to Dashboard
        </a>

    </div>

    <?php if ($message !== ""): ?>

        <div class="message <?= htmlspecialchars($messageType) ?>">
            <?= htmlspecialchars($message) ?>
        </div>

    <?php endif; ?>

    <div class="form-container">

        <h2>
            <?= $editCategory ? "✏️ Edit Category" : "➕ Add New Category" ?>
        </h2>

        <form method="POST">

            <!-- CSRF TOKEN -->
            <input type="hidden"
                   name="csrf_token"
                   value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">

            <input type="hidden"
                   name="category_id"
                   value="<?= (int)($editCategory["id"] ?? 0) ?>">

            <div class="form-group">

                <label>Category Name</label>

                <input type="text"
                       name="category_name"
                       required
                       maxlength="100"
                       placeholder="Example: Rice, Pulses, Snacks"
                       value="<?= htmlspecialchars($editCategory["category_name"] ?? "") ?>">

            </div>

            <button type="submit"
                    name="save_category"
                    class="save-btn">

                <?= $editCategory ? "Update Category" : "Add Category" ?>

            </button>

            <?php if ($editCategory): ?>

                <a href="categories.php" class="cancel-btn">
                    Cancel Edit
                </a>

            <?php endif; ?>

        </form>

    </div>

    <div class="table-container">

        <h2 style="margin-bottom:20px;">All Categories</h2>

        <table>

            <thead>

                <tr>
                    <th>ID</th>
                    <th>Category Name</th>
                    <th>Total Products</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>

            </thead>

            <tbody>

            <?php if (empty($categories)): ?>

                <tr>
                    <td colspan="5" style="text-align:center;">
                        No categories found.
                    </td>
                </tr>

            <?php else: ?>

                <?php foreach ($categories as $category): ?>

                    <tr>

                        <td><?= (int)$category["id"] ?></td>

                        <td>
                            <strong>
                                <?= htmlspecialchars($category["category_name"]) ?>
                            </strong>
                        </td>

                        <td>
                            <?= (int)$category["total_products"] ?>
                        </td>

                        <td>
                            <?= date("d M Y", strtotime($category["created_at"])) ?>
                        </td>

                        <td>

                            <a href="categories.php?edit=<?= (int)$category["id"] ?>"
                               class="edit-btn">
                                Edit
                            </a>

                            <form method="POST"
                                  style="display:inline;"
                                  onsubmit="return confirm('Are you sure you want to delete this category?');">

                                <!-- CSRF TOKEN -->
                                <input type="hidden"
                                       name="csrf_token"
                                       value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">

                                <input type="hidden"
                                       name="category_id"
                                       value="<?= (int)$category["id"] ?>">

                                <button type="submit"
                                        name="delete_category"
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