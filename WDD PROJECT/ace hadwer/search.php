<?php
require_once 'config/database.php';
require_once 'config/session.php';

$conn = getDBConnection();

// Get search query
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';

// Build search query
$where = "WHERE status = 'active'";
$params = [];

if ($search_query) {
    $where .= " AND (name LIKE ? OR description LIKE ? OR category LIKE ?)";
    $search_term = "%{$search_query}%";
    $params = [$search_term, $search_term, $search_term];
}

if ($category_filter) {
    $where .= " AND category = ?";
    $params[] = $category_filter;
}

// Get products
$products_query = "SELECT * FROM products $where ORDER BY name";
$stmt = null;

if (!empty($params)) {
    $stmt = $conn->prepare($products_query);
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $products_result = $stmt->get_result();
} else {
    $products_result = $conn->query($products_query);
}

// Get categories for filter
$categories_query = "SELECT DISTINCT category FROM products WHERE status = 'active'";
$categories_result = $conn->query($categories_query);

// Count results
$total_results = $products_result->num_rows;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search - ACE Hardware Store</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .search-header {
            background: var(--gradient-primary);
            padding: 3rem 2rem;
            text-align: center;
            color: var(--white);
            margin-bottom: 3rem;
        }
        
        .search-box {
            max-width: 700px;
            margin: 0 auto;
            position: relative;
        }
        
        .search-input {
            width: 100%;
            padding: 1.2rem 1.2rem 1.2rem 3.5rem;
            border: none;
            border-radius: 50px;
            font-size: 1.1rem;
            box-shadow: var(--shadow-lg);
            transition: all 0.3s ease;
        }
        
        .search-input:focus {
            outline: none;
            box-shadow: 0 0 0 4px rgba(255,255,255,0.3), var(--shadow-xl);
            transform: translateY(-2px);
        }
        
        .search-icon {
            position: absolute;
            left: 1.2rem;
            top: 50%;
            transform: translateY(-50%);
            width: 24px;
            height: 24px;
            color: var(--primary-color);
        }
        
        .search-filters {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 0.6rem 1.5rem;
            border: 2px solid rgba(255,255,255,0.3);
            background: rgba(255,255,255,0.1);
            color: var(--white);
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }
        
        .filter-btn:hover,
        .filter-btn.active {
            background: var(--white);
            color: var(--primary-color);
            border-color: var(--white);
            transform: translateY(-2px);
        }
        
        .results-info {
            text-align: center;
            margin: 2rem 0;
            color: var(--dark-light);
            font-size: 1.1rem;
        }
        
        .no-results {
            text-align: center;
            padding: 4rem 2rem;
        }
        
        .no-results svg {
            width: 120px;
            height: 120px;
            margin: 0 auto 2rem;
            opacity: 0.3;
        }
        
        .no-results h2 {
            color: var(--dark-light);
            margin-bottom: 1rem;
        }
        
        .no-results p {
            color: var(--dark-light);
            margin-bottom: 2rem;
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
                <?php if (isLoggedIn()): ?>
                    <li><a href="cart.php">Cart</a></li>
                    <li><a href="wishlist.php">Wishlist</a></li>
                    <li><a href="orders.php">Orders</a></li>
                    <li><a href="profile.php">Profile</a></li>
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

    <div class="search-header">
        <h1 style="font-size: 2.5rem; margin-bottom: 1rem; font-weight: 800;">Search Hardware</h1>
        <p style="font-size: 1.1rem; opacity: 0.9; margin-bottom: 2rem;">Find your favorite hardware products quickly</p>
        
        <form method="GET" action="search.php" class="search-box">
            <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text" 
                   name="q" 
                   class="search-input" 
                   placeholder="Search for hardware products, categories..." 
                   value="<?php echo htmlspecialchars($search_query); ?>"
                   autofocus>
        </form>
        
        <div class="search-filters">
            <a href="search.php<?php echo $search_query ? '?q=' . urlencode($search_query) : ''; ?>" 
               class="filter-btn <?php echo !$category_filter ? 'active' : ''; ?>">
                All
            </a>
            <?php 
            $categories_result->data_seek(0); // Reset pointer
            while ($cat = $categories_result->fetch_assoc()): 
            ?>
                <a href="search.php?<?php echo $search_query ? 'q=' . urlencode($search_query) . '&' : ''; ?>category=<?php echo urlencode($cat['category']); ?>" 
                   class="filter-btn <?php echo $category_filter === $cat['category'] ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($cat['category']); ?>
                </a>
            <?php endwhile; ?>
        </div>
    </div>

    <div class="container">
        <?php if ($search_query || $category_filter): ?>
            <div class="results-info">
                Found <strong><?php echo $total_results; ?></strong> result<?php echo $total_results != 1 ? 's' : ''; ?>
                <?php if ($search_query): ?>
                    for "<strong><?php echo htmlspecialchars($search_query); ?></strong>"
                <?php endif; ?>
                <?php if ($category_filter): ?>
                    in <strong><?php echo htmlspecialchars($category_filter); ?></strong>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($total_results > 0): ?>
            <div class="food-grid">
                <?php while ($product = $products_result->fetch_assoc()): ?>
                    <div class="food-card" id="product-<?php echo $product['id']; ?>">
                        <img src="<?php echo !empty($product['image']) ? htmlspecialchars($product['image']) : 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400&h=300&fit=crop'; ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             onerror="this.src='https://via.placeholder.com/400x300?text=Hardware+Image'">
                        <div class="food-card-content">
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p><?php echo htmlspecialchars($product['description']); ?></p>
                            <div style="margin: 0.5rem 0;">
                                <span style="background: var(--gradient-primary); color: var(--white); padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">
                                    <?php echo htmlspecialchars($product['category']); ?>
                                </span>
                            </div>
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
        <?php else: ?>
            <div class="no-results">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <h2>No Results Found</h2>
                <p>
                    <?php if ($search_query): ?>
                        We couldn't find any items matching "<?php echo htmlspecialchars($search_query); ?>"
                    <?php else: ?>
                                    Start searching to find high-quality hardware products!
                    <?php endif; ?>
                </p>
                <a href="menu.php" class="btn btn-primary">Browse All Products</a>
            </div>
        <?php endif; ?>
    </div>

    <footer>
        <p>&copy; 2024 ACE Hardware Store. All rights reserved.</p>
    </footer>

    <script src="assets/js/main.js"></script>
    <script>
        // Auto-submit search on Enter key
        document.querySelector('.search-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.form.submit();
            }
        });
    </script>
</body>
</html>
<?php 
if ($stmt) {
    $stmt->close();
}
$conn->close(); 
?>

