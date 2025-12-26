<?php
require_once '../config/database.php';
require_once '../config/session.php';
requireAdmin();

$conn = getDBConnection();
$success = '';
$error = '';

// Handle add product
if (isset($_POST['add_product'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $category = trim($_POST['category']);
    $image = trim($_POST['image']);
    $status = $_POST['status'];
    
    $stmt = $conn->prepare("INSERT INTO products (name, description, price, category, image, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdsss", $name, $description, $price, $category, $image, $status);
    
    if ($stmt->execute()) {
        $stmt->close();
        header('Location: products.php?added=1');
        exit();
    } else {
        $error = "Failed to add product!";
        $stmt->close();
    }
}

// Handle update product
if (isset($_POST['update_product'])) {
    $id = intval($_POST['product_id']);
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $category = trim($_POST['category']);
    $image = trim($_POST['image']);
    $status = $_POST['status'];
    
    // Get current product data to preserve image only if new image is empty
    $current_result = $conn->query("SELECT image FROM products WHERE id = $id");
    $current_product = $current_result->fetch_assoc();
    
    // Only preserve existing image if the new image field is completely empty
    // Otherwise, use the new image value (even if it's just whitespace, we'll use it)
    if (empty($image) && !empty($current_product['image'])) {
        $image = $current_product['image'];
    }
    
    $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, category = ?, image = ?, status = ? WHERE id = ?");
    $stmt->bind_param("ssdsssi", $name, $description, $price, $category, $image, $status, $id);
    
    if ($stmt->execute()) {
        $stmt->close();
        // Force refresh by redirecting without edit parameter first, then with it
        header('Location: products.php?updated=1&edit=' . $id);
        exit();
    } else {
        $error = "Failed to update product! Error: " . $conn->error;
        $stmt->close();
    }
}

// Handle delete product
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Temporarily disable foreign key checks to allow deletion
    // This ensures deletion works even if CASCADE is not properly configured
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    
    // Delete related records first
    $conn->query("DELETE FROM cart WHERE product_id = $id");
    $conn->query("DELETE FROM wishlist WHERE product_id = $id");
    $conn->query("DELETE FROM order_items WHERE product_id = $id");
    
    // Now delete the product
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        // Re-enable foreign key checks
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");
        $stmt->close();
        header('Location: products.php?deleted=1');
        exit();
    } else {
        // Re-enable foreign key checks even on error
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");
        $error = "Failed to delete product. Error: " . $conn->error;
        $stmt->close();
    }
}

// Handle delete all products
if (isset($_GET['delete_all']) && $_GET['delete_all'] === 'confirm') {
    // Temporarily disable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    
    // Delete all related records first
    $conn->query("DELETE FROM cart");
    $conn->query("DELETE FROM wishlist");
    $conn->query("DELETE FROM order_items");
    
    // Delete all products
    if ($conn->query("DELETE FROM products")) {
        // Reset AUTO_INCREMENT to start from 1
        $conn->query("ALTER TABLE products AUTO_INCREMENT = 1");
        
        // Re-enable foreign key checks
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");
        header('Location: products.php?all_deleted=1');
        exit();
    } else {
        // Re-enable foreign key checks even on error
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");
        $error = "Failed to delete all products. Error: " . $conn->error;
    }
}

// Get product for editing
$edit_product = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_result = $conn->query("SELECT * FROM products WHERE id = $edit_id");
    $edit_product = $edit_result->fetch_assoc();
}

// Get all products
$products = $conn->query("SELECT * FROM products ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-nav {
            background: var(--dark-color);
            padding: 1rem 0;
            margin-bottom: 2rem;
        }
        .admin-nav ul {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            gap: 1rem;
            list-style: none;
        }
        .admin-nav a {
            color: var(--white);
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .admin-nav a:hover {
            background: rgba(255,255,255,0.1);
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <a href="../index.php" class="logo">ACE Hardware Store Admin</a>
            <ul class="nav-links">
                <li><a href="../index.php">View Site</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <div class="admin-nav">
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="products.php">Products</a></li>
            <li><a href="orders.php">Orders</a></li>
            <li><a href="users.php">Users</a></li>
            <li><a href="messages.php">Messages</a></li>
            <li><a href="profile.php">Profile</a></li>
        </ul>
    </div>

    <div class="container">
        <h1 style="text-align: center; margin-bottom: 2rem; color: var(--primary-color);">Manage Products</h1>
        
        <?php if ($success || isset($_GET['added']) || isset($_GET['deleted']) || isset($_GET['updated']) || isset($_GET['all_deleted'])): ?>
            <div class="alert alert-success"><?php echo $success ?: (isset($_GET['added']) ? 'Product added successfully!' : (isset($_GET['updated']) ? 'Product updated successfully!' : (isset($_GET['all_deleted']) ? 'All products deleted successfully!' : 'Product deleted successfully!'))); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div style="background: var(--white); padding: 2rem; border-radius: 10px; box-shadow: var(--shadow); margin-bottom: 2rem;">
            <h2 style="margin-bottom: 1rem; color: var(--dark-color);">
                <?php echo $edit_product ? 'Edit Product' : 'Add New Product'; ?>
            </h2>
            <?php if ($edit_product): ?>
                <a href="products.php" class="btn btn-secondary" style="margin-bottom: 1rem;">Cancel Edit</a>
            <?php endif; ?>
            <form method="POST" action="">
                <?php if ($edit_product): ?>
                    <input type="hidden" name="product_id" value="<?php echo $edit_product['id']; ?>">
                <?php endif; ?>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label>Product Name *</label>
                        <input type="text" name="name" value="<?php echo $edit_product ? htmlspecialchars($edit_product['name']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Category *</label>
                        <input type="text" name="category" value="<?php echo $edit_product ? htmlspecialchars($edit_product['category']) : ''; ?>" required placeholder="e.g., Pizza, Burger">
                    </div>
                </div>
                <div class="form-group">
                    <label>Description *</label>
                    <textarea name="description" required><?php echo $edit_product ? htmlspecialchars($edit_product['description']) : ''; ?></textarea>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label>Price *</label>
                        <input type="number" name="price" step="0.01" value="<?php echo $edit_product ? $edit_product['price'] : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Image URL</label>
                        <input type="text" name="image" value="<?php echo $edit_product ? htmlspecialchars($edit_product['image']) : ''; ?>" placeholder="https://...">
                        <?php if ($edit_product && $edit_product['image']): ?>
                            <img src="<?php echo htmlspecialchars($edit_product['image']); ?>" 
                                 alt="Current image" 
                                 style="width: 100px; height: 100px; object-fit: cover; margin-top: 0.5rem; border-radius: 5px;"
                                 onerror="this.style.display='none'">
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label>Status *</label>
                        <select name="status" required>
                            <option value="active" <?php echo $edit_product && $edit_product['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $edit_product && $edit_product['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                <?php if ($edit_product): ?>
                    <button type="submit" name="update_product" class="btn btn-primary">Update Product</button>
                <?php else: ?>
                    <button type="submit" name="add_product" class="btn btn-primary">Add Product</button>
                <?php endif; ?>
            </form>
        </div>
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h2 style="margin: 0; color: var(--dark-color);">All Products</h2>
            <a href="products.php?delete_all=confirm" 
               class="btn btn-danger" 
               style="padding: 0.5rem 1rem;"
               onclick="return confirm('⚠️ WARNING: This will delete ALL products and all related data (cart items, wishlist items, order items). This action cannot be undone!\n\nAre you absolutely sure you want to delete ALL products?')">
                Delete All Products
            </a>
        </div>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($product = $products->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $product['id']; ?></td>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td><?php echo htmlspecialchars($product['category']); ?></td>
                        <td>Rs <?php echo number_format($product['price'], 2); ?></td>
                        <td>
                            <span class="badge <?php echo $product['status'] === 'active' ? 'badge-success' : 'badge-danger'; ?>">
                                <?php echo ucfirst($product['status']); ?>
                            </span>
                        </td>
                        <td>
                            <a href="products.php?edit=<?php echo $product['id']; ?>" class="btn btn-primary" style="padding: 0.3rem 0.8rem; font-size: 0.9rem;">Edit</a>
                            <a href="products.php?delete=<?php echo $product['id']; ?>" 
                               class="btn btn-danger" 
                               style="padding: 0.3rem 0.8rem; font-size: 0.9rem;"
                               onclick="return confirm('Are you sure?')">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <footer>
        <p>&copy; 2024 ACE Hardware Store. All rights reserved.</p>
    </footer>
</body>
</html>
<?php $conn->close(); ?>

