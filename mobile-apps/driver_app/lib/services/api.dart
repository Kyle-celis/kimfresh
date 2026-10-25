import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;

const String API = 'http://192.168.0.117:5000';       // Python Flask
const String PHP_API = 'http://192.168.0.117:8000';   // PHP Server


class ApiService {
  static const Duration _timeout = Duration(seconds: 15);
  static String? _token;

  static void setToken(String? token) {
    _token = token;
  }

  static void clearToken() {
    _token = null;
  }

  static Map<String, String> _authHeaders() {
    return {
      'Content-Type': 'application/json',
      if (_token != null) 'Authorization': 'Bearer $_token',
    };
  }

  /// Safely parse a response body as JSON. Returns a friendly error
  /// map instead of throwing when the server sends HTML (301/404/etc.).
  static Map<String, dynamic> _safeJson(http.Response res) {
    final body = res.body.trimLeft();
    if (body.isEmpty) {
      return {
        'success': false,
        'message': 'Empty response from server (HTTP ${res.statusCode}).',
      };
    }
    if (!body.startsWith('{') && !body.startsWith('[')) {
      // Dump a snippet for debugging
      final snippet =
      res.body.length > 300 ? '${res.body.substring(0, 300)}…' : res.body;
      debugPrint(
        'NON-JSON response (HTTP ${res.statusCode}): $snippet',
      );
      return {
        'success': false,
        'message': res.statusCode == 301 || res.statusCode == 302
            ? 'Server redirected the request. Use https:// for the API URL.'
            : 'Server returned non-JSON (HTTP ${res.statusCode}). '
            'Check API path or server config.',
      };
    }
    try {
      final decoded = jsonDecode(res.body);
      if (decoded is Map<String, dynamic>) return decoded;
      if (decoded is List) return {'success': true, 'data': decoded};
      return {'success': false, 'message': 'Unexpected response shape'};
    } catch (e) {
      return {'success': false, 'message': 'Bad JSON from server: $e'};
    }
  }

  /// Safely parse a response body as a JSON list.
  static List<dynamic> _safeJsonList(http.Response res) {
    if (res.statusCode != 200) return [];
    final body = res.body.trimLeft();
    if (!body.startsWith('[')) {
      debugPrint(
        'Expected JSON array (HTTP ${res.statusCode}), got: '
            '${res.body.length > 200 ? '${res.body.substring(0, 200)}…' : res.body}',
      );
      return [];
    }
    try {
      final decoded = jsonDecode(res.body);
      return decoded is List ? decoded : [];
    } catch (e) {
      debugPrint('Bad JSON list: $e');
      return [];
    }
  }

  static Future<Map<String, dynamic>> verifyOTP(int driverId, String otp) async {
    try {
      final res = await http.post(
        Uri.parse('$API/api/driver/verify-otp'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'driver_id': driverId, 'otp': otp}),
      ).timeout(_timeout);

      // Ensure you have a _safeJson helper, or use jsonDecode directly:
      final decoded = jsonDecode(res.body);
      if (decoded is Map<String, dynamic>) return decoded;
      return {'success': false, 'message': 'Unexpected response format'};

    } catch (e) {
      return {'success': false, 'message': 'Error: $e'};
    }
  }

  // ---------- LOGIN ----------
  static Future<Map<String, dynamic>> login(
      String email, String password) async {
    try {
      final res = await http.post(
        Uri.parse('$API/api/login'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'email': email, 'password': password}),
      ).timeout(_timeout);

      debugPrint(
          'LOGIN status=${res.statusCode} ctype=${res.headers['content-type']}');

      final decoded = _safeJson(res);
      if (res.statusCode == 200 &&
          decoded['success'] == true &&
          decoded['token'] != null) {
        _token = decoded['token'].toString();
      }
      return decoded;
    } on SocketException {
      return {'success': false, 'message': 'No internet connection'};
    } on TimeoutException {
      return {'success': false, 'message': 'Server timeout'};
    } catch (e) {
      return {'success': false, 'message': 'Error: $e'};
    }
  }

  // ---------- DRIVER SIGNUP ----------
  static Future<Map<String, dynamic>> register(String name, String email,
      String password, String phone, String license) async {
    try {
      final res = await http.post(
        Uri.parse('$API/api/driver/register'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'name': name,
          'email': email,
          'password': password,
          'phone': phone,
          'license_number': license,
        }),
      ).timeout(_timeout);

      return _safeJson(res);
    } on SocketException {
      return {'success': false, 'message': 'No internet connection'};
    } on TimeoutException {
      return {'success': false, 'message': 'Server timeout'};
    } catch (e) {
      return {'success': false, 'message': 'Error: $e'};
    }
  }

  // ---------- GET DRIVER DELIVERIES ----------
  static Future<List<dynamic>> getDeliveries(int driverId) async {
    try {
      final res = await http.get(
        Uri.parse('$API/api/driver/$driverId/deliveries'),
        headers: _authHeaders(),
      ).timeout(_timeout);

      return _safeJsonList(res);
    } catch (e) {
      debugPrint('getDeliveries failed: $e');
      return [];
    }
  }

  // ---------- UPDATE DELIVERY STATUS ----------
  static Future<Map<String, dynamic>> updateDeliveryStatus(
      int deliveryId, String status) async {
    try {
      final res = await http.put(
        Uri.parse('$API/api/deliveries/$deliveryId/status'),
        headers: _authHeaders(),
        body: jsonEncode({'delivery_status': status}),
      ).timeout(_timeout);

      return _safeJson(res);
    } catch (e) {
      return {'success': false, 'message': 'Error: $e'};
    }
  }

  // ---------- SAVE SENSOR DATA ----------
  static Future<void> saveSensorData(
      int deliveryId, double temp, double hum, bool alert) async {
    try {
      await http.post(
        Uri.parse('$API/api/sensor'),
        headers: _authHeaders(),
        body: jsonEncode({
          'delivery_id': deliveryId,
          'temperature': temp,
          'humidity': hum,
          'is_alert': alert,
        }),
      ).timeout(_timeout);
    } catch (e) {
      debugPrint('saveSensorData failed: $e');
    }
  }

  // ---------- GET SENSOR HISTORY ----------
  static Future<List<dynamic>> getSensorHistory(int driverId) async {
    try {
      final res = await http.get(
        Uri.parse('$API/api/driver/$driverId/sensor-history'),
        headers: _authHeaders(),
      ).timeout(_timeout);

      return _safeJsonList(res);
    } catch (e) {
      debugPrint('getSensorHistory failed: $e');
      return [];
    }
  }

  // ---------- GET DRIVER PROFILE ----------
  static Future<Map<String, dynamic>> getDriverProfile(int driverId) async {
    try {
      final res = await http.get(
        Uri.parse('$API/api/driver/$driverId/profile'),
        headers: _authHeaders(),
      ).timeout(_timeout);

      return _safeJson(res);
    } catch (e) {
      return {'success': false, 'message': 'Error: $e'};
    }
  }

  // ---------- UPDATE DRIVER PROFILE ----------
  static Future<Map<String, dynamic>> updateDriverProfile(
      int driverId, String name, String phone, String license) async {
    try {
      final res = await http.put(
        Uri.parse('$API/api/driver/$driverId/profile'),
        headers: _authHeaders(),
        body: jsonEncode({
          'name': name,
          'phone': phone,
          'license_number': license,
        }),
      ).timeout(_timeout);

      return _safeJson(res);
    } catch (e) {
      return {'success': false, 'message': 'Error: $e'};
    }
  }

  // ==========================================================
  // ---------- GPS: SAVE DRIVER LOCATION ----------
  // ==========================================================
  /// Sends the driver's current GPS coordinates to the server.
  /// Called every 10s from the active-delivery screen.
  /// Returns a Map with success/message (never throws).
  static Future<Map<String, dynamic>> saveDriverLocation(
      int orderId, double lat, double lng) async {
    try {
      final res = await http.post(
        Uri.parse('$PHP_API/save_location.php'),
        headers: {
          'Content-Type': 'application/json',
          if (_token != null) 'Authorization': 'Bearer $_token',
        },
        body: jsonEncode({
          'order_id': orderId,
          'lat': lat,
          'lng': lng,
        }),
      ).timeout(_timeout);

      debugPrint(
          'SAVE_LOCATION order=$orderId status=${res.statusCode}');

      return _safeJson(res);
    } on SocketException {
      debugPrint('saveDriverLocation: No internet');
      return {'success': false, 'message': 'No internet connection'};
    } on TimeoutException {
      debugPrint('saveDriverLocation: Timeout');
      return {'success': false, 'message': 'Server timeout'};
    } catch (e) {
      debugPrint('saveDriverLocation failed: $e');
      return {'success': false, 'message': 'Error: $e'};
    }
  }

  static Future<Map<String, dynamic>?> getDriverLocation(
      int orderId) async {
    try {
      final res = await http.get(
        Uri.parse('$PHP_API/track.php?order_id=$orderId'),
        headers: _authHeaders(),
      ).timeout(_timeout);

      if (res.statusCode != 200) {
        debugPrint(
            'getDriverLocation HTTP ${res.statusCode} for order $orderId');
        return null;
      }

      final decoded = _safeJson(res);

      if (decoded['success'] == true) {
        // Safe num→double: returns null if value is null/non-numeric.
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
    } on SocketException {
      debugPrint('getDriverLocation: No internet');
      return null;
    } on TimeoutException {
      debugPrint('getDriverLocation: Timeout');
      return null;
    } catch (e) {
      debugPrint('getDriverLocation failed: $e');
      return null;
    }
  }
}