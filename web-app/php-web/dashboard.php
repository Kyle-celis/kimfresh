<?php
session_start();
require 'db.php';

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// 1. Web -> DB (Direct SQL Queries)
$totalOrders = $conn->query("SELECT COUNT(*) AS c FROM `order`")->fetch_assoc()['c'];
$pendingOrders = $conn->query("SELECT COUNT(*) AS c FROM `order` WHERE order_status = 'pending'")->fetch_assoc()['c'];
$deliveredOrders = $conn->query("SELECT COUNT(*) AS c FROM `order` WHERE order_status = 'delivered'")->fetch_assoc()['c'];

// Fetch recent 5 orders
$recentOrders = $conn->query("
    SELECT o.order_id, r.customer_code, o.order_status, o.total_amount 
    FROM `order` o
    LEFT JOIN retailer r ON o.retailer_id = r.retailer_id
    ORDER BY o.order_date DESC LIMIT 5
");?>

<!DOCTYPE html>
<html>
<head>
    <title>KimFresh - PHP Dashboard</title>
    <style>
        body { font-family: Arial; background: #F5F6FA; margin: 0; padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; background: #800020; color: white; padding: 20px; border-radius: 10px; }
        .cards { display: flex; gap: 20px; margin-top: 20px; }
        .card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); flex: 1; text-align: center; border-left: 5px solid #800020; }
        .card h3 { margin: 0; color: #555; font-size: 14px; }
        .card h1 { margin: 10px 0 0 0; color: #800020; font-size: 32px; }
        table { width: 100%; margin-top: 20px; background: white; border-collapse: collapse; box-shadow: 0 4px 10px rgba(0,0,0,0.1); border-radius: 10px; overflow: hidden; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; color: #555; }
        .logout { background: white; color: #800020; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold; }
    </style>
</head>
<body>

    <div class="header">
        <h2>KimFresh Admin Dashboard (PHP)</h2>
        <div>
            <span>Welcome, <?php echo $_SESSION['admin_name']; ?></span>
            <a href="logout.php" class="logout">Logout</a>
        </div>
    </div>

    <div class="cards">
        <div class="card">
            <h3>Total Orders</h3>
            <h1><?php echo $totalOrders; ?></h1>
        </div>
        <div class="card">
            <h3>Pending Orders</h3>
            <h1><?php echo $pendingOrders; ?></h1>
        </div>
        <div class="card">
            <h3>Delivered Orders</h3>
            <h1><?php echo $deliveredOrders; ?></h1>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Retailer</th>
                <th>Status</th>
                <th>Total Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($recentOrders->num_rows > 0) {
                while($row = $recentOrders->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>#" . $row["order_id"] . "</td>";
                    echo "<td>" . $row["customer_code"] . "</td>";
                    echo "<td>" . strtoupper($row["order_status"]) . "</td>";
                    echo "<td>₱" . $row["total_amount"] . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='4'>No orders found</td></tr>";
            }
            ?>
        </tbody>
    </table>

</body>
</html>
