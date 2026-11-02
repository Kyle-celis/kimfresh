<?php
session_start();
require 'db.php';
require 'log_helper.php';

if (!isset($_SESSION['admin_id'])) { header("Location: login.php"); exit(); }

// --- HANDLE PDF EXPORT (FPDF) ---
if (isset($_GET['export_pdf'])) {
    logActivity($conn, $_SESSION['admin_id'], "Exported Drivers to PDF");
    require('fpdf/fpdf.php');

    class PDF extends FPDF {
        function Header() {
            $this->SetFont('Arial', 'B', 15);
            $this->SetTextColor(195, 66, 0); 
            $this->Cell(0, 10, 'KimFresh Driver Roster', 0, 1, 'C');
            $this->SetFont('Arial', 'I', 10);
            $this->SetTextColor(100, 100, 100);
            $this->Cell(0, 5, 'Generated on: ' . date('F j, Y, g:i A'), 0, 1, 'C');
            $this->Ln(10);
            
            $this->SetFont('Arial', 'B', 10);
            $this->SetFillColor(195, 66, 0);
            $this->SetTextColor(255, 255, 255);
            $this->Cell(50, 8, 'Driver Name', 1, 0, 'C', true);
            $this->Cell(40, 8, 'License', 1, 0, 'C', true);
            $this->Cell(50, 8, 'Email', 1, 0, 'C', true);
            $this->Cell(30, 8, 'Status', 1, 1, 'C', true);
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

    $print_query = $conn->query("SELECT * FROM driver ORDER BY name ASC");

    while($row = $print_query->fetch_assoc()) {
        $status = ($row['status'] === 'hidden') ? 'INACTIVE' : strtoupper($row['status']);
        $pdf->Cell(50, 8, htmlspecialchars($row['name']), 1, 0, 'L');
        $pdf->Cell(40, 8, htmlspecialchars($row['license_number'] ?? 'N/A'), 1, 0, 'L');
        $pdf->Cell(50, 8, htmlspecialchars($row['email'] ?? 'N/A'), 1, 0, 'L');
        $pdf->Cell(30, 8, $status, 1, 1, 'C');
    }

    $pdf->Output('D', 'KimFresh_Drivers.pdf');
    exit();
}

// --- HANDLE ACTIVATE/DEACTIVATE ---
if (isset($_POST['toggle_status'])) {
    $driver_id  = (int)$_POST['driver_id'];
    $new_status = $_POST['new_status'];

    $stmt = $conn->prepare("UPDATE driver SET status = ? WHERE driver_id = ?");
    $stmt->bind_param("si", $new_status, $driver_id);
    $stmt->execute();

    $action = ($new_status === 'hidden') ? "Deactivated Driver #" : "Activated Driver #";
    logActivity($conn, $_SESSION['admin_id'], $action . $driver_id);

    if ($new_status === 'available') { assignWaitingOrders($conn); }
    header("Location: drivers.php");
    exit();
}

// --- HANDLE MANUAL ASSIGN ---
if (isset($_POST['assign_order'])) {
    $order_id  = (int)$_POST['order_id'];
    $driver_id = (int)$_POST['driver_id'];

    if (manualAssignDriver($conn, $order_id, $driver_id)) {
        logActivity($conn, $_SESSION['admin_id'], "Manually assigned Order #$order_id to Driver #$driver_id");
    } else {
        logActivity($conn, $_SESSION['admin_id'], "Manual assign FAILED for Order #$order_id → Driver #$driver_id");
    }
    header("Location: drivers.php");
    exit();
}

// --- STATS FOR HEADER ---
$total_drivers = $conn->query("SELECT COUNT(*) AS c FROM driver WHERE status != 'hidden'")->fetch_assoc()['c'];
$available_drivers = $conn->query("SELECT COUNT(*) AS c FROM driver WHERE status = 'available' AND account_status = 'approved'")->fetch_assoc()['c'];
$on_delivery = $conn->query("SELECT COUNT(*) AS c FROM driver WHERE status = 'on_delivery'")->fetch_assoc()['c'];

// --- FILTER & SEARCH LOGIC ---
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all'; 
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$baseSql = " FROM driver d WHERE 1=1 ";

if ($filter === 'all') {
    $baseSql .= " AND d.status != 'hidden'";
} elseif ($filter === 'available') {
    $baseSql .= " AND (SELECT COUNT(*) FROM delivery dl INNER JOIN `order` o ON o.order_id = dl.order_id WHERE dl.driver_id = d.driver_id AND o.order_status != 'delivered') = 0 AND d.status != 'hidden'";
} elseif ($filter === 'assigned') {
    $baseSql .= " AND (SELECT dl.delivery_status FROM delivery dl INNER JOIN `order` o ON o.order_id = dl.order_id WHERE dl.driver_id = d.driver_id AND o.order_status != 'delivered' ORDER BY dl.delivery_id DESC LIMIT 1) = 'assigned'";
} elseif ($filter === 'on_delivery') {
    $baseSql .= " AND (SELECT dl.delivery_status FROM delivery dl INNER JOIN `order` o ON o.order_id = dl.order_id WHERE dl.driver_id = d.driver_id AND o.order_status != 'delivered' ORDER BY dl.delivery_id DESC LIMIT 1) IN ('in_transit', 'picked_up')";
} elseif ($filter === 'inactive') {
    $baseSql .= " AND d.status = 'hidden'";
} 

if (!empty($search)) {
    $safe_search = $conn->real_escape_string($search);
    $baseSql .= " AND (d.name LIKE '%$safe_search%' OR d.email LIKE '%$safe_search%' OR d.license_number LIKE '%$safe_search%')";
}

// --- PAGINATION LOGIC (6 per page) ---
$limit = 6;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$countQuery = $conn->query("SELECT COUNT(*) AS total $baseSql");
$total_rows = $countQuery->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

$sql = "
    SELECT d.*,
           (SELECT dl.delivery_status FROM delivery dl INNER JOIN `order` o ON o.order_id = dl.order_id WHERE dl.driver_id = d.driver_id AND o.order_status != 'delivered' ORDER BY dl.delivery_id DESC LIMIT 1) AS active_delivery_status,
           (SELECT COUNT(*) FROM delivery dl INNER JOIN `order` o ON o.order_id = dl.order_id WHERE dl.driver_id = d.driver_id AND o.order_status != 'delivered') AS active_orders
    $baseSql 
    ORDER BY d.name ASC 
    LIMIT $limit OFFSET $offset
";
$drivers = $conn->query($sql);

// --- FETCH UNASSIGNED ORDERS ---
$unassigned = $conn->query("
    SELECT o.order_id, r.name AS retailer_name 
    FROM `order` o
    JOIN retailer r ON o.retailer_id = r.retailer_id
    LEFT JOIN delivery d ON o.order_id = d.order_id
    WHERE o.order_status = 'approved' AND d.delivery_id IS NULL
");

// --- LOAD PAST DELIVERIES ---
$histories = [];
$histRes = $conn->query("
    SELECT dl.driver_id, o.order_id, o.order_status, o.total_amount, o.order_date, dl.delivery_status, dl.delivery_date AS assigned_at, r.name AS retailer_name, (SELECT SUM(oi.quantity) FROM order_item oi WHERE oi.order_id = o.order_id) AS total_items
    FROM delivery dl
    INNER JOIN `order` o ON o.order_id = dl.order_id
    LEFT  JOIN retailer r ON r.retailer_id = o.retailer_id
    ORDER BY dl.delivery_date DESC, o.order_id DESC
");
if ($histRes) {
    while ($h = $histRes->fetch_assoc()) {
        $histories[(int)$h['driver_id']][] = $h;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KimFresh - Drivers</title>
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/drivers.css">
    <link rel="stylesheet" href="css/responsive.css">
    
    <style>
        :root { --primary: #C34200; --primary-dark: #9a3400; }
        
        /* STATS CARDS */
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 24px; }
        .stat-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border-left: 5px solid #C34200; }
        .stat-card.green { border-left-color: #15803d; }
        .stat-card.orange { border-left-color: #f59e0b; }
        .stat-card p { margin: 0; color: #666; font-size: 13px; font-weight: 700; }
        .stat-card h2 { margin: 5px 0 0; font-size: 28px; color: #333; }

        .status.available { color: #15803d; background: #dcfce7; }
        .status.assigned { color: #1d4ed8; background: #dbeafe; }
        .status.on-delivery { color: #b45309; background: #fef3c7; }
        .status.inactive { color: #666; background: #eee; }

        .modal-backdrop { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 1000; }
        .modal-backdrop.open { display: flex; }
        .modal { background: white; border-radius: 12px; padding: 24px; width: 90%; max-width: 400px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .modal-wide { max-width: 800px; }
        .modal-actions { display: flex; justify-content: space-between; align-items: center; gap: 10px; }
        .modal-actions button { padding: 10px 20px; border-radius: 8px; font-weight: bold; cursor: pointer; border: none; }
        .modal-actions .cancel { background: #eee; color: #333; }
        
        .history-table { width: 100%; border-collapse: collapse; }
        .history-table th { text-align: left; padding: 12px; font-size: 12px; color: #666; border-bottom: 1px solid #eee; background: #f8f9fa; }
        .history-table td { padding: 12px; font-size: 13px; border-bottom: 1px solid #eee; }

        .history-btn { padding: 8px 16px; font-size: 12px; font-weight: 700; color: #fff; background: #C34200; border: none; border-radius: 8px; cursor: pointer; width: 100%; margin-bottom: 8px; }
        .history-btn:disabled { background: #ccc; cursor: not-allowed; }
        
        .btn-deactivate { padding: 8px 16px; font-size: 12px; font-weight: 700; color: #fff; background: #dc2626; border: 1px solid #dc2626; border-radius: 8px; cursor: pointer; width: 100%; }
        .btn-deactivate:hover { background: #b91c1c; }
        .btn-activate { padding: 8px 16px; font-size: 12px; font-weight: 700; color: #fff; background: #15803d; border: 1px solid #15803d; border-radius: 8px; cursor: pointer; width: 100%; }
        .btn-activate:hover { background: #166534; }

        .toolbar { display: flex; gap: 12px; margin-top: 10px; margin-bottom: 24px; justify-content: space-between; flex-wrap: wrap; }
        .search-box { flex: 1; max-width: 300px; }
        .search-box input { width: 100%; height: 44px; padding: 0 16px; border: 1px solid #ddd; border-radius: 10px; font-size: 13px; outline: none; }
        .filter-box select { width: 220px; height: 44px; padding: 0 16px; border: 1px solid #ddd; border-radius: 10px; font-size: 13px; outline: none; background: #fff; font-weight: bold; color: #333; }

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

        .assign-form { display: flex; gap: 5px; margin-top: 5px; }
        .assign-form select { flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 8px; font-size: 12px; outline: none; }
        .assign-form button { padding: 8px 12px; background: #C34200; color: white; border: none; border-radius: 8px; font-size: 12px; font-weight: bold; cursor: pointer; }

        /* PAGINATION */
        .pagination { display: flex; justify-content: center; gap: 6px; margin-top: 24px; }
        .pagination a, .pagination span { display: inline-flex; align-items: center; justify-content: center; min-width: 36px; height: 36px; padding: 0 12px; border-radius: 8px; font-size: 13px; font-weight: 700; text-decoration: none; border: 1px solid #ddd; color: #555; background: white; transition: 0.2s; }
        .pagination a:hover { background: #fdf0e6; border-color: #C34200; color: #C34200; }
        .pagination .active { background: #C34200; border-color: #C34200; color: white; }
        .pagination .disabled { opacity: 0.5; pointer-events: none; }

        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: 1fr; }
            .page-head { flex-direction: column !important; align-items: stretch !important; gap: 15px !important; }
            .page-head .btn-export { width: 100% !important; text-align: center !important; }
            .toolbar { flex-direction: column; }
            .search-box, .filter-box select { max-width: 100%; width: 100%; }
            .driver-grid { grid-template-columns: 1fr !important; }
            .assign-form { flex-direction: column; }
        }

        /* =========================================
           NOTIFICATION DROPDOWN (LIGHT THEME)
           ========================================= */
        .notification-container { position: relative; margin-right: 15px; }
        
        .notification-bell {
            position: relative; display: flex; align-items: center; justify-content: center;
            width: 44px; height: 44px; border-radius: 50%; background: transparent; cursor: pointer; transition: background 0.2s;
        }
        .notification-bell:hover { background: #f0f0f0; }
        
        .notif-badge {
            position: absolute; top: 2px; right: 2px; background: #dc2626; color: white;
            font-size: 11px; font-weight: 800; width: 20px; height: 20px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .notif-dropdown {
            position: absolute; top: 55px; right: 0; width: 320px;
            background: white; border-radius: 12px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.15); border: 1px solid #eee;
            display: none; z-index: 1000; color: #333; overflow: hidden;
        }
        .notif-dropdown.show { display: block; }
        
        .notif-header {
            display: flex; justify-content: space-between; align-items: center;
            padding: 15px; border-bottom: 1px solid #eee;
        }
        .notif-header h3 { margin: 0; font-size: 16px; font-weight: 800; color: #333; }
        .notif-mark-read { color: #C34200; text-decoration: none; font-size: 13px; font-weight: 600; }
        .notif-mark-read:hover { text-decoration: underline; }

        .notif-list { max-height: 350px; overflow-y: auto; }
        
        .notif-item {
            display: flex; gap: 12px; padding: 12px 15px;
            border-bottom: 1px solid #f5f5f5; text-decoration: none; color: #333;
            transition: background 0.2s;
        }
        .notif-item:hover { background: #fdf0e6; }
        
        .notif-icon {
            width: 45px; height: 45px; border-radius: 50%;
            background: #C34200; color: white; 
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; font-weight: bold; font-size: 16px;
        }
        
        .notif-content { flex: 1; display: flex; flex-direction: column; justify-content: center; }
        .notif-content p { margin: 0 0 4px 0; font-size: 13px; line-height: 1.3; color: #444; }
        .notif-content strong { font-weight: 800; color: #333; }
        .notif-time { color: #888; font-size: 11px; font-weight: 600; }
        
        .notif-empty { padding: 20px; text-align: center; color: #888; font-size: 13px; }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-brand">
                <img src="logo.png" class="logo" alt="KimFresh">
                <p>Administration</p>
            </div>
            <nav class="menu">
                <a href="dashboard.php">Dashboard</a>
                <a href="orders.php">Orders</a>
                <a href="products.php">Products</a>
                <a href="drivers.php" class="active">Drivers</a>
                 <a href="analytics.php">Analytics</a>
                <?php if (!empty($_SESSION['is_superadmin'])): ?>
                    <a href="superadmin.php">Manage Admins</a>
                <?php endif; ?>
            </nav>
        </aside>

        <div class="main-area">
            <header class="top-header">
                
                <!-- NOTIFICATION BELL & DROPDOWN -->
                <div class="notification-container" id="notifContainer">
                    <div class="notification-bell" onclick="toggleNotifications(event)">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#555" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                        </svg>
                        <span id="notif-badge" class="notif-badge" style="display: none;">0</span>
                    </div>
                    
                    <div class="notif-dropdown" id="notifDropdown">
                        <div class="notif-header">
                            <h3>Notifications</h3>
                            <a href="orders.php?status=pending" class="notif-mark-read">View All</a>
                        </div>
                        <div class="notif-list" id="notifList">
                            <div class="notif-empty">No new orders</div>
                        </div>
                    </div>
                </div>

                <div class="profile-menu" id="profileMenu">
                    <button type="button" class="profile-trigger" onclick="toggleProfileMenu(event)">
                        <div class="avatar">A</div>
                        <div class="user-info" style="text-align: left;">
                            <p class="user-name" style="margin: 0;"><?php echo htmlspecialchars($_SESSION['admin_name']); ?></p>
                            <p class="user-role" style="margin: 0;">Administrator</p>
                        </div>
                        <span style="font-size: 18px; color: #999; margin-left: 5px;">⌄</span>
                    </button>
                    <div class="profile-dropdown" id="profileDropdown">
                        <div class="dropdown-account">
                            <strong><?php echo htmlspecialchars($_SESSION['admin_name']); ?></strong>
                            <span><?php echo htmlspecialchars($_SESSION['admin_email'] ?? 'admin@kimfresh.com'); ?></span>
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
                        <p class="overview-label">Manage</p>
                        <h1 style="margin:0;">Drivers</h1>
                    </div>
                    <a href="?export_pdf=1" download="KimFresh_Drivers.pdf" class="btn-export">Export PDF</a>
                </div>

                <!-- STATS CARDS -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <p>Total Drivers</p>
                        <h2><?php echo $total_drivers; ?></h2>
                    </div>
                    <div class="stat-card green">
                        <p>Available</p>
                        <h2><?php echo $available_drivers; ?></h2>
                    </div>
                    <div class="stat-card orange">
                        <p>On Delivery</p>
                        <h2><?php echo $on_delivery; ?></h2>
                    </div>
                </div>

                <!-- SEARCH & FILTER -->
                <div class="toolbar no-print">
                    <div class="search-box">
                        <form method="GET" action="drivers.php" style="margin:0; display:flex; gap: 10px;">
                            <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                            <input type="text" name="search" placeholder="Search by name, email, or license..." value="<?php echo htmlspecialchars($search); ?>" style="flex: 1;">
                            <button type="submit" style="background: #C34200; color: white; border: none; padding: 0 15px; border-radius: 10px; font-weight: bold; cursor: pointer;">Search</button>
                        </form>
                    </div>
                    <div class="filter-box">
                        <form method="GET" action="drivers.php" style="margin:0;">
                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                            <select name="filter" onchange="this.form.submit()">
                                <option value="all" <?php echo $filter == 'all' ? 'selected' : ''; ?>>All Drivers</option>
                                <option value="available" <?php echo $filter == 'available' ? 'selected' : ''; ?>>Available</option>
                                <option value="assigned" <?php echo $filter == 'assigned' ? 'selected' : ''; ?>>Assigned</option>
                                <option value="on_delivery" <?php echo $filter == 'on_delivery' ? 'selected' : ''; ?>>On Delivery</option>
                                <option value="inactive" <?php echo $filter == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </form>
                    </div>
                </div>

                <div class="driver-grid" style="margin-top: 20px;">
                    <?php if ($drivers->num_rows > 0): ?>
                        <?php while($row = $drivers->fetch_assoc()):
                            $activeCount = (int)$row['active_orders'];
                            $deliveryStatus = $row['active_delivery_status'];
                            $isInactive = $row['status'] === 'hidden';

                            if ($isInactive) {
                                $statusClass = 'inactive'; $statusLabel = 'Inactive';
                            } elseif ($activeCount == 0) {
                                $statusClass = 'available'; $statusLabel = 'Available';
                            } elseif ($deliveryStatus == 'in_transit') {
                                $statusClass = 'on-delivery'; $statusLabel = 'On Delivery';
                            } else {
                                // If they have an order but haven't started transit, they are Assigned
                                $statusClass = 'assigned'; $statusLabel = 'Assigned';
                            }

                            $initial = strtoupper(substr($row['name'], 0, 1));
                            $driverId = (int)$row['driver_id'];
                            $driverPast = $histories[$driverId] ?? [];
                        ?>
                            <div class='driver-card'>
                                <div class='driver-top'>
                                    <div class='driver-avatar'><?php echo $initial; ?></div>
                                    <div class='driver-title'>
                                        <div class='driver-name'><?php echo htmlspecialchars($row['name']); ?></div>
                                        <div class='driver-license'><?php echo htmlspecialchars($row['license_number'] ?? 'No license'); ?></div>
                                    </div>
                                    <span class='status <?php echo $statusClass; ?>'><?php echo $statusLabel; ?></span>
                                </div>
                                <div class='driver-contact'>
                                    <p><?php echo htmlspecialchars($row['email'] ?? 'No email'); ?></p>
                                    <p><?php echo htmlspecialchars($row['phone'] ?? 'No phone'); ?></p>
                                </div>
                                <div class='driver-section'>
                                    <h4>Active Orders: <?php echo $activeCount; ?></h4>
                                    <p class="done-count"><?php echo count($driverPast); ?> past deliveries</p>
                                </div>

                                <div class="driver-actions no-print" style="display:flex; flex-direction:column; gap:8px;">
                                    <?php if ($activeCount == 0 && !$isInactive && $unassigned->num_rows > 0): ?>
                                        <form method="POST" class="assign-form">
                                            <input type="hidden" name="driver_id" value="<?php echo $driverId; ?>">
                                            <select name="order_id" required>
                                                <option value="">Assign an order...</option>
                                                <?php 
                                                    $unassigned->data_seek(0); 
                                                    while($uo = $unassigned->fetch_assoc()) {
                                                        echo "<option value='".$uo['order_id']."'>#".$uo['order_id']." - ".htmlspecialchars($uo['retailer_name'])."</option>";
                                                    }
                                                ?>
                                            </select>
                                            <button type="submit" name="assign_order">Assign</button>
                                        </form>
                                    <?php endif; ?>

                                    <button class="history-btn" type="button" data-driver-id="<?php echo $driverId; ?>" data-driver-name="<?php echo htmlspecialchars($row['name']); ?>" <?php if (empty($driverPast)) echo 'disabled title="No past deliveries"'; ?>>
                                        View History
                                    </button>

                                    <?php if ($isInactive): ?>
                                        <form method='POST' style='margin:0;'>
                                            <input type='hidden' name='driver_id' value='<?php echo $driverId; ?>'>
                                            <input type='hidden' name='new_status' value='available'>
                                            <button type='submit' name='toggle_status' class='btn-activate'>Activate</button>
                                        </form>
                                    <?php else: ?>
                                        <form method='POST' style='margin:0;'>
                                            <input type='hidden' name='driver_id' value='<?php echo $driverId; ?>'>
                                            <input type='hidden' name='new_status' value='hidden'>
                                            <button type='submit' name='toggle_status' class='btn-deactivate'>Deactivate</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <template id="history-tpl-<?php echo $driverId; ?>">
                                <?php if (!empty($driverPast)): ?>
                                    <table class="history-table">
                                        <thead><tr><th>Order</th><th>Retailer</th><th>Items</th><th>Total</th><th>Status</th><th>Delivery</th><th>Date</th></tr></thead>
                                        <tbody>
                                        <?php foreach ($driverPast as $h): ?>
                                            <tr>
                                                <td><span class="order-no">#<?php echo (int)$h['order_id']; ?></span></td>
                                                <td><?php echo htmlspecialchars($h['retailer_name'] ?? '—'); ?></td>
                                                <td><?php echo (int)($h['total_items'] ?? 0); ?></td>
                                                <td>₱<?php echo number_format((float)$h['total_amount'], 2); ?></td>
                                                <td><span class="status-pill <?php echo strtolower($h['order_status']); ?>"><?php echo strtoupper($h['order_status']); ?></span></td>
                                                <td><?php echo strtoupper($h['delivery_status']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($h['assigned_at'] ?? $h['order_date'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <p class="empty-note">This driver has no past deliveries yet.</p>
                                <?php endif; ?>
                            </template>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="text-align:center; padding: 20px; grid-column: 1/-1; color:#777;">No drivers found matching your filter.</p>
                    <?php endif; ?>
                </div>

                <!-- PAGINATION -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination no-print">
                    <?php if ($page > 1): ?>
                        <a href="?filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page - 1; ?>">Prev</a>
                    <?php else: ?>
                        <span class="disabled">Prev</span>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page + 1; ?>">Next</a>
                    <?php else: ?>
                        <span class="disabled">Next</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

            </main>
        </div>
    </div>

    <!-- ORDER HISTORY MODAL -->
    <div class="modal-backdrop no-print" id="historyModal">
        <div class="modal modal-wide">
            <h3 style="margin-top:0; color: #C34200;">Order History — <span id="historyDriverName">—</span></h3>
            <div id="historyBody" style="max-height: 60vh; overflow: auto; margin-top: 14px;"></div>
            <div class="modal-actions" style="margin-top: 16px;">
                <button type="button" class="cancel" onclick="closeHistory()">Close</button>
                <button type="button" class="btn-export" onclick="exportHistoryPDF()">Export to PDF</button>
            </div>
        </div>
    </div>

    <!-- CUSTOM CTRL+F FIND BAR -->
    <div id="customFindBar" class="no-print" style="display: none; position: fixed; top: 10px; right: 20px; background: white; padding: 10px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 9999; border: 1px solid #ddd;">
        <input type="text" id="findInput" placeholder="Find driver..." style="padding: 8px; border: 1px solid #ccc; border-radius: 4px; outline: none; width: 200px;">
        <button id="closeFind" style="padding: 8px 12px; margin-left: 5px; background: #eee; border: none; border-radius: 4px; cursor: pointer;">X</button>
    </div>

    <script>
        // PAGE ANIMATION
        if (sessionStorage.getItem('scrollPos')) { document.documentElement.style.visibility = 'hidden'; }
        window.addEventListener('load', function() {
            const savedPosition = sessionStorage.getItem('scrollPos');
            if (savedPosition) {
                window.scrollTo(0, parseInt(savedPosition));
                document.documentElement.style.visibility = 'visible';
                sessionStorage.removeItem('scrollPos');
            }
        });
        document.addEventListener('mousedown', function(event) {
            if (event.target.closest('th a') || event.target.closest('.pagination a') || event.target.closest('.filter-box select')) {
                sessionStorage.setItem('scrollPos', window.scrollY);
            }
        });

        // HISTORY MODAL
        const historyModal = document.getElementById('historyModal');
        const historyName  = document.getElementById('historyDriverName');
        const historyBody  = document.getElementById('historyBody');

        document.querySelectorAll('.history-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                if (btn.disabled) return;
                const id   = btn.dataset.driverId;
                const name = btn.dataset.driverName;
                const tpl  = document.getElementById('history-tpl-' + id);

                historyName.textContent = name;
                historyBody.innerHTML   = '';
                if (tpl) historyBody.appendChild(tpl.content.cloneNode(true));

                historyModal.classList.add('open');
            });
        });

        function closeHistory() { historyModal.classList.remove('open'); }
        historyModal.addEventListener('click', e => { if (e.target === historyModal) closeHistory(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeHistory(); });

        function exportHistoryPDF() {
            const driverName = historyName.textContent;
            const tableHTML = historyBody.innerHTML;

            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Delivery History - ${driverName}</title>
                    <style>
                        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #333; padding: 40px; }
                        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #C34200; padding-bottom: 15px; }
                        .header h1 { color: #C34200; margin: 0; font-size: 24px; }
                        .header p { color: #666; margin: 5px 0 0; font-size: 14px; }
                        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                        th { background: #fdf0e6; color: #C34200; padding: 10px; text-align: left; font-size: 12px; text-transform: uppercase; border-bottom: 2px solid #C34200; }
                        td { padding: 10px; border-bottom: 1px solid #eee; font-size: 12px; }
                        .footer { margin-top: 40px; text-align: center; font-size: 10px; color: #999; }
                    </style>
                </head>
                <body onload="window.print();">
                    <div class="header">
                        <h1>KimFresh Delivery History</h1>
                        <p>Driver: <strong>${driverName}</strong> | Generated on <?php echo date('F j, Y'); ?></p>
                    </div>
                    ${tableHTML}
                    <div class="footer">KimFresh IoT System - Confidential Report</div>
                </body>
                </html>
            `);
            printWindow.document.close();
        }

        // PROFILE MENU
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

        // CTRL+F FIND BAR
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                e.preventDefault(); 
                const findBar = document.getElementById('customFindBar');
                findBar.style.display = 'flex';
                document.getElementById('findInput').focus();
            }
            if (e.key === 'Escape') {
                const findBar = document.getElementById('customFindBar');
                if (findBar.style.display === 'flex') {
                    findBar.style.display = 'none';
                    document.getElementById('findInput').value = '';
                    document.querySelectorAll('.driver-card').forEach(card => card.style.display = '');
                    e.stopImmediatePropagation();
                }
            }
        });

        document.getElementById('closeFind').addEventListener('click', function() {
            document.getElementById('customFindBar').style.display = 'none';
            document.getElementById('findInput').value = '';
            document.querySelectorAll('.driver-card').forEach(card => card.style.display = '');
        });

        document.getElementById('findInput').addEventListener('keyup', function() {
            const query = this.value.toLowerCase();
            const cards = document.querySelectorAll('.driver-card');
            cards.forEach(card => {
                const text = card.innerText.toLowerCase();
                card.style.display = text.includes(query) ? '' : 'none';
            });
        });

    let lastNotifCount = 0;
    // 🔥 NEW: Built-in browser beep synthesizer
function playBeep() {
    try {
        let context = new (window.AudioContext || window.webkitAudioContext)();
        let osc = context.createOscillator();
        osc.type = 'sine';
        osc.frequency.value = 800; // 800Hz pitch
        osc.connect(context.destination);
        osc.start();
        osc.stop(context.currentTime + 0.15); // Beep for 0.15 seconds
    } catch (e) {
        console.log('Audio not supported in this browser.');
    }
}

    function toggleNotifications(event) {
        event.stopPropagation();
        const dropdown = document.getElementById('notifDropdown');
        // Just toggle the open/close state
        dropdown.classList.toggle('show');
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const container = document.getElementById('notifContainer');
        const dropdown = document.getElementById('notifDropdown');
        if (container && !container.contains(event.target)) {
            if(dropdown) dropdown.classList.remove('show');
        }
    });

    function checkNotifications() {
        fetch('get_notifications.php')
            .then(response => response.json())
            .then(data => {
                const badge = document.getElementById('notif-badge');
                const list = document.getElementById('notifList');
                
                if (badge && list) {
                    let readIds = JSON.parse(localStorage.getItem('readNotifIds') || '[]');
                    let unreadCount = 0;

                    // 1. Count unread
                    data.orders.forEach(item => {
                        const itemId = item.type === 'order' ? 'order-' + item.order_id : 'log-' + item.log_id;
                        if (!readIds.includes(itemId)) {
                            unreadCount++;
                        }
                    });

                    // 2. Update Badge
                    if (unreadCount > 0) {
                        badge.style.display = 'flex';
                        badge.textContent = unreadCount;
                        
if (unreadCount > lastNotifCount) {
    playBeep(); // 👈 Call our new beep function
}
                    } else {
                        badge.style.display = 'none';
                    }
                    lastNotifCount = unreadCount;

                    // 3. Re-render list
                    list.innerHTML = ''; 
                    data.orders.forEach(item => {
                        const date = new Date(item.type === 'order' ? item.order_date : item.timestamp);
                        const timeAgo = date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
                        
                        const itemId = item.type === 'order' ? 'order-' + item.order_id : 'log-' + item.log_id;
                        const isRead = readIds.includes(itemId);
                        const readStyle = isRead ? 'opacity: 0.6;' : '';

                        if (item.type === 'order') {
                            list.innerHTML += `
                                <a href="view_order.php?id=${item.order_id}" class="notif-item" style="${readStyle}" data-notif-id="${itemId}" onclick="markAsRead('${itemId}')">
                                    <div class="notif-icon">#${item.order_id}</div>
                                    <div class="notif-content">
                                        <p><strong>${item.retailer_name}</strong> placed a new order.</p>
                                        <span class="notif-time">${timeAgo} &middot; ₱${Number(item.total_amount).toFixed(2)}</span>
                                    </div>
                                </a>
                            `;
                        } else {
                            list.innerHTML += `
                                <a href="drivers.php?search=${encodeURIComponent(item.driver_name)}" class="notif-item" style="${readStyle}" data-notif-id="${itemId}" onclick="markAsRead('${itemId}')">
                                    <div class="notif-icon" style="background: #15803d; border-radius: 50%;">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                    </div>
                                    <div class="notif-content">
                                        <p>${item.action}</p>
                                        <span class="notif-time">${timeAgo} &middot; Click to view driver</span>
                                    </div>
                                </a>
                            `;
                        }
                    });
                }
            })
            .catch(err => console.error('Error fetching notifications:', err));
    }

    // 🔥 NEW FUNCTION: Marks only the clicked item as read
    function markAsRead(itemId) {
        let readIds = JSON.parse(localStorage.getItem('readNotifIds') || '[]');
        if (!readIds.includes(itemId)) {
            readIds.push(itemId);
            localStorage.setItem('readNotifIds', JSON.stringify(readIds));
        }
        // The badge will automatically update on the next polling interval
    }

    checkNotifications();
    setInterval(checkNotifications, 15000);
</script>

    <!-- KimFresh mobile burger menu script -->
    <script>
    (function () {
        var sidebar = document.querySelector('.sidebar');
        var header = document.querySelector('.top-header');
        if (!sidebar || !header) return;

        sidebar.id = sidebar.id || 'sidebar';

        var burger = document.createElement('button');
        burger.type = 'button';
        burger.className = 'menu-toggle';
        burger.setAttribute('aria-label', 'Open menu');
        burger.setAttribute('aria-controls', sidebar.id);
        burger.setAttribute('aria-expanded', 'false');
        burger.innerHTML = '<span></span><span></span><span></span>';
        header.insertBefore(burger, header.firstChild);

        var logo = sidebar.querySelector('.logo');
        if (logo) {
            var mobileLogo = logo.cloneNode(true);
            mobileLogo.className = 'mobile-logo';
            header.insertBefore(mobileLogo, burger.nextSibling);
        }

        var closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'sidebar-close';
        closeBtn.setAttribute('aria-label', 'Close menu');
        closeBtn.innerHTML = '&times;';
        sidebar.appendChild(closeBtn);

        var overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);

        function setOpen(open) {
            sidebar.classList.toggle('open', open);
            document.body.classList.toggle('sidebar-open', open);
            burger.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        burger.addEventListener('click', function () { setOpen(!sidebar.classList.contains('open')); });
        closeBtn.addEventListener('click', function () { setOpen(false); });
        overlay.addEventListener('click', function () { setOpen(false); });
        sidebar.addEventListener('click', function (e) { if (e.target.closest('a')) setOpen(false); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setOpen(false); });
        window.matchMedia('(min-width: 769px)').addEventListener('change', function (e) { if (e.matches) setOpen(false); });
    })();
    </script>
</body>
</html>