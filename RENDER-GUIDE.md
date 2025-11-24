# Deployment Guide for Render

Since you are using Render, deployment is automatic via GitHub!

## 1. Clean Up
I have removed the "FTP Deploy" action because Render pulls your code directly. You don't need GitHub Actions to push files.

## 2. Render Configuration
1.  Go to your **Render Dashboard**.
2.  Click **New +** -> **Web Service**.
3.  Connect your GitHub repository (`sarap_local1`).
4.  **Settings**:
    *   **Runtime**: Docker
    *   **Region**: Singapore (or closest to you)
    *   **Branch**: main
5.  **Environment Variables** (Add these in Render):
    *   `SITE_URL`: `https://your-app-name.onrender.com`
    *   `DB_HOST`: (Your database host)
    *   `DB_NAME`: (Your database name)
    *   `DB_USER`: (Your database user)
    *   `DB_PASS`: (Your database password)
    *   `SMTP_HOST`: `smtp.gmail.com` (if using Gmail)
    *   `SMTP_USER`: (Your email)
    *   `SMTP_PASS`: (Your app password)

## 3. Database
Render does **not** provide a free MySQL database permanently (they have a PostgreSQL free tier, or paid MySQL).
*   **Option A**: Use an external free MySQL host (like Aiven, TiDB, or InfinityFree's DB if allowed).
*   **Option B**: Use Render's PostgreSQL (requires code changes).
*   **Option C**: Pay for Render's MySQL.

## 4. Deploy
Once you connect the repo, Render will build the Docker image automatically.
- **Build Command**: (Auto-detected from Dockerfile)
- **Start Command**: (Auto-detected from Dockerfile)

Your site will be live at `https://your-app-name.onrender.com`.
