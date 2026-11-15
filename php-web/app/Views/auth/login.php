<?php /* Login view - vars from AuthController::showLogin() */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KimFresh - Admin Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/php-web/public/css/theme.css">
    
    <style>
        :root {
            --primary: #C34200;
            --primary-dark: #9a3400;
            --bg-orange: #C34200;
            --text-dark: #111;
            --text-gray: #666;
        }
        
        body {
            margin: 0;
            padding: 0;
            font-family: 'Nunito Sans', sans-serif;
            background-color: #C34200;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .login-container {
            display: flex;
            width: 1000px;
            max-width: 95%;
            background: #fff;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 20px 50px rgba(0,0,0,0.1);
            min-height: 600px;
        }

        /* Left Side (Branding) */
        .login-left {
            width: 45%;
            background: linear-gradient(165deg, #d94a00 0%, #cf561a 55%, #c96a3a 100%);
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: #fff;
        }
        .login-left img {
            width: 150px;
            margin-bottom: 40px;
        }
        .login-left h1 {
            font-size: 36px;
            font-weight: 800;
            line-height: 1.2;
            margin: 0 0 20px;
        }
        .login-left p {
            font-size: 16px;
            opacity: 0.9;
            line-height: 1.5;
            margin: 0;
        }

        /* Right Side (Form) */
        .login-right {
            width: 55%;
            padding: 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .login-right h2 {
            font-size: 32px;
            font-weight: 800;
            color: var(--primary);
            margin: 0 0 10px;
        }
        .login-right p.subtitle {
            color: var(--text-gray);
            margin: 0 0 30px;
            font-size: 15px;
        }

        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 700;
            color: #333;
        }
        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 15px;
            font-family: inherit;
            box-sizing: border-box;
            outline: none;
            transition: border-color 0.2s;
        }
        .form-group input:focus {
            border-color: var(--primary);
        }

        .btn-login {
            width: 100%;
            padding: 16px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 800;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 10px;
        }
        .btn-login:hover {
            background: var(--primary-dark);
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 24px;
            color: var(--text-gray);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }
        .back-link:hover {
            color: var(--primary);
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 600;
            border: 1px solid #f87171;
        }

        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
            }
            .login-left, .login-right {
                width: 100%;
                padding: 40px 30px;
            }
        }
    </style>
<style>
    body { background-color: #C34200 !important; background-image: none !important; }
    .login-left { background-color: #C34200 !important; background-image: none !important; }
</style>
</head>
</head>
<body>

    <div class="login-container">
        <!-- Left Side: Branding -->
        <div class="login-left" style="background: #C34200 !important; background-image: none !important;">
            <img src="/php-web/public/logo.png" alt="KimFresh">
            <h1>Control everything from one dashboard</h1>
            <p>Monitor users, manage products, and monitor real-time delivery fulfillment.</p>
        </div>

        <!-- Right Side: Login Form -->
        <div class="login-right">
            <h2>Admin Login</h2>
            <p class="subtitle">Enter your credentials to access the admin dashboard.</p>

            <?php if (isset($error) && $error): ?>
                <div class="alert-error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="/php-web/public/login">
                <?= csrf_field() ?>
                
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="admin@kimfresh.com" required autofocus>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Enter your password" required>
                </div>

                <button type="submit" class="btn-login">Sign In</button>

                <a href="/php-web/public/" class="back-link">&larr; Back to role selection</a>
            </form>
        </div>
    </div>

</body>
</html>


