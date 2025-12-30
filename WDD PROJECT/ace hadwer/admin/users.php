<?php
require_once '../config/database.php';
require_once '../config/session.php';
requireAdmin();

$conn = getDBConnection();

// Get all users (customers)
$users = $conn->query("SELECT * FROM users ORDER BY created_at DESC");

// Get all admins
$admins = $conn->query("SELECT * FROM admins ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - Admin</title>
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
        <h1 style="text-align: center; margin-bottom: 2rem; color: var(--primary-color);">Manage Users</h1>
        
        <h2 style="margin: 2rem 0 1rem 0; color: var(--dark-color);">Customers</h2>
        <table style="margin-bottom: 3rem;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Joined</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($user = $users->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <h2 style="margin: 2rem 0 1rem 0; color: var(--dark-color);">Admin Users</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Joined</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($admin = $admins->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $admin['id']; ?></td>
                        <td><?php echo htmlspecialchars($admin['username']); ?></td>
                        <td><?php echo htmlspecialchars($admin['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($admin['email']); ?></td>
                        <td><?php echo htmlspecialchars($admin['phone'] ?? 'N/A'); ?></td>
                        <td><?php echo date('M d, Y', strtotime($admin['created_at'])); ?></td>
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

