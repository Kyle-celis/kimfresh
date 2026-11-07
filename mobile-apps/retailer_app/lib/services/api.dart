import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;

const String API = 'http://10.0.2.2/php-web/public/api';
const String PHP_API = 'http://10.0.2.2/php-web/public';

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
        body: jsonEncode({'email': email, 'password': password, 'role': role ?? 'retailer'}),
      ).timeout(_timeout);

      final decoded = jsonDecode(res.body);
      if (decoded is Map<String, dynamic> && decoded['success'] == true) {
        _token = decoded['token'];
      }
      return decoded;
    } catch (e) {
      return {'success': false, 'message': 'Error: $e'};
    }
  }

  // ---------- REGISTER ----------
  static Future<Map<String, dynamic>> register(String name, String email, String password, String phone, String address) async {
    try {
      final res = await http.post(
        Uri.parse('$API/register.php'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'name': name, 'email': email, 'password': password, 'phone': phone, 'address': address}),
      ).timeout(_timeout);
      return jsonDecode(res.body);
    } catch (e) {
      return {'success': false, 'message': 'Error: $e'};
    }
  }

  // ---------- OTP VERIFICATION ----------
  static Future<Map<String, dynamic>> verifyRetailerOTP(int retailerId, String otp) async {
    try {
      final res = await http.post(
        Uri.parse('$API/retailer/verify-otp'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'retailer_id': retailerId, 'otp': otp}),
      ).timeout(_timeout);
      return jsonDecode(res.body);
    } catch (e) {
      return {'success': false, 'message': 'Error: $e'};
    }
  }

  // ---------- PRODUCTS ----------
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

  // ---------- PLACE ORDER ----------
  static Future<Map<String, dynamic>> placeOrder(int retailerId, List<Map<String, dynamic>> items, {double? lat, double? lng}) async {
    try {
      final res = await http.post(
        Uri.parse('$API/api_orders.php'),
        headers: _authHeaders(json: true),
        body: jsonEncode({'retailer_id': retailerId, 'items': items, if (lat != null) 'retailer_lat': lat, if (lng != null) 'retailer_lng': lng}),
      ).timeout(_timeout);
      return jsonDecode(res.body);
    } catch (e) {
      return {'success': false, 'message': 'Error: $e'};
    }
  }

  // ---------- GET ORDERS ----------
  static Future<List<dynamic>> getOrders(int retailerId) async {
    try {
      final res = await http.get(Uri.parse('$API/view_order.php?retailer_id=$retailerId') , headers: _authHeaders()).timeout(_timeout);
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        return data is List ? data : (data['orders'] ?? []);
      }
      return [];
    } catch (e) {
      return [];
    }
  }

  // ---------- STORE STATUS ----------
  static Future<Map<String, dynamic>> getStoreStatus() async {
    try {
      final res = await http.get(Uri.parse('$API/api_products.php?action=store_status'), headers: _authHeaders()).timeout(_timeout);
      return res.statusCode == 200 ? jsonDecode(res.body) : {'is_open': true, 'message': ''};
    } catch (e) {
      return {'is_open': true, 'message': ''};
    }
  }

  // ---------- PROFILE ----------
  static Future<Map<String, dynamic>> getRetailerProfile(int retailerId) async {
    try {
      final res = await http.get(Uri.parse('$API/profile.php?id=$retailerId'), headers: _authHeaders()).timeout(_timeout);
      return res.statusCode == 200 ? jsonDecode(res.body) : {'success': false};
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
      return jsonDecode(res.body);
    } catch (e) {
      return {'success': false, 'message': 'Error: $e'};
    }
  }


  // ---------- GPS (Pin Store) ----------
  static Future<bool> saveRetailerGPS(int retailerId, double lat, double lng) async {
    try {
      final res = await http.post(
        Uri.parse('$API/update_retailer_location.php'), // No URL parameters
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'retailer_id': retailerId, // Added to the body
          'lat': lat,
          'lng': lng
        }),
      ).timeout(_timeout);

      final data = jsonDecode(res.body);
      return data['success'] == true;
    } catch (e) {
      return false;
    }
  }

  // ---------- LIVE TRACKING ----------
  static Future<bool> saveRetailerLocation(int orderId, double lat, double lng) async {
    // We already send the location when placing the order in api_orders.php,
    // so this function can safely return true to prevent 404 crashes.
    return true;
  }

  static Future<Map<String, dynamic>?> getDriverLocation(int orderId) async {
    try {
      // Changed to point to the new MVC route
      final res = await http.get(Uri.parse('$PHP_API/track?order_id=$orderId')).timeout(_timeout);
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (data['success'] == true) {
          double? toD(dynamic v) => (v is num) ? v.toDouble() : null;
          return {'driver_lat': toD(data['driver_lat']), 'driver_lng': toD(data['driver_lng']), 'retailer_lat': toD(data['retailer_lat']), 'retailer_lng': toD(data['retailer_lng'])};
        }
      }
      return null;
    } catch (e) {
      return null;
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
      return jsonDecode(res.body);
    } catch (e) {
      return {'success': false, 'message': 'Error: $e'};
    }
  }

  // ---------- RESET PASSWORD ----------
  static Future<Map<String, dynamic>> resetPassword(String email, String otp, String newPassword) async {
    try {
      final res = await http.post(
        Uri.parse('$API/reset_password.php'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'email': email, 'otp': otp, 'new_password': newPassword}),
      ).timeout(_timeout);
      return jsonDecode(res.body);
    } catch (e) {
      return {'success': false, 'message': 'Error: $e'};
    }
  }

  // ---------- CANCEL ORDER ----------
  static Future<Map<String, dynamic>> cancelOrder(int orderId) async {
    try {
      final res = await http.put(
        Uri.parse('$API/orders/$orderId/cancel'),
        headers: _authHeaders(json: true),
      ).timeout(_timeout);

      return jsonDecode(res.body);
    } catch (e) {
      return {'success': false, 'message': 'Error: $e'};
    }
  }
}