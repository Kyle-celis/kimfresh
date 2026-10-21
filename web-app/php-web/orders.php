<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Handle Approve Action
if (isset($_POST['approve_order'])) {
    $order_id = $_POST['order_id'];
    $stmt = $conn->prepare("UPDATE `order` SET order_status = 'approved' WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    header("Location: orders.php"); // Refresh page
    exit();
}

// Fetch all orders
$orders = $conn->query("
    SELECT o.order_id, r.customer_code, r.name as retailer_name, o.order_status, o.total_amount 
    FROM `order` o
    LEFT JOIN retailer r ON o.retailer_id = r.retailer_id
    ORDER BY o.order_date DESC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>KimFresh - PHP Orders</title>
    <style>
        body { font-family: Arial; background: #F5F6FA; margin: 0; padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; background: #800020; color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .header a { color: white; text-decoration: none; margin-left: 20px; font-weight: bold; }
        table { width: 100%; background: white; border-collapse: collapse; box-shadow: 0 4px 10px rgba(0,0,0,0.1); border-radius: 10px; overflow: hidden; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; color: #555; }
        .btn { padding: 8px 15px; background: #800020; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
        .status-pending { color: #b45309; font-weight: bold; }
        .status-approved { color: #166534; font-weight: bold; }
    </style>
</head>
<body>

    <div class="header">
        <h2>Manage Orders (PHP)</h2>
        <div>
            <a href="dashboard.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Retailer</th>
                <th>Status</th>
                <th>Total Amount</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($orders->num_rows > 0) {
                while($row = $orders->fetch_assoc()) {
                    $statusClass = $row["order_status"] == 'pending' ? 'status-pending' : 'status-approved';
                    
                    echo "<tr>";
                    echo "<td>#" . $row["order_id"] . "</td>";
                    echo "<td>" . htmlspecialchars($row["retailer_name"]) . " (" . htmlspecialchars($row["customer_code"]) . ")</td>";
                    echo "<td class='" . $statusClass . "'>" . strtoupper($row["order_status"]) . "</td>";
                    echo "<td>₱" . $row["total_amount"] . "</td>";
                    
                    // Action Button
                    echo "<td>";
                    if ($row["order_status"] == 'pending') {
                        echo "<form method='POST' style='margin:0;'>";
                        echo "<input type='hidden' name='order_id' value='" . $row["order_id"] . "'>";
                        echo "<button type='submit' name='approve_order' class='btn'>Approve</button>";
                        echo "</form>";
                    } else {
                        echo "-";
                    }
                    echo "</td>";
                    
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='5'>No orders found</td></tr>";
            }
            ?>
        </tbody>
    </table>

</body>
</html>
