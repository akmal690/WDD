<?php
require_once 'config/session.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Food Order</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <nav>
            <a href="index.php" class="logo"> ACE Hardware Store </a>
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
        <h1 style="text-align: center; margin: 2rem 0; color: var(--primary-color);">About Us</h1>
        
        <div style="background: var(--white); padding: 3rem; border-radius: 10px; box-shadow: var(--shadow); margin: 2rem 0;">
            <h2 style="color: var(--primary-color); margin-bottom: 1rem;">Welcome to ACE Hardware Store </h2>
            <p style="font-size: 1.1rem; line-height: 1.8; margin-bottom: 1.5rem;">
            ACE Hardware is your one-stop destination for quality tools, hardware, and home improvement solutions.
            We are committed to providing reliable products and expert support to help you build, repair, and improve your home with confidence.
            </p>
            
            <h3 style="color: var(--dark-color); margin-top: 2rem; margin-bottom: 1rem;">Our Mission</h3>
            <p style="font-size: 1.1rem; line-height: 1.8; margin-bottom: 1.5rem;">
            Our mission is to provide high-quality hardware products with exceptional customer service. We believe that reliable tools and building materials should be accessible to everyone, and we work tirelessly to ensure that every product we offer meets the highest standards of durability, performance, and value.
            </p>
            
            <h3 style="color: var(--dark-color); margin-top: 2rem; margin-bottom: 1rem;">Why Choose Us?</h3>
            <ul style="font-size: 1.1rem; line-height: 2; margin-left: 2rem;">
            <li>High-quality tools and hardware products</li>
<li>Wide range of home improvement and construction supplies</li>
<li>Reliable and trusted brands</li>
<li>Competitive and affordable prices</li>
<li>Excellent customer support and expert advice</li>
<li>Easy and convenient online ordering system</li>

            </ul>
            
            <h3 style="color: var(--dark-color); margin-top: 2rem; margin-bottom: 1rem;">Our Story</h3>
            <p style="font-size: 1.1rem; line-height: 1.8;">
            Founded in 2024, ACE Hardware Store began with a simple goal: to make quality tools and home improvement solutions accessible to everyone. Since then, we have grown into a trusted destination for homeowners, DIY enthusiasts, and professionals alike. We continue to expand our product range and improve our services to deliver the best hardware shopping experience possible.
            </p>
        </div>
    </div>

    <footer>
        <p>&copy; 2024 Speedy Spice. All rights reserved.</p>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>

