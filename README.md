# 🥬 KimFresh: IoT-Driven Delivery Monitoring System

KimFresh is a complete end-to-end ecosystem designed for monitoring the temperature and humidity of perishable goods (Kimchi) during transit. It consists of a **Custom PHP MVC Backend**, a **Unified Flutter Mobile App (Driver & Retailer)**, and an **ESP32 IoT Sensor**.

---

## 📊 Before vs. After (Project Evolution)

### 🚨 BEFORE (Legacy State)
- **Architecture:** Flat PHP with 40+ loose `.php` files in the web root. Spaghetti code.
- **Security:** Zero CSRF protection. No central authentication guard. Brute-force attacks were unlimited.
- **Database:** Blocking queries. If MySQL crashed, the site hung for 120 seconds.
- **Mobile Apps:** Two separate apps with mismatched themes, hardcoded IP addresses, and a broken BLE implementation.
- **IoT/Inventory:** No concept of time or expiry. The ESP32 Bluetooth was not discoverable by Android.
- **Maintenance:** 20+ redundant `.bak` files cluttered the repository.

### ✅ AFTER (Current Refactored State)
- **Architecture:** Strict, hand-written MVC framework. The `public/` folder is the only web-accessible directory.
- **Security:** 
  - CSRF tokens on all POST forms.
  - Centralized route protection (Auth Guard).
  - Brute-force Rate Limiting (5 attempts / 15 mins).
  - Fail-Fast DB (3s timeout with clean 503 error).
- **Mobile App (Unified):** 
  - Single Flutter app with a beautiful **Role Selection Screen** (Driver or Retailer).
  - Unified design system (`AppTheme`) featuring the Rust Orange brand color.
  - Smart routing: Location/Bluetooth gates for Drivers, lightweight UI for Retailers.
- **IoT & Expiry Features:** 
  - Shelf-life tracking and auto-expire logic.
  - Store locks prevent admin edits while the store is open.
  - Live BLE Sensor: ESP32 broadcasts via NimBLE. The app scans, parses `T:30.7C H:72.3%`, and logs to the DB via an offline retry queue.
- **Maintenance:** Clean separation of concerns. All `.bak` files purged.

---

## 📱 Unified Mobile App Architecture

We successfully merged the previously separate `driver_app` and `retailer_app` into a single **`kimfresh_app`**.

### Key Mobile Features:
1. **Role Selection Landing Page:** Users choose "I am a Driver" or "I am a Retailer" upon opening the app.
2. **Shared Design System:** Both screens share colors, button styles, and card layouts.
3. **Dynamic Auth Routing:** 
   - Drivers are forced through `LocationGate` and `BluetoothGate` before seeing their delivery list.
   - Retailers bypass those gates and go straight to the shopping cart.
4. **Unified Signup:** Retailer Signup matches the premium look of Driver Signup, with OTP verification.
5. **IoT Sensor Integration:** Drivers scan for the ESP32 (`Kimchi_Monitor`), view live temp/humidity, and see alerts if the Kimchi gets too warm.

---

## 📁 The Project Tree

```text
kimfresh/
├── database/                 # SQL dumps (kimfresh.sql)
├── esp32/                    
│   └── kimchi_monitor/       # Arduino C++ code (NimBLE, Modbus RS485)
├── mobile-apps/              
│   └── kimfresh_app/         # Unified Flutter App (Driver + Retailer)
│       ├── lib/
│       │   ├── screens/      # UI (Role Selection, Deliveries, Products, etc.)
│       │   ├── services/     # API, BLE, Location, Foreground Services
│       │   └── theme/        # Unified AppTheme
│       └── android/          # Android Manifest & Permissions
├── php-web/                  # Custom PHP MVC Backend
│   ├── app/                  # Controllers, Models, Views, Helpers
│   ├── config/               # Database connection (Fail-fast logic)
│   ├── core/                 # Hand-written Micro-Framework (Base Controller, CSRF)
│   ├── public/               # The ONLY web-accessible folder
│   │   ├── api/              # Live API endpoints for mobile apps
│   │   ├── css/              # Stylesheets
│   │   └── index.php         # Front Controller / Router Entry Point
│   ├── lib/                  # Third-party (FPDF)
│   └── storage/              # Logs
└── README.md



💻 Tech Stack & Setup
Web / Backend
PHP 8.x (Custom MVC)

MySQL / MariaDB

XAMPP (Local development)

Setup: Place php-web inside C:\xampp\htdocs\. Import database/kimfresh.sql. 
Run Apache and MySQL via XAMPP.

Mobile App (Flutter)
Flutter 3.x

Dependencies: flutter_blue_plus, geolocator, flutter_foreground_task, permission_handler

Setup: Update the IP address in lib/services/api.dart to match your PC's local IPv4 address.

dart
const String API = 'http://192.168.0.xxx/php-web/public/api';
const String PHP_API = 'http://192.168.0.xxx/php-web/public';
IoT / Hardware
ESP32 (NimBLE, Modbus RTU RS485)

Sensor: RS485 Temperature & Humidity Probe.

Display: I2C LCD 16x2.

Setup: Upload esp32/kimchi_monitor/kimchi_monitor.ino using the Arduino IDE. 
Ensure the NimBLE-Arduino library is installed.

🔒 Security Highlights
CSRF Protection: All forms include rotating CSRF tokens.

Rate Limiting: Login attempts are tracked to prevent brute-force attacks.

Role Gating: A central Auth Guard ensures only logged-in users can access dashboards.

Secure BLE: Server-side validation blocks expired items and closed-store orders before they 
reach the database.