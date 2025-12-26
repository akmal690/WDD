<?php
require_once 'config/database.php';
require_once 'config/session.php';
requireLogin();

$conn = getDBConnection();
$user_id = getUserId();
$success = '';
$error = '';

// Get current user info
$user_query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Handle profile update
if (isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    
    // Check if email is already taken by another user
    $check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $check->bind_param("si", $email, $user_id);
    $check->execute();
    $check_result = $check->get_result();
    
    if ($check_result->num_rows > 0) {
        $error = "Email already taken by another user!";
    } else {
        $update = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
        $update->bind_param("ssssi", $full_name, $email, $phone, $address, $user_id);
        
        if ($update->execute()) {
            $_SESSION['full_name'] = $full_name;
            $success = "Profile updated successfully!";
            // Refresh user data
            $user['full_name'] = $full_name;
            $user['email'] = $email;
            $user['phone'] = $phone;
            $user['address'] = $address;
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
    
    if (!password_verify($current_password, $user['password'])) {
        $error = "Current password is incorrect!";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match!";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters!";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update->bind_param("si", $hashed_password, $user_id);
        
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
    <title>My Profile - Food Order</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <nav>
            <a href="index.php" class="logo">🍕 Speedy Spice</a>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="menu.php">Menu</a></li>
                <li><a href="search.php">Search</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="contact.php">Contact</a></li>
                <li><a href="cart.php">Cart</a></li>
                <li><a href="wishlist.php">Wishlist</a></li>
                <li><a href="orders.php">Orders</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <div class="container">
        <h1 style="text-align: center; margin: 2rem 0; color: var(--primary-color);">My Profile</h1>
        
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
                        <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                        <small style="color: #7f8c8d;">Username cannot be changed</small>
                    </div>
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
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
        <p>&copy; 2024 Speedy Spice. All rights reserved.</p>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>
<?php $conn->close(); ?>

