<?php
// app/Models/Admin.php — strict MVC Model
class Admin extends Model
{
    public function findByEmail($email)
    {
        $stmt = $this->conn->prepare("SELECT admin_id, name, email, password_hash, is_superadmin, status FROM admin WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function findById($id)
    {
        $stmt = $this->conn->prepare("SELECT is_superadmin FROM admin WHERE admin_id = ? AND status = 'active'");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}
