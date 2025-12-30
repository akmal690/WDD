<?php
require_once 'config/database.php';
require_once 'config/session.php';
requireLogin();

$conn = getDBConnection();
$user_id = getUserId();

// Handle remove from cart
if (isset($_GET['remove'])) {
    $cart_id = intval($_GET['remove']);
    $delete = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $delete->bind_param("ii", $cart_id, $user_id);
    $delete->execute();
    $delete->close();
    header('Location: cart.php');
    exit();
}

// Handle update quantity
if (isset($_POST['update_quantity'])) {
    $cart_id = intval($_POST['cart_id']);
    $quantity = intval($_POST['quantity']);
    
    if ($quantity > 0) {
        $update = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
        $update->bind_param("iii", $quantity, $cart_id, $user_id);
        $update->execute();
        $update->close();
    } else {
        $delete = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        $delete->bind_param("ii", $cart_id, $user_id);
        $delete->execute();
        $delete->close();
    }
    header('Location: cart.php');
    exit();
}

// Get cart items
$cart_query = "SELECT c.id, c.quantity, p.id as product_id, p.name, p.price, p.image 
               FROM cart c 
               JOIN products p ON c.product_id = p.id 
               WHERE c.user_id = ?";
$stmt = $conn->prepare($cart_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_result = $stmt->get_result();

$total = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart - Food Order</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .page-header {
            background: var(--gradient-primary);
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
            color: var(--primary-color);
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
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            Shopping Cart
        </h1>
        <p style="font-size: 1.1rem; opacity: 0.9;">Review your items before checkout</p>
    </div>

    <div class="container">
        <?php if (isset($_GET['empty'])): ?>
            <div class="alert alert-error" style="background: #fff3cd; color: #856404; border: 1px solid #ffeaa7;">
                Your cart is empty. Please add items to your cart before checkout.
            </div>
        <?php endif; ?>
        
        <?php if ($cart_result->num_rows > 0): ?>
            <?php while ($item = $cart_result->fetch_assoc()): 
                $item_total = $item['price'] * $item['quantity'];
                $total += $item_total;
            ?>
                <div class="cart-item">
                    <img src="<?php echo !empty($item['image']) ? htmlspecialchars($item['image']) : 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=200&h=200&fit=crop'; ?>" 
                         alt="<?php echo htmlspecialchars($item['name']); ?>"
                         onerror="this.src='https://via.placeholder.com/200x200?text=Food'">
                    <div class="cart-item-content">
                        <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                        <p style="font-size: 1.2rem; color: var(--primary-color); font-weight: bold;">
                            Rs <?php echo number_format($item['price'], 2); ?>
                        </p>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                            <div class="quantity-control">
                                <button type="submit" name="update_quantity" 
                                        onclick="this.form.quantity.value=Math.max(1,parseInt(this.form.quantity.value)-1);">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                    </svg>
                                </button>
                                <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" readonly>
                                <button type="submit" name="update_quantity" 
                                        onclick="this.form.quantity.value=parseInt(this.form.quantity.value)+1;">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                </button>
                            </div>
                        </form>
                        <p style="margin-top: 0.5rem; font-weight: bold;">
                            Subtotal: Rs <?php echo number_format($item_total, 2); ?>
                        </p>
                    </div>
                    <a href="cart.php?remove=<?php echo $item['id']; ?>" class="btn btn-danger" style="display: inline-flex; align-items: center; gap: 0.5rem;">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Remove
                    </a>
                </div>
            <?php endwhile; ?>
            
            <div style="background: var(--white); padding: 2.5rem; border-radius: 20px; box-shadow: var(--shadow-lg); margin-top: 2rem; border: 2px solid var(--primary-color);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h2 style="color: var(--primary-color); font-size: 2rem;">Total: Rs <?php echo number_format($total, 2); ?></h2>
                </div>
                <a href="checkout.php" class="btn btn-primary" style="width: 100%; font-size: 1.2rem; padding: 1rem; display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem;">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    Proceed to Checkout
                </a>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <h2 style="color: var(--dark-color); margin-bottom: 1rem;">Your cart is empty</h2>
                <p style="color: var(--dark-light); margin: 1rem 0;">Discover our range of high-quality hardware items!</p>
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
