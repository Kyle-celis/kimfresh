import 'package:flutter/material.dart';
import '../services/api.dart';
import '../theme/colors.dart';
import 'products_screen.dart';
import 'register_screen.dart';
import 'forgot_password_screen.dart';

class LoginScreen extends StatefulWidget {
  @override
  _LoginScreenState createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final emailController = TextEditingController();
  final passwordController = TextEditingController();
  final passwordFocus = FocusNode();

  String message = '';
  bool _loading = false;
  bool _obscure = true;

  @override
  void dispose() {
    emailController.dispose();
    passwordController.dispose();
    passwordFocus.dispose();
    super.dispose();
  }

  Future<void> login() async {
    if (_loading) return;
    if (!_formKey.currentState!.validate()) return;

    FocusScope.of(context).unfocus();
    setState(() {
      _loading = true;
      message = '';
    });

    final result = await ApiService.login(
      emailController.text.trim(),
      passwordController.text,
    );

    if (!mounted) return;
    setState(() => _loading = false);

    // 1. SUCCESS (No OTP required)
    if (result != null && result['success'] == true) {
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(
          builder: (_) => ProductsScreen(
            retailerId: result['user_id'],
            retailerName: result['name'],
          ),
        ),
      );
    }
    // 2. OTP REQUIRED (Backend says require_otp = true)
    else if (result != null && result['require_otp'] == true) {
      final userId = result['user_id']; // Or result['retailer_id']
      _showOTPDialog(userId, emailController.text.trim(), passwordController.text);
    }
    // 3. ERROR
    else {
      ApiService.clearToken();
      setState(() => message = result?['message'] ?? 'Invalid credentials');
    }
  }

  Future<void> _showOTPDialog(int retailerId, String email, String password) async {
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

                    final res = await ApiService.verifyOTP(retailerId, otp);

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
          const SnackBar(content: Text('Verification successful! Logging in...'), backgroundColor: Colors.green),
        );
        setState(() => _loading = true);

        // Log in again
        final result = await ApiService.login(email, password);
        if (!mounted) return;
        setState(() => _loading = false);

        if (result != null && result['success'] == true) {
          Navigator.pushReplacement(
            context,
            MaterialPageRoute(
              builder: (_) => ProductsScreen(
                retailerId: result['user_id'],
                retailerName: result['name'],
              ),
            ),
          );
        } else {
          setState(() => message = result?['message'] ?? 'Login failed after OTP.');
        }
      }
    }
  }

  InputDecoration _decoration({
    required String label,
    required IconData icon,
    Widget? suffix,
  }) {
    return InputDecoration(
      labelText: label,
      labelStyle: const TextStyle(color: AppColors.muted, fontSize: 14),
      floatingLabelStyle: const TextStyle(color: AppColors.brand, fontSize: 14),
      prefixIcon: Icon(icon, color: AppColors.muted, size: 20),
      suffixIcon: suffix,
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 24),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 400),
              child: Form(
                key: _formKey,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    // ---- Brand mark ----
                    Center(
                      child: Container(
                        height: 72,
                        width: 72,
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: AppColors.brand,
                          borderRadius: BorderRadius.circular(AppColors.rXl),
                        ),
                        child: Image.asset(
                          'assets/logo.png',
                          fit: BoxFit.contain,
                          errorBuilder: (_, __, ___) => const Icon(
                            Icons.storefront_rounded,
                            color: Colors.white,
                            size: 32,
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(height: 24),
                    const Text(
                      'Sign in',
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        fontSize: 26,
                        fontWeight: FontWeight.w700,
                        color: AppColors.text,
                        letterSpacing: -0.5,
                      ),
                    ),
                    const SizedBox(height: 6),
                    const Text(
                      'KimFresh Retailer Portal',
                      textAlign: TextAlign.center,
                      style: TextStyle(fontSize: 14, color: AppColors.muted),
                    ),
                    const SizedBox(height: 32),
                    Container(
                      padding: const EdgeInsets.all(20),
                      decoration: BoxDecoration(
                        color: AppColors.surface,
                        borderRadius: BorderRadius.circular(AppColors.rXl),
                        border: Border.all(color: AppColors.border),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          TextFormField(
                            controller: emailController,
                            keyboardType: TextInputType.emailAddress,
                            textInputAction: TextInputAction.next,
                            autocorrect: false,
                            decoration: _decoration(
                              label: 'Email',
                              icon: Icons.mail_outline_rounded,
                            ),
                            validator: (v) {
                              final value = v?.trim() ?? '';
                              if (value.isEmpty) return 'Enter your email';
                              final emailRegex = RegExp(r'^[\w\.\-]+@[\w\-]+\.[\w\.\-]+$');
                              if (!emailRegex.hasMatch(value)) {
                                return 'Enter a valid email';
                              }
                              return null;
                            },
                            onFieldSubmitted: (_) => FocusScope.of(context).requestFocus(passwordFocus),
                          ),
                          const SizedBox(height: 16),
                          TextFormField(
                            controller: passwordController,
                            focusNode: passwordFocus,
                            obscureText: _obscure,
                            textInputAction: TextInputAction.done,
                            decoration: _decoration(
                              label: 'Password',
                              icon: Icons.lock_outline_rounded,
                              suffix: IconButton(
                                icon: Icon(
                                  _obscure ? Icons.visibility_off_outlined : Icons.visibility_outlined,
                                  color: AppColors.muted,
                                  size: 20,
                                ),
                                onPressed: () => setState(() => _obscure = !_obscure),
                              ),
                            ),
                            validator: (v) => (v == null || v.isEmpty) ? 'Enter your password' : null,
                            onFieldSubmitted: (_) => login(),
                          ),

                          // 👇 FORGOT PASSWORD BUTTON 👇
                          Align(
                            alignment: Alignment.centerRight,
                            child: TextButton(
                              onPressed: () {
                                Navigator.push(
                                  context,
                                  MaterialPageRoute(builder: (_) => ForgotPasswordScreen()),
                                );
                              },
                              style: TextButton.styleFrom(
                                padding: EdgeInsets.zero,
                                minimumSize: const Size(0, 30),
                                tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                              ),
                              child: const Text(
                                'Forgot Password?',
                                style: TextStyle(
                                  fontSize: 13,
                                  fontWeight: FontWeight.w600,
                                  color: AppColors.brand,
                                ),
                              ),
                            ),
                          ),
                          // 👆 END OF FORGOT PASSWORD 👆

                          if (message.isNotEmpty) ...[
                            const SizedBox(height: 16),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                              decoration: BoxDecoration(
                                color: AppColors.error.withOpacity(0.06),
                                borderRadius: BorderRadius.circular(8),
                                border: Border.all(color: AppColors.error.withOpacity(0.25)),
                              ),
                              child: Row(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  const Icon(Icons.error_outline_rounded, color: AppColors.error, size: 18),
                                  const SizedBox(width: 8),
                                  Expanded(
                                    child: Text(
                                      message,
                                      style: const TextStyle(color: AppColors.error, fontSize: 13, height: 1.4),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ],
                          const SizedBox(height: 24),
                          SizedBox(
                            height: 50,
                            child: ElevatedButton(
                              onPressed: _loading ? null : login,
                              child: _loading
                                  ? const SizedBox(
                                height: 20,
                                width: 20,
                                child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                              )
                                  : const Text('Sign in'),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 24),
                    Row(
                      children: [
                        Expanded(child: Divider(color: Colors.grey.shade300)),
                        Padding(
                          padding: const EdgeInsets.symmetric(horizontal: 12),
                          child: Text('New to KimFresh?', style: TextStyle(fontSize: 13, color: AppColors.muted)),
                        ),
                        Expanded(child: Divider(color: Colors.grey.shade300)),
                      ],
                    ),
                    const SizedBox(height: 16),
                    SizedBox(
                      height: 50,
                      child: OutlinedButton(
                        onPressed: () {
                          Navigator.push(
                            context,
                            MaterialPageRoute(builder: (_) => RegisterScreen()),
                          );
                        },
                        child: const Text('Create an account'),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}