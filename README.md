# Garage Management System V3

Professional garage management solution with advanced tracking, reporting, and booking features. Built with PHP (PDO), MySQL, and Bootstrap 5.

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

## 📤 How to Upload to GitHub
1.  **Create Repository**: Create a new repository on GitHub.
2.  **Upload**: 
    - Use the **Docker-ready** version for best compatibility.
    - Drag and drop your project files (excluding the `.git` folder) into the GitHub upload window.
3.  **Commit**: Add a message like "Initial release V2" and commit your changes.

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
