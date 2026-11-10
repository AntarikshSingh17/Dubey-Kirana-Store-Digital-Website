<?php

require_once "config/config.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $mobile = trim($_POST['mobile'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!preg_match('/^[0-9]{10}$/', $mobile)) {
        $error = "Enter a valid 10-digit mobile number.";
    } elseif ($password === '') {
        $error = "Please enter your password.";
    } else {

        $stmt = $pdo->prepare("
            SELECT id, customer_name, mobile, email, address, password_hash
            FROM customers
            WHERE mobile = ?
            LIMIT 1
        ");

        $stmt->execute([$mobile]);

        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        if (
            $customer &&
            !empty($customer['password_hash']) &&
            password_verify($password, $customer['password_hash'])
        ) {

            session_regenerate_id(true);

            $_SESSION['customer_id'] = (int)$customer['id'];
            $_SESSION['customer_name'] = $customer['customer_name'];
            $_SESSION['customer_mobile'] = $customer['mobile'];

            header("Location: " . SITE_URL . "my-account.php");
            exit;

        } else {

            $error = "Invalid mobile number or password.";

        }
    }
}

require_once "includes/header.php";

?>

<style>
.customer-login {
    max-width: 450px;
    margin: 60px auto;
    padding: 30px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
}

.customer-login h1 {
    text-align: center;
    margin-bottom: 25px;
}

.customer-login label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
}

.customer-login input {
    width: 100%;
    padding: 12px;
    margin-bottom: 20px;
    border: 1px solid #ddd;
    border-radius: 6px;
    box-sizing: border-box;
}

.login-btn {
    width: 100%;
    padding: 13px;
    background: #198754;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 16px;
}

.login-error {
    background: #ffe5e5;
    color: #b00020;
    padding: 12px;
    margin-bottom: 20px;
    border-radius: 6px;
}
</style>

<div class="customer-login">

    <h1>Customer Login</h1>

    <?php if ($error !== ''): ?>
        <div class="login-error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <label for="mobile">Mobile Number</label>

        <input
            type="tel"
            id="mobile"
            name="mobile"
            maxlength="10"
            pattern="[0-9]{10}"
            required
        >

        <label for="password">Password</label>

        <input
            type="password"
            id="password"
            name="password"
            required
        >

        <button type="submit" class="login-btn">
            Login
        </button>

    </form>

</div>

<?php require_once "includes/footer.php"; ?>