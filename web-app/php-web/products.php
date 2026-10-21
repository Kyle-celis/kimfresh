<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Handle Hide/Restore Action
if (isset($_POST['toggle_status'])) {
    $product_id = $_POST['product_id'];
    $new_status = $_POST['new_status'];
    
    $stmt = $conn->prepare("UPDATE product SET status = ? WHERE product_id = ?");
    $stmt->bind_param("si", $new_status, $product_id);
    $stmt->execute();
    header("Location: products.php");
    exit();
}

// Fetch all products
$products = $conn->query("SELECT * FROM product ORDER BY product_id ASC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>KimFresh - PHP Products</title>
    <style>
        body { font-family: Arial; background: #F5F6FA; margin: 0; padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; background: #800020; color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .header a { color: white; text-decoration: none; margin-left: 20px; font-weight: bold; }
        table { width: 100%; background: white; border-collapse: collapse; box-shadow: 0 4px 10px rgba(0,0,0,0.1); border-radius: 10px; overflow: hidden; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; color: #555; }
        .btn { padding: 8px 15px; background: #800020; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
        .btn-gray { background: #555; }
        .status-available { color: #166534; font-weight: bold; }
        .status-hidden { color: #991b1b; font-weight: bold; }
    </style>
</head>
<body>

    <div class="header">
        <h2>Manage Products (PHP)</h2>
        <div>
            <a href="dashboard.php">Dashboard</a>
            <a href="orders.php">Orders</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Product Name</th>
                <th>SKU</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($products->num_rows > 0) {
                while($row = $products->fetch_assoc()) {
                    $statusClass = $row["status"] == 'available' ? 'status-available' : 'status-hidden';
                    $isHidden = $row["status"] == 'inactive';
                    
                    echo "<tr>";
                    echo "<td>" . $row["product_id"] . "</td>";
                    echo "<td>" . htmlspecialchars($row["name"]) . "</td>";
                    echo "<td>" . htmlspecialchars($row["sku"]) . "</td>";
                    echo "<td>₱" . $row["unit_price"] . "</td>";
                    echo "<td>" . $row["stock_quantity"] . "</td>";
                    echo "<td class='" . $statusClass . "'>" . strtoupper($row["status"]) . "</td>";
                    
                    echo "<td>";
                    echo "<form method='POST' style='margin:0;'>";
                    echo "<input type='hidden' name='product_id' value='" . $row["product_id"] . "'>";
                    if ($isHidden) {
                        echo "<input type='hidden' name='new_status' value='available'>";
                        echo "<button type='submit' name='toggle_status' class='btn'>Restore</button>";
                    } else {
                        echo "<input type='hidden' name='new_status' value='inactive'>";
                        echo "<button type='submit' name='toggle_status' class='btn btn-gray'>Hide</button>";
                    }
                    echo "</form>";
                    echo "</td>";
                    
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='7'>No products found</td></tr>";
            }
            ?>
        </tbody>
    </table>

</body>
</html>
