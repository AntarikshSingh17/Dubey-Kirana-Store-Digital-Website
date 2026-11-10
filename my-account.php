
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "config/config.php";

// Check customer login
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit;
}

$customer_id = $_SESSION['customer_id'];

$message = "";
$error = "";

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Update account details
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $error = "Invalid request. Please try again.";
    } else {

        $customer_name = trim($_POST['customer_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');

        if (empty($customer_name) || empty($address)) {
            $error = "Name and delivery address are required.";
        } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";
        } else {

            try {
                $stmt = $pdo->prepare("
                    UPDATE customers
                    SET customer_name = ?,
                        email = ?,
                        address = ?
                    WHERE id = ?
                ");

                $stmt->execute([
                    $customer_name,
                    $email,
                    $address,
                    $customer_id
                ]);

                $_SESSION['customer_name'] = $customer_name;

                $message = "Your account details have been updated successfully.";

            } catch (PDOException $e) {
                $error = "Unable to update your account. Please try again.";
            }
        }
    }
}

// Fetch customer details
$stmt = $pdo->prepare("
    SELECT id, customer_name, mobile, email, address
    FROM customers
    WHERE id = ?
");

$stmt->execute([$customer_id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    session_destroy();
    header("Location: login.php");
    exit;
}

require_once "includes/header.php";

?>

<style>
.account-container {
    max-width: 1100px;
    margin: 50px auto;
    padding: 20px;
}

.account-heading {
    margin-bottom: 30px;
}

.account-heading h1 {
    font-size: 32px;
    color: #222;
}

.account-heading p {
    color: #777;
}

.account-layout {
    display: grid;
    grid-template-columns: 250px 1fr;
    gap: 25px;
}

.account-sidebar,
.account-content {
    background: #fff;
    border: 1px solid #eee;
    border-radius: 12px;
    padding: 25px;
}

.account-sidebar h3 {
    margin-bottom: 20px;
}

.account-sidebar a {
    display: block;
    padding: 12px;
    text-decoration: none;
    color: #333;
    border-radius: 6px;
    margin-bottom: 8px;
}

.account-sidebar a:hover {
    background: #f1f8e9;
    color: #388e3c;
}

.account-content h2 {
    margin-bottom: 25px;
    font-size: 24px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 7px;
    font-size: 15px;
    box-sizing: border-box;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #4caf50;
}

.form-group textarea {
    min-height: 100px;
    resize: vertical;
}

.save-btn {
    background: #388e3c;
    color: white;
    padding: 12px 25px;
    border: none;
    border-radius: 7px;
    cursor: pointer;
    font-size: 15px;
    font-weight: 600;
}

.save-btn:hover {
    background: #2e7d32;
}

.success-message {
    background: #e8f5e9;
    color: #2e7d32;
    padding: 12px;
    border-radius: 7px;
    margin-bottom: 20px;
}

.error-message {
    background: #ffebee;
    color: #c62828;
    padding: 12px;
    border-radius: 7px;
    margin-bottom: 20px;
}

@media (max-width: 768px) {
    .account-layout {
        grid-template-columns: 1fr;
    }

    .account-container {
        margin: 20px auto;
    }
}
</style>

<div class="account-container">

    <div class="account-heading">
        <h1>My Account</h1>
        <p>Manage your personal information and delivery address.</p>
    </div>

    <div class="account-layout">

        <!-- Sidebar -->
        <div class="account-sidebar">

            <h3>My Profile</h3>

            <a href="my-account.php">👤 My Account</a>

            <a href="my-orders.php">📦 My Orders</a>

            <a href="about.php">ℹ️ About Us</a>

            <a href="logout.php">🚪 Logout</a>

        </div>

        <!-- Account Details -->
        <div class="account-content">

            <h2>Account Information</h2>

            <?php if (!empty($message)): ?>
                <div class="success-message">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="my-account.php">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"
                >

                <div class="form-group">
                    <label>Full Name</label>

                    <input
                        type="text"
                        name="customer_name"
                        value="<?php echo htmlspecialchars($customer['customer_name'] ?? ''); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Mobile Number</label>

                    <input
                        type="text"
                        value="<?php echo htmlspecialchars($customer['mobile'] ?? ''); ?>"
                        readonly
                    >
                </div>

                <div class="form-group">
                    <label>Email Address</label>

                    <input
                        type="email"
                        name="email"
                        value="<?php echo htmlspecialchars($customer['email'] ?? ''); ?>"
                    >
                </div>

                <div class="form-group">
                    <label>Delivery Address</label>

                    <textarea
                        name="address"
                        required
                    ><?php echo htmlspecialchars($customer['address'] ?? ''); ?></textarea>
                </div>

                <button type="submit" class="save-btn">
                    Save Changes
                </button>

            </form>

        </div>

    </div>

</div>

<?php require_once "includes/footer.php"; ?>