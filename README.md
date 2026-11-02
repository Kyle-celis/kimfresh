# KimFresh: IoT-Based Kimchi Management System

An integrated web and mobile-based system for managing kimchi distribution in Bacolod City.

## Components

| Component | Tech | Status |
| :--- | :--- | :--- |
| **Backend REST API** | **Core PHP (No Frameworks)** | ✅ |
| **Admin Web App** | **Core PHP + MySQL** | ✅ |
| **Retailer App** | Flutter + Dart | ✅ |
| **Driver App** | Flutter + Dart | ✅ |
| **IoT Sensor** | ESP32 + BLE | ✅ |

## Screenshots

### Admin Orders Dashboard with IoT Data
The admin dashboard shows temperature and humidity for each delivery. Rows turn red when temperature exceeds the threshold.

## Features

### Admin Web (Core PHP)
*   **No Frameworks:** Built using native PHP `mysqli` and a custom MVC architecture.
*   **Product Management:** Add, edit, hide, and restore products with stock validation.
*   **Order Management:** View, approve, reject, and bulk approve orders.
*   **Driver Management:** Add drivers, assign orders, and handle OTP account approvals.
*   **IoT Monitoring:** Live temperature and humidity tracking per delivery.
*   **Alert Detection:** Automated red-row highlighting for out-of-range readings.
*   **Store Settings:** Smart scheduling with force open/close manual overrides.
*   **Superadmin System:** Manage admin accounts and view detailed activity logs.
*   **PDF Exports:** Generate real FPDF reports for Orders, Products, and Drivers.

### Retailer App (Flutter)
*   **Strict Signup:** Split First/Last name and strict Philippine 11-digit phone validation.
*   **Secure Authentication:** JWT Authentication with 2FA OTP (One-Time Password) verification via Gmail SMTP.
*   **Account Verification:** 5-minute expiring OTP sent during registration to verify email addresses.
*   **Smart Ordering:** Browse products with real-time stock validation.
*   **GPS Capture:** Automatic location tagging when placing orders for accurate delivery routing.
*   **Order Tracking:** Dynamic timeline and status updates.
*   **Live Delivery Map:** Draggable bottom sheet with ETA, distance, driver details, and OpenStreetMap integration.
*   **Digital Receipts:** View detailed order history and receipts.

### Driver App (Flutter)
*   **Strict Signup:** Auto-formatting LTO license plate validation (e.g., N01-23-456789).
*   **Admin Gated:** Account remains locked until the Admin manually approves it via the web dashboard.
*   **OTP Verification:** Secure login using OTP verification for account approvals.
*   **Delivery Management:** View assigned deliveries and update status (Assigned → Picked Up → In Transit → Delivered).
*   **Live Navigation Map:** Route display to the retailer's GPS location.
*   **Background GPS Tracking:** Pings `save_location.php` every 10 seconds while in transit.
*   **IoT Integration:** Connect to the ESP32 via BLE to read real-time temperature and humidity.
*   **Offline Logging:** View and sync stored offline sensor readings.

### IoT Device (ESP32)
*   DHT22 temperature/humidity sensor.
*   BLE communication with the Driver App.
*   Offline data logging via LittleFS.
*   Command-based stored data retrieval.
*   LED status indicator.
*   Alert detection (temperature > 10°C).

## System Architecture

```text
ESP32 → BLE → Driver App → REST API (Core PHP) → MySQL → Admin Dashboard
Retailer App → REST API (Core PHP) → Admin Dashboard
Retailer App ⇄ PHP GPS Endpoints ⇄ Driver App (Live Map Tracking)



New Workflows
Account Creation: Retailer/Driver signs up → OTP generated → Email sent → Account verified → Auto-login (Retailer) / Admin Approval (Driver).

Order Flow: Retailer places order (GPS saved) → Admin approves → Driver auto-assigned → Driver picks up → Retailer watches truck move on the map → Delivered.

Data Gating: GPS locations are hidden from the driver until the admin approves the order.

Setup
Backend (Local Development)
bash
# Navigate to the web folder
cd php-web

# Start the PHP built-in server
php -S 0.0.0.0:8000
Database Import
bash
# Create a local database and import the master schema
mysql -u root -p -e "CREATE DATABASE kimfresh;"
mysql -u root -p kimfresh < database/kimfresh_master.sql
Environment Setup
Create a .env file in the php-web directory:

env
DB_HOST="localhost"
DB_NAME="kimfresh"
DB_USER="root"
DB_PASS="your_local_password"
Production (Hostinger VPS)
Web Server: Nginx with reverse proxy.

PHP: PHP 8.3 FPM.

Database: MySQL 8.0.

SSL: Let's Encrypt (Certbot).

Deployment: Automated via GitHub Actions (Pull & Reset).