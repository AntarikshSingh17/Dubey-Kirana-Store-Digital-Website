
<?php

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load configuration
require_once __DIR__ . '/../config/config.php';

// Load helper functions
require_once __DIR__ . '/functions.php';

// Get cart count
$cartCount = getCartCount();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?= defined('SITE_NAME') ? htmlspecialchars(SITE_NAME) : 'Dubey Kirana Store' ?>
    </title>

    <meta name="description"
          content="Shop fresh groceries, fruits, vegetables, dairy products and daily essentials at Dubey Kirana Store.">

    <!-- Main CSS -->
    <link rel="stylesheet"
          href="<?= rtrim(SITE_URL, '/') ?>/assets/css/style.css">

    <!-- Responsive CSS -->
    <link rel="stylesheet"
          href="<?= rtrim(SITE_URL, '/') ?>/assets/css/responsive.css">

    <!-- Font Awesome -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body>

    <!-- Navigation Bar -->
    <?php include __DIR__ . '/navbar.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
