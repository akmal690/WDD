<?php
require_once 'config/database.php';
require_once 'config/session.php';
requireLogin();

$conn = getDBConnection();
$user_id = getUserId();

// Handle checkout
if (isset($_GET['checkout'])) {
    // Get user info
    $user_query = "SELECT phone, address FROM users WHERE id = ?";
    $user_stmt = $conn->prepare($user_query);
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $user = $user_result->fetch_assoc();
    $user_stmt->close();
    
    // Get cart items
    $cart_query = "SELECT c.product_id, c.quantity, p.price 
                   FROM cart c 
                   JOIN products p ON c.product_id = p.id 
                   WHERE c.user_id = ?";
    $cart_stmt = $conn->prepare($cart_query);
    $cart_stmt->bind_param("i", $user_id);
    $cart_stmt->execute();
    $cart_result = $cart_stmt->get_result();
    
    $total = 0;
    $items = [];
    while ($item = $cart_result->fetch_assoc()) {
        $subtotal = $item['price'] * $item['quantity'];
        $total += $subtotal;
        $items[] = $item;
    }
    $cart_stmt->close();
    
    if (count($items) > 0) {
        // Create order
        $address = $user['address'] ?? 'Not provided';
        $phone = $user['phone'] ?? 'Not provided';
        
        $order_stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, delivery_address, phone) VALUES (?, ?, ?, ?)");
        $order_stmt->bind_param("idss", $user_id, $total, $address, $phone);
        $order_stmt->execute();
        $order_id = $conn->insert_id;
        $order_stmt->close();
        
        // Create order items
        $item_stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        foreach ($items as $item) {
            $item_stmt->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['price']);
            $item_stmt->execute();
        }
        $item_stmt->close();
        
        // Clear cart
        $conn->query("DELETE FROM cart WHERE user_id = $user_id");
        
        header('Location: orders.php?success=1');
        exit();
    }
}

// Handle delete order
if (isset($_GET['delete'])) {
    $order_id = intval($_GET['delete']);
    
    // Verify order belongs to user
    $check = $conn->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ?");
    $check->bind_param("ii", $order_id, $user_id);
    $check->execute();
    $check_result = $check->get_result();
    
    if ($check_result->num_rows > 0) {
        // Delete order items first
        $conn->query("DELETE FROM order_items WHERE order_id = $order_id");
        // Delete order
        $conn->query("DELETE FROM orders WHERE id = $order_id");
        header('Location: orders.php?deleted=1');
        exit();
    }
    $check->close();
}

// Handle order form submission
if (isset($_POST['place_order'])) {
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);
    
    // Get cart items
    $cart_query = "SELECT c.product_id, c.quantity, p.price 
                   FROM cart c 
                   JOIN products p ON c.product_id = p.id 
                   WHERE c.user_id = ?";
    $cart_stmt = $conn->prepare($cart_query);
    $cart_stmt->bind_param("i", $user_id);
    $cart_stmt->execute();
    $cart_result = $cart_stmt->get_result();
    
    $total = 0;
    $items = [];
    while ($item = $cart_result->fetch_assoc()) {
        $subtotal = $item['price'] * $item['quantity'];
        $total += $subtotal;
        $items[] = $item;
    }
    $cart_stmt->close();
    
    if (count($items) > 0) {
        // Create order
        $order_stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, delivery_address, phone) VALUES (?, ?, ?, ?)");
        $order_stmt->bind_param("idss", $user_id, $total, $address, $phone);
        $order_stmt->execute();
        $order_id = $conn->insert_id;
        $order_stmt->close();
        
        // Create order items
        $item_stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        foreach ($items as $item) {
            $item_stmt->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['price']);
            $item_stmt->execute();
        }
        $item_stmt->close();
        
        // Clear cart
        $conn->query("DELETE FROM cart WHERE user_id = $user_id");
        
        header('Location: orders.php?success=1');
        exit();
    }
}

// Get user orders
$orders_query = "SELECT o.*, 
                 (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
                 FROM orders o 
                 WHERE o.user_id = ? 
                 ORDER BY o.created_at DESC";
$orders_stmt = $conn->prepare($orders_query);
$orders_stmt->bind_param("i", $user_id);
$orders_stmt->execute();
$orders_result = $orders_stmt->get_result();

// Get user info for form
$user_query = "SELECT phone, address FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_info = $user_result->fetch_assoc();
$user_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - ACE Hardware Store</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .page-header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
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
            color: #10b981;
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
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
            </svg>
            My Orders
        </h1>
        <p style="font-size: 1.1rem; opacity: 0.9;">Track and manage your orders</p>
    </div>

    <div class="container">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">Order placed successfully!</div>
        <?php endif; ?>
        
        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success">Order deleted successfully!</div>
        <?php endif; ?>
        
        <?php if ($orders_result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th style="display: flex; align-items: center; gap: 0.5rem;">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                            </svg>
                            Order ID
                        </th>
                        <th style="display: flex; align-items: center; gap: 0.5rem;">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            Date
                        </th>
                        <th style="display: flex; align-items: center; gap: 0.5rem;">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            Items
                        </th>
                        <th style="display: flex; align-items: center; gap: 0.5rem;">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Total Amount
                        </th>
                        <th style="display: flex; align-items: center; gap: 0.5rem;">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Status
                        </th>
                        <th style="display: flex; align-items: center; gap: 0.5rem;">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            Address
                        </th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($order = $orders_result->fetch_assoc()): ?>
                        <tr>
                            <td><strong>#<?php echo $order['id']; ?></strong></td>
                            <td><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></td>
                            <td><?php echo $order['item_count']; ?> item(s)</td>
                            <td style="font-weight: bold; color: var(--primary-color);">Rs <?php echo number_format($order['total_amount'], 2); ?></td>
                            <td>
                                <span class="badge <?php 
                                    echo $order['status'] === 'completed' ? 'badge-success' : 
                                        ($order['status'] === 'pending' ? 'badge-warning' : 'badge-danger'); 
                                ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($order['delivery_address']); ?></td>
                            <td>
                                <?php if ($order['status'] === 'pending'): ?>
                                    <a href="orders.php?delete=<?php echo $order['id']; ?>" 
                                       class="btn btn-danger" 
                                       style="padding: 0.3rem 0.8rem; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 0.3rem;"
                                       onclick="return confirm('Are you sure you want to cancel this order?')">
                                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                        Cancel
                                    </a>
                                <?php else: ?>
                                    <span style="color: #7f8c8d;">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <h2 style="color: var(--dark-color); margin-bottom: 1rem;">No orders yet</h2>
                <p style="color: var(--dark-light); margin: 1rem 0;">Start ordering BEST hardware products!</p>
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
$orders_stmt->close();
$conn->close(); 
?>
