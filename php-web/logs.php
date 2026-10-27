<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
require 'db.php';
require 'log_helper.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Force Philippine Time for this page
date_default_timezone_set('Asia/Manila');

// --- PAGINATION LOGIC ---
$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$countQuery = $conn->query("SELECT COUNT(*) AS total FROM activity_logs");
$total_rows = $countQuery->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// --- HANDLE PDF EXPORT (FPDF) ---
if (isset($_GET['export_pdf'])) {
    logActivity($conn, $_SESSION['admin_id'], "Exported Activity Logs to PDF");
    
    require('fpdf/fpdf.php');

    class PDF extends FPDF {
        function Header() {
            $this->SetFont('Arial', 'B', 15);
            $this->SetTextColor(195, 66, 0); 
            $this->Cell(0, 10, 'KimFresh System Activity Logs', 0, 1, 'C');
            $this->SetFont('Arial', 'I', 10);
            $this->SetTextColor(100, 100, 100);
            $this->Cell(0, 5, 'Generated on: ' . date('F j, Y, g:i A'), 0, 1, 'C');
            $this->Ln(10);
            
            $this->SetFont('Arial', 'B', 10);
            $this->SetFillColor(195, 66, 0);
            $this->SetTextColor(255, 255, 255);
            $this->Cell(50, 8, 'Admin', 1, 0, 'C', true);
            $this->Cell(90, 8, 'Action', 1, 0, 'C', true);
            $this->Cell(50, 8, 'Timestamp', 1, 1, 'C', true);
        }

        function Footer() {
            $this->SetY(-15);
            $this->SetFont('Arial', 'I', 8);
            $this->SetTextColor(128);
            $this->Cell(0, 10, 'Page ' . $this->PageNo() . ' - KimFresh IoT System', 0, 0, 'C');
        }
    }

    $pdf = new PDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(0, 0, 0);

    // PDF exports ALL logs (no pagination limit for the file)
    $print_query = $conn->query("
        SELECT l.*, a.name as admin_name 
        FROM activity_logs l 
        LEFT JOIN admin a ON l.admin_id = a.admin_id 
        ORDER BY l.timestamp DESC
    ");

    while($row = $print_query->fetch_assoc()) {
        $admin_display = $row['admin_name'] ?? 'System';
        $pdf->Cell(50, 8, htmlspecialchars($admin_display), 1, 0, 'L');
        $pdf->Cell(90, 8, htmlspecialchars($row['action']), 1, 0, 'L');
        $pdf->Cell(50, 8, date('M d, Y h:i A', strtotime($row['timestamp'])), 1, 1, 'C');
    }

    $pdf->Output('D', 'KimFresh_Activity_Logs.pdf');
    exit();
}

// --- FETCH LOGS (LIMITED TO 10) ---
$sql = "
    SELECT l.*, a.name as admin_name 
    FROM activity_logs l 
    LEFT JOIN admin a ON l.admin_id = a.admin_id 
    ORDER BY l.timestamp DESC
    LIMIT $limit OFFSET $offset
";
$logs = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KimFresh - Activity Logs</title>
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/orders.css">
    <link rel="stylesheet" href="css/responsive.css">
    
    <style>
        :root { --primary: #C34200; --primary-dark: #9a3400; }
        
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

        .btn-export { background: #C34200; color: white; padding: 10px 20px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.2s; text-decoration: none; display: inline-block; }
        .btn-export:hover { background: #9a3400; }

        /* PAGINATION STYLES */
        .pagination { display: flex; justify-content: center; gap: 6px; margin-top: 24px; }
        .pagination a, .pagination span {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 36px; height: 36px; padding: 0 12px;
            border-radius: 8px; font-size: 13px; font-weight: 700;
            text-decoration: none; border: 1px solid #ddd; color: #555; background: white;
            transition: 0.2s;
        }
        .pagination a:hover { background: #fdf0e6; border-color: #C34200; color: #C34200; }
        .pagination .active { background: #C34200; border-color: #C34200; color: white; }
        .pagination .disabled { opacity: 0.5; pointer-events: none; }
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
                            <span><?php echo $_SESSION['admin_email'] ?? 'admin@kimfresh.com'; ?></span>
                        </div>
                        <a href="settings.php" class="dropdown-item">Settings</a>
                        <a href="logs.php" class="dropdown-item">Activity Logs</a>
                        <a href="logout.php" class="dropdown-item" style="color: #C34200;">Logout</a>
                    </div>
                </div>
            </header>

            <main class="main-content">
                <div class="page-head" style="display:flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                    <div>
                        <p class="overview-label">System</p>
                        <h1 style="margin:0;">Activity Logs</h1>
                    </div>
                    <a href="?export_pdf=1" download="KimFresh_Activity_Logs.pdf" class="btn-export">Export to PDF</a>
                </div>

                <div class="table-section">
                    <div class="table-responsive">
                        <table class="orders-table" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th style="width: 20%; text-align: left; padding: 15px;">Admin</th>
                                    <th style="width: 50%; text-align: left; padding: 15px;">Action</th>
                                    <th style="width: 30%; text-align: left; padding: 15px;">Timestamp</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($logs->num_rows > 0) {
                                    while($row = $logs->fetch_assoc()) {
                                        $admin_display = $row['admin_name'] ?? 'System';
                                        
                                        echo "<tr>";
                                        echo "<td style='padding: 15px; border-bottom: 1px solid #eee;'>" . htmlspecialchars($admin_display) . "</td>";
                                        echo "<td style='padding: 15px; border-bottom: 1px solid #eee;'>" . htmlspecialchars($row['action']) . "</td>";
                                        echo "<td style='padding: 15px; border-bottom: 1px solid #eee;'>" . date('M d, Y h:i A', strtotime($row['timestamp'])) . "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='3' style='text-align:center; padding: 20px;'>No activity logs found.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- PAGINATION LINKS -->
                    <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>">Prev</a>
                        <?php else: ?>
                            <span class="disabled">Prev</span>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="active"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>">Next</a>
                        <?php else: ?>
                            <span class="disabled">Next</span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
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
    <!-- SECURITY: Force reload on back button & hide body on leave -->
    <script>
        window.addEventListener('pageshow', function (event) {
            if (event.persisted || (typeof window.performance != "undefined" && window.performance.navigation.type === 2)) {
                window.location.reload();
            }
        });
        window.addEventListener('pagehide', function (event) {
            document.body.style.display = 'none';
        });
    </script>
</body>
</html>