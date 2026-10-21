<?php
function logActivity($conn, $admin_id, $action) {
    $stmt = $conn->prepare("INSERT INTO activity_logs (admin_id, action) VALUES (?, ?)");
    $stmt->bind_param("is", $admin_id, $action);
    $stmt->execute();
}
?>
