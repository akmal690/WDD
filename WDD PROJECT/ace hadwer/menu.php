<?php
require_once 'config/database.php';
require_once 'config/session.php';

$conn = getDBConnection();

// Handle add to cart
if (isset($_GET['add_cart']) && isLoggedIn()) {
    $product_id = intval($_GET['add_cart']);
    $user_id = getUserId();
    
    // Check if item already in cart
    $check = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
    $check->bind_param("ii", $user_id, $product_id);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows > 0) {
        // Update quantity
        $cart_item = $result->fetch_assoc();
        $new_quantity = $cart_item['quantity'] + 1;
        $update = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $update->bind_param("ii", $new_quantity, $cart_item['id']);
        $update->execute();
        $update->close();
    } else {
        // Add new item
        $insert = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)");
        $insert->bind_param("ii", $user_id, $product_id);
        $insert->execute();
        $insert->close();
    }
    $check->close();
    header('Location: menu.php?added=1');
    exit();
}

// Handle add to wishlist
if (isset($_GET['add_wishlist']) && isLoggedIn()) {
    $product_id = intval($_GET['add_wishlist']);
    $user_id = getUserId();
    
    $insert = $conn->prepare("INSERT IGNORE INTO wishlist (user_id, product_id) VALUES (?, ?)");
    $insert->bind_param("ii", $user_id, $product_id);
    $insert->execute();
    $insert->close();
    header('Location: menu.php?wishlist_added=1');
    exit();
}

// Get all products
$category = isset($_GET['category']) ? $_GET['category'] : '';
$where = "WHERE status = 'active'";
if ($category) {
    $where .= " AND category = '" . $conn->real_escape_string($category) . "'";
}

$products_query = "SELECT * FROM products $where ORDER BY name";
$products_result = $conn->query($products_query);

// Get categories
$categories_query = "SELECT DISTINCT category FROM products WHERE status = 'active'";
$categories_result = $conn->query($categories_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Menu - ACE Hardware Store</title>
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
        <h1 style="text-align: center; margin: 2rem 0; color: var(--primary-color);">Our Menu</h1>
        
        <?php if (isset($_GET['added'])): ?>
            <div class="alert alert-success">Item added to cart!</div>
        <?php endif; ?>
        
        <?php if (isset($_GET['wishlist_added'])): ?>
            <div class="alert alert-success">Item added to wishlist!</div>
        <?php endif; ?>
        
        <!-- Category Filter -->
        <div style="text-align: center; margin-bottom: 2rem;">
            <a href="menu.php" class="btn <?php echo !$category ? 'btn-primary' : 'btn-secondary'; ?>">All</a>
            <?php while ($cat = $categories_result->fetch_assoc()): ?>
                <a href="menu.php?category=<?php echo urlencode($cat['category']); ?>" 
                   class="btn <?php echo $category === $cat['category'] ? 'btn-primary' : 'btn-secondary'; ?>">
                    <?php echo htmlspecialchars($cat['category']); ?>
                </a>
            <?php endwhile; ?>
        </div>
        
        <div class="food-grid">
            <?php while ($product = $products_result->fetch_assoc()): ?>
                <div class="food-card" id="product-<?php echo $product['id']; ?>">
                    <img src="<?php echo !empty($product['image']) ? htmlspecialchars($product['image']) : 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400&h=300&fit=crop'; ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                            onerror="this.src='https://via.placeholder.com/400x300?text=Hardware+Image'">
                    <div class="food-card-content">
                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p><?php echo htmlspecialchars($product['description']); ?></p>
                        <div class="food-card-footer">
                            <span class="price">Rs <?php echo number_format($product['price'], 2); ?></span>
                            <div style="display: flex; gap: 0.5rem;">
                                <?php if (isLoggedIn()): ?>
                                    <a href="menu.php?add_cart=<?php echo $product['id']; ?>" class="btn btn-primary">Add to Cart</a>
                                    <a href="menu.php?add_wishlist=<?php echo $product['id']; ?>" class="btn btn-secondary">❤️</a>
                                <?php else: ?>
                                    <a href="customer_login.php" class="btn btn-primary">Login to Order</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <footer>
        <p>&copy; 2024 ACE Hardware Store. All rights reserved.</p>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>
<?php $conn->close(); ?>

