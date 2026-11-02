<?php
session_start();
require 'db.php';
require 'log_helper.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// --- SMART STORE STATUS LOGIC ---
$store = $conn->query("SELECT * FROM store_settings WHERE id = 1")->fetch_assoc();
date_default_timezone_set('Asia/Manila');
$current_time = date('H:i:s');
$is_store_open = false;

if ($store['manual_override'] !== NULL) {
    $is_store_open = ($store['manual_override'] == 1);
} else {
    if ($store['open_time'] <= $store['close_time']) {
        $is_store_open = ($current_time >= $store['open_time'] && $current_time <= $store['close_time']);
    } else {
        $is_store_open = ($current_time >= $store['open_time'] || $current_time <= $store['close_time']);
    }
}

// Handle Manual Override Toggle
if (isset($_POST['toggle_manual'])) {
    $override = $_POST['override_value'];
    $stmt = $conn->prepare("UPDATE store_settings SET manual_override = ? WHERE id = 1");
    $stmt->bind_param("i", $override);
    $stmt->execute();
    logActivity($conn, $_SESSION['admin_id'], ($override == 1) ? "Manually Opened Store" : "Manually Closed Store");
    header("Location: products.php");
    exit();
}

// Handle Reset to Schedule
if (isset($_POST['reset_schedule'])) {
    $stmt = $conn->prepare("UPDATE store_settings SET manual_override = NULL WHERE id = 1");
    $stmt->execute();
    logActivity($conn, $_SESSION['admin_id'], "Reset Store to Auto Schedule");
    header("Location: products.php");
    exit();
}

// --- HANDLE PDF EXPORT (FPDF) ---
if (isset($_GET['export_pdf'])) {
    logActivity($conn, $_SESSION['admin_id'], "Exported Products to PDF");
    require('fpdf/fpdf.php');

    class PDF extends FPDF {
        function Header() {
            $this->SetFont('Arial', 'B', 15); $this->SetTextColor(195, 66, 0); 
            $this->Cell(0, 10, 'KimFresh Product Inventory', 0, 1, 'C');
            $this->SetFont('Arial', 'I', 10); $this->SetTextColor(100, 100, 100);
            $this->Cell(0, 5, 'Generated on: ' . date('F j, Y, g:i A'), 0, 1, 'C');
            $this->Ln(10);
            $this->SetFont('Arial', 'B', 10); $this->SetFillColor(195, 66, 0); $this->SetTextColor(255, 255, 255);
            $this->Cell(60, 8, 'Product Name', 1, 0, 'C', true); $this->Cell(30, 8, 'SKU', 1, 0, 'C', true);
            $this->Cell(30, 8, 'Price', 1, 0, 'C', true); $this->Cell(20, 8, 'Stock', 1, 0, 'C', true);
            $this->Cell(25, 8, 'Reorder', 1, 0, 'C', true); $this->Cell(25, 8, 'Status', 1, 1, 'C', true);
        }
        function Footer() {
            $this->SetY(-15); $this->SetFont('Arial', 'I', 8); $this->SetTextColor(128);
            $this->Cell(0, 10, 'Page ' . $this->PageNo() . ' - KimFresh IoT System', 0, 0, 'C');
        }
    }

    $pdf = new PDF(); $pdf->AddPage(); $pdf->SetFont('Arial', '', 10); $pdf->SetTextColor(0, 0, 0);
    $products_query = $conn->query("SELECT * FROM product ORDER BY product_id ASC");
    while($row = $products_query->fetch_assoc()) {
        $pdf->Cell(60, 8, htmlspecialchars($row['name']), 1, 0, 'L');
        $pdf->Cell(30, 8, htmlspecialchars($row['sku']), 1, 0, 'C');
        $pdf->Cell(30, 8, 'PHP ' . number_format($row['unit_price'], 2), 1, 0, 'R');
        $pdf->Cell(20, 8, $row['stock_quantity'], 1, 0, 'C');
        $pdf->Cell(25, 8, $row['reorder_level'], 1, 0, 'C');
        $pdf->Cell(25, 8, strtoupper($row['status']), 1, 1, 'C');
    }
    $pdf->Output('D', 'KimFresh_Products.pdf');
    exit();
}

// --- HANDLE HARD DELETE ---
if (isset($_POST['delete_product'])) {
    $product_id = $_POST['product_id'];
    $stmt = $conn->prepare("SELECT name FROM product WHERE product_id = ?");
    $stmt->bind_param("i", $product_id); $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $product_name = $product ? $product['name'] : 'Unknown';

    $stmt = $conn->prepare("DELETE FROM order_item WHERE product_id = ?");
    $stmt->bind_param("i", $product_id); $stmt->execute();
    $stmt = $conn->prepare("DELETE FROM product WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    if ($stmt->execute()) logActivity($conn, $_SESSION['admin_id'], "Permanently Deleted Product: " . $product_name);
    header("Location: products.php"); exit();
}

$error_msg = "";

// Handle Add/Edit
if (isset($_POST['save_product'])) {
    $product_id = $_POST['product_id'];
    $name = $_POST['name']; $sku = $_POST['sku']; $price = $_POST['unit_price']; $stock = $_POST['stock_quantity']; $reorder = $_POST['reorder_level'];

    if ($price < 0 || $stock < 0 || $reorder < 0) {
        $error_msg = "Error: Price, Stock, and Reorder level cannot be negative!";
    } else {
        if (empty($product_id)) {
            $stmt = $conn->prepare("INSERT INTO product (name, sku, unit_price, stock_quantity, reorder_level, status) VALUES (?, ?, ?, ?, ?, 'available')");
            $stmt->bind_param("ssdii", $name, $sku, $price, $stock, $reorder);
        } else {
            $stmt = $conn->prepare("UPDATE product SET name=?, sku=?, unit_price=?, stock_quantity=?, reorder_level=? WHERE product_id=?");
            $stmt->bind_param("ssdiii", $name, $sku, $price, $stock, $reorder, $product_id);
        }
        $stmt->execute();
        logActivity($conn, $_SESSION['admin_id'], (empty($product_id) ? "Added new product: " : "Updated product: ") . $name);
        
        // SAVE ID TO SESSION FOR HIGHLIGHTING
        $_SESSION['highlight_products'] = [empty($product_id) ? $conn->insert_id : $product_id];

        header("Location: products.php"); exit();
    }
}

// Fetch Data for Stats
$total_products = $conn->query("SELECT COUNT(*) AS c FROM product")->fetch_assoc()['c'];
$low_stock = $conn->query("SELECT COUNT(*) AS c FROM product WHERE stock_quantity <= reorder_level")->fetch_assoc()['c'];
$available_products = $conn->query("SELECT COUNT(*) AS c FROM product WHERE status = 'available' AND stock_quantity > 0")->fetch_assoc()['c'];

// --- SORTING LOGIC ---
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'id_asc';
$orderBy = "product_id ASC"; 
if ($sort == 'name_asc') $orderBy = "name ASC"; if ($sort == 'name_desc') $orderBy = "name DESC";
if ($sort == 'price_asc') $orderBy = "unit_price ASC"; if ($sort == 'price_desc') $orderBy = "unit_price DESC";
if ($sort == 'stock_asc') $orderBy = "stock_quantity ASC"; if ($sort == 'stock_desc') $orderBy = "stock_quantity DESC";

// --- PAGINATION LOGIC (LIMIT SET TO 7) ---
$limit = 7;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$countQuery = $conn->query("SELECT COUNT(*) AS total FROM product");
$total_rows = $countQuery->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

$sql = "SELECT * FROM product ORDER BY $orderBy LIMIT $limit OFFSET $offset";
$products = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KimFresh - Products</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/products.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/responsive.css">
    
    <style>
        :root { --primary: #C34200; --primary-dark: #9a3400; --primary-soft: #fdf0e6; }
        .table-section { background: white; border-radius: 16px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; font-size: 13px; color: #666; border-bottom: 1px solid #eee; background: transparent; font-weight: 700; }
        td { padding: 15px; border-bottom: 1px solid #f5f5f5; font-size: 13px; }
        th a { color: #666; transition: color 0.2s ease; text-decoration: none; }
        th a:hover { color: #C34200 !important; text-decoration: underline; }
        .product-name { font-weight: 800; color: #333; font-size: 14px; margin-bottom: 3px; }
        .product-sku { color: #888; font-size: 12px; }
        .price { font-weight: 700; color: #333; }
        .status { padding: 4px 12px; font-size: 11px; font-weight: 700; border-radius: 20px; }
        .status.in-stock, .status.available { color: #15803d; background: #dcfce7; }
        .status.low-stock { color: #b45309; background: #fef3c7; }
        .actions { display: flex; gap: 8px; }
        .btn-edit { padding: 7px 16px; font-size: 12px; font-weight: 700; color: #333; background: #fff; border: 1px solid #ddd; border-radius: 8px; cursor: pointer; text-decoration: none; }
        .btn-edit:hover { background: #f8f9fa; }
        .btn-delete { padding: 7px 16px; font-size: 12px; font-weight: 700; color: white; background: #dc2626; border: 1px solid #dc2626; border-radius: 8px; cursor: pointer; }
        .btn-delete:hover { background: #b91c1c; }
        dialog { width: 90%; max-width: 460px; margin: auto; padding: 28px; border: none; border-radius: 18px; box-shadow: 0 20px 60px rgba(0,0,0,0.35); }
        dialog::backdrop { background: rgba(0,0,0,0.55); }
        dialog h2 { font-size: 20px; font-weight: 800; margin-bottom: 15px; }
        dialog label { display: block; margin-bottom: 5px; font-size: 12px; font-weight: 700; color: #555;}
        dialog input { width: 100%; height: 40px; margin-bottom: 15px; padding: 0 14px; font-size: 13px; border: 1px solid #ddd; border-radius: 8px; outline: none; }
        dialog .row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        dialog .dialog-buttons { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
        dialog .cancel-btn { padding: 10px 20px; background: #eee; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; }
        dialog .primary-btn { padding: 10px 20px; background: #C34200; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; }
        .error-banner { background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; border: 1px solid #f87171; }
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
        .pagination { display: flex; justify-content: center; gap: 6px; margin-top: 24px; }
        .pagination a, .pagination span { display: inline-flex; align-items: center; justify-content: center; min-width: 36px; height: 36px; padding: 0 12px; border-radius: 8px; font-size: 13px; font-weight: 700; text-decoration: none; border: 1px solid #ddd; color: #555; background: white; transition: 0.2s; }
        .pagination a:hover { background: #fdf0e6; border-color: #C34200; color: #C34200; }
        .pagination .active { background: #C34200; border-color: #C34200; color: white; }
        .pagination .disabled { opacity: 0.5; pointer-events: none; }
        .store-status-box { background: white; padding: 15px 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; }
        .store-status-box.open { border-left: 5px solid #15803d; }
        .store-status-box.closed { border-left: 5px solid #dc2626; }
        .btn-store { color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; cursor: pointer; }
        .btn-store.open { background: #dc2626; }
        .btn-store.closed { background: #15803d; }
        
        @media print {
            .sidebar, .top-header, .page-head, .actions, .pagination, .btn-export, .primary-btn, .store-status-box { display: none !important; }
            body { background: white !important; margin: 0 !important; padding: 0 !important; }
            .dashboard-layout { display: block !important; }
            .main-area { margin-left: 0 !important; width: 100% !important; }
            .main-content { padding: 0 !important; }
            .table-section { box-shadow: none !important; border: none !important; padding: 0 !important; margin-top: 0 !important; }
            table { width: 100% !important; border-collapse: collapse !important; }
            th, td { border: 1px solid #000 !important; padding: 8px !important; font-size: 12px !important; color: #000 !important; }
            th { background: #eee !important; }
            h1 { margin-bottom: 20px !important; text-align: center; color: #C34200 !important; }
            .stats { display: flex !important; gap: 20px; margin-bottom: 20px; }
            .stat { border: 1px solid #000 !important; padding: 10px !important; flex: 1; text-align: center; }
            .stat h2 { margin: 0; font-size: 18px; }
            .stat p { margin: 0 0 5px 0; font-size: 12px; }
            th:last-child, td:last-child { display: none !important; }
        }
        
        @media (max-width: 768px) {
            .page-head { flex-direction: column !important; align-items: stretch !important; gap: 15px !important; }
            .page-head > div:last-child { display: flex !important; flex-direction: row !important; gap: 10px !important; width: 100% !important; flex-wrap: wrap !important; }
            .page-head .btn-export, .page-head .primary-btn { width: auto !important; flex: 1 !important; text-align: center !important; padding: 10px 12px !important; font-size: 13px !important; white-space: nowrap !important; }
            .table-section { padding: 10px !important; }
            .table-responsive { overflow-x: hidden !important; width: 100% !important; }
            table { width: 100% !important; min-width: 0 !important; table-layout: fixed; }
            th, td { padding: 8px 4px !important; font-size: 11px !important; word-wrap: break-word; white-space: normal !important; }
            th:nth-child(4), td:nth-child(4) { display: none !important; }
            .actions { flex-direction: column; gap: 4px; }
            .btn-edit, .btn-delete { padding: 4px 6px !important; font-size: 10px !important; width: 100%; text-align: center; }
            .status { font-size: 9px !important; padding: 2px 4px !important; display: inline-block; }
            .store-status-box { flex-direction: column; gap: 15px; align-items: stretch; }
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

        /* =========================================
           ACTION MENU (3 DOTS)
           ========================================= */
        .action-menu-container { position: relative; display: inline-block; }
        .action-menu-btn {
            background: transparent; border: 1px solid #ddd; border-radius: 8px;
            width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;
            cursor: pointer; color: #333; transition: 0.2s;
        }
        .action-menu-btn:hover { background: #f8f9fa; border-color: #C34200; color: #C34200; }
        .action-menu-dropdown {
            position: absolute; top: 45px; right: 0; background: white; border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15); width: 180px; padding: 8px;
            display: none; z-index: 100; border: 1px solid #eee;
        }
        .action-menu-dropdown.show { display: block; }
        .action-menu-item {
            display: flex; align-items: center; gap: 10px; padding: 10px 12px;
            text-decoration: none; color: #333; font-size: 13px; font-weight: 600;
            border-radius: 8px; transition: 0.2s; border: none; background: transparent;
            width: 100%; cursor: pointer; text-align: left;
        }
        .action-menu-item:hover { background: #fdf0e6; color: #C34200; }
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
                <a href="products.php" class="active">Products</a>
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
                <?php if (isset($_GET['import_success'])): ?>
                    <div style="background:#dcfce7;color:#166534;padding:12px;border-radius:8px;margin-bottom:20px;font-weight:bold; border:1px solid #bbf7d0;">
                        Successfully imported/updated <?php echo (int)$_GET['import_success']; ?> products!
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['error'])): ?>
                    <div style="background:#fee2e2;color:#991b1b;padding:12px;border-radius:8px;margin-bottom:20px;font-weight:bold; border:1px solid #f87171;">
                        There was an error processing your CSV file. Please check the format.
                    </div>
                <?php endif; ?>
                <?php if(!empty($error_msg)): ?>
                    <div class="error-banner"><?php echo $error_msg; ?></div>
                <?php endif; ?>

                <p class="overview-label">Manage</p>

                <div class="page-head" style="display:flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                    <div>
                        <h1 style="margin:0;">Products</h1>
                        <p style="margin-top:5px;">Add, delete and keep track of your kimchi stock.</p>
                    </div>
                    
                    <div style="display:flex; gap:10px; flex-wrap: wrap; align-items: center;">
                        <!-- ACTION MENU (3 DOTS) -->
                        <div class="action-menu-container">
                            <button class="action-menu-btn" onclick="toggleActionMenu(event)">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="1"></circle>
                                    <circle cx="12" cy="5" r="1"></circle>
                                    <circle cx="12" cy="19" r="1"></circle>
                                </svg>
                            </button>
                            <div class="action-menu-dropdown" id="actionMenuDropdown">
                                <a href="csv_handler.php?export_csv=1" class="action-menu-item">Export CSV</a>
                                <a href="?export_pdf=1" download="KimFresh_Products.pdf" class="action-menu-item">Export PDF</a>
                                <button type="button" class="action-menu-item" onclick="document.getElementById('importModal').showModal(); document.getElementById('actionMenuDropdown').classList.remove('show');">Import CSV</button>
                            </div>
                        </div>

                        <button class="primary-btn" onclick="openProductModal()">+ Add Product</button>
                    </div>
                </div>

                <!-- STORE STATUS TOGGLE -->
                <div class="store-status-box <?php echo $is_store_open ? 'open' : 'closed'; ?>">
                    <div>
                        <h3 style="margin: 0; font-size: 16px;">Store Status: 
                            <span style="color: <?php echo $is_store_open ? '#15803d' : '#dc2626'; ?>;">
                                <?php echo $is_store_open ? 'OPEN' : 'CLOSED'; ?>
                            </span>
                        </h3>
                        <p style="margin: 5px 0 0 0; font-size: 13px; color: #666;">
                            <?php 
                            if ($store['manual_override'] !== NULL) {
                                echo "Manual Override Active.";
                            } else {
                                echo "Auto Schedule: " . date('h:i A', strtotime($store['open_time'])) . " - " . date('h:i A', strtotime($store['close_time'])); 
                            }
                            ?>
                        </p>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <?php if ($store['manual_override'] !== NULL): ?>
                            <form method="POST" style="margin:0;">
                                <button type="submit" name="reset_schedule" class="btn-store" style="background: #555;">
                                    Reset to Schedule
                                </button>
                            </form>
                        <?php endif; ?>
                        <form method="POST" style="margin:0;">
                            <input type="hidden" name="override_value" value="<?php echo $is_store_open ? 0 : 1; ?>">
                            <button type="submit" name="toggle_manual" class="btn-store <?php echo $is_store_open ? 'open' : 'closed'; ?>">
                                <?php echo $is_store_open ? 'Deactivate' : 'Activate'; ?>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- STATS WITH AVAILABLE -->
                <div class="stats">
                    <div class="stat"><p>Total Products</p><h2><?php echo $total_products; ?></h2></div>
                    <div class="stat" style="border-left-color: #15803d;"><p>Available Products</p><h2><?php echo $available_products; ?></h2></div>
                    <div class="stat warn"><p>Low Stock</p><h2><?php echo $low_stock; ?></h2></div>
                </div>

                <section class="table-section">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th><a href="?sort=<?php echo ($sort == 'name_asc' ? 'name_desc' : 'name_asc'); ?>">Product ↕</a></th>
                                    <th><a href="?sort=<?php echo ($sort == 'price_asc' ? 'price_desc' : 'price_asc'); ?>">Price ↕</a></th>
                                    <th><a href="?sort=<?php echo ($sort == 'stock_asc' ? 'stock_desc' : 'stock_asc'); ?>">Stock ↕</a></th>
                                    <th>Reorder Level</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($products->num_rows > 0) {
                                    while($row = $products->fetch_assoc()) {
                                        $isLow = $row['stock_quantity'] <= $row['reorder_level'];
                                        $stockLabel = $isLow ? 'Low Stock' : 'In Stock';
                                        $stockClass = $isLow ? 'low-stock' : 'in-stock';
                                        
                                        // HIGHLIGHT LOGIC
                                        $isHighlighted = isset($_SESSION['highlight_products']) && in_array($row['product_id'], $_SESSION['highlight_products']);
                                        $rowStyle = $isHighlighted ? "style='background-color: #fef3c7; transition: background-color 1s ease;'" : "";
                                        
                                        echo "<tr $rowStyle>";
                                        echo "<td data-label='Product'>
                                                <div class='product-name'>" . htmlspecialchars($row['name']) . "</div>
                                                <div class='product-sku'>" . htmlspecialchars($row['sku']) . "</div>
                                              </td>";
                                        echo "<td data-label='Price' class='price'>₱" . number_format($row['unit_price'], 2) . "</td>";
                                        echo "<td data-label='Stock'>" . $row['stock_quantity'] . " <span class='status " . $stockClass . "'>" . $stockLabel . "</span></td>";
                                        echo "<td data-label='Reorder'>" . $row['reorder_level'] . "</td>";
                                        echo "<td data-label='Status'><span class='status available'>Available</span></td>";
                                        
                                        echo "<td data-label='Action' class='actions-cell'>
                                                <div class='actions'>
                                                    <button class='btn-edit' onclick='openProductModal(" . json_encode($row) . ")'>Edit</button>";
                                        
                                        if ($is_store_open) {
                                            echo "<button class='btn-delete' style='opacity: 0.5; cursor: not-allowed;' title='Close the store first to delete products.' disabled>Delete</button>";
                                        } else {
                                            echo "<button class='btn-delete' onclick='openDeleteModal(" . $row['product_id'] . ", \"" . addslashes($row['name']) . "\")'>Delete</button>";
                                        }
                                        echo "</div></td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='6' class='empty' style='text-align:center;'>No products found</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?sort=<?php echo $sort; ?>&page=<?php echo $page - 1; ?>">Prev</a>
                        <?php else: ?>
                            <span class="disabled">Prev</span>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="active"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?sort=<?php echo $sort; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?sort=<?php echo $sort; ?>&page=<?php echo $page + 1; ?>">Next</a>
                        <?php else: ?>
                            <span class="disabled">Next</span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </section>
            </main>
        </div>
    </div>

    <!-- ADD / EDIT MODAL -->
    <dialog id="productModal">
        <h2 id="modalTitle">Add Product</h2>
        <form method="POST">
            <input type="hidden" name="product_id" id="form_id" value="">
            <label>Product Name</label>
            <input type="text" name="name" id="form_name" maxlength="50" required>
            <label>SKU</label>
            <input type="text" name="sku" id="form_sku" maxlength="50">
            <div class="row">
                <div>
                    <label>Price (₱)</label>
                    <input type="number" step="0.01" min="0" name="unit_price" id="form_price" required>
                </div>
                <div>
                    <label>Reorder Level</label>
                    <input type="number" min="1" name="reorder_level" id="form_reorder" required>
                </div>
            </div>
            <label>Stock Quantity</label>
            <input type="number" min="0" name="stock_quantity" id="form_stock" required>
            <div class="dialog-buttons">
                <button type="button" class="cancel-btn" onclick="closeProductModal()">Cancel</button>
                <button type="submit" name="save_product" class="primary-btn">Save Product</button>
            </div>
        </form>
    </dialog>        

    <!-- CUSTOM DELETE CONFIRMATION MODAL -->
    <dialog id="deleteModal">
        <h2 style="color: #dc2626; margin-bottom: 10px;">Confirm Deletion</h2>
        <p style="margin-bottom: 20px; font-size: 14px; color: #555; line-height: 1.5;">
            Are you sure you want to permanently delete <strong id="deleteProductName" style="color: #333;"></strong>? This action cannot be undone.
        </p>
        <form method="POST" id="deleteForm">
            <input type="hidden" name="product_id" id="delete_product_id" value="">
            <div class="dialog-buttons">
                <button type="button" class="cancel-btn" onclick="closeDeleteModal()">Cancel</button>
                <button type="submit" name="delete_product" style="padding: 10px 20px; background: #dc2626; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold;">Yes, Delete</button>
            </div>
        </form>
    </dialog>

    <!-- IMPORT CSV MODAL -->
    <dialog id="importModal" style="width: 90%; max-width: 400px; border: none; border-radius: 12px; padding: 24px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); margin: auto;">
        <style>#importModal::backdrop { background: rgba(0,0,0,0.5); }</style>
        <h2 style="margin-top:0; color: #333;">Import Products (CSV)</h2>
        <p style="font-size: 13px; color: #555; margin-bottom: 15px;">
            Upload a CSV file to bulk-add or update products. 
            <br><br>
            <strong>Required Columns:</strong><br>
            <code>product_id, name, sku, unit_price, stock_quantity, reorder_level, status</code>
            <br><br>
            <em>(Leave <code>product_id</code> blank to add a new product. Use an existing ID to update it.)</em>
        </p>
        <form action="csv_handler.php" method="POST" enctype="multipart/form-data">
            <input type="file" name="csv_file" accept=".csv" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 20px;">
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="document.getElementById('importModal').close()" style="padding: 10px 20px; background: #eee; border: none; border-radius: 8px; font-weight: bold; cursor: pointer;">Cancel</button>
                <button type="submit" name="import_csv" style="padding: 10px 20px; background: #333; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer;">Upload CSV</button>
            </div>
        </form>
    </dialog>

    <!-- CUSTOM CTRL+F FIND BAR -->
    <div id="customFindBar" style="display: none; position: fixed; top: 10px; right: 20px; background: white; padding: 10px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 9999; border: 1px solid #ddd;">
        <input type="text" id="findInput" placeholder="Find product..." style="padding: 8px; border: 1px solid #ccc; border-radius: 4px; outline: none; width: 200px;">
        <button id="closeFind" style="padding: 8px 12px; margin-left: 5px; background: #eee; border: none; border-radius: 4px; cursor: pointer;">X</button>
    </div>

    <!-- CLEAR HIGHLIGHT SESSION -->
    <?php unset($_SESSION['highlight_products']); ?>

    <!-- PAGE ANIMATION & SCRIPTS -->
    <script>
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
            if (event.target.closest('th a') || event.target.closest('.pagination a')) {
                sessionStorage.setItem('scrollPos', window.scrollY);
            }
        });

        // ACTION MENU (3 DOTS)
        function toggleActionMenu(event) {
            event.stopPropagation();
            document.getElementById('actionMenuDropdown').classList.toggle('show');
        }
        document.addEventListener('click', function(event) {
            const container = event.target.closest('.action-menu-container');
            if (!container) {
                document.getElementById('actionMenuDropdown').classList.remove('show');
            }
        });

        // PRODUCT MODAL
        function openProductModal(product = null) {
            const modal = document.getElementById('productModal');
            const title = document.getElementById('modalTitle');
            
            if (product) {
                title.textContent = 'Edit Product';
                document.getElementById('form_id').value = product.product_id;
                document.getElementById('form_name').value = product.name;
                document.getElementById('form_sku').value = product.sku;
                document.getElementById('form_price').value = product.unit_price;
                document.getElementById('form_reorder').value = product.reorder_level;
                document.getElementById('form_stock').value = product.stock_quantity;
            } else {
                title.textContent = 'Add Product';
                document.getElementById('form_id').value = '';
                document.getElementById('form_name').value = '';
                document.getElementById('form_sku').value = '';
                document.getElementById('form_price').value = '';
                document.getElementById('form_reorder').value = '';
                document.getElementById('form_stock').value = '';
            }
            modal.showModal();
        }
        function closeProductModal() { document.getElementById('productModal').close(); }

        // DELETE MODAL
        function openDeleteModal(id, name) {
            document.getElementById('delete_product_id').value = id;
            document.getElementById('deleteProductName').textContent = name;
            document.getElementById('deleteModal').showModal();
        }
        function closeDeleteModal() { document.getElementById('deleteModal').close(); }

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
                    document.querySelectorAll('table tbody tr').forEach(row => row.style.display = '');
                    e.stopImmediatePropagation();
                }
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