
<?php

// ==========================================
// DUBEY KIRANA STORE
// NAVIGATION BAR
// ==========================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$cartCount = function_exists('getCartCount')
    ? getCartCount()
    : 0;

$currentPage = basename($_SERVER['PHP_SELF'] ?? '');

$baseUrl = rtrim(SITE_URL, '/');

$isCustomerLoggedIn = isset($_SESSION['customer_id']);

?>

<header class="site-header">

    <div class="container header-inner">

        <!-- LOGO -->
        <div class="logo">

            <a href="<?= htmlspecialchars($baseUrl) ?>/index.php">

                <i class="fa-solid fa-basket-shopping logo-icon"></i>

                <div class="logo-text">
                    Dubey Kirana
                    <span>STORE</span>
                </div>

            </a>

        </div>


        <!-- MOBILE MENU BUTTON -->
        <button
            type="button"
            class="mobile-menu-btn"
            id="mobileMenuBtn"
            aria-label="Toggle navigation menu"
            aria-controls="mainNavigation"
            aria-expanded="false"
        >

            <i class="fa-solid fa-bars"></i>

        </button>


        <!-- MAIN NAVIGATION -->
        <nav
            class="main-navigation"
            id="mainNavigation"
            aria-label="Main navigation"
        >

            <ul>

                <!-- HOME -->
                <li>

                    <a
                        href="<?= htmlspecialchars($baseUrl) ?>/index.php"
                        class="<?= $currentPage === 'index.php' ? 'active' : '' ?>"
                    >
                        Home
                    </a>

                </li>


                <!-- PRODUCTS -->
                <li>

                    <a
                        href="<?= htmlspecialchars($baseUrl) ?>/products.php"
                        class="<?= $currentPage === 'products.php' ? 'active' : '' ?>"
                    >
                        Products
                    </a>

                </li>


                <!-- CART -->
                <li>

                    <a
                        href="<?= htmlspecialchars($baseUrl) ?>/cart.php"
                        class="<?= $currentPage === 'cart.php' ? 'active' : '' ?>"
                    >
                        <i class="fa-solid fa-cart-shopping"></i>
                        Cart
                        <span class="cart-count">
                            <?= (int)$cartCount ?>
                        </span>
                    </a>

                </li>


                <!-- CONTACT -->
                <li>

                    <a
                        href="<?= htmlspecialchars($baseUrl) ?>/contact.php"
                        class="<?= $currentPage === 'contact.php' ? 'active' : '' ?>"
                    >
                        Contact
                    </a>

                </li>


                <!-- CUSTOMER ACCOUNT LINKS -->
                <?php if ($isCustomerLoggedIn): ?>

                    <!-- MY ACCOUNT -->
                    <li>

                        <a
                            href="<?= htmlspecialchars($baseUrl) ?>/my-account.php"
                            class="<?= $currentPage === 'my-account.php' ? 'active' : '' ?>"
                        >
                            <i class="fa-solid fa-user"></i>
                            My Account
                        </a>

                    </li>


                    <!-- MY ORDERS -->
                    <li>

                        <a
                            href="<?= htmlspecialchars($baseUrl) ?>/my-orders.php"
                            class="<?= $currentPage === 'my-orders.php' ? 'active' : '' ?>"
                        >
                            <i class="fa-solid fa-box"></i>
                            My Orders
                        </a>

                    </li>


                    <!-- LOGOUT -->
                    <li>

                        <a
                            href="<?= htmlspecialchars($baseUrl) ?>/logout.php"
                            class="logout-link"
                        >
                            <i class="fa-solid fa-right-from-bracket"></i>
                            Logout
                        </a>

                    </li>

                <?php else: ?>

                    <!-- CUSTOMER LOGIN -->
                    <li>

                        <a
                            href="<?= htmlspecialchars($baseUrl) ?>/login.php"
                            class="<?= $currentPage === 'login.php' ? 'active' : '' ?>"
                        >
                            <i class="fa-solid fa-user"></i>
                            Login
                        </a>

                    </li>


                    <!-- CUSTOMER REGISTER -->
                    <li>

                        <a
                            href="<?= htmlspecialchars($baseUrl) ?>/register.php"
                            class="<?= $currentPage === 'register.php' ? 'active' : '' ?>"
                        >
                            <i class="fa-solid fa-user-plus"></i>
                            Register
                        </a>

                    </li>

                <?php endif; ?>


                <!-- ADMIN LOGIN -->
                <li>

                    <a
                        href="<?= htmlspecialchars($baseUrl) ?>/admin/login.php"
                        class="admin-login-link"
                    >
                        <i class="fa-solid fa-user-shield"></i>
                        Admin Login
                    </a>

                </li>

            </ul>

        </nav>


        <!-- HEADER ACTIONS -->
        <div class="header-actions">

            <a
                href="<?= htmlspecialchars($baseUrl) ?>/cart.php"
                class="header-icon"
                aria-label="Shopping cart with <?= (int)$cartCount ?> items"
            >

                <i class="fa-solid fa-cart-shopping"></i>

                <span class="cart-count">
                    <?= (int)$cartCount ?>
                </span>

            </a>

        </div>

    </div>

</header>
