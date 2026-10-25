import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../services/api.dart';
import '../theme/app_theme.dart';

// 👇 THIS IS THE MAGIC FORMATTER 👇
class LicensePlateFormatter extends TextInputFormatter {
  @override
  TextEditingValue formatEditUpdate(TextEditingValue oldValue, TextEditingValue newValue) {
    // 1. Remove all hyphens and uppercase everything
    String text = newValue.text.replaceAll('-', '').toUpperCase();

    // 2. Limit to 11 characters (1 letter + 10 digits)
    if (text.length > 11) {
      text = text.substring(0, 11);
    }

    // 3. Re-insert hyphens based on length
    String formatted = '';
    for (int i = 0; i < text.length; i++) {
      if (i == 3 || i == 5) {
        formatted += '-';
      }
      formatted += text[i];
    }

    // 4. Return the new text and place cursor at the end
    return TextEditingValue(
      text: formatted,
      selection: TextSelection.collapsed(offset: formatted.length),
    );
  }
}
// 👆 END OF FORMATTER 👆

class SignupScreen extends StatefulWidget {
  @override
  _SignupScreenState createState() => _SignupScreenState();
}

class _SignupScreenState extends State<SignupScreen> {
  final _formKey = GlobalKey<FormState>();

  // Controllers
  final firstNameController = TextEditingController();
  final lastNameController = TextEditingController();
  final emailController = TextEditingController();
  final passwordController = TextEditingController();
  final phoneController = TextEditingController();
  final licenseController = TextEditingController();

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
    licenseController.dispose();
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
      licenseController.text.trim(),
    );

    if (!mounted) return;
    setState(() => _loading = false);

    if (result['success'] == true) {
      _showSuccessDialog();
    } else {
      setState(() => message = result['message'] ?? 'Registration failed');
    }
  }

  void _showSuccessDialog() {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (_) => Dialog(
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(20),
        ),
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 68,
                height: 68,
                decoration: BoxDecoration(
                  color: AppColors.success.withOpacity(0.1),
                  shape: BoxShape.circle,
                ),
                alignment: Alignment.center,
                child: const Icon(
                  Icons.check_circle_rounded,
                  color: AppColors.success,
                  size: 36,
                ),
              ),
              const SizedBox(height: 18),
              const Text(
                'Account created',
                style: TextStyle(
                  fontSize: 20,
                  fontWeight: FontWeight.w700,
                  color: AppColors.textPrimary,
                ),
              ),
              const SizedBox(height: 8),
              const Text(
                'Your driver account is pending admin approval. You will be able to sign in once approved.',
                textAlign: TextAlign.center,
                style: TextStyle(
                  fontSize: 13,
                  height: 1.45,
                  color: AppColors.textSecondary,
                ),
              ),
              const SizedBox(height: 22),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: () {
                    Navigator.pop(context);
                    Navigator.pop(context);
                  },
                  child: const Text('Got it'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final size = MediaQuery.of(context).size;

    return Scaffold(
      backgroundColor: AppColors.canvas,
      body: Stack(
        children: [
          // Burgundy hero header
          Container(
            height: size.height * 0.30,
            decoration: const BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
                colors: [
                  AppColors.burgundy,
                  AppColors.burgundyDark,
                ],
              ),
              borderRadius: BorderRadius.only(
                bottomLeft: Radius.circular(36),
                bottomRight: Radius.circular(36),
              ),
            ),
          ),

          SafeArea(
            child: SingleChildScrollView(
              padding: const EdgeInsets.symmetric(horizontal: 24),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Row(
                    children: [
                      IconButton(
                        onPressed: () => Navigator.pop(context),
                        icon: const Icon(Icons.arrow_back_rounded, color: Colors.white),
                        tooltip: 'Back',
                      ),
                    ],
                  ),
                  const SizedBox(height: 4),

                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 4),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Image.asset('assets/logo.png', width: 120, fit: BoxFit.contain),
                        const SizedBox(height: 16),
                        const Text(
                          'Create account',
                          style: TextStyle(color: Colors.white, fontSize: 28, fontWeight: FontWeight.w800),
                        ),
                        const SizedBox(height: 6),
                        const Text(
                          'Join KimFresh as a driver partner',
                          style: TextStyle(color: Colors.white70, fontSize: 13),
                        ),
                      ],
                    ),
                  ),

                  SizedBox(height: size.height * 0.05),

                  Container(
                    padding: const EdgeInsets.fromLTRB(22, 26, 22, 22),
                    decoration: BoxDecoration(
                      color: AppColors.surface,
                      borderRadius: BorderRadius.circular(24),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withOpacity(0.06),
                          blurRadius: 30,
                          offset: const Offset(0, 10),
                        ),
                      ],
                    ),
                    child: Form(
                      key: _formKey,
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          const _SectionLabel('Personal information'),
                          const SizedBox(height: 14),

                          // First & Last Name Row
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
                                    prefixIcon: Icon(Icons.person_outline_rounded, color: AppColors.textSecondary),
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
                          const SizedBox(height: 14),

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
                              prefixIcon: Icon(Icons.mail_outline_rounded, color: AppColors.textSecondary),
                            ),
                          ),
                          const SizedBox(height: 14),

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
                              prefixIcon: const Icon(Icons.lock_outline_rounded, color: AppColors.textSecondary),
                              suffixIcon: IconButton(
                                icon: Icon(_obscure ? Icons.visibility_off_outlined : Icons.visibility_outlined, color: AppColors.textSecondary),
                                onPressed: () => setState(() => _obscure = !_obscure),
                              ),
                            ),
                          ),

                          const SizedBox(height: 22),
                          const Divider(height: 1),
                          const SizedBox(height: 22),

                          const _SectionLabel('Driver details'),
                          const SizedBox(height: 14),

                          // PHONE NUMBER
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
                              prefixIcon: Icon(Icons.phone_outlined, color: AppColors.textSecondary),
                            ),
                          ),
                          const SizedBox(height: 14),

                          // LICENSE NUMBER (Auto-Formatting)
                          TextFormField(
                            controller: licenseController,
                            textCapitalization: TextCapitalization.characters,
                            inputFormatters: [
                              // Apply our custom formatter here!
                              LicensePlateFormatter(),
                            ],
                            validator: (v) {
                              if (v == null || v.trim().isEmpty) return 'License number is required';
                              final ltoRegex = RegExp(r'^[A-Z][0-9]{2}-[0-9]{2}-[0-9]{6}$');
                              if (!ltoRegex.hasMatch(v.trim().toUpperCase())) {
                                return 'Format must be N01-23-456789';
                              }
                              return null;
                            },
                            decoration: const InputDecoration(
                              labelText: 'License number *',
                              hintText: 'N01-23-456789',
                              prefixIcon: Icon(Icons.badge_outlined, color: AppColors.textSecondary),
                            ),
                          ),

                          if (message.isNotEmpty) ...[
                            const SizedBox(height: 16),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                              decoration: BoxDecoration(
                                color: AppColors.danger.withOpacity(0.08),
                                borderRadius: BorderRadius.circular(10),
                                border: Border.all(color: AppColors.danger.withOpacity(0.3)),
                              ),
                              child: Row(
                                children: [
                                  const Icon(Icons.error_outline_rounded, color: AppColors.danger, size: 18),
                                  const SizedBox(width: 8),
                                  Expanded(
                                    child: Text(
                                      message,
                                      style: const TextStyle(color: AppColors.danger, fontSize: 13, fontWeight: FontWeight.w500),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ],

                          const SizedBox(height: 24),

                          SizedBox(
                            height: 52,
                            child: ElevatedButton(
                              onPressed: _loading ? null : register,
                              style: ElevatedButton.styleFrom(
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                                textStyle: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700),
                              ),
                              child: _loading
                                  ? const SizedBox(
                                height: 22,
                                width: 22,
                                child: CircularProgressIndicator(strokeWidth: 2.4, color: Colors.white),
                              )
                                  : const Text('Create account'),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),

                  const SizedBox(height: 20),

                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const Text('Already have an account?', style: TextStyle(color: AppColors.textSecondary, fontSize: 13)),
                      TextButton(
                        onPressed: () => Navigator.pop(context),
                        child: const Text('Sign in', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: AppColors.burgundy)),
                      ),
                    ],
                  ),
                  const SizedBox(height: 24),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _SectionLabel extends StatelessWidget {
  final String text;
  const _SectionLabel(this.text);

  @override
  Widget build(BuildContext context) {
    return Text(
      text,
      style: const TextStyle(
        fontSize: 12,
        fontWeight: FontWeight.w700,
        color: AppColors.textSecondary,
        letterSpacing: 0.6,
      ),
    );
  }
}