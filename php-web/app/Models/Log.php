<?php
// app/Models/Log.php — strict MVC Model
class Log extends Model
{
    public function count($is_superadmin, $admin_id)
    {
        if ($is_superadmin) {
            return (int)$this->conn->query("SELECT COUNT(*) AS total FROM activity_logs")->fetch_assoc()['total'];
        }
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS total FROM activity_logs WHERE admin_id = ?");
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        return (int)$stmt->get_result()->fetch_assoc()['total'];
    }

    public function getAll($is_superadmin, $admin_id, $limit, $offset)
    {
        if ($is_superadmin) {
            return $this->conn->query("
                SELECT l.*, a.name as admin_name
                FROM activity_logs l
                LEFT JOIN admin a ON l.admin_id = a.admin_id
                ORDER BY l.timestamp DESC
                LIMIT $limit OFFSET $offset
            ");
        }
        $stmt = $this->conn->prepare("
            SELECT l.*, a.name as admin_name
            FROM activity_logs l
            LEFT JOIN admin a ON l.admin_id = a.admin_id
            WHERE l.admin_id = ?
            ORDER BY l.timestamp DESC
            LIMIT $limit OFFSET $offset
        ");
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function getAllForPdf($is_superadmin, $admin_id)
    {
        if ($is_superadmin) {
            return $this->conn->query("
                SELECT l.log_id, a.name as admin_name, l.action, l.timestamp
                FROM activity_logs l
                LEFT JOIN admin a ON l.admin_id = a.admin_id
                ORDER BY l.timestamp DESC
            ");
        }
        $stmt = $this->conn->prepare("
            SELECT l.log_id, a.name as admin_name, l.action, l.timestamp
            FROM activity_logs l
            LEFT JOIN admin a ON l.admin_id = a.admin_id
            WHERE l.admin_id = ?
            ORDER BY l.timestamp DESC
        ");
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        return $stmt->get_result();
    }
}