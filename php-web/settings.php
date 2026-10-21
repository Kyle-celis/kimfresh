<?php
session_start();
require 'db.php';
require 'log_helper.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];
$msg = "";
$msg_type = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $new_password = $_POST['password'];

    if (!empty($new_password)) {
        $hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE admin SET name = ?, password_hash = ? WHERE admin_id = ?");
        $stmt->bind_param("ssi", $name, $hash, $admin_id);
    } else {
        $stmt = $conn->prepare("UPDATE admin SET name = ? WHERE admin_id = ?");
        $stmt->bind_param("si", $name, $admin_id);
    }

    if ($stmt->execute()) {
        $_SESSION['admin_name'] = $name;
        logActivity($conn, $admin_id, "Updated admin settings");
        $msg = "Settings updated successfully!";
        $msg_type = "success";
    } else {
        $msg = "Failed to update settings.";
        $msg_type = "error";
    }
}

$stmt = $conn->prepare("SELECT name, email FROM admin WHERE admin_id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Settings</title>
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/responsive.css">
    
    <style>
        :root { --primary: #C34200; --primary-dark: #9a3400; }
        .settings-box { background: white; padding: 30px; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); max-width: 500px; margin-top: 20px; }
        .settings-box label { display: block; font-weight: 700; font-size: 13px; margin-bottom: 8px; color: #555; }
        .settings-box input { width: 100%; height: 44px; padding: 0 16px; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .settings-box button { background: #C34200; color: white; padding: 12px 24px; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; width: 100%; font-size: 14px; }
        .alert { padding: 12px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; }
        .alert.success { background: #dcfce7; color: #166534; }
        .alert.error { background: #fee2e2; color: #991b1b; }
        .readonly-text { color: #888; font-style: italic; font-size: 13px; margin-top: -10px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-brand">
                <img src="logo.png" alt="KimFresh" class="logo">
                <p>Administration</p>
            </div>
            <nav class="menu">
                <a href="dashboard.php">Dashboard</a>
                <a href="orders.php">Orders</a>
                <a href="products.php">Products</a>
                <a href="drivers.php">Drivers</a>
                <a href="settings.php">Settings</a>
                <a href="logs.php">Activity Logs</a>
            </nav>
        </aside>

        <div class="main-area">
            <header class="top-header">
                <div class="profile-menu">
                    <div class="user-profile">
                        <div class="avatar">A</div>
                        <div class="user-info">
                            <p class="user-name"><?php echo $_SESSION['admin_name']; ?></p>
                            <p class="user-role">Administrator</p>
                        </div>
                        <a href="logout.php" class="logout-btn" style="margin-left: 15px; text-decoration: none;">Logout</a>
                    </div>
                </div>
            </header>

            <main class="main-content">
                <p class="overview-label">Admin</p>
                <h1>Settings</h1>

                <div class="settings-box">
                    <?php if($msg): ?>
                        <div class="alert <?php echo $msg_type; ?>"><?php echo $msg; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <label>Email Address (Read-Only)</label>
                        <input type="email" value="<?php echo htmlspecialchars($admin['email']); ?>" disabled>
                        <p class="readonly-text">Email cannot be changed.</p>

                        <label>Display Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($admin['name']); ?>" required>

                        <label>New Password (Leave blank to keep current)</label>
                        <input type="password" name="password" placeholder="Enter new password">

                        <button type="submit">Save Changes</button>
                    </form>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
