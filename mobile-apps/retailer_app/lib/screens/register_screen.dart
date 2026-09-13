import 'package:flutter/material.dart';
import '../services/api.dart';

class RegisterScreen extends StatefulWidget {
  @override
  _RegisterScreenState createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen> {
  final nameController = TextEditingController();
  final emailController = TextEditingController();
  final passwordController = TextEditingController();
  final phoneController = TextEditingController();
  final addressController = TextEditingController();
  String message = '';

  void register() async {
    if (nameController.text.isEmpty ||
        emailController.text.isEmpty ||
        passwordController.text.isEmpty) {
      setState(() => message = 'Name, email, and password are required');
      return;
    }

    final result = await ApiService.register(
      nameController.text,
      emailController.text,
      passwordController.text,
      phoneController.text,
      addressController.text,
    );

    if (result != null && result['success'] == true) {
      showDialog(
        context: context,
        builder: (_) => AlertDialog(
          title: Text('Registration Successful'),
          content: Text('Your customer code: ${result['customer_code']}\n\nPlease save this code.'),
          actions: [
            TextButton(
              onPressed: () {
                Navigator.pop(context);
                Navigator.pop(context);
              },
              child: Text('OK'),
            ),
          ],
        ),
      );
    } else {
      setState(() => message = result?['message'] ?? 'Registration failed');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Retailer Sign Up')),
      body: SingleChildScrollView(
        padding: EdgeInsets.all(20),
        child: Column(
          children: [
            TextField(controller: nameController, decoration: InputDecoration(labelText: 'Name *')),
            TextField(controller: emailController, decoration: InputDecoration(labelText: 'Email *')),
            TextField(controller: passwordController, decoration: InputDecoration(labelText: 'Password *'), obscureText: true),
            TextField(controller: phoneController, decoration: InputDecoration(labelText: 'Phone')),
            TextField(controller: addressController, decoration: InputDecoration(labelText: 'Address')),
            SizedBox(height: 20),
            ElevatedButton(onPressed: register, child: Text('Register')),
            SizedBox(height: 10),
            Text(message, style: TextStyle(color: Colors.red)),
          ],
        ),
      ),
    );
  }
}