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
  bool _loading = false;

  @override
  void dispose() {
    nameController.dispose();
    emailController.dispose();
    passwordController.dispose();
    phoneController.dispose();
    addressController.dispose();
    super.dispose();
  }

  Future<void> register() async {
    if (_loading) return;

    if (nameController.text.trim().isEmpty ||
        emailController.text.trim().isEmpty ||
        passwordController.text.isEmpty) {
      setState(() => message = 'Name, email, and password are required');
      return;
    }

    setState(() {
      _loading = true;
      message = '';
    });

    final result = await ApiService.register(
      nameController.text.trim(),
      emailController.text.trim(),
      passwordController.text,
      phoneController.text.trim(),
      addressController.text.trim(),
    );

    if (!mounted) return;
    setState(() => _loading = false);

    if (result != null && result['success'] == true) {
      showDialog(
        context: context,
        barrierDismissible: false,
        builder: (_) => AlertDialog(
          title: Text('Registration Successful'),
          content: Text(
            'Your customer code: ${result['customer_code']}\n\nPlease save this code.',
          ),
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
            TextField(controller: nameController, maxLength: 100, decoration: InputDecoration(labelText: 'Name *')),
            TextField(controller: emailController, maxLength: 100, decoration: InputDecoration(labelText: 'Email *')),
            TextField(controller: passwordController, maxLength: 100, decoration: InputDecoration(labelText: 'Password *'), obscureText: true),
            TextField(controller: phoneController, maxLength: 20, decoration: InputDecoration(labelText: 'Phone')),
            TextField(controller: addressController, maxLength: 200, decoration: InputDecoration(labelText: 'Address')),
            SizedBox(height: 20),
            ElevatedButton(
              onPressed: _loading ? null : register,
              child: _loading
                  ? SizedBox(
                height: 20,
                width: 20,
                child: CircularProgressIndicator(
                  strokeWidth: 2,
                  color: Colors.white,
                ),
              )
                  : Text('Register'),
            ),
            SizedBox(height: 10),
            Text(message, style: TextStyle(color: Colors.red)),
          ],
        ),
      ),
    );
  }
}