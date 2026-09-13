import 'dart:convert';
import 'package:http/http.dart' as http;

const String API = 'http://192.168.1.125:5000';

class ApiService {
  // LOGIN
  static Future<Map<String, dynamic>?> login(String email, String password) async {
    final res = await http.post(
      Uri.parse('$API/api/login'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({'email': email, 'password': password}),
    );
    if (res.statusCode == 200) {
      final data = jsonDecode(res.body);
      if (data['success'] == true && data['role'] == 'retailer') {
        return data;
      }
      return data;
    }
    return null;
  }

  // REGISTER
  static Future<Map<String, dynamic>?> register(
      String name, String email, String password, String phone, String address) async {
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
    );
    return jsonDecode(res.body);
  }

  // GET PRODUCTS
  static Future<List<dynamic>> getProducts() async {
    final res = await http.get(Uri.parse('$API/api/products'));
    if (res.statusCode == 200) {
      return jsonDecode(res.body);
    }
    return [];
  }

  // PLACE ORDER
  static Future<Map<String, dynamic>> placeOrder(int retailerId, List<Map<String, dynamic>> items) async {
    final res = await http.post(
      Uri.parse('$API/api/orders'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({'retailer_id': retailerId, 'items': items}),
    );
    return jsonDecode(res.body);
  }

  // GET ORDERS
  static Future<List<dynamic>> getOrders(int retailerId) async {
    final res = await http.get(Uri.parse('$API/api/orders/$retailerId'));
    if (res.statusCode == 200) {
      return jsonDecode(res.body);
    }
    return [];
  }
}