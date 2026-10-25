import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;

// --- URLS ---
const String API = 'http://192.168.0.117:5000';       // Python Flask
const String PHP_API = 'http://192.168.0.117:8000';   // PHP Server

class ApiService {
  static const Duration _timeout = Duration(seconds: 15);

  // ---------- TOKEN ----------
  static String? _token;

  static void setToken(String? token) => _token = token;
  static void clearToken() => _token = null;

  static Map<String, String> _authHeaders({bool json = false}) => {
    'Accept': 'application/json',
    if (json) 'Content-Type': 'application/json',
    if (_token != null) 'Authorization': 'Bearer $_token',
  };

  // ---------- LOGIN ----------
  static Future<Map<String, dynamic>?> login(
      String email, String password) async {
    try {
      final res = await http.post(
        Uri.parse('$API/api/login'),
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        body: jsonEncode({'email': email, 'password': password}),
      ).timeout(_timeout);

      Map<String, dynamic>? parsed;
      try {
        final decoded = jsonDecode(res.body);
        if (decoded is Map<String, dynamic>) parsed = decoded;
      } catch (_) {}

      if (res.statusCode == 200) {
        if (parsed != null) {
          if (parsed['success'] == true && parsed['role'] == 'retailer') {
            final token = parsed['token'];
            if (token is String && token.isNotEmpty) {
              _token = token;
            }
          }
          return parsed;
        }
        return {
          'success': false,
          'message': 'Unexpected server response (200, non-JSON)',
        };
      }

      if (parsed != null) return parsed;
      return {
        'success': false,
        'message': 'Server returned ${res.statusCode}',
      };
    } on SocketException {
      return {'success': false, 'message': 'No internet connection'};
    } on HandshakeException {
      return {'success': false, 'message': 'Secure connection failed'};
    } on TimeoutException {
      return {'success': false, 'message': 'Server timeout, try again'};
    } catch (e) {
      return {'success': false, 'message': 'Unexpected error: $e'};
    }
  }

  static Future<Map<String, dynamic>> verifyOTP(int retailerId, String otp) async {
    try {
      final res = await http.post(
        Uri.parse('$API/api/retailer/verify-otp'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'retailer_id': retailerId, 'otp': otp}),
      ).timeout(_timeout);

      // Assuming you have a helper, or just use jsonDecode
      final decoded = jsonDecode(res.body);
      if (decoded is Map<String, dynamic>) return decoded;
      return {'success': false, 'message': 'Unexpected response format'};

    } catch (e) {
      return {'success': false, 'message': 'Error: $e'};
    }
  }

  // ---------- SAVE RETAILER LOCATION (PHP) ----------
  static Future<bool> saveRetailerLocation(int orderId, double lat, double lng) async {
    try {
      final res = await http.post(
        Uri.parse('$PHP_API/save_retailer_location.php'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'order_id': orderId,
          'lat': lat,
          'lng': lng,
        }),
      ).timeout(_timeout);

      if (res.statusCode == 200 || res.statusCode == 201) {
        return true;
      }
      _logFailure('saveRetailerLocation', res);
      return false;
    } catch (e) {
      _logNetwork('saveRetailerLocation', '$e');
      return false;
    }
  }

  // ---------- GET DRIVER + RETAILER LOCATION (PHP) ----------
  static Future<Map<String, dynamic>?> getDriverLocation(int orderId) async {
    try {
      final res = await http.get(
        Uri.parse('$PHP_API/track.php?order_id=$orderId'),
      ).timeout(_timeout);

      if (res.statusCode != 200) {
        _logFailure('getDriverLocation', res);
        return null;
      }

      dynamic data;
      try {
        data = jsonDecode(res.body);
      } catch (_) {
        _logFailure('getDriverLocation', res, note: 'non-JSON');
        return null;
      }

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
      _logNetwork('getDriverLocation', '$e');
      return null;
    }
  }

  // Verify Retailer OTP
  static Future<Map<String, dynamic>> verifyRetailerOTP(int retailerId, String otp) async {
    try {
      final res = await http.post(
        Uri.parse('$API/api/retailer/verify-otp'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'retailer_id': retailerId,
          'otp': otp,
        }),
      ).timeout(_timeout);

      final decoded = jsonDecode(res.body);
      if (decoded is Map<String, dynamic>) return decoded;
      return {'success': false, 'message': 'Unexpected response format'};

    } catch (e) {
      return {'success': false, 'message': 'Error: $e'};
    }
  }

  // ---------- REGISTER ----------
  static Future<Map<String, dynamic>?> register(
      String name,
      String email,
      String password,
      String phone,
      ) async {
    try {
      final res = await http.post(
        Uri.parse('$API/api/retailer/register'),
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
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

      return {
        'success': false,
        'message': 'Server returned ${res.statusCode}',
      };
    } on SocketException {
      return {'success': false, 'message': 'No internet connection'};
    } on HandshakeException {
      return {'success': false, 'message': 'Secure connection failed'};
    } on TimeoutException {
      return {'success': false, 'message': 'Server timeout, try again'};
    } catch (e) {
      return {'success': false, 'message': 'Unexpected error: $e'};
    }
  }

  // ---------- PRODUCTS ----------
  static Future<List<dynamic>> getProducts() async {
    try {
      final res = await http.get(
        Uri.parse('$API/api/products'),
        headers: _authHeaders(),
      ).timeout(_timeout);

      if (res.statusCode != 200) {
        _logFailure('getProducts', res);
        return [];
      }

      final data = jsonDecode(res.body);
      if (data is List) return data;
      if (data is Map && data['products'] is List) {
        return data['products'] as List;
      }
      return [];
    } catch (e) {
      _logNetwork('getProducts', '$e');
      return [];
    }
  }

  // ---------- PLACE ORDER ----------
  static Future<Map<String, dynamic>> placeOrder(
      int retailerId,
      List<Map<String, dynamic>> items,
      ) async {
    try {
      final res = await http.post(
        Uri.parse('$API/api/orders'),
        headers: _authHeaders(json: true),
        body: jsonEncode({
          'retailer_id': retailerId,
          'items': items,
        }),
      ).timeout(_timeout);

      Map<String, dynamic>? parsed;
      try {
        final decoded = jsonDecode(res.body);
        if (decoded is Map<String, dynamic>) parsed = decoded;
      } catch (_) {}

      if (parsed != null) return parsed;

      return {
        'success': false,
        'message': 'Server returned ${res.statusCode}: ${res.body}',
      };
    } catch (e) {
      return {'success': false, 'message': 'Unexpected error: $e'};
    }
  }

  // ---------- STORE STATUS ----------
  static Future<Map<String, dynamic>> getStoreStatus() async {
    try {
      final res = await http.get(
        Uri.parse('$API/api/store/status'),
        headers: _authHeaders(),
      ).timeout(_timeout);

      if (res.statusCode == 200) {
        return jsonDecode(res.body);
      }
      return {'is_open': true, 'message': ''};
    } catch (e) {
      return {'is_open': true, 'message': ''};
    }
  }

  // ---------- RETAILER PROFILE ----------
  static Future<Map<String, dynamic>> getRetailerProfile(
      int retailerId) async {
    try {
      final res = await http.get(
        Uri.parse('$API/api/retailer/$retailerId/profile'),
        headers: _authHeaders(),
      ).timeout(_timeout);

      if (res.statusCode != 200) {
        return {
          'success': false,
          'message': 'Server returned ${res.statusCode}',
        };
      }

      final decoded = jsonDecode(res.body);
      if (decoded is Map<String, dynamic>) return decoded;
      return {
        'success': false,
        'message': 'Unexpected server response',
      };
    } catch (e) {
      return {'success': false, 'message': 'Unexpected error: $e'};
    }
  }

  // ---------- UPDATE RETAILER PROFILE ----------
  static Future<Map<String, dynamic>> updateRetailerProfile(
      int retailerId,
      String name,
      String phone,
      String address,
      ) async {
    try {
      final res = await http.put(
        Uri.parse('$API/api/retailer/$retailerId/profile'),
        headers: _authHeaders(json: true),
        body: jsonEncode({
          'name': name,
          'phone': phone,
          'address': address,
        }),
      ).timeout(_timeout);

      Map<String, dynamic>? parsed;
      try {
        final decoded = jsonDecode(res.body);
        if (decoded is Map<String, dynamic>) parsed = decoded;
      } catch (_) {}

      if (parsed != null) return parsed;

      return {
        'success': false,
        'message': 'Server returned ${res.statusCode}',
      };
    } catch (e) {
      return {'success': false, 'message': 'Unexpected error: $e'};
    }
  }

  // ---------- GET ORDERS ----------
  static Future<List<dynamic>> getOrders(int retailerId) async {
    try {
      final res = await http.get(
        Uri.parse('$API/api/orders/$retailerId'),
        headers: _authHeaders(),
      ).timeout(_timeout);

      if (res.statusCode != 200) {
        _logFailure('getOrders', res);
        return [];
      }

      final data = jsonDecode(res.body);
      if (data is List) return data;
      if (data is Map && data['orders'] is List) {
        return data['orders'] as List;
      }
      return [];
    } catch (e) {
      _logNetwork('getOrders', '$e');
      return [];
    }
  }

  // ---------- DEBUG LOGGING ----------
  static void _logFailure(String fn, http.Response res, {String? note}) {
    assert(() {
      final n = note == null ? '' : ' ($note)';
      print('[ApiService] $fn → ${res.statusCode}$n  body=${res.body}');
      return true;
    }());
  }

  static void _logNetwork(String fn, String reason) {
    assert(() {
      print('[ApiService] $fn → network error: $reason');
      return true;
    }());
  }
}