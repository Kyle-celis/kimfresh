import 'package:flutter/material.dart';
import 'package:flutter/services.dart'; // For HapticFeedback
import '../services/api.dart';
import '../theme/app_theme.dart';
import 'role_selection_screen.dart';
import 'signup_screen.dart';
import 'register_screen.dart';
import 'bluetooth_gate.dart';
import '../services/location_gate.dart';
import 'deliveries_screen.dart';
import 'forgot_password_screen.dart';
import 'products_screen.dart';

class LoginScreen extends StatefulWidget {
  final String role;
  const LoginScreen({super.key, required this.role});

  @override
  _LoginScreenState createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final emailController = TextEditingController();
  final passwordController = TextEditingController();
  final _emailFocus = FocusNode();
  final _passwordFocus = FocusNode();

  bool _obscure = true;
  bool _isLoading = false;
  String message = '';

  @override
  void dispose() {
    emailController.dispose();
    passwordController.dispose();
    _emailFocus.dispose();
    _passwordFocus.dispose();
    super.dispose();
  }

  Future<void> login() async {
    if (_isLoading) return;
    FocusScope.of(context).unfocus();
    if (!_formKey.currentState!.validate()) return;

    HapticFeedback.mediumImpact();

    final email = emailController.text.trim();
    final password = passwordController.text;

    setState(() {
      _isLoading = true;
      message = '';
    });

    final result = await ApiService.login(email, password, role: widget.role);

    if (!mounted) return;
    setState(() => _isLoading = false);

    if (result != null && result['success'] == true) {
      _navigateToHome(result);
    } else if (result != null && result['require_otp'] == true) {
      final userId = result['driver_id'] ?? result['user_id'];
      _showOTPDialog(userId, email, password);
    } else {
      setState(() => message = result?['message'] ?? 'Login failed');
    }
  }

  void _navigateToHome(Map<String, dynamic> result) {
    final token = result['token'];
    if (token is String) ApiService.setToken(token);

    final dynamic rawId = result['driver_id'] ?? result['user_id'] ?? result['retailer_id'];
    final int userId = int.tryParse(rawId.toString()) ?? 0;
    final String userName = result['name']?.toString() ?? 'User';

    if (widget.role == 'driver') {
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(
          builder: (_) => LocationGate(
            child: BluetoothGate(
              child: DeliveriesScreen(driverId: userId, driverName: userName),
            ),
          ),
        ),
      );
    } else {
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(
          builder: (_) => ProductsScreen(retailerId: userId, retailerName: userName),
        ),
      );
    }
  }

  Future<void> _showOTPDialog(int userId, String email, String password) async {
    final TextEditingController otpController = TextEditingController();
    bool isVerifying = false;
    String dialogError = '';

    final bool? verified = await showDialog<bool>(
      context: context,
      barrierDismissible: false,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setDialogState) {
            Future<void> resendOtp() async {
              HapticFeedback.selectionClick();
              setDialogState(() {
                isVerifying = true;
                dialogError = '';
              });
              final res = await ApiService.login(email, password, role: widget.role);
              setDialogState(() => isVerifying = false);

              if (res != null && res['require_otp'] == true) {
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(
                    content: Text('A new OTP has been sent to your email!'),
                    backgroundColor: AppColors.success,
                  ),
                );
              } else {
                setDialogState(() => dialogError = 'Failed to resend OTP. Try again.');
              }
            }

            return AlertDialog(
              backgroundColor: Colors.white,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
              titlePadding: const EdgeInsets.fromLTRB(24, 24, 24, 8),
              contentPadding: const EdgeInsets.fromLTRB(24, 8, 24, 8),
              title: Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: AppColors.brand.withOpacity(0.1),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: const Icon(Icons.verified_user_rounded, color: AppColors.brand, size: 22),
                  ),
                  const SizedBox(width: 12),
                  const Text('OTP Required', style: TextStyle(fontWeight: FontWeight.bold)),
                ],
              ),
              content: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Text(
                    'Your account has been approved! Enter the 6-digit OTP sent to your email.',
                    style: TextStyle(fontSize: 14, color: AppColors.textSecondary, height: 1.4),
                  ),
                  const SizedBox(height: 20),
                  TextField(
                    controller: otpController,
                    keyboardType: TextInputType.number,
                    maxLength: 6,
                    textAlign: TextAlign.center,
                    style: const TextStyle(
                      fontSize: 26,
                      letterSpacing: 10,
                      fontWeight: FontWeight.bold,
                      color: AppColors.textPrimary,
                    ),
                    decoration: InputDecoration(
                      hintText: '000000',
                      hintStyle: TextStyle(color: AppColors.textSecondary.withOpacity(0.4), letterSpacing: 10),
                      counterText: '',
                      filled: true,
                      fillColor: AppColors.background,
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(14),
                        borderSide: BorderSide.none,
                      ),
                      enabledBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(14),
                        borderSide: BorderSide.none,
                      ),
                      focusedBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(14),
                        borderSide: const BorderSide(color: AppColors.brand, width: 2),
                      ),
                      errorText: dialogError.isEmpty ? null : dialogError,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Text(
                        "Didn't receive the code?",
                        style: TextStyle(fontSize: 12, color: AppColors.textSecondary),
                      ),
                      TextButton(
                        onPressed: isVerifying ? null : resendOtp,
                        style: TextButton.styleFrom(
                          padding: const EdgeInsets.only(left: 8),
                          minimumSize: Size.zero,
                          tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                        ),
                        child: const Text(
                          'Resend',
                          style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: AppColors.brand),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
              actionsPadding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
              actions: [
                TextButton(
                  onPressed: isVerifying ? null : () {
                    HapticFeedback.lightImpact();
                    Navigator.pop(context, false);
                  },
                  child: Text('Cancel', style: TextStyle(color: AppColors.textSecondary)),
                ),
                ElevatedButton(
                  onPressed: isVerifying
                      ? null
                      : () async {
                    HapticFeedback.mediumImpact();
                    final otp = otpController.text.trim();
                    if (otp.length != 6) {
                      setDialogState(() => dialogError = 'Must be 6 digits');
                      return;
                    }
                    setDialogState(() {
                      isVerifying = true;
                      dialogError = '';
                    });
                    final res = widget.role == 'driver'
                        ? await ApiService.verifyOTP(userId, otp)
                        : await ApiService.verifyRetailerOTP(userId, otp);

                    if (res['success'] == true) {
                      Navigator.pop(context, true);
                    } else {
                      setDialogState(() {
                        isVerifying = false;
                        dialogError = res['message'] ?? 'Invalid OTP';
                      });
                    }
                  },
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.brand,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(horizontal: 28, vertical: 12),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  child: isVerifying
                      ? const SizedBox(
                    width: 16,
                    height: 16,
                    child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                  )
                      : const Text('Verify', style: TextStyle(fontWeight: FontWeight.bold)),
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
            backgroundColor: AppColors.success,
          ),
        );
        setState(() => _isLoading = true);
        final result = await ApiService.login(email, password, role: widget.role);
        if (!mounted) return;
        setState(() => _isLoading = false);

        if (result != null && result['success'] == true) {
          _navigateToHome(result);
        } else {
          setState(() => message = result?['message'] ?? 'Login failed after OTP.');
        }
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDriver = widget.role == 'driver';
    final title = isDriver ? 'Driver Login' : 'Retailer Login';
    final subtitle = isDriver
        ? 'Sign in to manage your deliveries'
        : 'Sign in to order fresh products';

    return Scaffold(
      backgroundColor: AppColors.canvas, // Matched to RoleSelectionScreen
      body: GestureDetector(
        onTap: () => FocusScope.of(context).unfocus(),
        child: Stack(
          children: [
            // ── 1. Ultra-Soft Blurred Background Blobs ──
            Positioned(
              top: -100,
              left: -100,
              child: _buildBlurredBlob(AppColors.burgundy.withOpacity(0.15), 320),
            ),
            Positioned(
              bottom: -50,
              right: -100,
              child: _buildBlurredBlob(AppColors.burgundy.withOpacity(0.10), 280),
            ),

            // ── Main Content ──
            SafeArea(
              child: Stack(
                children: [
                  // ── Back Button ──
                  Positioned(
                    top: 8,
                    left: 8,
                    child: IconButton(
                      icon: const Icon(Icons.arrow_back_ios_new_rounded, size: 20, color: AppColors.brand),
                      onPressed: () {
                        HapticFeedback.lightImpact();
                        Navigator.pushReplacement(
                          context,
                          MaterialPageRoute(builder: (_) => const RoleSelectionScreen()),
                        );
                      },
                    ),
                  ),

                  // ── Scrollable Form ──
                  Center(
                    child: SingleChildScrollView(
                      padding: const EdgeInsets.fromLTRB(24, 60, 24, 24),
                      child: Form(
                        key: _formKey,
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [
                            // ── Elevated Logo Presentation (Matched) ──
                            _EntranceAnimation(
                              delay: 0,
                              child: Center(
                                child: Container(
                                  padding: const EdgeInsets.all(20),
                                  decoration: BoxDecoration(
                                    color: Colors.white,
                                    shape: BoxShape.circle,
                                    boxShadow: [
                                      BoxShadow(
                                        color: AppColors.burgundy.withOpacity(0.15),
                                        blurRadius: 40,
                                        spreadRadius: 10,
                                        offset: const Offset(0, 10),
                                      ),
                                    ],
                                  ),
                                  child: Image.asset(
                                    'assets/logo.png',
                                    height: 70, // Slightly smaller to fit login screen
                                    fit: BoxFit.contain,
                                    color: AppColors.textPrimary,
                                  ),
                                ),
                              ),
                            ),
                            const SizedBox(height: 36),

                            // ── Title & Subtitle ──
                            _EntranceAnimation(
                              delay: 100,
                              child: Column(
                                children: [
                                  Text(
                                    title,
                                    textAlign: TextAlign.center,
                                    style: const TextStyle(
                                      fontSize: 28,
                                      fontWeight: FontWeight.w900,
                                      color: AppColors.textPrimary,
                                      letterSpacing: -0.8,
                                    ),
                                  ),
                                  const SizedBox(height: 8),
                                  Text(
                                    subtitle,
                                    textAlign: TextAlign.center,
                                    style: const TextStyle(
                                      fontSize: 15,
                                      color: AppColors.textSecondary,
                                      fontWeight: FontWeight.w500,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            const SizedBox(height: 36),

                            // ── Input Fields ──
                            _EntranceAnimation(
                              delay: 200,
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.stretch,
                                children: [
                                  const _FieldLabel(label: 'Email address'),
                                  const SizedBox(height: 8),
                                  TextFormField(
                                    controller: emailController,
                                    focusNode: _emailFocus,
                                    keyboardType: TextInputType.emailAddress,
                                    textInputAction: TextInputAction.next,
                                    onFieldSubmitted: (_) => _passwordFocus.requestFocus(),
                                    validator: (v) => (v == null || v.trim().isEmpty) ? 'Email is required' : null,
                                    style: const TextStyle(color: AppColors.textPrimary, fontSize: 15),
                                    decoration: InputDecoration(
                                      hintText: 'you@example.com',
                                      hintStyle: TextStyle(color: AppColors.textSecondary.withOpacity(0.6), fontSize: 14),
                                      prefixIcon: const Padding(
                                        padding: EdgeInsets.only(left: 4),
                                        child: Icon(Icons.mail_outline_rounded, color: AppColors.textSecondary, size: 20),
                                      ),
                                      prefixIconConstraints: const BoxConstraints(minWidth: 48),
                                      filled: true,
                                      fillColor: Colors.white,
                                      contentPadding: const EdgeInsets.symmetric(vertical: 18, horizontal: 12),
                                      border: OutlineInputBorder(
                                        borderRadius: BorderRadius.circular(20),
                                        borderSide: const BorderSide(color: AppColors.border),
                                      ),
                                      enabledBorder: OutlineInputBorder(
                                        borderRadius: BorderRadius.circular(20),
                                        borderSide: const BorderSide(color: AppColors.border),
                                      ),
                                      focusedBorder: OutlineInputBorder(
                                        borderRadius: BorderRadius.circular(20),
                                        borderSide: const BorderSide(color: AppColors.brand, width: 2),
                                      ),
                                      errorBorder: OutlineInputBorder(
                                        borderRadius: BorderRadius.circular(20),
                                        borderSide: const BorderSide(color: AppColors.danger),
                                      ),
                                    ),
                                  ),
                                  const SizedBox(height: 18),
                                  const _FieldLabel(label: 'Password'),
                                  const SizedBox(height: 8),
                                  TextFormField(
                                    controller: passwordController,
                                    focusNode: _passwordFocus,
                                    obscureText: _obscure,
                                    textInputAction: TextInputAction.done,
                                    onFieldSubmitted: (_) => login(),
                                    validator: (v) => (v == null || v.isEmpty) ? 'Password is required' : null,
                                    style: const TextStyle(color: AppColors.textPrimary, fontSize: 15),
                                    decoration: InputDecoration(
                                      hintText: '••••••••',
                                      hintStyle: TextStyle(color: AppColors.textSecondary.withOpacity(0.6), fontSize: 14),
                                      prefixIcon: const Padding(
                                        padding: EdgeInsets.only(left: 4),
                                        child: Icon(Icons.lock_outline_rounded, color: AppColors.textSecondary, size: 20),
                                      ),
                                      prefixIconConstraints: const BoxConstraints(minWidth: 48),
                                      suffixIcon: IconButton(
                                        icon: Icon(
                                          _obscure ? Icons.visibility_off_rounded : Icons.visibility_rounded,
                                          color: AppColors.textSecondary,
                                          size: 20,
                                        ),
                                        onPressed: () {
                                          HapticFeedback.selectionClick();
                                          setState(() => _obscure = !_obscure);
                                        },
                                      ),
                                      filled: true,
                                      fillColor: Colors.white,
                                      contentPadding: const EdgeInsets.symmetric(vertical: 18, horizontal: 12),
                                      border: OutlineInputBorder(
                                        borderRadius: BorderRadius.circular(20),
                                        borderSide: const BorderSide(color: AppColors.border),
                                      ),
                                      enabledBorder: OutlineInputBorder(
                                        borderRadius: BorderRadius.circular(20),
                                        borderSide: const BorderSide(color: AppColors.border),
                                      ),
                                      focusedBorder: OutlineInputBorder(
                                        borderRadius: BorderRadius.circular(20),
                                        borderSide: const BorderSide(color: AppColors.brand, width: 2),
                                      ),
                                      errorBorder: OutlineInputBorder(
                                        borderRadius: BorderRadius.circular(20),
                                        borderSide: const BorderSide(color: AppColors.danger),
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                            ),

                            // ── Forgot Password ──
                            _EntranceAnimation(
                              delay: 300,
                              child: Align(
                                alignment: Alignment.centerRight,
                                child: TextButton(
                                  onPressed: () {
                                    HapticFeedback.lightImpact();
                                    Navigator.push(
                                      context,
                                      MaterialPageRoute(builder: (_) =>  ForgotPasswordScreen()),
                                    );
                                  },
                                  style: TextButton.styleFrom(
                                    padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 8),
                                    minimumSize: Size.zero,
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
                            ),

                            // ── Error Message ──
                            if (message.isNotEmpty) ...[
                              const SizedBox(height: 8),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                                decoration: BoxDecoration(
                                  color: AppColors.danger.withOpacity(0.08),
                                  borderRadius: BorderRadius.circular(12),
                                  border: Border.all(color: AppColors.danger.withOpacity(0.2)),
                                ),
                                child: Row(
                                  children: [
                                    const Icon(Icons.error_outline_rounded, color: AppColors.danger, size: 18),
                                    const SizedBox(width: 10),
                                    Expanded(
                                      child: Text(
                                        message,
                                        style: const TextStyle(color: AppColors.danger, fontSize: 13),
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ],

                            const SizedBox(height: 28),

                            // ── Sign In Button ──
                            _EntranceAnimation(
                              delay: 400,
                              child: SizedBox(
                                height: 54,
                                child: ElevatedButton(
                                  onPressed: _isLoading ? null : login,
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: AppColors.brand,
                                    foregroundColor: Colors.white,
                                    disabledBackgroundColor: AppColors.brand.withOpacity(0.5),
                                    elevation: 0,
                                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                                  ),
                                  child: _isLoading
                                      ? const SizedBox(
                                    width: 22,
                                    height: 22,
                                    child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2.5),
                                  )
                                      : const Row(
                                    mainAxisAlignment: MainAxisAlignment.center,
                                    children: [
                                      Text(
                                        'Sign In',
                                        style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                                      ),
                                      SizedBox(width: 8),
                                      Icon(Icons.arrow_forward_rounded, size: 20),
                                    ],
                                  ),
                                ),
                              ),
                            ),

                            const SizedBox(height: 24),

                            // ── Divider ──
                            _EntranceAnimation(
                              delay: 500,
                              child: Row(
                                children: [
                                  const Expanded(child: Divider(color: AppColors.border, thickness: 1)),
                                  Padding(
                                    padding: const EdgeInsets.symmetric(horizontal: 12),
                                    child: Text(
                                      'OR',
                                      style: TextStyle(
                                        fontSize: 12,
                                        fontWeight: FontWeight.w600,
                                        color: AppColors.textSecondary.withOpacity(0.7),
                                        letterSpacing: 0.5,
                                      ),
                                    ),
                                  ),
                                  const Expanded(child: Divider(color: AppColors.border, thickness: 1)),
                                ],
                              ),
                            ),

                            const SizedBox(height: 20),

                            // ── Sign Up Prompt ──
                            _EntranceAnimation(
                              delay: 600,
                              child: Row(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  Text(
                                    isDriver ? "Don't have an account?" : "New retailer?",
                                    style: const TextStyle(fontSize: 14, color: AppColors.textSecondary),
                                  ),
                                  TextButton(
                                    onPressed: () {
                                      HapticFeedback.lightImpact();
                                      Navigator.push(
                                        context,
                                        MaterialPageRoute(
                                          builder: (_) => isDriver ?  SignupScreen() :  RegisterScreen(),
                                        ),
                                      );
                                    },
                                    style: TextButton.styleFrom(
                                      padding: const EdgeInsets.only(left: 6),
                                      minimumSize: Size.zero,
                                      tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                                    ),
                                    child: const Text(
                                      'Sign Up',
                                      style: TextStyle(
                                        fontSize: 14,
                                        fontWeight: FontWeight.bold,
                                        color: AppColors.brand,
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  // Helper to generate smooth, blurred background blobs
  Widget _buildBlurredBlob(Color color, double size) {
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        color: color,
        shape: BoxShape.circle,
        boxShadow: [
          BoxShadow(color: color, blurRadius: 60, spreadRadius: 20),
        ],
      ),
    );
  }
}

/// Small label above each input field for a cleaner, modern look.
class _FieldLabel extends StatelessWidget {
  final String label;
  const _FieldLabel({required this.label});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(left: 4),
      child: Text(
        label,
        style: const TextStyle(
          fontSize: 13,
          fontWeight: FontWeight.w600,
          color: AppColors.textPrimary,
        ),
      ),
    );
  }
}

/// Smooth Fade + Slide Up Entrance Animation Widget
class _EntranceAnimation extends StatefulWidget {
  final Widget child;
  final int delay;

  const _EntranceAnimation({required this.child, required this.delay});

  @override
  State<_EntranceAnimation> createState() => _EntranceAnimationState();
}

class _EntranceAnimationState extends State<_EntranceAnimation> with SingleTickerProviderStateMixin {
  late AnimationController _controller;
  late Animation<double> _fadeAnimation;
  late Animation<Offset> _slideAnimation;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 600),
    );

    _fadeAnimation = Tween<double>(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(parent: _controller, curve: Curves.easeOut),
    );

    _slideAnimation = Tween<Offset>(begin: const Offset(0, 0.15), end: Offset.zero).animate(
      CurvedAnimation(parent: _controller, curve: Curves.easeOutCubic),
    );

    Future.delayed(Duration(milliseconds: widget.delay), () {
      if (mounted) _controller.forward();
    });
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return FadeTransition(
      opacity: _fadeAnimation,
      child: SlideTransition(
        position: _slideAnimation,
        child: widget.child,
      ),
    );
  }
}