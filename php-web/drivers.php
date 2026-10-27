<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
require 'db.php';
require 'log_helper.php';
require 'mailer.php'; 

// FIX: Session security
if (!isset($_SESSION['admin_id'])) { header("Location: login.php"); exit(); }

// --- HANDLE ACTIVATE/DEACTIVATE ---
if (isset($_POST['toggle_status'])) {
    $driver_id = (int)$_POST['driver_id'];
    $new_status = $_POST['new_status'];
    
    $stmt = $conn->prepare("UPDATE driver SET status = ? WHERE driver_id = ?");
    $stmt->bind_param("si", $new_status, $driver_id);
    $stmt->execute();
    
    $action = ($new_status == 'hidden') ? "Deactivated Driver #" : "Activated Driver #";
    logActivity($conn, $_SESSION['admin_id'], $action . $driver_id);
    
    header("Location: drivers.php");
    exit();
}

// --- HANDLE MANUAL ASSIGN ---
if (isset($_POST['assign_order'])) {
    $order_id = (int)$_POST['order_id'];
    $driver_id = (int)$_POST['driver_id'];
    
    // Check if order already has a delivery
    $check = $conn->prepare("SELECT delivery_id FROM delivery WHERE order_id = ?");
    $check->bind_param("i", $order_id);
    $check->execute();
    
    if ($check->get_result()->num_rows > 0) {
        header("Location: drivers.php?error=already_assigned");
        exit();
    }

    // Create delivery record
    $stmt = $conn->prepare("INSERT INTO delivery (order_id, driver_id, delivery_status) VALUES (?, ?, 'assigned')");
    $stmt->bind_param("ii", $order_id, $driver_id);
    $stmt->execute();
    
    // Mark driver as busy
    $conn->query("UPDATE driver SET status = 'on_delivery' WHERE driver_id = $driver_id");
    
    // Mark order as approved
    $conn->query("UPDATE `order` SET order_status = 'approved' WHERE order_id = $order_id AND order_status = 'pending'");
    
    logActivity($conn, $_SESSION['admin_id'], "Manually assigned Order #$order_id to Driver #$driver_id");
    
    header("Location: drivers.php");
    exit();
}

// --- HANDLE BULK DRIVER APPROVAL ---
if (isset($_POST['bulk_approve_drivers'])) {
    if (!empty($_POST['driver_ids'])) {
        foreach ($_POST['driver_ids'] as $driver_id) {
            $driver_id = (int)$driver_id;
            $stmt = $conn->prepare("UPDATE driver SET account_status = 'approved', status = 'available' WHERE driver_id = ?");
            $stmt->bind_param("i", $driver_id);
            $stmt->execute();
            logActivity($conn, $_SESSION['admin_id'], "Bulk Approved Driver Registration #$driver_id");
        }
    }
    header("Location: drivers.php");
    exit();
}

// --- HANDLE SINGLE APPROVAL/REJECTION ---
if (isset($_POST['approve_driver']) || isset($_POST['reject_driver'])) {
    $driver_id = (int)$_POST['driver_id'];
    $is_approve = isset($_POST['approve_driver']);
    
    if ($is_approve) {
        $res = $conn->query("SELECT name, email FROM driver WHERE driver_id = $driver_id");
        $driver = $res->fetch_assoc();
        
        $otp = sprintf("%06d", mt_rand(1, 999999));
        $expiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));
        
        $stmt = $conn->prepare("UPDATE driver SET account_status = 'otp_pending', otp_code = ?, otp_expires_at = ? WHERE driver_id = ?");
        $stmt->bind_param("ssi", $otp, $expiry, $driver_id);
        $stmt->execute();
        
        sendOTPEmail($driver['email'], $driver['name'], $otp, 'Driver');
        logActivity($conn, $_SESSION['admin_id'], "Approved Driver #$driver_id and generated OTP");
    } else {
        $stmt = $conn->prepare("UPDATE driver SET account_status = 'rejected', status = 'hidden', rejected_at = NOW() WHERE driver_id = ?");
        $stmt->bind_param("i", $driver_id);
        $stmt->execute();
        logActivity($conn, $_SESSION['admin_id'], "Rejected Driver Registration #$driver_id");
    }
    
    header("Location: drivers.php");
    exit();
}

// --- FETCH PENDING DRIVERS (PAGINATED) ---
$pending_limit = 5;
$pending_page = isset($_GET['p_page']) && is_numeric($_GET['p_page']) ? (int)$_GET['p_page'] : 1;
if ($pending_page < 1) $pending_page = 1;
$pending_offset = ($pending_page - 1) * $pending_limit;

$total_pending = $conn->query("SELECT COUNT(*) as total FROM driver WHERE account_status = 'pending'")->fetch_assoc()['total'];
$total_pending_pages = ceil($total_pending / $pending_limit);

$pendingDrivers = $conn->query("
    SELECT * FROM driver 
    WHERE account_status = 'pending' 
    ORDER BY created_at ASC
    LIMIT $pending_limit OFFSET $pending_offset
");

// --- FILTER LOGIC FOR ACTIVE DRIVERS ---
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all'; 
$orderBy = "d.name ASC"; 

$sql = "
    SELECT d.*,
           (SELECT dl.delivery_status 
            FROM delivery dl 
            INNER JOIN `order` o ON o.order_id = dl.order_id
            WHERE dl.driver_id = d.driver_id AND o.order_status != 'delivered' 
            ORDER BY dl.delivery_id DESC LIMIT 1) AS active_delivery_status,
           (SELECT COUNT(*) 
            FROM delivery dl 
            INNER JOIN `order` o ON o.order_id = dl.order_id
            WHERE dl.driver_id = d.driver_id AND o.order_status != 'delivered') AS active_orders
    FROM driver d
    WHERE d.account_status = 'approved'
";

if ($filter === 'all') {
    $sql .= " AND d.status != 'hidden'";
} elseif ($filter === 'available') {
    $sql .= " AND (SELECT COUNT(*) FROM delivery dl INNER JOIN `order` o ON o.order_id = dl.order_id WHERE dl.driver_id = d.driver_id AND o.order_status != 'delivered') = 0 AND d.status != 'hidden'";
} elseif ($filter === 'assigned') {
    $sql .= " AND (SELECT dl.delivery_status FROM delivery dl INNER JOIN `order` o ON o.order_id = dl.order_id WHERE dl.driver_id = d.driver_id AND o.order_status != 'delivered' ORDER BY dl.delivery_id DESC LIMIT 1) = 'assigned'";
} elseif ($filter === 'on_delivery') {
    $sql .= " AND (SELECT dl.delivery_status FROM delivery dl INNER JOIN `order` o ON o.order_id = dl.order_id WHERE dl.driver_id = d.driver_id AND o.order_status != 'delivered' ORDER BY dl.delivery_id DESC LIMIT 1) IN ('in_transit', 'picked_up')";
} elseif ($filter === 'inactive') {
    $sql .= " AND d.status = 'hidden'";
} 

$sql .= " ORDER BY $orderBy";
$drivers = $conn->query($sql);

// --- FETCH UNASSIGNED ORDERS (For Manual Assign) ---
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

        .filters { display: flex; gap: 12px; margin-top: 24px; margin-bottom: 24px; justify-content: flex-end; }
        .filters select { width: 220px; height: 44px; padding: 0 16px; border: 1px solid #ddd; border-radius: 10px; font-size: 13px; outline: none; background: #fff; font-weight: bold; color: #333; }
        .filters select:focus { border-color: #C34200; }

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

        th a {
    text-decoration: none !important;
}

        .assign-form { display: flex; gap: 5px; margin-top: 5px; }
        .assign-form select { flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 8px; font-size: 12px; outline: none; }
        .assign-form button { padding: 8px 12px; background: #C34200; color: white; border: none; border-radius: 8px; font-size: 12px; font-weight: bold; cursor: pointer; }

        @media (max-width: 768px) {
            .page-head { flex-direction: column !important; align-items: stretch !important; gap: 15px !important; }
            .page-head form { width: 100% !important; }
            .page-head .btn-export { width: 100% !important; padding: 12px !important; text-align: center !important; }
            .filters { justify-content: stretch !important; margin-top: 10px !important; margin-bottom: 10px !important; }
            .filters select { width: 100% !important; height: 48px; }
            .driver-grid { grid-template-columns: 1fr !important; gap: 16px !important; }
            .driver-card { padding: 16px !important; }
            .assign-form { flex-direction: column !important; gap: 8px !important; }
            .assign-form select { width: 100% !important; padding: 10px !important; }
            .assign-form button { width: 100% !important; padding: 10px !important; }
            .btn-export, .history-btn, .btn-activate, .btn-deactivate { padding: 12px !important; font-size: 13px !important; }
        }
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
                <div class="page-head" style="display:flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                    <div>
                        <p class="overview-label">Manage</p>
                        <h1 style="margin:0;">Drivers</h1>
                    </div>
                    <a href="?export_pdf=1" download="KimFresh_Drivers.pdf" class="btn-export">Export to PDF</a>
                </div>

                <!-- PENDING DRIVERS SECTION (PAGINATED) -->
                <?php if ($total_pending > 0): ?>
                    <div class="pending-section" style="margin-bottom: 30px; background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #fde047; box-shadow: 0 4px 12px rgba(253, 224, 71, 0.15);">
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                            <h2 style="margin: 0; color: #b45309; font-size: 18px;">
                                Pending Driver Approvals (<?php echo $total_pending; ?>)
                            </h2>
                            <form id="bulk-driver-form" method="POST">
                                <button type="submit" name="bulk_approve_drivers" style="background: #15803d; color: white; padding: 8px 16px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; transition: 0.2s;">
                                    Approve Selected
                                </button>
                            </form>
                        </div>
                        
                        <p style="font-size: 13px; color: #666; margin-bottom: 16px;">These drivers cannot log in to the mobile app until approved.</p>
                        
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 5px;">
                                <input type="checkbox" id="selectAllDrivers" style="width: 16px; height: 16px; cursor: pointer;">
                                <label for="selectAllDrivers" style="font-size: 13px; font-weight: bold; color: #555; cursor: pointer;">Select All on this page</label>
                            </div>

                            <?php while($pd = $pendingDrivers->fetch_assoc()): ?>
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px; border: 1px solid #fef08a; background: #fefce8; border-radius: 8px;">
                                    <div style="display: flex; align-items: center; gap: 15px;">
                                        <input type="checkbox" form="bulk-driver-form" name="driver_ids[]" value="<?php echo $pd['driver_id']; ?>" class="driver-checkbox" style="width: 16px; height: 16px; cursor: pointer;">
                                        <div>
                                            <strong style="display: block; font-size: 16px; color: #333;"><?php echo htmlspecialchars($pd['name']); ?></strong>
                                            <span style="font-size: 13px; color: #666;"><?php echo htmlspecialchars($pd['email']); ?> | License: <?php echo htmlspecialchars($pd['license_number'] ?? 'N/A'); ?></span>
                                            <div style="font-size: 11px; color: #999; margin-top: 4px;">Registered: <?php echo date('M d, Y g:i A', strtotime($pd['created_at'])); ?></div>
                                        </div>
                                    </div>
                                    
                                    <div style="display: flex; gap: 8px;">
                                        <form method="POST" style="margin:0;">
                                            <input type="hidden" name="driver_id" value="<?php echo $pd['driver_id']; ?>">
                                            <button type="submit" name="reject_driver" style="background: #dc2626; color: white; padding: 8px 16px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; transition: 0.2s;" onclick="return confirm('Reject this driver?');">Reject</button>
                                        </form>
                                        <form method="POST" style="margin:0;">
                                            <input type="hidden" name="driver_id" value="<?php echo $pd['driver_id']; ?>">
                                            <button type="submit" name="approve_driver" style="background: #15803d; color: white; padding: 8px 16px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; transition: 0.2s;">Approve & Send OTP</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>

                        <?php if ($total_pending_pages > 1): ?>
                        <div style="display: flex; justify-content: center; gap: 6px; margin-top: 20px;">
                            <?php if ($pending_page > 1): ?>
                                <a href="?p_page=<?php echo $pending_page - 1; ?>" style="padding: 6px 12px; border: 1px solid #ddd; border-radius: 6px; text-decoration: none; color: #555; background: white;">Prev</a>
                            <?php endif; ?>

                            <span style="padding: 6px 12px; background: #C34200; color: white; border-radius: 6px; font-weight: bold;">
                                Page <?php echo $pending_page; ?> of <?php echo $total_pending_pages; ?>
                            </span>

                            <?php if ($pending_page < $total_pending_pages): ?>
                                <a href="?p_page=<?php echo $pending_page + 1; ?>" style="padding: 6px 12px; border: 1px solid #ddd; border-radius: 6px; text-decoration: none; color: #555; background: white;">Next</a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- ACTIVE DRIVERS GRID -->
                <form method="GET" class="filters no-print">
                    <select name="filter" onchange="this.form.submit()">
                        <option value="all" <?php echo $filter == 'all' ? 'selected' : ''; ?>>All Drivers</option>
                        <option value="available" <?php echo $filter == 'available' ? 'selected' : ''; ?>>Available</option>
                        <option value="assigned" <?php echo $filter == 'assigned' ? 'selected' : ''; ?>>Assigned</option>
                        <option value="on_delivery" <?php echo $filter == 'on_delivery' ? 'selected' : ''; ?>>On Delivery</option>
                        <option value="inactive" <?php echo $filter == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </form>

                <div class="driver-grid" style="margin-top: 20px;">
                    <?php if ($drivers->num_rows > 0): ?>
                        <?php while($row = $drivers->fetch_assoc()):
                            $activeCount = (int)$row['active_orders'];
                            $deliveryStatus = $row['active_delivery_status'];
                            $isInactive = $row['status'] === 'hidden';

                            if ($isInactive) {
                                $statusClass = 'inactive';
                                $statusLabel = 'Inactive';
                            } elseif ($activeCount == 0) {
                                $statusClass = 'available';
                                $statusLabel = 'Available';
                            } elseif ($deliveryStatus == 'assigned') {
                                $statusClass = 'assigned';
                                $statusLabel = 'Assigned';
                            } else {
                                $statusClass = 'on-delivery';
                                $statusLabel = 'On Delivery';
                            }

                            $initial     = strtoupper(substr($row['name'], 0, 1));
                            $driverId    = (int)$row['driver_id'];
                            $driverPast  = $histories[$driverId] ?? [];
                        ?>
                            <div class='driver-card'>
                                <div class='driver-top'>
                                    <div class='driver-avatar'><?php echo $initial; ?></div>
                                    <div class='driver-title'>
                                        <div class='driver-name'><?php echo htmlspecialchars($row['name']); ?></div>
                                        <div class='driver-license' style="font-weight: 500; color: #666; margin-top: 2px;">License: <?php echo htmlspecialchars($row['license_number'] ?? 'N/A'); ?></div>
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
                                    
                                    <!-- MANUAL ASSIGN FORM -->
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

                                    <button
                                        class="history-btn"
                                        type="button"
                                        data-driver-id="<?php echo $driverId; ?>"
                                        data-driver-name="<?php echo htmlspecialchars($row['name']); ?>"
                                        <?php if (empty($driverPast)) echo 'disabled title="No past deliveries for this driver"'; ?>
                                    >
                                        View Delivery History
                                    </button>

                                    <?php if ($isInactive): ?>
                                        <form method='POST' style='margin:0;'>
                                            <input type='hidden' name='driver_id' value='<?php echo $driverId; ?>'>
                                            <input type='hidden' name='new_status' value='available'>
                                            <button type='submit' name='toggle_status' class='btn-activate'>Activate Driver</button>
                                        </form>
                                    <?php else: ?>
                                        <form method='POST' style='margin:0;'>
                                            <input type='hidden' name='driver_id' value='<?php echo $driverId; ?>'>
                                            <input type='hidden' name='new_status' value='hidden'>
                                            <button type='submit' name='toggle_status' class='btn-deactivate'>Deactivate Driver</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <template id="history-tpl-<?php echo $driverId; ?>">
                                <?php if (!empty($driverPast)): ?>
                                    <table class="history-table">
                                        <thead>
                                            <tr>
                                                <th>Order</th>
                                                <th>Retailer</th>
                                                <th>Items</th>
                                                <th>Total</th>
                                                <th>Order Status</th>
                                                <th>Delivery Status</th>
                                                <th>Assigned</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($driverPast as $h):
                                            $orderClass    = strtolower(str_replace(' ', '_', $h['order_status']));
                                            $deliveryClass = strtolower(str_replace(' ', '_', $h['delivery_status']));
                                        ?>
                                            <tr>
                                                <td><span class="order-no">#<?php echo (int)$h['order_id']; ?></span></td>
                                                <td><?php echo htmlspecialchars($h['retailer_name'] ?? '—'); ?></td>
                                                <td><?php echo (int)($h['total_items'] ?? 0); ?></td>
                                                <td>₱<?php echo number_format((float)$h['total_amount'], 2); ?></td>
                                                <td><span class="status-pill <?php echo $orderClass; ?>"><?php echo strtoupper(str_replace('_', ' ', $h['order_status'])); ?></span></td>
                                                <td><span class="status-pill <?php echo $deliveryClass; ?>"><?php echo strtoupper(str_replace('_', ' ', $h['delivery_status'])); ?></span></td>
                                                <td><?php echo htmlspecialchars($h['assigned_at'] ?? $h['order_date']); ?></td>
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
                        <p style="text-align:center; padding: 20px;">No drivers found matching your filter.</p>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Order History Modal -->
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

    <script>
        // --- SELECT ALL DRIVERS LOGIC ---
        const selectAllDrivers = document.getElementById('selectAllDrivers');
        if (selectAllDrivers) {
            selectAllDrivers.addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.driver-checkbox');
                checkboxes.forEach(cb => cb.checked = this.checked);
            });
        }

        // --- HISTORY MODAL LOGIC ---
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

        historyModal.addEventListener('click', e => {
            if (e.target === historyModal) closeHistory();
        });

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

        // --- FIX PDF EXPORT ---
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
