<?php
// app/Models/SuperAdmin.php — strict MVC Model
class SuperAdmin extends Model
{
    public function isSuperAdmin($admin_id)
    {
        $stmt = $this->conn->prepare("SELECT is_superadmin FROM admin WHERE admin_id = ?");
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row && (int)$row['is_superadmin'] === 1;
    }

    public function emailExists($email)
    {
        $stmt = $this->conn->prepare("SELECT admin_id FROM admin WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    public function create($name, $email, $password_hash)
    {
        $stmt = $this->conn->prepare("INSERT INTO admin (name, email, password_hash, is_superadmin) VALUES (?, ?, ?, 0)");
        $stmt->bind_param("sss", $name, $email, $password_hash);
        $stmt->execute();
        return $this->conn->insert_id;
    }

    public function getAll()
    {
        return $this->conn->query("SELECT admin_id, name, email, is_superadmin, status, created_at FROM admin ORDER BY admin_id ASC");
    }

    public function findById($id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM admin WHERE admin_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function update($id, $name, $email, $new_password = null, $is_superadmin = null)
    {
        if ($new_password !== null) {
            $hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $this->conn->prepare("UPDATE admin SET name=?, email=?, password_hash=? WHERE admin_id=?");
            $stmt->bind_param("sssi", $name, $email, $hash, $id);
        } else {
            $stmt = $this->conn->prepare("UPDATE admin SET name=?, email=? WHERE admin_id=?");
            $stmt->bind_param("ssi", $name, $email, $id);
        }
        $stmt->execute();
        return $stmt->affected_rows;
    }

    public function setStatus($id, $status)
    {
        $stmt = $this->conn->prepare("UPDATE admin SET status=? WHERE admin_id=?");
        $stmt->bind_param("si", $status, $id);
        $stmt->execute();
        return $stmt->affected_rows;
    }

    public function getActivityLogs($admin_id, $limit = 5)
    {
        $stmt = $this->conn->prepare("SELECT action, timestamp FROM activity_logs WHERE admin_id = ? ORDER BY timestamp DESC LIMIT ?");
        $stmt->bind_param("ii", $admin_id, $limit);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($r = $res->fetch_assoc()) $rows[] = $r;
        return $rows;
    }
}
