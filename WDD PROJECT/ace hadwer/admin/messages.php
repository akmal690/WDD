<?php
require_once '../config/database.php';
require_once '../config/session.php';
requireAdmin();

$conn = getDBConnection();

// Handle mark as read
if (isset($_GET['mark_read'])) {
    $id = intval($_GET['mark_read']);
    $conn->query("UPDATE messages SET status = 'read' WHERE id = $id");
    header('Location: messages.php');
    exit();
}

// Handle delete message
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM messages WHERE id = $id");
    header('Location: messages.php?deleted=1');
    exit();
}

// Get all messages
$messages = $conn->query("SELECT * FROM messages ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - ACE Hardware Store Admin</title>
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
        .message-card {
            background: var(--white);
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: var(--shadow);
            margin-bottom: 1rem;
        }
        .message-card.unread {
            border-left: 4px solid var(--primary-color);
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
        <h1 style="text-align: center; margin-bottom: 2rem; color: var(--primary-color);">Customer Messages</h1>
        
        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success">Message deleted successfully!</div>
        <?php endif; ?>
        
        <?php while ($message = $messages->fetch_assoc()): ?>
            <div class="message-card <?php echo $message['status'] === 'unread' ? 'unread' : ''; ?>">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                    <div>
                        <h3 style="color: var(--dark-color); margin-bottom: 0.5rem;">
                            <?php echo htmlspecialchars($message['name']); ?>
                        </h3>
                        <p style="color: #7f8c8d; font-size: 0.9rem;">
                            <?php echo htmlspecialchars($message['email']); ?>
                        </p>
                    </div>
                    <div style="text-align: right;">
                        <span style="font-size: 0.9rem; color: #95a5a6;">
                            <?php echo date('M d, Y H:i', strtotime($message['created_at'])); ?>
                        </span>
                        <?php if ($message['status'] === 'unread'): ?>
                            <span class="badge badge-warning" style="display: block; margin-top: 0.5rem;">New</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($message['subject']): ?>
                    <h4 style="color: var(--primary-color); margin-bottom: 0.5rem;">
                        <?php echo htmlspecialchars($message['subject']); ?>
                    </h4>
                <?php endif; ?>
                <p style="line-height: 1.6; color: var(--dark-color);">
                    <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                </p>
                <div style="margin-top: 1rem; display: flex; gap: 0.5rem;">
                    <?php if ($message['status'] === 'unread'): ?>
                        <a href="messages.php?mark_read=<?php echo $message['id']; ?>" class="btn btn-success" style="padding: 0.3rem 0.8rem; font-size: 0.9rem;">Mark as Read</a>
                    <?php endif; ?>
                    <a href="messages.php?delete=<?php echo $message['id']; ?>" 
                       class="btn btn-danger" 
                       style="padding: 0.3rem 0.8rem; font-size: 0.9rem;"
                       onclick="return confirm('Are you sure?')">Delete</a>
                </div>
            </div>
        <?php endwhile; ?>
    </div>

    <footer>
        <p>&copy; 2024 ACE Hardware Store. All rights reserved.</p>
    </footer>
</body>
</html>
<?php $conn->close(); ?>

