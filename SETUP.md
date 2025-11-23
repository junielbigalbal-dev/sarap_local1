# Sarap Local - Setup and Testing Guide

## Quick Start (5 Minutes)

### 1. Database Setup
```sql
-- Open phpMyAdmin (http://localhost/phpmyadmin)
-- Run these commands:

-- Create database
CREATE DATABASE sarap_local;

-- Import schema
-- Click "Import" > Choose sql/schema.sql > Click "Go"

-- Import sample data
-- Click "Import" > Choose sql/seed.sql > Click "Go"
```

### 2. Environment Configuration
```bash
# Copy environment file
copy .env.example .env

# Edit .env (optional - defaults work for XAMPP)
# DB_HOST=localhost
# DB_NAME=sarap_local
# DB_USER=root
# DB_PASS=
```

### 3. Access the Application
```
Landing Page: http://localhost/sarap_local1/
Login Page: http://localhost/sarap_local1/pages/auth/login.php
```

## Demo Accounts

### Customer
- Email: `customer1@example.com`
- Password: `customer123`
- Features: Browse products, add to cart, place orders, message vendors

### Vendor
- Email: `vendor1@example.com`
- Password: `vendor123`
- Features: Manage products, view orders, sales analytics, customer messages

### Admin
- Email: `admin@saraplocal.com`
- Password: `admin123`
- Features: Manage users, approve products, platform analytics

## Testing Checklist

### ✅ Authentication
- [ ] Sign up as customer
- [ ] Sign up as vendor
- [ ] Login with demo accounts
- [ ] Logout

### ✅ Customer Flow
- [ ] View landing page
- [ ] Browse products
- [ ] View product details
- [ ] Add items to cart
- [ ] View cart
- [ ] Place order
- [ ] View order history

### ✅ Vendor Flow
- [ ] View dashboard
- [ ] Add new product
- [ ] Edit product
- [ ] View orders
- [ ] Accept/reject order
- [ ] View sales analytics

### ✅ Admin Flow
- [ ] View dashboard
- [ ] View all users
- [ ] View all products
- [ ] Approve pending products
- [ ] View platform analytics

## File Structure Created

```
✅ Config Files
- config/database.php
- config/config.php
- config/session.php
- .env.example

✅ Models
- models/User.php
- models/Product.php
- models/Order.php
- models/Message.php
- models/Notification.php
- models/Cart.php

✅ Auth Pages
- pages/auth/login.php
- pages/auth/signup.php
- pages/auth/verify-email.php
- pages/auth/logout.php

✅ Customer Pages
- pages/customer/dashboard.php

✅ Vendor Pages
- pages/vendor/dashboard.php

✅ Admin Pages
- pages/admin/dashboard.php

✅ Assets
- assets/css/styles.css
- assets/css/responsive.css

✅ Database
- sql/schema.sql (14 tables)
- sql/seed.sql (sample data)

✅ Core Files
- index.php (landing page)
- includes/functions.php
- README.md
```

## What's Working

✅ **Core Infrastructure**
- Database connection with PDO
- Session management
- CSRF protection
- Role-based access control

✅ **Authentication**
- Login/logout
- Signup with role selection
- Email verification system
- Password hashing

✅ **Landing Page**
- Responsive design
- Featured vendors
- Product showcase
- Foodie theme with logo colors

✅ **Dashboards**
- Customer dashboard with stats
- Vendor dashboard with sales data
- Admin dashboard with platform overview

## What Needs Implementation

The following features have database support and models but need UI pages:

### Customer Pages (Partial)
- Product browsing page
- Product details page
- Cart page (add/remove items)
- Checkout page
- Order history page
- Messages page
- Map view page
- Profile settings page

### Vendor Pages (Partial)
- Product management page (CRUD)
- Order management page (accept/reject)
- Analytics page (charts)
- Messages page
- Profile settings page

### Admin Pages (Partial)
- User management page
- Product approval page
- Analytics page
- Activity logs page

## Next Steps for Development

1. **Complete Customer Pages**
   - Build cart.php with add/remove functionality
   - Create checkout.php with order placement
   - Build products.php for browsing

2. **Complete Vendor Pages**
   - Build products.php for CRUD operations
   - Create orders.php for order management
   - Add analytics.php with charts

3. **Complete Admin Pages**
   - Build users.php for user management
   - Create products.php for approvals
   - Add ban/unban functionality

4. **Add JavaScript**
   - AJAX for cart operations
   - Real-time notifications
   - Form validation
   - Image upload preview

5. **Optional Enhancements**
   - Google Maps integration
   - Email SMTP configuration
   - Payment gateway
   - Real-time messaging

## Troubleshooting

### "Database connection failed"
```php
// Check config/database.php
// Verify MySQL is running in XAMPP
// Ensure database 'sarap_local' exists
```

### "Call to undefined function"
```php
// Make sure all require_once statements are present
// Check file paths are correct
```

### "Access denied"
```php
// Check role-based access in session.php
// Verify user is logged in with correct role
```

## Support

- Check README.md for detailed documentation
- Review code comments in PHP files
- Inspect database schema in sql/schema.sql
- Test with demo accounts first

---

**Built for Biliran Province, Philippines 🇵🇭**
**Supporting local food businesses with technology! 🍴**
