import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../services/api.dart';
import '../theme/colors.dart';
import 'products_screen.dart'; // 👈 Needed for navigation after OTP

class RegisterScreen extends StatefulWidget {
  @override
  _RegisterScreenState createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen> {
  final _formKey = GlobalKey<FormState>();

  final firstNameController = TextEditingController();
  final lastNameController = TextEditingController();
  final emailController = TextEditingController();
  final passwordController = TextEditingController();
  final phoneController = TextEditingController();

  bool _obscure = true;
  String message = '';
  bool _loading = false;

  @override
  void dispose() {
    firstNameController.dispose();
    lastNameController.dispose();
    emailController.dispose();
    passwordController.dispose();
    phoneController.dispose();
    super.dispose();
  }

  Future<void> register() async {
    if (_loading) return;
    FocusScope.of(context).unfocus();

    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _loading = true;
      message = '';
    });

    final String fullName = '${firstNameController.text.trim()} ${lastNameController.text.trim()}';

    final result = await ApiService.register(
      fullName,
      emailController.text.trim(),
      passwordController.text,
      phoneController.text.trim(),
    );

    if (!mounted) return;
    setState(() => _loading = false);

    if (result != null && result['success'] == true) {
      // SUCCESS! Show the OTP Dialog immediately
      final retailerId = result['retailer_id']; // Make sure Flask sends this back
      _showOTPDialog(retailerId, emailController.text.trim(), passwordController.text);
    } else {
      setState(() => message = result?['message'] ?? 'Registration failed');
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
              title: const Text('Verify Account'),
              content: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Text(
                    'Enter the 6-digit OTP sent to your email to activate your account.',
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

                    final res = await ApiService.verifyRetailerOTP(retailerId, otp);

                    if (res['success'] == true) {
                      Navigator.pop(context, true);
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
      // OTP Success! Log them in automatically
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Account verified! Logging you in...'),
            backgroundColor: Colors.green,
          ),
        );

        setState(() => _loading = true);

        // Call login API to get the token
        final result = await ApiService.login(email, password);

        if (!mounted) return;
        setState(() => _loading = false);

        if (result != null && result['success'] == true) {
          // Navigate to home
          Navigator.pushAndRemoveUntil(
            context,
            MaterialPageRoute(
              builder: (_) => ProductsScreen(
                retailerId: result['user_id'],
                retailerName: result['name'],
              ),
            ),
                (route) => false,
          );
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
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Create Retailer Account'),
        backgroundColor: Colors.transparent,
        elevation: 0,
        iconTheme: const IconThemeData(color: AppColors.text),
      ),
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24.0),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 400),
              child: Form(
                key: _formKey,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Expanded(
                          child: TextFormField(
                            controller: firstNameController,
                            textCapitalization: TextCapitalization.words,
                            validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null,
                            decoration: const InputDecoration(
                              labelText: 'First name *',
                              prefixIcon: Icon(Icons.person_outline),
                            ),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: TextFormField(
                            controller: lastNameController,
                            textCapitalization: TextCapitalization.words,
                            validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null,
                            decoration: const InputDecoration(
                              labelText: 'Last name *',
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),

                    TextFormField(
                      controller: emailController,
                      keyboardType: TextInputType.emailAddress,
                      validator: (v) {
                        if (v == null || v.trim().isEmpty) return 'Email is required';
                        final ok = RegExp(r'^[\w\.\-]+@[\w\-]+\.[\w\.\-]+$').hasMatch(v.trim());
                        return ok ? null : 'Enter a valid email';
                      },
                      decoration: const InputDecoration(
                        labelText: 'Email *',
                        prefixIcon: Icon(Icons.mail_outline),
                      ),
                    ),
                    const SizedBox(height: 16),

                    TextFormField(
                      controller: passwordController,
                      obscureText: _obscure,
                      validator: (v) {
                        if (v == null || v.isEmpty) return 'Password is required';
                        if (v.length < 6) return 'Use at least 6 characters';
                        return null;
                      },
                      decoration: InputDecoration(
                        labelText: 'Password *',
                        prefixIcon: const Icon(Icons.lock_outline),
                        suffixIcon: IconButton(
                          icon: Icon(_obscure ? Icons.visibility_off : Icons.visibility),
                          onPressed: () => setState(() => _obscure = !_obscure),
                        ),
                      ),
                    ),
                    const SizedBox(height: 16),

                    TextFormField(
                      controller: phoneController,
                      keyboardType: TextInputType.phone,
                      inputFormatters: [
                        FilteringTextInputFormatter.digitsOnly,
                        LengthLimitingTextInputFormatter(11),
                      ],
                      validator: (v) {
                        if (v == null || v.isEmpty) return 'Phone number is required';
                        if (!v.startsWith('09')) return 'Must start with 09';
                        if (v.length != 11) return 'Must be exactly 11 digits';
                        return null;
                      },
                      decoration: const InputDecoration(
                        labelText: 'Phone number *',
                        hintText: '09XXXXXXXXX',
                        prefixIcon: Icon(Icons.phone_outlined),
                      ),
                    ),

                    if (message.isNotEmpty) ...[
                      const SizedBox(height: 16),
                      Text(message, style: const TextStyle(color: AppColors.error, fontSize: 13)),
                    ],

                    const SizedBox(height: 32),

                    SizedBox(
                      height: 52,
                      child: ElevatedButton(
                        onPressed: _loading ? null : register,
                        style: ElevatedButton.styleFrom(
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                        ),
                        child: _loading
                            ? const CircularProgressIndicator(color: Colors.white)
                            : const Text('Create Account', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
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