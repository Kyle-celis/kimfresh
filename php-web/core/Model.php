<?php
// core/Model.php — strict, framework-free

abstract class Model
{
    protected $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    protected function fetchAll($sql, $types = '', $params = [])
    {
        $stmt = $this->conn->prepare($sql);
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    protected function fetchOne($sql, $types = '', $params = [])
    {
        $rows = $this->fetchAll($sql, $types, $params);
        return $rows[0] ?? null;
    }

    protected function execute($sql, $types = '', $params = [])
    {
        $stmt = $this->conn->prepare($sql);
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->affected_rows;
    }
}
