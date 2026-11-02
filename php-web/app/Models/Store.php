<?php
// app/Models/Store.php — strict MVC Model
class Store extends Model
{
    public function get()
    {
        return $this->conn->query("SELECT * FROM store_settings WHERE id = 1")->fetch_assoc();
    }

    public function setManualOverride($value)
    {
        $stmt = $this->conn->prepare("UPDATE store_settings SET manual_override = ? WHERE id = 1");
        $stmt->bind_param("i", $value);
        $stmt->execute();
    }

    public function resetOverride()
    {
        $this->conn->query("UPDATE store_settings SET manual_override = NULL WHERE id = 1");
    }
}
