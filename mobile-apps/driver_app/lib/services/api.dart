import 'dart:async';
import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;

// LOCAL XAMPP URLS
const String API = 'http://10.0.2.2/php-web/api';
const String PHP_API = 'http://10.0.2.2/php-web';

class ApiService {
  static const Duration _timeout = Duration(seconds: 15);
  static String? _token;

  static void setToken(String? token) => _token = token;
  static void clearToken() => _token = null;

  static Map<String, String> _authHeaders() {
    return {
      'Content-Type': 'application/json',
      if (_token != null) 'Authorization': 'Bearer $_token',
    };
  }

  static Map<String, dynamic> _safeJson(http.Response res) {
    final body = res.body.trimLeft();
    if (body.isEmpty) {
      return {
        'success': false,
        'message': 'Empty response (HTTP ${res.statusCode})',
      };
    }
    if (!body.startsWith('{') && !body.startsWith('[')) {
      debugPrint('NON-JSON (HTTP ${res.statusCode}): ${res.body}');
      return {
        'success': false,
        'message': 'Server error (HTTP ${res.statusCode})',
      };
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

  // ---------- VERIFY OTP ----------
  static Future<Map<String, dynamic>> verifyOTP(
    int driverId,
    String otp,
  ) async {
    try {
      final res = await http
          .post(
            Uri.parse(
              '$API/verify_otp.php',
            ), // Ensure this file exists in /api/
            headers: {'Content-Type': 'application/json'},
            body: jsonEncode({'driver_id': driverId, 'otp': otp}),
          )
          .timeout(_timeout);
      return _safeJson(res);
    } catch (e) {
      return {'success': false, 'message': 'Error: $e'};
    }
  }

  // ---------- LOGIN ----------
  static Future<Map<String, dynamic>> login(
    String email,
    String password, {
    String? role,
  }) async {
    try {
      final res = await http
          .post(
            Uri.parse('$API/login.php'),
            headers: {'Content-Type': 'application/json'},
            body: jsonEncode({
              'email': email,
              'password': password,
              if (role != null) 'role': role,
            }),
          )
          .timeout(_timeout);
      final decoded = _safeJson(res);
      if (res.statusCode == 200 &&
          decoded['success'] == true &&
          decoded['token'] != null) {
        _token = decoded['token'].toString();
      }
      return decoded;
    } catch (e) {
      return {'success': false, 'message': 'Error: $e'};
    }
  }

  // ---------- DRIVER SIGNUP ----------
  static Future<Map<String, dynamic>> register(
    String name,
    String email,
    String password,
    String phone,
    String license,
  ) async {
    try {
      final res = await http
          .post(
            Uri.parse('$API/register.php'), // Ensure this file exists in /api/
            headers: {'Content-Type': 'application/json'},
            body: jsonEncode({
              'name': name,
              'email': email,
              'password': password,
              'phone': phone,
              'license_number': license,
            }),
          )
          .timeout(_timeout);
      return _safeJson(res);
    } catch (e) {
      return {'success': false, 'message': 'Error: $e'};
    }
  }

  // ---------- GET DRIVER DELIVERIES ----------
  static Future<List<dynamic>> getDeliveries(int driverId) async {
    try {
      final res = await http
          .get(
            Uri.parse('$API/driver.php?driver_id=$driverId'),
            headers: _authHeaders(),
          )
          .timeout(_timeout);

      debugPrint("🔍 DELIVERIES STATUS: ${res.statusCode}");
      debugPrint("🔍 DELIVERIES BODY: ${res.body}");

      if (res.statusCode != 200) return [];

      final decoded = jsonDecode(res.body);

      // If the PHP returns a List, return it
      if (decoded is List) {
        return decoded;
      }
      // If the PHP accidentally returns an object (e.g., {"success": true, "data": []}),
      // try to extract the data list.
      else if (decoded is Map && decoded['data'] is List) {
        return decoded['data'];
      }

      return [];
    } catch (e) {
      debugPrint("🔍 DELIVERIES ERROR: $e");
      return [];
    }
  }

  // ---------- UPDATE DELIVERY STATUS ----------
  static Future<Map<String, dynamic>> updateDeliveryStatus(
    int deliveryId,
    String status,
  ) async {
    try {
      final res = await http
          .put(
            Uri.parse(
              '$API/update_delivery_status.php?delivery_id=$deliveryId',
            ),
            headers: _authHeaders(),
            body: jsonEncode({'delivery_status': status}),
          )
          .timeout(_timeout);
      return _safeJson(res);
    } catch (e) {
      return {'success': false, 'message': 'Error: $e'};
    }
  }

  // ---------- SAVE SENSOR DATA ----------
  static Future<bool> saveSensorData(
    int deliveryId,
    double temp,
    double hum,
    bool alert,
  ) async {
    try {
      final res = await http
          .post(
            Uri.parse('$API/sensor.php'),
            headers: _authHeaders(),
            body: jsonEncode({
              'delivery_id': deliveryId,
              'temperature': temp,
              'humidity': hum,
              'is_alert': alert,
            }),
          )
          .timeout(_timeout);
      return res.statusCode >= 200 && res.statusCode < 300;
    } catch (e) {
      debugPrint('saveSensorData failed: $e');
      return false;
    }
  }

  // ---------- GPS: SAVE DRIVER LOCATION ----------
  static Future<bool> saveDriverLocation(
    int orderId,
    double lat,
    double lng,
  ) async {
    try {
      final res = await http
          .post(
            Uri.parse('$PHP_API/save_location.php'),
            headers: {'Content-Type': 'application/json'},
            body: jsonEncode({'order_id': orderId, 'lat': lat, 'lng': lng}),
          )
          .timeout(_timeout);
      if (res.statusCode < 200 || res.statusCode >= 300) return false;
      final decoded = _safeJson(res);
      return decoded['success'] == true;
    } catch (e) {
      debugPrint('saveDriverLocation failed: $e');
      return false;
    }
  }

  // ---------- GET SENSOR HISTORY ----------
  static Future<List<dynamic>> getSensorHistory(int driverId) async {
    try {
      final res = await http
          .get(
            Uri.parse(
              '$API/sensor_history.php?driver_id=$driverId',
            ), // Ensure this file exists in /api/
            headers: _authHeaders(),
          )
          .timeout(_timeout);
      return _safeJsonList(res);
    } catch (e) {
      return [];
    }
  }

  // ---------- GET DRIVER PROFILE ----------
  static Future<Map<String, dynamic>> getDriverProfile(int driverId) async {
    try {
      final res = await http
          .get(
            Uri.parse('$API/driver_profile.php?driver_id=$driverId'),
            headers: _authHeaders(),
          )
          .timeout(_timeout);
      return _safeJson(res);
    } catch (e) {
      return {'success': false, 'message': 'Error: $e'};
    }
  }

  // ---------- UPDATE DRIVER PROFILE ----------
  static Future<Map<String, dynamic>> updateDriverProfile(
    int driverId,
    String name,
    String phone,
    String license,
  ) async {
    try {
      final res = await http
          .put(
            Uri.parse('$API/driver_profile.php?driver_id=$driverId'),
            headers: _authHeaders(),
            body: jsonEncode({
              'name': name,
              'phone': phone,
              'license_number': license,
            }),
          )
          .timeout(_timeout);
      return _safeJson(res);
    } catch (e) {
      return {'success': false, 'message': 'Error: $e'};
    }
  }

  // ---------- FORGOT PASSWORD ----------
  static Future<Map<String, dynamic>> forgotPassword(String email) async {
    try {
      final res = await http
          .post(
            Uri.parse(
              '$API/forgot_password.php',
            ), // Ensure this file exists in /api/
            headers: {'Content-Type': 'application/json'},
            body: jsonEncode({'email': email}),
          )
          .timeout(_timeout);
      return _safeJson(res);
    } catch (e) {
      return {'success': false, 'message': 'Network error: $e'};
    }
  }

  // ---------- RESET PASSWORD ----------
  static Future<Map<String, dynamic>> resetPassword(
    String email,
    String otp,
    String newPassword,
  ) async {
    try {
      final res = await http
          .post(
            Uri.parse(
              '$API/reset_password.php',
            ), // Ensure this file exists in /api/
            headers: {'Content-Type': 'application/json'},
            body: jsonEncode({
              'email': email,
              'otp': otp,
              'new_password': newPassword,
            }),
          )
          .timeout(_timeout);
      return _safeJson(res);
    } catch (e) {
      return {'success': false, 'message': 'Network error: $e'};
    }
  }

  // ---------- GPS: GET DRIVER + RETAILER LOCATION ----------
  static Future<Map<String, dynamic>?> getDriverLocation(int orderId) async {
    try {
      final res = await http
          .get(
            Uri.parse('$PHP_API/track.php?order_id=$orderId'),
            headers: _authHeaders(),
          )
          .timeout(_timeout);

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
