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
*   **No Frameworks:** Built using native PHP `mysqli` and custom MVC architecture.
*   Product management (add, edit, hide, restore).
*   Order management (view, approve, reject, bulk approve).
*   Driver management (add, assign orders, OTP approval).
*   Temperature and humidity monitoring per delivery.
*   Alert detection (red row for out-of-range readings).
*   **Store Settings:** Force Open/Close override and schedule management.
*   **Superadmin System:** Manage other admins and view activity logs.
*   **PDF Exports:** Generate real FPDF reports for Orders, Products, and Drivers.

### Retailer App (Flutter)
*   **Strict Signup:** Split First/Last name and strict Philippine 11-digit phone validation.
*   **Secure Login:** JWT Authentication with OTP (One-Time Password) verification via Gmail.
*   **Forgot Password:** Secure password reset using a 5-minute expiring OTP.
*   Browse products with real-time stock validation.
*   Place orders with automatic GPS location capture for delivery.
*   Track order status via a dynamic timeline.
*   **Live Delivery Map:** Draggable bottom sheet with ETA, distance calculation, driver details, and OpenStreetMap integration.
*   View order history and digital receipts.

### Driver App (Flutter)
*   **Strict Signup:** Auto-formatting LTO license plate validation (e.g., N01-23-456789).
*   **Secure Login:** OTP verification flow for account approvals.
*   View assigned deliveries.
*   Update delivery status (Assigned → Picked Up → In Transit → Delivered).
*   **Live Navigation Map:** Shows the route to the retailer's GPS location.
*   **Background GPS Tracking:** Pings `save_location.php` every 10 seconds while in transit.
*   Connect to ESP32 via BLE.
*   View real-time temperature and humidity.
*   View stored offline readings.
*   Auto-save sensor data to backend.

### IoT Device (ESP32)
*   DHT22 temperature/humidity sensor
*   BLE communication to driver app
*   Offline data logging (LittleFS)
*   Command-based stored data retrieval
*   LED status indicator
*   Alert detection (temperature > 10°C)

## System Architecture

```text
ESP32 → BLE → Driver App → REST API (Core PHP) → MySQL → Admin Dashboard
Retailer App → REST API (Core PHP) → Admin Dashboard
Retailer App ⇄ PHP GPS Endpoints ⇄ Driver App (Live Map Tracking)
