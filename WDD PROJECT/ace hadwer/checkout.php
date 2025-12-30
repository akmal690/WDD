<?php
require_once 'config/database.php';
require_once 'config/session.php';
requireLogin();

$conn = getDBConnection();
$user_id = getUserId();

// Get user information
$user_query = "SELECT * FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();
$user_stmt->close();

$success_message = '';
$error_message = '';

// Handle order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $delivery_address = trim($_POST['delivery_address']);
    $phone = trim($_POST['phone']);
    $payment_method = $_POST['payment_method'] ?? '';
    
    // Validation
    if (empty($delivery_address)) {
        $error_message = 'Delivery address is required.';
    } elseif (empty($phone)) {
        $error_message = 'Phone number is required.';
    } elseif (empty($payment_method)) {
        $error_message = 'Please select a payment method.';
    } else {
        // Get cart items for order processing
        $cart_query = "SELECT c.id, c.quantity, p.id as product_id, p.name, p.price, p.image 
                       FROM cart c 
                       JOIN products p ON c.product_id = p.id 
                       WHERE c.user_id = ?";
        $cart_stmt = $conn->prepare($cart_query);
        $cart_stmt->bind_param("i", $user_id);
        $cart_stmt->execute();
        $cart_result = $cart_stmt->get_result();

        // Calculate total and prepare items
        $order_items = [];
        $total = 0;
        while ($item = $cart_result->fetch_assoc()) {
            $item_total = $item['price'] * $item['quantity'];
            $total += $item_total;
            $order_items[] = $item;
        }
        $cart_stmt->close();
        
        if (empty($order_items)) {
            $error_message = 'Your cart is empty. Please add items to your cart first.';
        } else {
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Create order
                $order_query = "INSERT INTO orders (user_id, total_amount, delivery_address, phone, status) VALUES (?, ?, ?, ?, 'pending')";
                $order_stmt = $conn->prepare($order_query);
                $order_stmt->bind_param("idss", $user_id, $total, $delivery_address, $phone);
                
                if ($order_stmt->execute()) {
                    $order_id = $conn->insert_id;
                    $order_stmt->close();
                    
                    // Add order items
                    $item_stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                    foreach ($order_items as $item) {
                        $item_stmt->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['price']);
                        $item_stmt->execute();
                    }
                    $item_stmt->close();
                    
                    // Clear cart
                    $clear_stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
                    $clear_stmt->bind_param("i", $user_id);
                    $clear_stmt->execute();
                    $clear_stmt->close();
                    
                    // Commit transaction
                    $conn->commit();
                    
                    // Set success message and redirect
                    $_SESSION['order_success'] = "Order #$order_id placed successfully! Total: Rs " . number_format($total, 2);
                    header('Location: orders.php?success=1&order_id=' . $order_id);
                    exit();
                } else {
                    throw new Exception("Failed to create order");
                }
                
            } catch (Exception $e) {
                // Rollback transaction
                $conn->rollback();
                $error_message = 'Failed to place order. Please try again.';
            }
        }
    }
}

// Get cart items for display (only if no POST request or if there was an error)
$cart_query = "SELECT c.id, c.quantity, p.id as product_id, p.name, p.price, p.image 
               FROM cart c 
               JOIN products p ON c.product_id = p.id 
               WHERE c.user_id = ?";
$cart_stmt = $conn->prepare($cart_query);
$cart_stmt->bind_param("i", $user_id);
$cart_stmt->execute();
$cart_result = $cart_stmt->get_result();

// Calculate total
$cart_items = [];
$total = 0;
while ($item = $cart_result->fetch_assoc()) {
    $item_total = $item['price'] * $item['quantity'];
    $total += $item_total;
    $cart_items[] = $item;
}
$cart_stmt->close();

// If cart is empty and no error message, redirect to cart page
if (empty($cart_items) && empty($error_message)) {
    header('Location: cart.php?empty=1');
    exit();
}

// If cart is empty but there's an error, show a message to add items
if (empty($cart_items) && !empty($error_message)) {
    // Show checkout page with error message and link to menu
    $show_empty_cart_message = true;
} else {
    $show_empty_cart_message = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - ACE Hardware Store</title>
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
        
        .checkout-container {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        .checkout-form {
            background: var(--white);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: var(--shadow-lg);
            height: fit-content;
        }
        
        .order-summary {
            background: var(--white);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: var(--shadow-lg);
            border: 2px solid var(--primary-color);
            height: fit-content;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid var(--light-color);
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .payment-methods {
            display: grid;
            gap: 1rem;
        }
        
        .payment-option {
            display: flex;
            align-items: center;
            padding: 1rem;
            border: 2px solid var(--light-color);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .payment-option:hover {
            border-color: var(--primary-color);
            background: var(--light-bg);
        }
        
        .payment-option input[type="radio"] {
            margin-right: 1rem;
            width: auto;
        }
        
        .payment-option.selected {
            border-color: var(--primary-color);
            background: var(--light-bg);
        }
        
        .order-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid var(--light-color);
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .order-item img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .order-item-details {
            flex: 1;
        }
        
        .order-item-details h4 {
            margin: 0 0 0.25rem 0;
            font-size: 0.9rem;
        }
        
        .order-item-details p {
            margin: 0;
            color: var(--dark-light);
            font-size: 0.85rem;
        }
        
        .order-total {
            border-top: 2px solid var(--primary-color);
            padding-top: 1rem;
            margin-top: 1rem;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        
        .total-row.final {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        @media (max-width: 768px) {
            .checkout-container {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .page-header h1 {
                font-size: 2rem;
            }
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
        <div style="margin-bottom: 1rem;">
            <a href="cart.php" style="color: var(--white); text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; opacity: 0.9; font-size: 0.9rem;">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Back to Cart
            </a>
        </div>
        <h1>
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
            </svg>
            Checkout
        </h1>
        <p style="font-size: 1.1rem; opacity: 0.9;">Complete your order</p>
    </div>

    <div class="checkout-container">
        <?php if ($show_empty_cart_message): ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 3rem;">
                <div style="background: var(--white); padding: 3rem; border-radius: 15px; box-shadow: var(--shadow-lg);">
                    <svg width="100" height="100" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin: 0 auto 2rem; color: var(--primary-color); opacity: 0.5;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <h2 style="color: var(--dark-color); margin-bottom: 1rem;">Your cart is empty</h2>
                    <?php if ($error_message): ?>
                        <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
                    <?php endif; ?>
                    <p style="color: var(--dark-light); margin: 1rem 0;">Please add items to your cart before proceeding to checkout.</p>
                    <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 2rem;">
                        <a href="menu.php" class="btn btn-primary">Browse Products</a>
                        <a href="cart.php" class="btn btn-secondary">View Cart</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
        <div class="checkout-form">
            <h2 style="margin-bottom: 1.5rem; color: var(--primary-color);">Delivery Information</h2>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" 
                           value="<?php echo htmlspecialchars($user['full_name']); ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number *</label>
                    <input type="tel" id="phone" name="phone" 
                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                           required placeholder="Enter your phone number">
                </div>
                
                <div class="form-group">
                    <label for="delivery_address">Delivery Address *</label>
                    <textarea id="delivery_address" name="delivery_address" 
                              required placeholder="Enter your complete delivery address"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Payment Method *</label>
                    <div class="payment-methods">
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="cash_on_delivery" required>
                            <div>
                                <strong>Cash on Delivery</strong>
                                <p style="margin: 0.25rem 0 0 0; color: var(--dark-light); font-size: 0.9rem;">
                                    Pay when your order arrives
                                </p>
                            </div>
                        </label>
                        
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="bank_transfer" required>
                            <div>
                                <strong>Bank Transfer</strong>
                                <p style="margin: 0.25rem 0 0 0; color: var(--dark-light); font-size: 0.9rem;">
                                    Transfer to our bank account
                                </p>
                            </div>
                        </label>
                        
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="mobile_payment" required>
                            <div>
                                <strong>Mobile Payment</strong>
                                <p style="margin: 0.25rem 0 0 0; color: var(--dark-light); font-size: 0.9rem;">
                                    JazzCash, EasyPaisa, etc.
                                </p>
                            </div>
                        </label>
                    </div>
                </div>
                
                <button type="submit" name="place_order" class="btn btn-primary" 
                        style="width: 100%; font-size: 1.1rem; padding: 1rem; display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem;">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Place Order
                </button>
            </form>
        </div>
        
        <div class="order-summary">
            <h2 style="margin-bottom: 1.5rem; color: var(--primary-color);">Order Summary</h2>
            
            <div class="order-items">
                <?php foreach ($cart_items as $item): ?>
                    <div class="order-item">
                        <img src="<?php echo !empty($item['image']) ? htmlspecialchars($item['image']) : 'https://via.placeholder.com/60x60?text=Item'; ?>" 
                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                             onerror="this.src='https://via.placeholder.com/60x60?text=Item'">
                        <div class="order-item-details">
                            <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                            <p>Qty: <?php echo $item['quantity']; ?> × Rs <?php echo number_format($item['price'], 2); ?></p>
                        </div>
                        <div style="font-weight: bold; color: var(--primary-color);">
                            Rs <?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="order-total">
                <div class="total-row">
                    <span>Subtotal:</span>
                    <span>Rs <?php echo number_format($total, 2); ?></span>
                </div>
                <div class="total-row">
                    <span>Delivery Fee:</span>
                    <span>Rs 0.00</span>
                </div>
                <div class="total-row final">
                    <span>Total:</span>
                    <span>Rs <?php echo number_format($total, 2); ?></span>
                </div>
            </div>
            
            <div style="margin-top: 1.5rem; padding: 1rem; background: var(--light-bg); border-radius: 8px;">
                <h4 style="margin: 0 0 0.5rem 0; color: var(--primary-color);">Delivery Information</h4>
                <p style="margin: 0; font-size: 0.9rem; color: var(--dark-light);">
                    • Free delivery on all orders<br>
                    • Estimated delivery: 1-2 business days<br>
                    • You will receive SMS updates
                </p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <footer style="margin-top: 3rem;">
        <p>&copy; 2024 ACE Hardware Store. All rights reserved.</p>
    </footer>

    <script src="assets/js/main.js"></script>
    <script>
        // Handle payment method selection styling
        document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
            radio.addEventListener('change', function() {
                // Remove selected class from all options
                document.querySelectorAll('.payment-option').forEach(option => {
                    option.classList.remove('selected');
                });
                
                // Add selected class to chosen option
                this.closest('.payment-option').classList.add('selected');
            });
        });
    </script>
</body>
</html>
<?php 
$conn->close(); 
?>