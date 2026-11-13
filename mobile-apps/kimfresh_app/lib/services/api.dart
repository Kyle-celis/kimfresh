import 'dart:async';
import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;

const String API = 'http://192.168.0.117/php-web/public/api';
const String PHP_API = 'http://192.168.0.117/php-web/public';

class ApiService {
  static const Duration _timeout = Duration(seconds: 15);
  static String? _token;

  static void setToken(String? token) => _token = token;
  static void clearToken() => _token = null;

  static Map<String, String> _authHeaders({bool json = false}) {
    return {
      'Accept': 'application/json',
      if (json) 'Content-Type': 'application/json',
      if (_token != null) 'Authorization': 'Bearer $_token',
    };
  }

  static Map<String, dynamic> _safeJson(http.Response res) {
    final body = res.body.trimLeft();
    if (body.isEmpty) {
      return {'success': false, 'message': 'Empty response (HTTP ${res.statusCode})'};
    }
    if (!body.startsWith('{') && !body.startsWith('[')) {
      debugPrint('NON-JSON (HTTP ${res.statusCode}): ${res.body}');
      return {'success': false, 'message': 'Server error (HTTP ${res.statusCode})'};
    }
    try {
      final decoded = jsonDecode(res.body);
      if (decoded is Map<String, dynamic>) return decoded;
      if (decoded is List) return {'success': true, 'data': decoded};
      return {'success': false, 'message': 'Unexpected response'};
    } catch (e) {
      return {'success': false, 'message': 'Bad JSON: $e'};
    }
  }

  static List<dynamic> _safeJsonList(http.Response res) {
    if (res.statusCode != 200) return [];
    final body = res.body.trimLeft();
    if (!body.startsWith('[')) return [];
    try {
      return jsonDecode(res.body) as List;
    } catch (e) {
      return [];
    }
  }

  // ---------- LOGIN (Handles both Driver & Retailer) ----------
  static Future<Map<String, dynamic>> login(String email, String password, {String? role}) async {
    try {
      final res = await http.post(
        Uri.parse('$API/login.php'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'email': email, 'password': password, if (role != null) 'role': role}),
      ).timeout(_timeout);

      final decoded = _safeJson(res);
      if (res.statusCode == 200 && decoded['success'] == true && decoded['token'] != null) {
        _token = decoded['token'].toString();
      }
      return decoded;
    } catch (e) {
      return {'success': false, 'message': 'Error: $e'};
    }
  }

  // ---------- DRIVER & RETAILER SIGNUP (Combined into one safe method) ----------
  static Future<Map<String, dynamic>> register(
    String name,
    String email,
    String password,
    String phone, {
    String? license,
    String? address,
  }) async {
    try {
      final res = await http.post(
        Uri.parse('$API/register.php'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'name': name,
          'email': email,
          'password': password,
          'phone': phone,
          if (license != null) 'license_number': license,
          if (address != null) 'address': address,
        }),
      ).timeout(_timeout);
      return _safeJson(res);
    } catch (e) {
      return {'success': false, 'message': 'Error: $e'};
    }
  }

  // ---------- OTP VERIFICATION ----------
  static Future<Map<String, dynamic>> verifyOTP(int driverId, String otp) async {
    try {
      final res = await http.post(
        Uri.parse('$API/verify_otp.php'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'driver_id': driverId, 'otp': otp}),
      ).timeout(_timeout);
      return _safeJson(res);
    } catch (e) {
      return {'success': false, 'message': 'Error: $e'};
    }
  }

  static Future<Map<String, dynamic>> verifyRetailerOTP(int retailerId, String otp) async {
    try {
      final res = await http.post(
        Uri.parse('$API/retailer/verify-otp'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'retailer_id': retailerId, 'otp': otp}),
      ).timeout(_timeout);
      return _safeJson(res);
    } catch (e) {
      return {'success': false, 'message': 'Error: $e'};
    }
  }

  // ==========================================
  // DRIVER METHODS
  // ==========================================
  static Future<List<dynamic>> getDeliveries(int driverId) async {
    try {
      final res = await http.get(Uri.parse('$API/driver.php?driver_id=$driverId'), headers: _authHeaders()).timeout(_timeout);
      if (res.statusCode != 200) return [];
      final decoded = jsonDecode(res.body);
      if (decoded is List) return decoded;
      if (decoded is Map && decoded['data'] is List) return decoded['data'];
      return [];
    } catch (e) {
      return [];
    }
  }

  static Future<Map<String, dynamic>> updateDriverStatus(int driverId, String status) async {
    try {
      final res = await http.post(
        Uri.parse('$API/driver_status.php'),
        headers: _authHeaders(json: true),
        body: jsonEncode({'driver_id': driverId, 'status': status}),
      ).timeout(_timeout);
      return _safeJson(res);
    } catch (e) {
      return {'success': false, 'message': 'Network error: $e'};
    }
  }

  static Future<Map<String, dynamic>> updateDeliveryStatus(int deliveryId, String status) async {
    try {
      final res = await http.put(
        Uri.parse('$API/update_delivery_status.php?delivery_id=$deliveryId'),
        headers: _authHeaders(json: true),
        body: jsonEncode({'delivery_status': status}),
      ).timeout(_timeout);
      return _safeJson(res);
    } catch (e) {
      return {'success': false, 'message': 'Error: $e'};
    }
  }

  static Future<bool> saveSensorData(int deliveryId, double temp, double hum, bool alert) async {
    try {
      final res = await http.post(
        Uri.parse('$API/sensor.php'),
        headers: _authHeaders(json: true),
        body: jsonEncode({'delivery_id': deliveryId, 'temperature': temp, 'humidity': hum, 'is_alert': alert}),
      ).timeout(_timeout);
      return res.statusCode >= 200 && res.statusCode < 300;
    } catch (e) {
      return false;
    }
  }

  static Future<bool> saveDriverLocation(int orderId, double lat, double lng) async {
    try {
      final res = await http.post(
        Uri.parse('$PHP_API/save_driver_location.php'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'order_id': orderId, 'lat': lat, 'lng': lng}),
      ).timeout(_timeout);
      if (res.statusCode < 200 || res.statusCode >= 300) return false;
      final decoded = _safeJson(res);
      return decoded['success'] == true;
    } catch (e) {
      return false;
    }
  }

  static Future<List<dynamic>> getSensorHistory(int driverId) async {
    try {
      final res = await http.get(Uri.parse('$API/sensor_history.php?driver_id=$driverId'), headers: _authHeaders()).timeout(_timeout);
      return _safeJsonList(res);
    } catch (e) {
      return [];
    }
  }

  static Future<Map<String, dynamic>> getDriverProfile(int driverId) async {
    try {
      final res = await http.get(Uri.parse('$API/driver_profile.php?driver_id=$driverId'), headers: _authHeaders()).timeout(_timeout);
      return _safeJson(res);
    } catch (e) {
      return {'success': false, 'message': 'Error: $e'};
    }
  }

  static Future<Map<String, dynamic>> updateDriverProfile(int driverId, String name, String phone, String license) async {
    try {
      final res = await http.put(
        Uri.parse('$API/driver_profile.php?driver_id=$driverId'),
        headers: _authHeaders(json: true),
        body: jsonEncode({'name': name, 'phone': phone, 'license_number': license}),
      ).timeout(_timeout);
      return _safeJson(res);
    } catch (e) {
      return {'success': false, 'message': 'Error: $e'};
    }
  }

  // ==========================================
  // RETAILER METHODS
  // ==========================================
  static Future<List<dynamic>> getProducts() async {
    try {
      final res = await http.get(Uri.parse('$API/api_products.php'), headers: _authHeaders()).timeout(_timeout);
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        return data is List ? data : (data['products'] ?? []);
      }
      return [];
    } catch (e) {
      return [];
    }
  }

  static Future<Map<String, dynamic>> placeOrder(int retailerId, List<Map<String, dynamic>> items, {double? lat, double? lng}) async {
    try {
      final res = await http.post(
        Uri.parse('$API/api_orders.php'),
        headers: _authHeaders(json: true),
        body: jsonEncode({'retailer_id': retailerId, 'items': items, if (lat != null) 'retailer_lat': lat, if (lng != null) 'retailer_lng': lng}),
      ).timeout(_timeout);
      return _safeJson(res);
    } catch (e) {
      return {'success': false, 'message': 'Error: $e'};
    }
  }

  static Future<List<dynamic>> getOrders(int retailerId) async {
    try {
      final res = await http.get(Uri.parse('$API/view_order.php?retailer_id=$retailerId'), headers: _authHeaders()).timeout(_timeout);
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        return data is List ? data : (data['orders'] ?? []);
      }
      return [];
    } catch (e) {
      return [];
    }
  }

  static Future<Map<String, dynamic>> getStoreStatus() async {
    try {
      final res = await http.get(Uri.parse('$API/api_products.php?action=store_status'), headers: _authHeaders()).timeout(_timeout);
      return res.statusCode == 200 ? _safeJson(res) : {'is_open': true, 'message': ''};
    } catch (e) {
      return {'is_open': true, 'message': ''};
    }
  }

  static Future<Map<String, dynamic>> getRetailerProfile(int retailerId) async {
    try {
      final res = await http.get(Uri.parse('$API/profile.php?id=$retailerId'), headers: _authHeaders()).timeout(_timeout);
      return res.statusCode == 200 ? _safeJson(res) : {'success': false};
    } catch (e) {
      return {'success': false, 'message': 'Error: $e'};
    }
  }

  static Future<Map<String, dynamic>> updateRetailerProfile(int retailerId, String name, String phone, String address) async {
    try {
      final res = await http.put(
        Uri.parse('$API/profile.php?id=$retailerId'),
        headers: _authHeaders(json: true),
        body: jsonEncode({'name': name, 'phone': phone, 'address': address}),
      ).timeout(_timeout);
      return _safeJson(res);
    } catch (e) {
      return {'success': false, 'message': 'Error: $e'};
    }
  }

  static Future<bool> saveRetailerGPS(int retailerId, double lat, double lng) async {
    try {
      final res = await http.post(
        Uri.parse('$API/update_retailer_location.php'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'retailer_id': retailerId, 'lat': lat, 'lng': lng}),
      ).timeout(_timeout);
      final data = _safeJson(res);
      return data['success'] == true;
    } catch (e) {
      return false;
    }
  }

  static Future<Map<String, dynamic>> cancelOrder(int orderId) async {
    try {
      final res = await http.post(
        Uri.parse('$API/cancel_order.php'),
        headers: _authHeaders(json: true),
        body: jsonEncode({'order_id': orderId}),
      ).timeout(_timeout);
      return _safeJson(res);
    } catch (e) {
      return {'success': false, 'message': 'Error: $e'};
    }
  }

  // ==========================================
  // SHARED METHODS (Forgot Password / Tracking)
  // ==========================================
  static Future<Map<String, dynamic>> forgotPassword(String email) async {
    try {
      final res = await http.post(
        Uri.parse('$API/forgot_password.php'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'email': email}),
      ).timeout(_timeout);
      return _safeJson(res);
    } catch (e) {
      return {'success': false, 'message': 'Network error: $e'};
    }
  }

  static Future<Map<String, dynamic>> resetPassword(String email, String otp, String newPassword) async {
    try {
      final res = await http.post(
        Uri.parse('$API/reset_password.php'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'email': email, 'otp': otp, 'new_password': newPassword}),
      ).timeout(_timeout);
      return _safeJson(res);
    } catch (e) {
      return {'success': false, 'message': 'Network error: $e'};
    }
  }

  static Future<Map<String, dynamic>?> getDriverLocation(int orderId) async {
    try {
      final res = await http.get(Uri.parse('$PHP_API/track?order_id=$orderId'), headers: _authHeaders()).timeout(_timeout);
      if (res.statusCode != 200) return null;
      final decoded = _safeJson(res);
      if (decoded['success'] == true) {
        double? toD(dynamic v) => (v is num) ? v.toDouble() : null;
        return {
          'driver_lat': toD(decoded['driver_lat']),
          'driver_lng': toD(decoded['driver_lng']),
          'retailer_lat': toD(decoded['retailer_lat']),
          'retailer_lng': toD(decoded['retailer_lng']),
          'order_status': decoded['order_status'],
        };
      }
      return null;
    } catch (e) {
      return null;
    }
  }
}