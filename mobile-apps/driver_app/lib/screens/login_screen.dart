import 'package:flutter/material.dart';
import '../services/api.dart';
import '../theme/app_theme.dart';
import 'signup_screen.dart';
import 'deliveries_screen.dart';
import 'forgot_password_screen.dart'; // Added import

class LoginScreen extends StatefulWidget {
  @override
  _LoginScreenState createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final emailController = TextEditingController();
  final passwordController = TextEditingController();

  bool _obscure = true;
  bool _isLoading = false;
  String message = '';

  @override
  void dispose() {
    emailController.dispose();
    passwordController.dispose();
    super.dispose();
  }

  Future<void> login() async {
    if (_isLoading) return;
    FocusScope.of(context).unfocus();

    if (!_formKey.currentState!.validate()) return;

    final email = emailController.text.trim();
    final password = passwordController.text;

    setState(() {
      _isLoading = true;
      message = '';
    });

    final result = await ApiService.login(email, password);

    if (!mounted) return;
    setState(() => _isLoading = false);

    if (result != null && result['success'] == true) {
      // 1. NORMAL SUCCESS (Already verified)
      _navigateToHome(result);

    } else if (result != null && result['require_otp'] == true) {
      // 2. OTP REQUIRED
      _showOTPDialog(result['driver_id'], email, password);

    } else {
      // 3. ERROR (Pending approval, wrong password, etc.)
      setState(() {
        message = result?['message'] ?? 'Login failed';
      });
    }
  }

  void _navigateToHome(Map<String, dynamic> result) {
    final token = result['token'];
    if (token is String) ApiService.setToken(token);

    final dynamic rawId = result['driver_id'] ?? result['user_id'];
    final int driverId = int.tryParse(rawId.toString()) ?? 0;
    final String driverName = result['name']?.toString() ?? 'Driver';

    Navigator.pushReplacement(
      context,
      MaterialPageRoute(
        builder: (_) => DeliveriesScreen(
          driverId: driverId,
          driverName: driverName,
        ),
      ),
    );
  }

  Future<void> _showOTPDialog(int driverId, String email, String password) async {
    final TextEditingController otpController = TextEditingController();
    bool isVerifying = false;
    String dialogError = '';

    final bool? verified = await showDialog<bool>(
      context: context,
      barrierDismissible: false,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setDialogState) {
            return AlertDialog(
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
              title: const Text('OTP Required'),
              content: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Text(
                    'Your account has been approved! Enter the 6-digit OTP sent to your email.',
                    style: TextStyle(fontSize: 14),
                  ),
                  const SizedBox(height: 16),
                  TextField(
                    controller: otpController,
                    keyboardType: TextInputType.number,
                    maxLength: 6,
                    textAlign: TextAlign.center,
                    style: const TextStyle(fontSize: 24, letterSpacing: 8, fontWeight: FontWeight.bold),
                    decoration: InputDecoration(
                      hintText: '000000',
                      counterText: '',
                      border: const OutlineInputBorder(),
                      errorText: dialogError.isEmpty ? null : dialogError,
                    ),
                  ),
                ],
              ),
              actions: [
                TextButton(
                  onPressed: isVerifying ? null : () => Navigator.pop(context, false),
                  child: const Text('Cancel', style: TextStyle(color: Colors.grey)),
                ),
                ElevatedButton(
                  onPressed: isVerifying
                      ? null
                      : () async {
                    final otp = otpController.text.trim();
                    if (otp.length != 6) {
                      setDialogState(() => dialogError = 'Must be 6 digits');
                      return;
                    }

                    setDialogState(() {
                      isVerifying = true;
                      dialogError = '';
                    });

                    final res = await ApiService.verifyOTP(driverId, otp);

                    if (res['success'] == true) {
                      Navigator.pop(context, true); // Close dialog on success
                    } else {
                      setDialogState(() {
                        isVerifying = false;
                        dialogError = res['message'] ?? 'Invalid OTP';
                      });
                    }
                  },
                  child: isVerifying
                      ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                      : const Text('Verify'),
                ),
              ],
            );
          },
        );
      },
    );

    if (verified == true) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Verification successful! Logging in...'),
            backgroundColor: Colors.green,
          ),
        );

        setState(() => _isLoading = true);

        final result = await ApiService.login(email, password);

        if (!mounted) return;
        setState(() => _isLoading = false);

        if (result != null && result['success'] == true) {
          _navigateToHome(result);
        } else {
          setState(() {
            message = result?['message'] ?? 'Login failed after OTP.';
          });
        }
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      // 👇 REVERTED TO WHITE BACKGROUND HERE 👇
      backgroundColor: Colors.white,
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24.0),
            child: Form(
              key: _formKey,
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Image.asset('assets/logo.png', height: 100, fit: BoxFit.contain),
                  const SizedBox(height: 32),
                  const Text(
                    'Welcome Back',
                    textAlign: TextAlign.center,
                    style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
                  ),
                  const SizedBox(height: 32),

                  TextFormField(
                    controller: emailController,
                    keyboardType: TextInputType.emailAddress,
                    validator: (v) => (v == null || v.trim().isEmpty) ? 'Email is required' : null,
                    // Ensure text is dark on white
                    style: const TextStyle(color: Colors.black87),
                    decoration: InputDecoration(
                      labelText: 'Email',
                      labelStyle: TextStyle(color: Colors.grey.shade600),
                      prefixIcon: Icon(Icons.mail_outline_rounded, color: Colors.grey.shade600),
                      fillColor: Colors.white,
                      filled: true,
                    ),
                  ),
                  const SizedBox(height: 16),

                  TextFormField(
                    controller: passwordController,
                    obscureText: _obscure,
                    validator: (v) => (v == null || v.isEmpty) ? 'Password is required' : null,
                    style: const TextStyle(color: Colors.black87),
                    decoration: InputDecoration(
                      labelText: 'Password',
                      labelStyle: TextStyle(color: Colors.grey.shade600),
                      prefixIcon: Icon(Icons.lock_outline_rounded, color: Colors.grey.shade600),
                      suffixIcon: IconButton(
                        icon: Icon(_obscure ? Icons.visibility_off : Icons.visibility, color: Colors.grey.shade600),
                        onPressed: () => setState(() => _obscure = !_obscure),
                      ),
                      fillColor: Colors.white,
                      filled: true,
                    ),
                  ),

                  Align(
                    alignment: Alignment.centerRight,
                    child: TextButton(
                      onPressed: () {
                        Navigator.push(
                          context,
                          MaterialPageRoute(builder: (_) => ForgotPasswordScreen()),
                        );
                      },
                      child: const Text(
                        'Forgot Password?',
                        style: TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.w600,
                          color: AppColors.burgundy,
                        ),
                      ),
                    ),
                  ),

                  if (message.isNotEmpty) ...[
                    const SizedBox(height: 8),
                    Text(message, style: const TextStyle(color: AppColors.danger, fontSize: 13)),
                  ],

                  const SizedBox(height: 24),

                  SizedBox(
                    height: 52,
                    child: ElevatedButton(
                      onPressed: _isLoading ? null : login,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppColors.burgundy, // Consistent button color
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                      ),
                      child: _isLoading
                          ? const CircularProgressIndicator(color: Colors.white)
                          : const Text('Sign In', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.white)),
                    ),
                  ),

                  const SizedBox(height: 16),

                  TextButton(
                    onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => SignupScreen())),
                    child: const Text('Don\'t have an account? Sign up'),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}