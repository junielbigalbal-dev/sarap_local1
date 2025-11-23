# Quick Database Setup Guide

## The Error
You're seeing this error because the database tables don't exist yet:
```
Table 'sarap_local.user_profiles' doesn't exist
```

## Fix This in 2 Minutes

### Step 1: Open phpMyAdmin
1. Go to: **http://localhost/phpmyadmin**
2. Click on "SQL" tab at the top

### Step 2: Import Schema
1. Open the file: `c:\xampp\htdocs\sarap_local1\sql\schema.sql`
2. Copy ALL the content
3. Paste it into the SQL tab in phpMyAdmin
4. Click "Go" button

### Step 3: Import Sample Data (Optional but Recommended)
1. Open the file: `c:\xampp\htdocs\sarap_local1\sql\seed.sql`
2. Copy ALL the content
3. Paste it into the SQL tab in phpMyAdmin
4. Click "Go" button

### Step 4: Refresh Your Page
1. Go to: **http://localhost/sarap_local1/**
2. Press **Ctrl + F5** to hard refresh

## Alternative: Quick Command Line Method

If you prefer command line:

```bash
# Navigate to XAMPP mysql bin
cd C:\xampp\mysql\bin

# Import schema
mysql -u root -p sarap_local < C:\xampp\htdocs\sarap_local1\sql\schema.sql

# Import sample data
mysql -u root -p sarap_local < C:\xampp\htdocs\sarap_local1\sql\seed.sql
```

(Press Enter when asked for password - default XAMPP has no password)

## What This Does

The schema.sql creates **14 tables**:
- users
- user_profiles
- products
- product_media
- orders
- order_items
- carts
- messages
- notifications
- posts
- email_verifications
- admin_logs
- reviews

The seed.sql adds **sample data**:
- 1 admin account
- 2 customers
- 3 vendors (Pedro's Lechon, Ana's Sweet Treats, Jose's Fresh Catch)
- 11 products
- Sample orders and messages

## After Setup

You can login with these demo accounts:
- **Customer**: customer1@example.com / customer123
- **Vendor**: vendor1@example.com / vendor123
- **Admin**: admin@saraplocal.com / admin123

---

**That's it! Your Foodpanda-style landing page will work perfectly after this! 🚀**
