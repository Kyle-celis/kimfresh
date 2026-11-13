import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../services/api.dart';
import '../theme/colors.dart';
import 'login_screen.dart';

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
  final addressController = TextEditingController();

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
    addressController.dispose();
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
      address: addressController.text.trim(),
    );

    if (!mounted) return;
    setState(() => _loading = false);

    if (result != null && result['success'] == true) {
      final retailerId = result['retailer_id'];
      _showOTPDialog(retailerId, emailController.text.trim());
    } else {
      setState(() => message = result?['message'] ?? 'Registration failed');
    }
  }

  Future<void> _showOTPDialog(int retailerId, String email) async {
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
              backgroundColor: Colors.white,
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
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Account verified successfully! Please log in.'),
            backgroundColor: Colors.green,
            duration: Duration(seconds: 3),
          ),
        );

        // Go back to Login
        Navigator.pushAndRemoveUntil(
          context,
          MaterialPageRoute(builder: (_) => const LoginScreen(role: 'retailer')),
              (route) => false,
        );
      }
    }
  }

  InputDecoration _decoration({required String label, required IconData icon}) {
    return InputDecoration(
      labelText: label,
      labelStyle: const TextStyle(color: Colors.grey, fontSize: 14),
      floatingLabelStyle: const TextStyle(color: AppColors.brand, fontSize: 14),
      prefixIcon: Icon(icon, color: Colors.grey, size: 20),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      body: CustomScrollView(
        slivers: [
          SliverAppBar(
            expandedHeight: 200.0,
            floating: false,
            pinned: true,
            backgroundColor: AppColors.brand,
            leading: IconButton(
              icon: const Icon(Icons.arrow_back, color: Colors.white),
              onPressed: () => Navigator.pop(context),
            ),
            flexibleSpace: FlexibleSpaceBar(
              background: Container(
                color: AppColors.brand,
                child: SafeArea(
                  child: Padding(
                    padding: const EdgeInsets.all(24.0),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      mainAxisAlignment: MainAxisAlignment.end,
                      children: [
                        Image.asset('assets/logo.png', height: 40, fit: BoxFit.contain),
                        const SizedBox(height: 16),
                        const Text('Create account', style: TextStyle(fontSize: 28, fontWeight: FontWeight.bold, color: Colors.white)),
                        const SizedBox(height: 4),
                        const Text('Join KimFresh as a retailer partner', style: TextStyle(fontSize: 14, color: Colors.white70)),
                      ],
                    ),
                  ),
                ),
              ),
            ),
          ),
          SliverToBoxAdapter(
            child: Container(
              decoration: const BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.vertical(top: Radius.circular(30)),
              ),
              padding: const EdgeInsets.all(24),
              child: Form(
                key: _formKey,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    const Text('Personal information', style: TextStyle(fontSize: 14, fontWeight: FontWeight.w700, color: AppColors.text)),
                    const SizedBox(height: 16),
                    Row(
                      children: [
                        Expanded(child: TextFormField(controller: firstNameController, validator: (v) => v!.isEmpty ? 'Required' : null, decoration: _decoration(label: 'First name *', icon: Icons.person_outline))),
                        const SizedBox(width: 12),
                        Expanded(child: TextFormField(controller: lastNameController, validator: (v) => v!.isEmpty ? 'Required' : null, decoration: _decoration(label: 'Last name *', icon: Icons.person_outline))),
                      ],
                    ),
                    const SizedBox(height: 16),
                    TextFormField(controller: emailController, validator: (v) => v!.isEmpty ? 'Required' : null, decoration: _decoration(label: 'Email *', icon: Icons.mail_outline)),
                    const SizedBox(height: 16),
                    TextFormField(
                      controller: passwordController,
                      obscureText: _obscure,
                      validator: (v) => v!.length < 6 ? 'Min 6 characters' : null,
                      decoration: InputDecoration(
                        labelText: 'Password *',
                        prefixIcon: const Icon(Icons.lock_outline),
                        suffixIcon: IconButton(
                          icon: Icon(_obscure ? Icons.visibility_off : Icons.visibility),
                          onPressed: () => setState(() => _obscure = !_obscure),
                        ),
                      ),
                    ),
                    const SizedBox(height: 24),

                    const Text('Retailer details', style: TextStyle(fontSize: 14, fontWeight: FontWeight.w700, color: AppColors.text)),
                    const SizedBox(height: 16),
                    TextFormField(
                      controller: phoneController,
                      keyboardType: TextInputType.phone,
                      validator: (v) => v!.isEmpty ? 'Required' : null,
                      decoration: _decoration(label: 'Phone number *', icon: Icons.phone_outlined),
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
                          backgroundColor: AppColors.brand,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                        ),
                        child: _loading ? const CircularProgressIndicator(color: Colors.white) : const Text('Create account', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                      ),
                    ),
                    const SizedBox(height: 24),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        const Text('Already have an account?', style: TextStyle(color: AppColors.muted)),
                        TextButton(
                          onPressed: () => Navigator.pop(context),
                          child: const Text('Sign in', style: TextStyle(color: AppColors.brand, fontWeight: FontWeight.bold)),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}