import 'package:flutter/material.dart';
import '../services/api.dart';
import 'deliveries_screen.dart';
import 'package:permission_handler/permission_handler.dart';

class LoginScreen extends StatefulWidget {
  @override
  _LoginScreenState createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final emailController = TextEditingController();
  final passwordController = TextEditingController();
  String message = '';

  @override
  void initState() {
    super.initState();
    requestPermissions();
  }

  Future<void> requestPermissions() async {
    await [
      Permission.bluetoothScan,
      Permission.bluetoothConnect,
      Permission.location,
    ].request();
  }

  void login() async {
    final result = await ApiService.login(emailController.text, passwordController.text);
    if (result != null && result['success'] == true && result['role'] == 'driver') {
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(builder: (_) => DeliveriesScreen(
          driverId: result['user_id'],
          driverName: result['name'],
        )),
      );
    } else {
      setState(() => message = result?['message'] ?? 'Invalid credentials');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('KimFresh Driver Login')),
      body: Padding(
        padding: EdgeInsets.all(20),
        child: Column(
          children: [
            TextField(controller: emailController, decoration: InputDecoration(labelText: 'Email')),
            TextField(controller: passwordController, decoration: InputDecoration(labelText: 'Password'), obscureText: true),
            SizedBox(height: 20),
            ElevatedButton(onPressed: login, child: Text('Login')),
            SizedBox(height: 10),
            Text(message, style: TextStyle(color: Colors.red)),
          ],
        ),
      ),
    );
  }
}