<?php
session_start();

// Database connection
include_once '../inc/database.php';

// Simple admin authentication
$admin_username = 'admin';
$admin_password = 'admin123';

$login_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($username === $admin_username && $password === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        header('Location: dashboard.php');
        exit;
    } else {
        $login_error = 'Invalid credentials';
    }
}

// If already logged in, redirect to admin dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Raffle Platform</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --ps-blue: #007aff;
            --ps-light: #5ac8fa;
            --ps-text-dark: #2c3e50;
            --ps-bg-light: #f8f9fa;
            --ps-bg-white: #ffffff;
            --ps-border-color: #e9ecef;
            --ps-red: #ff2d55;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--ps-blue) 0%, var(--ps-light) 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        
        .login-container {
            background-color: var(--ps-bg-white);
            padding: 50px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 122, 255, 0.15);
            width: 100%;
            max-width: 400px;
            text-align: center;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .login-container h1 {
            font-family: 'Poppins', sans-serif;
            color: var(--ps-blue);
            font-weight: 700;
            font-size: 28px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }
        
        .admin-icon {
            font-size: 32px;
            background: linear-gradient(135deg, var(--ps-blue), var(--ps-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--ps-text-dark);
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--ps-border-color);
            border-radius: 12px;
            font-size: 1rem;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--ps-blue);
            box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.1);
        }
        
        .login-btn {
            width: 100%;
            background: linear-gradient(135deg, var(--ps-blue), var(--ps-light));
            color: white;
            border: none;
            padding: 14px;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 122, 255, 0.3);
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 122, 255, 0.4);
        }
        
        .login-btn:active {
            transform: translateY(0);
        }
        
        .error {
            color: var(--ps-red);
            margin-bottom: 20px;
            font-weight: 500;
            padding: 12px;
            background: rgba(255, 45, 85, 0.1);
            border-radius: 8px;
            border-left: 4px solid var(--ps-red);
        }
        
        .back-link {
            margin-top: 20px;
            text-align: center;
        }
        
        .back-link a {
            color: var(--ps-blue);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .back-link a:hover {
            color: var(--ps-light);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>
            <i class="fas fa-shield-alt admin-icon"></i>
            Admin Login
        </h1>
        
        <?php if (!empty($login_error)): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($login_error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">
                    <i class="fas fa-user"></i> Username
                </label>
                <input type="text" id="username" name="username" required autocomplete="username">
            </div>
            
            <div class="form-group">
                <label for="password">
                    <i class="fas fa-lock"></i> Password
                </label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            
            <button type="submit" name="login" class="login-btn">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>
        
        <div class="back-link">
            <a href="../index.php">
                <i class="fas fa-arrow-left"></i> Back to Platform
            </a>
        </div>
    </div>
</body>
</html> 