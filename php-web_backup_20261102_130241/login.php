<?php
session_start();
require 'db.php';
require 'log_helper.php';

// If already logged in, redirect to the correct dashboard
if (isset($_SESSION['admin_id'])) {
    $stmt = $conn->prepare("SELECT is_superadmin FROM admin WHERE admin_id = ? AND status = 'active'");
    $stmt->bind_param("i", $_SESSION['admin_id']);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row) {
        session_destroy();
        header("Location: login.php");
        exit();
    }
    header("Location: " . ($row['is_superadmin'] == 1 ? "superadmin.php" : "dashboard.php"));
    exit();
}

$loginError = '';

if (isset($_POST['login_submit'])) {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $loginError = "Please enter a valid email and password.";
    } else {
        $stmt = $conn->prepare("
            SELECT admin_id, name, email, password_hash, is_superadmin, status
            FROM admin WHERE email = ? LIMIT 1
        ");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if (!$row) {
            $loginError = "Email not found.";
        } elseif ($row['status'] !== 'active') {
            $loginError = "This account is deactivated. Contact a superadmin.";
        } elseif (!password_verify($password, $row['password_hash'])) {
            $loginError = "Invalid password.";
        } else {
            session_regenerate_id(true);
            $_SESSION['admin_id']       = (int)$row['admin_id'];
            $_SESSION['admin_name']     = $row['name'];
            $_SESSION['admin_email']    = $row['email'];
            $_SESSION['is_superadmin']  = (int)$row['is_superadmin'];

            logActivity($conn, $row['admin_id'], "Logged in");

            header("Location: " . ($row['is_superadmin'] == 1 ? "superadmin.php" : "dashboard.php"));
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KimFresh - Admin Portal (PHP)</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/login.css">
</head>
<body>

    <div class="page">
        <div class="left">
            <img src="logo.png" alt="KimFresh" class="logo">
            <div class="left-text">
                <h1>Control everything</h1>
                <h1 class="pink">from one dashboard.</h1>
                <p>Monitor users, manage products, and monitor real-time delivery fulfillment.</p>
            </div>
            <div class="boxes">
                <div class="box"><h3>Monitor</h3><p>Live deliveries</p></div>
                <div class="box"><h3>Manage</h3><p>Order requests</p></div>
                <div class="box"><h3>Monitor</h3><p>Inventory</p></div>
            </div>
        </div>

        <div class="right">
            <div class="login-box">

                <div class="tabs">
                    <a href="javascript:void(0);" class="tab active">Login</a>
                </div>

                <div class="panel">
                    <h1>Login to your account</h1>
                    <p class="subtitle">Sign in to your KimFresh admin account to continue.</p>

                    <?php if($loginError): ?>
                        <p class="alert-box" style="color: red; background: #fee2e2; padding: 10px; border-radius: 5px;">
                            <?php echo htmlspecialchars($loginError); ?>
                        </p>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <label for="email">Email</label>
                        <div class="input-box">
                            <input type="email" name="email" placeholder="admin@kimfresh.com" required>
                        </div>

                        <label for="password">Password</label>
                        <div class="input-box">
                            <input type="password" name="password" placeholder="Enter your password" required>
                        </div>

                        <button type="submit" name="login_submit" class="btn">Login</button>
                    </form>
                </div>

            </div>
        </div>
    </div>

</body>
</html>