<?php
require_once 'config/database.php';
require_once 'config/session.php';

$success = '';
$error = '';

if (isset($_POST['send_message'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    if ($name && $email && $message) {
        $conn = getDBConnection();
        $stmt = $conn->prepare("INSERT INTO messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $subject, $message);
        
        if ($stmt->execute()) {
            $success = "Message sent successfully! We'll get back to you soon.";
        } else {
            $error = "Failed to send message. Please try again.";
        }
        
        $stmt->close();
        $conn->close();
    } else {
        $error = "Please fill in all required fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Food Order</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <nav>
            <a href="index.php" class="logo">ACE Hardware Store</a>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="menu.php">Menu</a></li>
                <li><a href="search.php">Search</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="contact.php">Contact</a></li>
                <?php if (isLoggedIn()): ?>
                    <li><a href="cart.php">Cart</a></li>
                    <li><a href="wishlist.php">Wishlist</a></li>
                    <li><a href="orders.php">Orders</a></li>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="customer_login.php">Login</a></li>
                    <li><a href="customer_register.php" class="btn btn-register" style="display: inline-flex; align-items: center; gap: 0.5rem;">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                        Register
                    </a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <div class="container">
        <h1 style="text-align: center; margin: 2rem 0; color: var(--primary-color);">Contact Us</h1>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin: 2rem 0;">
            <div style="background: var(--white); padding: 2rem; border-radius: 10px; box-shadow: var(--shadow);">
                <h2 style="color: var(--primary-color); margin-bottom: 1.5rem;">Get in Touch</h2>
                <div style="margin-bottom: 1.5rem;">
                    <h3 style="color: var(--dark-color); margin-bottom: 0.5rem;">📍 Address</h3>
                    <p>123 Food Street, City, State 12345</p>
                </div>
                <div style="margin-bottom: 1.5rem;">
                    <h3 style="color: var(--dark-color); margin-bottom: 0.5rem;">📞 Phone</h3>
                    <p>+1 (555) 123-4567</p>
                </div>
                <div style="margin-bottom: 1.5rem;">
                    <h3 style="color: var(--dark-color); margin-bottom: 0.5rem;">✉️ Email</h3>
                    <p>info@acehardwarestore.com</p>
                </div>
                <div>
                    <h3 style="color: var(--dark-color); margin-bottom: 0.5rem;">🕒 Hours</h3>
                    <p>Monday - Friday: 9:00 AM - 10:00 PM<br>
                       Saturday - Sunday: 10:00 AM - 11:00 PM</p>
                </div>
            </div>
            
            <div style="background: var(--white); padding: 2rem; border-radius: 10px; box-shadow: var(--shadow);">
                <h2 style="color: var(--primary-color); margin-bottom: 1.5rem;">Send us a Message</h2>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Name *</label>
                        <input type="text" name="name" required 
                               value="<?php echo isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Subject</label>
                        <input type="text" name="subject">
                    </div>
                    <div class="form-group">
                        <label>Message *</label>
                        <textarea name="message" required></textarea>
                    </div>
                    <button type="submit" name="send_message" class="btn btn-primary" style="width: 100%;">Send Message</button>
                </form>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; 2024 ACE Hardware Store. All rights reserved.</p>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>

