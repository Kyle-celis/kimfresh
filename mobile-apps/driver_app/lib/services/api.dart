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
      if (data['success'] == true && data['role'] == 'driver') {
        return data;
      }
      return data;
    }
    return null;
  }

  // GET DRIVER DELIVERIES
  static Future<List<dynamic>> getDeliveries(int driverId) async {
    final res = await http.get(Uri.parse('$API/api/driver/$driverId/deliveries'));
    if (res.statusCode == 200) {
      return jsonDecode(res.body);
    }
    return [];
  }

  // UPDATE DELIVERY STATUS
  static Future<Map<String, dynamic>> updateDeliveryStatus(int deliveryId, String status) async {
    final res = await http.put(
      Uri.parse('$API/api/deliveries/$deliveryId/status'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({'delivery_status': status}),
    );
    return jsonDecode(res.body);
  }

  // SAVE SENSOR DATA
  static Future<void> saveSensorData(int deliveryId, double temp, double hum, bool alert) async {
    await http.post(
      Uri.parse('$API/api/sensor'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({
        'delivery_id': deliveryId,
        'temperature': temp,
        'humidity': hum,
        'is_alert': alert,
      }),
    );
  }
}