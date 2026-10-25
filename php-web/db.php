<?php
// db.php
$host = 'localhost';
$dbname = 'kimfresh';
$username = 'root'; 
$password = 'YOUR_DB_PASSWORD'; // Your local password

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// --- ROUND ROBIN AUTO ASSIGN ---
function autoAssignDriver($conn, $order_id) {
    // 1. Find the last assigned driver_id for Round-Robin
    $last_res = $conn->query("SELECT driver_id FROM delivery ORDER BY delivery_id DESC LIMIT 1");
    $last_id = 0;
    if ($last_res && $last_res->num_rows > 0) {
        $last_id = $last_res->fetch_assoc()['driver_id'];
    }

    // 2. Find all available drivers
    $available = $conn->query("SELECT driver_id FROM driver WHERE status = 'available' AND account_status = 'approved' ORDER BY driver_id ASC");
    
    if ($available->num_rows == 0) {
        return false; // No drivers available
    }

    $chosen_driver = null;

    // 3. Loop to find the next driver after last_id
    while ($row = $available->fetch_assoc()) {
        if ($row['driver_id'] > $last_id) {
            $chosen_driver = $row['driver_id'];
            break;
        }
    }

    // 4. Wrap around if we reached the end
    if (!$chosen_driver) {
        $available->data_seek(0);
        $chosen_driver = $available->fetch_assoc()['driver_id'];
    }

    // 5. Insert delivery and update driver status
    if ($chosen_driver) {
        $stmt = $conn->prepare("INSERT INTO delivery (order_id, driver_id, delivery_status) VALUES (?, ?, 'assigned')");
        $stmt->bind_param("ii", $order_id, $chosen_driver);
        $stmt->execute();

        $stmt = $conn->prepare("UPDATE driver SET status = 'on_delivery' WHERE driver_id = ?");
        $stmt->bind_param("i", $chosen_driver);
        $stmt->execute();

        return $chosen_driver;
    }

    return false;
}

// --- MANUAL ASSIGN ---
function manualAssignDriver($conn, $order_id, $driver_id) {
    // 1. Check if the order already has a delivery to avoid duplicates
    $check = $conn->prepare("SELECT delivery_id FROM delivery WHERE order_id = ?");
    $check->bind_param("i", $order_id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        return false; // Already assigned!
    }

    // 2. Insert the delivery record
    $stmt = $conn->prepare("INSERT INTO delivery (order_id, driver_id, delivery_status) VALUES (?, ?, 'assigned')");
    $stmt->bind_param("ii", $order_id, $driver_id);
    $stmt->execute();

    // 3. Update the driver's status
    $stmt = $conn->prepare("UPDATE driver SET status = 'on_delivery' WHERE driver_id = ?");
    $stmt->bind_param("i", $driver_id);
    $stmt->execute();

    return true;
}


function assignWaitingOrders($conn) {
    // Find orders that are approved but have NO delivery record
    $waitingOrders = $conn->query("
        SELECT o.order_id 
        FROM `order` o
        LEFT JOIN delivery d ON o.order_id = d.order_id
        WHERE o.order_status = 'approved' AND d.delivery_id IS NULL
        ORDER BY o.order_date ASC
    ");

    while ($order = $waitingOrders->fetch_assoc()) {
        // Try to auto-assign this order to an available driver
        $assigned = autoAssignDriver($conn, $order['order_id']);
        
        // If no drivers are left, stop the loop
        if (!$assigned) {
            break; 
        }
    }
}
?>
