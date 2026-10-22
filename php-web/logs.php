<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$current_admin_id = $_SESSION['admin_id'];
$is_superadmin = ($current_admin_id == 1);

// --- HANDLE CSV EXPORT ---
if (isset($_POST['export_csv'])) {
    // 1. Log the activity
    require 'log_helper.php';
    logActivity($conn, $current_admin_id, "Exported Activity Logs to CSV");

    // 2. Set headers for CSV download
    $filename = "KimFresh_Activity_Logs_" . date('Y-m-d') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    // 3. Open output stream
    $output = fopen('php://output', 'w');

    // 4. Add Column Headers
    fputcsv($output, array('Log ID', 'Admin Name', 'Action', 'Timestamp'));

    // 5. Fetch data based on role
    if ($is_superadmin) {
        // Superadmin sees everything
        $logs_export = $conn->query("
            SELECT l.log_id, a.name as admin_name, l.action, l.timestamp 
            FROM activity_logs l
            LEFT JOIN admin a ON l.admin_id = a.admin_id
            ORDER BY l.timestamp DESC
        ");
    } else {
        // Normal Admins only see their own logs
        $logs_export = $conn->query("
            SELECT l.log_id, a.name as admin_name, l.action, l.timestamp 
            FROM activity_logs l
            LEFT JOIN admin a ON l.admin_id = a.admin_id
            WHERE l.admin_id = $current_admin_id
            ORDER BY l.timestamp DESC
        ");
    }
    
    while ($row = $logs_export->fetch_assoc()) {
        $admin = $row['admin_name'] ? $row['admin_name'] : 'System';
        fputcsv($output, array(
            $row['log_id'], 
            $admin, 
            $row['action'], 
            date('M d, Y h:i A', strtotime($row['timestamp']))
        ));
    }

    fclose($output);
    exit();
}

// Fetch logs for display based on role
if ($is_superadmin) {
    $logs = $conn->query("
        SELECT l.*, a.name as admin_name 
        FROM activity_logs l
        LEFT JOIN admin a ON l.admin_id = a.admin_id
        ORDER BY l.timestamp DESC
    ");
} else {
    $logs = $conn->query("
        SELECT l.*, a.name as admin_name 
        FROM activity_logs l
        LEFT JOIN admin a ON l.admin_id = a.admin_id
        WHERE l.admin_id = $current_admin_id
        ORDER BY l.timestamp DESC
    ");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs</title>
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/responsive.css">
    
    <style>
        :root { --primary: #C34200; }
        .table-section { background: white; border-radius: 16px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; }
        
        th { 
            text-align: left; 
            padding: 15px; 
            font-size: 13px; 
            color: #666; 
            border-bottom: 1px solid #eee; 
            background: transparent !important; 
            font-weight: 700;
        }
        
        td { padding: 15px; border-bottom: 1px solid #f5f5f5; font-size: 13px; }
        
        .page-head { display: flex; justify-content: space-between; align-items: center; }
        .btn-export { background: #C34200; color: white; padding: 12px 20px; border: none; border-radius: 8px; font-weight: 700; font-size: 13px; cursor: pointer; transition: 0.2s; }
        .btn-export:hover { background: #9a3400; }

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

        /* Mobile Table and Header Fixes */
        @media (max-width: 768px) {
            .page-head {
                flex-direction: column !important;
                align-items: stretch !important;
                gap: 15px !important;
            }
            
            .page-head form {
                width: 100% !important;
            }
            
            .page-head .btn-export {
                width: 100% !important;
                padding: 12px !important;
            }

            .table-section {
                padding: 10px !important;
            }

            table {
                width: 100% !important;
                table-layout: fixed;
            }

            th, td {
                padding: 10px 8px !important;
                font-size: 11px !important;
                word-wrap: break-word;
                white-space: normal !important;
            }

            /* Hide the timestamp column to make room for actions */
            th:nth-child(3), td:nth-child(3) {
                display: none !important; 
            }
        }
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
                    </div>
                        <a href="settings.php" class="dropdown-item">Settings</a>
                        <a href="logs.php" class="dropdown-item">Activity Logs</a>
                        <a href="logout.php" class="dropdown-item" style="color: #C34200;">Logout</a>
                    </div>
                </div>
            </header>

            <main class="main-content">
                <div class="page-head" style="display:flex; justify-content: space-between; align-items: center;">
                    <div>
                        <p class="overview-label">System</p>
                        <h1>Activity Logs</h1>
                    </div>
                    <!-- EXPORT BUTTON -->
                    <form method="POST" style="margin:0;">
                        <button type="submit" name="export_csv" class="btn-export">Download CSV Report</button>
                    </form>
                </div>

                <section class="table-section">
                    <table>
                        <thead>
                            <tr>
                                <th>Admin</th>
                                <th>Action</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($logs->num_rows > 0) {
                                while($row = $logs->fetch_assoc()) {
                                    $admin = $row['admin_name'] ? htmlspecialchars($row['admin_name']) : 'System';
                                    echo "<tr>";
                                    echo "<td>" . $admin . "</td>";
                                    echo "<td>" . htmlspecialchars($row['action']) . "</td>";
                                    echo "<td>" . date('M d, Y h:i A', strtotime($row['timestamp'])) . "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='3' style='text-align:center;'>No activity logs found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </section>
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

    <!-- KimFresh mobile burger menu. Include this just before </body> on every admin page. -->
    <script>
    /* KimFresh mobile burger menu.
       Builds the burger button, a mobile logo, a close button and the overlay,
       so every admin page only needs to include this one script. */
    (function () {
        var sidebar = document.querySelector('.sidebar');
        var header = document.querySelector('.top-header');
        if (!sidebar || !header) return;

        sidebar.id = sidebar.id || 'sidebar';

        // Burger button (first item in the top bar)
        var burger = document.createElement('button');
        burger.type = 'button';
        burger.className = 'menu-toggle';
        burger.setAttribute('aria-label', 'Open menu');
        burger.setAttribute('aria-controls', sidebar.id);
        burger.setAttribute('aria-expanded', 'false');
        burger.innerHTML = '<span></span><span></span><span></span>';
        header.insertBefore(burger, header.firstChild);

        // Logo in the top bar (copied from the sidebar, since the sidebar is hidden)
        var logo = sidebar.querySelector('.logo');
        if (logo) {
            var mobileLogo = logo.cloneNode(true);
            mobileLogo.className = 'mobile-logo';
            header.insertBefore(mobileLogo, burger.nextSibling);
        }

        // Close button inside the drawer
        var closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'sidebar-close';
        closeBtn.setAttribute('aria-label', 'Close menu');
        closeBtn.innerHTML = '&times;';
        sidebar.appendChild(closeBtn);

        // Dark overlay behind the drawer
        var overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);

        function setOpen(open) {
            sidebar.classList.toggle('open', open);
            document.body.classList.toggle('sidebar-open', open);
            burger.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        burger.addEventListener('click', function () {
            setOpen(!sidebar.classList.contains('open'));
        });
        closeBtn.addEventListener('click', function () { setOpen(false); });
        overlay.addEventListener('click', function () { setOpen(false); });
        sidebar.addEventListener('click', function (e) {
            if (e.target.closest('a')) setOpen(false);
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') setOpen(false);
        });
        // If the window grows past phone size, reset the drawer
        window.matchMedia('(min-width: 769px)').addEventListener('change', function (e) {
            if (e.matches) setOpen(false);
        });
    })();
    </script>
</body>
</html>