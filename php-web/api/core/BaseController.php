<?php
declare(strict_types=1);

abstract class BaseController {
    protected mysqli $db;

    public function __construct(mysqli $db) {
        $this->db = $db;
    }

    // DRY: Standard JSON Response
    protected function respond(array $data, int $status = 200): void {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
