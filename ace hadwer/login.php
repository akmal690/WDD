<?php
require_once 'config/database.php';
require_once 'config/session.php';

$error = '';
$success = '';

// Handle registration
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
        
        // Check if username or email already exists
        $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Username or email already exists!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, phone) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $username, $email, $hashed_password, $full_name, $phone);
            
            if ($stmt->execute()) {
                $success = "Registration successful! Please login.";
            } else {
                $error = "Registration failed! Please try again.";
            }
            $stmt->close();
        }
        $check->close();
        $conn->close();
    }
}

// Handle login
if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT id, username, email, password, role, full_name FROM users WHERE username = ? OR email = ?");
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
            
            if ($user['role'] === 'admin') {
                header('Location: admin/dashboard.php');
            } else {
                header('Location: index.php');
            }
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

$register_mode = isset($_GET['register']) || isset($_POST['register']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $register_mode ? 'Register' : 'Login'; ?> - Food Order</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <nav>
            <a href="index.php" class="logo">🍕 Speedy Spice</a>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="menu.php">Menu</a></li>
            </ul>
        </nav>
    </header>

    <div class="form-container">
        <h2 style="text-align: center; margin-bottom: 1.5rem; color: var(--primary-color);">
            <?php echo $register_mode ? 'Create Account' : 'Login'; ?>
        </h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($register_mode): ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" required>
                </div>
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="tel" name="phone">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" required>
                </div>
                <button type="submit" name="register" class="btn btn-primary" style="width: 100%;">Register</button>
            </form>
            <p style="text-align: center; margin-top: 1rem;">
                Already have an account? <a href="login.php">Login here</a>
            </p>
        <?php else: ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label>Username or Email</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" name="login" class="btn btn-primary" style="width: 100%;">Login</button>
            </form>
            <p style="text-align: center; margin-top: 1rem;">
                Don't have an account? <a href="login.php?register=1">Register here</a>
            </p>
            <p style="text-align: center; margin-top: 0.5rem;">
                <a href="admin/login.php">Admin Login</a>
            </p>
        <?php endif; ?>
    </div>

    <footer>
        <p>&copy; 2024 Speedy Spice. All rights reserved.</p>
    </footer>
</body>
</html>

