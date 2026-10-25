import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;

const String API = 'https://fax-northwest-subscribe-eng.trycloudflare.com/';

class ApiService {
  static const Duration _timeout = Duration(seconds: 15);

  // ---------- TOKEN ----------
  static String? _token;

  static void setToken(String? token) => _token = token;
  static void clearToken() => _token = null;

  static Map<String, String> _authHeaders({bool json = false}) => {
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
        if (data['success'] == true && data['role'] == 'retailer') {
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

  // ---------- REGISTER ----------
  static Future<Map<String, dynamic>?> register(
      String name,
      String email,
      String password,
      String phone,
      String address,
      ) async {
    try {
      final res = await http.post(
        Uri.parse('$API/api/retailer/register'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'name': name,
          'email': email,
          'password': password,
          'phone': phone,
          'address': address,
        }),
      ).timeout(_timeout);

      return jsonDecode(res.body);
    } on SocketException {
      return {'success': false, 'message': 'No internet connection'};
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

      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (data is List) return data;
        if (data is Map && data['products'] is List) {
          return data['products'] as List;
        }
      }
      return [];
    } catch (_) {
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

      if (res.statusCode == 200 || res.statusCode == 201) {
        return jsonDecode(res.body);
      }
      return {
        'success': false,
        'message': 'Server returned ${res.statusCode}: ${res.body}',
      };
    } on SocketException {
      return {'success': false, 'message': 'No internet connection'};
    } on TimeoutException {
      return {'success': false, 'message': 'Server timeout, try again'};
    } catch (e) {
      return {'success': false, 'message': 'Unexpected error: $e'};
    }
  }

  // ---------- GET RETAILER PROFILE ----------
  static Future<Map<String, dynamic>> getRetailerProfile(int retailerId) async {
    try {
      final res = await http.get(
        Uri.parse('$API/api/retailer/$retailerId/profile'),
        headers: _authHeaders(),
      ).timeout(_timeout);
      return jsonDecode(res.body);
    } catch (e) {
      return {'success': false, 'message': 'Error: $e'};
    }
  }

  // ---------- UPDATE RETAILER PROFILE ----------
  static Future<Map<String, dynamic>> updateRetailerProfile(
      int retailerId, String name, String phone, String address) async {
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
      return jsonDecode(res.body);
    } catch (e) {
      return {'success': false, 'message': 'Error: $e'};
    }
  }

  // ---------- GET ORDERS ----------
  static Future<List<dynamic>> getOrders(int retailerId) async {
    try {
      final res = await http.get(
        Uri.parse('$API/api/orders/$retailerId'),
        headers: _authHeaders(),
      ).timeout(_timeout);

      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (data is List) return data;
        if (data is Map && data['orders'] is List) {
          return data['orders'] as List;
        }
      }
      return [];
    } catch (_) {
      return [];
    }
  }
}