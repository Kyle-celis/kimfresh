# KimFresh: IoT-Based Kimchi Management System

An integrated web and mobile-based system for managing kimchi distribution in Bacolod City.

## Components

| Component | Tech | Status |
|-----------|------|--------|
| Backend API | Flask + MySQL | ✅ |
| Admin Web | HTML + JS | ✅ |
| Retailer App | Flutter + Dart | ✅ |
| Driver App | Flutter + Dart | 🚧 |
| IoT Sensor | ESP32 + BLE | 🚧 |

## Features

- Product management (add, edit, hide, restore)
- Order management (place, approve, reject)
- Driver management (add, assign orders)
- Retailer mobile app (browse, order, track)
- IoT temperature/humidity monitoring (planned)

## Setup

### Backend

```bash
cd backend
python3 -m venv venv
source venv/bin/activate
pip install flask flask-cors mysql-connector-python
python app.py
