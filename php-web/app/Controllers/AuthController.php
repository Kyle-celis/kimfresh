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

        $email = trim($_POST['email'] ?? '');

        // PATCH-5: rate limit check BEFORE password verification
        if ($email !== '') {
            $blocked = $this->rateLimitBlockRemaining($email);
            if ($blocked > 0) {
                $minutes = (int)ceil($blocked / 60);
                http_response_code(429);
                $this->view('auth/login', ['loginError' => "Too many failed attempts. Try again in {$minutes} minute(s)."]);
                return;
            }
        }
        require_once __DIR__ . '/../../core/Csrf.php';
        $model = new Admin($this->conn);

        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
            $_SESSION['login_error'] = "Please enter a valid email and password.";
            $this->redirect('/php-web/public/login');
        }

        $row = $model->findByEmail($email);

        if (!$row) {
            $this->recordLoginAttempt($email, false);
            $_SESSION['login_error'] = "Email not found.";
            $this->redirect('/php-web/public/login');
        }
        if ($row['status'] !== 'active') {
            $this->recordLoginAttempt($email, false);
            $_SESSION['login_error'] = "This account is deactivated. Contact a superadmin.";
            $this->redirect('/php-web/public/login');
        }
        if (!password_verify($password, $row['password_hash'])) {
            $this->recordLoginAttempt($email, false);
            $_SESSION['login_error'] = "Invalid password.";
            $this->redirect('/php-web/public/login');
        }

        $this->recordLoginAttempt($email, true); // PATCH-5: clears prior failures
        session_regenerate_id(true);
        Csrf::rotate();

        $_SESSION['admin_id']      = (int)$row['admin_id'];
        $_SESSION['admin_name']    = $row['name'];
        $_SESSION['admin_email']   = $row['email'];
        $_SESSION['is_superadmin'] = (int)$row['is_superadmin'];

        logActivity($this->conn, $row['admin_id'], "Logged in");

        $this->redirect($row['is_superadmin'] == 1
            ? '/php-web/public/superadmin'
            : '/php-web/public/dashboard');
    }
    // ============================================================
    // PATCH-5: Login rate limiting
    // ============================================================
    private const RATE_LIMIT_MAX      = 5;    // failed attempts allowed in window
    private const RATE_LIMIT_WINDOW   = 900;  // 15 minutes in seconds
    private const RATE_LIMIT_BLOCK    = 900;  // 15 minutes lockout

    private function clientIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    // Count failures in window for (email, ip). Returns seconds until unblock, or 0 if allowed.
    private function rateLimitBlockRemaining(string $email): int
    {
        $ip = $this->clientIp();
        $sql = "SELECT COUNT(*) AS n, MAX(attempted_at) AS last_at
                FROM login_attempts
                WHERE email = ? AND ip = ? AND success = 0
                  AND attempted_at > (NOW() - INTERVAL " . self::RATE_LIMIT_WINDOW . " SECOND)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $email, $ip);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ((int)($row['n'] ?? 0) < self::RATE_LIMIT_MAX) {
            return 0;
        }

        // We've hit the max — determine how many seconds until the *oldest relevant* failure ages out.
        $sql2 = "SELECT attempted_at FROM login_attempts
                 WHERE email = ? AND ip = ? AND success = 0
                   AND attempted_at > (NOW() - INTERVAL " . self::RATE_LIMIT_WINDOW . " SECOND)
                 ORDER BY attempted_at ASC LIMIT 1";
        $stmt2 = $this->conn->prepare($sql2);
        $stmt2->bind_param("ss", $email, $ip);
        $stmt2->execute();
        $oldest = $stmt2->get_result()->fetch_assoc();
        $stmt2->close();

        if (!$oldest) return 0;

        $oldestTs = strtotime($oldest['attempted_at']);
        $unblockAt = $oldestTs + self::RATE_LIMIT_WINDOW;
        $remaining = $unblockAt - time();
        return $remaining > 0 ? $remaining : 1;
    }

    private function recordLoginAttempt(string $email, bool $success): void
    {
        $ip = $this->clientIp();
        $ok = $success ? 1 : 0;

        $stmt = $this->conn->prepare("INSERT INTO login_attempts (email, ip, success) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $email, $ip, $ok);
        $stmt->execute();
        $stmt->close();

        // Opportunistic cleanup: ~1 in 20 logins, prune attempts older than 24h
        if (random_int(1, 20) === 1) {
            $this->conn->query("DELETE FROM login_attempts WHERE attempted_at < (NOW() - INTERVAL 1 DAY)");
        }

        // On success, clear prior failures for this (email, ip)
        if ($success) {
            $del = $this->conn->prepare("DELETE FROM login_attempts WHERE email = ? AND ip = ? AND success = 0");
            $del->bind_param("ss", $email, $ip);
            $del->execute();
            $del->close();
        }
    }
}