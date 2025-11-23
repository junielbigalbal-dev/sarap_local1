# Admin Dashboard Setup Instructions

## Quick Start

### Step 1: Run Database Migrations

Before using the admin dashboard, you need to create the required database tables.

**Option A: Using phpMyAdmin (Recommended)**
1. Open phpMyAdmin in your browser: `http://localhost/phpmyadmin`
2. Select your database (likely `sarap_local`)
3. Click on the **Import** tab
4. Click **Choose File** and select: `c:\xampp\htdocs\sarap_local1\sql\admin_migrations.sql`
5. Click **Go** at the bottom
6. Wait for the success message

**Option B: Using MySQL Command Line**
```bash
mysql -u root -p sarap_local < c:\xampp\htdocs\sarap_local1\sql\admin_migrations.sql
```

### Step 2: Access the Admin Dashboard

Once migrations are complete, navigate to:
```
http://localhost/sarap_local1/pages/admin/dashboard.php
```

> **Note:** Make sure you're logged in as an admin user. The system uses `requireRole('admin')` to protect all admin pages.

---

## What the Migrations Create

The migration file creates the following tables:

- **delivery_riders** - For managing delivery personnel
- **support_tickets** - Customer support system
- **ticket_messages** - Support ticket conversations
- **promotions** - Discount codes and campaigns
- **promo_usage** - Track promotion usage
- **banners** - Marketing banners
- **cms_content** - Announcements, blogs, notifications
- **admin_roles** - Role-based permissions
- **login_logs** - Security monitoring
- **vendor_payouts** - Payment tracking

It also adds these columns to the existing `orders` table:
- `rider_id` - Link to delivery rider
- `promo_id` - Link to promotion used
- `discount_amount` - Discount applied

---

## Troubleshooting

### Error: "Table doesn't exist"
**Solution:** Run the database migrations (Step 1 above)

### Error: "Access denied" or "Not authorized"
**Solution:** Make sure you're logged in as an admin user. Check your `users` table and ensure your account has `role = 'admin'`

### Error: "Column not found"
**Solution:** Your database schema might be outdated. Run the migrations to add missing columns.

---

## Admin Features Available

Once set up, you'll have access to:

✅ **Dashboard Overview** - Statistics, charts, recent activity  
✅ **Vendor Management** - Approve/reject/suspend vendors  
✅ **Customer Management** - View customer profiles and history  
✅ **Order Management** - Track orders, assign riders  
✅ **Analytics & Reports** - Sales trends, top performers  
✅ **Promotions** - Create and manage discount codes  
✅ **Support Tickets** - Customer support system  
✅ **Content Management** - Announcements, blogs  
✅ **Payments** - Vendor payouts and commissions  
✅ **Security Logs** - Monitor login activity  

---

## Need Help?

If you encounter any issues:
1. Check that XAMPP/MySQL is running
2. Verify your database connection in `config/database.php`
3. Ensure migrations ran successfully (check for error messages)
4. Verify you're logged in as an admin user
