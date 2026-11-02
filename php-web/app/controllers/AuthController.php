<?php
// app/Controllers/AuthController.php — strict MVC Controller
class AuthController extends Controller
{
    public function showLogin()
    {
        require_once __DIR__ . '/../Models/Admin.php';
        $model = new Admin($this->conn);

        // Already logged in → redirect to correct home
        if (isset($_SESSION['admin_id'])) {
            $row = $model->findById((int)$_SESSION['admin_id']);
            if (!$row) {
                session_destroy();
                $this->redirect('/php-web/public/login');
            }
            $this->redirect($row['is_superadmin'] == 1
                ? '/php-web/public/superadmin'
                : '/php-web/public/dashboard');
        }

        $loginError = $_SESSION['login_error'] ?? '';
        unset($_SESSION['login_error']);

        $this->view('auth/login', ['loginError' => $loginError]);
    }

    public function login()
    {
        require_once __DIR__ . '/../Models/Admin.php';
        $model = new Admin($this->conn);

        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
            $_SESSION['login_error'] = "Please enter a valid email and password.";
            $this->redirect('/php-web/public/login');
        }

        $row = $model->findByEmail($email);

        if (!$row) {
            $_SESSION['login_error'] = "Email not found.";
            $this->redirect('/php-web/public/login');
        }
        if ($row['status'] !== 'active') {
            $_SESSION['login_error'] = "This account is deactivated. Contact a superadmin.";
            $this->redirect('/php-web/public/login');
        }
        if (!password_verify($password, $row['password_hash'])) {
            $_SESSION['login_error'] = "Invalid password.";
            $this->redirect('/php-web/public/login');
        }

        session_regenerate_id(true);
        $_SESSION['admin_id']      = (int)$row['admin_id'];
        $_SESSION['admin_name']    = $row['name'];
        $_SESSION['admin_email']   = $row['email'];
        $_SESSION['is_superadmin'] = (int)$row['is_superadmin'];

        logActivity($this->conn, $row['admin_id'], "Logged in");

        $this->redirect($row['is_superadmin'] == 1
            ? '/php-web/public/superadmin'
            : '/php-web/public/dashboard');
    }
}
