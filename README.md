# Garage Management System V3

Professional garage management solution with advanced tracking, reporting, and booking features. Built with PHP (PDO), MySQL, and Bootstrap 5.

---

## ✨ Version 3.3 Major Update (Current)
- **Service Catalog & Offers**: A new "Services" module has been introduced where admins can define general services (e.g., Oil Change, Wheel Balancing) alongside customizable Promotional Offers (Percentage or Fixed Discounts) that apply automatically on checkout.
- **Promotional Email Broadcasting**: Added a mass-email capability allowing admins to broadcast newly created service offers simultaneously to all registered garage customers with a single click.
- **Job Card Photos**: Technicians can now securely upload unlimited diagnostic or repair photos directly into an active Job Card.
- **Vehicle Photo History**: Introduced a dynamic gallery inside Job Cards that automatically pulls and displays all historical photos linked to any past jobs for the same vehicle, providing an instant visual diagnostic timeline.
- **Security Lockdowns**: Hardened role-based access controls by fully restricting the Bookings module strictly to Administrators, ensuring Technicians focus solely on active jobs.

---

## ✨ Version 3.2 Major Update


## ✨ Version 3.1 Major Update (Current)

- **Social Integration**: LinkedIn and GitHub icons added to the Dashboard and Login screens for a professional presence.
- **Persistent Data Storage**: Docker configuration now includes persistent volumes for `assets/uploads`, ensuring logos and photos survive container updates.
- **Dynamic Footer**: Global footer with automatic copyright year updating and professional branding.
- **Smart Logo Handling**: Advanced path detection to ensure company logos display correctly across all server environments (Linux/Windows).
- **Integrity Fixes**: Resolved critical database constraints in Inventory and Bookings modules for improved system stability.

---

## ✨ Version 3.0 Highlights
- **Branded Login Screen**: Dynamic company logo and name on the sign-in page.
- **Password Reset Integration**: Direct link to the reset utility from the login screen.
- **Security Clean-up**: Default credentials removed from the UI for a professional look.
- **Technician Profile Photos**: Admins can manage profile photos for technicians (V2).
- **Enhanced Date & Time**: Precision tracking for Jobs, Invoices, and Bookings (V2).
- **Docker Ready**: One-click deployment for any environment.

---

## 🚀 Deployment Guide

### Default Credentials
For the first-time login, use the following credentials:
- **Email**: `admin@example.com`
- **Password**: `admin123`

### Method A: Docker Installation (Recommended)
This is the fastest "one-click" method and is ideal for developers and production environments.

1.  **Download & Extract**:
    - **From GitHub**: Download the ZIP or run `git clone [repository-url]`.
    - **Extraction**: Extract the contents to a folder on your computer.
2.  **Prerequisites**: Ensure you have **Docker** and **Docker Compose** installed.
3.  **Launch the System**:
    - Open a terminal in the project root folder.
    - **Windows**: Run `docker-compose up -d`
    - **Linux**: Run `sudo docker-compose up -d`
4.  **Wait for Initialization**: 
    - Docker will automatically install all PHP extensions and import the database schema (`database.sql`).
    - **Linux Note**: If you have permission issues with uploads, run: `sudo chmod -R 777 assets/uploads`.
5.  **Access & Login**:
    - URL: `http://localhost:8080` (or `http://YOUR_SERVER_IP:8080` for VMs).
    - Email: `admin@example.com`
    - Password: `admin123`

---

### Method B: Manual Installation (XAMPP / WAMP / IIS)
1.  **Copy Files**: Place the project folder in your web server's root (e.g., `C:\xampp\htdocs\garage_sys`).
2.  **Database Setup**:
    - Create a database named `garage_sys` in phpMyAdmin.
    - Import `full_database_v2_export.sql` (found in the root).
3.  **Configure**:
    - Open `config/db.php` and update the database credentials if they differ from XAMPP defaults.
4.  **Access**:
    - URL: `http://localhost/garage_sys`
    - Email: `admin@example.com` | Password: `admin123`

---

### Automated Reports Setup (Cron Job)
To enable the **Automated Daily and Monthly Reports**, you must configure your server's scheduler to execute the reporting script (`includes/cron_reports.php`) regularly.

**On Linux Servers (Recommended Method):**
1. Open your terminal and type `crontab -e`.
2. Add the following rule to execute the script at the top of every hour (adjust `/var/www/html` to your exact directory path):
   ```bash
   0 * * * * php /var/www/html/garage-system-v2/includes/cron_reports.php >> /var/log/garage_cron.log 2>&1
   ```
   *The script will automatically check the database for your preferred "Daily Reporting Time" and only send the email when the hour matches.*

**On Windows Servers (XAMPP):**
1. Open **Task Scheduler** from the start menu.
2. Click **Create Basic Task** and name it "Garage Reports".
3. Set the trigger to **Daily** and select the exact time you configured in the application's Company Profile.
4. Set the Action to **Start a program**.
5. Program/script: Browse to your PHP executable (e.g., `C:\xampp\php\php.exe`).
6. Add arguments: `C:\xampp\htdocs\garage-system-v2\includes\cron_reports.php`.

---

## 🛠️ Features & Security
- **Global Search**: Find anything via the top navbar.
- **Booking & Calendar**: Manage appointments with an interactive dashboard.
- **Inventory & Billing**: Automatic stock deduction and professional invoicing.
- **Security**: Bcrypt password hashing, PDO prepared statements, and role-based access.

---

## 📁 Folder Structure
- `config/`: Database connection.
- `includes/`: Common layout parts.
- `modules/`: Feature-specific code.
- `assets/`: Styling, JS, and profile photo uploads.
