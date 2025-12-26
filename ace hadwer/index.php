<?php
require_once 'config/database.php';
require_once 'config/session.php';

$conn = getDBConnection();

// Get featured products
$featured_query = "SELECT * FROM products WHERE status = 'active' LIMIT 6";
$featured_result = $conn->query($featured_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ACE Hardware Store - Home</title>
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

    <section class="hero">
        <h1>High-Quality Tools and Building Materials Delivered to Your Door</h1>
        <p>Shop our wide selection of hardware products and get expert advice to help you succeed</p>
        <a href="menu.php" class="btn btn-primary">Order Now</a>
    </section>

    <div class="container">
        <h2 style="text-align: center; margin-bottom: 2rem; color: var(--dark-color);">Featured Products</h2>
        
        <div class="food-grid">
            <?php while ($product = $featured_result->fetch_assoc()): ?>
                <div class="food-card" onclick="window.location.href='menu.php#product-<?php echo $product['id']; ?>'">
                    <img src="<?php echo !empty($product['image']) ? htmlspecialchars($product['image']) : 'https://img.hoodline.com/uploads/story/image/698737/o.jpg'; ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                         onerror="this.src='https://via.placeholder.com/400x300?text=Hardware+Image'">
                    <div class="food-card-content">
                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p><?php echo htmlspecialchars($product['description']); ?></p>
                        <div class="food-card-footer">
                            <span class="price">Rs <?php echo number_format($product['price'], 2); ?></span>
                            <?php if (isLoggedIn()): ?>
                                <a href="menu.php?add_cart=<?php echo $product['id']; ?>" class="btn btn-primary">Add to Cart</a>
                            <?php else: ?>
                                <a href="customer_login.php" class="btn btn-primary">Login to Order</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <div style="text-align: center; margin: 3rem 0;">
            <a href="menu.php" class="btn btn-secondary">View Full Menu</a>
        </div>
    </div>

    <footer>
                <p>&copy; 2024 ACE Hardware Store. All rights reserved.</p>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>
<?php $conn->close(); ?>

