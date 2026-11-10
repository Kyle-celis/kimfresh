<?php
function logActivity($conn, $admin_id, $action) {
    try {
        // Flush any pending results — prevents prepare() from blocking
        while ($conn->more_results() && $conn->next_result()) { ; }

        // Cap lock wait so a stuck table can never hang the calling request
        $conn->query("SET SESSION innodb_lock_wait_timeout = 3");

        $action = mb_substr((string)$action, 0, 255);
        $stmt = $conn->prepare("INSERT INTO activity_logs (admin_id, action) VALUES (?, ?)");
        $stmt->bind_param("is", $admin_id, $action);
        $stmt->execute();
        $stmt->close();
    } catch (Throwable $e) {
        error_log("logActivity failed: " . $e->getMessage());
    }
}
<?php broken ?>
