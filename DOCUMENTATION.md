# KimFresh: Logic Problems and Solutions

**Developer:** Kyle Francis Celis
**Project:** KimFresh: IoT-Based Kimchi Management System
**Date:** September 14, 2026

---

## Problem 1: Login Bypass — Password Not Checked

| Field | Details |
|-------|---------|
| **Error** | Anyone could login with email only, no password required |
| **Cause** | Backend `login()` never compared the submitted password against `password_hash` |
| **Logic Fix** | Hash the submitted password with SHA-256 and compare to stored hash |
| **Code Change** | `password_hash = hashlib.sha256(password.encode()).hexdigest()` then `if stored_hash != password_hash: return 401` |
| **Result** | Password verification enforced for retailers and drivers |

---

## Problem 2: Order Status Not Syncing with Delivery Status

| Field | Details |
|-------|---------|
| **Error** | Order #1 remained `approved` even after driver marked delivery as `delivered` |
| **Cause** | Driver app updated `delivery.delivery_status` only; `order.order_status` was untouched |
| **Logic Fix** | Map delivery status to order status in the same endpoint |
| **Code Change** | `order_status_map = {'assigned':'approved', 'picked_up':'in_transit', 'in_transit':'in_transit', 'delivered':'delivered'}` then `UPDATE order SET order_status = ...` |
| **Result** | Both tables stay in sync |

---

## Problem 3: BLE Notifications Missed After Connection

| Field | Details |
|-------|---------|
| **Error** | Stored readings `S6:...` sent by ESP32 were never received by the Flutter app |
| **Cause** | ESP32 sent data immediately after connect; phone had not yet subscribed to notifications |
| **Logic Fix** | Instead of guessing a delay, let the phone request data when ready |
| **Code Change** | Added writable characteristic `5678`. Phone writes `REQUEST_STORED`; ESP32 responds |
| **Result** | 100% reliable delivery of stored readings |

---

## Problem 4: Sensor Data Never Saved to Database

| Field | Details |
|-------|---------|
| **Error** | `sensor_data` table was empty except manual test rows |
| **Cause** | Flutter received BLE data but never sent it to the backend |
| **Logic Fix** | Add `saveSensorData()` API call inside the BLE reading listener |
| **Code Change** | In `initBle()`, after `setState`, call `ApiService.saveSensorData(...)` for both live and stored readings |
| **Result** | Every reading saved automatically |

---

## Problem 5: No Active Delivery → Save Skipped

| Field | Details |
|-------|---------|
| **Error** | Flutter log showed `delivery=null` and `SKIP SAVE` |
| **Cause** | All deliveries for driver 2 had status `delivered`; `currentDeliveryId` remained `null` |
| **Logic Fix** | Pick the first delivery that is NOT `delivered` |
| **Code Change** | `data.firstWhere((d) => d['delivery_status'] != 'delivered', orElse: () => null)` |
| **Result** | Data saves as long as one delivery is active |

---

## Problem 6: Duplicate SKU Error on Add Product

| Field | Details |
|-------|---------|
| **Error** | `Duplicate entry 'KIM-001' for key 'product.sku'` |
| **Cause** | SKU column is UNIQUE; adding same SKU again failed |
| **Logic Fix** | Check if product exists first; update it instead of inserting |
| **Code Change** | `SELECT * FROM product WHERE sku = %s`; if found, `UPDATE`; else `INSERT` |
| **Result** | Add form now updates existing products |

---

## Problem 7: Hardcoded Database Password in Public Repo

| Field | Details |
|-------|---------|
| **Error** | `password="12345"` visible on GitHub |
| **Cause** | Credentials hardcoded in `db_config.py` |
| **Logic Fix** | Move secrets to `.env` and load via `python-dotenv` |
| **Code Change** | `load_dotenv(); password=os.getenv('DB_PASSWORD')` |
| **Result** | Password not committed; `.env` in `.gitignore` |

---

## Problem 8: Hardcoded Absolute Path Broke on Other Machines

| Field | Details |
|-------|---------|
| **Error** | `/home/kyle/kimfresh/web-app` only worked on developer's laptop |
| **Cause** | Absolute path was specific to one machine |
| **Logic Fix** | Build path relative to the script's own location |
| **Code Change** | `WEB_DIR = os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', 'web-app')` |
| **Result** | Works on any machine |

---

## Problem 9: API URL Hardcoded in Web Pages

| Field | Details |
|-------|---------|
| **Error** | `http://192.168.1.125:5000` broke if IP changed |
| **Cause** | IP was hardcoded in every HTML file |
| **Logic Fix** | Auto-detect hostname from browser |
| **Code Change** | ``const API = `http://${window.location.hostname}:5000` `` |
| **Result** | Works from any device on the network |

---

## Problem 10: Null Stock Quantity on Product Add

| Field | Details |
|-------|---------|
| **Error** | `reorder_level` and `stock_quantity` stored as NULL when blank |
| **Cause** | Frontend sent empty string; backend stored NULL |
| **Logic Fix** | Default to 5 if blank |
| **Code Change** | `stock = data.get('reorder_level') if data.get('reorder_level') not in [None, ''] else 5` |
| **Result** | No more NULL values |

---

## Problem 11: Rollback Failed Order

| Field | Details |
|-------|---------|
| **Error** | Partial data could be inserted if step 2 failed after step 1 |
| **Cause** | No transaction management |
| **Logic Fix** | Use `try/except` with `conn.commit()` and `conn.rollback()` |
| **Code Change** | Wrap all inserts in `try`; on error call `conn.rollback()` |
| **Result** | Database stays consistent |

---

## Problem 12: Delivery Creation on Assign Failed

| Field | Details |
|-------|---------|
| **Error** | Order could not be assigned more than once |
| **Cause** | No check for existing delivery for same order |
| **Logic Fix** | Check `SELECT delivery_id FROM delivery WHERE order_id = %s` first |
| **Code Change** | `if existing: return "already assigned"` |
| **Result** | Strict one-driver-per-order rule enforced |

---

## Problem 13: Database Filling Up Over Time

| Field | Details |
|-------|---------|
| **Error** | Sensor data grows at ~105 MB per driver per year |
| **Cause** | Every 20-second reading saved forever |
| **Logic Fix** | Delete readings older than 30 days |
| **Code Change** | `DELETE FROM sensor_data WHERE reading_timestamp < DATE_SUB(NOW(), INTERVAL 30 DAY)` |
| **Result** | Database size capped |

---

## Summary

| # | Problem | Solution |
|---|---------|----------|
| 1 | Login bypass | SHA-256 password check |
| 2 | Status not syncing | Map delivery → order status |
| 3 | BLE data missed | Command-based retrieval |
| 4 | Data not saved | Auto-save from Flutter |
| 5 | No active delivery | Pick non-delivered delivery |
| 6 | Duplicate SKU | Update existing product |
| 7 | Hardcoded password | `.env` file |
| 8 | Hardcoded path | Relative path |
| 9 | Hardcoded IP | Auto-detect hostname |
| 10 | Null stock | Default value |
| 11 | Partial order | Rollback on error |
| 12 | Duplicate assignment | Existence check |
| 13 | Database growth | 30-day cleanup |

---

## Repository

https://github.com/Kyle-celis/kimfresh
