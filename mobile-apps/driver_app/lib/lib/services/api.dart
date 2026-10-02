import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;

const String API = 'https://basketball-inventory-object-traditional.trycloudflare.com';

class ApiService {
  static const Duration _timeout = Duration(seconds: 15);

  // ---------- TOKEN ----------
  static String? _token;

  static void setToken(String? token) => _token = token;
  static void clearToken() => _token = null;

  static Map<String, String> _headers({bool json = false}) => {
    if (json) 'Content-Type': 'application/json',
    if (_token != null) 'Authorization': 'Bearer $_token',
  };

  // ---------- LOGIN ----------
  static Future<Map<String, dynamic>?> login(String email, String password) async {
    try {
      final res = await http.post(
        Uri.parse('$API/api/login'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'email': email, 'password': password}),
      ).timeout(_timeout);

      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (data['success'] == true && data['role'] == 'driver') {
          _token = data['token'];
          return data;
        }
        return data;
      }
      return null;
    } on SocketException {
      return {'success': false, 'message': 'No internet connection'};
    } on TimeoutException {
      return {'success': false, 'message': 'Server timeout, try again'};
    } catch (e) {
      return {'success': false, 'message': 'Unexpected error: $e'};
    }
  }

  // ---------- GET DRIVER DELIVERIES ----------
  static Future<List<dynamic>> getDeliveries(int driverId) async {
    try {
      final res = await http.get(
        Uri.parse('$API/api/driver/$driverId/deliveries'),
        headers: _headers(),
      ).timeout(_timeout);

      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (data is List) return data;
        if (data is Map && data['deliveries'] is List) {
          return data['deliveries'] as List;
        }
      }
      return [];
    } catch (_) {
      return [];
    }
  }

  // ---------- UPDATE DELIVERY STATUS ----------
  static Future<Map<String, dynamic>> updateDeliveryStatus(
      int deliveryId, String status) async {
    try {
      final res = await http.put(
        Uri.parse('$API/api/deliveries/$deliveryId/status'),
        headers: _headers(json: true),
        body: jsonEncode({'delivery_status': status}),
      ).timeout(_timeout);

      if (res.statusCode == 200) {
        return jsonDecode(res.body);
      }
      return {'success': false, 'message': 'Server returned ${res.statusCode}'};
    } on SocketException {
      return {'success': false, 'message': 'No internet connection'};
    } on TimeoutException {
      return {'success': false, 'message': 'Server timeout, try again'};
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
        headers: _headers(json: true),
        body: jsonEncode({
          'delivery_id': deliveryId,
          'temperature': temp,
          'humidity': hum,
          'is_alert': alert,
        }),
      ).timeout(_timeout);
    } catch (_) {
      // silent — sensor save failure should not crash driver app
    }
  }

  // ---------- GET SENSOR HISTORY ----------
  static Future<List<dynamic>> getSensorHistory(int driverId) async {
    try {
      final res = await http.get(
        Uri.parse('$API/api/driver/$driverId/sensor-history'),
        headers: _headers(),
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
}