import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import '../theme/colors.dart';

class ForgotPasswordScreen extends StatefulWidget {
  @override
  _ForgotPasswordScreenState createState() => _ForgotPasswordScreenState();
}

class _ForgotPasswordScreenState extends State<ForgotPasswordScreen> {
  final _emailFormKey = GlobalKey<FormState>();
  final _resetFormKey = GlobalKey<FormState>();

  final emailController = TextEditingController();
  final otpController = TextEditingController();
  final newPasswordController = TextEditingController();
  final confirmPasswordController = TextEditingController();

  bool _isLoading = false;
  bool _isEmailVerified = false;
  bool _obscureNew = true;
  bool _obscureConfirm = true;
  String _errorMessage = '';
  String _userEmail = '';

  // Replace with your actual API URL
  final String API = 'http://192.168.0.117:5000';

  Future<void> _requestOTP() async {
    if (_isLoading) return;
    FocusScope.of(context).unfocus();

    if (!_emailFormKey.currentState!.validate()) return;

    setState(() {
      _isLoading = true;
      _errorMessage = '';
    });

    try {
      final res = await http.post(
        Uri.parse('$API/api/forgot-password'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'email': emailController.text.trim()}),
      ).timeout(const Duration(seconds: 10));

      final data = jsonDecode(res.body);

      if (!mounted) return;

      if (res.statusCode == 200 && data['success'] == true) {
        setState(() {
          _isLoading = false;
          _userEmail = emailController.text.trim();
          _isEmailVerified = true; // Move to step 2
        });
      } else {
        setState(() {
          _isLoading = false;
          _errorMessage = data['message'] ?? 'Failed to send OTP';
        });
      }
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _isLoading = false;
        _errorMessage = 'Network error. Please try again.';
      });
    }
  }

  Future<void> _submitNewPassword() async {
    if (_isLoading) return;
    FocusScope.of(context).unfocus();

    if (!_resetFormKey.currentState!.validate()) return;

    if (newPasswordController.text != confirmPasswordController.text) {
      setState(() => _errorMessage = 'Passwords do not match');
      return;
    }

    setState(() {
      _isLoading = true;
      _errorMessage = '';
    });

    try {
      final res = await http.post(
        Uri.parse('$API/api/reset-password'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'email': _userEmail,
          'otp': otpController.text.trim(),
          'new_password': newPasswordController.text,
        }),
      ).timeout(const Duration(seconds: 10));

      final data = jsonDecode(res.body);

      if (!mounted) return;
      setState(() => _isLoading = false);

      if (res.statusCode == 200 && data['success'] == true) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Password reset successfully! Please log in.'),
            backgroundColor: Colors.green,
          ),
        );
        Navigator.pop(context);
      } else {
        setState(() {
          _errorMessage = data['message'] ?? 'Failed to reset password';
        });
      }
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _isLoading = false;
        _errorMessage = 'Network error. Please try again.';
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background, // ✅ FIXED
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        iconTheme: const IconThemeData(color: AppColors.text), // ✅ FIXED
      ),
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24.0),
            child: _isEmailVerified ? _buildResetForm() : _buildEmailForm(),
          ),
        ),
      ),
    );
  }

  // Step 1: Ask for Email
  Widget _buildEmailForm() {
    return Form(
      key: _emailFormKey,
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const Icon(Icons.lock_reset_rounded, size: 80, color: AppColors.brand), // ✅ FIXED
          const SizedBox(height: 24),
          const Text(
            'Forgot Password?',
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold, color: AppColors.text), // ✅ FIXED
          ),
          const SizedBox(height: 12),
          const Text(
            'Enter your email address to receive a 1-minute OTP.',
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 14, color: AppColors.muted), // ✅ FIXED
          ),
          const SizedBox(height: 32),

          TextFormField(
            controller: emailController,
            keyboardType: TextInputType.emailAddress,
            validator: (v) {
              if (v == null || v.trim().isEmpty) return 'Email is required';
              final ok = RegExp(r'^[\w\.\-]+@[\w\-]+\.[\w\.\-]+$').hasMatch(v.trim());
              return ok ? null : 'Enter a valid email';
            },
            decoration: const InputDecoration(
              labelText: 'Email Address',
              prefixIcon: Icon(Icons.mail_outline_rounded),
            ),
          ),

          if (_errorMessage.isNotEmpty) ...[
            const SizedBox(height: 16),
            Text(_errorMessage, style: const TextStyle(color: AppColors.error, fontSize: 13)), // ✅ FIXED
          ],

          const SizedBox(height: 24),

          SizedBox(
            height: 52,
            child: ElevatedButton(
              onPressed: _isLoading ? null : _requestOTP,
              style: ElevatedButton.styleFrom(
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
              ),
              child: _isLoading
                  ? const CircularProgressIndicator(color: Colors.white)
                  : const Text('Request OTP', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
            ),
          ),
        ],
      ),
    );
  }

  // Step 2: Enter OTP & New Password
  Widget _buildResetForm() {
    return Form(
      key: _resetFormKey,
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const Icon(Icons.password_rounded, size: 80, color: AppColors.success), // ✅ FIXED
          const SizedBox(height: 24),
          const Text(
            'Create New Password',
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold, color: AppColors.text), // ✅ FIXED
          ),
          const SizedBox(height: 12),
          Text(
            'Enter the OTP sent to\n$_userEmail',
            textAlign: TextAlign.center,
            style: const TextStyle(fontSize: 14, color: AppColors.muted), // ✅ FIXED
          ),
          const SizedBox(height: 32),

          TextFormField(
            controller: otpController,
            keyboardType: TextInputType.number,
            maxLength: 6,
            textAlign: TextAlign.center,
            style: const TextStyle(fontSize: 20, letterSpacing: 8, fontWeight: FontWeight.bold),
            validator: (v) {
              if (v == null || v.isEmpty) return 'OTP is required';
              if (v.length != 6) return 'Must be 6 digits';
              return null;
            },
            decoration: const InputDecoration(
              labelText: '6-Digit OTP',
              counterText: '',
              border: OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 16),

          TextFormField(
            controller: newPasswordController,
            obscureText: _obscureNew,
            validator: (v) {
              if (v == null || v.isEmpty) return 'Password is required';
              if (v.length < 6) return 'Use at least 6 characters';
              return null;
            },
            decoration: InputDecoration(
              labelText: 'New Password',
              prefixIcon: const Icon(Icons.lock_outline_rounded),
              suffixIcon: IconButton(
                icon: Icon(_obscureNew ? Icons.visibility_off : Icons.visibility),
                onPressed: () => setState(() => _obscureNew = !_obscureNew),
              ),
            ),
          ),
          const SizedBox(height: 16),

          TextFormField(
            controller: confirmPasswordController,
            obscureText: _obscureConfirm,
            validator: (v) {
              if (v == null || v.isEmpty) return 'Please confirm password';
              return null;
            },
            decoration: InputDecoration(
              labelText: 'Confirm New Password',
              prefixIcon: const Icon(Icons.lock_outline_rounded),
              suffixIcon: IconButton(
                icon: Icon(_obscureConfirm ? Icons.visibility_off : Icons.visibility),
                onPressed: () => setState(() => _obscureConfirm = !_obscureConfirm),
              ),
            ),
          ),

          if (_errorMessage.isNotEmpty) ...[
            const SizedBox(height: 16),
            Text(_errorMessage, style: const TextStyle(color: AppColors.error, fontSize: 13)), // ✅ FIXED
          ],

          const SizedBox(height: 24),

          SizedBox(
            height: 52,
            child: ElevatedButton(
              onPressed: _isLoading ? null : _submitNewPassword,
              style: ElevatedButton.styleFrom(
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
              ),
              child: _isLoading
                  ? const CircularProgressIndicator(color: Colors.white)
                  : const Text('Reset Password', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
            ),
          ),
        ],
      ),
    );
  }
}