<?php
// db.php
date_default_timezone_set('Asia/Manila');

$host = 'localhost';
$dbname = 'kimfresh';
$username = 'root';
$password = ''; // XAMPP default is blank

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

// --- ROUND ROBIN AUTO ASSIGN (transactional) ---
function autoAssignDriver($conn, $order_id) {
    // Already has a delivery? bail.
    $chk = $conn->prepare("SELECT delivery_id FROM delivery WHERE order_id = ?");
    $chk->bind_param("i", $order_id);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) return false;

    // Last assigned driver (round-robin cursor)
    $last_id = 0;
    $last_res = $conn->query("SELECT driver_id FROM delivery ORDER BY delivery_id DESC LIMIT 1");
    if ($last_res && $last_res->num_rows > 0) {
        $last_id = (int)$last_res->fetch_assoc()['driver_id'];
    }

    // Available drivers only
    $available = $conn->query("
        SELECT driver_id FROM driver
        WHERE status = 'available' AND account_status = 'approved'
        ORDER BY driver_id ASC
    ");
    if ($available->num_rows === 0) return false;

    $chosen = null;
    while ($row = $available->fetch_assoc()) {
        if ((int)$row['driver_id'] > $last_id) { $chosen = (int)$row['driver_id']; break; }
    }
    if (!$chosen) {
        $available->data_seek(0);
        $chosen = (int)$available->fetch_assoc()['driver_id'];
    }
    if (!$chosen) return false;

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO delivery (order_id, driver_id, delivery_status) VALUES (?, ?, 'assigned')");
        $stmt->bind_param("ii", $order_id, $chosen);
        $stmt->execute();

        $stmt = $conn->prepare("UPDATE driver SET status = 'on_delivery' WHERE driver_id = ? AND status = 'available'");
        $stmt->bind_param("i", $chosen);
        $stmt->execute();
        if ($stmt->affected_rows !== 1) throw new Exception("Driver state changed mid-assign.");

        $conn->commit();
        return $chosen;
    } catch (Throwable $e) {
        $conn->rollback();
        error_log("autoAssignDriver failed: " . $e->getMessage());
        return false;
    }
}

// --- MANUAL ASSIGN (transactional + validated) ---
function manualAssignDriver($conn, $order_id, $driver_id) {
    // Order already assigned?
    $chk = $conn->prepare("SELECT delivery_id FROM delivery WHERE order_id = ?");
    $chk->bind_param("i", $order_id);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) return false;

    // Driver must be available & approved
    $chk = $conn->prepare("SELECT status, account_status FROM driver WHERE driver_id = ?");
    $chk->bind_param("i", $driver_id);
    $chk->execute();
    $drv = $chk->get_result()->fetch_assoc();
    if (!$drv || $drv['status'] !== 'available' || $drv['account_status'] !== 'approved') {
        return false;
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO delivery (order_id, driver_id, delivery_status) VALUES (?, ?, 'assigned')");
        $stmt->bind_param("ii", $order_id, $driver_id);
        $stmt->execute();

        $stmt = $conn->prepare("UPDATE driver SET status = 'on_delivery' WHERE driver_id = ?");
        $stmt->bind_param("i", $driver_id);
        $stmt->execute();

        $conn->commit();
        return true;
    } catch (Throwable $e) {
        $conn->rollback();
        error_log("manualAssignDriver failed: " . $e->getMessage());
        return false;
    }
}

// --- SWEEP: assign every approved order with no delivery ---
function assignWaitingOrders($conn) {
    $waiting = $conn->query("
        SELECT o.order_id FROM `order` o
        LEFT JOIN delivery d ON o.order_id = d.order_id
        WHERE o.order_status = 'approved' AND d.delivery_id IS NULL
        ORDER BY o.order_date ASC
    ");
    while ($row = $waiting->fetch_assoc()) {
        if (!autoAssignDriver($conn, (int)$row['order_id'])) break;
    }
}