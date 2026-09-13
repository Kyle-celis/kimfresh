# KimFresh: IoT-Based Kimchi Management System

An integrated web and mobile-based system for managing kimchi distribution in Bacolod City.

## Components

| Component | Tech | Status |
|-----------|------|--------|
| Backend API | Flask + MySQL | ✅ |
| Admin Web | HTML + JS | ✅ |
| Retailer App | Flutter + Dart | ✅ |
| Driver App | Flutter + Dart | ✅ |
| IoT Sensor | ESP32 + BLE | ✅ |

## Screenshots

### Admin Orders Dashboard with IoT Data

![Admin Orders](screenshots/orders.png)

The admin dashboard shows temperature and humidity for each delivery. Rows turn red when temperature exceeds the threshold.

## Features

### Admin Web
- Product management (add, edit, hide, restore)
- Order management (view, approve, reject)
- Driver management (add, assign orders)
- Temperature and humidity monitoring per delivery
- Alert detection (red row for out-of-range readings)

### Retailer App
- Sign up with auto-generated customer code
- Browse products
- Place orders
- Track order status

### Driver App
- View assigned deliveries
- Update delivery status (Assigned → Picked Up → In Transit → Delivered)
- Connect to ESP32 via BLE
- View real-time temperature and humidity
- View stored offline readings
- Auto-save sensor data to backend

### IoT Device (ESP32)
- DHT22 temperature/humidity sensor
- BLE communication to driver app
- Offline data logging (LittleFS)
- Command-based stored data retrieval
- LED status indicator
- Alert detection (temperature > 10°C)

## System Architecture

ESP32 → BLE → Driver App → REST API → MySQL → Admin Dashboard

## Setup

### Backend

```bash
cd backend
python3 -m venv venv
source venv/bin/activate
pip install flask flask-cors mysql-connector-python
python app.py
