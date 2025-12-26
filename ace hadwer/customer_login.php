<?php
require_once 'config/database.php';
require_once 'config/session.php';

$error = '';

// Handle customer login
if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT id, username, email, password, role, full_name FROM users WHERE (username = ? OR email = ?) AND role = 'user'");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            
            header('Location: index.php');
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
    <title>Customer Login - Food Order</title>
    <link rel="stylesheet" href="assets/css/style.css">
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
                    <div class="login-logo">ACE Hardware Store</div>
                    <h1 class="login-welcome">Welcome to<br>ACE Hardware Store</h1>
                    <p class="login-description">
                        Discover high-quality tools and building materials for your next home improvement project. Shop our wide selection of hardware products and get expert advice to help you succeed. Join thousands of satisfied customers today!
                    </p>
                </div>
                <p class="login-footer-text">Experience the best hardware shopping experience in town</p>
            </div>

            <!-- Right Side - Login Form -->
            <div class="login-right">
                <h2 class="login-form-title">Login</h2>
                <p class="login-form-subtitle">Welcome! Login to get amazing discounts and offers only for you.</p>
                
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
                        <span style="color: var(--dark-light);">New User? </span>
                        <a href="customer_register.php">Signup</a>
                    </div>
                    <a href="#" style="color: var(--dark-light);">Forgot your password?</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
