# KimFresh: IoT-Based Kimchi Management System

An integrated web and mobile-based system for managing kimchi distribution in Bacolod City with IoT temperature and humidity monitoring.

## Components

| Component | Tech | Status |
|-----------|------|--------|
| Backend API | Flask + MySQL | ✅ |
| Admin Web | HTML + JS | ✅ |
| Retailer App | Flutter + Dart | ✅ |
| Driver App | Flutter + Dart | ✅ |
| IoT Sensor | ESP32 + DHT22 + BLE | ✅ |

## Features

- **Product management** — add, edit, hide, restore
- **Order management** — place, approve, reject
- **Driver management** — add, assign orders
- **Retailer sign up** — self-registration with unique customer code
- **Delivery tracking** — assigned → picked up → in transit → delivered
- **IoT monitoring** — real-time temperature/humidity via ESP32 + BLE
- **Offline storage** — ESP32 stores readings when phone disconnects
- **Alert detection** — red row when temperature/humidity out of range
- **Sensor data** — saved to database, shown in admin dashboard

## Architecture
