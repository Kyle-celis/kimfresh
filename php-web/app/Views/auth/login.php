<?php
// app/Views/auth/login.php
$base = '/php-web/public';

$isSuper = (isset($role) && $role === 'superadmin');
$panelTitle = $isSuper ? 'Super Admin Login' : 'Admin Login';
$panelDesc = $isSuper ? 'Enter your credentials to access the super admin dashboard.' : 'Enter your credentials to access the admin dashboard.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $panelTitle ?> — KimFresh</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;600;700;800&display=swap">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: "Nunito Sans", Arial, sans-serif; }
    body { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #C34200; padding: 20px; }
    
    .split-container {
      display: flex; width: 100%; max-width: 1000px; min-height: 600px; background: #fff;
      border-radius: 20px; overflow: hidden; box-shadow: 0 24px 60px rgba(0,0,0,0.25);
    }
    
    /* Left Panel */
    .left-panel {
      flex: 1; background: #C34200; padding: 60px 48px; position: relative; display: flex; flex-direction: column;
      color: #fff; background-image: radial-gradient(circle at 10% 90%, rgba(255,255,255,0.1) 0%, transparent 40%);
      justify-content: center;
    }
    .left-panel .logo { max-width: 220px; height: auto; margin-bottom: 40px; object-fit: contain; }
    
    .left-panel h1 { font-size: 34px; font-weight: 800; line-height: 1.2; margin-bottom: 20px; }
    .left-panel p { font-size: 16px; line-height: 1.6; color: rgba(255,255,255,0.9); max-width: 90%; }

    /* Right Panel */
    .right-panel { flex: 1; padding: 60px 50px; display: flex; flex-direction: column; justify-content: center; }
    .right-panel h2 { font-size: 26px; font-weight: 800; color: #C34200; margin-bottom: 8px; }
    .right-panel .subtitle { font-size: 14px; color: #737887; margin-bottom: 30px; line-height: 1.5; }
    
    /* Form Elements */
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-size: 13px; font-weight: 800; color: #343643; margin-bottom: 8px; }
    .form-control {
      width: 100%; padding: 14px 16px; border: 1px solid #dfe1e9; border-radius: 10px;
      font-size: 14px; outline: none; transition: border-color 0.2s;
    }
    .form-control:focus { border-color: #C34200; box-shadow: 0 0 0 4px rgba(195,66,0,0.08); }
    
    .btn-login {
      width: 100%; padding: 16px; background: #C34200; color: #fff; border: none; border-radius: 10px;
      font-size: 16px; font-weight: 800; cursor: pointer; transition: background 0.2s; margin-top: 10px;
    }
    .btn-login:hover { background: #9a3400; }
    
    .back-link { text-align: center; display: block; margin-top: 24px; font-size: 13px; color: #737887; text-decoration: none; font-weight: 700; }
    .back-link:hover { color: #C34200; }

    /* Error Box */
    .alert-box { margin-bottom: 20px; padding: 12px 14px; color: #C34200; background: #fdf0e6; border: 1px solid #f9d4b8; border-radius: 10px; font-size: 13px; line-height: 1.5; font-weight: 700; }
    .alert-box:empty { display: none; }

    @media (max-width: 800px) {
      .split-container { flex-direction: column; max-width: 500px; }
      .left-panel { padding: 40px 32px; }
      .left-panel h1 { font-size: 28px; }
      .right-panel { padding: 40px 32px; }
    }
  </style>
</head>
<body>

  <div class="split-container">
    <!-- Left Side -->
    <div class="left-panel">
      <img src="<?= $base ?>/logo.png" alt="KimFresh" class="logo">
      <h1>Control everything from one dashboard</h1>
      <p>Monitor users, manage product and monitor real-time delivery fulfillment</p>
    </div>

    <!-- Right Side -->
    <div class="right-panel">
      <h2><?= $panelTitle ?></h2>
      <p class="subtitle"><?= $panelDesc ?></p>

      <!-- Error Message Display -->
      <?php if (!empty($loginError)): ?>
        <div class="alert-box"><?= htmlspecialchars($loginError) ?></div>
      <?php endif; ?>

      <form action="<?= $base ?>/login" method="POST">
        <?= csrf_field() ?>
        
        <input type="hidden" name="role" value="<?= htmlspecialchars($role) ?>">
        
        <div class="form-group">
          <label>Email Address</label>
          <input type="email" name="email" class="form-control" placeholder="admin@kimfresh.com" required>
        </div>
        
        <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" class="form-control" placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn-login">Sign In</button>
      </form>

      <a href="<?= $base ?>/welcome" class="back-link">← Back to role selection</a>
    </div>
  </div>

</body>
</html>

