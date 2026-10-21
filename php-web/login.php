<?php
session_start();
require 'db.php';

$loginError = '';
$signupError = '';
$signupSuccess = '';
$activeTab = 'login'; // Default tab

// --- HANDLE LOGIN ---
if (isset($_POST['login_submit'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT admin_id, name, password_hash FROM admin WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password_hash'])) {
            $_SESSION['admin_id'] = $row['admin_id'];
            $_SESSION['admin_name'] = $row['name'];
            
            // Log the activity
            require 'log_helper.php';
            logActivity($conn, $row['admin_id'], "Admin logged in");
            
            header("Location: dashboard.php");
            exit();
        } else {
            $loginError = "Invalid password.";
        }
    } else {
        $loginError = "Email not found.";
    }
}

// --- HANDLE SIGNUP ---
if (isset($_POST['signup_submit'])) {
    $activeTab = 'signup'; // Stay on signup tab if there is an error
    $name = $_POST['name'];
    $email = $_POST['su_email'];
    $password = $_POST['su_password'];

    // Check if email exists
    $check = $conn->prepare("SELECT admin_id FROM admin WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $signupError = "Email already registered.";
    } else {
        // Hash password and insert
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO admin (name, email, password_hash) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $hash);
        
        if ($stmt->execute()) {
            $signupSuccess = "Account created! You can now log in.";
            $activeTab = 'login'; // Switch back to login on success
        } else {
            $signupError = "Failed to create account.";
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
    <style>
        /* A tiny bit of CSS to hide/show the panels */
        .hidden { display: none; }
    </style>
</head>
<body>

    <div class="page">
        <div class="left">
            <img src="/logo.png" alt="KimFresh" class="logo">
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
                    <a href="javascript:void(0);" id="tabLogin" class="tab <?php echo $activeTab == 'login' ? 'active' : ''; ?>" onclick="switchTab('login')">Login</a>
                    <a href="javascript:void(0);" id="tabSignup" class="tab <?php echo $activeTab == 'signup' ? 'active' : ''; ?>" onclick="switchTab('signup')">Sign up</a>
                </div>

                <!-- LOGIN PANEL -->
                <div id="loginPanel" class="panel <?php echo $activeTab == 'signup' ? 'hidden' : ''; ?>">
                    <h1>Login to your account</h1>
                    <p class="subtitle">Sign in to your KimFresh admin account to continue.</p>

                    <?php if($loginError): ?><p class="alert-box"><?php echo $loginError; ?></p><?php endif; ?>
                    <?php if($signupSuccess): ?><p class="alert-box success"><?php echo $signupSuccess; ?></p><?php endif; ?>

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

                <!-- SIGNUP PANEL -->
                <div id="signupPanel" class="panel <?php echo $activeTab == 'login' ? 'hidden' : ''; ?>">
                    <h1>Create an account</h1>
                    <p class="subtitle">Set up your KimFresh admin access.</p>

                    <?php if($signupError): ?><p class="alert-box"><?php echo $signupError; ?></p><?php endif; ?>

                    <form method="POST" action="">
                        <label for="suName">Full name</label>
                        <div class="input-box">
                            <input type="text" name="name" id="suName" placeholder="Juan Dela Cruz" required>
                        </div>

                        <label for="suEmail">Email</label>
                        <div class="input-box">
                            <input type="email" name="su_email" id="suEmail" placeholder="admin@kimfresh.com" required>
                        </div>

                        <label for="suPassword">Password</label>
                        <div class="input-box">
                            <input type="password" name="su_password" id="suPassword" placeholder="Create a strong password" required>
                        </div>

                        <button type="submit" name="signup_submit" class="btn">Sign Up</button>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <script>
        // Simple JavaScript to toggle the tabs visually
        function switchTab(tab) {
            const isLogin = tab === 'login';
            document.getElementById('loginPanel').classList.toggle('hidden', !isLogin);
            document.getElementById('signupPanel').classList.toggle('hidden', isLogin);
            document.getElementById('tabLogin').classList.toggle('active', isLogin);
            document.getElementById('tabSignup').classList.toggle('active', !isLogin);
        }
    </script>
</body>
</html>