<?php
// app/Controllers/LogoutController.php — strict MVC Controller
class LogoutController extends Controller
{
    public function index()
    {
        if (isset($_SESSION['admin_id'])) {
            logActivity($this->conn, $_SESSION['admin_id'], "Admin logged out");
        }

        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();
        $this->redirect('/php-web/public/login');
    }
}
