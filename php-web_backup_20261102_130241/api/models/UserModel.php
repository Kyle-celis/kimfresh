<?php
declare(strict_types=1);

class UserModel {
    private mysqli $db;

    public function __construct(mysqli $db) {
        $this->db = $db;
    }

    public function findRetailerByEmail(string $email): ?array {
        $stmt = $this->db->prepare("SELECT retailer_id as user_id, name, password_hash, account_status FROM retailer WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function findDriverByEmail(string $email): ?array {
        $stmt = $this->db->prepare("SELECT driver_id as user_id, name, password_hash, account_status FROM driver WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function findAdminByEmail(string $email): ?array {
        $stmt = $this->db->prepare("SELECT admin_id as user_id, name, password_hash FROM admin WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function createRetailer(string $name, string $email, string $phone, string $hash, string $otp, string $expiry): int {
        $stmt = $this->db->prepare("INSERT INTO retailer (name, email, phone, password_hash, account_status, otp_code, otp_expires_at) VALUES (?, ?, ?, ?, 'otp_pending', ?, ?)");
        $stmt->bind_param("ssssss", $name, $email, $phone, $hash, $otp, $expiry);
        $stmt->execute();
        return $this->db->insert_id;
    }

    public function createDriver(string $name, string $email, string $phone, string $license, string $hash): int {
        $stmt = $this->db->prepare("INSERT INTO driver (name, email, phone, license_number, password_hash, account_status, status) VALUES (?, ?, ?, ?, ?, 'pending', 'available')");
        $stmt->bind_param("sssss", $name, $email, $phone, $license, $hash);
        $stmt->execute();
        return $this->db->insert_id;
    }

    public function getOTP(string $table, string $id_col, int $id): ?array {
        $stmt = $this->db->prepare("SELECT otp_code, otp_expires_at FROM $table WHERE $id_col = ? AND account_status = 'otp_pending'");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function approveUser(string $table, string $id_col, int $id): void {
        $stmt = $this->db->prepare("UPDATE $table SET account_status = 'approved', otp_code = NULL WHERE $id_col = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }

    public function getRetailerProfile(int $id): ?array {
        $stmt = $this->db->prepare("SELECT retailer_id, name, email, phone, address, customer_code FROM retailer WHERE retailer_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function getDriverProfile(int $id): ?array {
        $stmt = $this->db->prepare("SELECT driver_id, name, email, phone, license_number, status FROM driver WHERE driver_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }
}