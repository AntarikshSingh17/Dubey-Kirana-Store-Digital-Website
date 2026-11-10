<?php
require_once "includes/header.php";

// ==========================================
// 1. GET PRODUCT ID
// ==========================================

$product_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

$product_id = ($product_id && $product_id > 0) ? $product_id : 0;


// ==========================================
// 2. FETCH PRODUCT DETAILS
// ==========================================

$product = null;

if ($product_id > 0) {

    $stmt = $pdo->prepare("
        SELECT
            p.*,
            c.category_name
        FROM products p
        LEFT JOIN categories c
            ON p.category_id = c.id
        WHERE p.id = ?
        AND p.status = 'active'
        LIMIT 1
    ");

    $stmt->execute([$product_id]);

    $product = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!-- ==========================================
     PRODUCT DETAILS SECTION
========================================== -->

<?php if ($product): ?>

<section class="section">

    <div class="product-details-container">


        <!-- ==========================================
             PRODUCT IMAGE
        ========================================== -->

        <div class="product-details-image">

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
                >

            <?php else: ?>

                <div class="product-image-placeholder">

                    <i class="fas fa-basket-shopping"></i>

                    <p>No Image Available</p>

                </div>

            <?php endif; ?>

        </div>


        <!-- ==========================================
             PRODUCT INFORMATION
        ========================================== -->

        <div class="product-details-info">


            <!-- STORE BADGE -->

            <span class="product-details-badge">

                <i class="fas fa-store"></i>

                Dubey Kirana Store

            </span>


            <!-- PRODUCT NAME -->

            <h1>

                <?= htmlspecialchars(
                    $product['product_name'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </h1>


            <!-- PRODUCT CATEGORY -->

            <p class="category-label">

                <i class="fas fa-tag"></i>

                Category:

                <?= htmlspecialchars(
                    $product['category_name'] ?? 'Uncategorized',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </p>


            <!-- PRODUCT PRICE -->

            <h2 class="price">

                <?= CURRENCY ?>

                <?= number_format(
                    (float)$product['price'],
                    2
                ) ?>

            </h2>


            <!-- PRODUCT DESCRIPTION -->

            <p class="description">

                <?= nl2br(
                    htmlspecialchars(
                        $product['description'] ?? 'Quality grocery product for your daily needs.',
                        ENT_QUOTES,
                        'UTF-8'
                    )
                ) ?>

            </p>


            <!-- ==========================================
                 STOCK STATUS
            ========================================== -->

            <div class="product-stock-status">


                <?php if ((int)$product['stock_quantity'] > 0): ?>


                    <span class="in-stock">

                        <i class="fas fa-check-circle"></i>

                        Available in Stock

                    </span>


                    <p>

                        Available Quantity:

                        <strong>

                            <?= (int)$product['stock_quantity'] ?>

                        </strong>

                    </p>


                <?php else: ?>


                    <span class="out-of-stock">

                        <i class="fas fa-times-circle"></i>

                        Out of Stock

                    </span>


                <?php endif; ?>


            </div>


            <!-- ==========================================
                 ADD TO CART FORM
            ========================================== -->

            <?php if ((int)$product['stock_quantity'] > 0): ?>


                <form
                    action="actions/add-to-cart.php"
                    method="POST"
                    class="product-details-cart-form"
                >


                    <!-- PRODUCT ID -->

                    <input
                        type="hidden"
                        name="product_id"
                        value="<?= (int)$product['id'] ?>"
                    >


                    <!-- QUANTITY LABEL -->

                    <label for="quantity">

                        Select Quantity

                    </label>


                    <!-- QUANTITY CONTROL -->

                    <div class="quantity-control">


                        <!-- DECREASE BUTTON -->

                        <button
                            type="button"
                            class="quantity-btn"
                            onclick="changeQuantity(-1)"
                            aria-label="Decrease quantity"
                        >

                            −

                        </button>


                        <!-- QUANTITY INPUT -->

                        <input
                            type="number"
                            name="quantity"
                            id="quantity"
                            value="1"
                            min="1"
                            max="<?= (int)$product['stock_quantity'] ?>"
                            required
                        >


                        <!-- INCREASE BUTTON -->

                        <button
                            type="button"
                            class="quantity-btn"
                            onclick="changeQuantity(1)"
                            aria-label="Increase quantity"
                        >

                            +

                        </button>


                    </div>


                    <!-- ADD TO CART BUTTON -->

                    <button
                        type="submit"
                        class="btn add-cart-details-btn"
                    >

                        <i class="fas fa-cart-shopping"></i>

                        Add to Cart

                    </button>


                </form>


            <?php else: ?>


                <!-- OUT OF STOCK BUTTON -->

                <button
                    type="button"
                    class="btn out-of-stock-btn"
                    disabled
                >

                    <i class="fas fa-ban"></i>

                    Currently Unavailable

                </button>


            <?php endif; ?>


            <!-- ==========================================
                 BACK TO PRODUCTS
            ========================================== -->

            <a
                href="products.php"
                class="btn back-products-btn"
            >

                <i class="fas fa-arrow-left"></i>

                Back to Products

            </a>


        </div>

    </div>

</section>


<!-- ==========================================
     QUANTITY CONTROL JAVASCRIPT
========================================== -->

<script>

function changeQuantity(change) {

    const quantityInput = document.getElementById('quantity');

    if (!quantityInput) {
        return;
    }

    const min = parseInt(quantityInput.min) || 1;

    const max = parseInt(quantityInput.max) || 1;

    let value = parseInt(quantityInput.value) || 1;

    value += change;

    if (value < min) {
        value = min;
    }

    if (value > max) {
        value = max;
    }

    quantityInput.value = value;
}


// Validate quantity before submitting

document.addEventListener('DOMContentLoaded', function () {

    const quantityInput = document.getElementById('quantity');

    if (quantityInput) {

        quantityInput.addEventListener('change', function () {

            let value = parseInt(this.value) || 1;

            const min = parseInt(this.min);

            const max = parseInt(this.max);

            if (value < min) {
                value = min;
            }

            if (value > max) {
                value = max;
            }

            this.value = value;

        });

    }

});

</script>


<?php else: ?>


<!-- ==========================================
     PRODUCT NOT FOUND
========================================== -->

<section class="section">

    <div class="product-not-found">


        <i class="fas fa-box-open"></i>


        <h2>

            Product Not Found

        </h2>


        <p>

            Sorry, this product may have been removed
            or is currently unavailable.

        </p>


        <a
            href="products.php"
            class="btn"
        >

            <i class="fas fa-store"></i>

            Browse Products

        </a>


    </div>

</section>


<?php endif; ?>


<?php

// ==========================================
// 3. INCLUDE FOOTER
// ==========================================

require_once "includes/footer.php";

?>