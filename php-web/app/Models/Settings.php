<?php
// app/Models/Settings.php — strict MVC Model
class Settings extends Model
{
    public function getAdmin($admin_id)
    {
        $stmt = $this->conn->prepare("SELECT name, email FROM admin WHERE admin_id = ?");
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function updateProfile($admin_id, $name, $new_password = null)
    {
        if ($new_password !== null && $new_password !== '') {
            $hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $this->conn->prepare("UPDATE admin SET name = ?, password_hash = ? WHERE admin_id = ?");
            $stmt->bind_param("ssi", $name, $hash, $admin_id);
        } else {
            $stmt = $this->conn->prepare("UPDATE admin SET name = ? WHERE admin_id = ?");
            $stmt->bind_param("si", $name, $admin_id);
        }
        $stmt->execute();
        return $stmt->affected_rows;
    }

    public function getStore()
    {
        return $this->conn->query("SELECT * FROM store_settings WHERE id = 1")->fetch_assoc();
    }

    public function updateSchedule($open_time, $close_time)
    {
        $stmt = $this->conn->prepare("UPDATE store_settings SET open_time = ?, close_time = ?, manual_override = NULL WHERE id = 1");
        $stmt->bind_param("ss", $open_time, $close_time);
        $stmt->execute();
        return $stmt->affected_rows;
    }
}