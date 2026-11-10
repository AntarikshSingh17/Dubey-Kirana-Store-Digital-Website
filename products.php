<?php
require_once "includes/header.php";

// ==========================================
// 1. GET SEARCH KEYWORD
// ==========================================

$search = trim($_GET['search'] ?? '');


// ==========================================
// 2. GET SELECTED CATEGORY
// ==========================================

$category_id = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT);
$category_id = $category_id ?: 0;


// ==========================================
// 3. GET SORTING OPTION
// ==========================================

$sort = $_GET['sort'] ?? 'latest';

$allowedSorts = [
    'latest'     => 'id DESC',
    'price_low'  => 'price ASC',
    'price_high' => 'price DESC',
    'name_az'    => 'product_name ASC'
];

$orderBy = $allowedSorts[$sort] ?? 'id DESC';


// ==========================================
// 4. FETCH ALL CATEGORIES
// ==========================================

$categoryStmt = $pdo->query("
    SELECT id, category_name
    FROM categories
    ORDER BY category_name ASC
");

$categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);


// ==========================================
// 5. BUILD PRODUCT QUERY
// ==========================================

$sql = "
    SELECT *
    FROM products
    WHERE status = 'active'
";

$params = [];


// CATEGORY FILTER

if ($category_id > 0) {

    $sql .= " AND category_id = ?";

    $params[] = $category_id;

}


// SEARCH FILTER

if ($search !== '') {

    $sql .= " AND product_name LIKE ?";

    $params[] = "%" . $search . "%";

}


// SORT PRODUCTS

$sql .= " ORDER BY " . $orderBy;


// ==========================================
// 6. EXECUTE PRODUCT QUERY
// ==========================================

$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalProducts = count($products);

?>

<!-- ==========================================
     PRODUCTS HERO SECTION
========================================== -->

<section class="products-hero">

    <div class="products-hero-content">

        <span class="hero-badge">
            Dubey Kirana Store
        </span>

        <h1>Our Grocery Products</h1>

        <p>
            Fresh, quality and affordable products
            for your everyday needs.
        </p>

    </div>

</section>


<!-- ==========================================
     PRODUCTS MAIN SECTION
========================================== -->

<section class="section">

    <!-- PRODUCTS TOOLBAR -->

    <div class="products-toolbar">


        <!-- SEARCH FORM -->

        <form
            action="products.php"
            method="GET"
            class="product-search-form"
        >

            <?php if ($category_id > 0): ?>

                <input
                    type="hidden"
                    name="category"
                    value="<?= (int)$category_id ?>"
                >

            <?php endif; ?>


            <input
                type="hidden"
                name="sort"
                value="<?= htmlspecialchars($sort, ENT_QUOTES, 'UTF-8') ?>"
            >


            <input
                type="text"
                name="search"
                placeholder="Search grocery products..."
                value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
            >


            <button type="submit">

                <i class="fas fa-search"></i>

                Search

            </button>

        </form>


        <!-- CATEGORY FILTER -->

        <form
            action="products.php"
            method="GET"
            class="category-filter-form"
        >

            <?php if ($search !== ''): ?>

                <input
                    type="hidden"
                    name="search"
                    value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
                >

            <?php endif; ?>


            <input
                type="hidden"
                name="sort"
                value="<?= htmlspecialchars($sort, ENT_QUOTES, 'UTF-8') ?>"
            >


            <select
                name="category"
                onchange="this.form.submit()"
            >

                <option value="0">
                    All Categories
                </option>


                <?php foreach ($categories as $category): ?>

                    <option
                        value="<?= (int)$category['id'] ?>"
                        <?= $category_id == $category['id'] ? 'selected' : '' ?>
                    >

                        <?= htmlspecialchars(
                            $category['category_name'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </option>

                <?php endforeach; ?>

            </select>

        </form>


        <!-- SORTING FORM -->

        <form
            action="products.php"
            method="GET"
            class="sort-form"
        >

            <?php if ($search !== ''): ?>

                <input
                    type="hidden"
                    name="search"
                    value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
                >

            <?php endif; ?>


            <?php if ($category_id > 0): ?>

                <input
                    type="hidden"
                    name="category"
                    value="<?= (int)$category_id ?>"
                >

            <?php endif; ?>


            <select
                name="sort"
                onchange="this.form.submit()"
            >

                <option
                    value="latest"
                    <?= $sort === 'latest' ? 'selected' : '' ?>
                >
                    Latest Products
                </option>


                <option
                    value="price_low"
                    <?= $sort === 'price_low' ? 'selected' : '' ?>
                >
                    Price: Low to High
                </option>


                <option
                    value="price_high"
                    <?= $sort === 'price_high' ? 'selected' : '' ?>
                >
                    Price: High to Low
                </option>


                <option
                    value="name_az"
                    <?= $sort === 'name_az' ? 'selected' : '' ?>
                >
                    Name: A to Z
                </option>

            </select>

        </form>

    </div>


    <!-- ==========================================
         PRODUCTS HEADING
    ========================================== -->

    <div class="products-heading">

        <div>

            <h2>

                <?php if ($search !== ''): ?>

                    Search Results

                <?php elseif ($category_id > 0): ?>

                    Filtered Products

                <?php else: ?>

                    All Products

                <?php endif; ?>

            </h2>


            <p>

                <?= $totalProducts ?>

                <?= $totalProducts === 1 ? 'product' : 'products' ?>

                found

            </p>

        </div>


        <!-- CLEAR FILTERS -->

        <?php if ($search !== '' || $category_id > 0 || $sort !== 'latest'): ?>

            <a
                href="products.php"
                class="btn"
            >

                <i class="fas fa-filter-circle-xmark"></i>

                Clear Filters

            </a>

        <?php endif; ?>

    </div>


    <!-- ==========================================
         PRODUCT GRID
    ========================================== -->

    <div class="product-grid">


        <?php if (!empty($products)): ?>


            <?php foreach ($products as $product): ?>


                <div class="product-card">


                    <!-- PRODUCT IMAGE -->

                    <div class="product-image-wrapper">


                        <?php if (!empty($product['image'])): ?>


                            <img
                                src="<?= SITE_URL . htmlspecialchars(
                                    $product['image'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                alt="<?= htmlspecialchars(
                                    $product['product_name'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                class="product-card-image"
                                loading="lazy"
                            >


                        <?php else: ?>


                            <div class="no-product-image">

                                <i class="fas fa-basket-shopping"></i>

                            </div>


                        <?php endif; ?>


                    </div>


                    <!-- PRODUCT INFORMATION -->

                    <div class="product-card-info">


                        <!-- PRODUCT NAME -->

                        <h3>

                            <?= htmlspecialchars(
                                $product['product_name'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </h3>


                        <!-- PRODUCT PRICE -->

                        <p class="price">

                            <?= CURRENCY ?>

                            <?= number_format(
                                (float)$product['price'],
                                2
                            ) ?>

                        </p>


                        <!-- STOCK STATUS -->

                        <p class="stock-info">


                            <?php if ((int)$product['stock_quantity'] > 0): ?>


                                <span class="in-stock">

                                    <i class="fas fa-check-circle"></i>

                                    In Stock:

                                    <?= (int)$product['stock_quantity'] ?>

                                </span>


                            <?php else: ?>


                                <span class="out-of-stock">

                                    <i class="fas fa-times-circle"></i>

                                    Out of Stock

                                </span>


                            <?php endif; ?>


                        </p>


                        <!-- VIEW PRODUCT DETAILS -->

                        <a
                            href="product-details.php?id=<?= (int)$product['id'] ?>"
                            class="btn"
                        >

                            <i class="fas fa-eye"></i>

                            View Details

                        </a>


                        <!-- ADD TO CART -->

                        <?php if ((int)$product['stock_quantity'] > 0): ?>


                            <form
                                action="actions/add-to-cart.php"
                                method="POST"
                            >


                                <input
                                    type="hidden"
                                    name="product_id"
                                    value="<?= (int)$product['id'] ?>"
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

                                <i class="fas fa-ban"></i>

                                Out of Stock

                            </button>


                        <?php endif; ?>


                    </div>


                </div>


            <?php endforeach; ?>


        <?php else: ?>


            <!-- ==========================================
                 NO PRODUCTS FOUND
            ========================================== -->


            <div class="no-products">


                <i class="fas fa-box-open"></i>


                <h3>
                    No Products Found
                </h3>


                <p>
                    Sorry, we couldn't find any products matching your search.
                    Try another product name or category.
                </p>


                <a
                    href="products.php"
                    class="btn"
                >

                    <i class="fas fa-store"></i>

                    View All Products

                </a>


            </div>


        <?php endif; ?>


    </div>

</section>


<?php

// ==========================================
// 7. INCLUDE FOOTER
// ==========================================

require_once "includes/footer.php";

?>