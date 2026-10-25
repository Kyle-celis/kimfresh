<?php
session_start();
require 'db.php';
require 'log_helper.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$trigger_print = false;

// 1. Fetch Order Details
$stmt = $conn->prepare("
    SELECT o.*, r.name as retailer_name, r.customer_code, r.address, r.phone 
    FROM `order` o 
    LEFT JOIN retailer r ON o.retailer_id = r.retailer_id 
    WHERE o.order_id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    die("Order not found.");
}

// 2. Fetch Order Items
$items = $conn->query("
    SELECT oi.quantity, oi.unit_price, oi.subtotal, p.name AS product_name 
    FROM order_item oi
    JOIN product p ON oi.product_id = p.product_id
    WHERE oi.order_id = $order_id
");

// 3. Fetch Cold Chain History
$sensor_data = $conn->query("
    SELECT sd.temperature, sd.humidity, sd.reading_timestamp, sd.is_alert
    FROM sensor_data sd
    JOIN delivery d ON sd.delivery_id = d.delivery_id
    WHERE d.order_id = $order_id
    ORDER BY sd.reading_timestamp DESC
    LIMIT 10
");

// 4. Fetch Driver Info (if assigned)
$driver_query = $conn->query("
    SELECT dr.name as driver_name 
    FROM delivery d 
    JOIN driver dr ON d.driver_id = dr.driver_id 
    WHERE d.order_id = $order_id
");
$driver = $driver_query->fetch_assoc();
$driver_name = $driver ? $driver['driver_name'] : 'No driver assigned yet';

// --- HANDLE PDF EXPORT (FPDF) ---
if (isset($_GET['export_pdf'])) {
    logActivity($conn, $_SESSION['admin_id'], "Exported Order #$order_id Receipt to PDF");
    
    require('fpdf/fpdf.php');

    class PDF extends FPDF {
        function Header() {
            $this->SetFont('Arial', 'B', 15);
            $this->SetTextColor(195, 66, 0); 
            $this->Cell(0, 10, 'KimFresh Order Receipt', 0, 1, 'C');
            $this->SetFont('Arial', 'I', 10);
            $this->SetTextColor(100, 100, 100);
            $this->Cell(0, 5, 'Generated on: ' . date('F j, Y, g:i A'), 0, 1, 'C');
            $this->Ln(10);
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

    // Retailer Info
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(0, 8, 'Retailer Information', 0, 1);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6, 'Name: ' . htmlspecialchars($order['retailer_name']), 0, 1);
    $pdf->Cell(0, 6, 'Code: ' . htmlspecialchars($order['customer_code']), 0, 1);
    $pdf->Cell(0, 6, 'Address: ' . htmlspecialchars($order['address']), 0, 1);
    $pdf->Ln(5);

    // Order Info
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(0, 8, 'Order Information', 0, 1);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6, 'Order ID: #' . $order_id, 0, 1);
    $pdf->Cell(0, 6, 'Date: ' . date('M d, Y h:i A', strtotime($order['order_date'])), 0, 1);
    $pdf->Cell(0, 6, 'Driver: ' . htmlspecialchars($driver_name), 0, 1);
    $pdf->Cell(0, 6, 'Status: ' . strtoupper(str_replace('_', ' ', $order['order_status'])), 0, 1);
    $pdf->Ln(5);

    // Items Table
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(195, 66, 0);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(80, 8, 'Product', 1, 0, 'L', true);
    $pdf->Cell(25, 8, 'Qty', 1, 0, 'C', true);
    $pdf->Cell(35, 8, 'Unit Price', 1, 0, 'R', true);
    $pdf->Cell(35, 8, 'Subtotal', 1, 1, 'R', true);
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(0, 0, 0);
    $items->data_seek(0);
    while($item = $items->fetch_assoc()) {
        $pdf->Cell(80, 8, htmlspecialchars($item['product_name']), 1, 0, 'L');
        $pdf->Cell(25, 8, $item['quantity'], 1, 0, 'C');
        $pdf->Cell(35, 8, 'PHP ' . number_format($item['unit_price'], 2), 1, 0, 'R');
        $pdf->Cell(35, 8, 'PHP ' . number_format($item['subtotal'], 2), 1, 1, 'R');
    }
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(140, 8, 'Total Amount', 1, 0, 'R');
    $pdf->Cell(35, 8, 'PHP ' . number_format($order['total_amount'], 2), 1, 1, 'R');

    $pdf->Output('D', 'KimFresh_Order_' . $order_id . '.pdf');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo $order_id; ?> - Receipt</title>
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/orders.css">
    <link rel="stylesheet" href="css/flow.css">
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
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar no-print">
            <div class="sidebar-brand">
                <img src="logo.png" alt="KimFresh" class="logo">
                <p>Administration</p>
            </div>
            <nav class="menu">
                <a href="dashboard.php">Dashboard</a>
                <a href="orders.php" class="active">Orders</a>
                <a href="products.php">Products</a>
                <a href="drivers.php">Drivers</a>
                <?php if ($_SESSION['admin_id'] == 1): ?>
                    <a href="superadmin.php">Manage Admins</a>
                <?php endif; ?>
            </nav>
        </aside>

        <div class="main-area">
            <header class="top-header no-print">
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
                <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;" class="no-print">
                    <a href="orders.php" class="back-link" style="color: #C34200; text-decoration: none; font-weight: bold; font-size: 14px;">&larr; Back to Orders</a>
                    <a href="?id=<?php echo $order_id; ?>&export_pdf=1" download="KimFresh_Order_<?php echo $order_id; ?>.pdf" class="btn-export">Export to PDF</a>
                </div>

                <div class="table-section" style="margin-bottom: 24px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #C34200; padding-bottom: 15px; margin-bottom: 20px;">
                        <h2 style="color: #C34200; margin: 0;">Order #<?php echo $order_id; ?></h2>
                        <span class="status-pill <?php echo strtolower($order['order_status']); ?>">
                            <?php echo strtoupper(str_replace('_', ' ', $order['order_status'])); ?>
                        </span>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                        <div>
                            <h4 style="color: #555; margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 5px;">Retailer Information</h4>
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($order['retailer_name']); ?></p>
                            <p><strong>Code:</strong> <?php echo htmlspecialchars($order['customer_code']); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
                            <p><strong>Address:</strong> <?php echo htmlspecialchars($order['address']); ?></p>
                        </div>
                        <div>
                            <h4 style="color: #555; margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 5px;">Order Information</h4>
                            <p><strong>Date:</strong> <?php echo date('M d, Y h:i A', strtotime($order['order_date'])); ?></p>
                            <p><strong>Assigned Driver:</strong> <?php echo htmlspecialchars($driver_name); ?></p>
                            <p><strong>Total Amount:</strong> <span style="color: #C34200; font-weight: bold; font-size: 16px;">₱<?php echo number_format($order['total_amount'], 2); ?></span></p>
                        </div>
                    </div>

                    <h3 style="margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 5px;">Items Ordered</h3>
                    <div class="table-responsive">
                        <table class="items-table" style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                            <thead>
                                <tr>
                                    <th style="text-align: left; padding: 10px;">Product</th>
                                    <th style="text-align: left; padding: 10px;">Quantity</th>
                                    <th style="text-align: left; padding: 10px;">Unit Price</th>
                                    <th style="text-align: left; padding: 10px;">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($items->num_rows > 0) {
                                    $items->data_seek(0);
                                    while($item = $items->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td style='padding: 10px; border-bottom: 1px solid #eee;'>" . htmlspecialchars($item['product_name']) . "</td>";
                                        echo "<td style='padding: 10px; border-bottom: 1px solid #eee;'>" . $item['quantity'] . "</td>";
                                        echo "<td style='padding: 10px; border-bottom: 1px solid #eee;'>₱" . number_format($item['unit_price'], 2) . "</td>";
                                        echo "<td style='padding: 10px; border-bottom: 1px solid #eee;'>₱" . number_format($item['subtotal'], 2) . "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='4' style='padding: 20px; text-align: center; color: #777;'>No items found for this order.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Cold Chain History -->
                <div class="table-section">
                    <h3 style="margin-bottom: 5px;">Cold-chain History</h3>
                    <p style="font-size: 12px; color: #777; margin-bottom: 20px;">IoT temperature readings recorded during this delivery (Last 10 records).</p>
                    
                    <div class="table-responsive">
                        <table class="items-table" style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr>
                                    <th style="text-align: left; padding: 10px;">Recorded At</th>
                                    <th style="text-align: left; padding: 10px;">Temperature</th>
                                    <th style="text-align: left; padding: 10px;">Humidity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($sensor_data->num_rows > 0) {
                                    while($sensor = $sensor_data->fetch_assoc()) {
                                        $tempColor = $sensor['is_alert'] ? '#991b1b' : '#166534'; 
                                        echo "<tr>";
                                        echo "<td style='padding: 10px; border-bottom: 1px solid #eee;'>" . date('M d, Y h:i A', strtotime($sensor['reading_timestamp'])) . "</td>";
                                        echo "<td style='padding: 10px; border-bottom: 1px solid #eee; font-weight: bold; color: " . $tempColor . ";'>" . $sensor['temperature'] . "°C</td>";
                                        echo "<td style='padding: 10px; border-bottom: 1px solid #eee;'>" . $sensor['humidity'] . "%</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='3' style='padding: 20px; text-align: center; color: #777;'>No temperature readings recorded for this order.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
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
            if (menu && !menu.contains(event.target)) {
                document.getElementById('profileDropdown').classList.remove('show');
            }
        });
    </script>
</body>
</html>