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

// Handle Admin Info Update
if (isset($_POST['update_profile'])) {
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
        $msg = "Profile updated successfully!";
        $msg_type = "success";
    }
}

// Handle Store Schedule Update
if (isset($_POST['update_schedule'])) {
    $open_time = $_POST['open_time'];
    $close_time = $_POST['close_time'];
    
    $stmt = $conn->prepare("UPDATE store_settings SET open_time = ?, close_time = ?, manual_override = NULL WHERE id = 1");
    $stmt->bind_param("ss", $open_time, $close_time);
    
    if ($stmt->execute()) {
        logActivity($conn, $admin_id, "Updated Store Schedule to $open_time - $close_time");
        $msg = "Store schedule updated successfully!";
        $msg_type = "success";
    }
}

// Fetch Admin Data
$stmt = $conn->prepare("SELECT name, email FROM admin WHERE admin_id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

// Fetch Store Settings
$store = $conn->query("SELECT * FROM store_settings WHERE id = 1")->fetch_assoc();
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
        .settings-box { background: white; padding: 30px; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); max-width: 600px; margin-top: 20px; margin-bottom: 20px; }
        .settings-box h3 { margin-top: 0; margin-bottom: 20px; color: #333; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .settings-box label { display: block; font-weight: 700; font-size: 13px; margin-bottom: 8px; color: #555; }
        .settings-box input { width: 100%; height: 44px; padding: 0 16px; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 20px; font-size: 14px; outline: none; }
        .settings-box input:focus { border-color: #C34200; }
        .settings-box button { background: #C34200; color: white; padding: 12px 24px; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; width: 100%; font-size: 14px; }
        .alert { padding: 12px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; }
        .alert.success { background: #dcfce7; color: #166534; }
        .alert.error { background: #fee2e2; color: #991b1b; }
        .readonly-text { color: #888; font-style: italic; font-size: 13px; margin-top: -10px; margin-bottom: 20px; }
        
        /* PROFILE DROPDOWN STYLES */
        .profile-menu { position: relative; }
        .profile-trigger { display: flex; align-items: center; gap: 10px; background: transparent; border: none; cursor: pointer; padding: 8px; border-radius: 10px; transition: 0.2s; }
        .profile-trigger:hover { background: var(--primary-soft); }
        .profile-dropdown { position: absolute; top: 100%; right: 0; background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); width: 200px; padding: 8px; display: none; z-index: 100; border: 1px solid #eee; }
        .profile-dropdown.show { display: block; }
        .dropdown-account { padding: 12px; border-bottom: 1px solid #eee; margin-bottom: 8px; }
        .dropdown-account strong { display: block; font-size: 14px; color: #333; }
        .dropdown-account span { font-size: 12px; color: #777; }
        .dropdown-item { display: flex; align-items: center; gap: 10px; padding: 10px 12px; text-decoration: none; color: #333; font-size: 13px; font-weight: 600; border-radius: 8px; transition: 0.2s; }
        .dropdown-item:hover { background: #fdf0e6; color: #C34200; }
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
                <?php if ($_SESSION['admin_id'] == 1): ?>
                    <a href="superadmin.php">Manage Admins</a>
                <?php endif; ?>
            </nav>
        </aside>

        <div class="main-area">
            <header class="top-header">
                <div class="profile-menu" id="profileMenu">
                    <button type="button" class="profile-trigger" onclick="toggleProfileMenu(event)">
                        <div class="avatar">A</div>
                        <div class="user-info" style="text-align: left;">
                            <p class="user-name" style="margin: 0;"><?php echo $_SESSION['admin_name']; ?></p>
                            <p class="user-role" style="margin: 0;">Administrator</p>
                        </div>
                        <span style="font-size: 18px; color: #999; margin-left: 5px;">⌄</span>
                    </button>
                    <div class="profile-dropdown" id="profileDropdown">
                        <div class="dropdown-account">
                            <strong><?php echo $_SESSION['admin_name']; ?></strong>
                            <span>admin@kimfresh.com</span>
                        </div>
                        <a href="settings.php" class="dropdown-item">Settings</a>
                        <a href="logs.php" class="dropdown-item">Activity Logs</a>
                        <a href="logout.php" class="dropdown-item" style="color: #C34200;">Logout</a>
                    </div>
                </div>
            </header>

            <main class="main-content">
                <p class="overview-label">Admin</p>
                <h1>Settings</h1>

                <?php if($msg): ?>
                    <div class="alert <?php echo $msg_type; ?>" style="max-width: 600px;"><?php echo $msg; ?></div>
                <?php endif; ?>

                <!-- PROFILE SETTINGS -->
                <div class="settings-box">
                    <h3>Admin Profile</h3>
                    <form method="POST">
                        <label>Email Address (Read-Only)</label>
                        <input type="email" value="<?php echo htmlspecialchars($admin['email']); ?>" disabled>
                        <p class="readonly-text">Email cannot be changed.</p>

                        <label>Display Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($admin['name']); ?>" required>

                        <label>New Password (Leave blank to keep current)</label>
                        <input type="password" name="password" placeholder="Enter new password">

                        <button type="submit" name="update_profile">Save Profile</button>
                    </form>
                </div>

                <!-- STORE SCHEDULE -->
                <div class="settings-box">
                    <h3>Store Operating Hours</h3>
                    <form method="POST">
                        <div style="display: flex; gap: 20px; margin-bottom: 20px;">
                            <div style="flex: 1;">
                                <label>Opening Time</label>
                                <input type="time" name="open_time" value="<?php echo $store['open_time']; ?>" required>
                            </div>
                            <div style="flex: 1;">
                                <label>Closing Time</label>
                                <input type="time" name="close_time" value="<?php echo $store['close_time']; ?>" required>
                            </div>
                        </div>
                        <button type="submit" name="update_schedule">Update Schedule</button>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <script>
        function toggleProfileMenu(event) {
            event.stopPropagation();
            document.getElementById('profileDropdown').classList.toggle('show');
        }
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('profileMenu');
            if (!menu.contains(event.target)) {
                document.getElementById('profileDropdown').classList.remove('show');
            }
        });
    </script>
</body>
</html>