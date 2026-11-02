import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;

// NEW LOCAL URLS
const String BASE_URL = 'http://10.0.2.2/php-web';
const String API = 'http://10.0.2.2/php-web/api';

class ApiService {
  static const Duration _timeout = Duration(seconds: 15);
  static String? _token;

  static void setToken(String? token) => _token = token;
  static void clearToken() => _token = null;

  static Map<String, String> _authHeaders({bool json = false}) => {
    'Accept': 'application/json',
    if (json) 'Content-Type': 'application/json',
    if (_token != null) 'Authorization': 'Bearer $_token',
  };

  // ---------- LOGIN ----------
  static Future<Map<String, dynamic>> login(String email, String password, {String? role}) async {
    try {
      final res = await http.post(
        Uri.parse('$API/login.php'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'email': email,
          'password': password,
          if (role != null) 'role': role,
        }),
      ).timeout(_timeout);

      final decoded = jsonDecode(res.body);
      if (decoded is Map<String, dynamic>) {
        if (decoded['success'] == true && decoded['token'] != null) {
          _token = decoded['token'];
        }
        return decoded;
      }
      return {'success': false, 'message': 'Server returned ${res.statusCode}'};

    } on SocketException {
      return {'success': false, 'message': 'No internet connection'};
    } on TimeoutException {
      return {'success': false, 'message': 'Server timeout, try again'};
    } catch (e) {
      return {'success': false, 'message': 'Unexpected error: $e'};
    }
  }

  // ---------- REGISTER ----------
  static Future<Map<String, dynamic>?> register(
      String name, String email, String password, String phone) async {
    try {
      final res = await http.post(
        Uri.parse('$API/register.php'), // Assuming you have a register.php in api folder
        headers: {'Accept': 'application/json', 'Content-Type': 'application/json'},
        body: jsonEncode({
          'name': name,
          'email': email,
          'password': password,
          'phone': phone,
        }),
      ).timeout(_timeout);

      try {
        final decoded = jsonDecode(res.body);
        if (decoded is Map<String, dynamic>) return decoded;
      } catch (_) {}
      return {'success': false, 'message': 'Server returned ${res.statusCode}'};
    } catch (e) {
      return {'success': false, 'message': 'Unexpected error: $e'};
    }
  }

  // ---------- RETAILER OTP ----------
  static Future<Map<String, dynamic>> verifyRetailerOTP(int retailerId, String otp) async {
    try {
      final res = await http.post(
        Uri.parse('$API/verify-otp.php'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'retailer_id': retailerId, 'otp': otp}),
      ).timeout(_timeout);

      final decoded = jsonDecode(res.body);
      if (decoded is Map<String, dynamic>) return decoded;
      return {'success': false, 'message': 'Unexpected response format'};
    } catch (e) {
      return {'success': false, 'message': 'Error: $e'};
    }
  }

  // ---------- FORGOT PASSWORD ----------
  static Future<Map<String, dynamic>> forgotPassword(String email) async {
    try {
      final res = await http.post(
        Uri.parse('$API/forgot_password.php'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'email': email}),
      ).timeout(_timeout);

      final data = jsonDecode(res.body);
      if (data is Map<String, dynamic>) return data;
      return {'success': false, 'message': 'Invalid response format'};
    } catch (e) {
      return {'success': false, 'message': 'Network error: $e'};
    }
  }

  // ---------- RESET PASSWORD ----------
  static Future<Map<String, dynamic>> resetPassword(String email, String otp, String newPassword) async {
    try {
      final res = await http.post(
        Uri.parse('$API/reset_password.php'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'email': email,
          'otp': otp,
          'new_password': newPassword,
        }),
      ).timeout(_timeout);

      final data = jsonDecode(res.body);
      if (data is Map<String, dynamic>) return data;
      return {'success': false, 'message': 'Invalid response format'};
    } catch (e) {
      return {'success': false, 'message': 'Network error: $e'};
    }
  }

  // ---------- PRODUCTS ----------
  static Future<List<dynamic>> getProducts() async {
    try {
      final res = await http.get(
        Uri.parse('$API/api_products.php'),
        headers: _authHeaders(),
      ).timeout(_timeout);

      if (res.statusCode != 200) return [];

      final data = jsonDecode(res.body);
      if (data is List) return data;
      if (data is Map && data['products'] is List) {
        return data['products'] as List;
      }
      return [];
    } catch (e) {
      return [];
    }
  }

  // ---------- PLACE ORDER ----------
  static Future<Map<String, dynamic>> placeOrder(
      int retailerId, List<Map<String, dynamic>> items, {double? lat, double? lng}) async {
    try {
      final res = await http.post(
        Uri.parse('$API/api_orders.php'),
        headers: _authHeaders(json: true),
        body: jsonEncode({
          'retailer_id': retailerId,
          'items': items,
          if (lat != null) 'retailer_lat': lat,
          if (lng != null) 'retailer_lng': lng,
        }),
      ).timeout(_timeout);

      String cleanBody = res.body.trim();
      if (cleanBody.endsWith('null')) {
        cleanBody = cleanBody.substring(0, cleanBody.length - 4);
      }

      Map<String, dynamic>? parsed;
      try {
        final decoded = jsonDecode(cleanBody);
        if (decoded is Map<String, dynamic>) parsed = decoded;
      } catch (_) {}

      if (parsed != null) return parsed;
      return {'success': false, 'message': 'Server returned ${res.statusCode}'};
    } catch (e) {
      return {'success': false, 'message': 'Unexpected error: $e'};
    }
  }

  static Future<Map<String, dynamic>> cancelOrder(int orderId) async {
    try {
      final res = await http.post(
        Uri.parse('$BASE_URL/cancel_order.php'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'order_id': orderId}),
      ).timeout(_timeout);

      final data = jsonDecode(res.body);
      if (data is Map<String, dynamic>) return data;
      return {'success': false, 'message': 'Unexpected response'};
    } catch (e) {
      return {'success': false, 'message': 'Error: $e'};
    }
  }

  // ---------- STORE STATUS ----------
  static Future<Map<String, dynamic>> getStoreStatus() async {
    try {
      final res = await http.get(
        Uri.parse('$BASE_URL/settings.php'),
        headers: _authHeaders(),
      ).timeout(_timeout);

      if (res.statusCode == 200) return jsonDecode(res.body);
      return {'is_open': true, 'message': ''};
    } catch (e) {
      return {'is_open': true, 'message': ''};
    }
  }

  // ---------- RETAILER PROFILE ----------
  static Future<Map<String, dynamic>> getRetailerProfile(int retailerId) async {
    try {
      final res = await http.get(
        Uri.parse('$API/profile.php?id=$retailerId'),
        headers: _authHeaders(),
      ).timeout(_timeout);

      if (res.statusCode != 200) return {'success': false, 'message': 'Server returned ${res.statusCode}'};
      final decoded = jsonDecode(res.body);
      if (decoded is Map<String, dynamic>) return decoded;
      return {'success': false, 'message': 'Unexpected server response'};
    } catch (e) {
      return {'success': false, 'message': 'Unexpected error: $e'};
    }
  }

  // ---------- UPDATE RETAILER PROFILE ----------
  static Future<Map<String, dynamic>> updateRetailerProfile(int retailerId, String name, String phone, String address) async {
    try {
      final res = await http.post(
        Uri.parse('$BASE_URL/profile.php?id=$retailerId'),
        headers: _authHeaders(json: true),
        body: jsonEncode({'name': name, 'phone': phone, 'address': address}),
      ).timeout(_timeout);

      Map<String, dynamic>? parsed;
      try {
        final decoded = jsonDecode(res.body);
        if (decoded is Map<String, dynamic>) parsed = decoded;
      } catch (_) {}

      if (parsed != null) return parsed;
      return {'success': false, 'message': 'Server returned ${res.statusCode}'};
    } catch (e) {
      return {'success': false, 'message': 'Unexpected error: $e'};
    }
  }

  static Future<bool> saveRetailerGPS(int retailerId, double lat, double lng) async {
    try {
      final res = await http.post(
        Uri.parse('$API/update_retailer_location.php'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'retailer_id': retailerId, 'lat': lat, 'lng': lng}),
      ).timeout(_timeout);

      final data = jsonDecode(res.body);
      return data['success'] == true;
    } catch (e) {
      return false;
    }
  }

  // ---------- GET ORDERS ----------
  static Future<List<dynamic>> getOrders(int retailerId) async {
    try {
      final res = await http.get(
        Uri.parse('$API/view_order.php?retailer_id=$retailerId'),
        headers: _authHeaders(),
      ).timeout(_timeout);

      if (res.statusCode != 200) return [];

      final data = jsonDecode(res.body);
      if (data is List) return data;
      if (data is Map && data['orders'] is List) {
        return data['orders'] as List;
      }
      return [];
    } catch (e) {
      return [];
    }
  }

  // ---------- GPS ----------
  static Future<bool> saveRetailerLocation(int orderId, double lat, double lng) async {
    try {
      final res = await http.post(
        Uri.parse('$BASE_URL/save_retailer_location.php'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'order_id': orderId, 'lat': lat, 'lng': lng}),
      ).timeout(_timeout);
      return res.statusCode == 200 || res.statusCode == 201;
    } catch (e) {
      return false;
    }
  }

  static Future<Map<String, dynamic>?> getDriverLocation(int orderId) async {
    try {
      final res = await http.get(
        Uri.parse('$BASE_URL/track.php?order_id=$orderId'),
      ).timeout(_timeout);

      if (res.statusCode != 200) return null;
      final data = jsonDecode(res.body);

      if (data is Map && data['success'] == true) {
        double? toD(dynamic v) => (v is num) ? v.toDouble() : null;
        return {
          'driver_lat': toD(data['driver_lat']),
          'driver_lng': toD(data['driver_lng']),
          'retailer_lat': toD(data['retailer_lat']),
          'retailer_lng': toD(data['retailer_lng']),
        };
      }
      return null;
    } catch (e) {
      return null;
    }
  }
}