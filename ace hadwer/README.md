## Installation

1. **Requirements**
   - XAMPP (or any PHP server with MySQL)
   - PHP 7.4 or higher
   - MySQL 5.7 or higher

2. **Setup Steps**
   - Place all files in your XAMPP `htdocs` folder
   - Start Apache and MySQL from XAMPP Control Panel
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - The database will be created automatically on first page load

3. **Default Admin Credentials**
   - Username: `admin`
   - Password: `admin123`
   - Email: `admin@foodorder.com`

4. **Access the Website**
   - User Site: http://localhost/New folder/
   - Admin Login: http://localhost/New folder/admin/login.php

## Database Structure

The system automatically creates the following tables:
- `users` - User accounts (customers and admins)
- `products` - Food items/menu
- `cart` - Shopping cart items
- `wishlist` - User wishlist items
- `orders` - Order records
- `order_items` - Individual items in each order
- `messages` - Contact form messages

## File Structure

```
New folder/
├── config/
│   ├── database.php      # Database configuration
│   └── session.php       # Session management
├── admin/
│   ├── login.php         # Admin login
│   ├── dashboard.php     # Admin dashboard
│   ├── products.php      # Product management
│   ├── orders.php        # Order management
│   ├── users.php         # User management
│   ├── messages.php      # Message management
│   └── profile.php       # Admin profile
├── assets/
│   ├── css/
│   │   └── style.css     # Main stylesheet
│   └── js/
│       └── main.js       # JavaScript functions
├── index.php             # Home page
├── login.php             # User login/register
├── menu.php              # Menu page
├── cart.php              # Shopping cart
├── wishlist.php          # Wishlist
├── orders.php            # User orders
├── about.php             # About page
├── contact.php           # Contact page
└── logout.php            # Logout handler
```

## Usage

### For Users
1. Register a new account or login
2. Browse the menu and add items to cart
3. Add items to wishlist for later
4. Proceed to checkout from cart
5. View order history

### For Admins
1. Login with admin credentials
2. View dashboard for statistics
3. Add/edit products from Products page
4. Manage orders and update statuses
5. View users and messages
6. Update profile information

## Customization

- **Images**: Replace image URLs in the database or update the product image field
- **Colors**: Modify CSS variables in `assets/css/style.css`
- **Categories**: Add new categories when creating products

## Security Notes

- Change default admin password after first login
- Use strong passwords for production
- Consider adding CSRF protection for production use
- Validate and sanitize all user inputs

## Support

For issues or questions, please contact the development team.

---

**Note**: This is a development version. For production use, implement additional security measures and optimize the code.


