<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

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
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="css/flow.css">
</head>
<body>
    <div class="dashboard-layout">
        <!-- SIDEBAR -->
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
            </nav>
        </aside>

        <div class="main-area">
            <!-- TOP HEADER -->
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

            <!-- MAIN CONTENT -->
            <main class="main-content">
                <div style="margin-bottom: 20px;">
                    <a href="orders.php" style="color: #800020; text-decoration: none; font-weight: bold; font-size: 14px;">&larr; Back to Orders</a>
                </div>

                <div class="table-section" style="margin-bottom: 24px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #800020; padding-bottom: 15px; margin-bottom: 20px;">
                        <h2 style="color: #800020; margin: 0;">Order #<?php echo $order_id; ?> Receipt</h2>
                        <span class="status-pill <?php echo strtolower($order['order_status']); ?>">
                            <?php echo strtoupper(str_replace('_', ' ', $order['order_status'])); ?>
                        </span>
                    </div>

                    <!-- Retailer & Order Info -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                        <div>
                            <h4 style="color: #555; margin-bottom: 10px;">Retailer Information</h4>
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($order['retailer_name']); ?></p>
                            <p><strong>Code:</strong> <?php echo htmlspecialchars($order['customer_code']); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
                            <p><strong>Address:</strong> <?php echo htmlspecialchars($order['address']); ?></p>
                        </div>
                        <div>
                            <h4 style="color: #555; margin-bottom: 10px;">Order Information</h4>
                            <p><strong>Date:</strong> <?php echo date('M d, Y h:i A', strtotime($order['order_date'])); ?></p>
                            <p><strong>Assigned Driver:</strong> <?php echo htmlspecialchars($driver_name); ?></p>
                            <p><strong>Total Amount:</strong> <span style="color: #800020; font-weight: bold;">₱<?php echo number_format($order['total_amount'], 2); ?></span></p>
                        </div>
                    </div>

                    <!-- Items Ordered -->
                    <h3 style="margin-bottom: 15px;">Items Ordered</h3>
                    <div class="table-responsive">
                        <table class="items-table" style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                            <thead>
                                <tr style="border-bottom: 1px solid #ddd;">
                                    <th style="text-align: left; padding: 10px;">Product</th>
                                    <th style="text-align: left; padding: 10px;">Quantity</th>
                                    <th style="text-align: left; padding: 10px;">Unit Price</th>
                                    <th style="text-align: left; padding: 10px;">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($items->num_rows > 0) {
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
                                <tr style="border-bottom: 1px solid #ddd;">
                                    <th style="text-align: left; padding: 10px;">Recorded At</th>
                                    <th style="text-align: left; padding: 10px;">Temperature</th>
                                    <th style="text-align: left; padding: 10px;">Humidity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($sensor_data->num_rows > 0) {
                                    while($sensor = $sensor_data->fetch_assoc()) {
                                        $tempColor = $sensor['is_alert'] ? '#991b1b' : '#166534'; // Red if alert, green if safe
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
</body>
</html>