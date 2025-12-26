<?php
require_once '../config/database.php';
require_once '../config/session.php';

$error = '';
$success = '';

// Handle admin registration
if (isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    
    if ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters!";
    } else {
        $conn = getDBConnection();
        
        // Check if username or email already exists in admins table
        $check = $conn->prepare("SELECT id FROM admins WHERE username = ? OR email = ?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Username or email already exists!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO admins (username, email, password, full_name, phone) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $username, $email, $hashed_password, $full_name, $phone);
            
            if ($stmt->execute()) {
                $success = "Admin registration successful! Please login.";
            } else {
                $error = "Registration failed! Please try again.";
            }
            $stmt->close();
        }
        $check->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Register - ACE Hardware Store</title>
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
                    <div class="login-logo">ACE Hardware Store</div>
                    <h1 class="login-welcome">Create<br>Admin Account</h1>
                    <p class="login-description">
                        Register as an administrator to manage your hardware shopping platform. Gain access to all management features and control your business operations effectively.
                    </p>
                </div>
                <p class="login-footer-text">Join the admin team today</p>
            </div>

            <!-- Right Side - Register Form -->
            <div class="login-right">
                <h2 class="login-form-title">Admin Signup</h2>
                <p class="login-form-subtitle">Create your admin account to get started.</p>
                
                <?php if ($error): ?>
                    <div class="alert alert-error" style="margin-bottom: 1.5rem;"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success" style="margin-bottom: 1.5rem;"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="login-form-group">
                        <label for="full_name">Full Name *</label>
                        <svg class="login-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <input type="text" id="full_name" name="full_name" placeholder="Enter your full name" required>
                    </div>
                    
                    <div class="login-form-group">
                        <label for="username">Username *</label>
                        <svg class="login-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <input type="text" id="username" name="username" placeholder="Choose a username" required>
                    </div>
                    
                    <div class="login-form-group">
                        <label for="email">Email *</label>
                        <svg class="login-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <input type="email" id="email" name="email" placeholder="Enter your email" required>
                    </div>
                    
                    <div class="login-form-group">
                        <label for="phone">Phone</label>
                        <svg class="login-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        <input type="tel" id="phone" name="phone" placeholder="Enter your phone number">
                    </div>
                    
                    <div class="login-form-group">
                        <label for="password">Password *</label>
                        <svg class="login-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <input type="password" id="password" name="password" placeholder="Create a password" required>
                    </div>
                    
                    <div class="login-form-group">
                        <label for="confirm_password">Confirm Password *</label>
                        <svg class="login-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                    </div>
                    
                    <button type="submit" name="register" class="login-btn">SIGNUP</button>
                </form>
                
                <div class="login-links">
                    <div>
                        <span style="color: var(--dark-light);">Already have an account? </span>
                        <a href="login.php">Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
