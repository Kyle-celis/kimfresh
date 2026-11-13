import 'package:flutter/material.dart';
import '../theme/app_theme.dart';
import 'login_screen.dart';

class RoleSelectionScreen extends StatelessWidget {
  const RoleSelectionScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.canvas,
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(24.0),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Image.asset(
                'assets/logo.png',
                height: 100,
                fit: BoxFit.contain,
                color: AppColors.textPrimary,
              ),
              const SizedBox(height: 30),
              const Text(
                'Welcome to KimFresh',
                style: TextStyle(fontSize: 26, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
              ),
              const SizedBox(height: 10),
              const Text(
                'Please select how you want to use the app.',
                style: TextStyle(fontSize: 16, color: AppColors.textSecondary),
              ),
              const SizedBox(height: 50),
              
              GestureDetector(
                onTap: () {
                  Navigator.pushReplacement(
                    context,
                    MaterialPageRoute(builder: (_) => const LoginScreen(role: 'driver')),
                  );
                },
                child: Container(
                  width: double.infinity,
                  padding: const EdgeInsets.symmetric(vertical: 30),
                  decoration: BoxDecoration(
                    color: AppColors.burgundy,
                    borderRadius: BorderRadius.circular(16),
                    boxShadow: [
                      BoxShadow(color: AppColors.burgundy.withOpacity(0.3), blurRadius: 10, offset: const Offset(0, 4)),
                    ],
                  ),
                  child: const Column(
                    children: [
                      Icon(Icons.local_shipping, color: Colors.white, size: 48),
                      SizedBox(height: 12),
                      Text('I am a Driver', style: TextStyle(color: Colors.white, fontSize: 22, fontWeight: FontWeight.bold)),
                    ],
                  ),
                ),
              ),
              
              const SizedBox(height: 20),
              
              GestureDetector(
                onTap: () {
                  Navigator.pushReplacement(
                    context,
                    MaterialPageRoute(builder: (_) => const LoginScreen(role: 'retailer')),
                  );
                },
                child: Container(
                  width: double.infinity,
                  padding: const EdgeInsets.symmetric(vertical: 30),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: AppColors.burgundy, width: 2),
                    boxShadow: [
                      BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 10, offset: const Offset(0, 4)),
                    ],
                  ),
                  child: const Column(
                    children: [
                      Icon(Icons.storefront, color: AppColors.burgundy, size: 48),
                      SizedBox(height: 12),
                      Text('I am a Retailer', style: TextStyle(color: AppColors.burgundy, fontSize: 22, fontWeight: FontWeight.bold)),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}