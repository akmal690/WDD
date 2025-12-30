<?php
require_once '../config/database.php';
require_once '../config/session.php';

$error = '';

// Handle admin login
if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT id, username, email, password, full_name FROM admins WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $admin = $result->fetch_assoc();
        if (password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['username'] = $admin['username'];
            $_SESSION['role'] = 'admin';
            $_SESSION['full_name'] = $admin['full_name'];
            
            header('Location: dashboard.php');
            exit();
        } else {
            $error = "Invalid username or password!";
        }
    } else {
        $error = "Invalid username or password!";
    }
    
    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - ACE Hardware Store</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }
        header, footer {
            display: none;
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-container">
            <!-- Left Side - Welcome Section -->
            <div class="login-left">
                <div class="login-left-content">
                    <div class="login-logo">ACE Hardware Store Admin</div>
                    <h1 class="login-welcome">Admin<br>Dashboard</h1>
                    <p class="login-description">
                        Manage your hardware shopping platform with ease. Access all administrative features, track orders, manage products, and oversee your business operations from one central location.
                    </p>
                </div>
                <p class="login-footer-text">Secure admin access to your platform</p>
            </div>

            <!-- Right Side - Login Form -->
            <div class="login-right">
                <h2 class="login-form-title">Admin Login</h2>
                <p class="login-form-subtitle">Welcome! Please login to access the admin dashboard.</p>
                
                <?php if ($error): ?>
                    <div class="alert alert-error" style="margin-bottom: 1.5rem;"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="login-form-group">
                        <label for="username">User Name</label>
                        <svg class="login-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <input type="text" id="username" name="username" placeholder="Enter your username or email" required>
                    </div>
                    
                    <div class="login-form-group">
                        <label for="password">Password</label>
                        <svg class="login-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    </div>
                    
                    <div class="login-checkbox-group">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember me</label>
                    </div>
                    
                    <button type="submit" name="login" class="login-btn">LOGIN</button>
                </form>
                
                <div class="login-links">
                    <div>
                        <span style="color: var(--dark-light);">New Admin? </span>
                        <a href="register.php">Signup</a>
                    </div>
                    <a href="#" style="color: var(--dark-light);">Forgot your password?</a>
                </div>
                
                <div style="text-align: center; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--light-gray);">
                    <a href="../index.php" style="color: var(--primary-color); text-decoration: none; font-weight: 600;">← Back to Site</a>
                </div>
                
                <p style="text-align: center; margin-top: 1rem; color: var(--dark-light); font-size: 0.85rem;">
                    Default: akmal / 567890
                </p>
            </div>
        </div>
    </div>
</body>
</html>
