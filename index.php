<?php
require_once "includes/header.php";

// Fetch Categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY id DESC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Popular Products
$stmt = $pdo->query("
    SELECT *
    FROM products
    WHERE status = 'active'
    ORDER BY id DESC
    LIMIT 8
");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- ================= HERO SECTION ================= -->
<section class="hero">
    <div class="hero-content">

        <span class="hero-badge">
            <i class="fas fa-leaf"></i>
            Your Trusted Local Grocery Store
        </span>

        <h1>
            Fresh Groceries.<br>
            <span>Better Living.</span>
        </h1>

        <p>
            Bring freshness to your kitchen with quality groceries,
            everyday essentials, and affordable prices.
        </p>

        <div class="hero-buttons">
            <a href="products.php" class="hero-btn">
                Shop Now
                <i class="fas fa-arrow-right"></i>
            </a>

            <a href="#categories" class="hero-secondary-btn">
                Explore Categories
            </a>
        </div>

        <div class="hero-features">
            <div class="hero-feature">
                <i class="fas fa-check-circle"></i>
                Quality Products
            </div>

            <div class="hero-feature">
                <i class="fas fa-tag"></i>
                Affordable Prices
            </div>

            <div class="hero-feature">
                <i class="fas fa-truck"></i>
                Home Delivery
            </div>
        </div>

    </div>
</section>


<!-- ================= TRUST FEATURES ================= -->
<section class="section trust-section">

    <div class="trust-grid">

        <div class="trust-card">
            <div class="trust-icon">
                <i class="fas fa-seedling"></i>
            </div>

            <div>
                <h3>Fresh & Quality</h3>
                <p>Quality products for your family</p>
            </div>
        </div>

        <div class="trust-card">
            <div class="trust-icon">
                <i class="fas fa-wallet"></i>
            </div>

            <div>
                <h3>Best Prices</h3>
                <p>Affordable daily essentials</p>
            </div>
        </div>

        <div class="trust-card">
            <div class="trust-icon">
                <i class="fas fa-truck-fast"></i>
            </div>

            <div>
                <h3>Fast Delivery</h3>
                <p>Convenient doorstep delivery</p>
            </div>
        </div>

        <div class="trust-card">
            <div class="trust-icon">
                <i class="fas fa-shopping-basket"></i>
            </div>

            <div>
                <h3>Easy Shopping</h3>
                <p>Simple and convenient ordering</p>
            </div>
        </div>

    </div>

</section>


<!-- ================= CATEGORY SECTION ================= -->
<section class="section category-section" id="categories">

    <div class="section-heading">

        <div>
            <span class="section-label">Explore Our Store</span>

            <h2 class="section-title">
                Shop by Category
            </h2>

            <p class="section-subtitle">
                Find everything you need for your daily kitchen.
            </p>
        </div>

        <a href="products.php" class="section-link">
            View All Categories
            <i class="fas fa-arrow-right"></i>
        </a>

    </div>

    <div class="category-grid">

        <?php if (!empty($categories)): ?>

            <?php foreach ($categories as $category): ?>

                <a
                    href="products.php?category=<?= (int)$category['id']; ?>"
                    class="category-card"
                >

                    <div class="category-icon">
                        <i class="fas fa-basket-shopping"></i>
                    </div>

                    <h3>
                        <?= clean($category['category_name']); ?>
                    </h3>

                    <span class="category-explore">
                        Explore
                        <i class="fas fa-arrow-right"></i>
                    </span>

                </a>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <p>No categories available right now.</p>
            </div>

        <?php endif; ?>

    </div>

</section>


<!-- ================= POPULAR PRODUCTS ================= -->
<section class="section products-section">

    <div class="section-heading">

        <div>
            <span class="section-label">Customer Favorites</span>

            <h2 class="section-title">
                Popular Products
            </h2>

            <p class="section-subtitle">
                Discover our latest grocery essentials.
            </p>
        </div>

        <a href="products.php" class="section-link">
            View All Products
            <i class="fas fa-arrow-right"></i>
        </a>

    </div>

    <div class="product-grid">

        <?php if (!empty($products)): ?>

            <?php foreach ($products as $product): ?>

                <div class="product-card">

                    <!-- Product Image -->
                    <div class="product-image-wrapper">

                        <?php if (!empty($product['image'])): ?>

                            <img
                                src="<?= SITE_URL . clean($product['image']); ?>"
                                alt="<?= clean($product['product_name']); ?>"
                                class="product-card-image"
                                loading="lazy"
                            >

                        <?php else: ?>

                            <div class="product-placeholder">
                                <i class="fas fa-image"></i>
                            </div>

                        <?php endif; ?>

                        <span class="product-badge">
                            Popular
                        </span>

                    </div>


                    <!-- Product Information -->
                    <div class="product-card-info">

                        <h3>
                            <?= clean($product['product_name']); ?>
                        </h3>

                        <p class="price">
                            <?= CURRENCY . number_format((float)$product['price'], 2); ?>
                        </p>

                        <!-- Stock Status -->
                        <div class="stock-info">

                            <?php if ((int)$product['stock_quantity'] > 0): ?>

                                <span class="in-stock">
                                    <i class="fas fa-check-circle"></i>
                                    In Stock
                                </span>

                            <?php else: ?>

                                <span class="out-of-stock">
                                    Out of Stock
                                </span>

                            <?php endif; ?>

                        </div>


                        <!-- View Product -->
                        <a
                            href="product-details.php?id=<?= (int)$product['id']; ?>"
                            class="btn view-product-btn"
                        >
                            View Details
                        </a>


                        <!-- Add to Cart -->
                        <?php if ((int)$product['stock_quantity'] > 0): ?>

                            <form
                                action="actions/add-to-cart.php"
                                method="POST"
                                class="add-cart-form"
                            >

                                <input
                                    type="hidden"
                                    name="product_id"
                                    value="<?= (int)$product['id']; ?>"
                                >

                                <input
                                    type="hidden"
                                    name="quantity"
                                    value="1"
                                >

                                <button
                                    type="submit"
                                    class="btn add-cart-btn"
                                >
                                    <i class="fas fa-cart-shopping"></i>
                                    Add to Cart
                                </button>

                            </form>

                        <?php else: ?>

                            <button
                                type="button"
                                class="btn out-of-stock-btn"
                                disabled
                            >
                                Out of Stock
                            </button>

                        <?php endif; ?>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <h3>No Products Available</h3>
                <p>Please check back soon for fresh products.</p>
            </div>

        <?php endif; ?>

    </div>

</section>


<!-- ================= PROMOTIONAL BANNER ================= -->
<section class="section promo-section">

    <div class="promo-content">

        <span class="promo-label">
            Dubey Kirana Store
        </span>

        <h2>
            Your Daily Needs,<br>
            All in One Place.
        </h2>

        <p>
            Make your grocery shopping easier.
            Explore our collection of everyday essentials.
        </p>

        <a href="products.php" class="promo-btn">
            Start Shopping
            <i class="fas fa-arrow-right"></i>
        </a>

    </div>

    <div class="promo-icon">
        <i class="fas fa-shopping-basket"></i>
    </div>

</section>


<!-- ================= WHY CHOOSE US ================= -->
<section class="section why-choose-section">

    <div class="section-heading centered-heading">

        <div>
            <span class="section-label">Our Promise</span>

            <h2 class="section-title">
                Why Choose Dubey Kirana Store?
            </h2>

            <p class="section-subtitle">
                We make your daily grocery shopping simple and convenient.
            </p>
        </div>

    </div>

    <div class="why-choose-grid">

        <div class="why-card">

            <div class="why-icon">
                <i class="fas fa-award"></i>
            </div>

            <h3>Quality Products</h3>

            <p>
                Carefully selected grocery products for your daily needs.
            </p>

        </div>

        <div class="why-card">

            <div class="why-icon">
                <i class="fas fa-indian-rupee-sign"></i>
            </div>

            <h3>Affordable Prices</h3>

            <p>
                Shop your everyday essentials at reasonable prices.
            </p>

        </div>

        <div class="why-card">

            <div class="why-icon">
                <i class="fas fa-truck-fast"></i>
            </div>

            <h3>Home Delivery</h3>

            <p>
                Get your groceries delivered conveniently to your doorstep.
            </p>

        </div>

        <div class="why-card">

            <div class="why-icon">
                <i class="fab fa-whatsapp"></i>
            </div>

            <h3>Easy Ordering</h3>

            <p>
                Enjoy a simple and convenient grocery shopping experience.
            </p>

        </div>

    </div>

</section>


<?php
require_once "includes/footer.php";
?>