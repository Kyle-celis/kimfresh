<?php
// core/Controller.php — strict, framework-free

class Controller
{
    protected $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->verifyCsrf();
    }

    // Render a view from app/Views/
    protected function view($path, $data = [])
    {
        extract($data, EXTR_SKIP);
        $file = __DIR__ . '/../app/Views/' . $path . '.php';
        if (!file_exists($file)) {
            http_response_code(500);
            die("View not found: {$file}");
        }
        require $file;
    }

    // Redirect
    protected function redirect($path)
    {
        header("Location: {$path}");
        exit();
    }

    // Guard: require admin login
    protected function requireAuth()
    {
        if (!isset($_SESSION['admin_id'])) {
            $this->redirect('/php-web/public/login');
        }
    }

    // Guard: verify CSRF on every state-changing request
    protected function verifyCsrf(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        require_once __DIR__ . '/Csrf.php';
        $token = $_POST['_csrf'] ?? '';
        if (!Csrf::verify($token)) {
            http_response_code(419);
            die('CSRF token missing or invalid. Go back, refresh the page, and try again.');
        }
    }

    // JSON response (for API endpoints)
    protected function json($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }
}