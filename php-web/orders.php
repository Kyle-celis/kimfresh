<?php
session_start();
require 'db.php';
require 'log_helper.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// --- HANDLE BULK ACTIONS (APPROVE / REJECT) ---
if (isset($_POST['bulk_action']) && isset($_POST['selected_orders'])) {
    $action = $_POST['bulk_action'];
    $selected = $_POST['selected_orders'];
    
    if (is_array($selected) && count($selected) > 0) {
        $success_count = 0;
        $fail_count = 0;

        foreach ($selected as $id) {
            $order_id = (int)$id;
            
            if ($action === 'approve') {
                $chk = $conn->prepare("SELECT order_status FROM `order` WHERE order_id = ?");
                $chk->bind_param("i", $order_id);
                $chk->execute();
                $cur = $chk->get_result()->fetch_assoc();
                
                if ($cur && $cur['order_status'] === 'pending') {
                    $conn->begin_transaction();
                    try {
                        $items = $conn->prepare("SELECT product_id, quantity FROM order_item WHERE order_id = ?");
                        $items->bind_param("i", $order_id);
                        $items->execute();
                        $res = $items->get_result();

                        $upd = $conn->prepare("UPDATE product SET stock_quantity = stock_quantity - ? WHERE product_id = ? AND stock_quantity >= ?");
                        while ($it = $res->fetch_assoc()) {
                            $upd->bind_param("iii", $it['quantity'], $it['product_id'], $it['quantity']);
                            $upd->execute();
                            if ($upd->affected_rows === 0) throw new Exception("Insufficient stock");
                        }

                        $stmt = $conn->prepare("UPDATE `order` SET order_status = 'approved' WHERE order_id = ?");
                        $stmt->bind_param("i", $order_id);
                        $stmt->execute();
                        $conn->commit();
                        
                        autoAssignDriver($conn, $order_id);
                        $success_count++;
                    } catch (Throwable $e) {
                        $conn->rollback();
                        $fail_count++;
                    }
                }
            } elseif ($action === 'reject') {
                $stmt = $conn->prepare("UPDATE `order` SET order_status = 'rejected' WHERE order_id = ? AND order_status = 'pending'");
                $stmt->bind_param("i", $order_id);
                $stmt->execute();
                if ($stmt->affected_rows > 0) $success_count++;
            }
        }
        
        assignWaitingOrders($conn); 
        logActivity($conn, $_SESSION['admin_id'], "Bulk $action: $success_count success, $fail_count failed");
        
        header("Location: orders.php?status=" . ($_GET['status'] ?? 'all') . "&bulk_success=$success_count");
        exit();
    }
}

// --- HANDLE SINGLE APPROVE ACTION ---
if (isset($_POST['approve_order'])) {
    $order_id = (int)$_POST['order_id'];
    $chk = $conn->prepare("SELECT order_status FROM `order` WHERE order_id = ?");
    $chk->bind_param("i", $order_id);
    $chk->execute();
    $cur = $chk->get_result()->fetch_assoc();
    if (!$cur || $cur['order_status'] !== 'pending') { header("Location: orders.php"); exit(); }

    $conn->begin_transaction();
    try {
        $items = $conn->prepare("SELECT product_id, quantity FROM order_item WHERE order_id = ?");
        $items->bind_param("i", $order_id);
        $items->execute();
        $res = $items->get_result();

        $upd = $conn->prepare("UPDATE product SET stock_quantity = stock_quantity - ? WHERE product_id = ? AND stock_quantity >= ?");
        while ($it = $res->fetch_assoc()) {
            $upd->bind_param("iii", $it['quantity'], $it['product_id'], $it['quantity']);
            $upd->execute();
            if ($upd->affected_rows === 0) throw new Exception("Insufficient stock");
        }

        $stmt = $conn->prepare("UPDATE `order` SET order_status = 'approved' WHERE order_id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        logActivity($conn, $_SESSION['admin_id'], "Approve FAILED Order #$order_id");
        header("Location: orders.php?status=pending&error=stock");
        exit();
    }

    $assigned = autoAssignDriver($conn, $order_id);
    assignWaitingOrders($conn);
    logActivity($conn, $_SESSION['admin_id'], $assigned ? "Approved Order #$order_id (Auto-Assigned)" : "Approved Order #$order_id");
    header("Location: orders.php?status=" . ($_GET['status'] ?? 'all'));
    exit();
}

// --- HANDLE SINGLE REJECT ACTION ---
if (isset($_POST['reject_order'])) {
    $order_id = (int)$_POST['order_id'];
    $stmt = $conn->prepare("UPDATE `order` SET order_status = 'rejected' WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    logActivity($conn, $_SESSION['admin_id'], "Rejected Order #" . $order_id);
    header("Location: orders.php?status=" . ($_GET['status'] ?? 'all')); 
    exit();
}

// --- HANDLE PDF EXPORT (FPDF) ---
if (isset($_GET['export_pdf'])) {
    logActivity($conn, $_SESSION['admin_id'], "Exported Orders to PDF");
    require('fpdf/fpdf.php');

    class PDF extends FPDF {
        function Header() {
            $this->SetFont('Arial', 'B', 15); $this->SetTextColor(195, 66, 0); 
            $this->Cell(0, 10, 'KimFresh Orders Report', 0, 1, 'C');
            $this->SetFont('Arial', 'I', 10); $this->SetTextColor(100, 100, 100);
            $this->Cell(0, 5, 'Generated on: ' . date('F j, Y, g:i A'), 0, 1, 'C');
            $this->Ln(10);
            $this->SetFont('Arial', 'B', 10); $this->SetFillColor(195, 66, 0); $this->SetTextColor(255, 255, 255);
            $this->Cell(20, 8, 'Order ID', 1, 0, 'C', true); $this->Cell(60, 8, 'Retailer', 1, 0, 'C', true);
            $this->Cell(30, 8, 'Date', 1, 0, 'C', true); $this->Cell(25, 8, 'Items', 1, 0, 'C', true);
            $this->Cell(30, 8, 'Total', 1, 0, 'C', true); $this->Cell(25, 8, 'Status', 1, 1, 'C', true);
        }
        function Footer() {
            $this->SetY(-15); $this->SetFont('Arial', 'I', 8); $this->SetTextColor(128);
            $this->Cell(0, 10, 'Page ' . $this->PageNo() . ' - KimFresh', 0, 0, 'C');
        }
    }

    $pdf = new PDF(); $pdf->AddPage(); $pdf->SetFont('Arial', '', 10); $pdf->SetTextColor(0, 0, 0);
    $print_query = $conn->query("
        SELECT o.order_id, r.name as retailer_name, o.order_date, o.total_amount, o.order_status,
        (SELECT SUM(quantity) FROM order_item WHERE order_id = o.order_id) as total_items
        FROM `order` o LEFT JOIN retailer r ON o.retailer_id = r.retailer_id ORDER BY o.order_date DESC
    ");
    while($row = $print_query->fetch_assoc()) {
        $pdf->Cell(20, 8, '#' . $row['order_id'], 1, 0, 'C');
        $pdf->Cell(60, 8, htmlspecialchars($row['retailer_name']), 1, 0, 'L');
        $pdf->Cell(30, 8, date('M d, Y', strtotime($row['order_date'])), 1, 0, 'C');
        $pdf->Cell(25, 8, $row['total_items'] . ' items', 1, 0, 'C');
        $pdf->Cell(30, 8, 'PHP ' . number_format($row['total_amount'], 2), 1, 0, 'R');
        $pdf->Cell(25, 8, strtoupper($row['order_status']), 1, 1, 'C');
    }
    $pdf->Output('D', 'KimFresh_Orders.pdf'); exit();
}

// --- SORTING LOGIC ---
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'date_desc';
$orderBy = "o.order_date DESC";
if ($sort == 'id_asc') $orderBy = "o.order_id ASC";
if ($sort == 'id_desc') $orderBy = "o.order_id DESC";
if ($sort == 'retailer_asc') $orderBy = "r.name ASC";
if ($sort == 'retailer_desc') $orderBy = "r.name DESC";
if ($sort == 'status_asc') $orderBy = "o.order_status ASC";
if ($sort == 'status_desc') $orderBy = "o.order_status DESC";
if ($sort == 'total_asc') $orderBy = "o.total_amount ASC";
if ($sort == 'total_desc') $orderBy = "o.total_amount DESC";

// Get counts
$counts = [
    'all' => $conn->query("SELECT COUNT(*) AS c FROM `order`")->fetch_assoc()['c'],
    'pending' => $conn->query("SELECT COUNT(*) AS c FROM `order` WHERE order_status = 'pending'")->fetch_assoc()['c'],
    'approved' => $conn->query("SELECT COUNT(*) AS c FROM `order` WHERE order_status = 'approved'")->fetch_assoc()['c'],
    'waiting_for_pickup' => $conn->query("SELECT COUNT(*) AS c FROM `order` WHERE order_status = 'waiting_for_pickup'")->fetch_assoc()['c'],
    'delivered' => $conn->query("SELECT COUNT(*) AS c FROM `order` WHERE order_status = 'delivered'")->fetch_assoc()['c'],
    'rejected' => $conn->query("SELECT COUNT(*) AS c FROM `order` WHERE order_status = 'rejected'")->fetch_assoc()['c'],
    'cancelled' => $conn->query("SELECT COUNT(*) AS c FROM `order` WHERE order_status = 'cancelled'")->fetch_assoc()['c'] 
];

$current_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// --- PAGINATION LOGIC ---
$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$baseSql = "FROM `order` o LEFT JOIN retailer r ON o.retailer_id = r.retailer_id WHERE 1=1";
if ($current_filter !== 'all') {
    $safe_filter = $conn->real_escape_string($current_filter);
    $baseSql .= " AND o.order_status = '$safe_filter' ";
}

$countQuery = $conn->query("SELECT COUNT(*) AS total $baseSql");
$total_rows = $countQuery->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

$sql = "SELECT o.order_id, r.name as retailer_name, r.customer_code, r.address, o.order_status, o.total_amount, o.order_date
        $baseSql ORDER BY $orderBy LIMIT $limit OFFSET $offset";
$orders = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KimFresh - Orders</title>
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="css/orders.css">
    <link rel="stylesheet" href="css/flow.css">
    <style>
        :root { --primary: #C34200; --primary-dark: #9a3400; }
        th a:hover { color: #C34200 !important; }
        
        /* Status Pill Colors */
        .status-pill.approved { color: #15803d; background: #dcfce7; }
        .status-pill.pending { color: #b45309; background: #fef3c7; }
        .status-pill.rejected { color: #991b1b; background: #fee2e2; }
        .status-pill.delivered { color: #1d4ed8; background: #dbeafe; }
        .status-pill.cancelled { color: #4b5563; background: #e5e7eb; }

        /* Bulk Checkbox Column */
        .col-checkbox { width: 40px; text-align: center; }
        .col-checkbox input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; accent-color: #C34200; }

        /* Floating Bulk Action Bar */
        .bulk-bar {
            position: fixed; bottom: -100px; left: 50%; transform: translateX(-50%);
            background: #333; color: white; padding: 15px 30px; border-radius: 12px;
            display: flex; align-items: center; gap: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            transition: bottom 0.3s ease; z-index: 1000;
        }
        .bulk-bar.show { bottom: 30px; }
        .bulk-bar span { font-weight: bold; font-size: 14px; }
        .bulk-btn { padding: 8px 16px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 13px; transition: 0.2s; }
        .bulk-btn.approve { background: #15803d; color: white; }
        .bulk-btn.approve:hover { background: #166534; }
        .bulk-btn.reject { background: #dc2626; color: white; }
        .bulk-btn.reject:hover { background: #b91c1c; }
        .bulk-btn.cancel { background: transparent; color: #aaa; text-decoration: underline; padding: 8px 0; }
        .bulk-btn.cancel:hover { color: white; }

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

        /* --- ACTION BUTTON STYLES --- */
        .action-group { display: flex; gap: 6px; justify-content: flex-end; align-items: center; }
        .btn-approve { background: #15803d; color: white; padding: 7px 14px; font-size: 12px; font-weight: 700; border: none; border-radius: 6px; cursor: pointer; transition: 0.2s; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; }
        .btn-approve:hover { background: #166534; }
        .btn-reject { background: #dc2626; color: white; padding: 7px 14px; font-size: 12px; font-weight: 700; border: none; border-radius: 6px; cursor: pointer; transition: 0.2s; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; }
        .btn-reject:hover { background: #b91c1c; }
        .btn-view { background: #fff; color: #333; padding: 7px 14px; font-size: 12px; font-weight: 700; border: 1px solid #ddd; border-radius: 6px; cursor: pointer; transition: 0.2s; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; }
        .btn-view:hover { background: #f8f9fa; border-color: #C34200; color: #C34200; }

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
        
        .alert-success { background: #dcfce7; color: #166534; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; border: 1px solid #bbf7d0; }

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
                <img src="logo.png" alt="KimFresh" class="logo">
                <p>Administration</p>
            </div>
            <nav class="menu">
                <a href="dashboard.php">Dashboard</a>
                <a href="orders.php" class="active">Orders</a>
                <a href="products.php">Products</a>
                <a href="drivers.php">Drivers</a>
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
                
                <!-- ALERTS -->
                <?php if (isset($_GET['error']) && $_GET['error'] === 'stock'): ?>
                  <div style="background:#fee2e2;color:#991b1b;padding:12px;border-radius:8px;margin-bottom:12px;font-weight:700; border: 1px solid #f87171;">
                    Approval failed: one or more products don't have enough stock.
                  </div>
                <?php endif; ?>
                <?php if (isset($_GET['bulk_success'])): ?>
                  <div class="alert-success">
                    Successfully processed <?php echo (int)$_GET['bulk_success']; ?> order(s).
                  </div>
                <?php endif; ?>

                <div style="display:flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                    <div>
                        <p class="overview-label">Manage</p>
                        <h1 style="margin:0;">Orders</h1>
                        <p class="page-sub" style="margin-top:5px;">Review retailer orders. Approve or reject pending requests.</p>
                    </div>
                    <a href="?export_pdf=1" download="KimFresh_Orders.pdf" class="btn-export">Export PDF</a>
                </div>

                <div class="toolbar">
                    <div class="tabs">
                        <a href="?status=all&sort=<?php echo $sort; ?>" class="tab <?php echo $current_filter == 'all' ? 'active' : ''; ?>" style="text-decoration:none;">All <span><?php echo $counts['all']; ?></span></a>
                        <a href="?status=pending&sort=<?php echo $sort; ?>" class="tab <?php echo $current_filter == 'pending' ? 'active' : ''; ?>" style="text-decoration:none;">Pending <span><?php echo $counts['pending']; ?></span></a>
                        <a href="?status=approved&sort=<?php echo $sort; ?>" class="tab <?php echo $current_filter == 'approved' ? 'active' : ''; ?>" style="text-decoration:none;">Approved <span><?php echo $counts['approved']; ?></span></a>
                        <a href="?status=waiting_for_pickup&sort=<?php echo $sort; ?>" class="tab <?php echo $current_filter == 'waiting_for_pickup' ? 'active' : ''; ?>" style="text-decoration:none;">In Pickup <span><?php echo $counts['waiting_for_pickup']; ?></span></a>
                        <a href="?status=delivered&sort=<?php echo $sort; ?>" class="tab <?php echo $current_filter == 'delivered' ? 'active' : ''; ?>" style="text-decoration:none;">Delivered <span><?php echo $counts['delivered']; ?></span></a>
                        <a href="?status=rejected&sort=<?php echo $sort; ?>" class="tab <?php echo $current_filter == 'rejected' ? 'active' : ''; ?>" style="text-decoration:none;">Rejected <span><?php echo $counts['rejected']; ?></span></a>
                        <a href="?status=cancelled&sort=<?php echo $sort; ?>" class="tab <?php echo $current_filter == 'cancelled' ? 'active' : ''; ?>" style="text-decoration:none;">Cancelled <span><?php echo $counts['cancelled']; ?></span></a>
                    </div>
                </div>

                <div class="orders-box">
                    <form method="POST" id="bulkForm">
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th class="col-checkbox"><input type="checkbox" id="selectAll"></th>
                                    <th><a href="?status=<?php echo $current_filter; ?>&sort=<?php echo ($sort == 'id_asc' ? 'id_desc' : 'id_asc'); ?>" style="text-decoration:none; color:inherit;">Order ↕</a></th>
                                    <th><a href="?status=<?php echo $current_filter; ?>&sort=<?php echo ($sort == 'retailer_asc' ? 'retailer_desc' : 'retailer_asc'); ?>" style="text-decoration:none; color:inherit;">Retailer ↕</a></th>
                                    <th>Date</th>
                                    <th>Items</th>
                                    <th><a href="?status=<?php echo $current_filter; ?>&sort=<?php echo ($sort == 'total_asc' ? 'total_desc' : 'total_asc'); ?>" style="text-decoration:none; color:inherit;">Total ↕</a></th>
                                    <th><a href="?status=<?php echo $current_filter; ?>&sort=<?php echo ($sort == 'status_asc' ? 'status_desc' : 'status_asc'); ?>" style="text-decoration:none; color:inherit;">Status ↕</a></th>
                                    <th style="text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($orders->num_rows > 0) {
                                    while($row = $orders->fetch_assoc()) {
                                        $status = strtolower($row["order_status"]);
                                        $displayStatus = strtoupper(str_replace('_', ' ', $row["order_status"]));
                                        
                                        $item_query = $conn->query("SELECT SUM(quantity) as total FROM order_item WHERE order_id = " . $row['order_id']);
                                        $item_count = $item_query->fetch_assoc()['total'] ?? 0;
                                        
                                        echo "<tr>";
                                        
                                        if ($row["order_status"] == 'pending') {
                                            echo "<td class='col-checkbox'><input type='checkbox' name='selected_orders[]' value='" . $row["order_id"] . "' class='order-checkbox'></td>";
                                        } else {
                                            echo "<td class='col-checkbox'></td>";
                                        }
                                        
                                        echo "<td><span class='order-no'>#" . $row["order_id"] . "</span></td>";
                                        echo "<td><strong>" . htmlspecialchars($row["retailer_name"]) . "</strong></td>";
                                        echo "<td>" . date('M d, Y, g:i A', strtotime($row['order_date'])) . "</td>";
                                        echo "<td>" . $item_count . " items</td>";
                                        echo "<td class='num'>₱" . number_format($row["total_amount"], 2) . "</td>";
                                        echo "<td><span class='status-pill " . $status . "'>" . $displayStatus . "</span></td>";
                                        
                                        echo "<td>";
                                        echo "<div class='action-group'>";
                                        
                                        if ($row["order_status"] == 'pending') {
                                            echo "<button type='button' name='approve_single' class='btn-approve' onclick='submitSingle(\"approve\", " . $row["order_id"] . ")'>Approve</button>";
                                            echo "<button type='button' name='reject_single' class='btn-reject' onclick='submitSingle(\"reject\", " . $row["order_id"] . ")'>Reject</button>";
                                        }
                                        
                                        echo "<a href='view_order.php?id=" . $row["order_id"] . "' class='btn-view'>View</a>";
                                        
                                        echo "</div>";
                                        echo "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='8' style='text-align:center; padding: 20px;'>No orders found for this filter.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>

                        <input type="hidden" name="bulk_action" id="bulkActionInput" value="">
                    </form>
                </div>

                <div class="bulk-bar" id="bulkBar">
                    <span id="bulkCount">0</span> orders selected
                    <button type="button" class="bulk-btn approve" onclick="submitBulk('approve')">Approve Selected</button>
                    <button type="button" class="bulk-btn reject" onclick="submitBulk('reject')">Reject Selected</button>
                    <button type="button" class="bulk-btn cancel" onclick="clearSelection()">Cancel</button>
                </div>

                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?status=<?php echo $current_filter; ?>&sort=<?php echo $sort; ?>&page=<?php echo $page - 1; ?>">Prev</a>
                    <?php else: ?>
                        <span class="disabled">Prev</span>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?status=<?php echo $current_filter; ?>&sort=<?php echo $sort; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?status=<?php echo $current_filter; ?>&sort=<?php echo $sort; ?>&page=<?php echo $page + 1; ?>">Next</a>
                    <?php else: ?>
                        <span class="disabled">Next</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

            </main>
        </div>
    </div>

    <!-- CUSTOM CONFIRMATION MODAL -->
    <dialog id="actionModal" style="width: 90%; max-width: 400px; border: none; border-radius: 12px; padding: 24px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); margin: auto;">
        <style>#actionModal::backdrop { background: rgba(0, 0, 0, 0.5); }</style>
        <h2 style="margin-top:0; color: #333;" id="modalTitle">Confirm Action</h2>
        <p style="font-size: 14px; color: #555; margin-bottom: 20px;" id="modalMessage">Are you sure you want to proceed?</p>
        <div style="display: flex; justify-content: flex-end; gap: 10px;">
            <button type="button" onclick="document.getElementById('actionModal').close()" style="padding: 10px 20px; background: #eee; border: none; border-radius: 8px; font-weight: bold; cursor: pointer;">Cancel</button>
            <button type="button" id="modalConfirmBtn" style="padding: 10px 20px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; color: white;">Confirm</button>
        </div>
    </dialog>

    <!-- CUSTOM CTRL+F FIND BAR -->
    <div id="customFindBar" style="display: none; position: fixed; top: 10px; right: 20px; background: white; padding: 10px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 9999; border: 1px solid #ddd;">
        <input type="text" id="findInput" placeholder="Find order..." style="padding: 8px; border: 1px solid #ccc; border-radius: 4px; outline: none; width: 200px;">
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
            if (event.target.closest('.tabs a') || event.target.closest('th a') || event.target.closest('.pagination a')) {
                sessionStorage.setItem('scrollPos', window.scrollY);
            }
        });

        // BULK SELECT LOGIC
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.order-checkbox');
        const bulkBar = document.getElementById('bulkBar');
        const bulkCount = document.getElementById('bulkCount');
        const bulkForm = document.getElementById('bulkForm');
        const bulkActionInput = document.getElementById('bulkActionInput');

        function updateBulkBar() {
            const checkedCount = document.querySelectorAll('.order-checkbox:checked').length;
            bulkCount.textContent = checkedCount;
            if (checkedCount > 0) bulkBar.classList.add('show');
            else bulkBar.classList.remove('show');
        }

        if (selectAll) {
            selectAll.addEventListener('change', function() {
                checkboxes.forEach(cb => cb.checked = this.checked);
                updateBulkBar();
            });
        }
        checkboxes.forEach(cb => cb.addEventListener('change', updateBulkBar));

        function clearSelection() {
            if (selectAll) selectAll.checked = false;
            checkboxes.forEach(cb => cb.checked = false);
            updateBulkBar();
        }

        // CUSTOM MODAL LOGIC
        const actionModal = document.getElementById('actionModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalMessage = document.getElementById('modalMessage');
        const modalConfirmBtn = document.getElementById('modalConfirmBtn');
        let pendingAction = null;

        function openConfirmModal(title, message, btnText, btnColor, actionCallback) {
            modalTitle.textContent = title;
            modalMessage.textContent = message;
            modalConfirmBtn.textContent = btnText;
            modalConfirmBtn.style.background = btnColor;
            pendingAction = actionCallback;
            actionModal.showModal();
        }

        modalConfirmBtn.addEventListener('click', function() {
            if (pendingAction) { pendingAction(); actionModal.close(); }
        });

        function submitBulk(action) {
            const count = document.querySelectorAll('.order-checkbox:checked').length;
            const isApprove = action === 'approve';
            openConfirmModal(
                isApprove ? "Approve Orders" : "Reject Orders",
                `Are you sure you want to ${action} these ${count} orders?`,
                isApprove ? "Approve Selected" : "Reject Selected",
                isApprove ? "#15803d" : "#dc2626",
                function() { bulkActionInput.value = action; bulkForm.submit(); }
            );
        }

        function submitSingle(action, orderId) {
            const isApprove = action === 'approve';
            openConfirmModal(
                isApprove ? "Approve Order" : "Reject Order",
                `Are you sure you want to ${action} Order #${orderId}?`,
                isApprove ? "Approve" : "Reject",
                isApprove ? "#15803d" : "#dc2626",
                function() {
                    const form = document.createElement('form');
                    form.method = 'POST'; form.style.display = 'none';
                    const actionInput = document.createElement('input');
                    actionInput.type = 'hidden'; actionInput.name = action + '_order'; form.appendChild(actionInput);
                    const idInput = document.createElement('input');
                    idInput.type = 'hidden'; idInput.name = 'order_id'; idInput.value = orderId; form.appendChild(idInput);
                    document.body.appendChild(form); form.submit();
                }
            );
        }

        // PROFILE & FIND BAR
        function toggleProfileMenu(event) {
            event.stopPropagation();
            document.getElementById('profileDropdown').classList.toggle('show');
        }
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('profileMenu');
            if (menu && !menu.contains(event.target)) {
                document.getElementById('profileDropdown').classList.remove('show');
            }
        });

        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                e.preventDefault(); 
                const findBar = document.getElementById('customFindBar');
                findBar.style.display = 'flex'; document.getElementById('findInput').focus();
            }
            if (e.key === 'Escape') {
                document.getElementById('customFindBar').style.display = 'none';
                document.getElementById('findInput').value = '';
                document.querySelectorAll('table tbody tr').forEach(row => row.style.display = '');
            }
        });

        document.getElementById('closeFind').addEventListener('click', function() {
            document.getElementById('customFindBar').style.display = 'none';
            document.getElementById('findInput').value = '';
            document.querySelectorAll('table tbody tr').forEach(row => row.style.display = '');
        });

        document.getElementById('findInput').addEventListener('keyup', function() {
            const query = this.value.toLowerCase();
            const rows = document.querySelectorAll('table tbody tr');
            rows.forEach(row => {
                if (row.cells.length < 2) return; 
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });

               // =========================================
        // REAL-TIME NOTIFICATIONS & DROPDOWN (MARK AS READ)
        // =========================================
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
</body>
</html>