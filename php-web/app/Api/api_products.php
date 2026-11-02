<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Allows the app to connect
require '../db.php'; 

$sql = "SELECT * FROM product";
$result = $conn->query($sql);

$products = array();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // Ensure numbers are numbers, not strings
        $row['product_id'] = (int)$row['product_id'];
        $row['unit_price'] = (float)$row['unit_price'];
        $row['stock_quantity'] = (int)$row['stock_quantity'];
        
        $products[] = $row;
    }
}

// Output pure JSON
echo json_encode($products);
$conn->close();
?>