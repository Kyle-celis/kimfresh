<?php
declare(strict_types=1);

class Database {
    private string $host;
    private string $dbname;
    private string $username;
    private string $password;
    public mysqli $conn;

    public function __construct() {
        $envPath = __DIR__ . '/../../.env';
        if (!file_exists($envPath)) {
            die("Environment file missing.");
        }
        $env = parse_ini_file($envPath);

        $this->host = $env['DB_HOST'];
        $this->dbname = $env['DB_NAME'];
        $this->username = $env['DB_USER'];
        $this->password = $env['DB_PASS'];

        $this->connect();
    }

    private function connect(): void {
        $this->conn = new mysqli($this->host, $this->username, $this->password, $this->dbname);
        if ($this->conn->connect_error) {
            die("Database connection failed.");
        }
    }
}
