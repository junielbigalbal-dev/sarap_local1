# Deployment Guide for Shared Hosting (InfinityFree, Hostinger, etc.)

This guide explains how to deploy Sarap Local to a shared hosting environment.

## 1. Prepare Your Files
1.  **Config Check**: Ensure `config/config.php` is using the dynamic `SITE_URL` setting (it should be by default).
2.  **Database Config**: Open `config/database.php`. It uses environment variables, but for shared hosting, you might need to hardcode credentials if you can't set environment variables easily.
    *   *Alternative*: Create a `.env` file on your server (if supported) or edit `config/database.php` directly on the server after uploading.

## 2. Upload Files
1.  **FTP Client**: Use FileZilla or WinSCP.
2.  **Connect**: Enter your hosting FTP credentials (Host, Username, Password, Port 21).
3.  **Target Directory**: Navigate to `htdocs` or `public_html` on the server.
4.  **Upload**: Drag and drop all files from your local `sarap_local1` folder to the server folder.
    *   **Exclude**: `.git`, `.github`, `node_modules` (if any).

## 3. Setup Database
1.  **Create Database**: Go to your hosting control panel (cPanel/VistaPanel) -> **MySQL Databases**.
2.  **Create New DB**: Name it (e.g., `epiz_12345_sarap_local`).
3.  **Import Schema**:
    *   Open **phpMyAdmin** from the control panel.
    *   Select your new database.
    *   Click **Import**.
    *   Choose `sql/schema.sql` from your local computer.
    *   Click **Go**.
    *   (Optional) Import `sql/seed.sql` for sample data.

## 4. Configure Connection
1.  **Edit Database Config**:
    *   On the server (via File Manager or before uploading), edit `config/database.php`.
    *   Update the credentials to match your hosting database:
    ```php
    // Example for shared hosting
    define('DB_HOST', 'sql123.infinityfree.com'); // Check your hosting details
    define('DB_NAME', 'epiz_12345_sarap_local');
    define('DB_USER', 'epiz_12345');
    define('DB_PASS', 'your_password');
    ```

## 5. Verify Dynamic URL
The application is designed to automatically detect your domain.
- **Localhost**: `http://localhost/sarap_local1`
- **Live**: `http://your-domain.com`

You do **NOT** need to manually change `SITE_URL` in `config.php` unless you have a specific reason.

## 6. Troubleshooting
- **Redirects to Localhost?**: Clear your browser cache. The old redirect might be cached.
- **404 Not Found?**: Ensure `.htaccess` is uploaded.
- **Database Error?**: Double-check `DB_HOST`, `DB_USER`, and `DB_PASS`. Shared hosting often uses a specific hostname (not localhost).

## 7. Email Verification (SMTP)
Shared hosting often blocks SMTP ports. You may need to:
1.  Use your hosting's email service.
2.  Use a third-party SMTP (SendGrid, Brevo) on port 587 or 2525.
3.  Update `SMTP_HOST`, `SMTP_USER`, `SMTP_PASS` in `config/config.php` or `.env`.
