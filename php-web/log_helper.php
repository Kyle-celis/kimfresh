<?php
function logActivity($conn, $admin_id, $action) {
    try {
        $action = mb_substr((string)$action, 0, 255); // respect column limit
        $stmt = $conn->prepare("INSERT INTO activity_logs (admin_id, action) VALUES (?, ?)");
        $stmt->bind_param("is", $admin_id, $action);
        $stmt->execute();
    } catch (Throwable $e) {
        error_log("logActivity failed: " . $e->getMessage());
    }
}
?>