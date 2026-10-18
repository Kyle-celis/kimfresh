import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;

const String API = 'https://fax-northwest-subscribe-eng.trycloudflare.com/';

class ApiService {
  static const Duration _timeout = Duration(seconds: 15);
  static String? _token;

  static void setToken(String? token) { _token = token; }
  static void clearToken() { _token = null; }

  static Map<String, String> _authHeaders() {
    return {
      'Content-Type': 'application/json',
      if (_token != null) 'Authorization': 'Bearer $_token',
    };
  }

  // ---------- LOGIN ----------
  static Future<Map<String, dynamic>> login(String email, String password) async {
    try {
      final res = await http.post(
        Uri.parse('$API/api/login'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'email': email, 'password': password}),
      ).timeout(_timeout);

      final body = jsonDecode(res.body);
      if (res.statusCode == 200 && body['success'] == true && body['role'] == 'driver') {
        _token = body['token'];
      }
      return body;
    } on SocketException {
      return {'success': false, 'message': 'No internet connection'};
    } on TimeoutException {
      return {'success': false, 'message': 'Server timeout'};
    } catch (e) {
      return {'success': false, 'message': 'Error: $e'};
    }
  }

  // ---------- DRIVER SIGNUP ----------
  static Future<Map<String, dynamic>> register(
      String name, String email, String password, String phone, String license) async {
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

      return jsonDecode(res.body);
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

      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (data is List) return data;
      }
      return [];
    } catch (_) {
      return [];
    }
  }

  // ---------- UPDATE DELIVERY STATUS ----------
  static Future<Map<String, dynamic>> updateDeliveryStatus(int deliveryId, String status) async {
    try {
      final res = await http.put(
        Uri.parse('$API/api/deliveries/$deliveryId/status'),
        headers: _authHeaders(),
        body: jsonEncode({'delivery_status': status}),
      ).timeout(_timeout);
      return jsonDecode(res.body);
    } catch (e) {
      return {'success': false, 'message': 'Error: $e'};
    }
  }

  // ---------- SAVE SENSOR DATA ----------
  static Future<void> saveSensorData(int deliveryId, double temp, double hum, bool alert) async {
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
    } catch (_) {}
  }

  // ---------- GET SENSOR HISTORY ----------
  static Future<List<dynamic>> getSensorHistory(int driverId) async {
    try {
      final res = await http.get(
        Uri.parse('$API/api/driver/$driverId/sensor-history'),
        headers: _authHeaders(),
      ).timeout(_timeout);

      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (data is List) return data;
      }
      return [];
    } catch (_) {
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
      return jsonDecode(res.body);
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
      return jsonDecode(res.body);
    } catch (e) {
      return {'success': false, 'message': 'Error: $e'};
    }
  }

}