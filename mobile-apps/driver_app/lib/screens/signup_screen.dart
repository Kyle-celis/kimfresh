import 'package:flutter/material.dart';
import '../services/api.dart';

class SignupScreen extends StatefulWidget {
  @override
  _SignupScreenState createState() => _SignupScreenState();
}

class _SignupScreenState extends State<SignupScreen> {
  final nameController = TextEditingController();
  final emailController = TextEditingController();
  final passwordController = TextEditingController();
  final phoneController = TextEditingController();
  final licenseController = TextEditingController();
  String message = '';
  bool _loading = false;

  @override
  void dispose() {
    nameController.dispose();
    emailController.dispose();
    passwordController.dispose();
    phoneController.dispose();
    licenseController.dispose();
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
      licenseController.text.trim(),
    );

    if (!mounted) return;
    setState(() => _loading = false);

    if (result['success'] == true) {
      showDialog(
        context: context,
        barrierDismissible: false,
        builder: (_) => AlertDialog(
          title: Text('Account Created'),
          content: Text('Wait for admin approval before logging in.'),
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
      setState(() => message = result['message'] ?? 'Registration failed');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Driver Sign Up')),
      body: SingleChildScrollView(
        padding: EdgeInsets.all(20),
        child: Column(
          children: [
            TextField(
              controller: nameController,
              decoration: InputDecoration(labelText: 'Name *'),
              maxLength: 100,
            ),
            TextField(
              controller: emailController,
              decoration: InputDecoration(labelText: 'Email *'),
              keyboardType: TextInputType.emailAddress,
              maxLength: 100,
            ),
            TextField(
              controller: passwordController,
              decoration: InputDecoration(labelText: 'Password *'),
              obscureText: true,
              maxLength: 100,
            ),
            TextField(
              controller: phoneController,
              decoration: InputDecoration(labelText: 'Phone'),
              keyboardType: TextInputType.phone,
              maxLength: 20,
            ),
            TextField(
              controller: licenseController,
              decoration: InputDecoration(labelText: 'License Number'),
              maxLength: 50,
            ),
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