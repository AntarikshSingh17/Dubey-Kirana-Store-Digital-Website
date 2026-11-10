
<?php

session_start();

require_once "../includes/db.php";

if (isset($_SESSION["admin_id"])) {
    header("Location: dashboard.php");
    exit;
}

$error = "";

if (!isset($_SESSION["login_token"])) {
    $_SESSION["login_token"] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["login_token"], $_POST["csrf_token"])
    ) {

        $error = "Invalid request. Please refresh the page.";

    } else {

        $username = trim($_POST["username"] ?? "");
        $password = $_POST["password"] ?? "";

        $stmt = $pdo->prepare(
            "SELECT id, username, password
             FROM admins
             WHERE username = ?
             LIMIT 1"
        );

        $stmt->execute([$username]);

        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin["password"])) {

            session_regenerate_id(true);

            $_SESSION["admin_id"] = $admin["id"];
            $_SESSION["admin_username"] = $admin["username"];

            unset($_SESSION["login_token"]);

            header("Location: dashboard.php");
            exit;

        } else {

            $error = "Invalid username or password.";

        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Login - Dubey Kirana Store</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .login-container {
            background: white;
            width: 380px;
            padding: 35px;
            border-radius: 15px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
        }

        .logo {
            text-align: center;
            font-size: 25px;
            font-weight: bold;
            color: #15803d;
        }

        h2 {
            text-align: center;
            margin-top: 10px;
        }

        .subtitle {
            text-align: center;
            color: #64748b;
            font-size: 14px;
        }

        input {
            width: 100%;
            padding: 13px;
            margin-top: 15px;
            border: 1px solid #d1d5db;
            border-radius: 7px;
        }

        button {
            width: 100%;
            padding: 13px;
            margin-top: 20px;
            background: #15803d;
            color: white;
            border: none;
            border-radius: 7px;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background: #166534;
        }

        .error {
            background: #fee2e2;
            color: #b91c1c;
            padding: 10px;
            border-radius: 6px;
            text-align: center;
        }

    </style>

</head>

<body>

<div class="login-container">

    <div class="logo">
        Dubey Kirana Store
    </div>

    <h2>Admin Login</h2>

    <p class="subtitle">
        Login to manage your store
    </p>

    <?php if ($error): ?>

        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>

    <form method="POST">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars($_SESSION["login_token"]) ?>"
        >

        <input
            type="text"
            name="username"
            placeholder="Admin Username"
            required
        >

        <input
            type="password"
            name="password"
            placeholder="Admin Password"
            required
        >

        <button type="submit">
            Login
        </button>

    </form>

</div>

</body>

</html>
