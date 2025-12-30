<?php
require_once '../config/database.php';
require_once '../config/session.php';
requireAdmin();

$conn = getDBConnection();
$admin_id = getAdminId();
$success = '';
$error = '';

// Get current admin info
$admin_query = "SELECT * FROM admins WHERE id = ?";
$stmt = $conn->prepare($admin_query);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$stmt->close();

// Handle profile update
if (isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    
    // Check if email is already taken by another admin
    $check = $conn->prepare("SELECT id FROM admins WHERE email = ? AND id != ?");
    $check->bind_param("si", $email, $admin_id);
    $check->execute();
    $check_result = $check->get_result();
    
    if ($check_result->num_rows > 0) {
        $error = "Email already taken by another admin!";
    } else {
        $update = $conn->prepare("UPDATE admins SET full_name = ?, email = ?, phone = ? WHERE id = ?");
        $update->bind_param("sssi", $full_name, $email, $phone, $admin_id);
        
        if ($update->execute()) {
            $_SESSION['full_name'] = $full_name;
            $success = "Profile updated successfully!";
            // Refresh admin data
            $admin['full_name'] = $full_name;
            $admin['email'] = $email;
            $admin['phone'] = $phone;
        } else {
            $error = "Failed to update profile!";
        }
        $update->close();
    }
    $check->close();
}

// Handle password change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (!password_verify($current_password, $admin['password'])) {
        $error = "Current password is incorrect!";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match!";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters!";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update = $conn->prepare("UPDATE admins SET password = ? WHERE id = ?");
        $update->bind_param("si", $hashed_password, $admin_id);
        
        if ($update->execute()) {
            $success = "Password changed successfully!";
        } else {
            $error = "Failed to change password!";
        }
        $update->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - ACE Hardware Store Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-nav {
            background: var(--dark-color);
            padding: 1rem 0;
            margin-bottom: 2rem;
        }
        .admin-nav ul {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            gap: 1rem;
            list-style: none;
        }
        .admin-nav a {
            color: var(--white);
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .admin-nav a:hover {
            background: rgba(255,255,255,0.1);
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <a href="../index.php" class="logo">ACE Hardware Store Admin</a>
            <ul class="nav-links">
                <li><a href="../index.php">View Site</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <div class="admin-nav">
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="products.php">Products</a></li>
            <li><a href="orders.php">Orders</a></li>
            <li><a href="users.php">Users</a></li>
            <li><a href="messages.php">Messages</a></li>
            <li><a href="profile.php">Profile</a></li>
        </ul>
    </div>

    <div class="container">
        <h1 style="text-align: center; margin-bottom: 2rem; color: var(--primary-color);">Update Profile</h1>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <div style="background: var(--white); padding: 2rem; border-radius: 10px; box-shadow: var(--shadow);">
                <h2 style="margin-bottom: 1.5rem; color: var(--dark-color);">Profile Information</h2>
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" value="<?php echo htmlspecialchars($admin['username']); ?>" disabled>
                        <small style="color: #7f8c8d;">Username cannot be changed</small>
                    </div>
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($admin['full_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="tel" name="phone" value="<?php echo htmlspecialchars($admin['phone'] ?? ''); ?>">
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary" style="width: 100%;">Update Profile</button>
                </form>
            </div>
            
            <div style="background: var(--white); padding: 2rem; border-radius: 10px; box-shadow: var(--shadow);">
                <h2 style="margin-bottom: 1.5rem; color: var(--dark-color);">Change Password</h2>
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Current Password *</label>
                        <input type="password" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label>New Password *</label>
                        <input type="password" name="new_password" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password *</label>
                        <input type="password" name="confirm_password" required>
                    </div>
                    <button type="submit" name="change_password" class="btn btn-primary" style="width: 100%;">Change Password</button>
                </form>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; 2024 ACE Hardware Store. All rights reserved.</p>
    </footer>
</body>
</html>
<?php $conn->close(); ?>

