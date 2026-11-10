
<?php

require_once "config/config.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = "";
$success = "";

$customer_name = "";
$mobile = "";
$email = "";
$address = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $customer_name = trim($_POST['customer_name'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');

    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // ==========================================
    // VALIDATION
    // ==========================================

    if ($customer_name === '') {

        $error = "Please enter your full name.";

    } elseif (strlen($customer_name) > 100) {

        $error = "Name cannot exceed 100 characters.";

    } elseif (!preg_match('/^[0-9]{10}$/', $mobile)) {

        $error = "Please enter a valid 10-digit mobile number.";

    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } elseif (strlen($password) < 8) {

        $error = "Password must contain at least 8 characters.";

    } elseif ($password !== $confirm_password) {

        $error = "Passwords do not match.";

    } else {

        try {

            // ==========================================
            // CHECK EXISTING CUSTOMER
            // ==========================================

            $stmt = $pdo->prepare("
                SELECT
                    id,
                    customer_name,
                    mobile,
                    email,
                    address,
                    password_hash
                FROM customers
                WHERE mobile = ?
                LIMIT 1
            ");

            $stmt->execute([$mobile]);

            $existing_customer = $stmt->fetch(PDO::FETCH_ASSOC);


            // ==========================================
            // HASH PASSWORD
            // ==========================================

            $password_hash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );


            // ==========================================
            // EXISTING CUSTOMER
            // ==========================================

            if ($existing_customer) {

                if (!empty($existing_customer['password_hash'])) {

                    $error = "An account already exists with this mobile number. Please login.";

                } else {

                    // Existing checkout customer:
                    // Set password for their existing account.

                    $stmt = $pdo->prepare("
                        UPDATE customers
                        SET
                            customer_name = ?,
                            email = ?,
                            address = ?,
                            password_hash = ?
                        WHERE id = ?
                    ");

                    $stmt->execute([
                        $customer_name,
                        $email !== '' ? $email : null,
                        $address !== '' ? $address : $existing_customer['address'],
                        $password_hash,
                        $existing_customer['id']
                    ]);

                    session_regenerate_id(true);

                    $_SESSION['customer_id'] =
                        (int)$existing_customer['id'];

                    $_SESSION['customer_name'] =
                        $customer_name;

                    $_SESSION['customer_mobile'] =
                        $mobile;

                    header(
                        "Location: " .
                        SITE_URL .
                        "my-account.php"
                    );

                    exit;
                }


            } else {

                // ==========================================
                // NEW CUSTOMER REGISTRATION
                // ==========================================

                $stmt = $pdo->prepare("
                    INSERT INTO customers
                    (
                        customer_name,
                        mobile,
                        email,
                        address,
                        password_hash
                    )
                    VALUES (?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $customer_name,
                    $mobile,
                    $email !== '' ? $email : null,
                    $address,
                    $password_hash
                ]);

                $customer_id = (int)$pdo->lastInsertId();


                // ==========================================
                // CREATE CUSTOMER SESSION
                // ==========================================

                session_regenerate_id(true);

                $_SESSION['customer_id'] = $customer_id;

                $_SESSION['customer_name'] = $customer_name;

                $_SESSION['customer_mobile'] = $mobile;


                // ==========================================
                // REDIRECT TO ACCOUNT
                // ==========================================

                header(
                    "Location: " .
                    SITE_URL .
                    "my-account.php"
                );

                exit;

            }

        } catch (PDOException $e) {

            $error = "Registration failed. Please try again.";

        }
    }
}

require_once "includes/header.php";

?>


<style>

.register-container {
    max-width: 550px;
    margin: 50px auto;
    padding: 30px;
    background: #ffffff;
    border-radius: 15px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.08);
}

.register-container h1 {
    text-align: center;
    margin-bottom: 10px;
    color: #198754;
}

.register-subtitle {
    text-align: center;
    color: #777;
    margin-bottom: 30px;
}

.register-form .form-group {
    margin-bottom: 20px;
}

.register-form label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
}

.register-form input,
.register-form textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 7px;
    font-size: 15px;
    box-sizing: border-box;
}

.register-form textarea {
    min-height: 90px;
    resize: vertical;
}

.register-btn {
    width: 100%;
    padding: 14px;
    background: #198754;
    color: white;
    border: none;
    border-radius: 7px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
}

.register-btn:hover {
    background: #146c43;
}

.register-error {
    background: #ffe5e5;
    color: #b00020;
    padding: 13px;
    border-radius: 7px;
    margin-bottom: 20px;
}

.register-login {
    text-align: center;
    margin-top: 25px;
}

.register-login a {
    color: #198754;
    font-weight: bold;
    text-decoration: none;
}

@media(max-width: 600px) {

    .register-container {
        margin: 25px 15px;
        padding: 20px;
    }

}

</style>


<div class="container">

    <div class="register-container">

        <h1>
            <i class="fa-solid fa-user-plus"></i>
            Create Account
        </h1>

        <p class="register-subtitle">
            Join Dubey Kirana Store
        </p>


        <?php if ($error !== ''): ?>

            <div class="register-error">

                <?= htmlspecialchars(
                    $error,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            action="register.php"
            class="register-form"
        >


            <!-- CUSTOMER NAME -->

            <div class="form-group">

                <label for="customer_name">
                    Full Name *
                </label>

                <input
                    type="text"
                    id="customer_name"
                    name="customer_name"
                    maxlength="100"
                    value="<?= htmlspecialchars(
                        $customer_name,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    required
                >

            </div>


            <!-- MOBILE -->

            <div class="form-group">

                <label for="mobile">
                    Mobile Number *
                </label>

                <input
                    type="tel"
                    id="mobile"
                    name="mobile"
                    maxlength="10"
                    pattern="[0-9]{10}"
                    value="<?= htmlspecialchars(
                        $mobile,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    required
                >

            </div>


            <!-- EMAIL -->

            <div class="form-group">

                <label for="email">
                    Email Address
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= htmlspecialchars(
                        $email,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

            </div>


            <!-- ADDRESS -->

            <div class="form-group">

                <label for="address">
                    Delivery Address
                </label>

                <textarea
                    id="address"
                    name="address"
                ><?= htmlspecialchars(
                    $address,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?></textarea>

            </div>


            <!-- PASSWORD -->

            <div class="form-group">

                <label for="password">
                    Password *
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    minlength="8"
                    required
                >

            </div>


            <!-- CONFIRM PASSWORD -->

            <div class="form-group">

                <label for="confirm_password">
                    Confirm Password *
                </label>

                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    minlength="8"
                    required
                >

            </div>


            <!-- REGISTER BUTTON -->

            <button
                type="submit"
                class="register-btn"
            >

                <i class="fa-solid fa-user-check"></i>

                Create Account

            </button>


        </form>


        <!-- LOGIN LINK -->

        <div class="register-login">

            Already have an account?

            <a href="<?= SITE_URL ?>login.php">
                Login Here
            </a>

        </div>

    </div>

</div>


<?php

require_once "includes/footer.php";

?>
