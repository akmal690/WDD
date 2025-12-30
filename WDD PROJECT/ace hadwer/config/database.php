<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ace_order_db');

// Create connection
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}

// Initialize database tables
function initializeDatabase() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
    
    // Create database if it doesn't exist
    $conn->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
    $conn->select_db(DB_NAME);
    
    // Check if products table exists before creating it (to detect fresh installation)
    $products_table_exists = $conn->query("SHOW TABLES LIKE 'products'")->num_rows > 0;
    $is_fresh_install = !$products_table_exists;
    
    // Users table (for customers only)
    $conn->query("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        phone VARCHAR(20),
        address TEXT,
        role ENUM('user') DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Admins table (separate table for admin users)
    $conn->query("CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        phone VARCHAR(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Products table
    $conn->query("CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        image VARCHAR(255),
        category VARCHAR(50),
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Cart table
    $conn->query("CREATE TABLE IF NOT EXISTS cart (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )");
    
    // Wishlist table
    $conn->query("CREATE TABLE IF NOT EXISTS wishlist (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        UNIQUE KEY unique_wishlist (user_id, product_id)
    )");
    
    // Orders table
    $conn->query("CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
        delivery_address TEXT,
        phone VARCHAR(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    
    // Order items table
    $conn->query("CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )");
    
    // Messages table
    $conn->query("CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        subject VARCHAR(200),
        message TEXT NOT NULL,
        status ENUM('unread', 'read') DEFAULT 'unread',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Insert default admin user (password: admin123)
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $conn->query("INSERT IGNORE INTO admins (username, email, password, full_name) 
                  VALUES ('admin', 'admin@foodorder.com', '$admin_password', 'Admin User')");
    
    // Insert sample products only on fresh installation (when products table doesn't exist yet)
    if ($is_fresh_install) {
        $sample_products = [
            ['Pizza Margherita', 'Classic Italian pizza with fresh mozzarella and basil', 12.99, 'pizza.jpg', 'Pizza'],
            ['Burger Deluxe', 'Juicy beef patty with fresh vegetables and special sauce', 9.99, 'burger.jpg', 'Burger'],
            ['Pasta Carbonara', 'Creamy pasta with bacon and parmesan cheese', 11.99, 'pasta.jpg', 'Pasta'],
            ['Sushi Platter', 'Fresh salmon and tuna sushi with wasabi and ginger', 18.99, 'sushi.jpg', 'Sushi'],
            ['Chicken Curry', 'Spicy chicken curry with basmati rice', 13.99, 'curry.jpg', 'Curry'],
            ['Caesar Salad', 'Fresh romaine lettuce with caesar dressing and croutons', 8.99, 'salad.jpg', 'Salad']
        ];
        
        foreach ($sample_products as $product) {
            $conn->query("INSERT INTO products (name, description, price, image, category) 
                          VALUES ('{$product[0]}', '{$product[1]}', {$product[2]}, '{$product[3]}', '{$product[4]}')");
        }
    }
    
    $conn->close();
}

// Initialize database on first run
initializeDatabase();
?>

