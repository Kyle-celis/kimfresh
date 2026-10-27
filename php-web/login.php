<?php
session_start();
require 'db.php';
require 'log_helper.php';

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
date_default_timezone_set('Asia/Manila');

// If already logged in, redirect based on role
if (isset($_SESSION['admin_id'])) {
    if ($_SESSION['admin_id'] == 1) {
        header("Location: superadmin.php");
    } else {
        header("Location: dashboard.php");
    }
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT admin_id, name, email, password_hash FROM admin WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result && password_verify($password, $result['password_hash'])) {
        $_SESSION['admin_id'] = $result['admin_id'];
        $_SESSION['admin_name'] = $result['name'];
        $_SESSION['admin_email'] = $result['email'];
        
        logActivity($conn, $result['admin_id'], "Admin logged in");
        
        // --- SUPERADMIN REDIRECT RULE ---
        if ($result['admin_id'] == 1) {
            header("Location: superadmin.php"); // Superadmin goes straight to Manage Admins
        } else {
            header("Location: dashboard.php");  // Regular admins go to Dashboard
        }
        exit();
    } else {
        $error = "Invalid email or password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KimFresh - Admin Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/login.css">
</head>
<body>

    <div class="page">
        
        <!-- LEFT SIDE (Brand/Image) -->
        <div class="left">
            <img src="logo.png" alt="KimFresh" class="logo">
            <div class="left-text">
                <span class="eyebrow">KimFresh Admin</span>
                <h1>Manage your <br><span class="pink">Kimchi empire</span></h1>
                <p>Log in to monitor orders, manage drivers, and keep the cold chain running smoothly.</p>
            </div>
            <div class="boxes">
                <div class="box"><h3>Orders</h3><p>Real-time tracking</p></div>
                <div class="box"><h3>Drivers</h3><p>Auto-assignment</p></div>
                <div class="box"><h3>IoT</h3><p>Cold chain</p></div>
            </div>
        </div>

        <!-- RIGHT SIDE (Form) -->
        <div class="right">
            <div class="login-box">
                <div class="panel">
                    <h1>Welcome back</h1>
                    <p class="subtitle">Sign in to your admin account to continue.</p>

                    <?php if ($error): ?>
                        <div class="alert-box"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <label>Email</label>
                        <div class="input-box">
                            <input type="email" name="email" required placeholder="admin@kimfresh.com" autocomplete="email">
                        </div>

                        <label>Password</label>
                        <div class="input-box">
                            <input type="password" name="password" required placeholder="••••••••" autocomplete="current-password">
                        </div>

                        <!-- Remember me and Forgot password REMOVED -->
                        <div style="margin-bottom: 22px;"></div>

                        <button type="submit" class="btn">Sign In</button>
                    </form>
                </div>
            </div>
        </div>
        
    </div>

</body>
</html>