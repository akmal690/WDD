<?php
require_once '../config/database.php';
require_once '../config/session.php';
requireAdmin();

$conn = getDBConnection();

// Get statistics
$pending_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'")->fetch_assoc()['count'];
$completed_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'completed'")->fetch_assoc()['count'];
$total_orders = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
$normal_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$admin_users = $conn->query("SELECT COUNT(*) as count FROM admins")->fetch_assoc()['count'];
$new_messages = $conn->query("SELECT COUNT(*) as count FROM messages WHERE status = 'unread'")->fetch_assoc()['count'];

// Get recent orders
$recent_orders = $conn->query("SELECT o.*, u.username, u.full_name 
                               FROM orders o 
                               JOIN users u ON o.user_id = u.id 
                               ORDER BY o.created_at DESC 
                               LIMIT 10");

// Get recent messages
$recent_messages = $conn->query("SELECT * FROM messages 
                                 ORDER BY created_at DESC 
                                 LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Food Order</title>
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
        <h1 style="text-align: center; margin-bottom: 2rem; color: var(--primary-color);">Admin Dashboard</h1>
        
        <div class="dashboard">
            <div class="dashboard-card">
                <h3>Pending Orders</h3>
                <div class="number"><?php echo $pending_orders; ?></div>
                <div class="label">Orders awaiting processing</div>
            </div>
            
            <div class="dashboard-card">
                <h3>Completed Orders</h3>
                <div class="number"><?php echo $completed_orders; ?></div>
                <div class="label">Successfully delivered</div>
            </div>
            
            <div class="dashboard-card">
                <h3>Total Orders</h3>
                <div class="number"><?php echo $total_orders; ?></div>
                <div class="label">All time orders</div>
            </div>
            
            <div class="dashboard-card">
                <h3>Normal Users</h3>
                <div class="number"><?php echo $normal_users; ?></div>
                <div class="label">Registered customers</div>
            </div>
            
            <div class="dashboard-card">
                <h3>Admin Users</h3>
                <div class="number"><?php echo $admin_users; ?></div>
                <div class="label">Administrators</div>
            </div>
            
            <div class="dashboard-card">
                <h3>New Messages</h3>
                <div class="number"><?php echo $new_messages; ?></div>
                <div class="label">Unread messages</div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; margin-top: 3rem;">
            <div>
                <h2 style="margin-bottom: 1rem; color: var(--dark-color);">Recent Orders</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($order = $recent_orders->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td><?php echo htmlspecialchars($order['full_name']); ?></td>
                                <td>Rs <?php echo number_format($order['total_amount'], 2); ?></td>
                                <td>
                                    <span class="badge <?php 
                                        echo $order['status'] === 'completed' ? 'badge-success' : 
                                            ($order['status'] === 'pending' ? 'badge-warning' : 'badge-danger'); 
                                    ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <a href="orders.php" class="btn btn-primary" style="margin-top: 1rem;">View All Orders</a>
            </div>
            
            <div>
                <h2 style="margin-bottom: 1rem; color: var(--dark-color);">Recent Messages</h2>
                <div style="background: var(--white); border-radius: 10px; box-shadow: var(--shadow); padding: 1rem;">
                    <?php while ($message = $recent_messages->fetch_assoc()): ?>
                        <div style="padding: 1rem; border-bottom: 1px solid #ddd;">
                            <h4 style="color: var(--dark-color); margin-bottom: 0.5rem;">
                                <?php echo htmlspecialchars($message['name']); ?>
                            </h4>
                            <p style="font-size: 0.9rem; color: #7f8c8d; margin-bottom: 0.5rem;">
                                <?php echo htmlspecialchars(substr($message['message'], 0, 50)); ?>...
                            </p>
                            <span style="font-size: 0.8rem; color: #95a5a6;">
                                <?php echo date('M d, Y', strtotime($message['created_at'])); ?>
                            </span>
                            <?php if ($message['status'] === 'unread'): ?>
                                <span class="badge badge-warning" style="margin-left: 0.5rem;">New</span>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                    <a href="messages.php" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">View All Messages</a>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; 2024 ACE Hardware Store. All rights reserved.</p>
    </footer>
</body>
</html>
<?php $conn->close(); ?>

