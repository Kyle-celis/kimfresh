<?php
session_start();
require 'db.php';
require 'log_helper.php';

if (isset($_SESSION['admin_id'])) {
    $stmt = $conn->prepare("SELECT is_superadmin FROM admin WHERE admin_id = ?");
    $stmt->bind_param("i", $_SESSION['admin_id']);
    $stmt->execute();
    $is_super = $stmt->get_result()->fetch_assoc()['is_superadmin'] ?? 0;
    
    if ($is_super == 1) {
        header("Location: superadmin.php");
    } else {
        header("Location: dashboard.php");
    }
    exit();
}

$loginError = '';

// --- HANDLE LOGIN ---
if (isset($_POST['login_submit'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT admin_id, name, email, password_hash, is_superadmin FROM admin WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password_hash'])) {
            $_SESSION['admin_id'] = $row['admin_id'];
            $_SESSION['admin_name'] = $row['name'];
            $_SESSION['admin_email'] = $row['email'];
            
            logActivity($conn, $row['admin_id'], "Logged in");

            if ($row['is_superadmin'] == 1) {
                header("Location: superadmin.php");
            } else {
                header("Location: dashboard.php");
            }
            exit();
        } else {
            $loginError = "Invalid password.";
        }
    } else {
        $loginError = "Email not found.";
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

                    <?php if($loginError): ?><p class="alert-box"><?php echo $loginError; ?></p><?php endif; ?>

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