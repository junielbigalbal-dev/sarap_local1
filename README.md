# Sarap Local - Local Food Marketplace

A complete PHP/MySQL web application for connecting local food vendors with customers in Biliran Province, Philippines.

## Features

### 🎨 Landing Page
- Foodie-themed responsive design with logo color scheme
- Featured local vendors showcase
- Popular dishes/products grid
- "How It Works" section
- Mobile-friendly navigation

### 🔐 Authentication
- Secure login with password hashing (bcrypt)
- Email verification system
- Role-based signup (Customer/Vendor)
- CSRF protection on all forms
- Session management with timeout

### 👤 Customer Features
- Browse products and vendors
- Shopping cart system
- Order placement and tracking
- Messaging with vendors
- Notifications for order updates
- Map view of nearby vendors (Biliran Province)
- Profile management

### 💼 Vendor Features
- Product management (CRUD operations)
- Order management (accept/reject/complete)
- Sales analytics dashboard
- Customer messaging
- Notification system with badges
- Business profile and location settings

### 🛡️ Admin Features
- Secret admin portal access
- User management (ban/unban)
- Product approval system
- Platform analytics
- Activity logs

## Installation Instructions

### Prerequisites
- XAMPP (Apache + MySQL + PHP 8.0+)
- Web browser (Chrome, Firefox, Edge)

### Step 1: Install XAMPP
1. Download XAMPP from https://www.apachefriends.org/
2. Install XAMPP to `C:\xampp`
3. Start Apache and MySQL from XAMPP Control Panel

### Step 2: Setup Database
1. Open phpMyAdmin: http://localhost/phpmyadmin
2. Import the database schema:
   - Click "Import" tab
   - Choose file: `sql/schema.sql`
   - Click "Go"
3. Import sample data (optional):
   - Click "Import" tab again
   - Choose file: `sql/seed.sql`
   - Click "Go"

### Step 3: Configure Environment
1. Copy `.env.example` to `.env`:
   ```bash
   copy .env.example .env
   ```
2. Edit `.env` file with your settings:
   ```
   DB_HOST=localhost
   DB_NAME=sarap_local
   DB_USER=root
   DB_PASS=
   ```

### Step 4: Create Upload Directories
Create these folders if they don't exist:
```
uploads/products/
uploads/profiles/
uploads/reels/
```

### Step 5: Access the Application
1. Open your browser
2. Go to: http://localhost/sarap_local1/
3. You should see the landing page!

## Demo Accounts

After importing `seed.sql`, you can login with:

**Customer Account:**
- Email: customer1@example.com
- Password: customer123

**Vendor Account:**
- Email: vendor1@example.com
- Password: vendor123

**Admin Account:**
- Email: admin@saraplocal.com
- Password: admin123

## Project Structure

```
sarap_local1/
├── config/              # Configuration files
│   ├── database.php     # Database connection
│   ├── config.php       # App settings
│   └── session.php      # Session management
├── models/              # Database models
│   ├── User.php
│   ├── Product.php
│   ├── Order.php
│   ├── Message.php
│   ├── Notification.php
│   └── Cart.php
├── controllers/         # Business logic (to be added)
├── pages/               # Application pages
│   ├── auth/           # Login, signup, verify
│   ├── customer/       # Customer dashboard & features
│   ├── vendor/         # Vendor dashboard & features
│   └── admin/          # Admin panel
├── includes/            # Shared components
│   ├── functions.php   # Helper functions
│   ├── header.php      # Header component
│   └── footer.php      # Footer component
├── assets/              # Static files
│   ├── css/            # Stylesheets
│   ├── js/             # JavaScript files
│   └── images/         # Images
├── uploads/             # User-uploaded files
│   ├── products/       # Product images
│   ├── profiles/       # Profile avatars
│   └── reels/          # Video content
├── sql/                 # Database files
│   ├── schema.sql      # Database structure
│   └── seed.sql        # Sample data
├── index.php            # Landing page
└── README.md            # This file
```

## Database Schema

The application uses 14 tables:
- `users` - User accounts
- `user_profiles` - User profile information
- `products` - Product listings
- `product_media` - Product images/videos
- `orders` - Customer orders
- `order_items` - Order line items
- `carts` - Shopping cart items
- `messages` - User messaging
- `notifications` - User notifications
- `posts` - News feed posts
- `email_verifications` - Email verification tokens
- `admin_logs` - Admin activity logs
- `reviews` - Product reviews

## Configuration

### Email Setup (Optional)
To enable email verification:
1. Edit `.env` file
2. Add your SMTP credentials:
   ```
   SMTP_HOST=smtp.gmail.com
   SMTP_PORT=587
   SMTP_USER=your-email@gmail.com
   SMTP_PASS=your-app-password
   ```

### Google Maps API (Optional)
To enable map features:
1. Get API key from https://console.cloud.google.com/
2. Add to `.env`:
   ```
   GOOGLE_MAPS_API_KEY=your-api-key-here
   ```

## Security Features

- ✅ Password hashing with bcrypt
- ✅ CSRF token protection
- ✅ SQL injection prevention (PDO prepared statements)
- ✅ XSS filtering (htmlspecialchars)
- ✅ Session timeout
- ✅ Role-based access control
- ✅ Email verification
- ✅ Secure file uploads

## Technology Stack

- **Backend:** PHP 8.0+
- **Database:** MySQL 5.7+
- **Frontend:** HTML5, CSS3, JavaScript
- **Server:** Apache (via XAMPP)
- **Architecture:** MVC pattern

## Troubleshooting

### Database Connection Error
- Check if MySQL is running in XAMPP
- Verify database credentials in `.env`
- Ensure `sarap_local` database exists

### Page Not Found (404)
- Check if Apache is running
- Verify the URL: http://localhost/sarap_local1/
- Check `.htaccess` file exists
- **Deployed?** See `DEPLOYMENT-GUIDE.md` for configuration details.

### Email Not Sending
- Email verification requires SMTP configuration
- For development, check database `email_verifications` table for tokens
- Manually verify by running: `UPDATE users SET email_verified = TRUE WHERE email = 'your@email.com'`

### Upload Errors
- Ensure upload directories exist and are writable
- Check PHP upload limits in `php.ini`

## Development Roadmap

### Phase 1 (Current)
- ✅ Core infrastructure
- ✅ Authentication system
- ✅ Landing page
- ✅ Database schema
- ✅ Basic models

### Phase 2 (To Do)
- ⏳ Customer dashboard pages
- ⏳ Vendor dashboard pages
- ⏳ Admin panel pages
- ⏳ Shopping cart functionality
- ⏳ Order processing

### Phase 3 (Future)
- ⏳ Real-time messaging
- ⏳ Map integration
- ⏳ Payment gateway
- ⏳ Mobile app
- ⏳ Push notifications

## Support

For issues or questions:
1. Check this README first
2. Review the code comments
3. Check database structure in `sql/schema.sql`

## License

This project is for educational and local business support purposes.

## Credits

Built with ❤️ for the local food community of Biliran Province, Philippines 🇵🇭
