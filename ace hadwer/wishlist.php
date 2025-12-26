<?php
require_once 'config/database.php';
require_once 'config/session.php';
requireLogin();

$conn = getDBConnection();
$user_id = getUserId();

// Handle remove from wishlist
if (isset($_GET['remove'])) {
    $wishlist_id = intval($_GET['remove']);
    $delete = $conn->prepare("DELETE FROM wishlist WHERE id = ? AND user_id = ?");
    $delete->bind_param("ii", $wishlist_id, $user_id);
    $delete->execute();
    $delete->close();
    header('Location: wishlist.php');
    exit();
}

// Handle add to cart from wishlist
if (isset($_GET['add_cart'])) {
    $product_id = intval($_GET['add_cart']);
    
    // Check if item already in cart
    $check = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
    $check->bind_param("ii", $user_id, $product_id);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows > 0) {
        $cart_item = $result->fetch_assoc();
        $new_quantity = $cart_item['quantity'] + 1;
        $update = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $update->bind_param("ii", $new_quantity, $cart_item['id']);
        $update->execute();
        $update->close();
    } else {
        $insert = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)");
        $insert->bind_param("ii", $user_id, $product_id);
        $insert->execute();
        $insert->close();
    }
    $check->close();
    header('Location: wishlist.php?added=1');
    exit();
}

// Get wishlist items
$wishlist_query = "SELECT w.id, p.id as product_id, p.name, p.price, p.description, p.image 
                   FROM wishlist w 
                   JOIN products p ON w.product_id = p.id 
                   WHERE w.user_id = ?";
$stmt = $conn->prepare($wishlist_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$wishlist_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wishlist - Food Order</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .page-header {
            background: linear-gradient(135deg, #ec4899 0%, #be185d 100%);
            padding: 3rem 2rem;
            text-align: center;
            color: var(--white);
            margin-bottom: 3rem;
        }
        
        .page-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
        }
        
        .page-header svg {
            width: 40px;
            height: 40px;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }
        
        .empty-state svg {
            width: 150px;
            height: 150px;
            margin: 0 auto 2rem;
            opacity: 0.3;
            color: #ec4899;
        }
    </style>
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
                <li><a href="cart.php" style="display: inline-flex; align-items: center; gap: 0.3rem;">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Cart
                </a></li>
                <li><a href="wishlist.php" style="display: inline-flex; align-items: center; gap: 0.3rem;">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                    Wishlist
                </a></li>
                <li><a href="orders.php" style="display: inline-flex; align-items: center; gap: 0.3rem;">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    Orders
                </a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <div class="page-header">
        <h1>
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
            </svg>
            My Wishlist
        </h1>
        <p style="font-size: 1.1rem; opacity: 0.9;">Your favorite items saved for later</p>
    </div>

    <div class="container">
        <?php if (isset($_GET['added'])): ?>
            <div class="alert alert-success">Item added to cart!</div>
        <?php endif; ?>
        
        <?php if ($wishlist_result->num_rows > 0): ?>
            <div class="food-grid">
                <?php while ($item = $wishlist_result->fetch_assoc()): ?>
                    <div class="food-card">
                        <img src="<?php echo !empty($item['image']) ? htmlspecialchars($item['image']) : 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400&h=300&fit=crop'; ?>" 
                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                             onerror="this.src='https://via.placeholder.com/400x300?text=Food+Image'">
                        <div class="food-card-content">
                            <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p><?php echo htmlspecialchars($item['description']); ?></p>
                            <div class="food-card-footer">
                                <span class="price">Rs <?php echo number_format($item['price'], 2); ?></span>
                                <div style="display: flex; gap: 0.5rem;">
                                    <a href="wishlist.php?add_cart=<?php echo $item['product_id']; ?>" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 0.3rem;">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                        Add to Cart
                                    </a>
                                    <a href="wishlist.php?remove=<?php echo $item['id']; ?>" class="btn btn-danger" style="display: inline-flex; align-items: center; gap: 0.3rem;">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                        Remove
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                </svg>
                <h2 style="color: var(--dark-color); margin-bottom: 1rem;">Your wishlist is empty</h2>
                <p style="color: var(--dark-light); margin: 1rem 0;">Start adding items you love!</p>
                <a href="menu.php" class="btn btn-primary">Browse Menu</a>
            </div>
        <?php endif; ?>
    </div>

    <footer>
        <p>&copy; 2024 ACE Hardware Store. All rights reserved.</p>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>
<?php 
$stmt->close();
$conn->close(); 
?>
