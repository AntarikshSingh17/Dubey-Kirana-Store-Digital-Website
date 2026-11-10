
<?php
require_once "includes/header.php";
?>

<style>
.about-page {
    padding: 50px 0;
}

.about-hero {
    background: linear-gradient(135deg, #198754, #146c43);
    color: white;
    padding: 60px 25px;
    text-align: center;
    border-radius: 15px;
    margin-bottom: 40px;
}

.about-hero h1 {
    font-size: 38px;
    margin-bottom: 15px;
}

.about-hero p {
    font-size: 17px;
    max-width: 700px;
    margin: auto;
    line-height: 1.7;
}

.about-section {
    margin-bottom: 35px;
}

.about-section h2 {
    color: #198754;
    margin-bottom: 15px;
}

.about-section p {
    line-height: 1.8;
    color: #555;
}

.about-features {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-top: 25px;
}

.about-feature {
    padding: 25px;
    background: #f8f9fa;
    border-radius: 12px;
    text-align: center;
}

.about-feature i {
    font-size: 35px;
    color: #198754;
    margin-bottom: 15px;
}

.about-feature h3 {
    margin-bottom: 10px;
}

.about-cta {
    text-align: center;
    background: #f1f8f4;
    padding: 35px;
    border-radius: 12px;
}

.about-cta a {
    display: inline-block;
    margin-top: 15px;
    padding: 12px 25px;
    background: #198754;
    color: white;
    text-decoration: none;
    border-radius: 6px;
}

.about-cta a:hover {
    background: #146c43;
}
</style>

<div class="container about-page">

    <section class="about-hero">
        <h1>About Dubey Kirana Store</h1>

        <p>
            Your trusted neighbourhood grocery store, providing
            quality products, daily essentials, and convenient
            shopping for your family.
        </p>
    </section>

    <section class="about-section">
        <h2>Who We Are</h2>

        <p>
            Dubey Kirana Store is a local grocery store located
            at Main Road, Near Post Office, Venkatnagar.
            We aim to make everyday grocery shopping simple,
            convenient, and reliable for our customers.
        </p>

        <p>
            We offer a range of grocery items, household products,
            and daily essentials. Our goal is to provide a
            comfortable shopping experience while maintaining
            quality and customer satisfaction.
        </p>
    </section>

    <section class="about-section">
        <h2>Our Mission</h2>

        <p>
            Our mission is to serve our community with quality
            grocery products, fair prices, and dependable service.
            We believe that customer trust and satisfaction are
            the foundation of a successful local business.
        </p>
    </section>

    <section class="about-section">
        <h2>Why Choose Us?</h2>

        <div class="about-features">

            <div class="about-feature">
                <i class="fa-solid fa-basket-shopping"></i>
                <h3>Daily Essentials</h3>
                <p>
                    A variety of grocery products and household
                    essentials for everyday needs.
                </p>
            </div>

            <div class="about-feature">
                <i class="fa-solid fa-hand-holding-heart"></i>
                <h3>Customer First</h3>
                <p>
                    We value our customers and aim to provide
                    friendly and reliable service.
                </p>
            </div>

            <div class="about-feature">
                <i class="fa-solid fa-truck"></i>
                <h3>Convenient Shopping</h3>
                <p>
                    Browse products online and place your grocery
                    orders conveniently.
                </p>
            </div>

            <div class="about-feature">
                <i class="fa-solid fa-store"></i>
                <h3>Local Store</h3>
                <p>
                    Proudly serving customers in Venkatnagar
                    and the surrounding community.
                </p>
            </div>

        </div>
    </section>

    <section class="about-cta">
        <h2>Start Shopping With Us</h2>

        <p>
            Explore our products and shop for your daily needs.
        </p>

        <a href="<?= SITE_URL ?>products.php">
            Shop Now
        </a>
    </section>

</div>

<?php
require_once "includes/footer.php";
?>